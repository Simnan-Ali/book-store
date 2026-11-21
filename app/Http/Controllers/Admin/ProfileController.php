<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['user' => request()->user()]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'email']);
        $previousAvatarPath = $user->avatar_path;

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');

            if ($avatarPath === false) {
                throw new RuntimeException('Unable to store the administrator profile photo.');
            }

            $data['avatar_path'] = $avatarPath;
        } elseif ($request->boolean('remove_avatar')) {
            $data['avatar_path'] = null;
        }

        DB::transaction(function () use ($user, $data): void {
            $user->update($data);
            ActivityLogger::record('updated', $user, 'Updated administrator account settings.');
        });

        if ($previousAvatarPath && array_key_exists('avatar_path', $data) && $previousAvatarPath !== $data['avatar_path']) {
            Storage::disk('public')->delete($previousAvatarPath);
        }

        return back()->with('success', 'Your account settings have been updated.');
    }
}
