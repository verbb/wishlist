<?php
namespace verbb\wishlist\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\actions\Duplicate;
use craft\elements\db\ElementQueryInterface;

use Throwable;

class DuplicateList extends Duplicate
{
    // Properties
    // =========================================================================

    public ?string $successMessage = null;


    // Public Methods
    // =========================================================================

    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Duplicate');
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $elements = $query->all();
        $successCount = 0;
        $failCount = 0;

        $this->_duplicateElements($query, $elements, $successCount, $failCount);

        // Did all of them fail?
        if ($successCount === 0) {
            $this->setMessage(Craft::t('app', 'Could not duplicate elements due to validation errors.'));
            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('app', 'Could not duplicate all elements due to validation errors.'));
        } else {
            $this->setMessage(Craft::t('app', 'Elements duplicated.'));
        }

        return true;
    }

    private function _duplicateElements(ElementQueryInterface $query, array $elements, int &$successCount, int &$failCount): void
    {
        $elementsService = Craft::$app->getElements();

        foreach ($elements as $element) {
            // Duplicate the list first
            try {
                $duplicateList = $elementsService->duplicateElement($element);
            } catch (Throwable) {
                // Validation error
                $failCount++;
                continue;
            }

            foreach ($element->getItems() as $item) {
                try {
                    $duplicateItem = $elementsService->duplicateElement($item, ['listId' => $duplicateList->id]);
                } catch (Throwable) {
                    // Validation error
                    $failCount++;
                    continue;
                }
            }

            $successCount++;
        }
    }
}
