<?php

namespace totalwebcreations\chatflow\variables;

use Craft;
use totalwebcreations\chatflow\Plugin;
use craft\web\View;

/**
 * ChatFlow template variable
 */
class ChatFlowVariable
{
    /**
     * Get form by handle for the current site
     */
    public function form(string $handle)
    {
        $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        return Plugin::getInstance()->forms->getFormByHandle($handle, $siteId);
    }

    /**
     * Get all forms
     */
    public function forms(): array
    {
        return Plugin::getInstance()->forms->getAllForms();
    }

    /**
     * Get submissions by form ID
     */
    public function submissions(int $formId): array
    {
        return Plugin::getInstance()->submissions->getSubmissionsByFormId($formId);
    }

    /**
     * Render modal template
     */
    public function modal(string $formHandle, string $triggerId): string
    {
        $plugin = Plugin::getInstance();
        $view = Craft::$app->view;

        // Get current template mode
        $oldTemplateMode = $view->getTemplateMode();

        // Switch to CP mode to access plugin templates
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        // Render the template
        $html = $view->renderTemplate('chatflow/_modal', [
            'formHandle' => $formHandle,
            'triggerId' => $triggerId,
        ]);

        // Restore original template mode
        $view->setTemplateMode($oldTemplateMode);

        return $html;
    }
}
