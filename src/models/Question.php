<?php

namespace totalwebcreations\chatflow\models;

use Craft;
use craft\base\Model;

/**
 * Question model
 */
class Question extends Model
{
    public ?int $id = null;
    public ?int $formId = null;
    public string $fieldType = 'text';
    public string $fieldName = '';
    public ?array $validation = null;
    public int $sortOrder = 0;
    public bool $required = true;

    // Site-specific content (loaded from chatflow_questions_sites)
    public string $questionText = '';
    public ?string $placeholder = null;
    public ?array $options = null; // For 'buttons' type
    public ?string $skipText = null;

    // Current site ID (used for loading/saving site-specific content)
    public ?int $siteId = null;

    const FIELD_TYPES = [
        'text' => 'Short Text',
        'email' => 'Email',
        'tel' => 'Phone',
        'textarea' => 'Long Text',
        'buttons' => 'Multiple Choice',
        'date' => 'Date',
    ];

    public function defineRules(): array
    {
        return [
            [['questionText', 'fieldType', 'fieldName'], 'required'],
            [['fieldType'], 'in', 'range' => array_keys(self::FIELD_TYPES)],
            [['fieldName'], 'match', 'pattern' => '/^[a-z][a-z0-9\_]*$/'],
            [['formId', 'sortOrder'], 'integer'],
            [['required'], 'boolean'],
            [['options', 'validation'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'questionText' => 'Question',
            'fieldType' => 'Field Type',
            'fieldName' => 'Field Name',
            'placeholder' => 'Placeholder',
            'options' => 'Options',
            'required' => 'Required',
            'skipText' => 'Skip Button Text',
        ];
    }
}
