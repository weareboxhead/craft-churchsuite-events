<?php

namespace boxhead\craftchurchsuiteevents\models;

use Craft;
use craft\base\Model;

/**
 * churchsuite-events settings
 */
class Settings extends Model
{
    public $churchSuiteOrganisationHandle = '';
    public $categoryGroupHandle = 'churchSuiteEventCategories';
    public $imageVolumeHandle = 'churchSuiteEventImages';
    public $enableEventsWhenCreated = true;

    public function defineRules(): array
    {
        return [
            [['churchSuiteOrganisationHandle', 'categoryGroupHandle', 'imageVolumeHandle'], 'required'],
            [['categoryGroupHandle', 'imageVolumeHandle'], 'string'],
            [['enableEventsWhenCreated'], 'boolean'],
        ];
    }
}
