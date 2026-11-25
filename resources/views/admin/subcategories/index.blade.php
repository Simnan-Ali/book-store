@extends('layouts.admin')

@section('title', 'Subcategories')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">A LITTLE MORE SPECIFIC</p><h1>Subcategories</h1><p>Give each category a useful layer of detail.</p></div>
        @can('subcategories.create')<a href="{{ route('admin.subcategories.create') }}" class="button button-primary"><x-icon name="plus" class="h-4 w-4"/> New subcategory</a>@endcan
    </div>

    <section class="panel filter-panel">
        <form method="GET" action="{{ route('admin.subcategories.index') }}" class="filter-form">
            <label class="filter-search"><x-icon name="search"/><input class="filter-input" type="search" name="q" value="{{ request('q') }}" placeholder="Search subcategories..." aria-label="Search subcategories"></label>
            <select class="filter-select" name="category_id" aria-label="Filter by category"><option value="">All categories</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>
            <select class="filter-select" name="status" aria-label="Filter by status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select>
            <button class="button button-outline filter-submit" type="submit"><x-icon name="filter" class="h-4 w-4"/> Filter</button>
            @if (request()->hasAny(['q', 'category_id', 'status']))<a class="filter-reset" href="{{ route('admin.subcategories.index') }}">Reset</a>@endif
        </form>
    </section>

    <section class="panel table-panel">
        @if ($subcategories->isEmpty())
            <div class="empty-state"><span class="empty-icon"><x-icon name="layers"/></span><strong>{{ request()->hasAny(['q', 'category_id', 'status']) ? 'No subcategories found' : 'Add a little more detail' }}</strong><p>{{ request()->hasAny(['q', 'category_id', 'status']) ? 'Try another search or choose a different filter.' : 'Create subcategories to make browsing your catalogue feel effortless.' }}</p>@if (! request()->hasAny(['q', 'category_id', 'status'])) @can('subcategories.create')<a href="{{ route('admin.subcategories.create') }}" class="button button-primary button-small">Create subcategory</a>@endcan @endif</div>
        @else
            <div class="table-wrap"><table class="admin-table">
                <thead><tr><th>Subcategory</th><th>Parent category</th><th>Books</th><th>Status</th><th>Created</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach ($subcategories as $subcategory)
                        <tr>
                            <td><div class="table-item"><span class="table-thumbnail">@if ($subcategory->image)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($subcategory->image) }}" alt="" loading="lazy">@else<x-icon name="book-open" data-image-placeholder/>@endif</span><span><span class="table-primary">{{ $subcategory->name }}</span><span class="table-secondary">/{{ $subcategory->slug }}</span></span></div></td>
                            <td><span class="table-primary">{{ $subcategory->category->name }}</span></td><td>{{ number_format($subcategory->products_count) }}</td>
                            <td><span class="status-pill {{ $subcategory->is_active ? 'status-active' : 'status-inactive' }}">{{ $subcategory->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>{{ $subcategory->created_at->format('M j, Y') }}</td>
                            <td><div class="row-actions">@can('subcategories.edit')<a class="row-action" href="{{ route('admin.subcategories.edit', $subcategory) }}" aria-label="Edit {{ $subcategory->name }}"><x-icon name="edit"/></a>@endcan @can('subcategories.delete')<form method="POST" action="{{ route('admin.subcategories.destroy', $subcategory) }}" data-confirm="Books assigned to this subcategory must be moved before it can be deleted.">@csrf @method('DELETE')<button class="row-action row-action--delete" type="submit" aria-label="Delete {{ $subcategory->name }}"><x-icon name="trash"/></button></form>@endcan</div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <div class="table-footer"><span>Showing {{ $subcategories->firstItem() }}&ndash;{{ $subcategories->lastItem() }} of {{ $subcategories->total() }} subcategories</span><div class="pagination">{{ $subcategories->onEachSide(1)->links('pagination::simple-tailwind') }}</div></div>
        @endif
    </section>
@endsection
