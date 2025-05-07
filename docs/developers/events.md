# Events
Events can be used to extend the functionality of Wishlist.

## List related events

### The `beforeSaveList` event
Plugins can get notified before a list is saved. Event handlers can prevent the list from getting sent by setting `$event->isValid` to false.

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\ListElement;
use yii\base\Event;

Event::on(ListElement::class, ListElement::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $list = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveList` event
Plugins can get notified after a list has been saved

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\ListElement;
use yii\base\Event;

Event::on(ListElement::class, ListElement::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $list = $event->sender;
});
```

### The `beforeDeleteList` event
Plugins can get notified before a list is deleted. Event handlers can prevent the list from getting sent by setting `$event->isValid` to false.

```php
use verbb\wishlist\elements\ListElement;
use yii\base\Event;

Event::on(ListElement::class, ListElement::EVENT_BEFORE_DELETE, function(Event $event) {
    $list = $event->sender;
    $event->isValid = false;
});
```

### The `afterDeleteList` event
Plugins can get notified after a list has been deleted

```php
use verbb\wishlist\elements\ListElement;
use yii\base\Event;

Event::on(ListElement::class, ListElement::EVENT_AFTER_DELETE, function(Event $event) {
    $list = $event->sender;
});
```

### The `beforeAddToCart` event
The event that is triggered before a list's content is added to the Commerce cart.

```php
use verbb\wishlist\controllers\ListsController;
use verbb\wishlist\events\AddToCartEvent;
use yii\base\Event;

Event::on(ListsController::class, ListsController::EVENT_BEFORE_ADD_TO_CART, function(AddToCartEvent $event) {
    $cart = $event->cart;
    $list = $event->list;
});
```

### The `afterAddToCart` event
The event that is triggered after a list's content has been added to the Commerce cart.

```php
use verbb\wishlist\controllers\ListsController;
use verbb\wishlist\events\AddToCartEvent;
use yii\base\Event;

Event::on(ListsController::class, ListsController::EVENT_AFTER_ADD_TO_CART, function(AddToCartEvent $event) {
    $cart = $event->cart;
    $list = $event->list;
});
```

### The `beforeAddToCart` event
The event that is triggered before a list's content is added to the Commerce cart.

```php
use verbb\wishlist\controllers\ListsController;
use verbb\wishlist\events\AddLineItemEvent;
use yii\base\Event;

Event::on(ListsController::class, ListsController::EVENT_BEFORE_ADD_LINE_ITEM, function(AddLineItemEvent $event) {
    $cart = $event->cart;
    $list = $event->list;
    $item = $event->item;
    $lineItem = $event->lineItem;
});
```

### The `afterAddToCart` event
The event that is triggered after a list's content has been added to the Commerce cart.

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


## List Type related events

### The `beforeSaveListType` event
Plugins can get notified before a list type is being saved.

```php
use verbb\wishlist\events\ListTypeEvent;
use verbb\wishlist\services\ListTypes;
use yii\base\Event;

Event::on(ListTypes::class, ListTypes::EVENT_BEFORE_SAVE_LISTTYPE, function(ListTypeEvent $event) {
     // Maybe create an audit trail of this action.
});
```

### The `afterSaveListType` event
Plugins can get notified after a list type has been saved.

```php
use verbb\wishlist\events\ListTypeEvent;
use verbb\wishlist\services\ListTypes;
use yii\base\Event;

Event::on(ListTypes::class, ListTypes::EVENT_AFTER_SAVE_LISTTYPE, function(ListTypeEvent $event) {
     // Maybe prepare some third party system for a new list type
});
```


## Item related events

### The `beforeSaveItem` event
Plugins can get notified before an item is saved. Event handlers can prevent the item from getting sent by setting `$event->isValid` to false.

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\Item;
use yii\base\Event;

Event::on(Item::class, Item::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $item = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveItem` event
Plugins can get notified after an item has been saved

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\Item;
use yii\base\Event;

Event::on(Item::class, Item::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $item = $event->sender;
});
```

### The `beforeDeleteItem` event
Plugins can get notified before an item is deleted. Event handlers can prevent the item from getting sent by setting `$event->isValid` to false.

```php
use verbb\wishlist\elements\Item;
use yii\base\Event;

Event::on(Item::class, Item::EVENT_BEFORE_DELETE, function(Event $event) {
    $item = $event->sender;
    $event->isValid = false;
});
```

### The `afterDeleteList` event
Plugins can get notified after a item has been deleted

```php
use verbb\wishlist\elements\Item;
use yii\base\Event;

Event::on(Item::class, Item::EVENT_AFTER_DELETE, function(Event $event) {
    $item = $event->sender;
});
```
