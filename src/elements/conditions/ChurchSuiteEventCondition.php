<?php

namespace boxhead\craftchurchsuiteevents\elements\conditions;

use Craft;
use craft\elements\conditions\ElementCondition;

/**
 * Church Suite Event condition
 */
class ChurchSuiteEventCondition extends ElementCondition
{
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            // ...
        ]);
    }
}
