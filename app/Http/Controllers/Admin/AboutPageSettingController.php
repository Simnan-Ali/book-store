<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AboutPageSettingRequest;
use App\Models\AboutPageSetting;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AboutPageSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.about-page-settings.edit', [
            'settings' => AboutPageSetting::singleton(),
        ]);
    }

    public function update(AboutPageSettingRequest $request): RedirectResponse
    {
        $settings = AboutPageSetting::singleton();
        $isNewSettings = ! $settings->exists;
        $previousImagePath = $settings->image_path;
        $data = $request->safe()->except(['image', 'remove_image']);
        $newImagePath = null;

        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('about-page', 'public');

            if (! is_string($newImagePath) || $newImagePath === '') {
                throw new RuntimeException('Unable to store the About Us image.');
            }

            $data['image_path'] = $newImagePath;
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
            $data['image_alt'] = null;
        }

        try {
            DB::transaction(function () use ($settings, $data, $isNewSettings): void {
                $settings->fill($data);
                $settings->save();
                ActivityLogger::record(
                    $isNewSettings ? 'created' : 'updated',
                    $settings,
                    'Updated About Us page settings.',
                );
            });
        } catch (Throwable $exception) {
            if ($newImagePath !== null && ! Storage::disk('public')->delete($newImagePath)) {
                throw new RuntimeException('Unable to remove the uncommitted About Us image.', 0, $exception);
            }

            throw $exception;
        }

        $imageWasChanged = array_key_exists('image_path', $data)
            && $previousImagePath !== $data['image_path'];

        if (
            $imageWasChanged
            && $previousImagePath
            && Storage::disk('public')->exists($previousImagePath)
            && ! Storage::disk('public')->delete($previousImagePath)
        ) {
            return redirect()->route('admin.about-page-settings.edit')
                ->with('success', 'About Us page settings were saved.')
                ->with('error', 'The previous About Us image could not be removed from storage.');
        }

        return redirect()
            ->route('admin.about-page-settings.edit')
            ->with('success', 'About Us page settings saved successfully.');
    }
}
