@extends('layouts.admin')

@section('title', 'Roles')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">ACCESS CONTROL</p><h1>Roles</h1><p>Group permissions into roles and assign them to your team.</p></div>
        @can('roles.create')<a href="{{ route('admin.roles.create') }}" class="button button-primary"><x-icon name="plus" class="h-4 w-4"/> New role</a>@endcan
    </div>

    <section class="panel filter-panel">
        <form method="GET" action="{{ route('admin.roles.index') }}" class="filter-form">
            <label class="filter-search"><x-icon name="search"/><input class="filter-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search role names..." aria-label="Search roles"></label>
            <select class="filter-select" name="status" aria-label="Filter by status"><option value="">All statuses</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option></select>
            <button class="button button-outline filter-submit" type="submit"><x-icon name="filter" class="h-4 w-4"/> Filter</button>
            @if ($filters)<a class="filter-reset" href="{{ route('admin.roles.index') }}">Reset</a>@endif
        </form>
    </section>

    <section class="panel table-panel">
        @if ($roles->isEmpty())
            <div class="empty-state"><span class="empty-icon"><x-icon name="shield"/></span><strong>No roles found</strong><p>Try changing the search or status filter.</p></div>
        @else
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Role</th><th>Permissions</th><th>Users</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td><span class="table-primary">{{ $role->display_name ?: $role->name }}</span><span class="table-secondary">{{ $role->name }}@if ($role->is_protected) &middot; Protected @endif</span></td>
                                <td>{{ number_format($role->permissions_count) }}</td>
                                <td>{{ number_format($role->users_count) }}</td>
                                <td><span class="status-pill {{ $role->is_active ? 'status-active' : 'status-inactive' }}">{{ $role->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td><div class="row-actions">
                                    @if (! $role->is_protected && auth()->user()->can('roles.edit'))
                                        <a class="row-action" href="{{ route('admin.roles.edit', $role) }}" aria-label="Edit {{ $role->display_name }}"><x-icon name="edit"/></a>
                                    @endif
                                    @if (! $role->is_protected && $role->users_count === 0 && auth()->user()->can('roles.delete'))
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" data-confirm="Delete this role? This action cannot be undone.">@csrf @method('DELETE')<button class="row-action row-action--delete" type="submit" aria-label="Delete {{ $role->display_name }}"><x-icon name="trash"/></button></form>
                                    @endif
                                </div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-footer"><span>Showing {{ $roles->firstItem() }}&ndash;{{ $roles->lastItem() }} of {{ $roles->total() }} roles</span><div class="pagination">{{ $roles->onEachSide(1)->links('pagination::simple-tailwind') }}</div></div>
        @endif
    </section>
@endsection
