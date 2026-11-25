<?php

namespace Tests\Feature;

use App\Models\AboutPageSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AboutPageSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_open_or_update_about_page_settings(): void
    {
        $this->get(route('admin.about-page-settings.edit'))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.about-page-settings.edit'))
            ->assertForbidden();

        $this->put(route('admin.about-page-settings.update'), $this->aboutPagePayload())
            ->assertForbidden();

        $this->assertDatabaseCount('about_page_settings', 0);
    }

    public function test_admin_opens_the_single_about_page_form_without_creating_a_record(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.about-page-settings.edit'))
            ->assertOk()
            ->assertSee('About Us Page Master')
            ->assertSee('Section title')
            ->assertSee('Main heading')
            ->assertSee('Description')
            ->assertSee('About Us image')
            ->assertDontSee('Image alt text')
            ->assertDontSee('Show button')
            ->assertDontSee('Display this call to action on the future About Us page.')
            ->assertDontSee('Open link in a new tab')
            ->assertDontSee('Only applies when the button is shown.')
            ->assertDontSee('Single page record')
            ->assertSee('Save changes')
            ->assertSee('Cancel')
            ->assertSee('data-image-preview="#about-image-preview"', false)
            ->assertSee('name="image_alt" type="hidden"', false)
            ->assertSee('name="show_button" value="0"', false)
            ->assertSee('name="open_new_tab" value="0"', false);

        $this->assertDatabaseCount('about_page_settings', 0);
    }

    public function test_about_page_content_updates_in_the_same_singleton_record(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $payload = $this->aboutPagePayload([
            'section_title' => 'Our story',
            'heading' => 'A bookstore built for readers',
            'description' => "We started with a small shelf.\nToday, we share stories with everyone.",
            'image_alt' => 'Readers browsing a bright bookstore',
            'button_text' => 'Read More',
            'button_link' => '/about-us',
            'open_new_tab' => 1,
            'show_button' => 1,
            'image' => UploadedFile::fake()->image('about-us.jpg'),
        ]);

        $this->put(route('admin.about-page-settings.update'), $payload)
            ->assertRedirect(route('admin.about-page-settings.edit'))
            ->assertSessionHas('success');

        $settings = AboutPageSetting::query()->findOrFail(AboutPageSetting::SINGLETON_ID);
        $firstImagePath = $settings->image_path;

        $this->assertDatabaseCount('about_page_settings', 1);
        $this->assertSame('Our story', $settings->section_title);
        $this->assertSame('A bookstore built for readers', $settings->heading);
        $this->assertSame("We started with a small shelf.\nToday, we share stories with everyone.", $settings->description);
        $this->assertSame('Readers browsing a bright bookstore', $settings->image_alt);
        $this->assertSame('Read More', $settings->button_text);
        $this->assertSame('/about-us', $settings->button_link);
        $this->assertSame(true, $settings->open_new_tab);
        $this->assertSame(true, $settings->show_button);
        Storage::disk('public')->assertExists($firstImagePath);
        $this->assertDatabaseHas('activities', [
            'action' => 'created',
            'subject_type' => AboutPageSetting::class,
            'subject_id' => AboutPageSetting::SINGLETON_ID,
        ]);

        $this->get(route('admin.about-page-settings.edit'))
            ->assertOk()
            ->assertDontSee('Image alt text')
            ->assertDontSee('Show button')
            ->assertDontSee('Open link in a new tab')
            ->assertDontSee('Single page record')
            ->assertSee('name="image_alt" type="hidden" value="Readers browsing a bright bookstore"', false)
            ->assertSee('name="show_button" value="1"', false)
            ->assertSee('name="open_new_tab" value="1"', false);

        $this->put(route('admin.about-page-settings.update'), $this->aboutPagePayload([
            'section_title' => 'A new chapter',
            'heading' => 'Still here for every reader',
            'image_alt' => 'Readers browsing a bright bookstore',
            'button_text' => 'Visit us',
            'button_link' => 'https://books.example/about',
            'open_new_tab' => 1,
            'show_button' => 1,
        ]))->assertRedirect(route('admin.about-page-settings.edit'));

        $updatedSettings = $settings->fresh();
        $this->assertSame(AboutPageSetting::SINGLETON_ID, $updatedSettings->id);
        $this->assertSame('A new chapter', $updatedSettings->section_title);
        $this->assertSame('Still here for every reader', $updatedSettings->heading);
        $this->assertSame('https://books.example/about', $updatedSettings->button_link);
        $this->assertSame(true, $updatedSettings->open_new_tab);
        $this->assertSame(true, $updatedSettings->show_button);
        $this->assertSame($firstImagePath, $updatedSettings->image_path);
        $this->assertDatabaseCount('about_page_settings', 1);
        $this->assertDatabaseHas('activities', [
            'action' => 'updated',
            'subject_type' => AboutPageSetting::class,
            'subject_id' => AboutPageSetting::SINGLETON_ID,
        ]);
    }

    public function test_about_page_image_can_be_replaced_and_removed(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $settings = AboutPageSetting::singleton();
        $settings->fill($this->aboutPagePayload([
            'image_path' => 'about-page/original.jpg',
            'image_alt' => 'Original bookstore photo',
        ]));
        $settings->save();
        Storage::disk('public')->put('about-page/original.jpg', 'original image');

        $this->put(route('admin.about-page-settings.update'), $this->aboutPagePayload([
            'image_alt' => 'Replacement bookstore photo',
            'image' => UploadedFile::fake()->image('replacement.webp'),
        ]))->assertRedirect(route('admin.about-page-settings.edit'));

        $replacementPath = $settings->fresh()->image_path;
        $this->assertNotSame('about-page/original.jpg', $replacementPath);
        Storage::disk('public')->assertMissing('about-page/original.jpg');
        Storage::disk('public')->assertExists($replacementPath);

        $this->put(route('admin.about-page-settings.update'), $this->aboutPagePayload([
            'remove_image' => 1,
            'image_alt' => 'Replacement bookstore photo',
        ]))->assertRedirect(route('admin.about-page-settings.edit'))
            ->assertSessionHas('success');

        $this->assertNull($settings->fresh()->image_path);
        $this->assertNull($settings->fresh()->image_alt);
        Storage::disk('public')->assertMissing($replacementPath);
        $this->assertDatabaseCount('about_page_settings', 1);
    }

    public function test_about_page_content_and_button_fields_are_validated(): void
    {
        $this->actingAs($this->admin());

        $this->from(route('admin.about-page-settings.edit'))
            ->put(route('admin.about-page-settings.update'), $this->aboutPagePayload([
                'section_title' => '',
                'heading' => '',
                'button_text' => '',
                'button_link' => '',
                'show_button' => 1,
            ]))
            ->assertRedirect(route('admin.about-page-settings.edit'))
            ->assertSessionHasErrors([
                'section_title' => 'The section title field is required.',
                'heading' => 'The heading field is required.',
                'button_text' => 'Enter button text when the About Us button is displayed.',
                'button_link' => 'Enter a destination when the About Us button is displayed.',
            ]);

        $this->assertDatabaseCount('about_page_settings', 0);
    }

    public function test_about_page_button_rejects_unsafe_urls(): void
    {
        $this->actingAs($this->admin());

        $this->from(route('admin.about-page-settings.edit'))
            ->put(route('admin.about-page-settings.update'), $this->aboutPagePayload([
                'button_text' => 'Read More',
                'button_link' => 'javascript:alert(1)',
                'show_button' => 1,
            ]))
            ->assertRedirect(route('admin.about-page-settings.edit'))
            ->assertSessionHasErrors([
                'button_link' => 'The button link must be an internal path or a valid HTTP or HTTPS URL.',
            ]);

        $this->assertDatabaseCount('about_page_settings', 0);
    }

    public function test_about_page_image_rejects_unsupported_file_types(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->from(route('admin.about-page-settings.edit'))
            ->put(route('admin.about-page-settings.update'), $this->aboutPagePayload([
                'image' => UploadedFile::fake()->create('about.svg', 5, 'image/svg+xml'),
            ]))
            ->assertRedirect(route('admin.about-page-settings.edit'))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('about_page_settings', 0);
        Storage::disk('public')->assertDirectoryEmpty('about-page');
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function aboutPagePayload(array $overrides = []): array
    {
        return array_replace([
            'section_title' => 'Our story',
            'heading' => 'Welcome to Folio Books',
            'description' => 'A home for curious readers.',
            'image_alt' => 'The Folio Books team',
            'button_text' => 'Read More',
            'button_link' => '/about-us',
            'open_new_tab' => 0,
            'show_button' => 0,
        ], $overrides);
    }
}
