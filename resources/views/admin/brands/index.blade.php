@extends('layouts.admin')

@section('title', 'Publishers')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">THE NAMES BEHIND THE BOOKS</p><h1>Publishers</h1><p>Keep your publisher directory tidy and connected to the catalogue.</p></div>
        <a href="{{ route('admin.brands.create') }}" class="button button-primary"><x-icon name="plus" class="h-4 w-4"/> New publisher</a>
    </div>
    <section class="panel filter-panel">
        <form method="GET" action="{{ route('admin.brands.index') }}" class="filter-form">
            <label class="filter-search"><x-icon name="search"/><input class="filter-input" type="search" name="q" value="{{ request('q') }}" placeholder="Search publishers..." aria-label="Search publishers"></label>
            <select class="filter-select" name="status" aria-label="Filter by status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select>
            <button class="button button-outline filter-submit" type="submit"><x-icon name="filter" class="h-4 w-4"/> Filter</button>
            @if (request()->hasAny(['q', 'status']))<a class="filter-reset" href="{{ route('admin.brands.index') }}">Reset</a>@endif
        </form>
    </section>
    <section class="panel table-panel">
        @if ($brands->isEmpty())
            <div class="empty-state"><span class="empty-icon"><x-icon name="publishers"/></span><strong>{{ request()->hasAny(['q', 'status']) ? 'No publishers found' : 'Add your first publisher' }}</strong><p>{{ request()->hasAny(['q', 'status']) ? 'Try adjusting your search or filters.' : 'Publishers bring the stories on your shelves to life.' }}</p>@if (! request()->hasAny(['q', 'status']))<a href="{{ route('admin.brands.create') }}" class="button button-primary button-small">Create publisher</a>@endif</div>
        @else
            <div class="table-wrap"><table class="admin-table">
                <thead><tr><th>Publisher</th><th>Contact</th><th>Books</th><th>Status</th><th>Added</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach ($brands as $brand)
                        <tr>
                            <td><div class="table-item"><span class="table-thumbnail">@if ($brand->logo)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($brand->logo) }}" alt="" loading="lazy">@else<x-icon name="publishers"/>@endif</span><span><span class="table-primary">{{ $brand->name }}</span><span class="table-secondary">/{{ $brand->slug }}</span></span></div></td>
                            <td>@if ($brand->email)<span class="table-primary">{{ $brand->email }}</span>@elseif ($brand->website_url)<span class="table-primary">{{ \Illuminate\Support\Str::limit($brand->website_url, 30) }}</span>@else<span class="table-secondary">No contact details</span>@endif</td>
                            <td>{{ number_format($brand->products_count) }}</td>
                            <td><span class="status-pill {{ $brand->is_active ? 'status-active' : 'status-inactive' }}">{{ $brand->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>{{ $brand->created_at->format('M j, Y') }}</td>
                            <td><div class="row-actions"><a class="row-action" href="{{ route('admin.brands.edit', $brand) }}" aria-label="Edit {{ $brand->name }}"><x-icon name="edit"/></a><form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" data-confirm="Publishers that are assigned to books cannot be deleted. Reassign books first.">@csrf @method('DELETE')<button class="row-action row-action--delete" type="submit" aria-label="Delete {{ $brand->name }}"><x-icon name="trash"/></button></form></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <div class="table-footer"><span>Showing {{ $brands->firstItem() }}&ndash;{{ $brands->lastItem() }} of {{ $brands->total() }} publishers</span><div class="pagination">{{ $brands->onEachSide(1)->links('pagination::simple-tailwind') }}</div></div>
        @endif
    </section>
@endsection
