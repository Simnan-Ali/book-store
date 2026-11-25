<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $isVendor = ! $user->isSuperAdmin() && $user->hasActiveRole('vendor');
        $vendorId = $user->getKey();
        $books = fn (): Builder => Product::query()->when($isVendor, fn (Builder $query) => $query->where('vendor_id', $vendorId));
        $ownedBooks = fn (Builder $query): Builder => $query->when($isVendor, fn (Builder $query) => $query->where('vendor_id', $vendorId));

        return view('admin.dashboard', [
            'stats' => [
                'books' => $books()->count(),
                'categories' => Category::count(),
                'subcategories' => Subcategory::count(),
                'brands' => Brand::count(),
                'active' => $books()
                    ->where('is_active', true)
                    ->count(),
                'outOfStock' => $books()
                    ->where('stock_quantity', 0)
                    ->count(),
                'lowStock' => $books()
                    ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
                    ->where('stock_quantity', '>', 0)
                    ->count(),
            ],
            'recentProducts' => Product::with(['category', 'brand'])
                ->when($isVendor, fn (Builder $query) => $query->where('vendor_id', $vendorId))
                ->latest()
                ->take(6)
                ->get(),
            'activities' => Activity::with('user')
                ->when(! $user->isSuperAdmin(), fn (Builder $query) => $query->where('user_id', $vendorId))
                ->latest()
                ->take(6)
                ->get(),
            'categorySummary' => Category::withCount(['products' => $ownedBooks])
                ->orderByDesc('products_count')
                ->take(6)
                ->get(),
        ]);
    }
}
