# Events

Wishlist provides events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to change Wishlist’s behaviour.

Lists and items are Craft elements, so their save and delete events are Craft’s own element events, triggered on the `ListElement` and `Item` classes.

## List Events

### The `beforeSave` Event

The event that is triggered before a list is saved. Set `$event->isValid` to `false` to prevent the list from being saved.

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\ListElement;
use yii\base\Event;

Event::on(ListElement::class, ListElement::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $list = $event->sender;
    $isNew = $event->isNew;
});
```

### The `afterSave` Event

The event that is triggered after a list is saved.

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\ListElement;
use yii\base\Event;

Event::on(ListElement::class, ListElement::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $list = $event->sender;
});
```

### The `beforeDelete` Event

The event that is triggered before a list is deleted. Set `$event->isValid` to `false` to prevent the list from being deleted.

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\ListElement;
use yii\base\Event;

Event::on(ListElement::class, ListElement::EVENT_BEFORE_DELETE, function(ModelEvent $event) {
    $list = $event->sender;
});
```

### The `afterDelete` Event

The event that is triggered after a list is deleted.

```php
use verbb\wishlist\elements\ListElement;
use yii\base\Event;

Event::on(ListElement::class, ListElement::EVENT_AFTER_DELETE, function(Event $event) {
    $list = $event->sender;
});
```

## Add to Cart Events

These events are triggered when a visitor adds a list’s items to their Craft Commerce cart, through the `wishlist/lists/add-to-cart` action. None of them can be cancelled.

### The `beforeAddToCart` Event

The event that is triggered before a list’s items are added to the cart. `$event->cart` is the visitor’s cart and `$event->list` is the list.

```php
use verbb\wishlist\controllers\ListsController;
use verbb\wishlist\events\AddToCartEvent;
use yii\base\Event;

Event::on(ListsController::class, ListsController::EVENT_BEFORE_ADD_TO_CART, function(AddToCartEvent $event) {
    $cart = $event->cart;
    $list = $event->list;
});
```

### The `afterAddToCart` Event

The event that is triggered after a list’s items have been added to the cart and the cart has been saved.

```php
use verbb\wishlist\controllers\ListsController;
use verbb\wishlist\events\AddToCartEvent;
use yii\base\Event;

Event::on(ListsController::class, ListsController::EVENT_AFTER_ADD_TO_CART, function(AddToCartEvent $event) {
    $cart = $event->cart;
    $list = $event->list;
});
```

### The `beforeAddLineItem` Event

The event that is triggered before an item’s line item is added to the cart. Changes to `$event->lineItem`, such as its quantity, options or note, are kept when the line item is added. The event can’t skip the item.

```php
use verbb\wishlist\controllers\ListsController;
use verbb\wishlist\events\AddLineItemEvent;
use yii\base\Event;

Event::on(ListsController::class, ListsController::EVENT_BEFORE_ADD_LINE_ITEM, function(AddLineItemEvent $event) {
    $cart = $event->cart;
    $list = $event->list;
    $item = $event->item;

    // Add each wishlist item to the cart with a note
    $event->lineItem->note = 'Added from ' . $list->title;
});
```

### The `afterAddLineItem` Event

The event that is triggered after an item’s line item has been added to the cart. The cart hasn’t been saved yet at this point.

```php
use verbb\wishlist\controllers\ListsController;
use verbb\wishlist\events\AddLineItemEvent;
use yii\base\Event;

Event::on(ListsController::class, ListsController::EVENT_AFTER_ADD_LINE_ITEM, function(AddLineItemEvent $event) {
    $cart = $event->cart;
    $list = $event->list;
    $item = $event->item;
    $lineItem = $event->lineItem;
});
```

## List Type Events

### The `beforeSaveListType` Event

The event that is triggered before a list type is validated and saved. `$event->isNew` is `true` for a new list type. Changes to `$event->listType` are saved, and the event can’t prevent the save.

```php
use verbb\wishlist\events\ListTypeEvent;
use verbb\wishlist\services\ListTypes;
use yii\base\Event;

Event::on(ListTypes::class, ListTypes::EVENT_BEFORE_SAVE_LISTTYPE, function(ListTypeEvent $event) {
    $listType = $event->listType;
    $isNew = $event->isNew;
});
```

### The `afterSaveListType` Event

The event that is triggered after a list type’s changes have been applied from project config, including on other environments when project config is applied there. `$event->isNew` is always `false` for this event.

```php
use verbb\wishlist\events\ListTypeEvent;
use verbb\wishlist\services\ListTypes;
use yii\base\Event;

Event::on(ListTypes::class, ListTypes::EVENT_AFTER_SAVE_LISTTYPE, function(ListTypeEvent $event) {
    $listType = $event->listType;
});
```

## Item Events

### The `beforeSave` Event

The event that is triggered before an item is saved. Set `$event->isValid` to `false` to prevent the item from being saved.

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\Item;
use yii\base\Event;

Event::on(Item::class, Item::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $item = $event->sender;
    $isNew = $event->isNew;
});
```

### The `afterSave` Event

The event that is triggered after an item is saved.

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\Item;
use yii\base\Event;

Event::on(Item::class, Item::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $item = $event->sender;
});
```

### The `beforeDelete` Event

The event that is triggered before an item is deleted. Set `$event->isValid` to `false` to prevent the item from being deleted.

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\Item;
use yii\base\Event;

Event::on(Item::class, Item::EVENT_BEFORE_DELETE, function(ModelEvent $event) {
    $item = $event->sender;
});
```

### The `afterDelete` Event

The event that is triggered after an item is deleted.

```php
use verbb\wishlist\elements\Item;
use yii\base\Event;

Event::on(Item::class, Item::EVENT_AFTER_DELETE, function(Event $event) {
    $item = $event->sender;
});
```

### The `modifySupportedElementTypes` Event

The event that is triggered when Wishlist works out which element types can be added to lists. `$event->types` starts with every registered element type except lists and items. Remove a class to stop visitors adding that type of element from the front end, which also removes it from the element types offered in the control panel.

```php
use craft\elements\Asset;
use craft\elements\User;
use verbb\wishlist\events\ModifySupportedElementTypesEvent;
use verbb\wishlist\services\Items;
use yii\base\Event;

Event::on(Items::class, Items::EVENT_MODIFY_SUPPORTED_ELEMENT_TYPES, function(ModifySupportedElementTypesEvent $event) {
    // Don’t allow assets or users to be added to lists
    $event->types = array_values(array_diff($event->types, [Asset::class, User::class]));
});
```

## PDF Events

### The `beforeRenderPdf` Event

The event that is triggered before a list’s PDF is rendered. `$event->list` is the list and `$event->template` is the template path from the `pdfPath` setting, which you can change to render a different template. Set `$event->pdf` to your own output to skip Wishlist’s rendering entirely, in which case the `afterRenderPdf` event isn’t triggered. The event is also triggered for the plain HTML format.

While a listener is registered for this event, Wishlist doesn’t cache rendered PDFs, because the listener’s output can differ from one request to the next.

```php
use verbb\wishlist\events\PdfEvent;
use verbb\wishlist\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_BEFORE_RENDER_PDF, function(PdfEvent $event) {
    // Use a different template for gift registries
    if ($event->list->getType()->handle === 'giftRegistry') {
        $event->template = '_pdf/gift-registry';
    }
});
```

### The `afterRenderPdf` Event

The event that is triggered after a list’s PDF has been rendered, including when it comes from the cache. `$event->pdf` holds the PDF output, and whatever it holds when your listener finishes is what Wishlist returns. It isn’t triggered for the plain HTML format.

```php
use verbb\wishlist\events\PdfEvent;
use verbb\wishlist\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_AFTER_RENDER_PDF, function(PdfEvent $event) {
    $list = $event->list;
    $pdf = $event->pdf;
});
```

## GraphQL Events

### The `authorizeLinkedElement` Event

The event that is triggered when a GraphQL request reads the element an item links to, and that element is of a type Wishlist doesn’t know how to check against the schema. Wishlist checks entries, users, addresses, assets, categories, tags, global sets, Commerce products and variants, and nested elements through their owner, itself. For any other element type, the element is hidden from GraphQL unless a listener sets `$event->authorized` to `true`. The site and status checks still apply first.

`$event->element` is the linked element and `$event->schema` is the schema being checked, or `null` for the active schema.

```php
use craft\helpers\Gql as CraftGql;
use verbb\wishlist\events\AuthorizeGqlElementEvent;
use verbb\wishlist\helpers\Gql;
use yii\base\Event;
use modules\sitemodule\elements\Recipe;

Event::on(Gql::class, Gql::EVENT_AUTHORIZE_LINKED_ELEMENT, function(AuthorizeGqlElementEvent $event) {
    if ($event->element instanceof Recipe) {
        $event->authorized = CraftGql::canSchema('recipes.all', 'read', $event->schema);
    }
});
```
