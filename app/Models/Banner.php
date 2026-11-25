<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title', 'name', 'slug', 'type', 'main_image', 'image_alt', 'heading', 'sub_heading',
    'description', 'button_text', 'button_url', 'show_button', 'open_new_tab', 'text_alignment',
    'status', 'sort_order', 'start_date', 'end_date',
])]
class Banner extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'show_button' => 'boolean',
            'open_new_tab' => 'boolean',
            'sort_order' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(BannerImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
