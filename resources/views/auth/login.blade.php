<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in &middot; {{ $adminBranding->name }}</title>
    @if ($adminBranding->favicon_path)<link rel="icon" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($adminBranding->favicon_path) }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">
    <div class="login-decoration login-decoration--one"></div>
    <div class="login-decoration login-decoration--two"></div>
    <main class="login-wrap">
        <a href="{{ route('login') }}" class="brand-lockup login-brand">
            <span class="brand-mark">@if ($adminBranding->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($adminBranding->logo_path) }}" alt="" class="brand-logo">@else<x-icon name="book-open" class="h-6 w-6"/>@endif</span>
            <span><strong>{{ $adminBranding->name }}</strong>@if ($adminBranding->tagline)<small>{{ $adminBranding->tagline }}</small>@endif</span>
        </a>
        <section class="login-card">
            <div class="login-eyebrow"><span></span> YOUR PRIVATE WORKSPACE</div>
            <h1>Welcome back.</h1>
            <p class="login-subtitle">Sign in to manage your bookstore.</p>

            @if ($errors->any())
                <div class="form-alert" role="alert">{{ $errors->first() }}</div>
            @endif
            @if (session('status'))
                <div class="form-success" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="form-stack">
                @csrf
                <label class="field-label" for="email">Email address</label>
                <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@yourbookstore.com" autocomplete="username" required autofocus>
                @error('email')<p class="field-error">{{ $message }}</p>@enderror

                <div class="password-label-row">
                    <label class="field-label" for="password">Password</label>
                </div>
                <input class="form-input" id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                <label class="remember-label"><input type="checkbox" name="remember" value="1"> <span>Keep me signed in</span></label>
                <button class="button button-primary login-submit" type="submit">Sign in to dashboard <x-icon name="arrow" class="h-4 w-4"/></button>
            </form>
        </section>
        <p class="login-footer">A calm space for a well-loved collection.</p>
    </main>
</body>
</html>
