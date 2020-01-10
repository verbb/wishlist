<?php
namespace verbb\wishlist;

use verbb\wishlist\base\PluginTrait;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\elements\Item;
use verbb\wishlist\helpers\ProjectConfigData;
use verbb\wishlist\models\Settings;
use verbb\wishlist\services\ListTypes;
use verbb\wishlist\variables\WishlistVariable;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Gc;
use craft\services\ProjectConfig;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;
use yii\web\User;

class Wishlist extends Plugin
{
    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.0.1';
    public $hasCpSettings = true;
    public $hasCpSection = true;


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_setLogging();
        $this->_registerCpRoutes();
        $this->_registerPermissions();
        $this->_registerVariables();
        $this->_registerElementTypes();
        $this->_registerProjectConfigEventListeners();
        $this->_registerGarbageCollection();
    }

    public function getPluginName()
    {
        return Craft::t('wishlist', $this->getSettings()->pluginName);
    }

    public function getSettingsResponse()
    {
        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('wishlist/settings'));
    }

    public function getCpNavItem(): array
    {
        $navItems = parent::getCpNavItem();

        $navItems['label'] = $this->getPluginName();

        if (Craft::$app->getUser()->checkPermission('wishlist-manageLists')) {
            $navItems['subnav']['lists'] = [
                'label' => Craft::t('wishlist', 'Lists'),
                'url' => 'wishlist/lists',
            ];
        }

        if (Craft::$app->getUser()->checkPermission('wishlist-manageListTypes')) {
            $navItems['subnav']['listTypes'] = [
                'label' => Craft::t('wishlist', 'List Types'),
                'url' => 'wishlist/list-types',
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $navItems['subnav']['settings'] = [
                'label' => Craft::t('wishlist', 'Settings'),
                'url' => 'wishlist/settings',
            ];
        }

        return $navItems;
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'wishlist' => 'wishlist/lists/index',
                'wishlist/lists/<listTypeHandle:{handle}>' => 'wishlist/lists/index',
                'wishlist/lists/<listTypeHandle:{handle}>/new' => 'wishlist/lists/edit-list',
                'wishlist/lists/<listTypeHandle:{handle}>/<listId:\d+>' => 'wishlist/lists/edit-list',
                'wishlist/lists/<listTypeHandle:{handle}>/<listId:\d+>/items/<itemId:\d+>' => 'wishlist/items/edit-item',
                'wishlist/list-types' => 'wishlist/list-types/list-type-index',
                'wishlist/list-types/<listTypeId:\d+>' => 'wishlist/list-types/edit-list-type',
                'wishlist/list-types/new' => 'wishlist/list-types/edit-list-type',
                'wishlist/settings' => 'wishlist/default/settings',
            ]);
        });
    }

    private function _registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $listTypes = Wishlist::getInstance()->getListTypes()->getAllListTypes();

            $listTypePermissions = [];
            foreach ($listTypes as $id => $listType) {
                $suffix = ':' . $id;
                $listTypePermissions['wishlist-manageListType' . $suffix] = ['label' => Craft::t('wishlist', 'Manage “{type}” lists', ['type' => $listType->name])];
            }

            $event->permissions[Craft::t('wishlist', 'Wishlist')] = [
                'wishlist-manageListTypes' => ['label' => Craft::t('wishlist', 'Manage list types')],
                'wishlist-manageLists' => ['label' => Craft::t('wishlist', 'Manage lists'), 'nested' => $listTypePermissions],
            ];
        });
    }

    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('wishlist', WishlistVariable::class);
        });
    }

    private function _registerElementTypes()
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = ListElement::class;
            $event->types[] = Item::class;
        });
    }

    private function _registerProjectConfigEventListeners()
    {
        $projectConfigService = Craft::$app->getProjectConfig();

        $listTypeService = $this->getListTypes();
        $projectConfigService->onAdd(ListTypes::CONFIG_LISTTYPES_KEY . '.{uid}', [$listTypeService, 'handleChangedListType'])
            ->onUpdate(ListTypes::CONFIG_LISTTYPES_KEY . '.{uid}', [$listTypeService, 'handleChangedListType'])
            ->onRemove(ListTypes::CONFIG_LISTTYPES_KEY . '.{uid}', [$listTypeService, 'handleDeletedListType']);

        Event::on(Fields::class, Fields::EVENT_AFTER_DELETE_FIELD, [$listTypeService, 'pruneDeletedField']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function (RebuildConfigEvent $event) {
            $event->config['wishlist'] = ProjectConfigData::rebuildProjectConfig();
        });
    }

    private function _registerGarbageCollection()
    {
        Event::on(Gc::class, Gc::EVENT_RUN, function() {
            // Deletes lists that meet the purge settings
            Wishlist::$plugin->getLists()->purgeInactiveLists();
        });
    }
}
