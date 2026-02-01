<?php

namespace totalwebcreations\chatflow\services;

use Craft;
use totalwebcreations\chatflow\models\Submission;
use totalwebcreations\chatflow\Plugin;
use totalwebcreations\chatflow\records\SubmissionRecord;
use yii\base\Component;

/**
 * Submissions service
 */
class SubmissionsService extends Component
{
    /**
     * Get all submissions
     */
    public function getAllSubmissions(): array
    {
        $records = SubmissionRecord::find()
            ->orderBy(['dateCreated' => SORT_DESC])
            ->all();

        $submissions = [];
        foreach ($records as $record) {
            $submissions[] = $this->createSubmissionFromRecord($record);
        }

        return $submissions;
    }

    /**
     * Get submissions by form ID
     */
    public function getSubmissionsByFormId(int $formId): array
    {
        $records = SubmissionRecord::find()
            ->where(['formId' => $formId])
            ->orderBy(['dateCreated' => SORT_DESC])
            ->all();

        $submissions = [];
        foreach ($records as $record) {
            $submissions[] = $this->createSubmissionFromRecord($record);
        }

        return $submissions;
    }

    /**
     * Get submission by ID
     */
    public function getSubmissionById(int $id): ?Submission
    {
        $record = SubmissionRecord::findOne($id);

        if (!$record) {
            return null;
        }

        return $this->createSubmissionFromRecord($record);
    }

    /**
     * Save submission
     */
    public function saveSubmission(Submission $submission): bool
    {
        $record = new SubmissionRecord();
        $record->formId = $submission->formId;
        $record->data = json_encode($submission->data);
        $record->userAgent = $submission->userAgent;
        $record->ipAddress = $submission->ipAddress;

        if (!$record->save()) {
            $submission->addErrors($record->getErrors());
            return false;
        }

        $submission->id = $record->id;

        // Convert dateCreated string to DateTime object
        if ($record->dateCreated) {
            $submission->dateCreated = new \DateTime($record->dateCreated);
        }

        return true;
    }

    /**
     * Delete submission
     */
    public function deleteSubmission(int $id): bool
    {
        $record = SubmissionRecord::findOne($id);

        if (!$record) {
            return false;
        }

        return (bool) $record->delete();
    }

    /**
     * Export submissions to CSV
     */
    public function exportToCsv(?int $formId = null): string
    {
        if ($formId) {
            // Export specific form
            $form = Plugin::getInstance()->forms->getFormById($formId);
            $submissions = $this->getSubmissionsByFormId($formId);

            if (empty($submissions)) {
                return '';
            }

            // Get headers from questions
            $headers = ['ID', 'Date'];
            foreach ($form->questions as $question) {
                $headers[] = $question->fieldName;
            }

            // Build CSV
            $csv = [];
            $csv[] = $headers;

            foreach ($submissions as $submission) {
                $row = [
                    $submission->id,
                    $submission->dateCreated->format('Y-m-d H:i:s'),
                ];

                foreach ($form->questions as $question) {
                    $row[] = $submission->data[$question->fieldName] ?? '';
                }

                $csv[] = $row;
            }
        } else {
            // Export all submissions
            $submissions = $this->getAllSubmissions();

            if (empty($submissions)) {
                return '';
            }

            // Generic headers for all forms
            $headers = ['ID', 'Form', 'Date', 'Data'];

            // Build CSV
            $csv = [];
            $csv[] = $headers;

            foreach ($submissions as $submission) {
                $form = Plugin::getInstance()->forms->getFormById($submission->formId);

                $row = [
                    $submission->id,
                    $form ? $form->name : 'Unknown',
                    $submission->dateCreated->format('Y-m-d H:i:s'),
                    json_encode($submission->data),
                ];

                $csv[] = $row;
            }
        }

        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvString = stream_get_contents($output);
        fclose($output);

        return $csvString;
    }

    /**
     * Send webhook
     */
    private function sendWebhook(string $url, $form, Submission $submission): void
    {
        try {
            $client = Craft::createGuzzleClient();
            $client->post($url, [
                'json' => [
                    'formHandle' => $form->handle,
                    'formName' => $form->name,
                    'submissionId' => $submission->id,
                    'data' => $submission->data,
                    'dateCreated' => $submission->dateCreated->format('c'),
                ],
                'timeout' => 10,
            ]);
        } catch (\Exception $e) {
            Craft::error('Webhook failed: ' . $e->getMessage(), 'chatflow');
        }
    }

    /**
     * Create Submission model from Record
     */
    private function createSubmissionFromRecord(SubmissionRecord $record): Submission
    {
        $submission = new Submission();
        $submission->id = $record->id;
        $submission->formId = $record->formId;
        $submission->data = json_decode($record->data, true);
        $submission->userAgent = $record->userAgent;
        $submission->ipAddress = $record->ipAddress;

        // Convert dateCreated string to DateTime object
        if ($record->dateCreated) {
            $submission->dateCreated = new \DateTime($record->dateCreated);
        }

        return $submission;
    }
}
