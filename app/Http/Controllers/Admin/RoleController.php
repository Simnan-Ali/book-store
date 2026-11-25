<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->when($filters['q'] ?? null, fn ($query, string $term) => $query->where(function ($query) use ($term): void {
                $query->where('display_name', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
            }))
            ->when(isset($filters['status']), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderBy('display_name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.roles.index', compact('roles', 'filters'));
    }

    public function create(): View
    {
        return view('admin.roles.form', [
            'role' => new Role(['guard_name' => 'web', 'is_active' => true]),
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => [],
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);
        $data['guard_name'] = 'web';

        DB::transaction(function () use ($data, $permissions): void {
            $role = Role::create($data);
            $role->syncPermissions($permissions);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        abort_if($role->is_protected, 403);
        $role->load('permissions');

        return view('admin.roles.form', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->is_protected, 403);

        if (! $request->boolean('is_active') && $role->users()->whereKey($request->user()->getKey())->exists()) {
            return back()->withInput()->with('error', 'You cannot deactivate a role assigned to your own account.');
        }

        $data = $request->validated();
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        DB::transaction(function () use ($role, $data, $permissions): void {
            $role->update($data);
            $role->syncPermissions($permissions);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_protected) {
            return back()->with('error', 'Protected roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Remove this role from every user before deleting it.');
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }

    /**
     * @return Collection<string, \Illuminate\Database\Eloquent\Collection<int, Permission>>
     */
    private function permissionGroups(): Collection
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('module')
            ->orderBy('display_name')
            ->get()
            ->groupBy(fn (Permission $permission): string => $permission->module ?: 'General');
    }
}
