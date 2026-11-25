<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryUpdateRequest;
use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Subcategory;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\UniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:subcategories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'in:in,out,low'],
            'sort' => ['nullable', 'in:latest,oldest,price_asc,price_desc,name'],
        ]);
        $isInventory = (bool) $request->route('inventory');

        if (! $isInventory) {
            Gate::authorize('viewAny', Product::class);
        }

        $products = Product::with(['category', 'subcategory', 'brand', 'vendor'])
            ->when(
                ! $request->user()->isSuperAdmin() && $request->user()->hasActiveRole('vendor'),
                fn (Builder $query) => $query->where('vendor_id', $request->user()->getKey()),
            )
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', "%{$term}%")
                        ->orWhere('isbn', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%");
                });
            })
            ->when($filters['category_id'] ?? null, fn (Builder $query, $id) => $query->where('category_id', $id))
            ->when($filters['subcategory_id'] ?? null, fn (Builder $query, $id) => $query->where('subcategory_id', $id))
            ->when($filters['brand_id'] ?? null, fn (Builder $query, $id) => $query->where('brand_id', $id))
            ->when(isset($filters['min_price']), fn (Builder $query) => $query->where('selling_price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn (Builder $query) => $query->where('selling_price', '<=', $filters['max_price']))
            ->when(($filters['stock'] ?? null) === 'in', fn (Builder $query) => $query->where('stock_quantity', '>', 0))
            ->when(($filters['stock'] ?? null) === 'out', fn (Builder $query) => $query->where('stock_quantity', 0))
            ->when(($filters['stock'] ?? null) === 'low', fn (Builder $query) => $query
                ->where('stock_quantity', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'low_stock_alert'))
            ->when(($filters['sort'] ?? 'latest') === 'oldest', fn (Builder $query) => $query->oldest())
            ->when(($filters['sort'] ?? 'latest') === 'price_asc', fn (Builder $query) => $query->orderBy('selling_price'))
            ->when(($filters['sort'] ?? 'latest') === 'price_desc', fn (Builder $query) => $query->orderByDesc('selling_price'))
            ->when(($filters['sort'] ?? 'latest') === 'name', fn (Builder $query) => $query->orderBy('title'))
            ->when(! isset($filters['sort']) || $filters['sort'] === 'latest', fn (Builder $query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'subcategories' => Subcategory::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'filters' => $filters,
            'isInventory' => $isInventory,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Product::class);

        $product = new Product([
            'is_active' => true,
            'format' => 'Paperback',
            'low_stock_alert' => 5,
            'stock_quantity' => 0,
        ]);

        return view('admin.products.form', [
            'product' => $product,
            ...$this->formOptions($request->user(), $product),
        ]);
    }

    public function show(Product $product): View
    {
        Gate::authorize('view', $product);

        $product->load(['category', 'subcategory', 'brand', 'vendor', 'images']);

        return view('admin.products.show', ['product' => $product]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        $data = $request->safe()->except(['main_image', 'images']);
        $data['slug'] = UniqueSlug::make($data['title'], 'products');
        $data['main_image'] = $request->file('main_image')?->store('books/covers', 'public');
        $data['vendor_id'] = ! $request->user()->isSuperAdmin() && $request->user()->hasActiveRole('vendor')
            ? $request->user()->getKey()
            : ($data['vendor_id'] ?? null);

        DB::transaction(function () use ($request, $data): void {
            $product = Product::create($data);
            $this->storeAdditionalImages($request, $product);
            ActivityLogger::record('created', $product, "Added book '{$product->title}'.");
        });

        return redirect()->route('admin.products.index')->with('success', 'Book added successfully.');
    }

    public function edit(Request $request, Product $product): View
    {
        Gate::authorize('update', $product);

        $product->load('images');

        return view('admin.products.form', [
            'product' => $product,
            ...$this->formOptions($request->user(), $product),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $data = $request->safe()->except(['main_image', 'images', 'remove_image_ids']);
        $data['slug'] = UniqueSlug::make($data['title'], 'products', $product->id);
        $previousCover = $product->main_image;
        $removedPaths = [];

        if (! $request->user()->isSuperAdmin() && $request->user()->hasActiveRole('vendor')) {
            $data['vendor_id'] = $request->user()->getKey();
        }

        if ($request->hasFile('main_image')) {
            $data['main_image'] = $request->file('main_image')->store('books/covers', 'public');
        }

        DB::transaction(function () use ($request, $product, $data, &$removedPaths): void {
            $product->update($data);

            if ($request->filled('remove_image_ids')) {
                $images = $product->images()->whereIn('id', $request->input('remove_image_ids'))->get();
                $removedPaths = $images->pluck('image_path')->all();
                $product->images()->whereIn('id', $images->modelKeys())->delete();
            }

            $this->storeAdditionalImages($request, $product);
            ActivityLogger::record('updated', $product, "Updated book '{$product->title}'.");
        });

        if ($request->hasFile('main_image') && $previousCover) {
            Storage::disk('public')->delete($previousCover);
        }

        Storage::disk('public')->delete($removedPaths);

        return redirect()->route('admin.products.index')->with('success', 'Book updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $paths = $product->images()->pluck('image_path')->all();

        DB::transaction(function () use ($product): void {
            ActivityLogger::record('deleted', $product, "Removed book '{$product->title}'.");
            $product->images()->delete();
            $product->delete();
        });

        if ($product->main_image) {
            $paths[] = $product->main_image;
        }

        Storage::disk('public')->delete($paths);

        return redirect()->route('admin.products.index')->with('success', 'Book deleted successfully.');
    }

    public function updateInventory(InventoryUpdateRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('updateInventory', $product);

        $product->update($request->validated());
        ActivityLogger::record('updated', $product, "Updated inventory for '{$product->title}'.");

        return back()->with('success', 'Inventory updated successfully.');
    }

    private function formOptions(User $user, Product $product): array
    {
        $canAssignVendor = $user->isSuperAdmin() || ! $user->hasActiveRole('vendor');
        $vendors = collect();

        if ($canAssignVendor) {
            $vendors = User::query()
                ->where(function (Builder $query) use ($product): void {
                    $query->whereHas('roles', fn (Builder $query) => $query
                        ->where('roles.name', 'vendor')
                        ->where('roles.guard_name', 'web')
                        ->where('roles.is_active', true));

                    if ($product->vendor_id !== null) {
                        $query->orWhereKey($product->vendor_id);
                    }
                })
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        return [
            'categories' => Category::orderBy('name')->get(),
            'subcategories' => Subcategory::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'vendors' => $vendors,
            'canAssignVendor' => $canAssignVendor,
        ];
    }

    private function storeAdditionalImages(ProductRequest $request, Product $product): void
    {
        foreach ($request->file('images', []) as $order => $image) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $image->store('books/gallery', 'public'),
                'alt_text' => $product->title,
                'sort_order' => $order,
            ]);
        }
    }
}
