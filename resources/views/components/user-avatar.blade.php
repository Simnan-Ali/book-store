@props(['user'])

<span {{ $attributes->merge(['class' => 'user-avatar']) }}>
    @if ($user->avatar_path)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) }}" alt="{{ $user->name }}" loading="eager">
    @else
        <span aria-hidden="true"><x-icon name="book-open" data-image-placeholder/></span>
    @endif
</span>
