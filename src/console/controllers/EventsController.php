<?php

namespace boxhead\craftchurchsuiteevents\console\controllers;

use Craft;
use craft\helpers\Queue;
use yii\console\ExitCode;
use craft\helpers\Console;
use craft\console\Controller;
use boxhead\craftchurchsuiteevents\jobs\SyncEventsFromAPI;

/**
 * ChurchSuite Events API Sync
 */
class EventsController extends Controller
{
    public $defaultAction = 'sync';

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        switch ($actionID) {
            case 'sync':
                // $options[] = '...';
                break;
        }
        return $options;
    }

     /**
     * Runs the ChurchSuite Events data sync
     *
     * @throws Throwable
     */
    public function actionSync(): int
    {
        Queue::push(new SyncEventsFromAPI());
        $this->stdout("Added ChurchSuite Events sync to the queue" . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
