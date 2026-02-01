<?php

namespace totalwebcreations\chatflow\services;

use Craft;
use totalwebcreations\chatflow\models\Form;
use totalwebcreations\chatflow\models\Submission;
use totalwebcreations\chatflow\Plugin;
use yii\base\Component;

/**
 * Mail service
 */
class MailService extends Component
{
    /**
     * Send notification email to a specific address
     */
    public function sendNotificationEmail(Submission $submission, Form $form, string $email): bool
    {
        if (empty($email)) {
            return false;
        }

        // Build email body
        $body = $this->buildEmailBody($form, $submission);

        try {
            $subject = Craft::t('chatflow', 'New ChatFlow Submission: {formName}', [
                'formName' => $form->name
            ]);

            $message = Craft::$app->mailer->compose()
                ->setTo($email)
                ->setSubject($subject)
                ->setHtmlBody($body);

            return $message->send();
        } catch (\Exception $e) {
            Craft::error('Failed to send notification email to ' . $email . ': ' . $e->getMessage(), 'chatflow');
            return false;
        }
    }

    /**
     * Send notification email (legacy method for backwards compatibility)
     */
    public function sendNotification(Form $form, Submission $submission): bool
    {
        $settings = Plugin::getInstance()->getSettings();

        // Determine recipient email
        $to = $form->notificationEmail ?: $settings->defaultNotificationEmail;

        if (!$to) {
            Craft::warning('No notification email configured for form: ' . $form->name, 'chatflow');
            return false;
        }

        return $this->sendNotificationEmail($submission, $form, $to);
    }

    /**
     * Build email body HTML
     */
    private function buildEmailBody(Form $form, Submission $submission): string
    {
        $html = '<html><body style="font-family: Arial, sans-serif; color: #333;">';
        $html .= '<h2 style="color: #584998;">' . Craft::t('chatflow', 'New Submission') . '</h2>';
        $html .= '<p><strong>' . Craft::t('chatflow', 'Form') . ':</strong> ' . htmlspecialchars($form->name) . '</p>';
        $html .= '<p><strong>' . Craft::t('chatflow', 'Date') . ':</strong> ' . $submission->dateCreated->format('F j, Y, g:i a') . '</p>';
        $html .= '<hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">';

        $html .= '<h3>' . Craft::t('chatflow', 'Answers') . ':</h3>';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';

        foreach ($form->questions as $question) {
            $fieldName = $question->fieldName;
            $value = $submission->data[$fieldName] ?? '(not answered)';

            $html .= '<tr>';
            $html .= '<td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; width: 30%;">';
            $html .= htmlspecialchars($question->questionText);
            $html .= '</td>';
            $html .= '<td style="padding: 10px; border-bottom: 1px solid #eee;">';
            $html .= htmlspecialchars($value);
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $html .= '<hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">';
        $html .= '<p style="font-size: 12px; color: #999;">' . Craft::t('chatflow', 'Submission ID') . ': ' . $submission->id . '</p>';
        $html .= '<p style="font-size: 12px; color: #999;">' . Craft::t('chatflow', 'IP Address') . ': ' . ($submission->ipAddress ?? 'Unknown') . '</p>';
        $html .= '</body></html>';

        return $html;
    }
}
