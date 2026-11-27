<?php

namespace Tests\Feature;

use App\Models\AdminBrandingSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_branding_and_it_appears_on_admin_and_login_pages(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.branding.update'), [
            'name' => 'My Bookshop',
            'tagline' => 'Library Manager',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'favicon' => UploadedFile::fake()->image('favicon.png'),
        ])->assertRedirect(route('admin.branding.edit'));

        $settings = AdminBrandingSetting::singleton();
        Storage::disk('public')->assertExists($settings->logo_path);
        Storage::disk('public')->assertExists($settings->favicon_path);
        $this->get(route('admin.branding.edit'))->assertOk()->assertSee('My Bookshop')->assertSee('Library Manager')->assertSee($settings->logo_path)->assertSee($settings->favicon_path);
        $this->post(route('admin.logout'));
        $this->get(route('login'))->assertOk()->assertSee('My Bookshop')->assertSee('Library Manager')->assertSee($settings->logo_path)->assertSee($settings->favicon_path);

        $this->actingAs($admin)->put(route('admin.branding.update'), [
            'name' => 'My Bookshop', 'tagline' => '', 'remove_logo' => 1, 'remove_favicon' => 1,
        ])->assertRedirect();
        Storage::disk('public')->assertMissing($settings->logo_path);
        Storage::disk('public')->assertMissing($settings->favicon_path);
        $this->assertNull(AdminBrandingSetting::singleton()->logo_path);
        $this->get(route('admin.branding.edit'))->assertDontSee('Library Manager');
        $this->assertNull(AdminBrandingSetting::singleton()->tagline);
    }

    public function test_branding_is_protected_and_uploads_are_validated(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $vendor = User::factory()->create();
        $vendor->assignRole('vendor');
        $this->actingAs($vendor)->get(route('admin.branding.edit'))->assertForbidden();
        $this->put(route('admin.branding.update'), ['name' => 'Changed'])->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('admin.branding.edit'))->assertOk();
        $this->put(route('admin.branding.update'), [
            'name' => '', 'logo' => UploadedFile::fake()->create('logo.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors(['name', 'logo']);
        $this->assertDatabaseCount('admin_branding_settings', 0);
    }
}
