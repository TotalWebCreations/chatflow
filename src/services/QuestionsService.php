<?php

namespace totalwebcreations\chatflow\services;

use Craft;
use totalwebcreations\chatflow\models\Question;
use totalwebcreations\chatflow\records\QuestionRecord;
use totalwebcreations\chatflow\records\QuestionSiteRecord;
use yii\base\Component;

/**
 * Questions service
 */
class QuestionsService extends Component
{
    /**
     * Get questions by form ID for a specific site
     */
    public function getQuestionsByFormId(int $formId, ?int $siteId = null): array
    {
        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        $records = QuestionRecord::find()
            ->where(['formId' => $formId])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        $questions = [];
        foreach ($records as $record) {
            $questions[] = $this->createQuestionFromRecord($record, $siteId);
        }

        return $questions;
    }

    /**
     * Get question by ID for a specific site
     */
    public function getQuestionById(int $id, ?int $siteId = null): ?Question
    {
        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        $record = QuestionRecord::findOne($id);

        if (!$record) {
            return null;
        }

        return $this->createQuestionFromRecord($record, $siteId);
    }

    /**
     * Save question
     */
    public function saveQuestion(Question $question, ?int $siteId = null): bool
    {
        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        // Save core question data
        if ($question->id) {
            $record = QuestionRecord::findOne($question->id);
            if (!$record) {
                throw new \Exception('Question not found');
            }
        } else {
            $record = new QuestionRecord();
        }

        $record->formId = $question->formId;
        $record->fieldType = $question->fieldType;
        $record->fieldName = $question->fieldName;
        $record->validation = $question->validation ? json_encode($question->validation) : null;
        $record->sortOrder = $question->sortOrder;
        $record->required = $question->required;

        if (!$record->save()) {
            $question->addErrors($record->getErrors());
            return false;
        }

        $question->id = $record->id;

        // Save site-specific content
        $siteRecord = QuestionSiteRecord::findOne([
            'questionId' => $record->id,
            'siteId' => $siteId,
        ]);

        if (!$siteRecord) {
            $siteRecord = new QuestionSiteRecord();
            $siteRecord->questionId = $record->id;
            $siteRecord->siteId = $siteId;
        }

        $siteRecord->questionText = $question->questionText;
        $siteRecord->placeholder = $question->placeholder;
        $siteRecord->options = $question->options ? json_encode($question->options) : null;
        $siteRecord->skipText = $question->skipText;

        if (!$siteRecord->save()) {
            $question->addErrors($siteRecord->getErrors());
            return false;
        }

        return true;
    }

    /**
     * Delete question
     */
    public function deleteQuestion(int $id): bool
    {
        $record = QuestionRecord::findOne($id);

        if (!$record) {
            return false;
        }

        // Site records will be deleted automatically due to CASCADE foreign key
        return (bool) $record->delete();
    }

    /**
     * Reorder questions
     */
    public function reorderQuestions(array $questionIds): bool
    {
        foreach ($questionIds as $index => $id) {
            $record = QuestionRecord::findOne($id);
            if ($record) {
                $record->sortOrder = $index;
                $record->save(false);
            }
        }

        return true;
    }

    /**
     * Create Question model from Record for a specific site
     */
    public function createQuestionFromRecord(QuestionRecord $record, int $siteId): Question
    {
        $question = new Question();
        $question->id = $record->id;
        $question->formId = $record->formId;
        $question->fieldType = $record->fieldType;
        $question->fieldName = $record->fieldName;
        $question->validation = $record->validation ? json_decode($record->validation, true) : null;
        $question->sortOrder = $record->sortOrder;
        $question->required = (bool) $record->required;
        $question->siteId = $siteId;

        // Load site-specific content
        $siteRecord = QuestionSiteRecord::findOne([
            'questionId' => $record->id,
            'siteId' => $siteId,
        ]);

        if ($siteRecord) {
            $question->questionText = $siteRecord->questionText;
            $question->placeholder = $siteRecord->placeholder;
            $question->options = $siteRecord->options ? json_decode($siteRecord->options, true) : null;
            $question->skipText = $siteRecord->skipText;
        } else {
            // Fallback to primary site if no content exists for this site
            $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;
            if ($primarySiteId !== $siteId) {
                $primarySiteRecord = QuestionSiteRecord::findOne([
                    'questionId' => $record->id,
                    'siteId' => $primarySiteId,
                ]);

                if ($primarySiteRecord) {
                    $question->questionText = $primarySiteRecord->questionText;
                    $question->placeholder = $primarySiteRecord->placeholder;
                    $question->options = $primarySiteRecord->options ? json_decode($primarySiteRecord->options, true) : null;
                    $question->skipText = $primarySiteRecord->skipText;
                }
            }
        }

        return $question;
    }

    /**
     * Copy question content to another site
     */
    public function copyQuestionToSite(int $questionId, int $sourceSiteId, int $targetSiteId): bool
    {
        $sourceSiteRecord = QuestionSiteRecord::findOne([
            'questionId' => $questionId,
            'siteId' => $sourceSiteId,
        ]);

        if (!$sourceSiteRecord) {
            return false;
        }

        $targetSiteRecord = QuestionSiteRecord::findOne([
            'questionId' => $questionId,
            'siteId' => $targetSiteId,
        ]);

        if (!$targetSiteRecord) {
            $targetSiteRecord = new QuestionSiteRecord();
            $targetSiteRecord->questionId = $questionId;
            $targetSiteRecord->siteId = $targetSiteId;
        }

        $targetSiteRecord->questionText = $sourceSiteRecord->questionText;
        $targetSiteRecord->placeholder = $sourceSiteRecord->placeholder;
        $targetSiteRecord->options = $sourceSiteRecord->options;
        $targetSiteRecord->skipText = $sourceSiteRecord->skipText;

        return $targetSiteRecord->save();
    }
}
