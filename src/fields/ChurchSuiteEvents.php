<?php
namespace boxhead\craftchurchsuiteevents\fields;

use boxhead\craftchurchsuiteevents\elements\ChurchSuiteEvent;
use craft\fields\BaseRelationField;

class ChurchSuiteEvents extends BaseRelationField
{
    public static function displayName(): string
    {
        return \Craft::t('churchsuite-events', 'ChurchSuite Events');
    }

    public static function elementType(): string
    {
        return ChurchSuiteEvent::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return \Craft::t('churchsuite-events', 'Add a ChurchSuite Event');
    }
}