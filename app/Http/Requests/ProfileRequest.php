<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('profile.edit');
    }

    public function rules(): array
    {
        $currentPasswordRules = $this->filled('password')
            ? ['required', 'current_password']
            : ['exclude'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'current_password' => $currentPasswordRules,
            'password' => ['nullable', 'confirmed', 'min:12', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
    }
}
