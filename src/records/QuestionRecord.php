<?php

namespace totalwebcreations\chatflow\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Question record
 *
 * @property int $id
 * @property int $formId
 * @property string $fieldType
 * @property string $fieldName
 * @property string|null $validation
 * @property int $sortOrder
 * @property bool $required
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string $uid
 */
class QuestionRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%chatflow_questions}}';
    }

    public function getForm(): ActiveQueryInterface
    {
        return $this->hasOne(FormRecord::class, ['id' => 'formId']);
    }

    public function getSiteContent(): ActiveQueryInterface
    {
        return $this->hasMany(QuestionSiteRecord::class, ['questionId' => 'id']);
    }
}
