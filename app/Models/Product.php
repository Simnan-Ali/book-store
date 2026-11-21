<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title', 'slug', 'isbn', 'sku', 'short_description', 'description', 'category_id',
    'subcategory_id', 'brand_id', 'author', 'language', 'edition', 'published_at',
    'regular_price', 'selling_price', 'stock_quantity', 'low_stock_alert', 'main_image',
    'pages', 'format', 'weight', 'is_featured', 'is_best_seller', 'is_active',
])]
class Product extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
            'regular_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function getStockStatusAttribute(): string
    {
        return $this->stock_quantity > 0 ? 'In stock' : 'Out of stock';
    }

    public function getDiscountPercentageAttribute(): int
    {
        if ((float) $this->regular_price <= 0 || (float) $this->selling_price >= (float) $this->regular_price) {
            return 0;
        }

        return (int) round(((float) $this->regular_price - (float) $this->selling_price) / (float) $this->regular_price * 100);
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity > 0 && $this->stock_quantity <= $this->low_stock_alert;
    }
}
