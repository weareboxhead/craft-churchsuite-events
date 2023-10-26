<?php

namespace boxhead\craftchurchsuiteevents\utilities;

use Craft;
use craft\base\Utility;

/**
 * Sync Utility
 */
class SyncUtility extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('churchsuite-events', 'ChurchSuite Events Sync');
    }

    public static function id(): string
    {
        return 'churchsuite-events';
    }

    public static function iconPath(): ?string
    {
        $iconPath = Craft::getAlias('@churchsuite-events/icon-mask.svg');

        if (!is_string($iconPath)) {
            return null;
        }

        return $iconPath;
    }

    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('churchsuite-events/_utilities/actions.twig', [
            'actions' => self::getActions(),
        ]);
    }

    /**
     * Returns available actions.
     */
    public static function getActions(bool $showAll = false): array
    {
        $actions = [];

        $actions[] = [
            'id' => 'sync',
            'label' => Craft::t('churchsuite-events', Craft::t('churchsuite-events', 'Sync Now')),
            'instructions' => Craft::t('churchsuite-events', 'Run the ChurchSuite Events sync operation now.'),
        ];

        return $actions;
    }
}
