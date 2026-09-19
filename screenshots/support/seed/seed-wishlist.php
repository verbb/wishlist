// craft-screenshots: saved-entry-list
/** Seed a single populated Wishlist list using ordinary Craft entries. */

use craft\elements\Entry;
use craft\elements\User;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\Json;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\models\ListType;
use verbb\wishlist\Wishlist;

$entries = Craft::$app->getEntries();
$elements = Craft::$app->getElements();
$fields = Craft::$app->getFields();
$site = Craft::$app->getSites()->getPrimarySite();
$sectionHandle = 'wishlistScreenshotReads';

$listType = Wishlist::$plugin->getListTypes()->getDefaultListType();

if (!$listType) {
    $listLayout = new FieldLayout(['type' => ListElement::class]);
    $listTab = new FieldLayoutTab(['name' => Craft::t('app', 'Content'), 'layout' => $listLayout]);
    $listTab->setElements([new TitleField()]);
    $listLayout->setTabs([$listTab]);
    $fields->saveLayout($listLayout);

    $itemLayout = new FieldLayout(['type' => Item::class]);
    $itemTab = new FieldLayoutTab(['name' => Craft::t('app', 'Content'), 'layout' => $itemLayout]);
    $itemLayout->setTabs([$itemTab]);
    $fields->saveLayout($itemLayout);

    $listType = new ListType([
        'name' => 'Wishlist',
        'handle' => 'wishlist',
        'default' => true,
        'fieldLayoutId' => $listLayout->id,
        'itemFieldLayoutId' => $itemLayout->id,
    ]);

    if (!Wishlist::$plugin->getListTypes()->saveListType($listType)) {
        throw new RuntimeException('Unable to save the Wishlist screenshot list type: ' . Json::encode($listType->getErrors()));
    }

    $listType = Wishlist::$plugin->getListTypes()->getListTypeByHandle('wishlist');
}

// Preserve the real edit form while ensuring its native title control exists
// in isolated installs whose initial list layout was created without one.
$listLayout = $listType->getListFieldLayout();
$listTab = new FieldLayoutTab(['name' => Craft::t('app', 'Content'), 'layout' => $listLayout]);
$listTab->setElements([new TitleField()]);
$listLayout->setTabs([$listTab]);
$fields->saveLayout($listLayout);

$section = $entries->getSectionByHandle($sectionHandle);

if (!$section) {
    $entryType = new EntryType(['name' => 'Saved Reads', 'handle' => $sectionHandle . 'Type']);
    $entryLayout = new FieldLayout(['type' => Entry::class]);
    $entryTab = new FieldLayoutTab(['name' => Craft::t('app', 'Content'), 'layout' => $entryLayout]);
    $entryTab->setElements([new EntryTitleField()]);
    $entryLayout->setTabs([$entryTab]);
    $entryType->setFieldLayout($entryLayout);

    if (!$entries->saveEntryType($entryType)) {
        throw new RuntimeException('Unable to save the Wishlist screenshot entry type: ' . Json::encode($entryType->getErrors()));
    }

    $section = new Section(['name' => 'Saved Reads', 'handle' => $sectionHandle, 'type' => Section::TYPE_CHANNEL]);
    $section->setEntryTypes([$entryType]);
    $section->setSiteSettings([new Section_SiteSettings([
        'siteId' => $site->id,
        'enabledByDefault' => true,
        'hasUrls' => false,
    ])]);

    if (!$entries->saveSection($section)) {
        throw new RuntimeException('Unable to save the Wishlist screenshot section: ' . Json::encode($section->getErrors()));
    }
}

$entryType = $entries->getEntryTypesBySectionId($section->id)[0] ?? null;
$titles = [
    'A guide to Melbourne’s hidden gardens',
    'Designing for calmer digital spaces',
    'How neighbourhood markets bring people together',
    'The beginner’s guide to native planting',
    'Six coastal walks for a quiet weekend',
];
$savedEntries = [];

foreach ($titles as $title) {
    $entry = Entry::find()->sectionId($section->id)->title($title)->siteId($site->id)->status(null)->one();

    if (!$entry) {
        $entry = new Entry([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
            'siteId' => $site->id,
            'title' => $title,
            'enabled' => true,
        ]);

        if (!$elements->saveElement($entry)) {
            throw new RuntimeException('Unable to save a Wishlist screenshot entry: ' . Json::encode($entry->getErrors()));
        }
    }

    $savedEntries[] = $entry;
}

$owner = User::find()->admin(true)->status(null)->one();

if (!$owner) {
    throw new RuntimeException('Unable to find the screenshot administrator for the Wishlist list.');
}

$list = ListElement::find()
    ->typeId($listType->id)
    ->title('Saved for the weekend')
    ->status(null)
    ->one();

if (!$list) {
    $list = new ListElement([
        'typeId' => $listType->id,
        'siteId' => $site->id,
        'title' => 'Saved for the weekend',
        'reference' => 'SAVEDREADS',
        'lastIp' => '203.0.113.42',
        'userId' => $owner->id,
        'default' => true,
        'enabled' => true,
    ]);

    if (!Wishlist::$plugin->getLists()->saveElement($list)) {
        throw new RuntimeException('Unable to save the Wishlist screenshot list: ' . Json::encode($list->getErrors()));
    }
}

foreach ($savedEntries as $savedEntry) {
    $item = Item::find()
        ->listId($list->id)
        ->elementId($savedEntry->id)
        ->elementSiteId($savedEntry->siteId)
        ->status(null)
        ->one();

    if (!$item) {
        $item = Wishlist::$plugin->getItems()->createItem($list, $savedEntry, ['enabled' => true]);

        if (!Wishlist::$plugin->getItems()->saveElement($item)) {
            throw new RuntimeException('Unable to save a Wishlist screenshot item: ' . Json::encode($item->getErrors()));
        }
    }
}

$itemCount = Item::find()->listId($list->id)->status(null)->count();

echo Json::encode([
    'listRoute' => '/admin/wishlist/lists/' . $listType->handle . '/' . $list->id,
    'itemCount' => (int)$itemCount,
]);
