<?php
namespace boxhead\craftchurchsuiteevents\variables;

use Craft;
use yii\base\Behavior;
use boxhead\craftchurchsuiteevents\elements\ChurchSuiteEvent;
use boxhead\craftchurchsuiteevents\elements\db\ChurchSuiteEventQuery;

/**
 * The class name isn't important, but we've used something that describes
 * how it is applied, rather than what it does.
 * 
 * You are only apt to need a single behavior, even if your plugin or module
 * provides multiple element types.
 */
class CraftVariableBehavior extends Behavior
{
    public function churchsuiteevents(array $criteria = []): ChurchSuiteEventQuery
    {
        // Create a query via your element type, and apply any passed criteria:
        return Craft::configure(ChurchSuiteEvent::find(), $criteria);
    }
}
