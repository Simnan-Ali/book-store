<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
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
        $this->get(route('login'))->assertOk()->assertSee('Welcome back.');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->get(route('admin.settings.edit'))->assertForbidden();
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
