@extends('layouts.admin')

@section('title', $brand->exists ? 'Edit publisher' : 'New publisher')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">PUBLISHER DIRECTORY</p><h1>{{ $brand->exists ? 'Edit publisher' : 'Add a publisher' }}</h1><p>Capture the details your team needs to keep books connected.</p></div>
        <a href="{{ route('admin.brands.index') }}" class="button button-muted">Back to publishers</a>
    </div>
    @if ($errors->any())<div class="form-alert" role="alert">Please review the highlighted fields and try again.</div>@endif
    <form method="POST" action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" enctype="multipart/form-data" data-loading>
        @csrf
        @if ($brand->exists) @method('PUT') @endif
        <div class="form-layout">
            <div class="panel form-panel">
                <section class="form-section"><div class="form-section-heading"><span class="form-section-number">01</span><div><h2>Publisher details</h2><p>The publisher identity and preferred contact information.</p></div></div>
                    <div class="form-grid">
                        <div class="field field--full {{ $errors->has('name') ? 'field-has-error' : '' }}"><label class="field-label" for="name">Publisher name <span class="required-mark">*</span></label><input class="form-input" id="name" name="name" value="{{ old('name', $brand->name) }}" maxlength="255" placeholder="e.g. Northstar Press" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('website_url') ? 'field-has-error' : '' }}"><label class="field-label" for="website_url">Website URL</label><input class="form-input" id="website_url" name="website_url" type="url" value="{{ old('website_url', $brand->website_url) }}" maxlength="255" placeholder="https://publisher.com">@error('website_url')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('email') ? 'field-has-error' : '' }}"><label class="field-label" for="email">Email address</label><input class="form-input" id="email" name="email" type="email" value="{{ old('email', $brand->email) }}" maxlength="255" placeholder="hello@publisher.com">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('phone') ? 'field-has-error' : '' }}"><label class="field-label" for="phone">Phone number</label><input class="form-input" id="phone" name="phone" type="tel" value="{{ old('phone', $brand->phone) }}" maxlength="40" placeholder="+1 (555) 000-0000">@error('phone')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('address') ? 'field-has-error' : '' }}"><label class="field-label" for="address">Address</label><textarea class="form-textarea" id="address" name="address" rows="2" maxlength="1000" placeholder="Street address, city, postal code">{{ old('address', $brand->address) }}</textarea>@error('address')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field field--full {{ $errors->has('description') ? 'field-has-error' : '' }}"><label class="field-label" for="description">Description <span>(optional)</span></label><textarea class="form-textarea" id="description" name="description" rows="4" placeholder="A little about this publisher...">{{ old('description', $brand->description) }}</textarea>@error('description')<p class="field-error">{{ $message }}</p>@enderror</div>
                    </div>
                </section>
                <div class="form-actions"><a class="button button-muted" href="{{ route('admin.brands.index') }}">Cancel</a><button class="button button-primary" type="submit" data-loading-button>{{ $brand->exists ? 'Save changes' : 'Add publisher' }}</button></div>
            </div>
            <aside class="form-sidebar">
                <section class="side-form-card"><h3>Publisher logo</h3><p>Upload a clear logo that helps your team identify the imprint.</p><label class="upload-drop" for="logo"><x-icon name="upload"/><strong>Choose a logo</strong><span>JPG, PNG or WebP &middot; up to 5 MB</span><input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-image-preview="#brand-logo-preview"></label>@error('logo')<p class="field-error">{{ $message }}</p>@enderror<img id="brand-logo-preview" class="image-preview" src="{{ $brand->logo ? \Illuminate\Support\Facades\Storage::disk('public')->url($brand->logo) : '' }}" alt="Publisher logo preview" @if (! $brand->logo) hidden @endif></section>
                <section class="side-form-card"><h3>Visibility</h3><p>Inactive publishers remain available in the admin but can be hidden from your store.</p><select class="form-select status-select" name="is_active" aria-label="Publisher status"><option value="1" @selected((string) old('is_active', (int) $brand->is_active) === '1')>Active</option><option value="0" @selected((string) old('is_active', (int) $brand->is_active) === '0')>Inactive</option></select>@error('is_active')<p class="field-error">{{ $message }}</p>@enderror</section>
            </aside>
        </div>
    </form>
@endsection
