@extends('layouts.admin')

@section('title', $subcategory->exists ? 'Edit subcategory' : 'New subcategory')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">COLLECTION STRUCTURE</p><h1>{{ $subcategory->exists ? 'Edit subcategory' : 'Create a subcategory' }}</h1><p>Every subcategory belongs to a parent category.</p></div>
        <a href="{{ route('admin.subcategories.index') }}" class="button button-muted">Back to subcategories</a>
    </div>
    @if ($errors->any())<div class="form-alert" role="alert">Please review the highlighted fields and try again.</div>@endif
    <form method="POST" action="{{ $subcategory->exists ? route('admin.subcategories.update', $subcategory) : route('admin.subcategories.store') }}" enctype="multipart/form-data" data-loading>
        @csrf
        @if ($subcategory->exists) @method('PUT') @endif
        <div class="form-layout">
            <div class="panel form-panel">
                <section class="form-section"><div class="form-section-heading"><span class="form-section-number">01</span><div><h2>Subcategory details</h2><p>Choose the parent collection and give this group a name.</p></div></div>
                    <div class="form-grid">
                        <div class="field field--full {{ $errors->has('category_id') ? 'field-has-error' : '' }}"><label class="field-label" for="category_id">Parent category <span class="required-mark">*</span></label><select class="form-select" id="category_id" name="category_id" required><option value="">Choose a category</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $subcategory->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>@error('category_id')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field field--full {{ $errors->has('name') ? 'field-has-error' : '' }}"><label class="field-label" for="name">Subcategory name <span class="required-mark">*</span></label><input class="form-input" id="name" name="name" value="{{ old('name', $subcategory->name) }}" maxlength="255" placeholder="e.g. Historical fiction" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field field--full {{ $errors->has('description') ? 'field-has-error' : '' }}"><label class="field-label" for="description">Description <span>(optional)</span></label><textarea class="form-textarea" id="description" name="description" rows="5" placeholder="A short introduction to this subcategory...">{{ old('description', $subcategory->description) }}</textarea>@error('description')<p class="field-error">{{ $message }}</p>@enderror</div>
                    </div>
                </section>
                <div class="form-actions"><a class="button button-muted" href="{{ route('admin.subcategories.index') }}">Cancel</a><button class="button button-primary" type="submit" data-loading-button>{{ $subcategory->exists ? 'Save changes' : 'Create subcategory' }}</button></div>
            </div>
            <aside class="form-sidebar">
                <section class="side-form-card"><h3>Subcategory image</h3><p>Optional image to help distinguish this collection.</p><label class="upload-drop" for="image"><x-icon name="upload"/><strong>Choose an image</strong><span>JPG, PNG or WebP &middot; up to 5 MB</span><input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-image-preview="#subcategory-image-preview"></label>@error('image')<p class="field-error">{{ $message }}</p>@enderror<img id="subcategory-image-preview" class="image-preview" src="{{ $subcategory->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($subcategory->image) : '' }}" alt="Subcategory image preview" @if (! $subcategory->image) hidden @endif></section>
                <section class="side-form-card"><h3>Visibility</h3><p>Inactive subcategories remain here but are hidden from your storefront.</p><select class="form-select status-select" name="is_active" aria-label="Subcategory status"><option value="1" @selected((string) old('is_active', (int) $subcategory->is_active) === '1')>Active</option><option value="0" @selected((string) old('is_active', (int) $subcategory->is_active) === '0')>Inactive</option></select>@error('is_active')<p class="field-error">{{ $message }}</p>@enderror</section>
            </aside>
        </div>
    </form>
@endsection
