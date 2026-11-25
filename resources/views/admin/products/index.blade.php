@extends('layouts.admin')

@section('title', $isInventory ? 'Inventory' : 'Books')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">{{ $isInventory ? 'A HEALTHY STOCKROOM' : 'YOUR BOOKSHOP CATALOGUE' }}</p><h1>{{ $isInventory ? 'Inventory' : 'Books' }}</h1><p>{{ $isInventory ? 'Keep an eye on availability and catch low stock early.' : 'Every title, carefully accounted for and ready to be discovered.' }}</p></div>
        @can('books.create')<a href="{{ route('admin.products.create') }}" class="button button-primary"><x-icon name="plus" class="h-4 w-4"/> Add a book</a>@endcan
    </div>

    <section class="panel filter-panel">
        <form method="GET" action="{{ $isInventory ? route('admin.inventory') : route('admin.products.index') }}" class="filter-form">
            <label class="filter-search"><x-icon name="search"/><input class="filter-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Title, ISBN or SKU..." aria-label="Search books"></label>
            <select class="filter-select" name="category_id" aria-label="Filter by category"><option value="">All categories</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>
            <select class="filter-select" name="subcategory_id" aria-label="Filter by subcategory"><option value="">All subcategories</option>@foreach ($subcategories as $subcategory)<option value="{{ $subcategory->id }}" @selected((string) ($filters['subcategory_id'] ?? '') === (string) $subcategory->id)>{{ $subcategory->name }}</option>@endforeach</select>
            <select class="filter-select" name="brand_id" aria-label="Filter by publisher"><option value="">All publishers</option>@foreach ($brands as $brand)<option value="{{ $brand->id }}" @selected((string) ($filters['brand_id'] ?? '') === (string) $brand->id)>{{ $brand->name }}</option>@endforeach</select>
            <select class="filter-select" name="stock" aria-label="Filter by stock status"><option value="">All stock</option><option value="in" @selected(($filters['stock'] ?? '') === 'in')>In stock</option><option value="low" @selected(($filters['stock'] ?? '') === 'low')>Low stock</option><option value="out" @selected(($filters['stock'] ?? '') === 'out')>Out of stock</option></select>
            <select class="filter-select" name="sort" aria-label="Sort books"><option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>Latest</option><option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest</option><option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Price: low to high</option><option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Price: high to low</option><option value="name" @selected(($filters['sort'] ?? '') === 'name')>Name: A to Z</option></select>
            <div class="filter-search" style="flex: 0 1 175px; min-width: 135px"><input class="filter-input" type="number" step="0.01" min="0" name="min_price" value="{{ $filters['min_price'] ?? '' }}" placeholder="Min price" aria-label="Minimum selling price" style="padding-left: 10px"></div>
            <div class="filter-search" style="flex: 0 1 175px; min-width: 135px"><input class="filter-input" type="number" step="0.01" min="0" name="max_price" value="{{ $filters['max_price'] ?? '' }}" placeholder="Max price" aria-label="Maximum selling price" style="padding-left: 10px"></div>
            <button class="button button-outline filter-submit" type="submit"><x-icon name="filter" class="h-4 w-4"/> Apply</button>
            @if (request()->query())<a class="filter-reset" href="{{ $isInventory ? route('admin.inventory') : route('admin.products.index') }}">Reset</a>@endif
        </form>
    </section>

    <section class="panel table-panel">
        @if ($products->isEmpty())
            <div class="empty-state"><span class="empty-icon"><x-icon name="books"/></span><strong>{{ request()->query() ? 'No books match those filters' : 'Your catalogue starts here' }}</strong><p>{{ request()->query() ? 'Try a different title, stock status, or category.' : 'Add your first book and give your collection a home.' }}</p>@if (! request()->query()) @can('books.create')<a href="{{ route('admin.products.create') }}" class="button button-primary button-small">Add a book</a>@endcan @endif</div>
        @else
            <div class="table-wrap"><table class="admin-table">
                <thead><tr><th>Book</th><th>Category</th><th>Publisher</th><th>Vendor</th><th>Price</th><th>Stock</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td><div class="table-item"><span class="table-thumbnail">@if ($product->main_image)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->main_image) }}" alt="" loading="lazy">@else<x-icon name="book-open" data-image-placeholder/>@endif</span><span><span class="table-primary">{{ $product->title }}</span><span class="table-secondary">{{ $product->author }} &middot; SKU {{ $product->sku }}</span></span></div></td>
                            <td><span class="table-primary">{{ $product->category->name }}</span>@if ($product->subcategory)<span class="table-secondary">{{ $product->subcategory->name }}</span>@endif</td>
                            <td>{{ $product->brand->name }}</td>
                            <td>{{ $product->vendor?->name ?? 'Unassigned' }}</td>
                            <td><span class="table-primary">₹{{ number_format((float) $product->selling_price, 2) }}</span>@if ($product->discount_percentage)<span class="table-secondary">{{ $product->discount_percentage }}% off</span>@endif</td>
                            <td><span class="table-primary">{{ number_format($product->stock_quantity) }} copies</span><span class="table-secondary">Alert at {{ $product->low_stock_alert }}</span>
                                @if ($isInventory && auth()->user()->can('inventory.update'))
                                    <form method="POST" action="{{ route('admin.inventory.stock.update', $product) }}" class="inventory-inline-form">@csrf @method('PATCH')<label><span class="sr-only">Stock quantity for {{ $product->title }}</span><input class="form-input" type="number" min="0" max="2147483647" name="stock_quantity" value="{{ $product->stock_quantity }}" required></label><label><span class="sr-only">Low-stock alert for {{ $product->title }}</span><input class="form-input" type="number" min="0" max="2147483647" name="low_stock_alert" value="{{ $product->low_stock_alert }}" required></label><button class="button button-outline button-small" type="submit">Save stock</button></form>
                                @endif
                            </td>
                            <td>
                                @if (! $product->is_active)<span class="status-pill status-inactive">Inactive</span>
                                @elseif ($product->stock_quantity === 0)<span class="status-pill stock-out">Out of stock</span>
                                @elseif ($product->isLowStock())<span class="status-pill stock-low">Low stock</span>
                                @else<span class="status-pill stock-in">In stock</span>@endif
                            </td>
                            <td><div class="row-actions">
                                @can('books.view')<a class="row-action" href="{{ route('admin.products.show', $product) }}" aria-label="View details for {{ $product->title }}" title="View details"><x-icon name="eye"/></a>@endcan
                                @can('books.edit')<a class="row-action" href="{{ route('admin.products.edit', $product) }}" aria-label="Edit {{ $product->title }}" title="Edit"><x-icon name="edit"/></a>@endcan
                                @can('books.delete')<form method="POST" action="{{ route('admin.products.destroy', $product) }}" data-confirm="This will remove the book and its uploaded images from your active catalogue.">@csrf @method('DELETE')<button class="row-action row-action--delete" type="submit" aria-label="Delete {{ $product->title }}" title="Delete"><x-icon name="trash"/></button></form>@endcan
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <div class="table-footer"><span>Showing {{ $products->firstItem() }}&ndash;{{ $products->lastItem() }} of {{ $products->total() }} books</span><div class="pagination">{{ $products->onEachSide(1)->links('pagination::simple-tailwind') }}</div></div>
        @endif
    </section>
@endsection
