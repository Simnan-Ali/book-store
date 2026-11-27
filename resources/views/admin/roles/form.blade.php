@extends('layouts.admin')

@section('title', $role->exists ? 'Edit role' : 'New role')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">ACCESS CONTROL</p><h1>{{ $role->exists ? 'Edit role' : 'Create a role' }}</h1><p>Choose the admin capabilities this role should provide.</p></div>
        <a href="{{ route('admin.roles.index') }}" class="button button-muted">Back to roles</a>
    </div>

    @if ($errors->any())<div class="form-alert" role="alert">Please review the highlighted fields and try again.</div>@endif

    @php($checkedPermissions = old('permissions', $selectedPermissions))
    @if (! is_array($checkedPermissions)) @php($checkedPermissions = []) @endif

    <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" data-loading>
        @csrf
        @if ($role->exists) @method('PUT') @endif
        <div class="form-layout">
            <div class="panel form-panel">
                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">01</span><div><h2>Role details</h2><p>Give the role a clear name and purpose.</p></div></div>
                    <div class="form-grid">
                        <div class="field {{ $errors->has('display_name') ? 'field-has-error' : '' }}"><label class="field-label" for="display_name">Display name <span class="required-mark">*</span></label><input class="form-input" id="display_name" name="display_name" value="{{ old('display_name', $role->display_name) }}" maxlength="150" required>@error('display_name')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('name') ? 'field-has-error' : '' }}"><label class="field-label" for="name">Role key <span class="required-mark">*</span></label><input class="form-input" id="name" name="name" value="{{ old('name', $role->name) }}" maxlength="100" pattern="[a-z0-9]+([._-][a-z0-9]+)*" required placeholder="e.g. content-editor">@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field field--full {{ $errors->has('description') ? 'field-has-error' : '' }}"><label class="field-label" for="description">Description</label><textarea class="form-textarea" id="description" name="description" rows="3" maxlength="2000">{{ old('description', $role->description) }}</textarea>@error('description')<p class="field-error">{{ $message }}</p>@enderror</div>
                    </div>
                </section>
            </div>

            <aside class="form-sidebar">
                <section class="side-form-card"><h3>Role status</h3><p>Deactivating a role immediately disables its inherited permissions.</p><select class="form-select status-select" name="is_active" aria-label="Role status"><option value="1" @selected((string) old('is_active', (int) $role->is_active) === '1')>Active</option><option value="0" @selected((string) old('is_active', (int) $role->is_active) === '0')>Inactive</option></select>@error('is_active')<p class="field-error">{{ $message }}</p>@enderror</section>
                <section class="side-form-card"><h3>Security note</h3><p>Only a Super Admin can grant role, permission, or user-administration capabilities.</p></section>
            </aside>

            <div class="panel form-panel role-permissions-panel">
                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">02</span><div><h2>Permissions</h2><p>Select what this role can see and manage. Inactive roles cannot grant access.</p></div></div>
                    @error('permissions')<p class="field-error">{{ $message }}</p>@enderror
                    <div class="permission-groups">
                        @foreach ($permissionGroups as $module => $permissions)
                            @php($groupKey = 'permission-group-'.$loop->index)
                            <fieldset class="permission-group">
                                <legend>{{ $moduleLabels[$module] ?? \Illuminate\Support\Str::headline($module) }}</legend>
                                <div class="permission-group-actions"><button class="filter-reset" type="button" data-permissions-toggle="{{ $groupKey }}">Select all</button></div>
                                <div class="permission-options">
                                    @foreach ($permissions as $permission)
                                        <label class="permission-option">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" data-permission-group="{{ $groupKey }}" @checked(in_array($permission->name, $checkedPermissions, true))>
                                            <span><strong>{{ $permission->display_name ?: $permission->name }}</strong><small>{{ $permission->description }}</small></span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>
                    @error('permissions.*')<p class="field-error">{{ $message }}</p>@enderror
                </section>
                <div class="form-actions"><a class="button button-muted" href="{{ route('admin.roles.index') }}">Cancel</a><button class="button button-primary" type="submit" data-loading-button>{{ $role->exists ? 'Save changes' : 'Create role' }}</button></div>
            </div>

        </div>
    </form>
@endsection
