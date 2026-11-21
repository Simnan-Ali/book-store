<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaLibraryController extends Controller
{
    public function __invoke(): View
    {
        $covers = Product::whereNotNull('main_image')
            ->latest()
            ->get(['id', 'title', 'main_image', 'created_at'])
            ->map(fn (Product $product) => [
                'url' => Storage::disk('public')->url($product->main_image),
                'title' => $product->title,
                'product_id' => $product->id,
                'created_at' => $product->created_at,
                'kind' => 'Book cover',
            ]);

        $gallery = ProductImage::with('product:id,title')
            ->latest('id')
            ->get()
            ->map(fn (ProductImage $image) => [
                'url' => Storage::disk('public')->url($image->image_path),
                'title' => $image->alt_text ?: $image->product->title,
                'product_id' => $image->product_id,
                'created_at' => null,
                'kind' => 'Gallery image',
            ]);

        return view('admin.media.index', [
            'assets' => $covers->concat($gallery),
            'assetCount' => $covers->count() + $gallery->count(),
        ]);
    }
}
