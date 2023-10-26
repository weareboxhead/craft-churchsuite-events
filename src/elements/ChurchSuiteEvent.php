<?php

namespace boxhead\craftchurchsuiteevents\elements;

use Craft;
use DateTime;
use craft\helpers\Db;
use yii\web\Response;
use craft\base\Element;
use craft\elements\User;
use craft\helpers\UrlHelper;
use yii\base\InvalidConfigException;
use craft\web\CpScreenResponseBehavior;
use craft\elements\conditions\ElementConditionInterface;
use boxhead\craftchurchsuiteevents\elements\db\ChurchSuiteEventQuery;
use boxhead\craftchurchsuiteevents\elements\conditions\ChurchSuiteEventCondition;
use boxhead\craftchurchsuiteevents\records\ChurchSuiteEvent as ChurchSuiteEventRecord;

/**
 * Church Suite Event element type
 */
class ChurchSuiteEvent extends Element
{
    // Public Properties
    // =========================================================================

    public ?int $churchSuiteId = null;
    public ?string $identifier = '';
    public ?bool $publicVisible = false;
    public ?bool $publicFeatured = false;
    public ?bool $embedVisible = false;
    public ?DateTime $startDate = null;
    public ?DateTime $endDate = null;
    public ?string $name = '';
    public ?string $description = '';
    public ?int $capacity = null;
    public ?string $locationName = '';
    public ?string $locationAddress = '';
    public ?string $locationLatitude = null;
    public ?string $locationLongitude = null;
    public ?string $locationType = '';
    public ?string $locationUrl = '';
    public ?string $image = '';
    public ?bool $ticketsEnabled = false;
    public ?string $ticketUrl = '';

    public static function displayName(): string
    {
        return Craft::t('churchsuite-events', 'Events');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('churchsuite-events', 'churchsuite event');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('churchsuite-events', 'ChurchSuite Events');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('churchsuite-events', 'churchsuite events');
    }

    public static function refHandle(): ?string
    {
        return 'churchsuiteevent';
    }

    public static function trackChanges(): bool
    {
        return true;
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return false;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function find(): ChurchSuiteEventQuery
    {
        return Craft::createObject(ChurchSuiteEventQuery::class, [static::class]);
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(ChurchSuiteEventCondition::class, [static::class]);
    }

    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('churchsuite-events', 'All ChurchSuite Events'),
            ],
        ];
    }

    protected static function defineActions(string $source): array
    {
        // List any bulk element actions here
        return [];
    }

    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            [
                'label' => Craft::t('app', 'Name'),
                'orderBy' => 'name',
                'attribute' => 'name',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Start Date'),
                'orderBy' => 'startDate',
                'attribute' => 'startDate',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'End Date'),
                'orderBy' => 'endDate',
                'attribute' => 'endDate',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
            // ...
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'name' => ['label' => Craft::t('app', 'Name')],
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'churchSuiteId' => ['label' => Craft::t('app', 'ChurchSuite ID')],
            'identifier' => ['label' => Craft::t('app', 'Identifier')],
            'startDate' => ['label' => Craft::t('app', 'Start Date')],
            'endDate' => ['label' => Craft::t('app', 'End Date')],
            'locationType' => ['label' => Craft::t('app', 'Location Type')],
            'locationName' => ['label' => Craft::t('app', 'Location Name')],
            'capacity' => ['label' => Craft::t('app', 'Capacity')],
            'publicVisible' => ['label' => Craft::t('app', 'Public Visible')],
            'publicFeatured' => ['label' => Craft::t('app', 'Public Featured')],
            'embedVisible' => ['label' => Craft::t('app', 'Embed Visible')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            // ...
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'startDate',
            'endDate',
            'dateCreated',
            'link',
            // ...
        ];
    }

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }

    public function getUriFormat(): ?string
    {
        // If church suite events should have URLs, define their URI format here
        return null;
    }

    protected function previewTargets(): array
    {
        $previewTargets = [];
        $url = $this->getUrl();
        if ($url) {
            $previewTargets[] = [
                'label' => Craft::t('app', 'Primary {type} page', [
                    'type' => self::lowerDisplayName(),
                ]),
                'url' => $url,
            ];
        }
        return $previewTargets;
    }

    protected function route(): array|string|null
    {
        // Define how church suite events should be routed when their URLs are requested
        return [
            'templates/render',
            [
                'template' => 'site/template/path',
                'variables' => ['churchSuiteEvent' => $this],
            ]
        ];
    }

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('editChurchSuiteEvents');
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('editChurchSuiteEvents');
    }

    public function canDuplicate(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('editChurchSuiteEvents');
    }

    public function canDelete(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('deleteChurchSuiteEvents');
    }

    public function canCreateDrafts(User $user): bool
    {
        return false;
    }

    protected function cpEditUrl(): ?string
    {
        return sprintf('churchsuite-events/%s', $this->getCanonicalId());
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('churchsuite-events');
    }

    public function getFieldLayout(): null|\craft\models\FieldLayout
    {
        return \Craft::$app->getFields()->getLayoutByType(self::class);
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => self::pluralDisplayName(),
                'url' => UrlHelper::cpUrl('churchsuite-events'),
            ],
        ]);
    }

    /**
     * Returns element metadata that should be shown within the editor sidebar.
     *
     * @return array The data, with keys representing the labels. The values can either be strings or callables.
     * If a value is `false`, it will be omitted.
     * @since 3.7.0
     */
    protected function metadata(): array
    {
        return [
            'ChurchSuite ID' => $this->churchSuiteId,
            'Identifier' => $this->identifier,
            'Publicly Visible' => $this->publicVisible ? 'Yes' : 'No',
            'Publicly Featured' => $this->publicFeatured ? 'Yes' : 'No',
            'Embed Visible' => $this->embedVisible ? 'Yes' : 'No',
            'Start Date' => $this->startDate ? $this->startDate->format('d/m/Y H:i') : null,
            'End Date' => $this->endDate ? $this->endDate->format('d/m/Y H:i') : null,
            'Description' => strip_tags($this->description) ?? 'Not Set',
            'Location Name' => $this->locationName ?? 'Not Set',
            'Location Address' => $this->locationAddress ?? 'Not Set',
            'Location Type' => ucwords($this->locationType),
            'Location URL' => $this->locationUrl ?? 'Not Set',
            'Capacity' => $this->capacity ?? 'None',
            'Tickets Enabled' => $this->ticketsEnabled ? 'Yes' : 'No',
            'Ticket URL' => $this->ticketUrl ? '<a href="' . $this->ticketUrl . '" target="_blank">Ticket Link</a>' : 'Not Set',

        ];
    }


    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            if (!$isNew) {
                $record = ChurchSuiteEventRecord::findOne($this->id);

                if (!$record) {
                    throw new InvalidConfigException("Invalid event ID: $this->id");
                }
            } else {
                $record = new ChurchSuiteEventRecord();
                $record->id = (int)$this->id;
            }

            $record->churchSuiteId = $this->churchSuiteId;
            $record->identifier = $this->identifier;
            $record->publicVisible = $this->publicVisible;
            $record->publicFeatured = $this->publicFeatured;
            $record->embedVisible = $this->embedVisible;
            $record->startDate = Db::prepareDateForDb($this->startDate);
            $record->endDate = Db::prepareDateForDb($this->endDate);
            $record->name = $this->name;
            $record->description = $this->description;
            $record->capacity = $this->capacity;
            $record->locationName = $this->locationName;
            $record->locationAddress = $this->locationAddress;
            $record->locationLatitude = $this->locationLatitude;
            $record->locationLongitude = $this->locationLongitude;
            $record->locationType = $this->locationType;
            $record->locationUrl = $this->locationUrl;
            $record->image = $this->image;
            $record->ticketsEnabled = $this->ticketsEnabled;
            $record->ticketUrl = $this->ticketUrl;

            $record->save(false);
        }

        parent::afterSave($isNew);
    }
}
