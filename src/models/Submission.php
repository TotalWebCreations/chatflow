<?php

namespace totalwebcreations\chatflow\models;

use craft\base\Model;

/**
 * Submission model
 */
class Submission extends Model
{
    public ?int $id = null;
    public ?int $formId = null;
    public ?array $data = null;
    public ?string $userAgent = null;
    public ?string $ipAddress = null;
    public ?\DateTime $dateCreated = null;

    public function defineRules(): array
    {
        return [
            [['formId', 'data'], 'required'],
            [['formId'], 'integer'],
            [['data'], 'safe'],
        ];
    }
}
