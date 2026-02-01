<?php

namespace totalwebcreations\chatflow\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install migration
 */
class Install extends Migration
{
    public function safeUp(): bool
    {
        // Create chatflow_forms table
        $this->createTable('{{%chatflow_forms}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'successMessage' => $this->text(),

            // Email notifications
            'notificationEmails' => $this->text(),
            'enableNotifications' => $this->boolean()->defaultValue(true),

            // Slack notifications
            'enableSlack' => $this->boolean()->defaultValue(false),
            'slackWebhookUrl' => $this->text(),

            // Microsoft Teams notifications
            'enableTeams' => $this->boolean()->defaultValue(false),
            'teamsWebhookUrl' => $this->text(),

            // Custom webhook
            'enableWebhook' => $this->boolean()->defaultValue(false),
            'webhookUrl' => $this->text(),

            'settings' => $this->text(), // JSON field for future extensibility
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%chatflow_forms}}', 'handle', true);

        // Create chatflow_questions table (core structure only)
        $this->createTable('{{%chatflow_questions}}', [
            'id' => $this->primaryKey(),
            'formId' => $this->integer()->notNull(),
            'fieldType' => $this->string()->notNull(), // text, email, tel, textarea, buttons, date
            'fieldName' => $this->string()->notNull(),
            'validation' => $this->text(), // JSON for validation rules
            'sortOrder' => $this->integer()->notNull()->defaultValue(0),
            'required' => $this->boolean()->defaultValue(true),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%chatflow_questions}}', 'formId');
        $this->createIndex(null, '{{%chatflow_questions}}', 'sortOrder');

        $this->addForeignKey(
            null,
            '{{%chatflow_questions}}',
            'formId',
            '{{%chatflow_forms}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Create chatflow_questions_sites table for multi-site content
        $this->createTable('{{%chatflow_questions_sites}}', [
            'id' => $this->primaryKey(),
            'questionId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'questionText' => $this->text()->notNull(),
            'placeholder' => $this->string(),
            'options' => $this->text(), // JSON array for button options
            'skipText' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%chatflow_questions_sites}}', 'questionId');
        $this->createIndex(null, '{{%chatflow_questions_sites}}', 'siteId');
        $this->createIndex(null, '{{%chatflow_questions_sites}}', ['questionId', 'siteId'], true);

        $this->addForeignKey(
            null,
            '{{%chatflow_questions_sites}}',
            'questionId',
            '{{%chatflow_questions}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%chatflow_questions_sites}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Create chatflow_submissions table
        $this->createTable('{{%chatflow_submissions}}', [
            'id' => $this->primaryKey(),
            'formId' => $this->integer()->notNull(),
            'data' => $this->text()->notNull(), // JSON with all answers
            'userAgent' => $this->string(),
            'ipAddress' => $this->string(45), // IPv6 support
            'dateCreated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%chatflow_submissions}}', 'formId');
        $this->createIndex(null, '{{%chatflow_submissions}}', 'dateCreated');

        $this->addForeignKey(
            null,
            '{{%chatflow_submissions}}',
            'formId',
            '{{%chatflow_forms}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%chatflow_submissions}}');
        $this->dropTableIfExists('{{%chatflow_questions_sites}}');
        $this->dropTableIfExists('{{%chatflow_questions}}');
        $this->dropTableIfExists('{{%chatflow_forms}}');

        return true;
    }
}
