<?php

namespace boxhead\craftchurchsuiteevents\elements\db;

use Craft;
use craft\helpers\Db;
use craft\elements\db\ElementQuery;

/**
 * Church Suite Event query
 */
class ChurchSuiteEventQuery extends ElementQuery
{
    // Public Properties
    // =========================================================================
    public $churchSuiteId;
    public $identifier;
    public $publicVisible;
    public $publicFeatured;
    public $embedVisible;
    public $name;
    public $startDate;
    public $endDate;

    // Public Methods
    // =========================================================================
    public function churchSuiteId($value): self
    {
        $this->churchSuiteId = $value;
        return $this;
    }

    public function identifier($value): self
    {
        $this->identifier = $value;
        return $this;
    }

    public function publicVisible($value): self
    {
        $this->publicVisible = $value;
        return $this;
    }

    public function publicFeatured($value): self
    {
        $this->publicFeatured = $value;
        return $this;
    }

    public function embedVisible($value): self
    {
        $this->embedVisible = $value;
        return $this;
    }

    public function name($value): self
    {
        $this->name = $value;
        return $this;
    }

    public function startDate($value): self
    {
        $this->startDate = $value;
        return $this;
    }

    public function endDate($value): self
    {
        $this->endDate = $value;
        return $this;
    }

    public function status(array|string|null $value): ChurchSuiteEventQuery
    {
        parent::status($value);
        return $this;
    }

    protected function beforePrepare(): bool
    {
        // Join the `churchsuiteevents` table
        $this->joinElementTable('churchsuite_events');

        // Select the columns
        $this->query->select([
            'churchsuite_events.churchSuiteId',
            'churchsuite_events.identifier',
            'churchsuite_events.publicVisible',
            'churchsuite_events.publicFeatured',
            'churchsuite_events.embedVisible',
            'churchsuite_events.startDate',
            'churchsuite_events.endDate',
            'churchsuite_events.name',
            'churchsuite_events.description',
            'churchsuite_events.capacity',
            'churchsuite_events.locationName',
            'churchsuite_events.locationAddress',
            'churchsuite_events.locationLatitude',
            'churchsuite_events.locationLongitude',
            'churchsuite_events.locationType',
            'churchsuite_events.locationUrl',
            'churchsuite_events.image',
            'churchsuite_events.ticketsEnabled',
            'churchsuite_events.ticketUrl',
        ]);

        // Apply any custom query params
        if ($this->churchSuiteId) {
            $this->subQuery->andWhere(Db::parseParam('churchsuite_events.churchSuiteId', $this->churchSuiteId));
        }

        if ($this->identifier) {
            $this->subQuery->andWhere(Db::parseParam('churchsuite_events.identifier', $this->identifier));
        }

        if ($this->publicVisible) {
            $this->subQuery->andWhere(Db::parseBooleanParam('churchsuite_events.publicVisible', $this->publicVisible));
        }

        if ($this->publicFeatured) {
            $this->subQuery->andWhere(Db::parseBooleanParam('churchsuite_events.publicFeatured', $this->publicFeatured));
        }

        if ($this->embedVisible) {
            $this->subQuery->andWhere(Db::parseBooleanParam('churchsuite_events.embedVisible', $this->embedVisible));
        }

        if ($this->name) {
            $this->subQuery->andWhere(Db::parseParam('churchsuite_events.name', $this->name));
        }

        if ($this->startDate) {
            $this->subQuery->andWhere(Db::parseDateParam('churchsuite_events.startDate', $this->startDate));
        }

        if ($this->endDate) {
            $this->subQuery->andWhere(Db::parseDateParam('churchsuite_events.endDate', $this->endDate));
        }

        return parent::beforePrepare();
    }
}
