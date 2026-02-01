<?php

namespace totalwebcreations\chatflow\models;

use craft\base\Model;

/**
 * ChatFlow settings
 */
class Settings extends Model
{
    public string $defaultNotificationEmail = '';
    public string $notificationEmails = ''; // Comma-separated list of emails
    public string $webhookUrl = '';
    public bool $enableWebhooks = false;
    public bool $enableEmailNotifications = true;

    // Slack notifications
    public bool $enableSlack = false;
    public string $slackWebhookUrl = '';

    // Microsoft Teams notifications
    public bool $enableTeams = false;
    public string $teamsWebhookUrl = '';

    // Appearance settings
    public string $avatarType = 'solid'; // 'solid', 'gradient', 'image'
    public string $primaryColor = '#0891b2'; // Teal/cyan
    public string $secondaryColor = '#06b6d4'; // Lighter cyan
    public string $initials = 'CF'; // Default initials for ChatFlow
    public int|array|null $avatarImage = null; // Asset ID for custom image (array from form, int when saved)

    public function defineRules(): array
    {
        return [
            [['defaultNotificationEmail'], 'email'],
            [['notificationEmails'], 'string'],
            [['webhookUrl', 'slackWebhookUrl', 'teamsWebhookUrl'], 'url'],
            [['enableWebhooks', 'enableEmailNotifications', 'enableSlack', 'enableTeams'], 'boolean'],
            [['avatarType', 'primaryColor', 'secondaryColor', 'initials'], 'string'],
            [['avatarImage'], 'safe'],
        ];
    }

    public function beforeValidate(): bool
    {
        // Convert avatarImage array to single ID before validation
        if (is_array($this->avatarImage)) {
            $this->avatarImage = $this->avatarImage[0] ?? null;
        }

        // Ensure color values have # prefix
        $this->primaryColor = $this->ensureHashPrefix($this->primaryColor);
        $this->secondaryColor = $this->ensureHashPrefix($this->secondaryColor);

        return parent::beforeValidate();
    }

    /**
     * Get all notification emails as array
     */
    public function getNotificationEmailsArray(): array
    {
        $emails = [];

        // Add default email if set
        if (!empty($this->defaultNotificationEmail)) {
            $emails[] = $this->defaultNotificationEmail;
        }

        // Add additional emails
        if (!empty($this->notificationEmails)) {
            $additionalEmails = array_map('trim', explode(',', $this->notificationEmails));
            $additionalEmails = array_filter($additionalEmails); // Remove empty values
            $emails = array_merge($emails, $additionalEmails);
        }

        // Return unique emails
        return array_unique($emails);
    }

    /**
     * Get avatar background CSS
     */
    public function getAvatarBackgroundStyle(): string
    {
        $primaryColor = $this->ensureHashPrefix($this->primaryColor);
        $secondaryColor = $this->ensureHashPrefix($this->secondaryColor);

        switch ($this->avatarType) {
            case 'solid':
                return "background-color: {$primaryColor};";

            case 'gradient':
                return "background-image: linear-gradient(to bottom right, {$primaryColor}, {$secondaryColor});";

            case 'image':
                if ($this->avatarImage && is_int($this->avatarImage)) {
                    $asset = \Craft::$app->assets->getAssetById($this->avatarImage);
                    if ($asset && $asset->getUrl()) {
                        return "background-image: url('{$asset->getUrl()}'); background-size: cover; background-position: center;";
                    }
                }
                // Fallback to solid color
                return "background-color: {$primaryColor};";

            default:
                return "background-color: {$primaryColor};";
        }
    }

    /**
     * Ensure color has # prefix
     */
    private function ensureHashPrefix(string $color): string
    {
        return str_starts_with($color, '#') ? $color : "#{$color}";
    }

    /**
     * Check if avatar should show initials
     */
    public function showInitials(): bool
    {
        return in_array($this->avatarType, ['solid', 'gradient']);
    }

    /**
     * Get text color based on background brightness
     */
    public function getInitialsColor(): string
    {
        // Get primary color for calculation
        $color = $this->ensureHashPrefix($this->primaryColor);

        // Remove # and convert to RGB
        $hex = ltrim($color, '#');

        // Convert to RGB
        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        // Calculate perceived brightness (formula: https://www.w3.org/TR/AERT/#color-contrast)
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        // Return white for dark backgrounds, dark for light backgrounds
        return $brightness > 155 ? '#18181b' : '#ffffff';
    }

    /**
     * Get CSS variables for dynamic theming
     */
    public function getCssVariables(): string
    {
        $primaryColor = $this->ensureHashPrefix($this->primaryColor);
        $secondaryColor = $this->ensureHashPrefix($this->secondaryColor);

        $gradient = "linear-gradient(to bottom right, {$primaryColor}, {$secondaryColor})";

        // User message background should use primary color (not image URL for buttons)
        $userMessageBg = match($this->avatarType) {
            'gradient' => $gradient,
            default => $primaryColor, // solid color for both 'solid' and 'image' types
        };

        // Convert primary color to rgba for effects
        $hex = ltrim($primaryColor, '#');
        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $focusShadow = "0 0 0 3px rgba({$r}, {$g}, {$b}, 0.1)";
        $hoverBg = "rgba({$r}, {$g}, {$b}, 0.05)";
        $textColor = $this->getInitialsColor();

        return "--chatflow-primary: {$primaryColor}; --chatflow-gradient: {$gradient}; --chatflow-user-message-bg: {$userMessageBg}; --chatflow-focus-shadow: {$focusShadow}; --chatflow-hover-bg: {$hoverBg}; --chatflow-text-color: {$textColor};";
    }
}
