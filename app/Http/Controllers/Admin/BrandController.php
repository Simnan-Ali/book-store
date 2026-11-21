<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use App\Support\ActivityLogger;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $brands = Brand::withCount('products')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('name', 'like', "%{$term}%"))
            ->when(isset($filters['status']), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.brands.index', compact('brands'));
    }

    public function create(): View
    {
        return view('admin.brands.form', ['brand' => new Brand(['is_active' => true])]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('logo');
        $data['slug'] = UniqueSlug::make($data['name'], 'brands');
        $data['logo'] = $request->file('logo')?->store('brands', 'public');

        DB::transaction(function () use ($data): void {
            $brand = Brand::create($data);
            ActivityLogger::record('created', $brand, "Created publisher '{$brand->name}'.");
        });

        return redirect()->route('admin.brands.index')->with('success', 'Publisher created successfully.');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.form', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $data = $request->safe()->except('logo');
        $data['slug'] = UniqueSlug::make($data['name'], 'brands', $brand->id);
        $previousLogo = $brand->logo;

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        DB::transaction(function () use ($brand, $data): void {
            $brand->update($data);
            ActivityLogger::record('updated', $brand, "Updated publisher '{$brand->name}'.");
        });

        if ($request->hasFile('logo') && $previousLogo) {
            Storage::disk('public')->delete($previousLogo);
        }

        return redirect()->route('admin.brands.index')->with('success', 'Publisher updated successfully.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return back()->with('error', 'This publisher is assigned to books. Reassign them before deleting it.');
        }

        DB::transaction(function () use ($brand): void {
            ActivityLogger::record('deleted', $brand, "Deleted publisher '{$brand->name}'.");
            $brand->delete();
        });

        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }

        return redirect()->route('admin.brands.index')->with('success', 'Publisher deleted successfully.');
    }
}
