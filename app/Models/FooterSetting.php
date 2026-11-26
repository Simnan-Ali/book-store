<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FooterSetting extends Model
{
    public $incrementing = false;

    protected $guarded = [];

    protected $attributes = ['show_copyright' => true];

    protected function casts(): array
    {
        return ['show_copyright' => 'boolean', 'copyright_year' => 'integer'];
    }

    public static function singleton(): self
    {
        return static::query()->find(1) ?? (new static)->forceFill(['id' => 1]);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(FooterSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(FooterSocialLink::class)->orderBy('sort_order')->orderBy('id');
    }

    public function contactValue(string $field): ?string
    {
        return $this->getAttribute($field) ?? ContactPageSetting::singleton()->getAttribute($field);
    }
}
