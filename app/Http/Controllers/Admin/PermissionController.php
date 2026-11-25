<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'module' => ['nullable', 'string', 'max:100'],
        ]);

        $modules = Permission::query()
            ->where('guard_name', 'web')
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->filter()
            ->values();

        $permissions = Permission::query()
            ->withCount(['roles', 'users'])
            ->when($filters['q'] ?? null, fn ($query, string $term) => $query->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('display_name', 'like', "%{$term}%");
            }))
            ->when($filters['module'] ?? null, fn ($query, string $module) => $query->where('module', $module))
            ->orderBy('module')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.permissions.index', compact('permissions', 'modules', 'filters'));
    }

    public function create(): View
    {
        return view('admin.permissions.form', ['permission' => new Permission(['guard_name' => 'web'])]);
    }

    public function store(PermissionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['guard_name'] = 'web';
        $data['is_system'] = false;

        DB::transaction(fn () => Permission::create($data));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission): View
    {
        abort_if($permission->is_system, 403);

        return view('admin.permissions.form', compact('permission'));
    }

    public function update(PermissionRequest $request, Permission $permission): RedirectResponse
    {
        abort_if($permission->is_system, 403);
        $permission->update($request->validated());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.permissions.index')->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if ($permission->is_system) {
            return back()->with('error', 'System permissions cannot be deleted.');
        }

        if ($permission->roles()->exists() || $permission->users()->exists()) {
            return back()->with('error', 'Remove this permission from every role and user before deleting it.');
        }

        $permission->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted successfully.');
    }
}
