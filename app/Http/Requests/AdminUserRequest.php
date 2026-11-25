<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $targetUser = $this->route('user');
        $ability = $targetUser ? 'users.edit' : 'users.create';

        if (! $user?->can($ability) || ($targetUser instanceof User && $targetUser->is($user))) {
            return false;
        }

        $roles = $this->input('roles', []);

        if (! $user->isSuperAdmin() && is_array($roles) && in_array('super-admin', $roles, true)) {
            return false;
        }

        if (
            $targetUser instanceof User
            && ($targetUser->is_admin || $targetUser->roles()->where('roles.is_protected', true)->exists())
        ) {
            return false;
        }

        if (! $user->isSuperAdmin() && is_array($roles)) {
            $selectedRoles = Role::query()
                ->whereIn('name', $roles)
                ->with('permissions')
                ->get();

            foreach ($selectedRoles->flatMap(fn (Role $role) => $role->permissions)->unique('name') as $permission) {
                if (
                    in_array($permission->module, ['roles', 'permissions', 'users'], true)
                    || ! $user->can($permission->name)
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->getKey() : null;
        $isCreating = $userId === null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$isCreating ? 'required' : 'nullable', 'string', 'min:12', 'max:255', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web')->where('is_active', true),
            ],
        ];
    }
}
