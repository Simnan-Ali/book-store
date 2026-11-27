<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permission_groups_follow_sidebar_order_on_create_and_edit(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed(RolePermissionSeeder::class);
        Permission::create([
            'name' => 'reports.export',
            'guard_name' => 'web',
            'display_name' => 'Export reports',
            'module' => 'reports',
        ]);

        $expectedOrder = [
            '<legend>Overview</legend>', '<legend>Categories</legend>', '<legend>Subcategories</legend>',
            '<legend>Publishers</legend>', '<legend>Books</legend>', '<legend>Inventory</legend>',
            '<legend>Media library</legend>', '<legend>Banner Management</legend>', '<legend>Menu Master</legend>',
            '<legend>Contact Us Page Master</legend>', '<legend>About Us Page Master</legend>', '<legend>Footer Management</legend>',
            '<legend>Roles</legend>', '<legend>Permissions</legend>', '<legend>Admin users</legend>',
            '<legend>My profile</legend>', '<legend>Reports</legend>',
        ];

        $role = Role::query()->where('name', 'admin')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.roles.create'))->assertOk()->assertSeeInOrder($expectedOrder, false);
        $this->get(route('admin.roles.edit', $role))->assertOk()->assertSeeInOrder($expectedOrder, false)
            ->assertSee('value="categories.view"', false)
            ->assertViewHas('selectedPermissions', fn (array $permissions): bool => in_array('categories.view', $permissions, true));
    }

    public function test_permission_key_format_explains_how_to_match_its_module(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed(RolePermissionSeeder::class);
        $this->actingAs($admin);

        $data = ['display_name' => 'View module', 'name' => 'View-module', 'module' => 'test-module'];
        $this->from(route('admin.permissions.create'))->post(route('admin.permissions.store'), $data)
            ->assertSessionHasErrors(['name' => 'Use lowercase letters and numbers. Start the key with the module followed by a dot, for example: test-module.view.']);

        $data['name'] = 'other.view';
        $this->post(route('admin.permissions.store'), $data)
            ->assertSessionHasErrors(['name' => 'The permission key must start with test-module. For example: test-module.view']);

        $data['name'] = 'test-module.view';
        $this->post(route('admin.permissions.store'), $data)->assertRedirect(route('admin.permissions.index'));
        $this->assertDatabaseHas('permissions', ['name' => 'test-module.view', 'module' => 'test-module']);
    }

    public function test_permissions_gate_admin_routes_and_inactive_roles_revoke_access(): void
    {
        $role = $this->createRoleWithPermissions('catalog-reader', ['categories.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertDontSee('New category');

        $this->get(route('admin.brands.index'))->assertForbidden();

        $role->update(['is_active' => false]);

        $this->get(route('admin.categories.index'))->assertForbidden();
        $this->post(route('admin.logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_view_only_roles_cannot_submit_single_page_settings_forms(): void
    {
        $role = $this->createRoleWithPermissions('content-viewer', [
            'about-page.view',
            'contact-page.view',
            'profile.view',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        $this->get(route('admin.contact-page-settings.edit'))
            ->assertOk()
            ->assertSee('<fieldset disabled>', false)
            ->assertDontSee('Save changes');

        $this->get(route('admin.about-page-settings.edit'))
            ->assertOk()
            ->assertSee('<fieldset disabled>', false)
            ->assertDontSee('Save changes');

        $this->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('<fieldset disabled>', false)
            ->assertDontSee('Save settings');
    }

    public function test_legacy_admin_is_preserved_as_super_admin_when_permissions_are_seeded(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin->refresh();

        $this->assertTrue($admin->hasActiveRole('super-admin'));
        $this->assertSame(1, Role::query()->where('name', 'super-admin')->count());
        $this->assertTrue($admin->can('users.view'));

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Admin users');
    }

    public function test_admin_user_creation_assigns_roles_and_protects_self_role_changes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed(RolePermissionSeeder::class);
        $this->actingAs($admin);

        $role = Role::query()->where('name', 'admin')->firstOrFail();
        $this->post(route('admin.users.store'), [
            'name' => 'Store Manager',
            'email' => 'manager@example.test',
            'password' => 'long-secure-password',
            'password_confirmation' => 'long-secure-password',
            'roles' => [$role->name],
        ])->assertRedirect(route('admin.users.index'));

        $createdUser = User::query()->where('email', 'manager@example.test')->firstOrFail();
        $this->assertTrue($createdUser->hasActiveRole('admin'));

        $this->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'roles' => ['admin'],
        ])->assertForbidden();

        $provisionerRole = Role::create([
            'name' => 'user-provisioner',
            'guard_name' => 'web',
            'display_name' => 'User provisioner',
            'is_active' => true,
            'is_protected' => false,
        ]);
        $provisionerRole->givePermissionTo(Permission::query()->where('name', 'users.create')->firstOrFail());
        $provisioner = User::factory()->create();
        $provisioner->assignRole($provisionerRole);

        $this->actingAs($provisioner)
            ->post(route('admin.users.store'), [
                'name' => 'Overprivileged user',
                'email' => 'overprivileged@example.test',
                'password' => 'long-secure-password',
                'password_confirmation' => 'long-secure-password',
                'roles' => ['admin'],
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_manage_roles_and_custom_permissions_without_mutating_system_permissions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->seed(RolePermissionSeeder::class);
        $this->actingAs($admin);

        $this->get(route('admin.roles.create'))
            ->assertOk()
            ->assertSee('Select all')
            ->assertSee('books.view');
        $this->get(route('admin.roles.index'))->assertOk();
        $this->get(route('admin.permissions.create'))->assertOk();
        $this->get(route('admin.permissions.index'))->assertOk();
        $this->get(route('admin.users.create'))->assertOk();

        $this->post(route('admin.roles.store'), [
            'name' => 'content-reader',
            'display_name' => 'Content reader',
            'description' => 'Can review books.',
            'is_active' => 1,
            'permissions' => ['books.view'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::query()->where('name', 'content-reader')->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('books.view'));

        $this->post(route('admin.permissions.store'), [
            'name' => 'reports.export',
            'display_name' => 'Export reports',
            'module' => 'reports',
            'description' => 'Download reports.',
        ])->assertRedirect(route('admin.permissions.index'));

        $permission = Permission::query()->where('name', 'reports.export')->firstOrFail();
        $this->put(route('admin.permissions.update', $permission), [
            'name' => 'reports.download',
            'display_name' => 'Download reports',
            'module' => 'reports',
            'description' => 'Download formatted reports.',
        ])->assertRedirect(route('admin.permissions.index'));
        $this->assertDatabaseHas('permissions', ['name' => 'reports.download']);

        $customPermission = Permission::query()->where('name', 'reports.download')->firstOrFail();
        $this->delete(route('admin.permissions.destroy', $customPermission))
            ->assertRedirect(route('admin.permissions.index'));
        $this->assertDatabaseMissing('permissions', ['id' => $customPermission->id]);

        $systemPermission = Permission::query()->where('name', 'books.view')->firstOrFail();
        $this->get(route('admin.permissions.edit', $systemPermission))->assertForbidden();
        $this->put(route('admin.permissions.update', $systemPermission), [
            'name' => 'books.read',
            'display_name' => 'Read books',
            'module' => 'books',
        ])->assertForbidden();
    }

    public function test_vendors_only_see_and_update_inventory_for_their_own_books(): void
    {
        $role = $this->createRoleWithPermissions('vendor', [
            'books.view',
            'books.create',
            'inventory.view',
            'inventory.update',
        ]);
        $vendor = User::factory()->create();
        $otherVendor = User::factory()->create();
        $otherVendor->assignRole($role);
        $vendor->assignRole($role);

        $category = Category::create(['name' => 'Fiction', 'slug' => 'fiction', 'is_active' => true]);
        $brand = Brand::create(['name' => 'North Press', 'slug' => 'north-press', 'is_active' => true]);
        $ownedBook = $this->createProduct($vendor, $category, $brand, 'Owned book', 'OWNED-1');
        $otherBook = $this->createProduct($otherVendor, $category, $brand, 'Private book', 'PRIVATE-1');

        $this->actingAs($vendor)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertDontSee('name="vendor_id"', false);

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Owned book')
            ->assertDontSee('Private book');

        $this->get(route('admin.products.show', $otherBook))->assertForbidden();

        $this->post(route('admin.products.store'), [
            'title' => 'Vendor-created title',
            'isbn' => '9781111111111',
            'sku' => 'VENDOR-NEW-1',
            'short_description' => 'A vendor-owned title.',
            'description' => 'Details for the new book.',
            'category_id' => $category->getKey(),
            'brand_id' => $brand->getKey(),
            'author' => 'Vendor Author',
            'language' => 'English',
            'edition' => 'First',
            'published_at' => '2025-01-15',
            'regular_price' => 25,
            'selling_price' => 20,
            'stock_quantity' => 3,
            'low_stock_alert' => 5,
            'format' => 'Paperback',
            'is_featured' => 0,
            'is_best_seller' => 0,
            'is_active' => 1,
            'vendor_id' => $otherVendor->getKey(),
        ])->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', [
            'sku' => 'VENDOR-NEW-1',
            'vendor_id' => $vendor->getKey(),
        ]);

        $this->patch(route('admin.inventory.stock.update', $ownedBook), [
            'stock_quantity' => 17,
            'low_stock_alert' => 4,
        ])->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $ownedBook->id, 'stock_quantity' => 17]);

        $this->patch(route('admin.inventory.stock.update', $otherBook), [
            'stock_quantity' => 2,
            'low_stock_alert' => 1,
        ])->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $otherBook->id, 'stock_quantity' => 3]);
    }

    private function createRoleWithPermissions(string $name, array $permissionNames): Role
    {
        $role = Role::create([
            'name' => $name,
            'guard_name' => 'web',
            'display_name' => str($name)->headline(),
            'is_active' => true,
            'is_protected' => false,
        ]);

        $permissions = collect($permissionNames)->map(function (string $permissionName): Permission {
            [$module] = explode('.', $permissionName, 2);

            return Permission::create([
                'name' => $permissionName,
                'guard_name' => 'web',
                'display_name' => str($permissionName)->replace('.', ' ')->headline(),
                'module' => $module,
                'is_system' => false,
            ]);
        });

        $role->syncPermissions($permissions);

        return $role;
    }

    private function createProduct(User $vendor, Category $category, Brand $brand, string $title, string $sku): Product
    {
        return Product::create([
            'title' => $title,
            'slug' => str($title)->slug(),
            'isbn' => null,
            'sku' => $sku,
            'category_id' => $category->getKey(),
            'brand_id' => $brand->getKey(),
            'author' => 'Test Author',
            'regular_price' => 20,
            'selling_price' => 18,
            'stock_quantity' => 3,
            'low_stock_alert' => 5,
            'format' => 'Paperback',
            'is_featured' => false,
            'is_best_seller' => false,
            'is_active' => true,
            'vendor_id' => $vendor->getKey(),
        ]);
    }
}
