@extends('layouts.admin')

@section('title', $banner->exists ? 'Edit banner' : 'New banner')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">FUTURE SITE CONTENT</p><h1>{{ $banner->exists ? 'Edit banner' : 'Create a banner' }}</h1><p>Manage artwork, campaign content and display settings for your future storefront.</p></div>
        <a href="{{ route('admin.banners.index') }}" class="button button-muted">Back to banners</a>
    </div>
    @if ($errors->any())<div class="form-alert" role="alert">Please review the highlighted fields and try again.</div>@endif

    <form method="POST" action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" enctype="multipart/form-data" data-loading>
        @csrf
        @if ($banner->exists) @method('PUT') @endif
        <div class="form-layout banner-form-layout">
            <div class="panel form-panel">
                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">01</span><div><h2>Banner details</h2><p>Give this placement a clear name so it's easy to find later.</p></div></div>
                    <div class="form-grid">
                        <div class="field {{ $errors->has('title') ? 'field-has-error' : '' }}"><label class="field-label" for="title">Banner title <span class="required-mark">*</span></label><input class="form-input" id="title" name="title" value="{{ old('title', $banner->title) }}" maxlength="255" placeholder="e.g. Summer reading collection" required autofocus>@error('title')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('name') ? 'field-has-error' : '' }}"><label class="field-label" for="name">Banner name <span class="required-mark">*</span></label><input class="form-input" id="name" name="name" value="{{ old('name', $banner->name) }}" maxlength="255" placeholder="e.g. summer-reading" data-slug-source="#slug" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <input id="slug" name="slug" type="hidden" value="{{ $banner->slug }}">
                        <input id="type" name="type" type="hidden" value="{{ $banner->type ?: 'main' }}">
                        <div class="field field--full {{ $errors->has('description') ? 'field-has-error' : '' }}"><label class="field-label" for="description">Banner description <span>(optional)</span></label><textarea class="form-textarea" id="description" name="description" rows="3" placeholder="A short campaign description for your team...">{{ old('description', $banner->description) }}</textarea>@error('description')<p class="field-error">{{ $message }}</p>@enderror</div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">02</span><div><h2>Main banner image</h2><p>Choose the primary artwork used for this banner.</p></div></div>
                    <div class="banner-main-upload">
                        <label class="upload-drop banner-dropzone" for="main_image" data-dropzone><x-icon name="upload"/><strong>{{ $banner->main_image ? 'Replace the main image' : 'Choose the main image' }}</strong><span>JPG, JPEG, PNG or WebP · up to 5 MB · Drop an image here</span><input id="main_image" name="main_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-image-preview="#banner-main-preview" data-image-remove-input="[name=remove_main_image]" @if (! $banner->exists) required @endif></label>
                        @error('main_image')<p class="field-error">{{ $message }}</p>@enderror
                        <img id="banner-main-preview" class="banner-main-preview" src="{{ $banner->main_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($banner->main_image) : '' }}" data-original-src="{{ $banner->main_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($banner->main_image) : '' }}" alt="{{ $banner->image_alt ?: 'Banner image preview' }}" @if (! $banner->main_image || old('remove_main_image')) hidden @endif>
                    </div>
                    <div class="form-grid banner-image-meta">
                        <input id="image_alt" name="image_alt" type="hidden" value="{{ $banner->image_alt }}">
                        @if ($banner->exists && $banner->main_image)
                            <label class="remove-main-image"><input type="checkbox" name="remove_main_image" value="1" data-image-remove="#banner-main-preview" data-image-file="#main_image" @checked((bool) old('remove_main_image'))><span>Remove current main image</span></label>
                        @endif
                    </div>
                </section>

                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">03</span><div><h2>Banner content</h2><p>Content is optional; image-only banners are supported.</p></div></div>
                    <div class="form-grid">
                        <div class="field {{ $errors->has('heading') ? 'field-has-error' : '' }}"><label class="field-label" for="heading">Main heading</label><input class="form-input" id="heading" name="heading" value="{{ old('heading', $banner->heading) }}" maxlength="255" placeholder="A headline for your campaign">@error('heading')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('sub_heading') ? 'field-has-error' : '' }}"><label class="field-label" for="sub_heading">Sub heading</label><input class="form-input" id="sub_heading" name="sub_heading" value="{{ old('sub_heading', $banner->sub_heading) }}" maxlength="255" placeholder="A supporting line">@error('sub_heading')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field field--full"><p class="banner-content-hint">Banner description is managed in the details section above. Leave the heading, subheading and button empty for an image-only banner.</p></div>
                        <div class="field {{ $errors->has('button_text') ? 'field-has-error' : '' }}"><label class="field-label" for="button_text">Button text</label><input class="form-input" id="button_text" name="button_text" value="{{ old('button_text', $banner->button_text) }}" maxlength="100" placeholder="e.g. Explore the collection">@error('button_text')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="field {{ $errors->has('button_url') ? 'field-has-error' : '' }}"><label class="field-label" for="button_url">Button URL</label><input class="form-input" id="button_url" name="button_url" value="{{ old('button_url', $banner->button_url) }}" maxlength="2048" placeholder="/collections/summer or https://example.com">@error('button_url')<p class="field-error">{{ $message }}</p>@enderror</div>
                    </div>
                    <input type="hidden" name="show_button" value="{{ $banner->show_button ? 1 : 0 }}">
                    <input type="hidden" name="open_new_tab" value="{{ $banner->open_new_tab ? 1 : 0 }}">
                </section>

                @unless ($banner->exists)
                    <section class="form-section">
                        <div class="form-section-heading"><span class="form-section-number">04</span><div><h2>Additional images</h2><p>Optional gallery images can be uploaded alongside the banner.</p></div></div>
                        <label class="upload-drop banner-dropzone" for="gallery_images" data-dropzone><x-icon name="media"/><strong>Choose gallery images</strong><span>Up to 10 images · JPG, PNG or WebP · Drop files here</span><input id="gallery_images" name="gallery_images[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple data-image-gallery="#banner-create-previews"></label>
                        @error('gallery_images')<p class="field-error">{{ $message }}</p>@enderror
                        @error('gallery_images.*')<p class="field-error">{{ $message }}</p>@enderror
                        <div id="banner-create-previews" class="banner-upload-previews"></div>
                    </section>
                @endunless

                <div class="form-actions"><a class="button button-muted" href="{{ route('admin.banners.index') }}">Cancel</a><button class="button button-primary" type="submit" data-loading-button>{{ $banner->exists ? 'Save banner' : 'Create banner' }}</button></div>
            </div>

            <aside class="form-sidebar">
                <section class="side-form-card"><h3>Display settings</h3><p>Set the banner's status and display order.</p>
                    <div class="field {{ $errors->has('status') ? 'field-has-error' : '' }}"><label class="field-label" for="status">Status</label><select class="form-select status-select" id="status" name="status" required><option value="active" @selected(old('status', $banner->status) === 'active')>Active</option><option value="inactive" @selected(old('status', $banner->status) === 'inactive')>Inactive</option></select>@error('status')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="field banner-side-field {{ $errors->has('sort_order') ? 'field-has-error' : '' }}"><label class="field-label" for="sort_order">Display order</label><input class="form-input" id="sort_order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $banner->sort_order) }}" required><p class="field-hint">Lower numbers appear first.</p>@error('sort_order')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <input type="hidden" name="text_alignment" value="{{ $banner->text_alignment ?: 'left' }}">
                    <input type="hidden" name="start_date" value="{{ $banner->start_date?->format('Y-m-d') }}">
                    <input type="hidden" name="end_date" value="{{ $banner->end_date?->format('Y-m-d') }}">
                </section>
            </aside>
        </div>
    </form>

    @if ($banner->exists)
        <section class="panel banner-gallery-panel">
            <div class="panel-heading"><div><p class="eyebrow">SUPPORTING ARTWORK</p><h2>Banner image gallery</h2></div><span class="banner-gallery-count">{{ $banner->images->count() }} {{ \Illuminate\Support\Str::plural('image', $banner->images->count()) }}</span></div>
            <form method="POST" action="{{ route('admin.banners.images.store', $banner) }}" enctype="multipart/form-data" class="banner-gallery-upload" data-loading>
                @csrf
                <label class="upload-drop banner-dropzone" for="gallery_images" data-dropzone><x-icon name="upload"/><strong>Add images to the gallery</strong><span>Select several images at once or drop them here · 5 MB max per image</span><input id="gallery_images" name="images[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple data-image-gallery="#banner-gallery-previews"></label>
                @error('images')<p class="field-error">{{ $message }}</p>@enderror
                @error('images.*')<p class="field-error">{{ $message }}</p>@enderror
                <div id="banner-gallery-previews" class="banner-upload-previews"></div>
                <div class="banner-gallery-upload-footer"><p class="field-hint">Images will be appended to the gallery. You can update alt text, order and visibility on each image below.</p><button class="button button-primary" type="submit" data-loading-button><x-icon name="plus" class="h-4 w-4"/> Upload images</button></div>
            </form>

            @if ($banner->images->isEmpty())
                <div class="empty-state empty-state--compact"><span class="empty-icon"><x-icon name="media"/></span><strong>No supporting images yet</strong><p>Add more artwork for future banner layouts.</p></div>
            @else
                <div class="banner-gallery-grid">
                    @foreach ($banner->images as $image)
                        <article class="banner-gallery-card">
                            <img class="banner-gallery-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) }}" alt="{{ $image->alt_text ?: 'Banner gallery image' }}" loading="lazy">
                            <div class="banner-gallery-card-body">
                                @if ($image->image_path === $banner->main_image)<span class="banner-main-label"><x-icon name="check" class="h-3 w-3"/> Main image</span>@endif
                                <form method="POST" action="{{ route('admin.banners.images.update', [$banner, $image]) }}" enctype="multipart/form-data" class="banner-gallery-edit-form" data-loading>
                                    @csrf @method('PATCH')
                                    <label class="field-label" for="alt_text_{{ $image->id }}">Alt text</label>
                                    <input class="form-input" id="alt_text_{{ $image->id }}" name="alt_text" value="{{ old('alt_text', $image->alt_text) }}" maxlength="255" placeholder="Describe this image">
                                    <div class="banner-gallery-card-fields">
                                        <div><label class="field-label" for="order_{{ $image->id }}">Display order</label><input class="form-input" id="order_{{ $image->id }}" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $image->sort_order) }}" required></div>
                                        <div><label class="field-label" for="status_{{ $image->id }}">Status</label><select class="form-select" id="status_{{ $image->id }}" name="status" required><option value="active" @selected(old('status', $image->status) === 'active')>Active</option><option value="inactive" @selected(old('status', $image->status) === 'inactive')>Inactive</option></select></div>
                                    </div>
                                    <label class="upload-drop banner-gallery-replace" for="replace_{{ $image->id }}" data-dropzone><x-icon name="upload" class="h-4 w-4"/><strong>Replace image</strong><input id="replace_{{ $image->id }}" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-image-preview="#replacement_preview_{{ $image->id }}"></label>
                                    <img id="replacement_preview_{{ $image->id }}" class="banner-replacement-preview" src="" alt="Replacement image preview" hidden>
                                    @error('alt_text')<p class="field-error">{{ $message }}</p>@enderror
                                    @error('sort_order')<p class="field-error">{{ $message }}</p>@enderror
                                    @error('status')<p class="field-error">{{ $message }}</p>@enderror
                                    @error('image')<p class="field-error">{{ $message }}</p>@enderror
                                    <button class="button button-outline button-small banner-gallery-save" type="submit" data-loading-button>Save image</button>
                                </form>
                                <div class="banner-gallery-card-actions">
                                    @if ($image->image_path !== $banner->main_image)
                                        <form method="POST" action="{{ route('admin.banners.images.main', [$banner, $image]) }}">@csrf @method('PATCH')<button class="banner-action-button" type="submit">Set as main</button></form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.banners.images.destroy', [$banner, $image]) }}" data-confirm="Remove this image from the banner gallery? The uploaded file will also be deleted.">@csrf @method('DELETE')<button class="banner-action-button banner-action-button--delete" type="submit"><x-icon name="trash" class="h-3 w-3"/> Remove</button></form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
@endsection
