<?php

namespace boxhead\craftchurchsuiteevents\jobs;

use Craft;
use craft\queue\BaseJob;
use boxhead\craftchurchsuiteevents\Plugin;

/**
 * Sync Events From Api queue job
 */
class CreateEventJob extends BaseJob
{
    private ?object $event;

    /**
     * @inheritdoc
     */
    public function __construct($event)
    {
        $this->event = $event;
    }

    public function execute($queue): void
    {
        // Create the element from the API data
        Plugin::getInstance()->syncService->createElement($this->event);
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('app', 'Creating ChurchSuite Event - ' . $this->event->name);
    }
}
