<?php
// Run with WISHLIST_TEST_VENDOR pointing to a Craft vendor directory. No site or database is modified.
namespace {
    $vendor = getenv('WISHLIST_TEST_VENDOR');
    if (!$vendor || !is_file($vendor . '/autoload.php')) { throw new RuntimeException('Set WISHLIST_TEST_VENDOR.'); }
    require $vendor . '/autoload.php';
    require_once $vendor . '/yiisoft/yii2/Yii.php';
    class Craft extends \Yii {}
}
namespace craft\base {
    interface ElementInterface {}
    interface NestedElementInterface extends ElementInterface {}
    class Element implements ElementInterface { const STATUS_ENABLED = 'enabled'; }
    class Event { public static function hasHandlers($class, $name) { return false; } }
}
namespace craft\models { class GqlSchema {} }
namespace craft\elements {
    class Entry extends \craft\base\Element {
        const STATUS_LIVE = 'live';
        public int $sectionId = 1;
        public int $siteId = 1;
        public string $status = 'live';
        public bool $draft = false;
        public bool $revision = false;
        public function __construct(public int $id, public string $title) {}
        public static function isLocalized() { return true; }
        public function getSection() { return (object)['uid' => (string)$this->sectionId]; }
        public function getIsDraft() { return $this->draft; }
        public function getIsRevision() { return $this->revision; }
        public function getStatus() { return $this->status; }
    }
    class ElementCollection extends \ArrayObject { public static function empty() { return new self(); } }
}
namespace craft\helpers {
    class Gql {
        public static array $scopes = ['wishlistListTypes.all:read', 'sections.1:read', 'sites.1:read'];
        public static function extractAllowedEntitiesFromSchema($action = null) { return ['wishlistListTypes' => ['all']]; }
        public static function canSchema($scope, $action = 'read', $schema = null) { return in_array("$scope:$action", self::$scopes, true); }
        public static function canQueryDrafts($schema = null) { return self::canSchema('drafts'); }
        public static function canQueryRevisions($schema = null) { return self::canSchema('revisions'); }
        public static function canQueryInactiveElements($schema = null) { return self::canSchema('inactive'); }
        public static function getFieldNameWithAlias($info, $source, $context) { return $info->fieldName; }
        public static function applyDirectives($source, $info, $value) { return $value; }
    }
    class Db { public static function idsByUids($table, $uids) { return [1]; } }
}
namespace craft\gql {
    class ArgumentManager { public function prepareArguments($args) { return $args; } }
    class ElementQueryConditionBuilder {
        public function setResolveInfo($info) {}
        public function setArgumentManager($manager) {}
        public function extractQueryConditions($field) { return []; }
    }
}
namespace craft\gql\interfaces {
    class Element {
        public static function getType() { return $GLOBALS['linkedType']; }
        public static function getFieldDefinitions(): array {
            return ['id' => ['type' => \GraphQL\Type\Definition\Type::int()], 'title' => ['type' => \GraphQL\Type\Definition\Type::string()]];
        }
    }
}
namespace craft\elements\db {
    class ElementQuery extends \yii\db\Query implements \IteratorAggregate {
        public function getIterator(): \ArrayIterator { return new \ArrayIterator($this->all()); }
        public mixed $title = null;
        public mixed $search = null;
        public mixed $elementId = null;
        public mixed $elementClass = null;
        public ?array $cached = null;
        public function __construct($config = []) { parent::__construct($config); $this->from('wishlist_items'); }
        public function __call($name, $args) {
            if (in_array($name, ['id', 'listId', 'title', 'search', 'elementId', 'elementClass'], true)) {
                $value = $args[0];
                if (property_exists($this, $name)) { $this->$name = $value; }
                if ($name === 'search') { $this->andWhere(['like', 'title', is_array($value) ? $value['query'] : $value]); }
                else { $this->andWhere(['wishlist_items.' . $name => $value]); }
                return $this;
            }
            return parent::__call($name, $args);
        }
        public function populate($rows) {
            return array_map(fn($row) => $GLOBALS['items'][(int)$row['id']], $rows);
        }
        public function all($db = null) { return $this->cached ?? parent::all($GLOBALS['db']); }
        public function each($batchSize = 100, $db = null) {
            // Model Craft's scored-search abort, which Yii's batch iterator propagates.
            if ($this->search === '__no_matches__') { throw new \craft\db\QueryAbortedException(); }
            return parent::each($batchSize, $GLOBALS['db']);
        }
        public function asArray($value = true) { return $this; }
        public function clearCachedResult(): void { $this->cached = null; }
    }
}
namespace verbb\wishlist\gql\arguments { class ItemArguments { public static function getArguments(): array { return []; } } }
namespace verbb\wishlist\elements {
    class Item {
        public string $elementClass = 'craft\\elements\\Entry';
        public ?int $elementId;
        public string $title;
        public function __construct(public int $id, public int $listId, private ?\craft\elements\Entry $element) {
            $this->elementId = $element?->id;
            $this->title = $element?->title ?? 'Deleted secret';
        }
        public function getElement() { return $this->element; }
        public static function find() { return new \craft\elements\db\ElementQuery(); }
    }
    class ListElement { public function __construct(public int $id, public array $items) {} }
}
namespace {
    use GraphQL\GraphQL;
    use GraphQL\Type\Schema;
    use GraphQL\Type\Definition\{ObjectType, Type};
    use craft\elements\Entry;
    use craft\helpers\Gql;
    use verbb\wishlist\elements\{Item, ListElement};
    use verbb\wishlist\gql\interfaces\{ItemInterface, ListInterface};
    use verbb\wishlist\gql\resolvers\ItemResolver;
    $root = dirname(__DIR__, 2);
    require $root . '/src/helpers/Gql.php';
    require $root . '/src/gql/resolvers/ItemResolver.php';
    require $root . '/src/gql/interfaces/ItemInterface.php';
    require $root . '/src/gql/interfaces/ListInterface.php';
    Craft::$app = new class {
        public function getGql() { return new class { public function prepareFieldDefinitions($fields, $name) { return $fields; } }; }
        public function getSites() { return new class { public function getSiteById($id) { return (object)['uid' => (string)$id]; } }; }
        public function getConfig() { return new class { public function getGeneral() { return (object)['maxGraphqlResults' => 100, 'gqlTypePrefix' => '']; } }; }
    };
    $allowed = new Entry(100, 'Shared public');
    $private = new Entry(200, 'Shared secret'); $private->sectionId = 2;
    $site = new Entry(300, 'Other site'); $site->siteId = 2;
    $inactive = new Entry(400, 'Inactive'); $inactive->status = 'disabled';
    $draft = new Entry(500, 'Draft'); $draft->draft = true;
    $revision = new Entry(600, 'Revision'); $revision->revision = true;
    $items = [];
    foreach ([1 => $private, 2 => $allowed, 3 => $site, 4 => $inactive, 5 => $draft, 6 => $revision, 7 => null, 8 => $allowed] as $id => $element) {
        $items[$id] = new Item($id, $id === 8 ? 20 : 10, $element);
    }
    $db = new \yii\db\Connection(['dsn' => 'sqlite::memory:']); $db->open();
    $db->createCommand('CREATE TABLE wishlist_items (id INTEGER, listId INTEGER, typeId INTEGER, title TEXT, elementId INTEGER, elementClass TEXT)')->execute();
    foreach ($items as $item) { $db->createCommand()->insert('wishlist_items', ['id' => $item->id, 'listId' => $item->listId, 'typeId' => 1, 'title' => $item->title, 'elementId' => $item->elementId, 'elementClass' => $item->elementClass])->execute(); }
    $linkedType = new ObjectType(['name' => 'Linked', 'fields' => ['id' => Type::int(), 'title' => Type::string()]]);
    $fields = ItemInterface::getFieldDefinitions();
    $itemType = new ObjectType(['name' => 'WishlistItem', 'fields' => $fields]);
    $args = ['title' => Type::listOf(Type::string()), 'search' => Type::string(), 'limit' => Type::int(), 'offset' => Type::int(), 'orderBy' => Type::string(), 'listId' => Type::int(), 'elementId' => Type::int(), 'elementClass' => Type::string()];
    // Use the production nested resolver if present; the old default returns the preloaded array.
    \craft\gql\GqlEntityRegistry::createEntity(ItemInterface::getName(), $itemType);
    $listFields = ListInterface::getFieldDefinitions();
    $nested = $listFields['items']; $nested['type'] = Type::listOf($itemType); $nested['args'] = $args;
    $listType = new ObjectType(['name' => 'Wishlist', 'fields' => ['items' => $nested]]);
    $schema = new Schema(['query' => new ObjectType(['name' => 'Query', 'fields' => [
        'wishlistItems' => ['type' => Type::listOf($itemType), 'args' => $args, 'resolve' => [ItemResolver::class, 'resolve']],
        'list' => ['type' => $listType, 'resolve' => fn() => new ListElement(10, array_values($items))],
    ]])]);
    $context = ['argumentManager' => new \craft\gql\ArgumentManager(), 'conditionBuilder' => new \craft\gql\ElementQueryConditionBuilder()];
    $count = 0;
    function check($label, $ok) { global $count; if (!$ok) { throw new RuntimeException('FAIL: ' . $label); } $count++; echo "PASS: $label\n"; }
    function execute($query) {
        global $schema, $context;
        $result = GraphQL::executeQuery($schema, $query, null, $context)->toArray(\GraphQL\Error\DebugFlag::INCLUDE_DEBUG_MESSAGE);
        if (isset($result['errors'])) { throw new RuntimeException(json_encode($result)); }
        return $result['data'];
    }
    $data = execute('{ wishlistItems { id listId title elementId elementClass element { id } } }')['wishlistItems'];
    check('Unfiltered query preserves every placeholder', count($data) === 8);
    check('Restricted copied title is hidden', $data[0]['title'] === null);
    foreach ([0, 2, 3, 4, 5, 6] as $index) {
        check('Restricted/missing link metadata is null: ' . $index, $data[$index]['title'] === null && $data[$index]['elementId'] === null && $data[$index]['elementClass'] === null && $data[$index]['element'] === null);
    }
    check('Readable metadata unchanged', $data[1]['title'] === 'Shared public' && $data[1]['elementId'] === 100 && $data[1]['elementClass'] === Entry::class);
    foreach (['title: ["Shared secret"]', 'search: "secret"', 'elementId: 200'] as $filter) {
        check('Root metadata probes exclude restricted items: ' . $filter, execute('{ wishlistItems(' . $filter . ') { id } }')['wishlistItems'] === []);
        check('Nested metadata probes exclude restricted items: ' . $filter, execute('{ list { items(' . $filter . ') { id } } }')['list']['items'] === []);
    }
    check('Aborted candidate search retains empty-result behaviour', execute('{ wishlistItems(search: "__no_matches__") { id } }')['wishlistItems'] === []);
    check('Permission filtering precedes first-page limit', execute('{ wishlistItems(search: "Shared", limit: 1) { id } }')['wishlistItems'] === [['id' => 2]]);
    check('Permission filtering precedes later-page offset', execute('{ wishlistItems(search: "Shared", limit: 1, offset: 1) { id } }')['wishlistItems'] === [['id' => 8]]);
    check('Nested list argument cannot widen parent', execute('{ list { items(listId: 20) { id } } }')['list']['items'] === []);
    check('Nested list does not trust wider preloaded items', count(execute('{ list { items { id } } }')['list']['items']) === 7);
    check('Sensitive sorting excludes hidden metadata', count(execute('{ wishlistItems(orderBy: "title ASC") { id } }')['wishlistItems']) === 2);
    check('Ordinary sorting retains placeholders', count(execute('{ wishlistItems(orderBy: "id ASC") { id } }')['wishlistItems']) === 8);
    Gql::$scopes = array_merge(Gql::$scopes, ['sections.2:read', 'sites.2:read', 'drafts:read', 'revisions:read', 'inactive:read']);
    check('Explicitly authorized schema retains restricted title', execute('{ wishlistItems(title: ["Shared secret"]) { title } }')['wishlistItems'] === [['title' => 'Shared secret']]);
    check('Direct item title remains unchanged', $items[1]->title === 'Shared secret');
    $query = Item::find(); $query->cached = [$items[1]];
    $source = (object)['items' => $query];
    $prepared = ItemResolver::prepareQuery($source, ['title' => ['Shared public']], 'items');
    check('Cached source cannot override query arguments', count($prepared->all()) === 2);
    $empty = ItemResolver::prepareQuery((object)['items' => []], [], 'items');
    check('Empty preloaded source remains empty', $empty->all() === []);
    Gql::$scopes = ['wishlistListTypes.all:read', 'sections.1:read', 'sites.1:read'];
    foreach (range(9, 118) as $id) {
        $items[$id] = new Item($id, 10, $allowed);
        $db->createCommand()->insert('wishlist_items', ['id' => $id, 'listId' => 10, 'typeId' => 1, 'title' => $allowed->title, 'elementId' => $allowed->id, 'elementClass' => Entry::class])->execute();
    }
    check('Authorization checks span candidate batches before offset', execute('{ wishlistItems(search: "Shared", limit: 1, offset: 102) { id } }')['wishlistItems'] === [['id' => 109]]);
    check('Craft maximum GraphQL result limit still applies', count(execute('{ wishlistItems(search: "Shared") { id } }')['wishlistItems']) === 100);
    echo "Completed $count checks.\n";
}
