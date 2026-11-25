@extends('layouts.admin')

@section('title', $permission->exists ? 'Edit permission' : 'New permission')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">ACCESS CONTROL</p><h1>{{ $permission->exists ? 'Edit permission' : 'Create a permission' }}</h1><p>Register one capability that can be assigned to roles.</p></div>
        <a href="{{ route('admin.permissions.index') }}" class="button button-muted">Back to permissions</a>
    </div>

    @if ($errors->any())<div class="form-alert" role="alert">Please review the highlighted fields and try again.</div>@endif

    <form method="POST" action="{{ $permission->exists ? route('admin.permissions.update', $permission) : route('admin.permissions.store') }}" data-loading>
        @csrf
        @if ($permission->exists) @method('PUT') @endif
        <div class="form-layout">
            <div class="panel form-panel">
                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">01</span><div><h2>Capability details</h2><p>Use a module.action key, such as reports.export.</p></div></div>
                    <div class="form-grid">
                        <div class="field {{ $errors->has('display_name') ? 'field-has-error' : '' }}"><label class="field-label" for="display_name">Display name <span class="required-mark">*</span></label><input class="form-input" id="display_name" name="display_name" value="{{ old('display_name', $permission->display_name) }}" maxlength="150" required>@error('display_name')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('name') ? 'field-has-error' : '' }}"><label class="field-label" for="name">Permission key <span class="required-mark">*</span></label><input class="form-input" id="name" name="name" value="{{ old('name', $permission->name) }}" maxlength="150" pattern="[a-z0-9]+([._-][a-z0-9]+)*" required placeholder="e.g. reports.export">@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('module') ? 'field-has-error' : '' }}"><label class="field-label" for="module">Module <span class="required-mark">*</span></label><input class="form-input" id="module" name="module" value="{{ old('module', $permission->module) }}" maxlength="100" pattern="[a-z0-9]+(-[a-z0-9]+)*" required placeholder="e.g. reports">@error('module')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('description') ? 'field-has-error' : '' }}"><label class="field-label" for="description">Description</label><textarea class="form-textarea" id="description" name="description" rows="3" maxlength="2000">{{ old('description', $permission->description) }}</textarea>@error('description')<p class="field-error">{{ $message }}</p>@enderror</div>
                    </div>
                </section>
                <div class="form-actions"><a class="button button-muted" href="{{ route('admin.permissions.index') }}">Cancel</a><button class="button button-primary" type="submit" data-loading-button>{{ $permission->exists ? 'Save changes' : 'Create permission' }}</button></div>
            </div>
            <aside class="form-sidebar"><section class="side-form-card"><h3>Access safety</h3><p>Creating a permission does not grant access by itself. Assign it only to roles that need it.</p><p>Built-in system permissions cannot be edited or deleted.</p></section></aside>
        </div>
    </form>
@endsection
