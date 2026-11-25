<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\BannerImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_access_banner_management(): void
    {
        $this->get(route('admin.banners.index'))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.banners.index'))
            ->assertForbidden();
    }

    public function test_administrators_can_create_a_banner_with_gallery_images(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $response = $this->post(route('admin.banners.store'), $this->bannerPayload([
            'gallery_images' => [
                UploadedFile::fake()->image('gallery-one.jpg'),
                UploadedFile::fake()->image('gallery-two.png'),
            ],
            'gallery_alt_texts' => ['A reading nook', 'A stack of books'],
        ]));

        $banner = Banner::query()->where('slug', 'summer-reading')->firstOrFail();
        $response->assertRedirect(route('admin.banners.edit', $banner))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'title' => 'Summer reading',
            'slug' => 'summer-reading',
            'status' => 'active',
            'sort_order' => 3,
        ]);
        $this->assertDatabaseCount('banner_images', 2);
        $this->assertSame('A reading nook', $banner->images()->firstOrFail()->alt_text);
        $this->assertDatabaseHas('activities', [
            'action' => 'created',
            'subject_type' => Banner::class,
            'subject_id' => $banner->id,
        ]);
        Storage::disk('public')->assertExists($banner->main_image);
        $banner->images->each(fn (BannerImage $image) => Storage::disk('public')->assertExists($image->image_path));

        $this->get(route('admin.banners.edit', $banner))
            ->assertOk()
            ->assertSee('Banner image gallery')
            ->assertSee('A reading nook');
    }

    public function test_duplicate_banner_names_receive_unique_slugs(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->post(route('admin.banners.store'), $this->bannerPayload())
            ->assertRedirect();
        $this->post(route('admin.banners.store'), $this->bannerPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('banners', ['slug' => 'summer-reading']);
        $this->assertDatabaseHas('banners', ['slug' => 'summer-reading-2']);
        $this->assertDatabaseCount('banners', 2);
    }

    public function test_new_banner_form_hides_the_requested_fields(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.banners.create'))
            ->assertOk()
            ->assertDontSee('Display button')
            ->assertDontSee('Show the call-to-action when rendered on your website.')
            ->assertDontSee('Open link in a new tab')
            ->assertDontSee('Only applies when the button is displayed.')
            ->assertDontSee('Image alt text')
            ->assertDontSee('Banner slug')
            ->assertDontSee('Banner type')
            ->assertDontSee('Text alignment')
            ->assertDontSee('Start date')
            ->assertDontSee('End date')
            ->assertSee('name="slug" type="hidden"', false)
            ->assertSee('name="type" type="hidden" value="main"', false)
            ->assertSee('name="image_alt" type="hidden"', false)
            ->assertSee('name="show_button" value="0"', false)
            ->assertSee('name="open_new_tab" value="0"', false)
            ->assertSee('name="text_alignment" value="left"', false);
    }

    public function test_editing_a_banner_preserves_values_for_hidden_fields(): void
    {
        $this->actingAs($this->admin());
        $banner = $this->createBanner([
            'image_alt' => 'Preserved cover description',
            'type' => 'promotional',
            'show_button' => true,
            'open_new_tab' => true,
            'button_text' => 'Shop the collection',
            'button_url' => '/collections/summer',
            'text_alignment' => 'center',
            'start_date' => '2026-11-25',
            'end_date' => '2026-12-01',
        ]);

        $this->get(route('admin.banners.edit', $banner))
            ->assertOk()
            ->assertDontSee('Display button')
            ->assertDontSee('Open link in a new tab')
            ->assertDontSee('Image alt text')
            ->assertDontSee('Banner slug')
            ->assertDontSee('Banner type')
            ->assertDontSee('Text alignment')
            ->assertDontSee('Start date')
            ->assertDontSee('End date')
            ->assertSee('name="image_alt" type="hidden" value="Preserved cover description"', false)
            ->assertSee('name="type" type="hidden" value="promotional"', false)
            ->assertSee('name="show_button" value="1"', false)
            ->assertSee('name="open_new_tab" value="1"', false)
            ->assertSee('name="text_alignment" value="center"', false)
            ->assertSee('name="start_date" value="2026-11-25"', false)
            ->assertSee('name="end_date" value="2026-12-01"', false);

        $payload = $this->bannerPayload([
            'title' => $banner->title,
            'name' => $banner->name,
            'type' => $banner->type,
            'image_alt' => 'Preserved cover description',
            'show_button' => 1,
            'open_new_tab' => 1,
            'button_text' => 'Shop the collection',
            'button_url' => '/collections/summer',
            'text_alignment' => 'center',
            'start_date' => '2026-11-25',
            'end_date' => '2026-12-01',
        ]);
        unset($payload['main_image']);

        $this->put(route('admin.banners.update', $banner), $payload)
            ->assertRedirect(route('admin.banners.edit', $banner));

        $updatedBanner = $banner->fresh();
        $this->assertSame('promotional', $updatedBanner->type);
        $this->assertSame('Preserved cover description', $updatedBanner->image_alt);
        $this->assertSame(true, $updatedBanner->show_button);
        $this->assertSame(true, $updatedBanner->open_new_tab);
        $this->assertSame('center', $updatedBanner->text_alignment);
        $this->assertSame('2026-11-25', $updatedBanner->start_date->format('Y-m-d'));
        $this->assertSame('2026-12-01', $updatedBanner->end_date->format('Y-m-d'));
    }

    public function test_banner_search_filters_and_display_order_are_applied(): void
    {
        $this->actingAs($this->admin());
        $this->createBanner([
            'title' => 'Campaign second',
            'name' => 'Campaign second',
            'slug' => 'campaign-second',
            'sort_order' => 20,
        ]);
        $this->createBanner([
            'title' => 'Campaign first',
            'name' => 'Campaign first',
            'slug' => 'campaign-first',
            'sort_order' => 1,
        ]);
        $this->createBanner([
            'title' => 'Campaign inactive',
            'name' => 'Campaign inactive',
            'slug' => 'campaign-inactive',
            'status' => 'inactive',
        ]);
        $this->createBanner([
            'title' => 'Campaign promotional',
            'name' => 'Campaign promotional',
            'slug' => 'campaign-promotional',
            'type' => 'promotional',
        ]);

        $response = $this->get(route('admin.banners.index', [
            'q' => 'Campaign',
            'type' => 'main',
            'status' => 'active',
            'sort' => 'order_asc',
        ]));

        $response->assertOk()
            ->assertSee('Campaign first')
            ->assertSee('Campaign second')
            ->assertDontSee('Campaign inactive')
            ->assertDontSee('Campaign promotional');

        $content = $response->getContent();
        $firstPosition = strpos($content, 'Campaign first');
        $secondPosition = strpos($content, 'Campaign second');
        $this->assertSame(true, $firstPosition !== false && $secondPosition !== false && $firstPosition < $secondPosition);
    }

    public function test_administrators_can_toggle_a_banner_status(): void
    {
        $this->actingAs($this->admin());
        $banner = $this->createBanner();

        $this->patch(route('admin.banners.status', $banner))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('activities', [
            'action' => 'updated',
            'subject_type' => Banner::class,
            'subject_id' => $banner->id,
        ]);
    }

    public function test_banner_creation_requires_valid_details_and_safe_button_urls(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->from(route('admin.banners.create'))
            ->post(route('admin.banners.store'), $this->bannerPayload([
                'title' => '',
                'button_url' => 'javascript:alert(1)',
                'show_button' => 1,
            ]))
            ->assertRedirect(route('admin.banners.create'))
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
                'button_url' => 'The button URL must be an internal path or a valid HTTP or HTTPS URL.',
            ]);

        $this->assertDatabaseCount('banners', 0);
        Storage::disk('public')->assertDirectoryEmpty('banners');
    }

    public function test_banner_creation_rejects_non_image_uploads(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->from(route('admin.banners.create'))
            ->post(route('admin.banners.store'), $this->bannerPayload([
                'main_image' => UploadedFile::fake()->create('banner.svg', 5, 'image/svg+xml'),
            ]))
            ->assertRedirect(route('admin.banners.create'))
            ->assertSessionHasErrors('main_image');

        $this->assertDatabaseCount('banners', 0);
        Storage::disk('public')->assertDirectoryEmpty('banners');
    }

    public function test_administrators_can_replace_and_remove_the_main_image(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $banner = $this->createBanner([
            'main_image' => 'banners/main/original.jpg',
            'image_alt' => 'Original cover',
        ]);
        Storage::disk('public')->put($banner->main_image, 'original');

        $this->get(route('admin.banners.edit', $banner))
            ->assertOk()
            ->assertSee('data-image-remove="#banner-main-preview"', false);

        $this->put(route('admin.banners.update', $banner), $this->bannerPayload([
            'title' => $banner->title,
            'name' => $banner->name,
            'main_image' => UploadedFile::fake()->image('replacement.webp'),
        ]))->assertRedirect(route('admin.banners.edit', $banner));

        $replacementPath = $banner->fresh()->main_image;
        $this->assertNotSame('banners/main/original.jpg', $replacementPath);
        Storage::disk('public')->assertMissing('banners/main/original.jpg');
        Storage::disk('public')->assertExists($replacementPath);

        $payload = $this->bannerPayload([
            'title' => $banner->title,
            'name' => $banner->name,
            'remove_main_image' => 1,
        ]);
        unset($payload['main_image']);

        $this->put(route('admin.banners.update', $banner), $payload)
            ->assertRedirect(route('admin.banners.edit', $banner))
            ->assertSessionHas('success');

        $this->assertNull($banner->fresh()->main_image);
        $this->assertNull($banner->fresh()->image_alt);
        Storage::disk('public')->assertMissing($replacementPath);
    }

    public function test_gallery_images_can_be_uploaded_replaced_promoted_and_removed(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $banner = $this->createBanner(['main_image' => 'banners/main/original.jpg']);
        Storage::disk('public')->put($banner->main_image, 'original');

        $this->from(route('admin.banners.edit', $banner))
            ->post(route('admin.banners.images.store', $banner), [
                'images' => [
                    UploadedFile::fake()->image('gallery-first.jpg'),
                    UploadedFile::fake()->image('gallery-second.jpg'),
                ],
                'alt_texts' => ['First artwork', 'Second artwork'],
            ])
            ->assertRedirect(route('admin.banners.edit', $banner))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('banner_images', 2);
        $galleryImage = $banner->images()->firstOrFail();
        $galleryPath = $galleryImage->image_path;

        $this->patch(route('admin.banners.images.main', [$banner, $galleryImage]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($galleryPath, $banner->fresh()->main_image);
        $this->assertSame('banners/main/original.jpg', $galleryImage->fresh()->image_path);

        $this->patch(route('admin.banners.images.update', [$banner, $galleryImage]), [
            'alt_text' => 'Replacement artwork',
            'sort_order' => 8,
            'status' => 'inactive',
            'image' => UploadedFile::fake()->image('replacement.png'),
        ])->assertRedirect()
            ->assertSessionHas('success');

        $replacementPath = $galleryImage->fresh()->image_path;
        $this->assertSame('Replacement artwork', $galleryImage->fresh()->alt_text);
        $this->assertSame(8, $galleryImage->fresh()->sort_order);
        $this->assertSame('inactive', $galleryImage->fresh()->status);
        Storage::disk('public')->assertMissing('banners/main/original.jpg');
        Storage::disk('public')->assertExists($replacementPath);

        $this->delete(route('admin.banners.images.destroy', [$banner, $galleryImage]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('banner_images', ['id' => $galleryImage->id]);
        Storage::disk('public')->assertMissing($replacementPath);
        $this->assertDatabaseCount('banner_images', 1);
    }

    public function test_gallery_images_cannot_be_changed_through_another_banner(): void
    {
        $this->actingAs($this->admin());
        $banner = $this->createBanner(['slug' => 'first-banner']);
        $otherBanner = $this->createBanner(['slug' => 'other-banner']);
        $image = $banner->images()->create([
            'image_path' => 'banners/gallery/test.jpg',
            'sort_order' => 0,
            'status' => 'active',
        ]);

        $this->delete(route('admin.banners.images.destroy', [$otherBanner, $image]))
            ->assertNotFound();

        $this->assertDatabaseHas('banner_images', ['id' => $image->id, 'banner_id' => $banner->id]);
    }

    public function test_deleting_a_banner_removes_its_gallery_records_and_stored_images(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $banner = $this->createBanner(['main_image' => 'banners/main/to-delete.jpg']);
        $galleryImage = $banner->images()->create([
            'image_path' => 'banners/gallery/to-delete.jpg',
            'sort_order' => 0,
            'status' => 'active',
        ]);
        Storage::disk('public')->put($banner->main_image, 'main');
        Storage::disk('public')->put($galleryImage->image_path, 'gallery');

        $this->delete(route('admin.banners.destroy', $banner))
            ->assertRedirect(route('admin.banners.index'))
            ->assertSessionHas('success');

        $this->assertSame(true, $banner->fresh()->trashed());
        $this->assertDatabaseCount('banner_images', 0);
        Storage::disk('public')->assertMissing('banners/main/to-delete.jpg');
        Storage::disk('public')->assertMissing('banners/gallery/to-delete.jpg');
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function bannerPayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Summer reading',
            'name' => 'Summer reading',
            'type' => 'main',
            'main_image' => UploadedFile::fake()->image('banner.jpg'),
            'image_alt' => 'Books on a table',
            'heading' => 'Find your next favorite',
            'sub_heading' => 'Stories for the season',
            'description' => 'Discover a fresh collection.',
            'button_text' => 'Browse now',
            'button_url' => '/collections/summer',
            'show_button' => 0,
            'open_new_tab' => 0,
            'text_alignment' => 'left',
            'status' => 'active',
            'sort_order' => 3,
            'start_date' => null,
            'end_date' => null,
        ], $overrides);
    }

    private function createBanner(array $overrides = []): Banner
    {
        return Banner::query()->create(array_replace([
            'title' => 'Campaign',
            'name' => 'Campaign',
            'slug' => 'campaign',
            'type' => 'main',
            'main_image' => null,
            'status' => 'active',
            'sort_order' => 10,
            'show_button' => false,
            'open_new_tab' => false,
            'text_alignment' => 'left',
        ], $overrides));
    }
}
