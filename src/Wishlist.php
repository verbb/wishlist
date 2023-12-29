<?php
namespace verbb\wishlist;

use verbb\wishlist\base\PluginTrait;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\elements\Item;
use verbb\wishlist\fieldlayoutelements\OptionsField;
use verbb\wishlist\gql\interfaces\ListInterface;
use verbb\wishlist\gql\interfaces\ItemInterface;
use verbb\wishlist\gql\queries\ListQuery;
use verbb\wishlist\gql\queries\ItemQuery;
use verbb\wishlist\helpers\ProjectConfigData;
use verbb\wishlist\models\Settings;
use verbb\wishlist\services\ListTypes;
use verbb\wishlist\variables\WishlistVariable;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\console\Controller as ConsoleController;
use craft\console\controllers\ResaveController;
use craft\events\DefineConsoleActionsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Gc;
use craft\services\Gql;
use craft\services\ProjectConfig;
use craft\services\SystemMessages;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;
use yii\web\User;

class Wishlist extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;
    public string $schemaVersion = '1.1.0';
    public string $minVersionRequired = '1.4.11';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerSessionEventListeners();
        $this->_registerEmailMessages();
        $this->_registerVariables();
        $this->_registerElementTypes();
        $this->_registerProjectConfigEventHandlers();
        $this->_registerGarbageCollection();
        $this->_registerTemplateHooks();
        $this->_registerGraphQl();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
            $this->_registerFieldLayoutListener();
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->_registerResaveCommand();
        }

        if (Craft::$app->getEdition() === Craft::Pro) {
            $this->_registerPermissions();
        }
    }

    public function getPluginName(): string
    {
        return Craft::t('wishlist', $this->getSettings()->pluginName);
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('wishlist/settings'));
    }

    public function getCpNavItem(): ?array
    {
        $nav = parent::getCpNavItem();

        $nav['label'] = $this->getPluginName();

        if (Craft::$app->getUser()->checkPermission('wishlist-manageLists')) {
            $nav['subnav']['lists'] = [
                'label' => Craft::t('wishlist', 'Lists'),
                'url' => 'wishlist/lists',
            ];
        }

        if (Craft::$app->getUser()->checkPermission('wishlist-manageListTypes')) {
            $nav['subnav']['listTypes'] = [
                'label' => Craft::t('wishlist', 'List Types'),
                'url' => 'wishlist/list-types',
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $nav['subnav']['settings'] = [
                'label' => Craft::t('wishlist', 'Settings'),
                'url' => 'wishlist/settings',
            ];
        }

        return $nav;
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'wishlist' => 'wishlist/lists/index',

                'wishlist/lists/<listTypeHandle:{handle}>' => 'wishlist/lists/index',
                'wishlist/lists/<listTypeHandle:{handle}>/new' => 'wishlist/lists/edit-list',
                'wishlist/lists/<listTypeHandle:{handle}>/<listId:\d+>' => 'wishlist/lists/edit-list',
                'wishlist/lists/<listTypeHandle:{handle}>/<listId:\d+>/items/new' => 'wishlist/items/edit-item',
                'wishlist/lists/<listTypeHandle:{handle}>/<listId:\d+>/items/<itemId:\d+>' => 'wishlist/items/edit-item',

                'wishlist/list-types' => 'wishlist/list-types/list-type-index',
                'wishlist/list-types/<listTypeId:\d+>' => 'wishlist/list-types/edit-list-type',
                'wishlist/list-types/new' => 'wishlist/list-types/edit-list-type',

                'wishlist/settings' => 'wishlist/settings/index',
                'wishlist/settings/general' => 'wishlist/settings/index',
            ]);
        });
    }

    private function _registerEmailMessages(): void
    {
        Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, function(RegisterEmailMessagesEvent $event) {
            $event->messages = array_merge($event->messages, [
                [
                    'key' => 'wishlist_share_list',
                    'heading' => Craft::t('wishlist', 'wishlist_share_list_heading'),
                    'subject' => Craft::t('wishlist', 'wishlist_share_list_subject'),
                    'body' => Craft::t('wishlist', 'wishlist_share_list_body'),
                ],
            ]);
        });
    }

    private function _registerPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $listTypes = Wishlist::$plugin->getListTypes()->getAllListTypes();

            $listTypePermissions = [];
            foreach ($listTypes as $listType) {
                $suffix = ':' . $listType->uid;
                $listTypePermissions['wishlist-manageListType' . $suffix] = ['label' => Craft::t('wishlist', 'Manage “{type}” lists', ['type' => $listType->name])];
            }

            $event->permissions[] = [
                'heading' => Craft::t('wishlist', 'Wishlist'),
                'permissions' => [
                    'wishlist-manageListTypes' => ['label' => Craft::t('wishlist', 'Manage list types')],
                    'wishlist-manageLists' => ['label' => Craft::t('wishlist', 'Manage lists'), 'nested' => $listTypePermissions],
                ],
            ];
        });
    }

    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('wishlist', WishlistVariable::class);
        });
    }

    private function _registerElementTypes(): void
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = ListElement::class;
            $event->types[] = Item::class;
        });
    }

    private function _registerProjectConfigEventHandlers(): void
    {
        $projectConfigService = Craft::$app->getProjectConfig();

        $listTypeService = $this->getListTypes();
        $projectConfigService->onAdd(ListTypes::CONFIG_LISTTYPES_KEY . '.{uid}', [$listTypeService, 'handleChangedListType'])
            ->onUpdate(ListTypes::CONFIG_LISTTYPES_KEY . '.{uid}', [$listTypeService, 'handleChangedListType'])
            ->onRemove(ListTypes::CONFIG_LISTTYPES_KEY . '.{uid}', [$listTypeService, 'handleDeletedListType']);

        Event::on(Fields::class, Fields::EVENT_AFTER_DELETE_FIELD, [$listTypeService, 'pruneDeletedField']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $event): void {
            $event->config['wishlist'] = ProjectConfigData::rebuildProjectConfig();
        });
    }

    private function _registerGarbageCollection(): void
    {
        Event::on(Gc::class, Gc::EVENT_RUN, function() {
            // Delete lists that meet the purge settings
            Wishlist::$plugin->getLists()->purgeInactiveLists();
        });
    }

    private function _registerSessionEventListeners(): void
    {
        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            Event::on(User::class, User::EVENT_AFTER_LOGIN, [$this->getLists(), 'loginHandler']);
        }
    }

    private function _registerResaveCommand(): void
    {
        if (!Craft::$app instanceof ConsoleApplication) {
            return;
        }

        Event::on(ResaveController::class, ConsoleController::EVENT_DEFINE_ACTIONS, function(DefineConsoleActionsEvent $event) {
            $event->actions['wishlist-items'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;

                    $criteria = [];

                    if ($controller->listId !== null) {
                        $criteria['listId'] = explode(',', $controller->listId);
                    }

                    return $controller->resaveElements(Item::class, $criteria);
                },
                'options' => ['listId'],
                'helpSummary' => 'Re-saves Wishlist items.',
                'optionsHelp' => [
                    'type' => 'The list type ID of the items to resave.',
                ],
            ];

            $event->actions['wishlist-lists'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;
                    
                    return $controller->resaveElements(ListElement::class);
                },
                'helpSummary' => 'Re-saves Wishlist lists.',
            ];
        });
    }

    private function _registerGraphQl(): void
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, function(RegisterGqlTypesEvent $event) {
            $event->types[] = ListInterface::class;
            $event->types[] = ItemInterface::class;
        });

        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function(RegisterGqlQueriesEvent $event) {
            foreach (ListQuery::getQueries() as $key => $value) {
                $event->queries[$key] = $value;
            }

            foreach (ItemQuery::getQueries() as $key => $value) {
                $event->queries[$key] = $value;
            }
        });

        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS, function(RegisterGqlSchemaComponentsEvent $event) {
            $listTypes = Wishlist::$plugin->getListTypes()->getAllListTypes();

            if (!empty($listTypes)) {
                $label = Craft::t('wishlist', 'Wishlist');
                $event->queries[$label]['wishlistListTypes.all:read'] = ['label' => Craft::t('wishlist', 'View all wishlists')];

                foreach ($listTypes as $listType) {
                    $suffix = 'wishlistListTypes.' . $listType->uid;

                    $event->queries[$label][$suffix . ':read'] = [
                        'label' => Craft::t('wishlist', 'View wishlist type - {listType}', ['listType' => Craft::t('site', $listType->name)]),
                    ];
                }
            }
        });
    }

    private function _registerTemplateHooks(): void
    {
        if ($this->getSettings()->showListInfoTab) {
            Craft::$app->getView()->hook('cp.users.edit', [$this->getLists(), 'addEditUserListInfoTab']);
            Craft::$app->getView()->hook('cp.users.edit.content', [$this->getLists(), 'addEditUserListInfoTabContent']);
        }
    }

    private function _registerFieldLayoutListener(): void
    {
        Event::on(FieldLayout::class, FieldLayout::EVENT_DEFINE_NATIVE_FIELDS, function(DefineFieldLayoutFieldsEvent $event): void {
            $fieldLayout = $event->sender;

            if ($fieldLayout->type == Item::class) {
                $event->fields[] = OptionsField::class;
            }
        });
    }

}
