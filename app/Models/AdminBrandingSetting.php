<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'tagline', 'logo_path', 'favicon_path'])]
class AdminBrandingSetting extends Model
{
    public $incrementing = false;

    protected $attributes = ['name' => 'book-store', 'tagline' => 'BOOKS ADMIN'];

    public static function singleton(): self
    {
        $settings = static::query()->find(1) ?? new static;
        $settings->id = 1;

        return $settings;
    }
}
