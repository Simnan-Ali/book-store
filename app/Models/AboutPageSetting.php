<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'section_title',
    'heading',
    'description',
    'image_path',
    'image_alt',
    'button_text',
    'button_link',
    'open_new_tab',
    'show_button',
])]
class AboutPageSetting extends Model
{
    public const SINGLETON_ID = 1;

    public $incrementing = false;

    protected $attributes = [
        'open_new_tab' => false,
        'show_button' => false,
    ];

    protected function casts(): array
    {
        return [
            'open_new_tab' => 'boolean',
            'show_button' => 'boolean',
        ];
    }

    public static function singleton(): self
    {
        $settings = static::query()->find(self::SINGLETON_ID);

        if ($settings !== null) {
            return $settings;
        }

        $settings = new static;
        $settings->setAttribute($settings->getKeyName(), self::SINGLETON_ID);

        return $settings;
    }
}
