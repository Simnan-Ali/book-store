@extends('layouts.admin')

@section('title', 'My profile')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">YOUR ACCOUNT, YOUR WAY</p><h1>My profile</h1><p>Update your profile photo, account details and password.</p></div>
    </div>
    <div class="settings-layout">
        <section class="settings-card">
            <h2>Administrator profile</h2><p>These details are used when you sign in and when your actions appear in the activity feed.</p>
            @if ($errors->any())<div class="form-alert" role="alert">Please review the highlighted fields and try again.</div>@endif
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" data-loading>
                @csrf @method('PUT')
                <div class="profile-photo-section">
                    <x-user-avatar :user="$user" class="avatar avatar--large"/>
                    <div class="profile-photo-controls">
                        <input id="avatar" class="visually-hidden-file" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-image-preview="#avatar-preview">
                        <label class="field-label" for="avatar">Profile photo</label>
                        <p class="field-hint">JPG, PNG or WebP, up to 2 MB. This photo appears in the admin header.</p>
                        <label class="button button-outline profile-photo-button" for="avatar"><x-icon name="upload" class="h-4 w-4"/> Choose photo</label>
                        @error('avatar')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <img id="avatar-preview" class="profile-photo-preview" src="{{ $user->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) : '' }}" alt="New profile photo preview" @if (! $user->avatar_path) hidden @endif>
                    @if ($user->avatar_path)
                        <label class="remove-avatar-label"><input type="checkbox" name="remove_avatar" value="1"> <span>Remove current photo</span></label>
                    @endif
                </div>
                <div class="form-grid">
                    <div class="field {{ $errors->has('name') ? 'field-has-error' : '' }}"><label class="field-label" for="name">Name <span class="required-mark">*</span></label><input class="form-input" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="field {{ $errors->has('email') ? 'field-has-error' : '' }}"><label class="field-label" for="email">Email address <span class="required-mark">*</span></label><input class="form-input" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" required>@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                </div>
                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number"><x-icon name="settings" class="h-4 w-4"/></span><div><h2>Change password</h2><p>Leave these fields blank if you don't want to change your password.</p></div></div>
                    <div class="form-grid">
                        <div class="field field--full {{ $errors->has('current_password') ? 'field-has-error' : '' }}"><label class="field-label" for="current_password">Current password</label><input class="form-input" id="current_password" name="current_password" type="password" autocomplete="current-password">@error('current_password')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('password') ? 'field-has-error' : '' }}"><label class="field-label" for="password">New password</label><input class="form-input" id="password" name="password" type="password" autocomplete="new-password" minlength="12">@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field"><label class="field-label" for="password_confirmation">Confirm new password</label><input class="form-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"></div>
                    </div>
                </section>
                <div class="form-actions"><button class="button button-primary" type="submit" data-loading-button>Save settings</button></div>
            </form>
        </section>
        <aside class="settings-card"><h2>Account security</h2><p>A few small practices keep your bookstore workspace safe.</p><div class="security-note"><strong>Administrator-only access</strong>Only administrator accounts can access the private store dashboard. There is no public registration page.</div><div class="security-note" style="margin-top: 10px"><strong>Strong password</strong>Choose a unique password with at least 12 characters. Your password is stored as a secure hash.</div></aside>
    </div>
@endsection
