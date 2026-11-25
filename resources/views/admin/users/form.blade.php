@extends('layouts.admin')

@section('title', $user->exists ? 'Edit admin user' : 'New admin user')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">TEAM ACCESS</p><h1>{{ $user->exists ? 'Edit admin user' : 'Create an admin user' }}</h1><p>Set account details and choose at least one active role.</p></div>
        <a href="{{ route('admin.users.index') }}" class="button button-muted">Back to users</a>
    </div>

    @if ($errors->any())<div class="form-alert" role="alert">Please review the highlighted fields and try again.</div>@endif

    @php($checkedRoles = old('roles', $selectedRoles))
    @if (! is_array($checkedRoles)) @php($checkedRoles = []) @endif

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" data-loading>
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <div class="form-layout">
            <div class="panel form-panel">
                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">01</span><div><h2>Account details</h2><p>Each account must use a unique email address.</p></div></div>
                    <div class="form-grid">
                        <div class="field {{ $errors->has('name') ? 'field-has-error' : '' }}"><label class="field-label" for="name">Name <span class="required-mark">*</span></label><input class="form-input" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required autofocus>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('email') ? 'field-has-error' : '' }}"><label class="field-label" for="email">Email <span class="required-mark">*</span></label><input class="form-input" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" required>@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('password') ? 'field-has-error' : '' }}"><label class="field-label" for="password">Password @if (! $user->exists)<span class="required-mark">*</span>@else<span>(leave blank to keep current)</span>@endif</label><input class="form-input" id="password" name="password" type="password" minlength="12" autocomplete="new-password" @required(! $user->exists)>@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('password_confirmation') ? 'field-has-error' : '' }}"><label class="field-label" for="password_confirmation">Confirm password</label><input class="form-input" id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password">@error('password_confirmation')<p class="field-error">{{ $message }}</p>@enderror</div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">02</span><div><h2>Access roles</h2><p>Permissions come from the selected roles. Inactive roles are not assignable.</p></div></div>
                    <div class="permission-options">
                        @forelse ($roles as $role)
                            <label class="permission-option">
                                <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, $checkedRoles, true))>
                                <span><strong>{{ $role->display_name ?: $role->name }}@if ($role->is_protected) <span class="status-pill status-active">Protected</span>@endif</strong><small>{{ $role->description }}</small></span>
                            </label>
                        @empty
                            <p class="empty-note">No active roles are available to assign.</p>
                        @endforelse
                    </div>
                    @error('roles')<p class="field-error">{{ $message }}</p>@enderror
                    @error('roles.*')<p class="field-error">{{ $message }}</p>@enderror
                </section>
                <div class="form-actions"><a class="button button-muted" href="{{ route('admin.users.index') }}">Cancel</a><button class="button button-primary" type="submit" data-loading-button>{{ $user->exists ? 'Save changes' : 'Create user' }}</button></div>
            </div>
            <aside class="form-sidebar"><section class="side-form-card"><h3>Protected accounts</h3><p>Super Admin accounts and your own account cannot be changed or deleted from this screen.</p><p>Vendors with books must have their catalogue reassigned before their account can be deleted.</p></section></aside>
        </div>
    </form>
@endsection
