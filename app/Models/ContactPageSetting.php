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
    'map_location_name',
    'google_maps_embed_url',
    'map_latitude',
    'map_longitude',
    'show_map',
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

    protected $attributes = [
        'show_map' => true,
    ];

    protected function casts(): array
    {
        return [
            'map_latitude' => 'decimal:7',
            'map_longitude' => 'decimal:7',
            'show_map' => 'boolean',
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

    public static function isSafeGoogleMapsEmbedUrl(?string $url): bool
    {
        if ($url === null || trim($url) === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $components = parse_url($url);

        if (! is_array($components)) {
            return false;
        }

        $path = $components['path'] ?? '';
        $isEmbedPath = $path === '/maps/embed' || str_starts_with($path, '/maps/embed/');

        return strtolower($components['scheme'] ?? '') === 'https'
            && strtolower($components['host'] ?? '') === 'www.google.com'
            && $isEmbedPath
            && isset($components['query'])
            && $components['query'] !== ''
            && ! isset($components['user'])
            && ! isset($components['pass'])
            && (! isset($components['port']) || $components['port'] === 443);
    }

    public function safeGoogleMapsEmbedUrl(): ?string
    {
        $url = $this->google_maps_embed_url;

        return self::isSafeGoogleMapsEmbedUrl($url) ? $url : null;
    }
}
