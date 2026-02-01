<?php

namespace totalwebcreations\chatflow\controllers;

use Craft;
use craft\web\Controller;
use yii\web\Response;

/**
 * Webhook Test Controller
 * Simple endpoint to test webhook functionality
 */
class WebhookTestController extends Controller
{
    protected array|bool|int $allowAnonymous = true;
    public $enableCsrfValidation = false;

    /**
     * Test webhook endpoint
     * URL: /actions/chatflow/webhook-test/receive
     */
    public function actionReceive(): Response
    {
        $this->response->format = Response::FORMAT_JSON;

        // Get the raw POST data
        $rawData = Craft::$app->request->getRawBody();

        // Get headers
        $headers = Craft::$app->request->getHeaders()->toArray();

        // Prepare log entry
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => Craft::$app->request->getMethod(),
            'headers' => $headers,
            'raw_data' => $rawData,
            'parsed_data' => json_decode($rawData, true),
            'get_params' => Craft::$app->request->getQueryParams(),
            'post_params' => Craft::$app->request->getBodyParams(),
        ];

        // Log to Craft's log
        Craft::info("Webhook Test Received:\n" . json_encode($logEntry, JSON_PRETTY_PRINT), 'chatflow-webhook-test');

        // Also write to separate log file for easy viewing
        $logFile = Craft::getAlias('@storage/logs/chatflow-webhook-test.log');
        file_put_contents(
            $logFile,
            "\n" . str_repeat('=', 80) . "\n" .
            "WEBHOOK RECEIVED\n" .
            str_repeat('=', 80) . "\n" .
            json_encode($logEntry, JSON_PRETTY_PRINT) . "\n",
            FILE_APPEND
        );

        return $this->asJson([
            'success' => true,
            'message' => 'Webhook received and logged',
            'timestamp' => date('c'),
            'log_file' => $logFile
        ]);
    }

    /**
     * View webhook logs
     * URL: /actions/chatflow/webhook-test/logs
     */
    public function actionLogs(): Response
    {
        $this->requirePermission('accessPlugin-chatflow');

        $logFile = Craft::getAlias('@storage/logs/chatflow-webhook-test.log');

        if (!file_exists($logFile)) {
            return $this->asJson([
                'success' => false,
                'message' => 'No webhook test logs found yet'
            ]);
        }

        $content = file_get_contents($logFile);

        $this->response->format = Response::FORMAT_RAW;
        $this->response->headers->set('Content-Type', 'text/plain; charset=utf-8');

        return $this->response->data = $content;
    }

    /**
     * Clear webhook logs
     * URL: /actions/chatflow/webhook-test/clear
     */
    public function actionClear(): Response
    {
        $this->requirePermission('accessPlugin-chatflow');
        $this->requirePostRequest();

        $logFile = Craft::getAlias('@storage/logs/chatflow-webhook-test.log');

        if (file_exists($logFile)) {
            unlink($logFile);
        }

        return $this->asJson([
            'success' => true,
            'message' => 'Webhook test logs cleared'
        ]);
    }
}
