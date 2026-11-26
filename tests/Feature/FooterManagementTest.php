<?php

namespace Tests\Feature;

use App\Models\ContactPageSetting;
use App\Models\FooterSetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FooterManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_are_singleton_and_contact_falls_back_to_contact_master(): void
    {
        $this->actingAs($this->admin());
        $contact = ContactPageSetting::singleton();
        $contact->fill(['page_title' => 'Contact', 'main_heading' => 'Contact', 'contact_email' => 'shared@example.com', 'contact_form_heading' => 'Contact', 'submit_button_text' => 'Send', 'success_message' => 'Sent'])->save();
        $this->get(route('admin.footer.edit'))->assertOk()->assertSee('Footer Management')->assertSee('shared@example.com');
        $this->assertDatabaseCount('footer_settings', 0);
        $this->put(route('admin.footer.update'), ['tagline' => 'Stories', 'show_copyright' => 1])->assertSessionHasNoErrors();
        $this->put(route('admin.footer.update'), ['tagline' => 'More stories', 'show_copyright' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('footer_settings', 1);
        $this->assertSame('shared@example.com', FooterSetting::singleton()->contactValue('contact_email'));
        $this->assertSame('More stories', FooterSetting::singleton()->tagline);
        $this->expectException(QueryException::class);
        DB::table('footer_settings')->insert(['id' => 2]);
    }

    public function test_logo_upload_replacement_removal_and_validation(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $this->put(route('admin.footer.update'), ['show_copyright' => 1, 'logo' => UploadedFile::fake()->image('first.png')])->assertSessionHasNoErrors();
        $first = FooterSetting::singleton()->logo_path;
        Storage::disk('public')->assertExists($first);
        $this->put(route('admin.footer.update'), ['show_copyright' => 1, 'logo' => UploadedFile::fake()->image('second.jpg')])->assertSessionHasNoErrors();
        $second = FooterSetting::singleton()->logo_path;
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
        $this->put(route('admin.footer.update'), ['show_copyright' => 1, 'remove_logo' => 1])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($second);
        $this->assertNull(FooterSetting::singleton()->logo_path);
        $this->put(route('admin.footer.update'), ['show_copyright' => 1, 'contact_email' => 'bad', 'google_maps_url' => 'javascript:alert(1)', 'logo' => UploadedFile::fake()->create('logo.svg', 2, 'image/svg+xml')])->assertSessionHasErrors(['contact_email', 'google_maps_url', 'logo']);
    }

    public function test_sections_links_order_status_and_safe_urls(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.footer.sections.store'), ['title' => 'Support', 'sort_order' => 2, 'status' => 1])->assertSessionHasNoErrors();
        $this->post(route('admin.footer.sections.store'), ['title' => 'Quick links', 'sort_order' => 1, 'status' => 0])->assertSessionHasNoErrors();
        $settings = FooterSetting::singleton();
        $this->assertSame(['Quick links', 'Support'], $settings->sections()->pluck('title')->all());
        $section = $settings->sections()->first();
        $this->put(route('admin.footer.sections.update', $section), ['title' => 'Quick', 'sort_order' => 4, 'status' => 1])->assertSessionHasNoErrors();
        $this->post(route('admin.footer.links.store', $section), ['label' => 'About', 'url' => '/about', 'sort_order' => 2, 'status' => 1, 'open_new_tab' => 0])->assertSessionHasNoErrors();
        $this->post(route('admin.footer.links.store', $section), ['label' => 'Home', 'url' => 'https://example.com', 'sort_order' => 1, 'status' => 0, 'open_new_tab' => 1])->assertSessionHasNoErrors();
        $this->assertSame(['Home', 'About'], $section->links()->pluck('label')->all());
        $this->get(route('admin.footer.edit'))->assertOk()->assertSee('Quick')->assertSee('Home')->assertSee('About');
        $link = $section->links()->first();
        $this->put(route('admin.footer.links.update', [$section, $link]), ['label' => 'Welcome', 'url' => '/welcome', 'sort_order' => 5, 'status' => 1, 'open_new_tab' => 0])->assertSessionHasNoErrors();
        $this->assertTrue($link->fresh()->status);
        $this->post(route('admin.footer.links.store', $section), ['label' => 'Bad', 'url' => 'javascript:alert(1)', 'sort_order' => 0, 'status' => 1, 'open_new_tab' => 0])->assertSessionHasErrors('url');
        $this->post(route('admin.footer.links.store', $section), ['label' => 'Bad', 'url' => ['unexpected'], 'sort_order' => 0, 'status' => 1, 'open_new_tab' => 0])->assertSessionHasErrors('url');
        $other = $settings->sections()->where('id', '!=', $section->id)->firstOrFail();
        $this->delete(route('admin.footer.links.destroy', [$other, $link]))->assertNotFound();
        $this->delete(route('admin.footer.links.destroy', [$section, $link]))->assertSessionHasNoErrors();
        $this->delete(route('admin.footer.sections.destroy', $section))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('footer_links', 0);
    }

    public function test_social_links_and_permissions_are_enforced(): void
    {
        $this->get(route('admin.footer.edit'))->assertRedirect(route('login'));
        $this->actingAs($this->admin());
        $social = [];
        foreach (['facebook', 'instagram', 'linkedin', 'youtube', 'twitter'] as $index => $platform) {
            $social[$platform] = ['url' => $platform === 'facebook' ? 'https://example.com' : null, 'icon' => $platform, 'sort_order' => $index, 'status' => $platform === 'facebook' ? 1 : 0];
        }
        $this->put(route('admin.footer.social.update'), ['social' => $social])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('footer_social_links', ['platform' => 'facebook', 'status' => 1]);
        $social['facebook']['url'] = 'javascript:alert(1)';
        $this->put(route('admin.footer.social.update'), ['social' => $social])->assertSessionHasErrors('social.facebook.url');
        $social['facebook']['url'] = null;
        $this->put(route('admin.footer.social.update'), ['social' => $social])->assertSessionHasErrors('social.facebook.url');
        $role = Role::create(['name' => 'footer-viewer', 'guard_name' => 'web', 'display_name' => 'Footer viewer', 'is_active' => true]);
        $role->givePermissionTo(Permission::findByName('footer.view', 'web'));
        $viewer = User::factory()->create();
        $viewer->assignRole($role);
        $this->actingAs($viewer)->get(route('admin.footer.edit'))->assertOk()->assertDontSee('Save social links');
        $this->put(route('admin.footer.update'), ['show_copyright' => 1])->assertForbidden();
        $this->post(route('admin.footer.sections.store'), ['title' => 'Unauthorized', 'sort_order' => 0, 'status' => 1])->assertForbidden();
        $this->put(route('admin.footer.social.update'), ['social' => $social])->assertForbidden();
    }

    public function test_deleting_a_section_with_links_requires_link_delete_permission(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.footer.sections.store'), ['title' => 'Help', 'sort_order' => 0, 'status' => 1]);
        $section = FooterSetting::singleton()->sections()->firstOrFail();
        $this->post(route('admin.footer.links.store', $section), ['label' => 'Help', 'url' => '/help', 'sort_order' => 0, 'status' => 1, 'open_new_tab' => 0]);
        $role = Role::create(['name' => 'section-editor', 'guard_name' => 'web', 'display_name' => 'Section editor', 'is_active' => true]);
        $role->givePermissionTo(Permission::findByName('footer.sections.delete', 'web'));
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user)->delete(route('admin.footer.sections.destroy', $section))->assertForbidden();
        $this->assertDatabaseCount('footer_links', 1);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }
}
