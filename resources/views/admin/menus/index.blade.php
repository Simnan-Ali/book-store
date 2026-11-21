@extends('layouts.admin')

@section('title', 'Menu Master')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">FUTURE SITE NAVIGATION</p><h1>Menu Master</h1><p>Manage reusable menu links for the website from one place.</p></div>
        <a href="{{ route('admin.menus.create') }}" class="button button-primary"><x-icon name="plus" class="h-4 w-4"/> New menu</a>
    </div>

    <section class="panel filter-panel">
        <form method="GET" action="{{ route('admin.menus.index') }}" class="filter-form">
            <label class="filter-search"><x-icon name="search"/><input class="filter-input" type="search" name="q" value="{{ request('q') }}" placeholder="Search menus or URLs..." aria-label="Search menus or URLs"></label>
            <select class="filter-select" name="type" aria-label="Filter by menu level"><option value="">All menu levels</option><option value="parent" @selected(request('type') === 'parent')>Top-level</option><option value="child" @selected(request('type') === 'child')>Child menus</option></select>
            <select class="filter-select" name="status" aria-label="Filter by status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select>
            <button class="button button-outline filter-submit" type="submit"><x-icon name="filter" class="h-4 w-4"/> Filter</button>
            @if (request()->hasAny(['q', 'type', 'status']))<a class="filter-reset" href="{{ route('admin.menus.index') }}">Reset</a>@endif
        </form>
    </section>

    <section class="panel table-panel">
        @if ($menus->isEmpty())
            <div class="empty-state"><span class="empty-icon"><x-icon name="menu"/></span><strong>{{ request()->hasAny(['q', 'type', 'status']) ? 'No menus found' : 'Start building your menu structure' }}</strong><p>{{ request()->hasAny(['q', 'type', 'status']) ? 'Try another search or choose a different filter.' : 'Add top-level menus and child links for the future website.' }}</p>@if (! request()->hasAny(['q', 'type', 'status']))<a href="{{ route('admin.menus.create') }}" class="button button-primary button-small">Create menu</a>@endif</div>
        @else
            <div class="table-wrap"><table class="admin-table menu-table">
                <thead><tr><th>Menu</th><th>URL</th><th>Parent menu</th><th>Order</th><th>Opens in</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach ($menus as $menu)
                        <tr>
                            <td><span class="table-primary">{{ $menu->name }}</span><span class="table-secondary">{{ $menu->parent_id ? 'Child menu' : 'Top-level menu' }}@if ($menu->children_count) &middot; {{ $menu->children_count }} {{ \Illuminate\Support\Str::plural('child', $menu->children_count) }}@endif</span></td>
                            <td><span class="menu-url-value">{{ $menu->url ?: 'No URL — grouping menu' }}</span></td>
                            <td>{{ $menu->parent?->name ?? '—' }}</td>
                            <td>{{ $menu->sort_order }}</td>
                            <td>{{ $menu->open_in_new_tab ? 'New tab' : 'Same tab' }}</td>
                            <td><form method="POST" action="{{ route('admin.menus.status', $menu) }}">@csrf @method('PATCH')<button class="menu-status-button" type="submit" aria-label="{{ $menu->is_active ? 'Deactivate' : 'Activate' }} {{ $menu->name }}"><span class="status-pill {{ $menu->is_active ? 'status-active' : 'status-inactive' }}">{{ $menu->is_active ? 'Active' : 'Inactive' }}</span></button></form></td>
                            <td><div class="row-actions"><a class="row-action" href="{{ route('admin.menus.edit', $menu) }}" aria-label="Edit {{ $menu->name }}"><x-icon name="edit"/></a><form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" data-confirm="{{ $menu->children_count ? 'Remove or move the child menus before deleting this menu.' : 'This menu will be permanently deleted.' }}">@csrf @method('DELETE')<button class="row-action row-action--delete" type="submit" aria-label="Delete {{ $menu->name }}"><x-icon name="trash"/></button></form></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <div class="table-footer"><span>Showing {{ $menus->firstItem() }}&ndash;{{ $menus->lastItem() }} of {{ $menus->total() }} menus</span><div class="pagination">{{ $menus->onEachSide(1)->links('pagination::simple-tailwind') }}</div></div>
        @endif
    </section>
@endsection
