<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');
        $permission = $role ? 'roles.edit' : 'roles.create';
        $user = $this->user();

        if (! $user?->can($permission) || ($role instanceof Role && $role->is_protected)) {
            return false;
        }

        if ($this->input('name') === 'super-admin' && ! $user->isSuperAdmin()) {
            return false;
        }

        $permissions = $this->input('permissions', []);

        if (! $user->isSuperAdmin() && is_array($permissions)) {
            foreach ($permissions as $permissionName) {
                if (! is_string($permissionName)) {
                    continue;
                }

                if (
                    str_starts_with($permissionName, 'roles.')
                    || str_starts_with($permissionName, 'permissions.')
                    || str_starts_with($permissionName, 'users.')
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    public function rules(): array
    {
        $role = $this->route('role');
        $roleId = $role instanceof Role ? $role->getKey() : null;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/\A[a-z0-9]+(?:[._-][a-z0-9]+)*\z/',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($roleId),
            ],
            'display_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ];
    }
}
