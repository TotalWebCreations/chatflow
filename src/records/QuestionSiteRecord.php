<?php

namespace totalwebcreations\chatflow\records;

use craft\db\ActiveRecord;

/**
 * Question Site record
 *
 * @property int $id
 * @property int $questionId
 * @property int $siteId
 * @property string $questionText
 * @property string|null $placeholder
 * @property string|null $options
 * @property string|null $skipText
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string $uid
 */
class QuestionSiteRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%chatflow_questions_sites}}';
    }

    public function getQuestion()
    {
        return $this->hasOne(QuestionRecord::class, ['id' => 'questionId']);
    }
}
