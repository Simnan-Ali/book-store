@extends('layouts.admin')

@section('title', 'Contact Us Page Master')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">KEEP IN TOUCH</p><h1>Contact Us Page Master</h1><p>Manage the content and contact details for the future website.</p></div>
    </div>

    @if ($errors->any())
        <div class="form-alert" role="alert">Please review the highlighted fields and try again.</div>
    @endif

    <form method="POST" action="{{ route('admin.contact-page-settings.update') }}" enctype="multipart/form-data" data-loading>
        @csrf
        @method('PUT')

        <div class="form-layout">
            <div class="panel form-panel">
                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">01</span><div><h2>Basic page information</h2><p>Set the page title and its main introduction.</p></div></div>
                    <div class="form-grid">
                        <div class="field {{ $errors->has('page_title') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="page_title">Page title <span class="required-mark">*</span></label>
                            <input class="form-input" id="page_title" name="page_title" value="{{ old('page_title', $settings->page_title) }}" maxlength="255" placeholder="Contact Us" required>
                            @error('page_title')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('page_subtitle') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="page_subtitle">Page subtitle</label>
                            <input class="form-input" id="page_subtitle" name="page_subtitle" value="{{ old('page_subtitle', $settings->page_subtitle) }}" maxlength="255" placeholder="We'd love to hear from you">
                            @error('page_subtitle')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field field--full {{ $errors->has('main_heading') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="main_heading">Main heading <span class="required-mark">*</span></label>
                            <input class="form-input" id="main_heading" name="main_heading" value="{{ old('main_heading', $settings->main_heading) }}" maxlength="255" placeholder="Let's start a conversation" required>
                            @error('main_heading')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field field--full {{ $errors->has('description') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="description">Description</label>
                            <textarea class="form-textarea" id="description" name="description" rows="4" placeholder="Tell visitors how your team can help...">{{ old('description', $settings->description) }}</textarea>
                            @error('description')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">02</span><div><h2>Contact information</h2><p>Keep the ways to reach your bookstore accurate.</p></div></div>
                    <div class="form-grid">
                        <div class="field {{ $errors->has('contact_email') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="contact_email">Contact email <span class="required-mark">*</span></label>
                            <input class="form-input" id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $settings->contact_email) }}" maxlength="255" placeholder="hello@example.com" required>
                            @error('contact_email')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('contact_phone') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="contact_phone">Contact phone</label>
                            <input class="form-input" id="contact_phone" name="contact_phone" type="tel" value="{{ old('contact_phone', $settings->contact_phone) }}" maxlength="50" placeholder="+1 555 123 4567">
                            @error('contact_phone')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('whatsapp_number') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="whatsapp_number">WhatsApp number</label>
                            <input class="form-input" id="whatsapp_number" name="whatsapp_number" type="tel" value="{{ old('whatsapp_number', $settings->whatsapp_number) }}" maxlength="50" placeholder="+1 555 123 4567">
                            @error('whatsapp_number')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('google_maps_url') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="google_maps_url">Google Maps URL</label>
                            <input class="form-input" id="google_maps_url" name="google_maps_url" type="url" value="{{ old('google_maps_url', $settings->google_maps_url) }}" maxlength="2048" placeholder="https://maps.google.com/...">
                            @error('google_maps_url')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field field--full {{ $errors->has('office_address') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="office_address">Office address</label>
                            <textarea class="form-textarea" id="office_address" name="office_address" rows="3" placeholder="Street, city, region, postal code">{{ old('office_address', $settings->office_address) }}</textarea>
                            @error('office_address')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">03</span><div><h2>Business information</h2><p>Share when and how your team is available.</p></div></div>
                    <div class="form-grid">
                        <div class="field {{ $errors->has('working_days') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="working_days">Working days</label>
                            <input class="form-input" id="working_days" name="working_days" value="{{ old('working_days', $settings->working_days) }}" maxlength="255" placeholder="Monday–Friday">
                            @error('working_days')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('business_hours') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="business_hours">Business hours</label>
                            <textarea class="form-textarea" id="business_hours" name="business_hours" rows="3" placeholder="9:00 AM–5:00 PM">{{ old('business_hours', $settings->business_hours) }}</textarea>
                            @error('business_hours')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field field--full {{ $errors->has('additional_information') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="additional_information">Additional information</label>
                            <textarea class="form-textarea" id="additional_information" name="additional_information" rows="4" placeholder="Any other helpful details for visitors...">{{ old('additional_information', $settings->additional_information) }}</textarea>
                            @error('additional_information')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">04</span><div><h2>Social media links</h2><p>Add the bookstore's official social profiles.</p></div></div>
                    <div class="form-grid">
                        <div class="field {{ $errors->has('facebook_url') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="facebook_url">Facebook URL</label>
                            <input class="form-input" id="facebook_url" name="facebook_url" type="url" value="{{ old('facebook_url', $settings->facebook_url) }}" maxlength="2048" placeholder="https://facebook.com/yourbookstore">
                            @error('facebook_url')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('instagram_url') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="instagram_url">Instagram URL</label>
                            <input class="form-input" id="instagram_url" name="instagram_url" type="url" value="{{ old('instagram_url', $settings->instagram_url) }}" maxlength="2048" placeholder="https://instagram.com/yourbookstore">
                            @error('instagram_url')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('linkedin_url') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="linkedin_url">LinkedIn URL</label>
                            <input class="form-input" id="linkedin_url" name="linkedin_url" type="url" value="{{ old('linkedin_url', $settings->linkedin_url) }}" maxlength="2048" placeholder="https://linkedin.com/company/yourbookstore">
                            @error('linkedin_url')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('youtube_url') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="youtube_url">YouTube URL</label>
                            <input class="form-input" id="youtube_url" name="youtube_url" type="url" value="{{ old('youtube_url', $settings->youtube_url) }}" maxlength="2048" placeholder="https://youtube.com/@yourbookstore">
                            @error('youtube_url')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">05</span><div><h2>Contact form content</h2><p>Prepare the wording for a future website contact form.</p></div></div>
                    <div class="form-grid">
                        <div class="field field--full {{ $errors->has('contact_form_heading') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="contact_form_heading">Contact form heading <span class="required-mark">*</span></label>
                            <input class="form-input" id="contact_form_heading" name="contact_form_heading" value="{{ old('contact_form_heading', $settings->contact_form_heading) }}" maxlength="255" placeholder="Send us a message" required>
                            @error('contact_form_heading')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field field--full {{ $errors->has('contact_form_description') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="contact_form_description">Contact form description</label>
                            <textarea class="form-textarea" id="contact_form_description" name="contact_form_description" rows="3" placeholder="Let visitors know what to include in their message...">{{ old('contact_form_description', $settings->contact_form_description) }}</textarea>
                            @error('contact_form_description')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('submit_button_text') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="submit_button_text">Submit button text <span class="required-mark">*</span></label>
                            <input class="form-input" id="submit_button_text" name="submit_button_text" value="{{ old('submit_button_text', $settings->submit_button_text) }}" maxlength="100" placeholder="Send message" required>
                            @error('submit_button_text')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('success_message') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="success_message">Success message <span class="required-mark">*</span></label>
                            <textarea class="form-textarea" id="success_message" name="success_message" rows="3" placeholder="Thank you for reaching out. We'll be in touch soon." required>{{ old('success_message', $settings->success_message) }}</textarea>
                            @error('success_message')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="form-section-heading"><span class="form-section-number">06</span><div><h2>SEO information</h2><p>Optional metadata to prepare the page for search engines.</p></div></div>
                    <div class="form-grid">
                        <div class="field field--full {{ $errors->has('meta_title') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="meta_title">Meta title</label>
                            <input class="form-input" id="meta_title" name="meta_title" value="{{ old('meta_title', $settings->meta_title) }}" maxlength="255" placeholder="Contact Us | Folio Books">
                            @error('meta_title')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field field--full {{ $errors->has('meta_description') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="meta_description">Meta description</label>
                            <textarea class="form-textarea" id="meta_description" name="meta_description" rows="3" placeholder="A brief description of how visitors can contact your bookstore...">{{ old('meta_description', $settings->meta_description) }}</textarea>
                            @error('meta_description')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field field--full {{ $errors->has('meta_keywords') ? 'field-has-error' : '' }}">
                            <label class="field-label" for="meta_keywords">Meta keywords</label>
                            <textarea class="form-textarea" id="meta_keywords" name="meta_keywords" rows="2" placeholder="bookstore, contact, customer support">{{ old('meta_keywords', $settings->meta_keywords) }}</textarea>
                            @error('meta_keywords')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <div class="form-actions">
                    <a class="button button-muted" href="{{ route('admin.dashboard') }}">Cancel</a>
                    <button class="button button-primary" type="submit" data-loading-button>Save changes</button>
                </div>
            </div>

            <aside class="form-sidebar">
                <section class="side-form-card">
                    <h3>Banner image</h3>
                    <p>Upload an image for future use on the Contact Us page. JPG, PNG or WebP, up to 5 MB.</p>
                    <label class="upload-drop" for="banner_image">
                        <x-icon name="upload"/>
                        <strong>Choose a banner image</strong>
                        <span>JPG, PNG or WebP</span>
                        <input id="banner_image" name="banner_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-image-preview="#contact-banner-preview" data-image-remove-input="#remove-banner-image">
                    </label>
                    @error('banner_image')<p class="field-error">{{ $message }}</p>@enderror
                    <img
                        id="contact-banner-preview"
                        class="image-preview contact-banner-preview"
                        src="{{ $settings->banner_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->banner_image) : '' }}"
                        data-original-src="{{ $settings->banner_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->banner_image) : '' }}"
                        alt="Contact Us banner preview"
                        @if (! $settings->banner_image || old('remove_banner_image')) hidden @endif
                    >
                    <input type="hidden" name="remove_banner_image" value="0">
                    @if ($settings->banner_image)
                        <label class="remove-avatar-label" for="remove-banner-image">
                            <input id="remove-banner-image" type="checkbox" name="remove_banner_image" value="1" data-image-remove="#contact-banner-preview" data-image-file="#banner_image" @checked((bool) old('remove_banner_image'))>
                            <span>Remove current banner image</span>
                        </label>
                    @endif
                    @error('remove_banner_image')<p class="field-error">{{ $message }}</p>@enderror
                </section>
                <section class="side-form-card">
                    <h3>Single page record</h3>
                    <p>All Contact Us page content is saved to one settings record. There are no add or delete actions.</p>
                </section>
            </aside>
        </div>
    </form>
@endsection
