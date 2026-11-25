<?php

namespace Tests\Feature;

use App\Models\ContactPageSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrators_can_save_and_update_the_google_maps_location(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get(route('admin.contact-page-settings.edit'))
            ->assertOk()
            ->assertSee('Google Maps Location')
            ->assertSee('name="google_maps_url"', false)
            ->assertSee('name="show_map" value="1"', false);

        $this->assertDatabaseCount('contact_page_settings', 0);

        $this->put(route('admin.contact-page-settings.update'), $this->contactPagePayload())
            ->assertRedirect(route('admin.contact-page-settings.edit'))
            ->assertSessionHas('success');

        $settings = ContactPageSetting::query()->findOrFail(ContactPageSetting::SINGLETON_ID);
        $this->assertSame('book-store Downtown', $settings->map_location_name);
        $this->assertSame('45.5231000', $settings->map_latitude);
        $this->assertSame('-122.6765000', $settings->map_longitude);
        $this->assertTrue($settings->show_map);
        $this->assertSame('https://maps.example/book-store', $settings->google_maps_url);

        $updatedPayload = $this->contactPagePayload([
            'map_location_name' => 'book-store Pearl District',
            'google_maps_embed_url' => 'https://www.google.com/maps/embed?pb=updated-location',
            'map_latitude' => '45.5290',
            'map_longitude' => '-122.6810',
            'show_map' => 0,
        ]);

        $this->put(route('admin.contact-page-settings.update'), $updatedPayload)
            ->assertRedirect(route('admin.contact-page-settings.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('contact_page_settings', 1);
        $this->assertDatabaseHas('contact_page_settings', [
            'id' => ContactPageSetting::SINGLETON_ID,
            'map_location_name' => 'book-store Pearl District',
            'google_maps_embed_url' => 'https://www.google.com/maps/embed?pb=updated-location',
            'show_map' => false,
            'contact_email' => 'hello@book-store.example',
            'google_maps_url' => 'https://maps.example/book-store',
        ]);
        $this->assertFalse($settings->fresh()->show_map);
    }

    public function test_map_preview_renders_only_safe_embed_urls_and_shows_an_empty_state(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get(route('admin.contact-page-settings.edit'))
            ->assertOk()
            ->assertSee('No map preview yet')
            ->assertSee('data-map-empty', false);

        $settings = ContactPageSetting::singleton();
        $settings->fill($this->contactPagePayload());
        $settings->save();

        $this->get(route('admin.contact-page-settings.edit'))
            ->assertOk()
            ->assertSee('src="https://www.google.com/maps/embed?pb=sample-location"', false)
            ->assertSee('title="book-store Downtown"', false)
            ->assertSee('sandbox="allow-scripts allow-same-origin allow-popups"', false)
            ->assertSee('referrerpolicy="strict-origin-when-cross-origin"', false);

        $settings->google_maps_embed_url = 'https://evil.example/maps/embed?pb=unsafe';
        $settings->save();

        $this->get(route('admin.contact-page-settings.edit'))
            ->assertOk()
            ->assertSee('No map preview yet')
            ->assertDontSee('src="https://evil.example/maps/embed?pb=unsafe"', false);
    }

    public function test_unsafe_map_urls_and_out_of_range_coordinates_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $response = $this->from(route('admin.contact-page-settings.edit'))
            ->put(route('admin.contact-page-settings.update'), $this->contactPagePayload([
                'google_maps_embed_url' => 'https://www.google.com.evil.example/maps/embed?pb=unsafe',
                'map_latitude' => 91,
                'map_longitude' => -181,
            ]));

        $response->assertSessionHasErrors([
            'google_maps_embed_url',
            'map_latitude',
            'map_longitude',
        ]);
        $this->assertSame(
            'Use a valid HTTPS Google Maps embed URL from www.google.com/maps/embed.',
            session('errors')->first('google_maps_embed_url'),
        );
        $this->assertDatabaseCount('contact_page_settings', 0);
    }

    private function contactPagePayload(array $overrides = []): array
    {
        return array_merge([
            'page_title' => 'Contact book-store',
            'main_heading' => 'We would love to hear from you',
            'contact_email' => 'hello@book-store.example',
            'google_maps_url' => 'https://maps.example/book-store',
            'map_location_name' => 'book-store Downtown',
            'google_maps_embed_url' => 'https://www.google.com/maps/embed?pb=sample-location',
            'map_latitude' => '45.5231',
            'map_longitude' => '-122.6765',
            'show_map' => 1,
            'contact_form_heading' => 'Send us a message',
            'submit_button_text' => 'Send message',
            'success_message' => 'Thank you. We will be in touch soon.',
        ], $overrides);
    }
}
