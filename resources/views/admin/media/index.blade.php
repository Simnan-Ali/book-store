@extends('layouts.admin')

@section('title', 'Media library')

@section('content')
    <div class="module-heading">
        <div><p class="eyebrow">EVERY COVER, ONE PLACE</p><h1>Media library</h1><p>Browse the book covers and gallery images attached to your catalogue.</p></div>
        <span class="status-pill status-active">{{ number_format($assetCount) }} {{ \Illuminate\Support\Str::plural('image', $assetCount) }}</span>
    </div>
    @if ($assets->isEmpty())
        <section class="panel"><div class="empty-state"><span class="empty-icon"><x-icon name="media"/></span><strong>Your image library is empty</strong><p>Book covers and gallery images you upload will be collected here.</p>@can('books.create')<a href="{{ route('admin.products.create') }}" class="button button-primary button-small">Add a book</a>@endcan</div></section>
    @else
        <div class="media-grid">
            @foreach ($assets as $asset)
                @if (auth()->user()->can('books.view'))
                <a class="media-card" href="{{ route(auth()->user()->can('books.edit') ? 'admin.products.edit' : 'admin.products.show', $asset['product_id']) }}">
                @else
                <div class="media-card">
                @endif
                    <div class="media-image"><img src="{{ $asset['url'] }}" alt="{{ $asset['title'] }}" loading="lazy"></div>
                    <div class="media-meta"><strong>{{ $asset['title'] }}</strong><span>{{ $asset['kind'] }}@if (auth()->user()->can('books.view')) &middot; {{ auth()->user()->can('books.edit') ? 'Edit book' : 'View book' }} <x-icon name="arrow" class="inline h-3 w-3"/>@endif</span></div>
                @if (auth()->user()->can('books.view'))
                </a>
                @else
                </div>
                @endif
            @endforeach
        </div>
    @endif
@endsection
