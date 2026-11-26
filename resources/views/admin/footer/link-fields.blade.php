@php
    $formKey = $formKey ?? 'link-'.$link->id;
    $isOldForm = old('_footer_form') === $formKey;
    $status = $isOldForm ? old('status') : ($link?->status ?? true);
    $newTab = $isOldForm ? old('open_new_tab') : ($link?->open_new_tab ?? false);
@endphp
<div class="footer-row footer-link-fields">
    <label class="field"><span class="field-label">Link label</span><input class="form-input" name="label" value="{{ $isOldForm ? old('label') : $link?->label }}" maxlength="255" placeholder="About us" required></label>
    <label class="field"><span class="field-label">Link URL</span><input class="form-input" name="url" value="{{ $isOldForm ? old('url') : $link?->url }}" maxlength="2048" placeholder="/about-us" required></label>
    <label class="field"><span class="field-label">Order</span><input class="form-input" name="sort_order" type="number" min="0" max="1000000" value="{{ $isOldForm ? old('sort_order') : ($link?->sort_order ?? 0) }}" required></label>
    <label class="field"><span class="field-label">Status</span><select class="form-select" name="status"><option value="1" @selected($status == 1)>Active</option><option value="0" @selected($status == 0)>Inactive</option></select></label>
    <label class="field"><span class="field-label">New tab</span><select class="form-select" name="open_new_tab"><option value="0" @selected($newTab == 0)>No</option><option value="1" @selected($newTab == 1)>Yes</option></select></label>
</div>
