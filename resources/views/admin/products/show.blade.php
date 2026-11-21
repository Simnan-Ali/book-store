@extends('layouts.admin')

@section('title', 'Book details')

@section('content')
    <div class="module-heading">
        <div>
            <p class="eyebrow">BOOK RECORD</p>
            <h1>{{ $product->title }}</h1>
            <p>Review this book's catalogue, pricing, and stock details.</p>
        </div>
        <div class="module-actions product-detail-actions">
            <a href="{{ route('admin.products.index') }}" class="button button-muted">Back to books</a>
            <a href="{{ route('admin.products.edit', $product) }}" class="button button-primary"><x-icon name="edit" class="h-4 w-4"/> Edit book</a>
        </div>
    </div>

    <div class="product-detail-layout">
        <section class="product-detail-card product-overview-card">
            <div class="product-detail-cover-frame">
                @if ($product->main_image)
                    <img class="product-detail-cover" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->main_image) }}" alt="{{ $product->title }} cover">
                @else
                    <div class="product-cover-placeholder"><x-icon name="book-open" class="h-10 w-10"/><span>No cover image</span></div>
                @endif
            </div>

            <div class="product-overview-content">
                <div class="product-detail-badges">
                    @if ($product->is_featured)<span class="product-detail-tag">Featured</span>@endif
                    @if ($product->is_best_seller)<span class="product-detail-tag">Best seller</span>@endif
                </div>
                <h2>{{ $product->title }}</h2>
                <p class="product-detail-author">By {{ $product->author }}</p>
                @if ($product->short_description)
                    <p class="product-detail-lead">{{ $product->short_description }}</p>
                @endif

                <div class="product-detail-prices">
                    <div><span>Selling price</span><strong>${{ number_format((float) $product->selling_price, 2) }}</strong></div>
                    <div><span>Regular price</span><strong class="product-regular-price">${{ number_format((float) $product->regular_price, 2) }}</strong></div>
                    @if ($product->discount_percentage)<span class="product-discount-label">{{ $product->discount_percentage }}% off</span>@endif
                </div>

                <div class="product-identifiers">
                    <div><span>SKU</span><strong>{{ $product->sku }}</strong></div>
                    <div><span>ISBN</span><strong>{{ $product->isbn ?: 'Not provided' }}</strong></div>
                </div>
            </div>
        </section>

        <aside class="product-detail-sidebar">
            <section class="product-detail-card">
                <div class="product-detail-card-heading"><h2>Stock overview</h2><x-icon name="inventory" class="h-5 w-5"/></div>
                <div class="product-stock-quantity"><strong>{{ number_format($product->stock_quantity) }}</strong><span>copies</span></div>
                <div class="product-stock-labels">
                    @if ($product->stock_quantity === 0)
                        <span class="status-pill stock-out">Out of stock</span>
                    @elseif ($product->isLowStock())
                        <span class="status-pill stock-low">Low stock</span>
                    @else
                        <span class="status-pill stock-in">In stock</span>
                    @endif
                    <span class="status-pill {{ $product->is_active ? 'status-active' : 'status-inactive' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="product-stock-alert"><span>Low stock alert</span><strong>{{ number_format($product->low_stock_alert) }} copies</strong></div>
            </section>

            <section class="product-detail-card product-record-card">
                <h2>Catalogue record</h2>
                <div class="product-record-row"><span>Added</span><strong>{{ $product->created_at->format('M j, Y') }}</strong></div>
                <div class="product-record-row"><span>Last updated</span><strong>{{ $product->updated_at->format('M j, Y') }}</strong></div>
            </section>
        </aside>
    </div>

    <div class="product-detail-info-grid">
        <section class="product-detail-card">
            <h2>Book description</h2>
            <p class="product-detail-copy">{{ $product->description ?: 'No full description has been added.' }}</p>
        </section>

        <section class="product-detail-card">
            <h2>Book details</h2>
            <dl class="product-detail-fields">
                <div><dt>Category</dt><dd>{{ $product->category->name }}</dd></div>
                <div><dt>Subcategory</dt><dd>{{ $product->subcategory?->name ?? 'Not assigned' }}</dd></div>
                <div><dt>Publisher</dt><dd>{{ $product->brand->name }}</dd></div>
                <div><dt>Language</dt><dd>{{ $product->language ?: 'Not specified' }}</dd></div>
                <div><dt>Edition</dt><dd>{{ $product->edition ?: 'Not specified' }}</dd></div>
                <div><dt>Publication date</dt><dd>{{ $product->published_at?->format('M j, Y') ?? 'Not specified' }}</dd></div>
                <div><dt>Pages</dt><dd>{{ $product->pages ?? 'Not specified' }}</dd></div>
                <div><dt>Format</dt><dd>{{ $product->format }}</dd></div>
                <div><dt>Weight</dt><dd>{{ $product->weight !== null ? number_format((float) $product->weight, 2).' g' : 'Not specified' }}</dd></div>
            </dl>
        </section>
    </div>

    @if ($product->images->isNotEmpty())
        <section class="product-detail-card product-gallery-panel">
            <h2>Additional images <span>{{ $product->images->count() }}</span></h2>
            <div class="product-gallery-grid">
                @foreach ($product->images as $image)
                    <figure class="product-gallery-item">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) }}" alt="{{ $image->alt_text ?: $product->title }}" loading="lazy">
                        <figcaption>{{ $image->alt_text ?: $product->title }}</figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif
@endsection
