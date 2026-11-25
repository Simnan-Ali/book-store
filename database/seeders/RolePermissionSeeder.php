<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('admin-permissions', []) as $module => $definitions) {
            foreach ($definitions as $definition) {
                Permission::updateOrCreate(
                    ['name' => $definition['name'], 'guard_name' => 'web'],
                    [
                        'display_name' => $definition['display_name'],
                        'module' => $module,
                        'description' => $definition['description'],
                        'is_system' => true,
                    ],
                );
            }
        }

        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            [
                'display_name' => 'Super Admin',
                'description' => 'Full access to every admin module and authorization setting.',
                'is_active' => true,
                'is_protected' => true,
            ],
        );
        $superAdmin->forceFill([
            'display_name' => 'Super Admin',
            'is_protected' => true,
        ])->save();
        $superAdmin->syncPermissions(Permission::query()->where('guard_name', 'web')->get());

        $defaultRoles = [
            'admin' => [
                'display_name' => 'Admin',
                'description' => 'Manages bookstore content without role or permission administration.',
                'permissions' => [
                    'dashboard.view',
                    'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
                    'subcategories.view', 'subcategories.create', 'subcategories.edit', 'subcategories.delete',
                    'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
                    'books.view', 'books.create', 'books.edit', 'books.delete',
                    'inventory.view', 'inventory.update',
                    'banners.view', 'banners.create', 'banners.edit', 'banners.delete',
                    'media.view',
                    'contact-page.view', 'contact-page.edit',
                    'about-page.view', 'about-page.edit',
                    'menu-master.view', 'menu-master.create', 'menu-master.edit', 'menu-master.delete',
                    'profile.view', 'profile.edit',
                ],
            ],
            'vendor' => [
                'display_name' => 'Vendor',
                'description' => 'Manages only books owned by this vendor.',
                'permissions' => [
                    'dashboard.view',
                    'categories.view', 'subcategories.view', 'brands.view',
                    'books.view', 'books.create', 'books.edit',
                    'inventory.view', 'inventory.update',
                    'profile.view', 'profile.edit',
                ],
            ],
            'inventory-manager' => [
                'display_name' => 'Inventory Manager',
                'description' => 'Reviews the catalogue and manages stock levels.',
                'permissions' => [
                    'dashboard.view',
                    'books.view',
                    'inventory.view', 'inventory.update',
                    'profile.view', 'profile.edit',
                ],
            ],
        ];

        foreach ($defaultRoles as $name => $definition) {
            $role = Role::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [
                    'display_name' => $definition['display_name'],
                    'description' => $definition['description'],
                    'is_active' => true,
                    'is_protected' => false,
                ],
            );

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions($definition['permissions']);
            }
        }

        $legacyAdmins = User::query()->where('is_admin', true)->with('roles')->get();

        foreach ($legacyAdmins as $user) {
            if ($user->roles->isEmpty()) {
                $user->assignRole($superAdmin);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
