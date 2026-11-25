@extends('layouts.admin')

@section('title', 'Banner Management')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">FUTURE SITE CONTENT</p><h1>Banner Management</h1><p>Manage campaign artwork and content for your future storefront.</p></div>
        @can('banners.create')<a href="{{ route('admin.banners.create') }}" class="button button-primary"><x-icon name="plus" class="h-4 w-4"/> New banner</a>@endcan
    </div>

    <section class="panel filter-panel">
        <form method="GET" action="{{ route('admin.banners.index') }}" class="filter-form">
            <label class="filter-search"><x-icon name="search"/><input class="filter-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search title, name or slug..." aria-label="Search banners"></label>
            <select class="filter-select" name="type" aria-label="Filter by banner type"><option value="">All types</option><option value="main" @selected(($filters['type'] ?? '') === 'main')>Main banner</option><option value="promotional" @selected(($filters['type'] ?? '') === 'promotional')>Promotional banner</option><option value="other" @selected(($filters['type'] ?? '') === 'other')>Other</option></select>
            <select class="filter-select" name="status" aria-label="Filter by status"><option value="">All statuses</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option></select>
            <select class="filter-select" name="sort" aria-label="Sort banners"><option value="order_asc" @selected(($filters['sort'] ?? 'order_asc') === 'order_asc')>Order: low to high</option><option value="order_desc" @selected(($filters['sort'] ?? '') === 'order_desc')>Order: high to low</option><option value="latest" @selected(($filters['sort'] ?? '') === 'latest')>Recently added</option><option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest first</option><option value="title" @selected(($filters['sort'] ?? '') === 'title')>Title: A to Z</option></select>
            <button class="button button-outline filter-submit" type="submit"><x-icon name="filter" class="h-4 w-4"/> Apply</button>
            @if (request()->hasAny(['q', 'type', 'status', 'sort']))<a class="filter-reset" href="{{ route('admin.banners.index') }}">Reset</a>@endif
        </form>
    </section>

    <section class="panel table-panel">
        @if ($banners->isEmpty())
            <div class="empty-state"><span class="empty-icon"><x-icon name="banner"/></span><strong>{{ request()->hasAny(['q', 'type', 'status']) ? 'No banners found' : 'Give your future storefront a little life' }}</strong><p>{{ request()->hasAny(['q', 'type', 'status']) ? 'Try another search or choose a different filter.' : 'Create a banner to manage campaign imagery and content for your future website.' }}</p>@if (! request()->hasAny(['q', 'type', 'status'])) @can('banners.create')<a href="{{ route('admin.banners.create') }}" class="button button-primary button-small">Create your first banner</a>@endcan @endif</div>
        @else
            <div class="table-wrap"><table class="admin-table banner-table">
                <thead><tr><th>Banner</th><th>Type</th><th>Display order</th><th>Gallery</th><th>Status</th><th>Dates</th><th>Created</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach ($banners as $banner)
                        <tr>
                            <td><div class="table-item"><span class="table-thumbnail banner-thumbnail">@if ($banner->main_image)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($banner->main_image) }}" alt="{{ $banner->image_alt ?: $banner->title }}" loading="lazy">@else<x-icon name="banner"/>@endif</span><span><span class="table-primary">{{ $banner->title }}</span><span class="table-secondary">{{ $banner->name }} · /{{ $banner->slug }}</span></span></div></td>
                            <td><span class="banner-type-pill banner-type-{{ $banner->type }}">{{ ['main' => 'Main banner', 'promotional' => 'Promotional', 'other' => 'Other'][$banner->type] }}</span></td>
                            <td><span class="banner-order">{{ $banner->sort_order }}</span></td>
                            <td>{{ number_format($banner->images_count) }} {{ \Illuminate\Support\Str::plural('image', $banner->images_count) }}</td>
                            <td>@can('banners.edit')<form method="POST" action="{{ route('admin.banners.status', $banner) }}">@csrf @method('PATCH')<button class="menu-status-button" type="submit" aria-label="{{ $banner->status === 'active' ? 'Deactivate' : 'Activate' }} {{ $banner->title }}"><span class="status-pill {{ $banner->status === 'active' ? 'status-active' : 'status-inactive' }}">{{ ucfirst($banner->status) }}</span></button></form>@else<span class="status-pill {{ $banner->status === 'active' ? 'status-active' : 'status-inactive' }}">{{ ucfirst($banner->status) }}</span>@endcan</td>
                            <td><span class="table-primary">{{ $banner->start_date?->format('M j, Y') ?? 'No start date' }}</span><span class="table-secondary">{{ $banner->end_date?->format('M j, Y') ?? 'No end date' }}</span></td>
                            <td>{{ $banner->created_at->format('M j, Y') }}</td>
                            <td><div class="row-actions">@can('banners.edit')<a class="row-action" href="{{ route('admin.banners.edit', $banner) }}" aria-label="Edit {{ $banner->title }}"><x-icon name="edit"/></a>@endcan @can('banners.delete')<form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" data-confirm="This will soft-delete the banner and remove all of its uploaded images.">@csrf @method('DELETE')<button class="row-action row-action--delete" type="submit" aria-label="Delete {{ $banner->title }}"><x-icon name="trash"/></button></form>@endcan</div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <div class="table-footer"><span>Showing {{ $banners->firstItem() }}&ndash;{{ $banners->lastItem() }} of {{ $banners->total() }} banners</span><div class="pagination">{{ $banners->onEachSide(1)->links('pagination::simple-tailwind') }}</div></div>
        @endif
    </section>
@endsection
