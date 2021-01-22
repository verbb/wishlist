<?php
namespace verbb\wishlist\migrations;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Console;
use craft\helpers\Json;

use Throwable;

use yii\helpers\Markdown;

class MigrateUpvote extends Migration
{
    // Properties
    // =========================================================================

    public $id;


    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $history = (new Query())
            ->from('{{%upvote_userhistories}}')
            ->where(['id' => $this->id])
            ->one();

        if (!$history) {
            return false;
        }

        $listType = Wishlist::$plugin->getListTypes()->getDefaultListType();

        // Create a new list
        $list = new ListElement();
        $list->userId = $history['id'];
        $list->reference = Wishlist::$plugin->getLists()->generateReferenceNumber();
        $list->typeId = $listType->id;
        $list->title = $listType->name;

        if (!Craft::$app->getElements()->saveElement($list)) {
            foreach ($list->getErrors() as $attr => $errors) {
                foreach ($errors as $error) {
                    $this->stdout("    > $attr: $error", Console::FG_RED);
                }
            }

            return false;
        }

        $this->stdout("    > History for user #{$history['id']} migrated.", Console::FG_GREEN);

        // Add all items to the list
        $votes = Json::decode($history['history']);

        foreach ($votes as $elementId => $vote) {
            $element = Craft::$app->getElements()->getElementById($elementId);

            if (!$element) {
                $this->stdout("    > Unable to find element $elementId", Console::FG_RED);

                continue;
            }

            $item = new Item();
            $item->listId = $list->id;
            $item->elementId = $elementId;
            $item->elementClass = get_class($element);
            $item->options = Json::encode([]);
            $item->optionsSignature = md5(Json::encode([]));

            if (!Wishlist::$plugin->getItems()->saveElement($item)) {
                foreach ($item->getErrors() as $attr => $errors) {
                    foreach ($errors as $error) {
                        $this->stdout("    > $attr: $error", Console::FG_RED);
                    }
                }

                continue;
            }

            $this->stdout("    > Element ID #{$elementId} migrated.", Console::FG_GREEN);
        }
    }

    public function safeDown()
    {
        return false;
    }


    // Private Methods
    // =========================================================================

    private function stdout($string, $color = '')
    {
        $class = '';

        if ($color) {
            $class = 'color-' . $color;
        }

        echo '<div class="log-label ' . $class . '">' . Markdown::processParagraph($string) . '</div>';
    }

}