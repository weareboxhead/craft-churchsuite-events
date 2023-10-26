<?php

namespace boxhead\craftchurchsuiteevents\controllers;

use Craft;
use craft\web\View;
use yii\web\Response;
use craft\helpers\Queue;
use craft\web\Controller;
use craft\elements\Category;
use yii\web\BadRequestHttpException;
use boxhead\craftchurchsuiteevents\Plugin;
use boxhead\craftchurchsuiteevents\jobs\SyncEventsFromAPI;
use boxhead\craftchurchsuiteevents\elements\ChurchSuiteEvent;

/**
 * Events controller
 */
class EventsController extends Controller
{
    protected array|int|bool $allowAnonymous = ['sync', 'sync-test'];

    public function actionSaveFieldLayout(): Response
    {
        // Ensure the user has permission to save events
        $this->requirePermission('edit-events');

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = ChurchSuiteEvent::class;

        Craft::$app->getFields()->saveLayout($fieldLayout);

        return $this->getResponse('Field layout saved.');
    }

    /**
     * churchsuite-events/sync-test action
     */
    public function actionSyncTest(): void
    {
        // // Get local data
        // $localData = Plugin::getInstance()->syncService->getLocalData();

        // // Get remote data from JSON endpoint
        // $remoteData = Plugin::getInstance()->syncService->getAPIData();

        // // Determine which events we are missing by id
        // $missingIds = array_diff($remoteData['churchSuiteEventIds'], $localData['churchSuiteEventIds']);

        // // Determine which events we shouldn't have by id
        // $removeIds = array_diff($localData['churchSuiteEventIds'], $remoteData['churchSuiteEventIds']);

        // // Determine which events need updating (all active events which we haven't just created)
        // $updatingIds = array_diff($remoteData['churchSuiteEventIds'], $missingIds);

        // $categoryGroup = Craft::$app->getCategories()->getGroupByHandle(\boxhead\craftchurchsuiteevents\Plugin::getInstance()->getSettings()->categoryGroupHandle);
        // $query = Category::find()
        //     ->groupId($categoryGroup->id)
        //     ->all();

        // echo '<pre>';
        // print_r($query);
        // echo '</pre>';
        // die();
    }

    /**
     * churchsuite-events/sync action
     */
    public function actionSync(): Response
    {
        // Plugin::getInstance()->syncService->getAPIData();
        Queue::push(new SyncEventsFromAPI());

        $message = 'Sync in progress.';

        return $this->getResponse($message);
    }

    /**
     * Returns a response.
     */
    private function getResponse(string $message, bool $success = true): Response
    {
        $request = Craft::$app->getRequest();

        // If front-end or JSON request
        // Run the queue to ensure action is completed in full
        if (Craft::$app->getView()->templateMode == View::TEMPLATE_MODE_SITE || $request->getAcceptsJson()) {
            Craft::$app->runAction('queue/run');

            return $this->asJson([
                'success' => $success,
                'message' => Craft::t('churchsuite-events', $message),
            ]);
        }

        if ($success) {
            Craft::$app->getSession()->setNotice(Craft::t('churchsuite-events', $message));
        } else {
            Craft::$app->getSession()->setError(Craft::t('churchsuite-events', $message));
        }

        return $this->redirectToPostedUrl(null, $request->referrer);
    }
}
