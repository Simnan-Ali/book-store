<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FooterSection extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function footerSetting(): BelongsTo
    {
        return $this->belongsTo(FooterSetting::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(FooterLink::class)->orderBy('sort_order')->orderBy('id');
    }
}
