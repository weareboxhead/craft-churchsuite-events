# churchsuite-events

Sync ChurchSuite Events into Craft as native elements.

## Requirements

This plugin requires Craft CMS 4.5.0 or later, and PHP 8.0.2 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require boxhead/craft-churchsuite-events

# tell Craft to install the plugin
./craft plugin/install churchsuite-events
```

## Configuring churchsuite-events

Once installed, you will need to configure the plugin settings with your ChurchSuite organisation/church handle as found in your ChurchSuite URLs. For example **abcd** in the URL https://**abcd**.churchsuite.com/.

The installation process will generate a number of custom fields, a filesystem and volume for storing event images, and a category group for event categories. You can change the names and labels of these elements but the handles should not be changed. Deleting these fields while the plugin is installed could also cause issues with the sync process.

You may create additional custom fields as your needs require and assign them to the ChurchSuiteEvent element field layout within the settings.

## Using churchsuite-events

The Sync process pulls in all available events from [ChurchSuite Calendar JSON feed](https://github.com/ChurchSuite/churchsuite-api/blob/master/modules/embed.md#calendar-json-feed) and creates a new Event element for each one. The sync process will also update existing events if they have been changed in ChurchSuite. The sync process will not delete events that have been deleted in ChurchSuite but will set the status to disabled for any missing of historic events.

The sync process can be run manually from the plugin ultities page or can be set to run automatically on a schedule. The sync process can also be run from the command line using the `./craft churchsuite-events/sync` command.

## Twig Templating

The plugin provides an `craft.churchsuiteevents()` method to query events. The method returns an instance of [craft\elements\db\ElementQuery](https://docs.craftcms.com/api/v4/craft-elements-db-elementquery.html) which can be used to further refine the query.

```twig
{% set events = craft.churchsuiteevents().all() %}
```
