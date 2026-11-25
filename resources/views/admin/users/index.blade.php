@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">TEAM ACCESS</p><h1>Admin users</h1><p>Manage accounts and assign active access roles.</p></div>
        @can('users.create')<a href="{{ route('admin.users.create') }}" class="button button-primary"><x-icon name="plus" class="h-4 w-4"/> New user</a>@endcan
    </div>

    <section class="panel filter-panel">
        <form method="GET" action="{{ route('admin.users.index') }}" class="filter-form">
            <label class="filter-search"><x-icon name="search"/><input class="filter-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name or email..." aria-label="Search users"></label>
            <button class="button button-outline filter-submit" type="submit"><x-icon name="filter" class="h-4 w-4"/> Search</button>
            @if ($filters)<a class="filter-reset" href="{{ route('admin.users.index') }}">Reset</a>@endif
        </form>
    </section>

    <section class="panel table-panel">
        @if ($users->isEmpty())
            <div class="empty-state"><span class="empty-icon"><x-icon name="users"/></span><strong>No users found</strong><p>Try a different search term.</p></div>
        @else
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>User</th><th>Assigned roles</th><th>Created</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($users as $user)
                            @php($protected = $user->is_admin || $user->roles->contains(fn ($role) => $role->is_protected))
                            <tr>
                                <td><span class="table-primary">{{ $user->name }}@if ($user->is(auth()->user())) <span class="status-pill status-active">You</span>@endif</span><span class="table-secondary">{{ $user->email }}@if ($protected) &middot; Protected administrator @endif</span></td>
                                <td><div class="role-badges">@forelse ($user->roles as $role)<span class="status-pill {{ $role->is_active ? 'status-active' : 'status-inactive' }}">{{ $role->display_name ?: $role->name }}</span>@empty @if ($user->is_admin)<span class="status-pill status-active">Legacy admin</span>@else<span class="table-secondary">No roles</span>@endif @endforelse</div></td>
                                <td>{{ $user->created_at?->format('M j, Y') }}</td>
                                <td><div class="row-actions">
                                    @if (! $user->is(auth()->user()) && ! $protected && auth()->user()->can('users.edit'))
                                        <a class="row-action" href="{{ route('admin.users.edit', $user) }}" aria-label="Edit {{ $user->name }}"><x-icon name="edit"/></a>
                                    @endif
                                    @if (! $user->is(auth()->user()) && ! $protected && auth()->user()->can('users.delete'))
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Delete this user account? This cannot be undone.">@csrf @method('DELETE')<button class="row-action row-action--delete" type="submit" aria-label="Delete {{ $user->name }}"><x-icon name="trash"/></button></form>
                                    @endif
                                </div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-footer"><span>Showing {{ $users->firstItem() }}&ndash;{{ $users->lastItem() }} of {{ $users->total() }} users</span><div class="pagination">{{ $users->onEachSide(1)->links('pagination::simple-tailwind') }}</div></div>
        @endif
    </section>
@endsection
