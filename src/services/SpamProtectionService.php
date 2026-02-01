<?php

namespace totalwebcreations\chatflow\services;

use Craft;
use craft\base\Component;
use totalwebcreations\chatflow\Plugin;

/**
 * Spam Protection Service
 *
 * Provides Tier 1 spam protection for ChatFlow forms:
 * - Honeypot field validation
 * - Time-based validation (min/max submission time)
 * - JavaScript requirement check
 * - Rate limiting per IP address
 */
class SpamProtectionService extends Component
{
    private const RATE_LIMIT_CACHE_PREFIX = 'chatflow_ratelimit_';

    /**
     * Validate a form submission against all spam protection measures
     *
     * @param array $data Form submission data
     * @param string $ipAddress Submitter's IP address
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validateSubmission(array $data, string $ipAddress): array
    {
        $settings = Plugin::getInstance()->getSettings();

        // Skip validation if spam protection is disabled
        if (!$settings->enableSpamProtection) {
            return ['valid' => true, 'error' => null];
        }

        // 1. Honeypot validation
        $honeypotResult = $this->validateHoneypot($data);
        if (!$honeypotResult['valid']) {
            return $honeypotResult;
        }

        // 2. Time-based validation
        $timeResult = $this->validateSubmissionTime($data, $settings);
        if (!$timeResult['valid']) {
            return $timeResult;
        }

        // 3. JavaScript token validation
        $jsResult = $this->validateJavaScriptToken($data);
        if (!$jsResult['valid']) {
            return $jsResult;
        }

        // 4. Rate limiting
        $rateLimitResult = $this->validateRateLimit($ipAddress, $settings);
        if (!$rateLimitResult['valid']) {
            return $rateLimitResult;
        }

        // All checks passed
        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate honeypot field (should be empty)
     *
     * @param array $data Form submission data
     * @return array
     */
    private function validateHoneypot(array $data): array
    {
        // Check if honeypot field exists and is empty
        if (isset($data['_chatflow_website']) && !empty($data['_chatflow_website'])) {
            Craft::info('Spam detected: Honeypot field filled', 'chatflow');
            return [
                'valid' => false,
                'error' => 'Invalid submission detected.'
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate submission time (not too fast, not too slow)
     *
     * @param array $data Form submission data
     * @param object $settings Plugin settings
     * @return array
     */
    private function validateSubmissionTime(array $data, $settings): array
    {
        if (!isset($data['_chatflow_timestamp'])) {
            Craft::info('Spam detected: Missing timestamp', 'chatflow');
            return [
                'valid' => false,
                'error' => 'Invalid submission detected.'
            ];
        }

        $timestamp = (int) $data['_chatflow_timestamp'];
        $currentTime = time();
        $elapsedTime = $currentTime - $timestamp;

        // Check minimum time (too fast = likely bot)
        $minTime = $settings->minSubmissionTime ?? 2;
        if ($elapsedTime < $minTime) {
            Craft::info("Spam detected: Submission too fast ({$elapsedTime}s)", 'chatflow');
            return [
                'valid' => false,
                'error' => 'Please take your time filling out the form.'
            ];
        }

        // Check maximum time (form expired/timeout)
        $maxTime = $settings->maxSubmissionTime ?? 1800; // 30 minutes default
        if ($elapsedTime > $maxTime) {
            Craft::info("Spam detected: Submission expired ({$elapsedTime}s)", 'chatflow');
            return [
                'valid' => false,
                'error' => 'Your session has expired. Please refresh and try again.'
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate JavaScript token (ensures JS is enabled)
     *
     * @param array $data Form submission data
     * @return array
     */
    private function validateJavaScriptToken(array $data): array
    {
        // Since ChatFlow requires JavaScript to work anyway, this is a simple check
        if (!isset($data['_chatflow_token']) || empty($data['_chatflow_token'])) {
            Craft::info('Spam detected: Missing JS token', 'chatflow');
            return [
                'valid' => false,
                'error' => 'JavaScript is required for this form.'
            ];
        }

        // Validate token format (simple check - it should be a hash)
        $token = $data['_chatflow_token'];
        if (strlen($token) < 16 || !ctype_alnum($token)) {
            Craft::info('Spam detected: Invalid JS token format', 'chatflow');
            return [
                'valid' => false,
                'error' => 'Invalid submission detected.'
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate rate limiting (max submissions per IP per timeframe)
     *
     * @param string $ipAddress Submitter's IP
     * @param object $settings Plugin settings
     * @return array
     */
    private function validateRateLimit(string $ipAddress, $settings): array
    {
        $cache = Craft::$app->getCache();
        $cacheKey = self::RATE_LIMIT_CACHE_PREFIX . md5($ipAddress);

        $maxSubmissions = $settings->rateLimitMaxSubmissions ?? 3;
        $timeWindow = $settings->rateLimitTimeWindow ?? 600; // 10 minutes default

        // Get current submission count for this IP
        $submissions = $cache->get($cacheKey);

        if ($submissions === false) {
            // First submission from this IP
            $cache->set($cacheKey, 1, $timeWindow);
            return ['valid' => true, 'error' => null];
        }

        // Check if limit exceeded
        if ($submissions >= $maxSubmissions) {
            Craft::info("Rate limit exceeded for IP: {$ipAddress} ({$submissions} submissions)", 'chatflow');
            return [
                'valid' => false,
                'error' => 'Too many submissions. Please try again later.'
            ];
        }

        // Increment counter
        $cache->set($cacheKey, $submissions + 1, $timeWindow);

        return ['valid' => true, 'error' => null];
    }

    /**
     * Generate a simple JavaScript token for client-side inclusion
     * This is called from the frontend JS
     *
     * @return string
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
