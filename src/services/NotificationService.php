<?php

namespace totalwebcreations\chatflow\services;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use totalwebcreations\chatflow\models\Form;
use totalwebcreations\chatflow\models\Submission;
use totalwebcreations\chatflow\Plugin;
use GuzzleHttp\Client;

/**
 * Notification Service
 * Handles all notification types: Email, Slack, Teams, Webhooks
 */
class NotificationService extends Component
{
    /**
     * Send all enabled notifications for a submission
     * Uses form-level settings with fallback to plugin settings
     */
    public function sendNotifications(Submission $submission, Form $form): void
    {
        $settings = Plugin::getInstance()->getSettings();

        // Send email notifications (form level takes priority)
        if ($form->enableNotifications || $settings->enableEmailNotifications) {
            $this->sendEmailNotifications($submission, $form);
        }

        // Send Slack notification (check form level first, then plugin settings)
        $slackUrl = $form->enableSlack && !empty($form->slackWebhookUrl)
            ? $form->slackWebhookUrl
            : ($settings->enableSlack ? $settings->slackWebhookUrl : null);

        if (!empty($slackUrl)) {
            $this->sendSlackNotification($submission, $form, $slackUrl);
        }

        // Send Teams notification (check form level first, then plugin settings)
        $teamsUrl = $form->enableTeams && !empty($form->teamsWebhookUrl)
            ? $form->teamsWebhookUrl
            : ($settings->enableTeams ? $settings->teamsWebhookUrl : null);

        if (!empty($teamsUrl)) {
            $this->sendTeamsNotification($submission, $form, $teamsUrl);
        }

        // Send generic webhook (check form level first, then plugin settings)
        $webhookUrl = $form->enableWebhook && !empty($form->webhookUrl)
            ? $form->webhookUrl
            : ($settings->enableWebhooks ? $settings->webhookUrl : null);

        if (!empty($webhookUrl)) {
            $this->sendWebhook($submission, $form, $webhookUrl);
        }
    }

    /**
     * Send email notifications to all configured addresses
     * Uses form-level emails first, then falls back to plugin settings
     */
    private function sendEmailNotifications(Submission $submission, Form $form): void
    {
        $settings = Plugin::getInstance()->getSettings();
        $emails = [];

        // Get emails from form level
        if ($form->enableNotifications) {
            $formEmails = $form->getNotificationEmailsArray();
            $emails = array_merge($emails, $formEmails);
        }

        // If no form-level emails, use plugin settings
        if (empty($emails)) {
            $settingsEmails = $settings->getNotificationEmailsArray();
            $emails = array_merge($emails, $settingsEmails);
        }

        // Remove duplicates
        $emails = array_unique($emails);

        // Send to each email address
        foreach ($emails as $email) {
            if (!empty($email)) {
                Plugin::getInstance()->mail->sendNotificationEmail($submission, $form, $email);
            }
        }
    }

    /**
     * Send Slack notification
     */
    private function sendSlackNotification(Submission $submission, Form $form, string $webhookUrl): void
    {

        // Prepare submission data as fields
        $fields = [];
        $data = is_string($submission->data) ? json_decode($submission->data, true) : $submission->data;

        foreach ($data as $key => $value) {
            $fields[] = [
                'title' => ucfirst(str_replace('_', ' ', $key)),
                'value' => is_array($value) ? implode(', ', $value) : $value,
                'short' => strlen($value) < 40
            ];
        }

        // Build Slack message payload
        $payload = [
            'text' => '🎯 New ChatFlow Submission',
            'attachments' => [
                [
                    'color' => '#584998',
                    'title' => $form->name,
                    'fields' => $fields,
                    'footer' => 'ChatFlow',
                    'ts' => $submission->dateCreated->getTimestamp()
                ]
            ]
        ];

        $this->sendWebhookRequest($webhookUrl, $payload);
    }

    /**
     * Send Microsoft Teams notification
     */
    private function sendTeamsNotification(Submission $submission, Form $form, string $webhookUrl): void
    {

        // Prepare submission data as facts
        $facts = [];
        $data = is_string($submission->data) ? json_decode($submission->data, true) : $submission->data;

        foreach ($data as $key => $value) {
            $facts[] = [
                'name' => ucfirst(str_replace('_', ' ', $key)),
                'value' => is_array($value) ? implode(', ', $value) : $value
            ];
        }

        // Build Teams message payload (MessageCard format)
        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'https://schema.org/extensions',
            'summary' => 'New ChatFlow Submission',
            'themeColor' => '584998',
            'title' => '🎯 New ChatFlow Submission',
            'sections' => [
                [
                    'activityTitle' => $form->name,
                    'activitySubtitle' => $submission->dateCreated->format('F j, Y g:i A'),
                    'facts' => $facts
                ]
            ]
        ];

        $this->sendWebhookRequest($webhookUrl, $payload);
    }

    /**
     * Send generic webhook
     */
    private function sendWebhook(Submission $submission, Form $form, string $webhookUrl): void
    {

        $data = is_string($submission->data) ? json_decode($submission->data, true) : $submission->data;

        $payload = [
            'form' => [
                'id' => $form->id,
                'name' => $form->name,
                'handle' => $form->handle
            ],
            'submission' => [
                'id' => $submission->id,
                'data' => $data,
                'dateCreated' => $submission->dateCreated->format('c'),
                'ipAddress' => $submission->ipAddress,
                'userAgent' => $submission->userAgent
            ]
        ];

        $this->sendWebhookRequest($webhookUrl, $payload);
    }

    /**
     * Send HTTP POST request to webhook URL
     */
    private function sendWebhookRequest(string $url, array $payload): void
    {
        try {
            $client = Craft::createGuzzleClient([
                'timeout' => 10,
                'connect_timeout' => 5,
            ]);

            $response = $client->post($url, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'ChatFlow/1.0',
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                Craft::error('Webhook request failed: ' . $response->getStatusCode(), __METHOD__);
            }
        } catch (\Exception $e) {
            Craft::error('Webhook request exception: ' . $e->getMessage(), __METHOD__);
        }
    }
}
