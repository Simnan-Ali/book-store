<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ContactPageSetting;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookstoreAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_access_the_admin_panel(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.contact-page-settings.edit'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('Welcome back.');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->get(route('admin.settings.edit'))->assertForbidden();
        $this->get(route('admin.contact-page-settings.edit'))->assertForbidden();
        $this->put(route('admin.contact-page-settings.update'), $this->contactPagePayload())
            ->assertForbidden();
    }

    public function test_administrator_can_sign_in_and_sign_out(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->post(route('admin.logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_administrators_can_upload_replace_and_remove_their_profile_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $this->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('data-account-menu', false)
            ->assertSee('account-menu-item--logout', false)
            ->assertSee('Sign out');

        $this->put(route('admin.settings.update'), [
            'name' => $admin->name,
            'email' => $admin->email,
            'avatar' => $this->fakePng('profile.png'),
        ])->assertRedirect(route('admin.settings.edit'));

        $firstAvatarPath = $admin->fresh()->avatar_path;
        $this->assertNotNull($firstAvatarPath);
        Storage::disk('public')->assertExists($firstAvatarPath);

        $this->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee($firstAvatarPath)
            ->assertSee('Remove current photo');

        $this->put(route('admin.settings.update'), [
            'name' => $admin->name,
            'email' => $admin->email,
            'avatar' => $this->fakePng('replacement.png'),
        ])->assertRedirect(route('admin.settings.edit'));

        $secondAvatarPath = $admin->fresh()->avatar_path;
        $this->assertNotSame($firstAvatarPath, $secondAvatarPath);
        Storage::disk('public')->assertMissing($firstAvatarPath);
        Storage::disk('public')->assertExists($secondAvatarPath);

        $this->put(route('admin.settings.update'), [
            'name' => $admin->name,
            'email' => $admin->email,
            'remove_avatar' => 1,
        ])->assertRedirect(route('admin.settings.edit'));

        $this->assertNull($admin->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($secondAvatarPath);
    }

    public function test_profile_photo_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $this->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), [
                'name' => $admin->name,
                'email' => $admin->email,
                'avatar' => UploadedFile::fake()->create('profile.svg', 5, 'image/svg+xml'),
            ])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($admin->fresh()->avatar_path);
        Storage::disk('public')->assertDirectoryEmpty('avatars');
    }

    public function test_administrators_can_save_and_update_the_single_contact_page_record(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.contact-page-settings.edit'))
            ->assertOk()
            ->assertSee('WEBSITE')
            ->assertSee('Menu Master')
            ->assertSee('Contact Us Page Master')
            ->assertSee('Page title');
        $this->assertDatabaseCount('contact_page_settings', 0);

        $payload = $this->contactPagePayload();
        $this->put(route('admin.contact-page-settings.update'), $payload)
            ->assertRedirect(route('admin.contact-page-settings.edit'))
            ->assertSessionHas('success');

        $settings = ContactPageSetting::query()->findOrFail(ContactPageSetting::SINGLETON_ID);
        $this->assertSame(ContactPageSetting::SINGLETON_ID, $settings->getKey());
        $this->assertDatabaseHas('contact_page_settings', $payload + ['id' => ContactPageSetting::SINGLETON_ID]);

        $updatedPayload = $this->contactPagePayload([
            'page_title' => 'Visit Folio Books',
            'page_subtitle' => 'Our team is ready to help',
            'main_heading' => 'Come say hello',
            'description' => 'Updated page introduction.',
            'contact_email' => 'support@folio.example',
            'contact_phone' => '+1 555 222 3344',
            'whatsapp_number' => '+1 555 777 8899',
            'office_address' => '42 Reading Lane, Portland',
            'google_maps_url' => 'https://maps.example/folio-updated',
            'business_hours' => '10:00 AM to 6:00 PM',
            'working_days' => 'Tuesday to Saturday',
            'additional_information' => 'Updated visitor information.',
            'facebook_url' => 'https://facebook.example/folio-updated',
            'instagram_url' => 'https://instagram.example/folio-updated',
            'linkedin_url' => 'https://linkedin.example/folio-updated',
            'youtube_url' => 'https://youtube.example/folio-updated',
            'contact_form_heading' => 'Write to our team',
            'contact_form_description' => 'Updated form introduction.',
            'submit_button_text' => 'Send to Folio',
            'success_message' => 'Your message is on its way.',
            'meta_title' => 'Visit Folio Books',
            'meta_description' => 'Updated search description.',
            'meta_keywords' => 'books, folio, contact',
        ]);

        $this->put(route('admin.contact-page-settings.update'), $updatedPayload + ['id' => 99])
            ->assertRedirect(route('admin.contact-page-settings.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('contact_page_settings', 1);
        $this->assertDatabaseMissing('contact_page_settings', ['id' => 99]);
        $this->assertDatabaseHas('contact_page_settings', $updatedPayload + [
            'id' => ContactPageSetting::SINGLETON_ID,
            'banner_image' => null,
            'banner_alt_text' => null,
        ]);
        $this->get(route('admin.contact-page-settings.edit'))
            ->assertOk()
            ->assertSee('support@folio.example')
            ->assertSee('Come say hello');
    }

    public function test_administrators_can_upload_replace_and_remove_contact_page_banner(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->put(route('admin.contact-page-settings.update'), array_merge(
            $this->contactPagePayload(),
            ['banner_image' => $this->fakePng('contact-banner.png')],
        ))->assertRedirect(route('admin.contact-page-settings.edit'));

        $settings = ContactPageSetting::query()->findOrFail(ContactPageSetting::SINGLETON_ID);
        $firstBannerPath = $settings->banner_image;
        $this->assertNotNull($firstBannerPath);
        Storage::disk('public')->assertExists($firstBannerPath);

        $this->put(route('admin.contact-page-settings.update'), array_merge(
            $this->contactPagePayload(['banner_alt_text' => 'Updated bookstore entrance']),
            ['banner_image' => $this->fakePng('replacement-banner.png')],
        ))->assertRedirect(route('admin.contact-page-settings.edit'));

        $secondBannerPath = $settings->fresh()->banner_image;
        $this->assertNotSame($firstBannerPath, $secondBannerPath);
        Storage::disk('public')->assertMissing($firstBannerPath);
        Storage::disk('public')->assertExists($secondBannerPath);

        $this->put(route('admin.contact-page-settings.update'), array_merge(
            $this->contactPagePayload(),
            ['remove_banner_image' => 1],
        ))->assertRedirect(route('admin.contact-page-settings.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('contact_page_settings', [
            'id' => ContactPageSetting::SINGLETON_ID,
            'banner_image' => null,
            'banner_alt_text' => null,
        ]);
        Storage::disk('public')->assertMissing($secondBannerPath);
        $this->assertDatabaseCount('contact_page_settings', 1);
    }

    public function test_contact_page_settings_require_valid_contact_details_urls_and_banner_images(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->from(route('admin.contact-page-settings.edit'))
            ->put(route('admin.contact-page-settings.update'), [])
            ->assertSessionHasErrors([
                'page_title',
                'main_heading',
                'contact_email',
                'contact_form_heading',
                'submit_button_text',
                'success_message',
            ]);

        $this->from(route('admin.contact-page-settings.edit'))
            ->put(route('admin.contact-page-settings.update'), array_merge(
                $this->contactPagePayload([
                    'contact_email' => 'not-an-email',
                    'google_maps_url' => 'javascript:alert(1)',
                    'facebook_url' => 'ftp://facebook.example/profile',
                ]),
                ['banner_image' => UploadedFile::fake()->create('banner.svg', 5, 'image/svg+xml')],
            ))
            ->assertSessionHasErrors(['contact_email', 'google_maps_url', 'facebook_url', 'banner_image']);

        $this->assertDatabaseCount('contact_page_settings', 0);
        Storage::disk('public')->assertDirectoryEmpty('contact-page');
    }

    public function test_category_crud_slug_uniqueness_and_dependency_guard(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.categories.store'), [
            'name' => 'Modern Fiction',
            'description' => 'Contemporary stories.',
            'is_active' => 1,
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::firstOrFail();
        $this->assertSame('modern-fiction', $category->slug);
        $this->get(route('admin.categories.index'))->assertOk()->assertSee('Modern Fiction');
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats) => $stats['categories'] === 1)
            ->assertViewHas('activities', fn ($activities) => $activities->isNotEmpty());

        $this->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), [
                'name' => 'Modern Fiction',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('name');

        $this->put(route('admin.categories.update', $category), [
            'name' => 'Modern Classics',
            'description' => 'A lasting collection.',
            'is_active' => 0,
        ])->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'slug' => 'modern-classics', 'is_active' => 0]);

        Subcategory::create([
            'category_id' => $category->id,
            'name' => 'Literary',
            'slug' => 'literary',
            'is_active' => true,
        ]);
        $this->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    public function test_subcategory_crud_requires_a_parent_and_lists_its_category(): void
    {
        $this->actingAs($this->admin());
        $category = Category::create(['name' => 'Mystery', 'slug' => 'mystery', 'is_active' => true]);

        $this->from(route('admin.subcategories.create'))
            ->post(route('admin.subcategories.store'), ['name' => 'No Parent', 'is_active' => 1])
            ->assertSessionHasErrors('category_id');

        $this->post(route('admin.subcategories.store'), [
            'category_id' => $category->id,
            'name' => 'Cozy Mystery',
            'description' => '',
            'is_active' => 1,
        ])->assertRedirect(route('admin.subcategories.index'));

        $subcategory = Subcategory::firstOrFail();
        $this->assertSame('cozy-mystery', $subcategory->slug);
        $this->get(route('admin.subcategories.index'))->assertOk()->assertSee('Mystery')->assertSee('Cozy Mystery');

        $this->put(route('admin.subcategories.update', $subcategory), [
            'category_id' => $category->id,
            'name' => 'Village Mystery',
            'description' => '',
            'is_active' => 1,
        ])->assertRedirect(route('admin.subcategories.index'));
        $this->assertDatabaseHas('subcategories', ['id' => $subcategory->id, 'slug' => 'village-mystery']);

        $this->delete(route('admin.categories.destroy', $category))->assertSessionHas('error');
        $this->delete(route('admin.subcategories.destroy', $subcategory))
            ->assertRedirect(route('admin.subcategories.index'));
        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));
    }

    public function test_publisher_crud_and_product_dependency_guard(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.brands.store'), [
            'name' => 'Northstar Press',
            'website_url' => 'https://northstar.example',
            'is_active' => 1,
        ])->assertRedirect(route('admin.brands.index'));

        $brand = Brand::firstOrFail();
        $this->assertSame('northstar-press', $brand->slug);
        $this->get(route('admin.brands.index'))->assertOk()->assertSee('Northstar Press');

        $this->put(route('admin.brands.update', $brand), [
            'name' => 'Northstar Publishing',
            'website_url' => '',
            'is_active' => 1,
        ])->assertRedirect(route('admin.brands.index'));
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'slug' => 'northstar-publishing']);

        $product = $this->makeProduct($brand);
        $this->delete(route('admin.brands.destroy', $brand))->assertSessionHas('error');
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'deleted_at' => null]);
        $product->delete();

        $this->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect(route('admin.brands.index'));
    }

    public function test_book_crud_uploads_images_filters_and_enforces_unique_identifiers(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $category = Category::create(['name' => 'Poetry', 'slug' => 'poetry', 'is_active' => true]);
        $brand = Brand::create(['name' => 'Verse House', 'slug' => 'verse-house', 'is_active' => true]);
        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'Modern Poetry',
            'slug' => 'modern-poetry',
            'is_active' => true,
        ]);
        $payload = $this->productPayload($category, $brand, $subcategory);
        $payload['main_image'] = $this->fakePng('cover.png');
        $payload['images'] = [$this->fakePng('inside.png')];

        $this->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product = Product::with('images')->firstOrFail();
        $this->assertSame('the-quiet-atlas', $product->slug);
        $this->assertCount(1, $product->images);
        Storage::disk('public')->assertExists($product->main_image);
        Storage::disk('public')->assertExists($product->images->first()->image_path);
        $this->get(route('admin.media.index'))->assertOk()->assertSee('The Quiet Atlas');
        $this->get(route('admin.inventory'))->assertOk()->assertSee('The Quiet Atlas');
        $this->get(route('admin.products.index', ['q' => 'QUIET', 'stock' => 'low']))
            ->assertOk()->assertSee('The Quiet Atlas');

        $this->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->productPayload($category, $brand, $subcategory))
            ->assertSessionHasErrors(['isbn', 'sku']);

        $this->put(route('admin.products.update', $product), array_merge($this->productPayload($category, $brand, $subcategory), [
            'title' => 'The Quiet Atlas, Revised',
            'stock_quantity' => 0,
        ]))->assertRedirect(route('admin.products.index'));
        $this->get(route('admin.products.index', ['stock' => 'out']))
            ->assertOk()->assertSee('The Quiet Atlas, Revised');

        $imagePath = $product->images()->first()->image_path;
        $coverPath = $product->main_image;
        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted($product);
        $this->assertDatabaseMissing('product_images', ['product_id' => $product->id]);
        Storage::disk('public')->assertMissing($imagePath);
        Storage::disk('public')->assertMissing($coverPath);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function contactPagePayload(array $overrides = []): array
    {
        return array_merge([
            'page_title' => 'Contact Folio Books',
            'page_subtitle' => 'We are here to help',
            'main_heading' => 'We would love to hear from you',
            'description' => 'Get in touch with the Folio Books team.',
            'banner_alt_text' => 'Folio Books storefront',
            'contact_email' => 'hello@folio.example',
            'contact_phone' => '+1 555 123 4567',
            'whatsapp_number' => '+1 555 987 6543',
            'office_address' => '10 Book Street, Portland',
            'google_maps_url' => 'https://maps.example/folio',
            'business_hours' => '9:00 AM to 5:00 PM',
            'working_days' => 'Monday to Friday',
            'additional_information' => 'Call ahead for special appointments.',
            'facebook_url' => 'https://facebook.example/folio',
            'instagram_url' => 'https://instagram.example/folio',
            'linkedin_url' => 'https://linkedin.example/folio',
            'youtube_url' => 'https://youtube.example/folio',
            'contact_form_heading' => 'Send us a message',
            'contact_form_description' => 'We will reply as soon as possible.',
            'submit_button_text' => 'Send message',
            'success_message' => 'Thank you. We will be in touch soon.',
            'meta_title' => 'Contact Folio Books',
            'meta_description' => 'Contact the Folio Books team.',
            'meta_keywords' => 'bookstore, contact, Folio Books',
        ], $overrides);
    }

    private function makeProduct(Brand $brand): Product
    {
        $category = Category::create(['name' => 'Essays', 'slug' => 'essays', 'is_active' => true]);

        return Product::create(array_merge($this->productPayload($category, $brand), [
            'slug' => 'sample-book',
        ]));
    }

    private function productPayload(Category $category, Brand $brand, ?Subcategory $subcategory = null): array
    {
        return [
            'title' => 'The Quiet Atlas',
            'isbn' => '9780000000001',
            'sku' => 'BK-QUIET-01',
            'short_description' => 'A thoughtful journey.',
            'description' => 'A longer description.',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory?->id,
            'brand_id' => $brand->id,
            'author' => 'Morgan Reed',
            'language' => 'English',
            'edition' => 'First edition',
            'published_at' => '2025-01-15',
            'regular_price' => 25,
            'selling_price' => 20,
            'stock_quantity' => 3,
            'low_stock_alert' => 5,
            'pages' => 240,
            'format' => 'Paperback',
            'weight' => 320,
            'is_featured' => 0,
            'is_best_seller' => 0,
            'is_active' => 1,
        ];
    }

    private function fakePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/Jx8AAAAASUVORK5CYII='),
        );
    }
}
