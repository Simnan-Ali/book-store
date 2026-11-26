<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FooterSocialLink extends Model
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
}
