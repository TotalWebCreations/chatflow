<?php

namespace totalwebcreations\chatflow\controllers;

use Craft;
use craft\web\Controller;
use totalwebcreations\chatflow\models\Form;
use totalwebcreations\chatflow\models\Question;
use totalwebcreations\chatflow\Plugin;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Forms controller
 */
class FormsController extends Controller
{
    /**
     * Forms index
     */
    public function actionIndex(): Response
    {
        $forms = Plugin::getInstance()->forms->getAllForms();

        return $this->renderTemplate('chatflow/forms/index', [
            'forms' => $forms,
        ]);
    }

    /**
     * Edit form
     */
    public function actionEdit(?int $formId = null, ?string $site = null): Response
    {
        // Get site
        if ($site) {
            $siteHandle = $site;
            $currentSite = Craft::$app->getSites()->getSiteByHandle($siteHandle);
            if (!$currentSite) {
                throw new NotFoundHttpException('Site not found');
            }
            $siteId = $currentSite->id;
        } else {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        if ($formId) {
            $form = Plugin::getInstance()->forms->getFormById($formId, $siteId);
            if (!$form) {
                throw new NotFoundHttpException('Form not found');
            }
        } else {
            $form = new Form();
            $form->successMessage = 'Perfect! We\'ll get back to you as soon as possible 👋';
        }

        // Get all sites for the site selector
        $sites = Craft::$app->getSites()->getAllSites();

        return $this->renderTemplate('chatflow/forms/_edit', [
            'form' => $form,
            'isNew' => !$formId,
            'currentSiteId' => $siteId,
            'sites' => $sites,
        ]);
    }

    /**
     * Save form
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $formId = $request->getBodyParam('formId');

        if ($formId) {
            $form = Plugin::getInstance()->forms->getFormById($formId);
            if (!$form) {
                throw new NotFoundHttpException('Form not found');
            }
        } else {
            $form = new Form();
        }

        $form->name = $request->getBodyParam('name');
        $form->handle = $request->getBodyParam('handle');
        $form->successMessage = $request->getBodyParam('successMessage');

        // Email notifications
        $form->notificationEmails = $request->getBodyParam('notificationEmails');
        $form->enableNotifications = (bool) $request->getBodyParam('enableNotifications');

        // Slack notifications
        $form->enableSlack = (bool) $request->getBodyParam('enableSlack');
        $form->slackWebhookUrl = $request->getBodyParam('slackWebhookUrl');

        // Teams notifications
        $form->enableTeams = (bool) $request->getBodyParam('enableTeams');
        $form->teamsWebhookUrl = $request->getBodyParam('teamsWebhookUrl');

        // Custom webhook
        $form->enableWebhook = (bool) $request->getBodyParam('enableWebhook');
        $form->webhookUrl = $request->getBodyParam('webhookUrl');

        if (!Plugin::getInstance()->forms->saveForm($form)) {
            Craft::$app->getSession()->setError('Couldn\'t save form.');

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
            ]);

            return null;
        }

        // Delete marked questions
        $deletedQuestions = $request->getBodyParam('deletedQuestions', []);
        foreach ($deletedQuestions as $questionId) {
            Plugin::getInstance()->questions->deleteQuestion((int)$questionId);
        }

        // Save questions
        $questions = $request->getBodyParam('questions', []);
        $this->saveQuestions($form->id, $questions);

        Craft::$app->getSession()->setNotice('Form saved.');

        return $this->redirectToPostedUrl($form);
    }

    /**
     * Delete form
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $formId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        if (!Plugin::getInstance()->forms->deleteForm($formId)) {
            return $this->asJson(['success' => false]);
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * Save questions
     */
    private function saveQuestions(int $formId, array $questionsData): void
    {
        // Get current site ID for saving site-specific content
        $siteId = Craft::$app->getRequest()->getBodyParam('siteId')
            ?? Craft::$app->getSites()->getCurrentSite()->id;

        $sortOrder = 0;

        foreach ($questionsData as $questionData) {
            $questionId = $questionData['id'] ?? null;

            if ($questionId) {
                $question = Plugin::getInstance()->questions->getQuestionById($questionId, $siteId);
            } else {
                $question = new Question();
                $question->formId = $formId;
            }

            // Site-specific content
            $question->questionText = $questionData['questionText'] ?? '';
            $question->placeholder = $questionData['placeholder'] ?? null;
            $question->skipText = $questionData['skipText'] ?? null;

            // Core question data (not site-specific)
            $question->fieldType = $questionData['fieldType'] ?? 'text';
            $question->fieldName = $questionData['fieldName'] ?? '';
            $question->required = (bool) ($questionData['required'] ?? true);
            $question->sortOrder = $sortOrder++;

            // Handle options for 'buttons' type (site-specific)
            if ($question->fieldType === 'buttons' && isset($questionData['options'])) {
                // Convert textarea string to array (split by newlines)
                if (is_string($questionData['options'])) {
                    $question->options = array_filter(array_map('trim', explode("\n", $questionData['options'])));
                } else {
                    $question->options = $questionData['options'];
                }
            } else {
                $question->options = null;
            }

            Plugin::getInstance()->questions->saveQuestion($question, $siteId);
        }
    }
}
