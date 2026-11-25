<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can($this->route('subcategory') ? 'subcategories.edit' : 'subcategories.create');
    }

    public function rules(): array
    {
        $subcategory = $this->route('subcategory');

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
            'slug' => [
                Rule::unique('subcategories', 'slug')
                    ->where('category_id', $this->input('category_id'))
                    ->ignore($subcategory?->id),
            ],
        ];
    }
}
