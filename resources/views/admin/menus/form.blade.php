@extends('layouts.admin')

@section('title', $menu->exists ? 'Edit menu' : 'New menu')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">WEBSITE STRUCTURE</p><h1>{{ $menu->exists ? 'Edit menu' : 'Create a menu' }}</h1><p>Keep navigation links organized for future website development.</p></div>
        <a href="{{ route('admin.menus.index') }}" class="button button-muted">Back to menus</a>
    </div>
    @if ($errors->any())<div class="form-alert" role="alert">Please review the highlighted fields and try again.</div>@endif
    <form method="POST" action="{{ $menu->exists ? route('admin.menus.update', $menu) : route('admin.menus.store') }}" data-loading>
        @csrf
        @if ($menu->exists) @method('PUT') @endif
        <div class="form-layout">
            <div class="panel form-panel">
                <section class="form-section"><div class="form-section-heading"><span class="form-section-number">01</span><div><h2>Menu details</h2><p>Choose a clear label and the destination for this menu item.</p></div></div>
                    <div class="form-grid">
                        <div class="field field--full {{ $errors->has('name') ? 'field-has-error' : '' }}"><label class="field-label" for="name">Menu name <span class="required-mark">*</span></label><input class="form-input" id="name" name="name" value="{{ old('name', $menu->name) }}" maxlength="255" placeholder="e.g. Books" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field field--full {{ $errors->has('url') ? 'field-has-error' : '' }}"><label class="field-label" for="url">Menu URL <span>(optional)</span></label><input class="form-input" id="url" name="url" type="text" inputmode="url" value="{{ old('url', $menu->url) }}" maxlength="2048" placeholder="/books or https://example.com">@error('url')<p class="field-error">{{ $message }}</p>@else<p class="field-hint">Use a site path or an HTTP/HTTPS address. Leave blank for a grouping menu.</p>@enderror</div>
                        <div class="field field--full {{ $errors->has('parent_id') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="parent_id">Parent menu <span>(optional)</span></label>
                            @if ($canChooseParent)
                                <select class="form-select" id="parent_id" name="parent_id"><option value="">Top-level menu</option>@foreach ($parentMenus as $parentMenu)<option value="{{ $parentMenu->id }}" @selected((string) old('parent_id', $menu->parent_id) === (string) $parentMenu->id)>{{ $parentMenu->name }}</option>@endforeach</select>
                                <p class="field-hint">Child menus can be assigned to top-level menus.</p>
                            @else
                                <input type="hidden" name="parent_id" value="">
                                <p class="field-hint">This top-level menu has children and must remain at the top level.</p>
                            @endif
                            @error('parent_id')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('sort_order') ? 'field-has-error' : '' }}"><label class="field-label" for="sort_order">Display order <span class="required-mark">*</span></label><input class="form-input" id="sort_order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $menu->sort_order) }}" required>@error('sort_order')<p class="field-error">{{ $message }}</p>@enderror</div>
                    </div>
                </section>
                <div class="form-actions"><a class="button button-muted" href="{{ route('admin.menus.index') }}">Cancel</a><button class="button button-primary" type="submit" data-loading-button>{{ $menu->exists ? 'Save changes' : 'Create menu' }}</button></div>
            </div>
            <aside class="form-sidebar">
                <section class="side-form-card"><h3>Visibility</h3><p>Inactive menus stay saved but can be excluded from a future website navigation.</p><div class="field {{ $errors->has('is_active') ? 'field-has-error' : '' }}"><label class="field-label" for="is_active">Menu status</label><select class="form-select status-select" id="is_active" name="is_active" required><option value="1" @selected((string) old('is_active', (int) $menu->is_active) === '1')>Active</option><option value="0" @selected((string) old('is_active', (int) $menu->is_active) === '0')>Inactive</option></select>@error('is_active')<p class="field-error">{{ $message }}</p>@enderror</div></section>
                <section class="side-form-card"><h3>Link behavior</h3><p>Choose whether a future website should open this destination in a new browser tab.</p><input type="hidden" name="open_in_new_tab" value="0"><label class="form-check"><input type="checkbox" name="open_in_new_tab" value="1" @checked((bool) old('open_in_new_tab', $menu->open_in_new_tab))><span class="form-check-copy">Open in a new tab<small>Applies when this menu is rendered as a link.</small></span></label>@error('open_in_new_tab')<p class="field-error">{{ $message }}</p>@enderror</section>
            </aside>
        </div>
    </form>
@endsection
