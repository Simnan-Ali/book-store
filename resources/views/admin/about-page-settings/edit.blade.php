@extends('layouts.admin')

@section('title', 'About Us Page Master')

@section('content')
    <div class="module-heading">
        <div>
            <p class="eyebrow">YOUR BOOKSTORE STORY</p>
            <h1>About Us Page Master</h1>
            <p>Manage the story and introduction that will appear on your future website.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="form-alert" role="alert">Please review the highlighted fields and try again.</div>
    @endif

    <form method="POST" action="{{ route('admin.about-page-settings.update') }}" enctype="multipart/form-data" data-loading>
        @csrf
        @method('PUT')
        @cannot('about-page.edit')<fieldset disabled>@endcannot

        <div class="form-layout">
            <div class="panel form-panel">
                <section class="form-section">
                    <div class="form-section-heading">
                        <span class="form-section-number">01</span>
                        <div>
                            <h2>About Us content</h2>
                            <p>Introduce your bookstore and the people behind it.</p>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="field {{ $errors->has('section_title') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="section_title">Section title <span class="required-mark">*</span></label>
                            <input class="form-input" id="section_title" name="section_title" value="{{ old('section_title', $settings->section_title) }}" maxlength="255" placeholder="Our story" required autofocus>
                            @error('section_title')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('heading') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="heading">Main heading <span class="required-mark">*</span></label>
                            <input class="form-input" id="heading" name="heading" value="{{ old('heading', $settings->heading) }}" maxlength="255" placeholder="A little about book-store" required>
                            @error('heading')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field field--full {{ $errors->has('description') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="description">Description</label>
                            <textarea class="form-textarea" id="description" name="description" rows="10" maxlength="10000" placeholder="Tell readers how your bookstore began, what you believe in, and what makes it special...">{{ old('description', $settings->description) }}</textarea>
                            @error('description')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="form-section-heading">
                        <span class="form-section-number">02</span>
                        <div>
                            <h2>Call-to-action button</h2>
                            <p>Optionally add a button for readers to explore more.</p>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="field {{ $errors->has('button_text') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="button_text">Button text</label>
                            <input class="form-input" id="button_text" name="button_text" value="{{ old('button_text', $settings->button_text) }}" maxlength="100" placeholder="Read more">
                            @error('button_text')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('button_link') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="button_link">Button link</label>
                            <input class="form-input" id="button_link" name="button_link" value="{{ old('button_link', $settings->button_link) }}" maxlength="2048" placeholder="/about-us or https://example.com">
                            <p class="field-hint">Use a site path such as /about-us or a full HTTP(S) URL.</p>
                            @error('button_link')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <input type="hidden" name="show_button" value="{{ $settings->show_button ? 1 : 0 }}">
                    <input type="hidden" name="open_new_tab" value="{{ $settings->open_new_tab ? 1 : 0 }}">
                </section>

                <div class="form-actions">
                    @can('about-page.edit')<a class="button button-muted" href="{{ route('admin.dashboard') }}">Cancel</a><button class="button button-primary" type="submit" data-loading-button>Save changes</button>@endcan
                </div>
            </div>

            <aside class="form-sidebar">
                <section class="side-form-card">
                    <h3>About Us image</h3>
                    <p>Upload an image to accompany your story. JPG, PNG or WebP, up to 5 MB.</p>
                    <label class="upload-drop" for="image" data-dropzone>
                        <x-icon name="upload"/>
                        <strong>{{ $settings->image_path ? 'Replace the current image' : 'Choose an image' }}</strong>
                        <span>JPG, JPEG, PNG or WebP</span>
                        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-image-preview="#about-image-preview" data-image-remove-input="#remove-about-image">
                    </label>
                    @error('image')<p class="field-error">{{ $message }}</p>@enderror
                    <div class="image-preview-frame" data-image-preview-frame>
                        <span class="image-placeholder-preview" data-image-placeholder @if ($settings->image_path && ! old('remove_image')) hidden @endif><x-icon name="book-open" class="h-8 w-8"/></span>
                        <img
                            id="about-image-preview"
                            class="image-preview"
                            src="{{ $settings->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->image_path) : '' }}"
                            data-original-src="{{ $settings->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->image_path) : '' }}"
                            alt="{{ $settings->image_alt ?: 'About Us image preview' }}"
                            @if (! $settings->image_path || old('remove_image')) hidden @endif
                        >
                    </div>
                    <input id="image_alt" name="image_alt" type="hidden" value="{{ $settings->image_alt }}">
                    <input type="hidden" name="remove_image" value="0">
                    @if ($settings->image_path)
                        <label class="remove-avatar-label" for="remove-about-image">
                            <input id="remove-about-image" type="checkbox" name="remove_image" value="1" data-image-remove="#about-image-preview" data-image-file="#image" @checked((bool) old('remove_image'))>
                            <span>Remove current image</span>
                        </label>
                    @endif
                    @error('remove_image')<p class="field-error">{{ $message }}</p>@enderror
                </section>
            </aside>
        </div>
        @cannot('about-page.edit')</fieldset>@endcannot
    </form>
@endsection
