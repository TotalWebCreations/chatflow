<?php

namespace totalwebcreations\chatflow\controllers;

use Craft;
use craft\web\Controller;
use totalwebcreations\chatflow\Plugin;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Submissions controller
 */
class SubmissionsController extends Controller
{
    /**
     * Submissions index
     */
    public function actionIndex(?int $formId = null): Response
    {
        if ($formId) {
            $submissions = Plugin::getInstance()->submissions->getSubmissionsByFormId($formId);
            $form = Plugin::getInstance()->forms->getFormById($formId);
        } else {
            $submissions = Plugin::getInstance()->submissions->getAllSubmissions();
            $form = null;
        }

        $forms = Plugin::getInstance()->forms->getAllForms();

        return $this->renderTemplate('chatflow/submissions/index', [
            'submissions' => $submissions,
            'forms' => $forms,
            'selectedFormId' => $formId,
            'selectedForm' => $form,
        ]);
    }

    /**
     * View submission detail
     */
    public function actionDetail(int $submissionId): Response
    {
        $submission = Plugin::getInstance()->submissions->getSubmissionById($submissionId);

        if (!$submission) {
            throw new NotFoundHttpException('Submission not found');
        }

        $form = Plugin::getInstance()->forms->getFormById($submission->formId);

        return $this->renderTemplate('chatflow/submissions/_detail', [
            'submission' => $submission,
            'form' => $form,
        ]);
    }

    /**
     * Export submissions to CSV
     */
    public function actionExport(): Response
    {
        $this->requirePostRequest();

        $formId = Craft::$app->getRequest()->getBodyParam('formId');

        if ($formId) {
            // Export specific form
            $form = Plugin::getInstance()->forms->getFormById($formId);

            if (!$form) {
                throw new NotFoundHttpException('Form not found');
            }

            $csv = Plugin::getInstance()->submissions->exportToCsv($formId);
            $filename = 'chatflow-' . $form->handle . '-' . date('Y-m-d') . '.csv';
        } else {
            // Export all submissions
            $csv = Plugin::getInstance()->submissions->exportToCsv(null);
            $filename = 'chatflow-all-submissions-' . date('Y-m-d') . '.csv';
        }

        return Craft::$app->getResponse()->sendContentAsFile($csv, $filename, [
            'mimeType' => 'text/csv',
        ]);
    }

    /**
     * Delete submission
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $submissionId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        if (!Plugin::getInstance()->submissions->deleteSubmission($submissionId)) {
            return $this->asJson(['success' => false]);
        }

        return $this->asJson(['success' => true]);
    }
}
