<?php

namespace boxhead\craftchurchsuiteevents\jobs;

use Craft;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use boxhead\craftchurchsuiteevents\Plugin;
use boxhead\craftchurchsuiteevents\jobs\CreateEventJob;
use boxhead\craftchurchsuiteevents\jobs\UpdateEventJob;

/**
 * Sync Events From Api queue job
 */
class SyncEventsFromAPI extends BaseJob
{
    public function execute($queue): void
    {
        // Get local data
        $localData = Plugin::getInstance()->syncService->getLocalData();

        // Get remote data from JSON endpoint
        $remoteData = Plugin::getInstance()->syncService->getAPIData();

        // Determine which events we are missing by id
        $missingIds = array_diff($remoteData['churchSuiteEventIds'], $localData['churchSuiteEventIds']);

        // Determine which events we shouldn't have by id
        $removeIds = array_diff($localData['churchSuiteEventIds'], $remoteData['churchSuiteEventIds']);

        // Determine which events need updating (all active events which we haven't just created)
        $updatingIds = array_diff($remoteData['churchSuiteEventIds'], $missingIds);

        $stepsCount = count($missingIds) + count($removeIds) + count($updatingIds);

        // Create all missing events
        foreach ($missingIds as $i => $churchSuiteEventId) {
            $this->setProgress(
                $queue,
                $i / $stepsCount,
                \Craft::t('app', 'Creating ChurchSuite Events {step, number} of {total, number}', [
                    'step' => $i + 1,
                    'total' => $stepsCount,
                ])
            );

            Queue::push(new CreateEventJob($remoteData['events'][$churchSuiteEventId]));
        }

        // Update all existing events
        foreach ($updatingIds as $i => $churchSuiteEventId) {
            $x = $i + count($missingIds);

            $this->setProgress(
                $queue,
                $x / $stepsCount,
                \Craft::t('app', 'Updating ChurchSuite Events {step, number} of {total, number}', [
                    'step' => $x + 1,
                    'total' => $stepsCount,
                ])
            );

            Queue::push(new UpdateEventJob($localData['events'][$churchSuiteEventId], $remoteData['events'][$churchSuiteEventId]));
        }

        // // If we have local data that doesn't match with anything from remote we should close the local entry
        foreach ($removeIds as $i => $churchSuiteEventId) {
            $x = $i + count($missingIds) + count($updatingIds);

            $this->setProgress(
                $queue,
                $x / $stepsCount,
                \Craft::t('app', 'Closing Events {step, number} of {total, number}', [
                    'step' => $x + 1,
                    'total' => $stepsCount,
                ])
            );

            Plugin::getInstance()->syncService->closeElement($localData['events'][$churchSuiteEventId]);
        }
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('app', 'Syncing ChurchSuite Event Data');
    }
}
