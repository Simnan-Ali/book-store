<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactPageSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'page_title' => ['required', 'string', 'max:255'],
            'page_subtitle' => ['nullable', 'string', 'max:255'],
            'main_heading' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'banner_alt_text' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'office_address' => ['nullable', 'string', 'max:5000'],
            'google_maps_url' => ['nullable', 'url:http,https', 'max:2048'],
            'business_hours' => ['nullable', 'string', 'max:5000'],
            'working_days' => ['nullable', 'string', 'max:255'],
            'additional_information' => ['nullable', 'string', 'max:5000'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:2048'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:2048'],
            'linkedin_url' => ['nullable', 'url:http,https', 'max:2048'],
            'youtube_url' => ['nullable', 'url:http,https', 'max:2048'],
            'contact_form_heading' => ['required', 'string', 'max:255'],
            'contact_form_description' => ['nullable', 'string', 'max:5000'],
            'submit_button_text' => ['required', 'string', 'max:100'],
            'success_message' => ['required', 'string', 'max:5000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:5000'],
            'meta_keywords' => ['nullable', 'string', 'max:5000'],
            'remove_banner_image' => ['sometimes', 'boolean'],
        ];
    }
}
