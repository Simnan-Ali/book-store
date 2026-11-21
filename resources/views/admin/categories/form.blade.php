@extends('layouts.admin')

@section('title', $category->exists ? 'Edit category' : 'New category')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">COLLECTION STRUCTURE</p><h1>{{ $category->exists ? 'Edit category' : 'Create a category' }}</h1><p>Give readers a clear path to the books they love.</p></div>
        <a href="{{ route('admin.categories.index') }}" class="button button-muted">Back to categories</a>
    </div>

    @if ($errors->any())<div class="form-alert" role="alert">Please review the highlighted fields and try again.</div>@endif

    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" enctype="multipart/form-data" data-loading>
        @csrf
        @if ($category->exists) @method('PUT') @endif
        <div class="form-layout">
            <div class="panel form-panel">
                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">01</span><div><h2>Category details</h2><p>The name and introduction shown throughout your catalogue.</p></div></div>
                    <div class="form-grid">
                        <div class="field field--full {{ $errors->has('name') ? 'field-has-error' : '' }}"><label class="field-label" for="name">Category name <span class="required-mark">*</span></label><input class="form-input" id="name" name="name" value="{{ old('name', $category->name) }}" maxlength="255" required autofocus placeholder="e.g. Contemporary fiction">@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field field--full {{ $errors->has('description') ? 'field-has-error' : '' }}"><label class="field-label" for="description">Description <span>(optional)</span></label><textarea class="form-textarea" id="description" name="description" rows="4" placeholder="A short introduction to this collection...">{{ old('description', $category->description) }}</textarea>@error('description')<p class="field-error">{{ $message }}</p>@enderror</div>
                    </div>
                </section>
                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">02</span><div><h2>Search appearance</h2><p>Optional metadata for search engines.</p></div></div>
                    <div class="form-grid">
                        <div class="field {{ $errors->has('meta_title') ? 'field-has-error' : '' }}"><label class="field-label" for="meta_title">Meta title</label><input class="form-input" id="meta_title" name="meta_title" value="{{ old('meta_title', $category->meta_title) }}" maxlength="255" placeholder="Search result title">@error('meta_title')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('meta_description') ? 'field-has-error' : '' }}"><label class="field-label" for="meta_description">Meta description</label><textarea class="form-textarea" id="meta_description" name="meta_description" rows="2" maxlength="1000" placeholder="A concise description for search results">{{ old('meta_description', $category->meta_description) }}</textarea>@error('meta_description')<p class="field-error">{{ $message }}</p>@enderror</div>
                    </div>
                </section>
                <div class="form-actions"><a class="button button-muted" href="{{ route('admin.categories.index') }}">Cancel</a><button class="button button-primary" type="submit" data-loading-button>{{ $category->exists ? 'Save changes' : 'Create category' }}</button></div>
            </div>
            <aside class="form-sidebar">
                <section class="side-form-card"><h3>Category image</h3><p>Use a landscape image to give this collection a visual identity.</p><label class="upload-drop" for="image"><x-icon name="upload"/><strong>Choose an image</strong><span>JPG, PNG or WebP &middot; up to 5 MB</span><input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-image-preview="#category-image-preview"></label>@error('image')<p class="field-error">{{ $message }}</p>@enderror<img id="category-image-preview" class="image-preview" src="{{ $category->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($category->image) : '' }}" alt="Category image preview" @if (! $category->image) hidden @endif></section>
                <section class="side-form-card"><h3>Visibility</h3><p>Inactive categories stay in your admin but are hidden from the storefront.</p><select class="form-select status-select" name="is_active" aria-label="Category status"><option value="1" @selected((string) old('is_active', (int) $category->is_active) === '1')>Active</option><option value="0" @selected((string) old('is_active', (int) $category->is_active) === '0')>Inactive</option></select>@error('is_active')<p class="field-error">{{ $message }}</p>@enderror</section>
            </aside>
        </div>
    </form>
@endsection
