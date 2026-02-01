<?php

namespace totalwebcreations\chatflow\models;

use craft\base\Model;
use totalwebcreations\chatflow\records\FormRecord;

/**
 * Form model
 */
class Form extends Model
{
    public ?int $id = null;
    public string $name = '';
    public string $handle = '';
    public ?string $successMessage = null;

    // Email notifications
    public string $notificationEmails = ''; // Comma-separated or newline-separated emails
    public bool $enableNotifications = true;

    // Slack notifications
    public bool $enableSlack = false;
    public string $slackWebhookUrl = '';

    // Microsoft Teams notifications
    public bool $enableTeams = false;
    public string $teamsWebhookUrl = '';

    // Custom webhook
    public bool $enableWebhook = false;
    public string $webhookUrl = '';

    public ?array $settings = null;
    public array $questions = [];

    public function defineRules(): array
    {
        return [
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [['handle'], 'match', 'pattern' => '/^[a-z][a-z0-9\-\_]*$/'],
            [['handle'], 'unique', 'targetClass' => FormRecord::class, 'targetAttribute' => 'handle', 'filter' => function($query) {
                if ($this->id) {
                    $query->andWhere(['not', ['id' => $this->id]]);
                }
            }],
            [['successMessage', 'notificationEmails', 'slackWebhookUrl', 'teamsWebhookUrl', 'webhookUrl'], 'string'],
            [['enableNotifications', 'enableSlack', 'enableTeams', 'enableWebhook'], 'boolean'],
        ];
    }

    /**
     * Get all notification emails as array
     */
    public function getNotificationEmailsArray(): array
    {
        if (empty($this->notificationEmails)) {
            return [];
        }

        // Split by newlines first, then by commas
        $lines = preg_split('/[\r\n]+/', $this->notificationEmails);
        $emails = [];

        foreach ($lines as $line) {
            $lineEmails = array_map('trim', explode(',', $line));
            $emails = array_merge($emails, $lineEmails);
        }

        // Filter out empty values and return unique
        $emails = array_filter($emails);
        return array_unique($emails);
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Name',
            'handle' => 'Handle',
            'successMessage' => 'Success Message',
            'notificationEmail' => 'Notification Email',
            'enableNotifications' => 'Enable Email Notifications',
        ];
    }
}
