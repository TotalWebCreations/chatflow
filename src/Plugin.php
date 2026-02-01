<?php

namespace totalwebcreations\chatflow;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use totalwebcreations\chatflow\models\Settings;
use totalwebcreations\chatflow\services\FormsService;
use totalwebcreations\chatflow\services\QuestionsService;
use totalwebcreations\chatflow\services\SubmissionsService;
use totalwebcreations\chatflow\services\MailService;
use totalwebcreations\chatflow\services\NotificationService;
use totalwebcreations\chatflow\variables\ChatFlowVariable;
use yii\base\Event;

/**
 * ChatFlow plugin
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 * @author TotalWebCreations
 * @copyright TotalWebCreations
 * @license MIT
 * @property-read FormsService $forms
 * @property-read QuestionsService $questions
 * @property-read SubmissionsService $submissions
 * @property-read MailService $mail
 * @property-read NotificationService $notifications
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public static function config(): array
    {
        return [
            'components' => [
                'forms' => FormsService::class,
                'questions' => QuestionsService::class,
                'submissions' => SubmissionsService::class,
                'mail' => MailService::class,
                'notifications' => NotificationService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
        });
    }

    /**
     * Returns the path to the plugin's templates directory
     */
    public function getTemplatePath(): string
    {
        return $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('chatflow/settings', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();

        $item['label'] = 'ChatFlow';
        $item['url'] = 'chatflow';

        $item['subnav'] = [
            'forms' => ['label' => 'Forms', 'url' => 'chatflow/forms'],
            'submissions' => ['label' => 'Submissions', 'url' => 'chatflow/submissions'],
        ];

        return $item;
    }

    private function attachEventHandlers(): void
    {
        // Register CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['chatflow'] = 'chatflow/forms/index';
                $event->rules['chatflow/forms'] = 'chatflow/forms/index';
                $event->rules['chatflow/forms/new'] = 'chatflow/forms/edit';
                $event->rules['chatflow/forms/<formId:\d+>'] = 'chatflow/forms/edit';
                $event->rules['chatflow/submissions'] = 'chatflow/submissions/index';
                $event->rules['chatflow/submissions/<submissionId:\d+>'] = 'chatflow/submissions/detail';
            }
        );

        // Register site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['chatflow/submit'] = 'chatflow/submit/submit';
            }
        );

        // Register template variable
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('chatflow', ChatFlowVariable::class);
            }
        );
    }
}
