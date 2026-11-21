<?php

namespace App\Http\Requests;

use App\Models\Menu;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['bail', 'nullable', 'integer', Rule::exists('menus', 'id')->whereNull('parent_id')],
            'name' => ['required', 'string', 'max:255'],
            'url' => [
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
                        $fail('The URL must be an internal path or a valid HTTP or HTTPS URL.');
                    }
                },
            ],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'open_in_new_tab' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $menu = $this->route('menu');
                $parentId = $this->input('parent_id');

                if (! $menu instanceof Menu || blank($parentId) || $validator->errors()->has('parent_id')) {
                    return;
                }

                if ((int) $parentId === $menu->getKey()) {
                    $validator->errors()->add('parent_id', 'A menu cannot be its own parent.');

                    return;
                }

                if ($menu->children()->exists()) {
                    $validator->errors()->add('parent_id', 'A menu with children must remain a top-level menu.');
                }
            },
        ];
    }
}
