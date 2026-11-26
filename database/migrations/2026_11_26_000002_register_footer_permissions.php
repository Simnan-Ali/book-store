<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (config('admin-permissions.footer') as $definition) {
            $permission = Permission::updateOrCreate(
                ['name' => $definition['name'], 'guard_name' => 'web'],
                ['display_name' => $definition['display_name'], 'module' => 'footer', 'description' => $definition['description'], 'is_system' => true],
            );
            $role = Role::query()->where('name', 'super-admin')->where('guard_name', 'web')->first();
            $role?->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', array_column(config('admin-permissions.footer'), 'name'))->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
