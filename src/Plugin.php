<?php

namespace boxhead\craftchurchsuiteevents;

use Craft;
use Exception;
use craft\db\Query;
use craft\db\Table;
use yii\base\Event;
use craft\base\Field;
use craft\base\Model;
use craft\services\Gc;
use craft\models\Volume;
use craft\web\UrlManager;
use craft\models\FieldGroup;
use craft\services\Elements;
use craft\events\ConfigEvent;
use craft\models\FieldLayout;
use craft\services\Utilities;
use craft\models\CategoryGroup;
use craft\models\FieldLayoutTab;
use craft\web\twig\variables\Cp;
use craft\services\UserPermissions;
use craft\base\Plugin as BasePlugin;
use craft\events\DefineBehaviorsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\fieldlayoutelements\TextField;
use craft\events\RegisterCpNavItemsEvent;
use craft\fieldlayoutelements\CustomField;
use craft\web\twig\variables\CraftVariable;
use craft\models\CategoryGroup_SiteSettings;
use craft\events\RegisterComponentTypesEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterUserPermissionsEvent;
use boxhead\craftchurchsuiteevents\models\Settings;
use boxhead\craftchurchsuiteevents\services\SyncService;
use boxhead\craftchurchsuiteevents\utilities\SyncUtility;
use boxhead\craftchurchsuiteevents\elements\ChurchSuiteEvent;
use boxhead\craftchurchsuiteevents\variables\CraftVariableBehavior;

/**
 * churchsuite-events plugin
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 * @author Boxhead <matt@boxhead.io>
 * @copyright Boxhead
 * @license https://craftcms.github.io/license/ Craft License
 * @property-read SyncService $syncService
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public static function config(): array
    {
        return [
            'components' => ['syncService' => SyncService::class],
        ];
    }

    public function beforeInstall(): void
    {
        // Check version before installing
        if (version_compare(Craft::$app->getInfo()->version, '4.0', '<')) {
            throw new Exception('ChurchSuite Events requires Craft CMS 4+ in order to run.');
        }

        if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 80000) {
            Craft::error('ChurchSuite Events requires PHP 8.0.2+ in order to run.');
        }

        $this->buildCategoryGroupAndField();
    }

    public function beforeUninstall(): void
    {
        $this->clearUpData();
    }

    public function init(): void
    {
        parent::init();

        Craft::setAlias('@churchsuite-events', __DIR__);

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function () {
            $this->attachEventHandlers();
        });

        // Handle Project Config updates
        Craft::$app->getProjectConfig()
            ->onUpdate('churchsuite-events.eventFieldLayout', [$this, 'handleChangedChurchSuiteEventFieldLayout']);

        // Add the Category Group to plugin settings
        $categoryGroup = Craft::$app->getCategories()->getGroupByHandle('churchSuiteEventCategories');

        if ($categoryGroup) {
            Craft::$app->getPlugins()->savePluginSettings($this, [
                'categoryGroupHandle' => $categoryGroup->handle,
                'imageVolumeHandle' => 'churchSuiteEventImages'
            ]);
        }
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $currentUser = Craft::$app->user;

        $subNav = [
            'events' => ['label' => 'Event List', 'url' => 'churchsuite-events'],
        ];

        if (Craft::$app->getConfig()->general->allowAdminChanges && $currentUser->getIsAdmin()) {
            $subNav['settings'] = ['label' => 'Settings', 'url' => 'churchsuite-events/settings'];
        }

        $item['subnav'] = $subNav;

        return $item;
    }

    public function handleChangedChurchSuiteEventFieldLayout(ConfigEvent $event)
    {
        $layout = FieldLayout::createFromConfig($event->newValue);

        // The `type` is not stored in project config:
        $layout->type = ChurchSuiteEvent::class;

        // Look up and assign the database ID, if it exists:
        $id = (new Query())
            ->select(['id'])
            ->from([Table::FIELDLAYOUTS])
            ->where(['type' => ChurchSuiteEvent::class])
            ->scalar();

        // This might be null, but that's OK—just means it's new!
        $layout->id = $id;

        Craft::$app->getFields()->saveLayout($layout, false);
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('churchsuite-events/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        // Sync Utility
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = SyncUtility::class;
        });

        // ChurchSuite Event Element
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = ChurchSuiteEvent::class;
        });

        // ChurchSuite Event URL Rules
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            // Event List & Edit Pages
            $event->rules['churchsuite-events'] = ['template' => 'churchsuite-events/events/_index.twig'];
            $event->rules['churchsuite-events/<elementId:\d+>'] = 'elements/edit';

            // Field Layout Page & Actions
            $event->rules['churchsuite-events/settings'] = ['template' => 'churchsuite-events/events/field-layout.twig'];
            // $event->rules['churchsuite-events/save-field-layout'] = 'churchsuite-events/events/save-field-layout';

            // Sync actions
            $event->rules['churchsuite-events/sync-test'] = 'churchsuite-events/events/sync-test';
            $event->rules['churchsuite-events/sync'] = 'churchsuite-events/events/sync';
        });

        // ChurchSuite Event Variable
        Event::on(CraftVariable::class, CraftVariable::EVENT_DEFINE_BEHAVIORS, function (DefineBehaviorsEvent $event) {
            $event->sender->attachBehaviors([
                CraftVariableBehavior::class,
            ]);
        });

        // Register User Permissions
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => 'ChurchSuite Events',
                    'permissions' => [
                        'editChurchSuiteEvents' => [
                            'label' => 'Can Edit Events',
                        ],
                        'deleteChurchSuiteEvents' => [
                            'label' => 'Can Delete Events',
                        ],
                    ],
                ];
            }
        );

        // ChurchSuite Event Element Garbage Collection
        Event::on(Gc::class, Gc::EVENT_RUN, function (Event $event) {
            // Delete `elements` table rows without peers in our custom churchsuite_events table
            Craft::$app->getGc()->deletePartialElements(
                ChurchSuiteEvent::class,
                'churchsuite_events',
                'id',
            );

            // Delete `elements` table rows without corresponding `content` table rows for the custom element
            Craft::$app->getGc()->deletePartialElements(
                ChurchSuiteEvent::class,
                Table::CONTENT,
                'elementId',
            );
        });
    }

    private function buildCategoryGroupAndField(): bool
    {
        $fieldsService = Craft::$app->getFields();

        // Check if field group already exists
        $fieldGroupId = null;

        foreach ($fieldsService->getAllGroups() as $group) {
            if ($group->name === 'ChurchSuite Events') {
                $fieldGroupId = $group->id;
            }
        }

        // Create a new Field Group
        if (!$fieldGroupId) {
            $fieldGroup = new FieldGroup();
            $fieldGroup->name = 'ChurchSuite Events';

            if (!$fieldsService->saveGroup($fieldGroup)) {
                Craft::error('ChurchSuiteEvents: Couldn\'t save the field group.', __METHOD__);
            }

            $fieldGroupId = $fieldGroup->id;
        }

        // =====================

        // Check if custom fields already exist
        $colourField = null;
        $categoryIdField = null;
        $categoryField = null;
        $imageField = null;

        foreach ($fieldsService->getAllFields() as $field) {
            if ($field->handle === 'churchSuiteCategoryColour') {
                $colourField = $field;
            }

            if ($field->handle === 'churchSuiteCategoryId') {
                $categoryIdField = $field;
            }

            if ($field->handle === 'churchSuiteEventCategories') {
                $categoryField = $field;
            }

            if ($field->handle === 'churchSuiteEventImage') {
                $imageField = $field;
            }
        }

        // Create the colour field
        if (!$colourField) {
            $colourField = $fieldsService->createField([
                'type' => 'craft\fields\Color',
                'id' => null,
                'uid' => null,
                'groupId' => $fieldGroupId,
                'name' => 'ChurchSuite Category Colour',
                'handle' => 'churchSuiteCategoryColour',
                'columnSuffix' => null,
                'instructions' => 'The category colour',
                'searchable' => false,
                'translationMethod' => Field::TRANSLATION_METHOD_NONE,
                'settings' => [
                    'defaultColor' => '00acec',
                ],
            ]);

            if (!$fieldsService->saveField($colourField)) {
                Craft::error('ChurchSuiteEvents: Couldn\'t save colour field.', __METHOD__);

                return false;
            }
        }

        // Create the category ID field
        if (!$categoryIdField) {
            $categoryIdField = $fieldsService->createField([
                'type' => 'craft\fields\Number',
                'id' => null,
                'uid' => null,
                'groupId' => $fieldGroupId,
                'name' => 'ChurchSuite Category ID',
                'handle' => 'churchSuiteCategoryId',
                'columnSuffix' => null,
                'instructions' => 'The category ID',
                'searchable' => true,
                'translationMethod' => Field::TRANSLATION_METHOD_NONE,
            ]);

            if (!$fieldsService->saveField($categoryIdField)) {
                Craft::error('ChurchSuiteEvents: Couldn\'t save category ID field.', __METHOD__);

                return false;
            }
        }

        // =====================

        // Check if the category group already exists
        $categoriesService = Craft::$app->getCategories();
        $categoryGroup = null;

        foreach ($categoriesService->getAllGroups() as $group) {
            if ($group->handle === 'churchSuiteEventCategories') {
                $categoryGroup = $group;
            }
        }

        // Create the category group
        if (!$categoryGroup) {
            $categoryGroup = new CategoryGroup();
            $categoryGroup->name = 'ChurchSuite Event Categories';
            $categoryGroup->handle = 'churchSuiteEventCategories';
            $categoryGroup->maxLevels = 1;
            $categoryGroup->defaultPlacement = 'end';

            $categoryGroupFieldLayout = FieldLayout::createFromConfig([
                'tabs' => [
                    [
                        'name' => 'Content',
                        'elements' => [
                            [
                                "label" => null,
                                "type" => CustomField::class,
                                "required" => false,
                                "fieldUid" => $colourField->uid,
                            ],
                            [
                                "label" => null,
                                "type" => CustomField::class,
                                "required" => false,
                                "fieldUid" => $categoryIdField->uid,
                            ],
                        ],
                    ],
                ],
            ]);

            // Site-specific settings
            $allSiteSettings = [];

            foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
                $siteSettings = new CategoryGroup_SiteSettings();
                $siteSettings->siteId = $siteId;
                $siteSettings->uriFormat = null;
                $siteSettings->template = null;
                $siteSettings->hasUrls = false;

                $allSiteSettings[$siteId] = $siteSettings;
            }

            $categoryGroup->setSiteSettings($allSiteSettings);

            $categoryGroupFieldLayout->type = Category::class;
            $categoryGroup->setFieldLayout($categoryGroupFieldLayout);

            // Save the category group
            if (!$categoriesService->saveGroup($categoryGroup)) {
                Craft::error('ChurchSuiteEvents: Couldn\'t save category group.', __METHOD__);

                return false;
            }
        }

        // Create the category field
        if (!$categoryField) {
            $categoryField = $fieldsService->createField([
                'type' => 'craft\fields\Categories',
                'id' => null,
                'uid' => null,
                'groupId' => $fieldGroupId,
                'name' => 'ChurchSuite Event Categories',
                'handle' => 'churchSuiteEventCategories',
                'columnSuffix' => null,
                'instructions' => 'Categories associated with the event',
                'searchable' => true,
                'translationMethod' => Field::TRANSLATION_METHOD_NONE,
                'settings' => [
                    'minRelations' => 0,
                    'maxRelations' => 1,
                    'source' => 'group:' . $categoryGroup->uid,
                    'selectionLabel' => 'Select Category',
                ],
            ]);

            // Save the field
            if (!$fieldsService->saveField($categoryField)) {
                Craft::error('ChurchSuiteEvents: Couldn\'t save category field.', __METHOD__);

                return false;
            }
        }

        // =====================

        // Create local filesystem for images if doesn't already exist
        $fsService = Craft::$app->getFs();
        $fs = $fsService->getFilesystemByHandle('localChurchSuiteEventImages');

        if (!$fs) {
            // $fsService->createDirectory('@webroot/churchsuite-event-images');

            $fs = $fsService->createFilesystem([
                'type' => 'craft\fs\Local',
                'name' => 'Local ChurchSuite Event Images',
                'handle' => 'localChurchSuiteEventImages',
                'settings' => [
                    'hasUrls' => 1,
                    'url' => '@web/churchsuite-event-images',
                    'path' => '@webroot/churchsuite-event-images',
                ],
            ]);

            if (!$fsService->saveFilesystem($fs)) {
                Craft::error('ChurchSuiteEvents: Couldn\'t save the filesystem.', __METHOD__);
            }
        }

        // Create volume for images if doesn't already exist
        $volumesService = Craft::$app->getVolumes();
        $volume = $volumesService->getVolumeByHandle('churchSuiteEventImages');

        if (!$volume) {
            $volume = new Volume([
                'name' => 'ChurchSuite Event Images',
                'handle' => 'churchSuiteEventImages',
                'fsHandle' => 'localChurchSuiteEventImages',
                'transformFsHandle' => 'localChurchSuiteEventImages',
                'transformSubpath' => '',
                'titleTranslationMethod' => Field::TRANSLATION_METHOD_SITE,
            ]);

            if (!$volumesService->saveVolume($volume)) {
                Craft::error('ChurchSuiteEvents: Couldn\'t save the volume.', __METHOD__);

                return false;
            }
        }

        if (!$imageField) {
            $imageField = $fieldsService->createField([
                'type' => 'craft\fields\Assets',
                'id' => null,
                'uid' => null,
                'groupId' => $fieldGroupId,
                'name' => 'ChurchSuite Event Image',
                'handle' => 'churchSuiteEventImage',
                'columnSuffix' => null,
                'instructions' => 'Image associated with the event',
                'searchable' => true,
                'translationMethod' => Field::TRANSLATION_METHOD_NONE,
                'settings' => [
                    'restrictLocation' => 1,
                    'restrictedLocationSource' => 'volume:' . $volume->uid,
                    'restrictedLocationSubpath' => '',
                    'restrictFiles' => 1,
                    'allowedKinds' => [
                        'image'
                    ],
                    'allowUploads' => 1,
                    'maxRelations' => 1,
                    'viewMode' => 'large',
                    'selectionLabel' => 'Add Image',
                    'previewMode' => 'full'
                ],
            ]);

            // Save the field
            if (!$fieldsService->saveField($imageField)) {
                Craft::error('ChurchSuiteEvents: Couldn\'t save image field.', __METHOD__);

                return false;
            }
        }

        // Add the category and image fields to the default field layout
        // Create a field layout for the Event Element
        $fieldLayout = FieldLayout::createFromConfig([
            'tabs' => [
                [
                    'name' => 'Content',
                    'elements' => [
                        [
                            "label" => null,
                            "type" => CustomField::class,
                            "required" => false,
                            "fieldUid" => $categoryField->uid,
                        ],
                        [
                            "label" => null,
                            "type" => CustomField::class,
                            "required" => false,
                            "fieldUid" => $imageField->uid,
                        ],
                    ],
                ],
            ],
        ]);

        $fieldLayout->type = ChurchSuiteEvent::class;

        // Save the field layout
        $fieldsService->saveLayout($fieldLayout);

        return true;
    }

    private function clearUpData(): bool
    {
        // Delete ChurchSuiteEvent elements
        $elements = ChurchSuiteEvent::find()->all();
        foreach ($elements as $element) {
            Craft::$app->getElements()->deleteElement($element, true);
        }

        // Delete the field layout
        $fieldLayout = Craft::$app->getFields()->getLayoutByType(ChurchSuiteEvent::class);
        Craft::$app->getFields()->deleteLayout($fieldLayout);

        return true;
    }
}
