@extends('layouts.admin')

@section('title', 'Footer Management')

@section('content')
    <div class="module-heading"><div><p class="eyebrow">WEBSITE CONTENT</p><h1>Footer Management</h1><p>Keep your bookstore's footer content organized and ready for your website.</p></div></div>

    @if ($errors->any())
        <div class="form-alert" role="alert"><strong>Please review your changes:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="footer-management">
        <form method="POST" action="{{ route('admin.footer.update') }}" enctype="multipart/form-data" class="panel form-panel">
            @csrf @method('PUT')
            @cannot('footer.edit')<fieldset disabled>@endcannot
            <section class="form-section">
                <div class="form-section-heading"><span class="form-section-number">01</span><div><h2>Branding</h2><p>Introduce your bookstore at a glance.</p></div></div>
                <div class="footer-brand-grid">
                    <div>
                        <label class="field-label" for="logo">Footer logo</label>
                        <label class="upload-drop" for="logo" data-dropzone><x-icon name="upload"/><strong>Choose or replace logo</strong><span>JPG, PNG or WebP · up to 5 MB</span><input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" data-image-preview="#footer-logo-preview" data-image-remove-input="#remove-footer-logo"></label>
                        <div class="image-preview-frame" data-image-preview-frame>
                            <span class="image-placeholder-preview" data-image-placeholder @if ($settings->logo_path && ! old('remove_logo')) hidden @endif><x-icon name="book-open" class="h-8 w-8"/></span>
                            <img id="footer-logo-preview" class="image-preview" src="{{ $settings->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->logo_path) : '' }}" data-original-src="{{ $settings->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->logo_path) : '' }}" alt="{{ $settings->logo_alt ?: 'Footer logo preview' }}" @if (! $settings->logo_path || old('remove_logo')) hidden @endif>
                        </div>
                        <input type="hidden" name="remove_logo" value="0">
                        @if ($settings->logo_path)<label class="remove-avatar-label"><input id="remove-footer-logo" type="checkbox" name="remove_logo" value="1" data-image-remove="#footer-logo-preview" data-image-file="#logo" @checked(old('remove_logo'))> Remove current logo</label>@endif
                    </div>
                    <div class="footer-field-stack">
                        <div class="field"><label class="field-label" for="logo_alt">Logo alt text</label><input class="form-input" id="logo_alt" name="logo_alt" maxlength="255" value="{{ old('logo_alt', $settings->logo_alt) }}" placeholder="Bookstore logo"></div>
                        <div class="field"><label class="field-label" for="tagline">Tagline</label><input class="form-input" id="tagline" name="tagline" maxlength="255" value="{{ old('tagline', $settings->tagline) }}" placeholder="A home for every reader"></div>
                        <div class="field"><label class="field-label" for="description">Description</label><textarea class="form-textarea" id="description" name="description" rows="5" maxlength="10000" placeholder="Tell readers a little about your bookstore.">{{ old('description', $settings->description) }}</textarea></div>
                    </div>
                </div>
            </section>
            <section class="form-section">
                <div class="form-section-heading"><span class="form-section-number">02</span><div><h2>Contact information</h2><p>Leave a field blank to use the corresponding Contact Us Page Master value.</p></div></div>
                <div class="form-grid">
                    @foreach (['contact_email' => 'Contact email', 'contact_phone' => 'Contact phone', 'whatsapp_number' => 'WhatsApp number', 'google_maps_url' => 'Google Maps URL'] as $field => $label)
                        <div class="field"><label class="field-label" for="{{ $field }}">{{ $label }}</label><input class="form-input" id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'contact_email' ? 'email' : ($field === 'google_maps_url' ? 'url' : 'text') }}" value="{{ old($field, $settings->$field) }}" placeholder="{{ $contact->$field ?: 'Use Contact Us value' }}">@if ($contact->$field)<p class="field-hint">Contact Us: {{ $contact->$field }}</p>@endif</div>
                    @endforeach
                    <div class="field field--full"><label class="field-label" for="office_address">Office address</label><textarea class="form-textarea" id="office_address" name="office_address" rows="3" placeholder="Use Contact Us value">{{ old('office_address', $settings->office_address) }}</textarea>@if ($contact->office_address)<p class="field-hint">Contact Us: {{ $contact->office_address }}</p>@endif</div>
                </div>
            </section>
            <section class="form-section">
                <div class="form-section-heading"><span class="form-section-number">03</span><div><h2>Copyright</h2><p>Set the message and year for your future footer.</p></div></div>
                <div class="form-grid">
                    <div class="field field--full"><label class="field-label" for="copyright_text">Copyright text</label><textarea class="form-textarea" id="copyright_text" name="copyright_text" rows="2" placeholder="Folio Books. All rights reserved.">{{ old('copyright_text', $settings->copyright_text) }}</textarea></div>
                    <div class="field"><label class="field-label" for="copyright_year">Copyright year</label><input class="form-input" id="copyright_year" name="copyright_year" type="number" min="1000" max="9999" value="{{ old('copyright_year', $settings->copyright_year) }}" placeholder="{{ now()->year }}"></div>
                    <div class="field"><label class="field-label" for="show_copyright">Show copyright</label><select class="form-select" id="show_copyright" name="show_copyright"><option value="1" @selected(old('show_copyright', $settings->show_copyright) == 1)>Yes</option><option value="0" @selected(old('show_copyright', $settings->show_copyright) == 0)>No</option></select></div>
                </div>
            </section>
            @can('footer.edit')<div class="form-actions"><a class="button button-muted" href="{{ route('admin.dashboard') }}">Cancel</a><button class="button button-primary" type="submit">Save changes</button></div>@endcan
            @cannot('footer.edit')</fieldset>@endcannot
        </form>

        <section class="panel form-panel" id="footer-sections">
            <div class="form-section-heading"><span class="form-section-number">04</span><div><h2>Link sections</h2><p>Use display order to arrange sections and their links. Lower numbers appear first.</p></div></div>
            @forelse ($sections as $section)
                <div class="footer-section-card">
                    <div class="footer-card-title"><div><strong>{{ $section->title }}</strong><span>{{ $section->status ? 'Active' : 'Inactive' }} · {{ $section->links->count() }} links</span></div></div>
                    <form method="POST" action="{{ route('admin.footer.sections.update', $section) }}" class="footer-inline-form">@csrf @method('PUT')<input type="hidden" name="_footer_form" value="section-{{ $section->id }}">
                        @cannot('footer.sections.edit')<fieldset disabled>@endcannot
                        <div class="footer-row footer-section-row">
                            <label class="field"><span class="field-label">Section title</span><input class="form-input" name="title" value="{{ old('_footer_form') === 'section-'.$section->id ? old('title') : $section->title }}" required maxlength="255"></label>
                            <label class="field"><span class="field-label">Display order</span><input class="form-input" name="sort_order" type="number" min="0" max="1000000" value="{{ old('_footer_form') === 'section-'.$section->id ? old('sort_order') : $section->sort_order }}" required></label>
                            @php($sectionStatus = old('_footer_form') === 'section-'.$section->id ? old('status') : $section->status)
                            <label class="field"><span class="field-label">Status</span><select class="form-select" name="status"><option value="1" @selected($sectionStatus == 1)>Active</option><option value="0" @selected($sectionStatus == 0)>Inactive</option></select></label>
                            @can('footer.sections.edit')<button class="button button-outline" type="submit">Save section</button>@endcan
                        </div>
                        @cannot('footer.sections.edit')</fieldset>@endcannot
                    </form>
                    <div class="footer-links-heading"><strong>Links</strong><span>Site paths or full HTTP(S) URLs</span></div>
                    @foreach ($section->links as $link)
                        <div class="footer-link-card">
                            <form method="POST" action="{{ route('admin.footer.links.update', [$section, $link]) }}">@csrf @method('PUT')<input type="hidden" name="_footer_form" value="link-{{ $link->id }}">
                                @cannot('footer.links.edit')<fieldset disabled>@endcannot
                                @include('admin.footer.link-fields', ['link' => $link])
                                @can('footer.links.edit')<button class="button button-outline button-small" type="submit">Save link</button>@endcan
                                @cannot('footer.links.edit')</fieldset>@endcannot
                            </form>
                            @can('footer.links.delete')<form method="POST" action="{{ route('admin.footer.links.destroy', [$section, $link]) }}" onsubmit="return confirm('Delete this link?')">@csrf @method('DELETE')<button class="button button-muted button-small" type="submit">Delete link</button></form>@endcan
                        </div>
                    @endforeach
                    @can('footer.links.create')
                        <details class="footer-add" @if (old('_footer_form') === 'new-link-'.$section->id) open @endif><summary>Add a link</summary><form method="POST" action="{{ route('admin.footer.links.store', $section) }}">@csrf <input type="hidden" name="_footer_form" value="new-link-{{ $section->id }}"> @include('admin.footer.link-fields', ['link' => null, 'formKey' => 'new-link-'.$section->id])<button class="button button-outline button-small" type="submit">Add link</button></form></details>
                    @endcan
                    @can('footer.sections.delete')
                        @if ($section->links->isEmpty() || auth()->user()->can('footer.links.delete'))<form class="footer-delete" method="POST" action="{{ route('admin.footer.sections.destroy', $section) }}" onsubmit="return confirm('Delete this section and all its links?')">@csrf @method('DELETE')<button class="button button-muted button-small" type="submit">Delete section</button></form>@endif
                    @endcan
                </div>
            @empty
                <p class="footer-empty">No link sections yet. Add a section to start organizing your footer navigation.</p>
            @endforelse
            @can('footer.sections.create')
                <details class="footer-add" @if (old('_footer_form') === 'new-section') open @endif><summary>Add a section</summary><form method="POST" action="{{ route('admin.footer.sections.store') }}">@csrf <input type="hidden" name="_footer_form" value="new-section">
                    <div class="footer-row footer-section-row"><label class="field"><span class="field-label">Section title</span><input class="form-input" name="title" value="{{ old('_footer_form') === 'new-section' ? old('title') : '' }}" required maxlength="255" placeholder="Quick links"></label><label class="field"><span class="field-label">Display order</span><input class="form-input" name="sort_order" type="number" min="0" max="1000000" value="{{ old('_footer_form') === 'new-section' ? old('sort_order') : 0 }}" required></label><label class="field"><span class="field-label">Status</span><select class="form-select" name="status"><option value="1" @selected(old('_footer_form') !== 'new-section' || old('status') == 1)>Active</option><option value="0" @selected(old('_footer_form') === 'new-section' && old('status') == 0)>Inactive</option></select></label><button class="button button-primary" type="submit">Add section</button></div>
                </form></details>
            @endcan
        </section>

        <section class="panel form-panel">
            <div class="form-section-heading"><span class="form-section-number">05</span><div><h2>Social media</h2><p>Manage destinations and icon identifiers. Only active links with URLs are intended for display.</p></div></div>
            <form method="POST" action="{{ route('admin.footer.social.update') }}">@csrf @method('PUT')
                @cannot('footer.social-links.manage')<fieldset disabled>@endcannot
                @foreach ($platforms as $platform => $label)
                    @php($social = $socialLinks->get($platform))
                    <div class="footer-social-row"><strong>{{ $label }}</strong><div class="footer-row footer-social-fields">
                        <label class="field"><span class="field-label">URL</span><input class="form-input" type="url" name="social[{{ $platform }}][url]" value="{{ old('social.'.$platform.'.url', $social?->url) }}" placeholder="https://"></label>
                        <label class="field"><span class="field-label">Icon</span><input class="form-input" name="social[{{ $platform }}][icon]" value="{{ old('social.'.$platform.'.icon', $social?->icon ?? $platform) }}" maxlength="100" required></label>
                        <label class="field"><span class="field-label">Order</span><input class="form-input" type="number" min="0" max="1000000" name="social[{{ $platform }}][sort_order]" value="{{ old('social.'.$platform.'.sort_order', $social?->sort_order ?? $loop->index) }}" required></label>
                        <label class="field"><span class="field-label">Status</span><select class="form-select" name="social[{{ $platform }}][status]"><option value="0" @selected(! old('social.'.$platform.'.status', $social?->status ?? false))>Inactive</option><option value="1" @selected(old('social.'.$platform.'.status', $social?->status ?? false))>Active</option></select></label>
                    </div></div>
                @endforeach
                @can('footer.social-links.manage')<div class="form-actions"><button class="button button-primary" type="submit">Save social links</button></div>@endcan
                @cannot('footer.social-links.manage')</fieldset>@endcannot
            </form>
        </section>
    </div>
@endsection
