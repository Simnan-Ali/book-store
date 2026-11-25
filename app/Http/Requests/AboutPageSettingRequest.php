<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AboutPageSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('about-page.edit');
    }

    public function rules(): array
    {
        return [
            'section_title' => ['required', 'string', 'max:255'],
            'heading' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_link' => [
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
                        $fail('The button link must be an internal path or a valid HTTP or HTTPS URL.');
                    }
                },
            ],
            'open_new_tab' => ['required', 'boolean'],
            'show_button' => ['required', 'boolean'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->boolean('show_button') && blank($this->input('button_text'))) {
                    $validator->errors()->add('button_text', 'Enter button text when the About Us button is displayed.');
                }

                if ($this->boolean('show_button') && blank($this->input('button_link'))) {
                    $validator->errors()->add('button_link', 'Enter a destination when the About Us button is displayed.');
                }
            },
        ];
    }
}
