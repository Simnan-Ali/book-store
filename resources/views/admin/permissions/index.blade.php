@extends('layouts.admin')

@section('title', 'Permissions')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">ACCESS CONTROL</p><h1>Permissions</h1><p>Review the capabilities available to roles across the admin panel.</p></div>
        @can('permissions.create')<a href="{{ route('admin.permissions.create') }}" class="button button-primary"><x-icon name="plus" class="h-4 w-4"/> New permission</a>@endcan
    </div>

    <section class="panel filter-panel">
        <form method="GET" action="{{ route('admin.permissions.index') }}" class="filter-form">
            <label class="filter-search"><x-icon name="search"/><input class="filter-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search capabilities..." aria-label="Search permissions"></label>
            <select class="filter-select" name="module" aria-label="Filter by module"><option value="">All modules</option>@foreach ($modules as $module)<option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>{{ \Illuminate\Support\Str::headline($module) }}</option>@endforeach</select>
            <button class="button button-outline filter-submit" type="submit"><x-icon name="filter" class="h-4 w-4"/> Filter</button>
            @if ($filters)<a class="filter-reset" href="{{ route('admin.permissions.index') }}">Reset</a>@endif
        </form>
    </section>

    <section class="panel table-panel">
        @if ($permissions->isEmpty())
            <div class="empty-state"><span class="empty-icon"><x-icon name="key"/></span><strong>No permissions found</strong><p>Try changing the search or module filter.</p></div>
        @else
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Capability</th><th>Module</th><th>Assigned roles</th><th>Type</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($permissions as $permission)
                            <tr>
                                <td><span class="table-primary">{{ $permission->display_name ?: $permission->name }}</span><span class="table-secondary">{{ $permission->name }}</span></td>
                                <td>{{ \Illuminate\Support\Str::headline($permission->module ?: 'General') }}</td>
                                <td>{{ number_format($permission->roles_count) }} role(s) &middot; {{ number_format($permission->users_count) }} user(s)</td>
                                <td><span class="status-pill {{ $permission->is_system ? 'status-active' : 'status-inactive' }}">{{ $permission->is_system ? 'System' : 'Custom' }}</span></td>
                                <td><div class="row-actions">
                                    @if (! $permission->is_system && auth()->user()->can('permissions.edit'))
                                        <a class="row-action" href="{{ route('admin.permissions.edit', $permission) }}" aria-label="Edit {{ $permission->name }}"><x-icon name="edit"/></a>
                                    @endif
                                    @if (! $permission->is_system && $permission->roles_count === 0 && $permission->users_count === 0 && auth()->user()->can('permissions.delete'))
                                        <form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" data-confirm="Delete this unused permission?">@csrf @method('DELETE')<button class="row-action row-action--delete" type="submit" aria-label="Delete {{ $permission->name }}"><x-icon name="trash"/></button></form>
                                    @endif
                                </div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-footer"><span>Showing {{ $permissions->firstItem() }}&ndash;{{ $permissions->lastItem() }} of {{ $permissions->total() }} permissions</span><div class="pagination">{{ $permissions->onEachSide(1)->links('pagination::simple-tailwind') }}</div></div>
        @endif
    </section>
@endsection
