<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'books' => Product::count(),
                'categories' => Category::count(),
                'subcategories' => Subcategory::count(),
                'brands' => Brand::count(),
                'active' => Product::where('is_active', true)->count(),
                'outOfStock' => Product::where('stock_quantity', 0)->count(),
                'lowStock' => Product::where('stock_quantity', '>', 0)
                    ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
                    ->count(),
            ],
            'recentProducts' => Product::with(['category', 'brand'])
                ->latest()
                ->take(6)
                ->get(),
            'activities' => Activity::with('user')->latest()->take(6)->get(),
            'categorySummary' => Category::withCount('products')
                ->orderByDesc('products_count')
                ->take(6)
                ->get(),
        ]);
    }
}
