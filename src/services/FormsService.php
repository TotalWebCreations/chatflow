<?php

namespace totalwebcreations\chatflow\services;

use Craft;
use totalwebcreations\chatflow\models\Form;
use totalwebcreations\chatflow\records\FormRecord;
use yii\base\Component;

/**
 * Forms service
 */
class FormsService extends Component
{
    /**
     * Get all forms
     */
    public function getAllForms(): array
    {
        $records = FormRecord::find()->all();
        $forms = [];

        foreach ($records as $record) {
            $forms[] = $this->createFormFromRecord($record);
        }

        return $forms;
    }

    /**
     * Get form by ID
     */
    public function getFormById(int $id, ?int $siteId = null): ?Form
    {
        $record = FormRecord::findOne($id);

        if (!$record) {
            return null;
        }

        return $this->createFormFromRecord($record, $siteId);
    }

    /**
     * Get form by handle
     */
    public function getFormByHandle(string $handle, ?int $siteId = null): ?Form
    {
        $record = FormRecord::findOne(['handle' => $handle]);

        if (!$record) {
            return null;
        }

        return $this->createFormFromRecord($record, $siteId);
    }

    /**
     * Save form
     */
    public function saveForm(Form $form): bool
    {
        if ($form->id) {
            $record = FormRecord::findOne($form->id);
            if (!$record) {
                throw new \Exception('Form not found');
            }
        } else {
            $record = new FormRecord();
        }

        $record->name = $form->name;
        $record->handle = $form->handle;
        $record->successMessage = $form->successMessage;

        // Email notifications
        $record->notificationEmails = $form->notificationEmails;
        $record->enableNotifications = $form->enableNotifications;

        // Slack notifications
        $record->enableSlack = $form->enableSlack;
        $record->slackWebhookUrl = $form->slackWebhookUrl;

        // Teams notifications
        $record->enableTeams = $form->enableTeams;
        $record->teamsWebhookUrl = $form->teamsWebhookUrl;

        // Custom webhook
        $record->enableWebhook = $form->enableWebhook;
        $record->webhookUrl = $form->webhookUrl;

        $record->settings = $form->settings ? json_encode($form->settings) : null;

        if (!$record->save()) {
            $form->addErrors($record->getErrors());
            return false;
        }

        $form->id = $record->id;

        return true;
    }

    /**
     * Delete form
     */
    public function deleteForm(int $id): bool
    {
        $record = FormRecord::findOne($id);

        if (!$record) {
            return false;
        }

        return (bool) $record->delete();
    }

    /**
     * Create Form model from Record
     */
    private function createFormFromRecord(FormRecord $record, ?int $siteId = null): Form
    {
        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        $form = new Form();
        $form->id = $record->id;
        $form->name = $record->name;
        $form->handle = $record->handle;
        $form->successMessage = $record->successMessage;

        // Email notifications
        $form->notificationEmails = $record->notificationEmails ?? '';
        $form->enableNotifications = (bool) $record->enableNotifications;

        // Slack notifications
        $form->enableSlack = (bool) ($record->enableSlack ?? false);
        $form->slackWebhookUrl = $record->slackWebhookUrl ?? '';

        // Teams notifications
        $form->enableTeams = (bool) ($record->enableTeams ?? false);
        $form->teamsWebhookUrl = $record->teamsWebhookUrl ?? '';

        // Custom webhook
        $form->enableWebhook = (bool) ($record->enableWebhook ?? false);
        $form->webhookUrl = $record->webhookUrl ?? '';

        $form->settings = $record->settings ? json_decode($record->settings, true) : null;

        // Load questions for the specified site
        $form->questions = [];
        foreach ($record->questions as $questionRecord) {
            $form->questions[] = Craft::$app->plugins->getPlugin('chatflow')->questions->createQuestionFromRecord($questionRecord, $siteId);
        }

        return $form;
    }
}
