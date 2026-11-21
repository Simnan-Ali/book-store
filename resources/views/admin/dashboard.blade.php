@extends('layouts.admin')

@section('title', 'Overview')

@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">YOUR STORE AT A GLANCE</p><h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }} <span class="heading-sparkle">*</span></h1><p class="page-subtitle">Here's what's happening across your bookstore today.</p></div>
        <a href="{{ route('admin.products.create') }}" class="button button-primary"><x-icon name="plus" class="h-4 w-4"/> Add a book</a>
    </div>

    <section class="stats-grid" aria-label="Store statistics">
        <article class="stat-card stat-card--accent">
            <div class="stat-card-top"><span class="stat-icon stat-icon--warm"><x-icon name="books"/></span><span class="stat-kicker">CATALOGUE</span></div>
            <div class="stat-value">{{ number_format($stats['books']) }}</div><div class="stat-label">Total books</div>
            <a href="{{ route('admin.products.index') }}" class="stat-link">View catalogue <x-icon name="arrow" class="h-3 w-3"/></a>
        </article>
        <article class="stat-card">
            <div class="stat-card-top"><span class="stat-icon stat-icon--lavender"><x-icon name="categories"/></span><span class="stat-kicker">ORGANIZATION</span></div>
            <div class="stat-value">{{ number_format($stats['categories']) }}</div><div class="stat-label">Categories</div>
            <div class="stat-caption">{{ number_format($stats['subcategories']) }} subcategories</div>
        </article>
        <article class="stat-card">
            <div class="stat-card-top"><span class="stat-icon stat-icon--sage"><x-icon name="publishers"/></span><span class="stat-kicker">IMPRINTS</span></div>
            <div class="stat-value">{{ number_format($stats['brands']) }}</div><div class="stat-label">Publishers</div>
            <a href="{{ route('admin.brands.index') }}" class="stat-link">Manage publishers <x-icon name="arrow" class="h-3 w-3"/></a>
        </article>
        <article class="stat-card">
            <div class="stat-card-top"><span class="stat-icon stat-icon--blue"><x-icon name="trend"/></span><span class="stat-kicker">AVAILABILITY</span></div>
            <div class="stat-value">{{ number_format($stats['active']) }}</div><div class="stat-label">Active titles</div>
            <div class="stat-caption">{{ number_format($stats['outOfStock']) }} currently out of stock</div>
        </article>
    </section>

    <section class="inventory-banner">
        <div class="inventory-banner-copy">
            <span class="banner-icon"><x-icon name="inventory" class="h-5 w-5"/></span>
            <div><p class="eyebrow">INVENTORY PULSE</p><h2>{{ $stats['lowStock'] + $stats['outOfStock'] }} {{ \Illuminate\Support\Str::plural('title', $stats['lowStock'] + $stats['outOfStock']) }} need your attention</h2><p>{{ $stats['lowStock'] }} running low &middot; {{ $stats['outOfStock'] }} out of stock</p></div>
        </div>
        <a href="{{ route('admin.inventory') }}" class="button button-outline">Review inventory <x-icon name="arrow" class="h-4 w-4"/></a>
    </section>

    <div class="dashboard-grid">
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">RECENTLY ADDED</p><h2>Fresh on the shelves</h2></div><a class="text-link" href="{{ route('admin.products.index') }}">All books <x-icon name="arrow" class="h-3 w-3"/></a></div>
            @if ($recentProducts->isEmpty())
                <div class="empty-state empty-state--compact"><span class="empty-icon"><x-icon name="book-open" class="h-5 w-5"/></span><strong>Your shelves are waiting.</strong><p>Add your first book to start building your catalogue.</p><a class="text-link" href="{{ route('admin.products.create') }}">Add your first book <x-icon name="arrow" class="h-3 w-3"/></a></div>
            @else
                <div class="recent-list">
                    @foreach ($recentProducts as $product)
                        <a href="{{ route('admin.products.edit', $product) }}" class="recent-book">
                            <span class="book-cover-thumb">
                                @if ($product->main_image)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->main_image) }}" alt="" loading="lazy">@else<x-icon name="book-open" class="h-5 w-5"/>@endif
                            </span>
                            <span class="recent-book-info"><strong>{{ $product->title }}</strong><small>{{ $product->author }} &middot; {{ $product->brand?->name ?? 'No publisher' }}</small></span>
                            <span class="recent-book-price">${{ number_format((float) $product->selling_price, 2) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">CATALOGUE MIX</p><h2>Books by category</h2></div><x-icon name="categories" class="panel-heading-icon"/></div>
            @if ($categorySummary->isEmpty())
                <div class="empty-state empty-state--compact"><strong>No categories yet</strong><p>Create categories to see how your collection is organized.</p><a class="text-link" href="{{ route('admin.categories.create') }}">Create a category <x-icon name="arrow" class="h-3 w-3"/></a></div>
            @else
                @php($maxCategoryCount = max(1, (int) $categorySummary->max('products_count')))
                <div class="category-bars">
                    @foreach ($categorySummary as $category)
                        <div class="category-bar-row"><div class="category-bar-label"><span>{{ $category->name }}</span><strong>{{ $category->products_count }}</strong></div><div class="bar-track"><span style="width: {{ round($category->products_count / $maxCategoryCount * 100) }}%"></span></div></div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <section class="panel activity-panel">
        <div class="panel-heading"><div><p class="eyebrow">THE PAPER TRAIL</p><h2>Recent activity</h2></div><span class="activity-live"><i></i> LIVE</span></div>
        @if ($activities->isEmpty())
            <div class="empty-state empty-state--compact"><span class="empty-icon"><x-icon name="clock" class="h-5 w-5"/></span><strong>A fresh start.</strong><p>Your recent changes will appear here as you manage your store.</p></div>
        @else
            <div class="activity-list">
                @foreach ($activities as $activity)
                    <div class="activity-row"><span @class(['activity-dot', 'activity-dot--created' => $activity->action === 'created', 'activity-dot--deleted' => $activity->action === 'deleted', 'activity-dot--updated' => $activity->action === 'updated'])></span><span class="activity-copy"><strong>{{ $activity->user?->name ?? 'Administrator' }}</strong> {{ $activity->description }}</span><time>{{ $activity->created_at->diffForHumans() }}</time></div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
