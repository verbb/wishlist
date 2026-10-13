<?php
// Run with: WISHLIST_TEST_VENDOR=/path/to/vendor php tests/security/element-read-security-fixture.php
namespace {
    $vendor = getenv('WISHLIST_TEST_VENDOR');
    if (!$vendor || !is_file($vendor . '/yiisoft/yii2/Yii.php')) {
        throw new RuntimeException('Set WISHLIST_TEST_VENDOR to an installed Craft vendor directory.');
    }
    require $vendor . '/yiisoft/yii2/Yii.php';
    class Craft extends \Yii {}
}
namespace craft\web {
    class Application {
        public mixed $controller;
        public mixed $request;
        public mixed $user;
        public function getRequest() { return $this->request; }
        public function getUser() { return $this->user; }
    }
}
namespace craft\controllers {
    class Controller { public mixed $action; }
    class ElementIndexesController extends Controller {}
    class ElementSelectorModalsController extends Controller {}
    class RelationalFieldsController extends Controller {}
    class AppController extends Controller {}
    if (!getenv('WISHLIST_TEST_WITHOUT_ELEMENT_SEARCH')) {
        class ElementSearchController extends Controller {}
    }
}
namespace craft\db { class QueryAbortedException extends \RuntimeException {} }
namespace craft\helpers {
    class Db {
        public static function parseParam($column, $value) { return [$column => $value]; }
    }
}
namespace craft\elements\db {
    class ElementQuery {
        public mixed $query;
        public mixed $subQuery;
        public mixed $dateCreated = null;
        public mixed $trashed = false;
        protected function joinElementTable($table): void {
            $this->query = new \yii\db\Query();
            $this->subQuery = (new \yii\db\Query())->from($table)->select("$table.id");
        }
        protected function beforePrepare(): bool { return true; }
    }
}
namespace verbb\wishlist\elements {
    class Item {}
    class ListElement {}
    class CustomItem extends Item {}
    class CustomList extends ListElement {}
}
namespace verbb\wishlist {
    class Wishlist { public static mixed $plugin; }
}
namespace {
    use craft\controllers\{AppController, ElementIndexesController, ElementSelectorModalsController, RelationalFieldsController};
    use verbb\wishlist\elements\{Item, ListElement, CustomItem, CustomList};
    use verbb\wishlist\elements\db\{ItemQuery, ListQuery};
    use verbb\wishlist\Wishlist;

    $root = dirname(__DIR__, 2);
    if (is_file($root . '/src/helpers/ElementPermissions.php')) {
        require $root . '/src/helpers/ElementPermissions.php';
    }
    require $root . '/src/elements/db/ItemQuery.php';
    require $root . '/src/elements/db/ListQuery.php';

    class Request {
        public function __construct(public array $query = [], public array $body = [], public bool $cp = true) {}
        public function getParam($key) { return $this->query[$key] ?? $this->body[$key] ?? null; }
        public function getQueryParam($key) { return $this->query[$key] ?? null; }
        public function getBodyParam($key) { return $this->body[$key] ?? null; }
        public function getIsCpRequest() { return $this->cp; }
    }
    class UserSession {
        public bool $loggedIn = true;
        public function getIdentity() { return $this->loggedIn ? (object)['id' => 1] : null; }
    }
    class ListTypes {
        public array $ids = [];
        public function getEditableListTypeIds() { return $this->ids; }
    }
    $types = new ListTypes();
    Wishlist::$plugin = new class($types) {
        public function __construct(private ListTypes $types) {}
        public function getListTypes() { return $this->types; }
    };
    $db = new \yii\db\Connection(['dsn' => 'sqlite::memory:']);
    $db->open();
    foreach ([
        'CREATE TABLE wishlist_lists (id INTEGER, typeId INTEGER)',
        'CREATE TABLE wishlist_items (id INTEGER, listId INTEGER, elementId INTEGER, elementSiteId INTEGER)',
        'CREATE TABLE elements (id INTEGER, dateDeleted TEXT)',
        'CREATE TABLE elements_sites (elementId INTEGER, siteId INTEGER, title TEXT)',
        'INSERT INTO wishlist_lists VALUES (10, 1), (20, 2)',
        'INSERT INTO wishlist_items VALUES (100, 10, 1000, 1), (200, 20, 2000, 1)',
        'INSERT INTO elements VALUES (10, NULL), (20, NULL)',
        "INSERT INTO elements_sites VALUES (1000, 1, 'Allowed product'), (2000, 1, 'Private product')",
    ] as $sql) { $db->createCommand($sql)->execute(); }
    $count = 0;
    function check(string $label, bool $ok): void {
        global $count;
        if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
        $count++;
        echo 'PASS: ' . $label . "\n";
    }
    function context(string $controller, string $action, array $query = [], array $body = [], bool $cp = true): void {
        $app = new \craft\web\Application();
        $app->controller = new $controller();
        $app->controller->action = (object)['id' => $action];
        $app->request = new Request($query, $body, $cp);
        $app->user = new UserSession();
        Craft::$app = $app;
    }
    function prepare(object $query): array {
        global $db;
        try {
            $method = new ReflectionMethod($query, 'beforePrepare');
            if (!$method->invoke($query)) { return []; }
            return array_map('intval', $query->subQuery->column($db));
        } catch (\craft\db\QueryAbortedException) { return []; }
    }
    // Existing index control passes before the fix; the alternate endpoint then reproduces the leak.
    context(ElementIndexesController::class, 'get-elements', [], ['elementType' => Item::class]);
    check('Existing item index denies a user with no list-type permission', prepare(new ItemQuery()) === []);
    context(AppController::class, 'render-elements', [], ['elements' => [['type' => Item::class], ['type' => ListElement::class]]]);
    check('Mixed chip request cannot retrieve restricted items', prepare(new ItemQuery()) === []);
    check('Mixed chip request cannot retrieve restricted lists', prepare(new ListQuery()) === []);

    $routes = [
        [AppController::class, 'render-elements'],
        [AppController::class, 'render-components'],
        [ElementSelectorModalsController::class, 'body'],
        [RelationalFieldsController::class, 'structured-input-html'],
    ];
    if (class_exists('craft\\controllers\\ElementSearchController')) {
        $routes[] = ['craft\\controllers\\ElementSearchController', 'search'];
    }
    foreach ($routes as [$controller, $action]) {
        foreach ([[], [1], [1, 2]] as $allowed) {
            $types->ids = $allowed;
            foreach ([ItemQuery::class => Item::class, ListQuery::class => ListElement::class] as $queryClass => $elementClass) {
                foreach ([[], ['elementType' => 'craft\\elements\\Entry'], ['elementType' => []]] as $decoy) {
                    context($controller, $action, $decoy, ['elementType' => $elementClass]);
                    $query = new $queryClass();
                    if ($query instanceof ListQuery) { $query->editable(false); }
                    $expected = $query instanceof ListQuery ? array_map(fn($i) => $i * 10, $allowed) : array_map(fn($i) => $i * 100, $allowed);
                    check("$action $queryClass filters actual result rows with URL decoy", prepare($query) === $expected);
                }
            }
        }
        $types->ids = [1];
        context($controller, $action);
        $list = new ListQuery(); $list->typeId(2);
        $item = new ItemQuery(); $item->listTypeId(2); $item->listId(20);
        check("$action explicit restricted list criteria cannot widen permission", prepare($list) === []);
        check("$action explicit restricted item criteria cannot widen permission", prepare($item) === []);
        $types->ids = [];
        Craft::$app->user->loggedIn = false;
        check("$action guest list query returns nothing", prepare(new ListQuery()) === []);
        check("$action guest item query returns nothing", prepare(new ItemQuery()) === []);
    }
    $types->ids = [1];
    foreach ([[ListElement::class, ListQuery::class], [CustomList::class, ListQuery::class], [Item::class, ItemQuery::class], [CustomItem::class, ItemQuery::class]] as [$elementClass, $queryClass]) {
        context(ElementIndexesController::class, 'get-elements', [], ['elementType' => $elementClass]);
        check('Index retains subclass permission filtering', count(prepare(new $queryClass())) === 1);
    }
    context(ElementIndexesController::class, 'export', ['elementType' => ListElement::class], ['elementType' => Item::class]);
    $export = new ListQuery(); prepare($export);
    check('Raw list export still excludes guest authority', !in_array('wishlist_lists.sessionId', $export->query->select, true));
    check('Ignored body type cannot restrict item lifecycle queries during list actions', prepare(new ItemQuery()) === [100, 200]);
    context(ElementIndexesController::class, 'perform-action', [], ['elementType' => ListElement::class]);
    check('List actions preserve internal item queries', prepare(new ItemQuery()) === [100, 200]);
    context(ElementIndexesController::class, 'perform-action', [], ['elementType' => Item::class]);
    check('Item actions preserve internal list queries', prepare(new ListQuery()) === [10, 20]);
    foreach ([[AppController::class, 'other'], ['craft\\controllers\\Controller', 'save'], ['craft\\controllers\\Controller', 'purge']] as [$controller, $action]) {
        context($controller, $action);
        check("$action preserves internal list queries", prepare(new ListQuery()) === [10, 20]);
        check("$action preserves internal item queries", prepare(new ItemQuery()) === [100, 200]);
    }
    context(AppController::class, 'render-elements', [], [], false);
    check('Site template list queries unchanged', prepare(new ListQuery()) === [10, 20]);
    check('Site template item queries unchanged', prepare(new ItemQuery()) === [100, 200]);
    context('craft\\controllers\\Controller', 'save');
    $explicit = new ListQuery(); $explicit->editable();
    check('Explicit editable queries still restrict internal results', prepare($explicit) === [10]);
    Craft::$app = new \stdClass();
    check('Console list queries unchanged', prepare(new ListQuery()) === [10, 20]);
    check('Console item queries unchanged', prepare(new ItemQuery()) === [100, 200]);
    echo "Completed $count checks.\n";
}
