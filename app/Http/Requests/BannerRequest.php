<?php

namespace App\Http\Requests;

use App\Models\Banner;
use App\Support\UniqueSlug;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        $banner = $this->route('banner');
        $slugSource = $this->input('name') ?: $this->input('title', '');

        $this->merge([
            'slug' => UniqueSlug::make(
                (string) $slugSource,
                'banners',
                $banner instanceof Banner ? $banner->id : null,
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('banners', 'slug')->ignore($this->route('banner')?->id)],
            'type' => ['required', Rule::in(['main', 'promotional', 'other'])],
            'main_image' => [$this->isMethod('post') ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_main_image' => ['sometimes', 'boolean'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'heading' => ['nullable', 'string', 'max:255'],
            'sub_heading' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    $isInternalPath = str_starts_with($value, '/')
                        && ! str_starts_with($value, '//')
                        && ! str_contains($value, '\\');
                    $isHttpUrl = filter_var($value, FILTER_VALIDATE_URL) !== false
                        && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);

                    if (! $isInternalPath && ! $isHttpUrl) {
                        $fail('The button URL must be an internal path or a valid HTTP or HTTPS URL.');
                    }
                },
            ],
            'show_button' => ['required', 'boolean'],
            'open_new_tab' => ['required', 'boolean'],
            'text_alignment' => ['required', Rule::in(['left', 'center', 'right'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'gallery_images' => ['nullable', 'array', 'max:10'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery_alt_texts' => ['nullable', 'array'],
            'gallery_alt_texts.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->boolean('show_button') && blank($this->input('button_text'))) {
                    $validator->errors()->add('button_text', 'Enter button text when the banner button is displayed.');
                }

                if ($this->boolean('show_button') && blank($this->input('button_url'))) {
                    $validator->errors()->add('button_url', 'Enter a destination when the banner button is displayed.');
                }
            },
        ];
    }
}
