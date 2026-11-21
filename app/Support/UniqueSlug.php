<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UniqueSlug
{
    public static function make(string $value, string $table, ?int $ignoreId = null, ?int $scopeId = null): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while (self::exists($table, $slug, $ignoreId, $scopeId)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private static function exists(string $table, string $slug, ?int $ignoreId, ?int $scopeId): bool
    {
        $query = DB::table($table)->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($scopeId !== null) {
            $query->where('category_id', $scopeId);
        }

        return $query->exists();
    }
}
