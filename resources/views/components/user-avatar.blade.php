@props(['user'])

<span {{ $attributes->merge(['class' => 'user-avatar']) }}>
    @if ($user->avatar_path)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) }}" alt="{{ $user->name }}" loading="eager">
    @else
        <span aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}</span>
    @endif
</span>
