@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">YOUR COLLECTION, ORGANIZED</p><h1>Categories</h1><p>Group books into thoughtful collections that make discovery easier.</p></div>
        @can('categories.create')<a href="{{ route('admin.categories.create') }}" class="button button-primary"><x-icon name="plus" class="h-4 w-4"/> New category</a>@endcan
    </div>

    <section class="panel filter-panel">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="filter-form">
            <label class="filter-search"><x-icon name="search"/><input class="filter-input" type="search" name="q" value="{{ request('q') }}" placeholder="Search category names..." aria-label="Search categories"></label>
            <select class="filter-select" name="status" aria-label="Filter by status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select>
            <button class="button button-outline filter-submit" type="submit"><x-icon name="filter" class="h-4 w-4"/> Filter</button>
            @if (request()->hasAny(['q', 'status']))<a class="filter-reset" href="{{ route('admin.categories.index') }}">Reset</a>@endif
        </form>
    </section>

    <section class="panel table-panel">
        @if ($categories->isEmpty())
            <div class="empty-state"><span class="empty-icon"><x-icon name="categories"/></span><strong>{{ request()->hasAny(['q', 'status']) ? 'No categories found' : 'Start with a category' }}</strong><p>{{ request()->hasAny(['q', 'status']) ? 'Try adjusting your search or filters.' : 'Categories help readers find their next favorite book.' }}</p>@if (! request()->hasAny(['q', 'status'])) @can('categories.create')<a href="{{ route('admin.categories.create') }}" class="button button-primary button-small">Create category</a>@endcan @endif</div>
        @else
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Category</th><th>Books</th><th>Subcategories</th><th>Status</th><th>Created</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td><div class="table-item"><span class="table-thumbnail">@if ($category->image)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($category->image) }}" alt="" loading="lazy">@else<x-icon name="book-open" data-image-placeholder/>@endif</span><span><span class="table-primary">{{ $category->name }}</span><span class="table-secondary">/{{ $category->slug }}</span></span></div></td>
                                <td>{{ number_format($category->products_count) }}</td><td>{{ number_format($category->subcategories_count) }}</td>
                                <td><span class="status-pill {{ $category->is_active ? 'status-active' : 'status-inactive' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td>{{ $category->created_at->format('M j, Y') }}</td>
                                <td><div class="row-actions">@can('categories.edit')<a class="row-action" href="{{ route('admin.categories.edit', $category) }}" aria-label="Edit {{ $category->name }}"><x-icon name="edit"/></a>@endcan @can('categories.delete')<form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="This will move the category to trash. Categories with books or subcategories cannot be deleted.">@csrf @method('DELETE')<button class="row-action row-action--delete" type="submit" aria-label="Delete {{ $category->name }}"><x-icon name="trash"/></button></form>@endcan</div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-footer"><span>Showing {{ $categories->firstItem() }}&ndash;{{ $categories->lastItem() }} of {{ $categories->total() }} categories</span><div class="pagination">{{ $categories->onEachSide(1)->links('pagination::simple-tailwind') }}</div></div>
        @endif
    </section>
@endsection
