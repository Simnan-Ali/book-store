@extends('layouts.admin')

@section('title', 'Media library')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">EVERY COVER, ONE PLACE</p><h1>Media library</h1><p>Browse the book covers and gallery images attached to your catalogue.</p></div>
        <span class="status-pill status-active">{{ number_format($assetCount) }} {{ \Illuminate\Support\Str::plural('image', $assetCount) }}</span>
    </div>
    @if ($assets->isEmpty())
        <section class="panel"><div class="empty-state"><span class="empty-icon"><x-icon name="media"/></span><strong>Your image library is empty</strong><p>Book covers and gallery images you upload will be collected here.</p><a href="{{ route('admin.products.create') }}" class="button button-primary button-small">Add a book</a></div></section>
    @else
        <div class="media-grid">
            @foreach ($assets as $asset)
                <a class="media-card" href="{{ route('admin.products.edit', $asset['product_id']) }}">
                    <div class="media-image"><img src="{{ $asset['url'] }}" alt="{{ $asset['title'] }}" loading="lazy"></div>
                    <div class="media-meta"><strong>{{ $asset['title'] }}</strong><span>{{ $asset['kind'] }} &middot; Edit book <x-icon name="arrow" class="inline h-3 w-3"/></span></div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
