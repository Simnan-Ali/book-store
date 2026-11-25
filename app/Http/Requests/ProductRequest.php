<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can($this->route('product') ? 'books.edit' : 'books.create');
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'title' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:32', Rule::unique('products', 'isbn')->ignore($product?->id)],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($product?->id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'subcategory_id' => [
                'nullable',
                'integer',
                Rule::exists('subcategories', 'id')
                    ->where('category_id', $this->input('category_id'))
                    ->whereNull('deleted_at'),
            ],
            'brand_id' => ['required', 'integer', Rule::exists('brands', 'id')->whereNull('deleted_at')],
            'vendor_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($product): void {
                    if ($product instanceof Product && (string) $product->vendor_id === (string) $value) {
                        return;
                    }

                    $isActiveVendor = User::query()
                        ->whereKey($value)
                        ->whereHas('roles', fn ($query) => $query
                            ->where('roles.name', 'vendor')
                            ->where('roles.guard_name', 'web')
                            ->where('roles.is_active', true))
                        ->exists();

                    if (! $isActiveVendor) {
                        $fail('The selected vendor must be an active user with the Vendor role.');
                    }
                },
            ],
            'author' => ['required', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:100'],
            'edition' => ['nullable', 'string', 'max:100'],
            'published_at' => ['nullable', 'date'],
            'regular_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'selling_price' => ['required', 'numeric', 'min:0', 'lte:regular_price'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'low_stock_alert' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => [
                'integer',
                Rule::exists('product_images', 'id')->where('product_id', $product?->id),
            ],
            'pages' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'format' => ['required', Rule::in(['Paperback', 'Hardcover', 'E-book'])],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'is_featured' => ['required', 'boolean'],
            'is_best_seller' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
