<?php

namespace boxhead\craftchurchsuiteevents\jobs;

use Craft;
use craft\queue\BaseJob;
use boxhead\craftchurchsuiteevents\Plugin;

/**
 * Sync Events From Api queue job
 */
class UpdateEventJob extends BaseJob
{
    private ?int $eventId;
    private ?object $remoteEvent;

    /**
     * @inheritdoc
     */
    public function __construct($eventId, $remoteEvent)
    {
        $this->eventId = $eventId;
        $this->remoteEvent = $remoteEvent;
    }

    public function execute($queue): void
    {
        // Create the element from the API data
        Plugin::getInstance()->syncService->updateElement($this->eventId, $this->remoteEvent);
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('app', 'Updating ChurchSuite Event - ' . $this->remoteEvent->name);
    }
}
