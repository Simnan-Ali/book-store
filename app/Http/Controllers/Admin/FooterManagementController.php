<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactPageSetting;
use App\Models\FooterLink;
use App\Models\FooterSection;
use App\Models\FooterSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class FooterManagementController extends Controller
{
    public const PLATFORMS = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'twitter' => 'X / Twitter'];

    public function edit(): View
    {
        $settings = FooterSetting::singleton();
        $sections = $settings->exists ? $settings->sections()->with('links')->get() : collect();
        $socialLinks = $settings->exists ? $settings->socialLinks()->get()->keyBy('platform') : collect();

        return view('admin.footer.edit', ['settings' => $settings, 'sections' => $sections, 'socialLinks' => $socialLinks, 'platforms' => self::PLATFORMS, 'contact' => ContactPageSetting::singleton()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_logo' => ['nullable', 'boolean'],
            'logo_alt' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'office_address' => ['nullable', 'string', 'max:5000'],
            'google_maps_url' => ['nullable', 'url:http,https', 'max:2048'],
            'copyright_text' => ['nullable', 'string', 'max:5000'],
            'copyright_year' => ['nullable', 'integer', 'between:1000,9999'],
            'show_copyright' => ['required', 'boolean'],
        ]);
        $settings = FooterSetting::singleton();
        $oldPath = $settings->logo_path;
        $newPath = null;
        if ($request->hasFile('logo')) {
            $newPath = $request->file('logo')->store('footer', 'public');
            if ($newPath === false) {
                throw new \RuntimeException('Unable to store the footer logo.');
            }
            $data['logo_path'] = $newPath;
        } elseif ($request->boolean('remove_logo')) {
            $data['logo_path'] = null;
        }
        unset($data['logo'], $data['remove_logo']);
        try {
            DB::transaction(function () use ($settings, $data): void {
                $settings->fill($data)->save();
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $exception;
        }
        if (array_key_exists('logo_path', $data) && $oldPath && $oldPath !== $data['logo_path'] && ! Storage::disk('public')->delete($oldPath)) {
            return $this->done()->with('error', 'The previous logo could not be removed from storage.');
        }

        return $this->done();
    }

    public function storeSection(Request $request): RedirectResponse
    {
        $data = $this->sectionData($request);
        DB::transaction(function () use ($data): void {
            $settings = FooterSetting::query()->firstOrCreate(['id' => 1]);
            $settings->sections()->create($data);
        });

        return $this->done();
    }

    public function updateSection(Request $request, FooterSection $section): RedirectResponse
    {
        $data = $this->sectionData($request);
        DB::transaction(fn () => $section->update($data));

        return $this->done();
    }

    public function destroySection(Request $request, FooterSection $section): RedirectResponse
    {
        if ($section->links()->exists()) {
            abort_unless($request->user()->can('footer.links.delete'), 403);
        }
        DB::transaction(fn () => $section->delete());

        return $this->done();
    }

    public function storeLink(Request $request, FooterSection $section): RedirectResponse
    {
        $data = $this->linkData($request);
        DB::transaction(fn () => $section->links()->create($data));

        return $this->done();
    }

    public function updateLink(Request $request, FooterSection $section, FooterLink $link): RedirectResponse
    {
        abort_unless($link->footer_section_id === $section->id, 404);
        $data = $this->linkData($request);
        DB::transaction(fn () => $link->update($data));

        return $this->done();
    }

    public function destroyLink(FooterSection $section, FooterLink $link): RedirectResponse
    {
        abort_unless($link->footer_section_id === $section->id, 404);
        DB::transaction(fn () => $link->delete());

        return $this->done();
    }

    public function updateSocial(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (self::PLATFORMS as $platform => $label) {
            $rules["social.$platform.url"] = ['nullable', 'url:http,https', 'max:2048', "required_if:social.$platform.status,1"];
            $rules["social.$platform.icon"] = ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9 _-]+$/'];
            $rules["social.$platform.sort_order"] = ['required', 'integer', 'between:0,1000000'];
            $rules["social.$platform.status"] = ['required', 'boolean'];
        }
        $data = $request->validate($rules)['social'];
        DB::transaction(function () use ($data): void {
            $settings = FooterSetting::query()->firstOrCreate(['id' => 1]);
            foreach (array_keys(self::PLATFORMS) as $platform) {
                $settings->socialLinks()->updateOrCreate(['platform' => $platform], $data[$platform]);
            }
        });

        return $this->done();
    }

    private function sectionData(Request $request): array
    {
        return $request->validate(['title' => ['required', 'string', 'max:255'], 'sort_order' => ['required', 'integer', 'between:0,1000000'], 'status' => ['required', 'boolean']]);
    }

    private function linkData(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value)) {
                    $fail('Use a site path or a valid HTTP(S) URL.');

                    return;
                }
                $internal = str_starts_with($value, '/') && ! str_starts_with($value, '//') && ! str_contains($value, '\\') && ! preg_match('/[\x00-\x20\x7f]/', $value);
                $external = filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
                if (! $internal && ! $external) {
                    $fail('Use a site path or a valid HTTP(S) URL.');
                }
            }],
            'open_new_tab' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'between:0,1000000'],
            'status' => ['required', 'boolean'],
        ]);
    }

    private function done(): RedirectResponse
    {
        return redirect()->route('admin.footer.edit')->with('success', 'Footer changes saved successfully.');
    }
}
