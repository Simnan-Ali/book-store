<?php

namespace App\Http\Requests;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->route('permission');
        $ability = $permission ? 'permissions.edit' : 'permissions.create';

        return (bool) $this->user()?->can($ability)
            && ! ($permission instanceof Permission && $permission->is_system);
    }

    public function rules(): array
    {
        $permission = $this->route('permission');
        $permissionId = $permission instanceof Permission ? $permission->getKey() : null;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                'regex:/\A[a-z0-9]+(?:[._-][a-z0-9]+)*\z/',
                Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($permissionId),
            ],
            'display_name' => ['required', 'string', 'max:150'],
            'module' => ['required', 'string', 'max:100', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $name = $this->input('name');
                $module = $this->input('module');

                if (
                    is_string($name)
                    && is_string($module)
                    && ! str_starts_with($name, $module.'.')
                ) {
                    $validator->errors()->add('name', 'The permission key must start with its module name and a period.');
                }
            },
        ];
    }
}
