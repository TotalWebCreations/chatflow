<?php

namespace totalwebcreations\chatflow\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Submission record
 *
 * @property int $id
 * @property int $formId
 * @property string $data
 * @property string|null $userAgent
 * @property string|null $ipAddress
 * @property \DateTime $dateCreated
 * @property string $uid
 */
class SubmissionRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%chatflow_submissions}}';
    }

    public function getForm(): ActiveQueryInterface
    {
        return $this->hasOne(FormRecord::class, ['id' => 'formId']);
    }
}
