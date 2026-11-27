<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminBrandingRequest;
use App\Models\AdminBrandingSetting;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AdminBrandingController extends Controller
{
    public function edit(): View
    {
        return view('admin.branding.edit', ['settings' => AdminBrandingSetting::singleton()]);
    }

    public function update(AdminBrandingRequest $request): RedirectResponse
    {
        $settings = AdminBrandingSetting::singleton();
        $previous = $settings->only(['logo_path', 'favicon_path']);
        $data = $request->safe()->only(['name', 'tagline']);
        $newPaths = [];

        try {
            foreach (['logo', 'favicon'] as $field) {
                if ($request->hasFile($field)) {
                    $path = $request->file($field)->store('admin-branding', 'public');
                    if (! is_string($path) || $path === '') {
                        throw new RuntimeException('Unable to store the branding image.');
                    }
                    $newPaths[] = $path;
                    $data[$field.'_path'] = $path;
                } elseif ($request->boolean('remove_'.$field)) {
                    $data[$field.'_path'] = null;
                }
            }

            DB::transaction(function () use ($settings, $data): void {
                $settings->id = 1;
                $settings->fill($data)->save();
                ActivityLogger::record('updated', $settings, 'Updated admin branding settings.');
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPaths);
            throw $exception;
        }

        foreach ($previous as $key => $path) {
            if ($path && array_key_exists($key, $data) && $path !== $data[$key]) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->route('admin.branding.edit')->with('success', 'Admin settings saved successfully.');
    }
}
