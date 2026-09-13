# Events
Events can be used to extend the functionality of Wishlist.


## Register a Listener

Register listeners from a custom module or plugin that is bootstrapped for the requests where the event occurs. Put the `use` imports at the top of its PHP file and the `Event::on(...)` call inside its `init()` method, after `parent::init()`. Do not place the listener in a Twig template or modify this plugin's source to register it.

Choose a hook whose timing matches your task. Cancellation depends on the particular event and emitter, as described for each hook below. Test a listener on the operation it affects, including any relevant queue or console path.

## List Related Events

### The `beforeSaveList` Event
The event that is triggered before a list is saved. Event handlers can prevent the list from being saved by setting `$event->isValid` to false.

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\ListElement;
use yii\base\Event;

Event::on(ListElement::class, ListElement::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $list = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveList` Event
The event that is triggered after a list has been saved

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\ListElement;
use yii\base\Event;

Event::on(ListElement::class, ListElement::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $list = $event->sender;
});
```

### The `beforeDeleteList` Event
The event that is triggered before a list is deleted. Event handlers can prevent the list from being deleted by setting `$event->isValid` to false.

```php
use verbb\wishlist\elements\ListElement;
use yii\base\Event;

Event::on(ListElement::class, ListElement::EVENT_BEFORE_DELETE, function(Event $event) {
    $list = $event->sender;
    $event->isValid = false;
});
```

### The `afterDeleteList` Event
The event that is triggered after a list has been deleted

```php
use verbb\wishlist\elements\ListElement;
use yii\base\Event;

Event::on(ListElement::class, ListElement::EVENT_AFTER_DELETE, function(Event $event) {
    $list = $event->sender;
});
```

### The `beforeAddToCart` Event
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

### The `afterAddToCart` Event
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

### The `beforeAddLineItem` Event
The event that is triggered before an individual wishlist item is added to the Commerce cart.

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

### The `afterAddLineItem` Event
The event that is triggered after an individual wishlist item has been added to the Commerce cart.

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


## List Type Related Events

### The `beforeSaveListType` Event
The event that is triggered before a list type is being saved.

```php
use verbb\wishlist\events\ListTypeEvent;
use verbb\wishlist\services\ListTypes;
use yii\base\Event;

Event::on(ListTypes::class, ListTypes::EVENT_BEFORE_SAVE_LISTTYPE, function(ListTypeEvent $event) {
     // Maybe create an audit trail of this action.
});
```

### The `afterSaveListType` Event
The event that is triggered after a list type has been saved.

```php
use verbb\wishlist\events\ListTypeEvent;
use verbb\wishlist\services\ListTypes;
use yii\base\Event;

Event::on(ListTypes::class, ListTypes::EVENT_AFTER_SAVE_LISTTYPE, function(ListTypeEvent $event) {
     // Maybe prepare some third party system for a new list type
});
```


## Item Related Events

### The `beforeSaveItem` Event
The event that is triggered before an item is saved. Event handlers can prevent the item from being saved by setting `$event->isValid` to false.

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\Item;
use yii\base\Event;

Event::on(Item::class, Item::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $item = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveItem` Event
The event that is triggered after an item has been saved

```php
use craft\events\ModelEvent;
use verbb\wishlist\elements\Item;
use yii\base\Event;

Event::on(Item::class, Item::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $item = $event->sender;
});
```

### The `beforeDeleteItem` Event
The event that is triggered before an item is deleted. Event handlers can prevent the item from being deleted by setting `$event->isValid` to false.

```php
use verbb\wishlist\elements\Item;
use yii\base\Event;

Event::on(Item::class, Item::EVENT_BEFORE_DELETE, function(Event $event) {
    $item = $event->sender;
    $event->isValid = false;
});
```

### The `afterDeleteItem` Event
The event that is triggered after an item has been deleted.

```php
use verbb\wishlist\elements\Item;
use yii\base\Event;

Event::on(Item::class, Item::EVENT_AFTER_DELETE, function(Event $event) {
    $item = $event->sender;
});
```
