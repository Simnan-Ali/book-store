<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'page_title',
    'page_subtitle',
    'main_heading',
    'description',
    'banner_image',
    'banner_alt_text',
    'contact_email',
    'contact_phone',
    'whatsapp_number',
    'office_address',
    'google_maps_url',
    'business_hours',
    'working_days',
    'additional_information',
    'facebook_url',
    'instagram_url',
    'linkedin_url',
    'youtube_url',
    'contact_form_heading',
    'contact_form_description',
    'submit_button_text',
    'success_message',
    'meta_title',
    'meta_description',
    'meta_keywords',
])]
class ContactPageSetting extends Model
{
    public const SINGLETON_ID = 1;

    public $incrementing = false;

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
