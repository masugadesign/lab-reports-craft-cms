<?php

namespace Masuga\LabReports\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\actions\Edit;

/**
 * This class exists so Craft 4 doesn't slide open an empty edit form. Craft 4 now
 * has a minimum set of element actions that must be available for all element types.
 */
class EditConfiguredReport extends Edit
{
    /**
     * @var string|null The trigger label
     */
    public ?string $label = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if (!isset($this->label)) {
            $this->label = Craft::t('app', 'Edit');
        }
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return $this->label;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        validateSelection: \$selectedItems => Garnish.hasAttr(\$selectedItems.find('.element'), 'data-savable'),
        activate: \$selectedItems => {
            const \$element = \$selectedItems.find('.element:first');
            //Craft.createElementEditor(\$element.data('type'), \$element);
            alert("The `Edit` element action is not available for ConfiguredReport elements but Craft 4 requires it. Sorry!");
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
