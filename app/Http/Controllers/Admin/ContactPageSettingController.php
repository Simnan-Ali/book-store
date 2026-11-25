<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactPageSettingRequest;
use App\Models\ContactPageSetting;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class ContactPageSettingController extends Controller
{
    public function edit(): View
    {
        $settings = ContactPageSetting::singleton();

        return view('admin.contact-page-settings.edit', [
            'settings' => $settings,
            'safeMapEmbedUrl' => $settings->safeGoogleMapsEmbedUrl(),
        ]);
    }

    public function update(ContactPageSettingRequest $request): RedirectResponse
    {
        $settings = ContactPageSetting::singleton();
        $isNewSettings = ! $settings->exists;
        $previousBannerPath = $settings->banner_image;
        $data = $request->safe()->except(['banner_image', 'remove_banner_image']);

        if ($request->hasFile('banner_image')) {
            $bannerPath = $request->file('banner_image')->store('contact-page', 'public');

            if ($bannerPath === false) {
                throw new RuntimeException('Unable to store the Contact Us banner image.');
            }

            $data['banner_image'] = $bannerPath;
        } elseif ($request->boolean('remove_banner_image')) {
            $data['banner_image'] = null;
            $data['banner_alt_text'] = null;
        }

        DB::transaction(function () use ($settings, $data, $isNewSettings): void {
            $settings->fill($data);
            $settings->save();
            ActivityLogger::record(
                $isNewSettings ? 'created' : 'updated',
                $settings,
                'Updated Contact Us page settings.',
            );
        });

        $bannerWasChanged = array_key_exists('banner_image', $data)
            && $previousBannerPath !== $data['banner_image'];

        if (
            $bannerWasChanged
            && $previousBannerPath
            && Storage::disk('public')->exists($previousBannerPath)
            && ! Storage::disk('public')->delete($previousBannerPath)
        ) {
            return redirect()->route('admin.contact-page-settings.edit')
                ->with('success', 'Contact Us page settings were saved.')
                ->with('error', 'The previous banner image could not be removed from storage.');
        }

        return redirect()
            ->route('admin.contact-page-settings.edit')
            ->with('success', 'Contact Us page settings saved successfully.');
    }
}
