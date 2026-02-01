<?php

namespace totalwebcreations\chatflow\controllers;

use Craft;
use craft\web\Controller;
use totalwebcreations\chatflow\models\Submission;
use totalwebcreations\chatflow\Plugin;
use yii\web\Response;

/**
 * Submit controller (public endpoint)
 */
class SubmitController extends Controller
{
    protected array|bool|int $allowAnonymous = ['submit'];

    /**
     * Submit form
     */
    public function actionSubmit(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $formHandle = $request->getRequiredBodyParam('formHandle');
        $data = $request->getBodyParam('data', []);

        // Get form
        $form = Plugin::getInstance()->forms->getFormByHandle($formHandle);

        if (!$form) {
            return $this->asJson([
                'success' => false,
                'message' => 'Form not found',
            ]);
        }

        // Spam protection check
        $ipAddress = $request->getUserIP();
        $spamCheck = Plugin::getInstance()->spamProtection->validateSubmission($data, $ipAddress);

        if (!$spamCheck['valid']) {
            Craft::warning("Spam detected for form '{$formHandle}' from IP {$ipAddress}: {$spamCheck['error']}", 'chatflow');
            return $this->asJson([
                'success' => false,
                'message' => $spamCheck['error'],
            ]);
        }

        // Validate data
        $errors = $this->validateSubmission($form, $data);
        if (!empty($errors)) {
            return $this->asJson([
                'success' => false,
                'errors' => $errors,
            ]);
        }

        // Create submission
        $submission = new Submission();
        $submission->formId = $form->id;
        $submission->data = $data;
        $submission->userAgent = $request->getUserAgent();
        $submission->ipAddress = $request->getUserIP();

        // Save submission
        if (!Plugin::getInstance()->submissions->saveSubmission($submission)) {
            return $this->asJson([
                'success' => false,
                'message' => 'Could not save submission',
            ]);
        }

        // Send all notifications (Email, Slack, Teams, Webhooks)
        try {
            Plugin::getInstance()->notifications->sendNotifications($submission, $form);
        } catch (\Exception $e) {
            Craft::error('Failed to send notifications: ' . $e->getMessage(), __METHOD__);
        }

        return $this->asJson([
            'success' => true,
            'message' => $form->successMessage ?: 'Thank you for your submission!',
            'submissionId' => $submission->id,
        ]);
    }

    /**
     * Validate submission data
     */
    private function validateSubmission($form, array $data): array
    {
        $errors = [];

        foreach ($form->questions as $question) {
            $value = $data[$question->fieldName] ?? null;

            // Required check
            if ($question->required && empty($value)) {
                $errors[$question->fieldName] = 'This field is required';
                continue;
            }

            // Skip validation if empty and not required
            if (empty($value)) {
                continue;
            }

            // Email validation
            if ($question->fieldType === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$question->fieldName] = 'Please enter a valid email address';
            }
        }

        return $errors;
    }
}
