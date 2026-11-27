@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">MAKE IT YOURS</p><h1>Settings</h1><p>Manage the branding shown in your admin workspace.</p></div>
    </div>
    <div class="settings-layout">
        <section class="settings-card">
            <h2>Admin branding</h2>
            <p>Update your workspace name, logo and browser icon.</p>
            @if ($errors->any())<div class="form-alert" role="alert">Please review the highlighted fields and try again.</div>@endif
            <form method="POST" action="{{ route('admin.branding.update') }}" enctype="multipart/form-data" data-loading>
                @csrf @method('PUT')
                <div class="field">
                    <label class="field-label" for="name">Admin name <span class="required-mark">*</span></label>
                    <input class="form-input" id="name" name="name" value="{{ old('name', $settings->name) }}" maxlength="100" required>
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field" style="margin-top: 16px">
                    <label class="field-label" for="tagline">Text below admin name</label>
                    <input class="form-input" id="tagline" name="tagline" value="{{ old('tagline', $settings->tagline) }}" maxlength="100">
                    <p class="field-hint">Leave blank to hide this text.</p>
                    @error('tagline')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="profile-photo-section">
                    <span class="brand-mark" data-image-preview-frame>
                        <span data-image-placeholder @if ($settings->logo_path) hidden @endif><x-icon name="book-open" class="h-6 w-6"/></span>
                        <img id="admin-logo-preview" class="brand-logo" src="{{ $settings->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->logo_path) : '' }}" data-original-src="{{ $settings->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->logo_path) : '' }}" alt="Admin logo preview" @if (! $settings->logo_path) hidden @endif>
                    </span>
                    <div class="profile-photo-controls">
                        <label class="field-label" for="logo">Admin logo</label>
                        <p class="field-hint">JPG, PNG or WebP, up to 2 MB.</p>
                        <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-image-preview="#admin-logo-preview" data-image-remove-input="#remove-admin-logo">
                        @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                        @if ($settings->logo_path)<label class="remove-avatar-label"><input id="remove-admin-logo" type="checkbox" name="remove_logo" value="1" data-image-remove="#admin-logo-preview" data-image-file="#logo"> Remove current logo</label>@endif
                    </div>
                </div>
                <div class="profile-photo-section">
                    <span class="brand-mark" data-image-preview-frame>
                        <span data-image-placeholder @if ($settings->favicon_path) hidden @endif><x-icon name="settings" class="h-6 w-6"/></span>
                        <img id="admin-favicon-preview" class="brand-logo" src="{{ $settings->favicon_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->favicon_path) : '' }}" data-original-src="{{ $settings->favicon_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->favicon_path) : '' }}" alt="Favicon preview" @if (! $settings->favicon_path) hidden @endif>
                    </span>
                    <div class="profile-photo-controls">
                        <label class="field-label" for="favicon">Favicon</label>
                        <p class="field-hint">ICO or PNG, up to 1 MB. A square image works best.</p>
                        <input id="favicon" name="favicon" type="file" accept=".ico,.png,image/x-icon,image/png" data-image-preview="#admin-favicon-preview" data-image-remove-input="#remove-admin-favicon">
                        @error('favicon')<p class="field-error">{{ $message }}</p>@enderror
                        @if ($settings->favicon_path)<label class="remove-avatar-label"><input id="remove-admin-favicon" type="checkbox" name="remove_favicon" value="1" data-image-remove="#admin-favicon-preview" data-image-file="#favicon"> Remove current favicon</label>@endif
                    </div>
                </div>
                <div class="form-actions"><button class="button button-primary" type="submit" data-loading-button>Save settings</button></div>
            </form>
        </section>
        <aside class="settings-card"><h2>Your workspace</h2><p>The name and logo appear in the admin sidebar and sign-in page. The favicon appears in the browser tab.</p></aside>
    </div>
@endsection
