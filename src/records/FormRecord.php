<?php

namespace totalwebcreations\chatflow\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Form record
 *
 * @property int $id
 * @property string $name
 * @property string $handle
 * @property string|null $successMessage
 * @property string|null $notificationEmails
 * @property bool $enableNotifications
 * @property bool $enableSlack
 * @property string|null $slackWebhookUrl
 * @property bool $enableTeams
 * @property string|null $teamsWebhookUrl
 * @property bool $enableWebhook
 * @property string|null $webhookUrl
 * @property string|null $settings
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string $uid
 */
class FormRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%chatflow_forms}}';
    }

    public function getQuestions(): ActiveQueryInterface
    {
        return $this->hasMany(QuestionRecord::class, ['formId' => 'id'])->orderBy(['sortOrder' => SORT_ASC]);
    }

    public function getSubmissions(): ActiveQueryInterface
    {
        return $this->hasMany(SubmissionRecord::class, ['formId' => 'id']);
    }
}
