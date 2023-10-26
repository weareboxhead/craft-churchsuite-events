<?php

namespace boxhead\craftchurchsuiteevents\records;

use Craft;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Church Suite Event record
 *
 * @property int $id ID
 * @property int $churchSuiteId Church suite ID
 * @property string $identifier Identifier
 * @property int $publicVisible Public visible
 * @property int $publicFeatured Public featured
 * @property int $embedVisible Embed visible
 * @property string $startDate Start date
 * @property string $endDate End date
 * @property string $name Name
 * @property string|null $description Description
 * @property int|null $capacity Capacity
 * @property string|null $locationName Location name
 * @property string|null $locationAddress Location address
 * @property string|null $locationLatitude Location latitude
 * @property string|null $locationLongitude Location longitude
 * @property string|null $locationType Location type
 * @property string|null $locationUrl Location url
 * @property string|null $image Image
 * @property int $ticketsEnabled Tickets enabled
 * @property string|null $ticketUrl Ticket url
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date updated
 * @property string $uid Uid
 */
class ChurchSuiteEvent extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName()
    {
        return '{{%churchsuite_events}}';
    }

    /**
     * Returns the event's element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the event's type.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getType(): ActiveQueryInterface
    {
        return $this->hasOne(EntryType::class, ['id' => 'typeId']);
    }
}
