<?php

namespace boxhead\craftchurchsuiteevents\services;

use Yii;
use Craft;
use DateTime;
use Throwable;
use GuzzleHttp\Client;
use yii\base\Component;
use craft\elements\Asset;
use craft\helpers\Assets;
use craft\elements\Category;
use craft\helpers\FileHelper;
use craft\helpers\DateTimeHelper;
use boxhead\craftchurchsuiteevents\Plugin;
use boxhead\craftchurchsuiteevents\elements\ChurchSuiteEvent;

/**
 * Sync Service service
 */
class SyncService extends Component
{
    private $settings;

    public function __construct()
    {
        // Check for all required settings
        $this->checkSettings();
    }

    /**
     * Returns an array of local data for ChurchSuite events.
     *
     * @return array An array containing two keys:
     *               - 'churchSuiteEventIds': An array of ChurchSuite event IDs.
     *               - 'eventElements': An array of event element IDs, with ChurchSuite event IDs as keys.
     */
    public function getLocalData(): array
    {
        // Query for all ChurchSuite Event Elements
        $query = ChurchSuiteEvent::find()
            ->status(null)
            ->all();

        $data = [
            'churchSuiteEventIds' => [],
            'events' => []
        ];

        // For each event
        foreach ($query as $event) {
            $churchSuiteEventId = '';

            // Get the id of this event
            $churchSuiteEventId = isset($event->churchSuiteId) ? $event->churchSuiteId : '';

            // Add this id to our array
            $data['churchSuiteEventIds'][] = $churchSuiteEventId;

            // Add this entry id to our array, using the event id as the key for reference
            $data['events'][$churchSuiteEventId] = $event->id;
        }

        return $data;
    }

    public function getAPIData(): array|bool
    {
        Craft::info('ChurchSuiteEvents: Begin sync with JSON endpoint', __METHOD__);

        // Get all ChurchSuite events
        $client = new Client();

        $url = 'https://' . $this->settings->churchSuiteOrganisationHandle . '.churchsuite.com/embed/calendar/json';

        $response = $client->request('GET', $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ]);

        // Do we have a success response?
        if ($response->getStatusCode() !== 200) {
            Craft::error('ChurchSuiteEvents: Reponse Error ' . $response->getStatusCode() . ": " . $response->getReasonPhrase(), __METHOD__);

            return false;
        }

        $body = json_decode($response->getBody());

        // Are there any results
        if (!isset($body) || !count($body)) {
            Craft::error('ChurchSuiteEvents: No results from JSON Request', __METHOD__);

            return false;
        }

        $data = [
            'churchSuiteEventIds' => [],
            'events' => [],
        ];

        // For each returned event
        foreach ($body as $event) {
            // Get the id
            $churchSuiteEventId = $event->id;

            // Add this id to our array
            $data['churchSuiteEventIds'][] = $churchSuiteEventId;

            // Add this event to our array, using the id as the key
            $data['events'][$churchSuiteEventId] = $event;
        }

        Craft::info('ChurchSuiteEvents: Finished getting remote data', __METHOD__);

        return $data;
    }

    public function createElement($apiEvent): void
    {
        // Create a new instance of the Craft Entry Model
        $event = new ChurchSuiteEvent();

        $this->saveElementData(true, $event, $apiEvent);
    }

    public function updateElement($elementId, $apiEvent): void
    {
        // Create a new instance of the Craft Entry Model
        $event = ChurchSuiteEvent::find()
            ->id($elementId)
            ->status(null)
            ->one();

        $this->saveElementData(false, $event, $apiEvent);
    }

    public function closeElement($elementId): void
    {
        // Create a new instance of the Craft Entry Model
        $event = ChurchSuiteEvent::find()
            ->id($elementId)
            ->status(null)
            ->one();

        $event->enabled = false;

        // Re-save the entry
        Craft::$app->getElements()->saveElement($event);
    }

    // Private Methods
    // =========================================================================

    private function saveElementData(bool $isNew, $event, $apiEvent): bool
    {
        $elementsService = Craft::$app->getElements();

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Enabled?
            if ($isNew) {
                $event->enabled = $this->settings->enableEventsWhenCreated ? true : false;
            }

            // Set the title
            $event->title = $apiEvent->name;

            $event->churchSuiteId = $apiEvent->id;
            $event->identifier = $apiEvent->identifier;
            $event->publicVisible = $apiEvent->public_visible ?? false;
            $event->publicFeatured = $apiEvent->signup_options->public->featured ?? false;
            $event->embedVisible = $apiEvent->signup_options->embed->visible ?? false;
            $event->name = $apiEvent->name ?? '';
            $event->description = $apiEvent->description ?? null;
            $event->capacity = $apiEvent->capacity ?? null;
            $event->locationName = $apiEvent->location->name ?? null;
            $event->locationAddress = $apiEvent->location->address ?? null;
            $event->locationLatitude = $apiEvent->location->latitude ?? null;
            $event->locationLongitude = $apiEvent->location->longitude ?? null;
            $event->locationType = $apiEvent->location->type ?? null;
            $event->locationUrl = $apiEvent->location->url ?? null;
            $event->image = $apiEvent->images->original_100 ?? null;
            $event->ticketsEnabled = $apiEvent->signup_options->tickets->enabled ?? false;
            $event->ticketUrl = $apiEvent->signup_options->tickets->url ?? null;

            // Dates
            $startDate = $apiEvent->datetime_start ? new DateTime($apiEvent->datetime_start) : DateTimeHelper::toDateTime('now');
            $endDate = $apiEvent->datetime_end ? new DateTime($apiEvent->datetime_end) : DateTimeHelper::toDateTime('now');
            $event->startDate = $startDate;
            $event->endDate = $endDate;

            // Custom Fields
            $event->setFieldValues([
                'churchSuiteEventCategories' => (isset($apiEvent->category)) ? $this->parseCategory($apiEvent->category) : [],
                'churchSuiteEventImage' => (isset($apiEvent->images)) ? $this->parseImage($apiEvent->images) : [],
            ]);

            // Save the event!
            $success = $elementsService->saveElement($event);

            if (!$success) {
                $transaction->rollBack();
                Craft::error('ChurchSuiteEvents: Couldn\'t save the entry "' . $apiEvent->name . '"', __METHOD__);

                return $this->asModelFailure(
                    $event,
                    'Couldn\'t save the entry "' . $apiEvent->name . '"',
                    'chuchsuite-event'
                );
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    private function parseImage($apiImage): mixed
    {
        // If there is no volume specified, don't do this
        if (!$this->settings->imageVolumeHandle || !$apiImage) {
            return [];
        }

        // Get Volume & root folder
        $volume = Craft::$app->getVolumes()->getVolumeByHandle($this->settings->imageVolumeHandle);

        if (!$volume) {
            return [];
        }

        $assetsService = Craft::$app->getAssets();

        $folder = $assetsService->findFolder([
            'volumeId' => $volume->id,
            'parentId' => ':empty:',
        ]);

        if (!$folder) {
            return [];
        }

        // Check if an image already exists with this filename
        $imageUrl = $apiImage->lg ? $apiImage->lg->url : $apiImage->md->url;

        // Get asset by filename
        $filename = Assets::prepareAssetName($imageUrl);
        $asset = Asset::find()
            ->filename($filename)
            ->one();

        // If it exists, return it
        if ($asset) {
            return [$asset->id];
        }

        // Asset doesn't exist, create it and retun it's ID
        $elementsService = Craft::$app->getElements();

        // Save remote image to temp folder
        $tempFilename = FileHelper::uniqueName($filename);
        $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;
        file_put_contents($tempPath, fopen($imageUrl, 'r'));

        // Create a new asset element
        $asset = new Asset();
        $asset->tempFilePath = $tempPath;
        $asset->setFilename($filename);
        $asset->newFolderId = $folder->id;
        $asset->setVolumeId($volume->id);
        $asset->avoidFilenameConflicts = false;
        $asset->setScenario(Asset::SCENARIO_CREATE);

        if (!$elementsService->saveElement($asset)) {
            // Asset couldn't be saved for whatever reason
            return [];
        }

        return [$asset->id];
    }

    private function parseCategory($apiCategory): mixed
    {
        // If there is no category group specified, don't do this
        if (!$this->settings->categoryGroupHandle) {
            return false;
        }

        $craftCategories = [];

        // Get Category Group
        $categoryGroup = Craft::$app->getCategories()->getGroupByHandle($this->settings->categoryGroupHandle);

        // Query all categories in the group
        $query = Category::find()
            ->groupId($categoryGroup->id)
            ->all();

        // For each category
        foreach ($query as $category) {
            // Add its churchSuiteCategoryId and id to our array
            $craftCategories[$category->churchSuiteCategoryId] = $category->id;
        }

        $returnIds = [];

        // Does this apiCategory exist already as a Craft category?
        $categoryExists = false;

        foreach ($craftCategories as $churchSuiteSiteId => $id) {
            // apiCategory is already a Craft category
            if ($churchSuiteSiteId == $apiCategory->id) {
                $returnIds[] = $id;
                $categoryExists = true;

                break;
            }
        }

        // Do we need to create the Category?
        if (!$categoryExists) {
            // Create the category
            $newCategory = new Category();

            $newCategory->title = $apiCategory->name;
            $newCategory->groupId = $categoryGroup->id;

            $newCategory->setFieldValues([
                'churchSuiteCategoryId' => $apiCategory->id,
                'churchSuiteCategoryColour' => $apiCategory->color ?? ''
            ]);

            // Save the category!
            if (!Craft::$app->elements->saveElement($newCategory)) {
                Craft::error('ChurchSuite: Couldn\'t save the category "' . $newCategory->title . '"', __METHOD__);

                return false;
            }

            $returnIds[] = $newCategory->id;
        }

        return $returnIds;
    }

    private function checkSettings(): bool
    {
        $this->settings = Plugin::getInstance()->getSettings();

        // Check our Plugin's settings for the apiKey
        if ($this->settings->churchSuiteOrganisationHandle === null) {
            Craft::error('ChurchSuiteEvents: No organisation handle or ID set', __METHOD__);

            return false;
        }

        return true;
    }

    private function dd($data): void
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        die();
    }
}
