<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubcategoryRequest;
use App\Models\Category;
use App\Models\Subcategory;
use App\Support\ActivityLogger;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SubcategoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $subcategories = Subcategory::with('category')
            ->withCount('products')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('name', 'like', "%{$term}%"))
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when(isset($filters['status']), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.subcategories.index', [
            'subcategories' => $subcategories,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.subcategories.form', [
            'subcategory' => new Subcategory(['is_active' => true]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(SubcategoryRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['slug'] = UniqueSlug::make($data['name'], 'subcategories', null, (int) $data['category_id']);
        $data['image'] = $request->file('image')?->store('subcategories', 'public');

        DB::transaction(function () use ($data): void {
            $subcategory = Subcategory::create($data);
            ActivityLogger::record('created', $subcategory, "Created subcategory '{$subcategory->name}'.");
        });

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory created successfully.');
    }

    public function edit(Subcategory $subcategory): View
    {
        return view('admin.subcategories.form', [
            'subcategory' => $subcategory,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(SubcategoryRequest $request, Subcategory $subcategory): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'slug']);
        $data['slug'] = UniqueSlug::make($data['name'], 'subcategories', $subcategory->id, (int) $data['category_id']);
        $previousImage = $subcategory->image;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('subcategories', 'public');
        }

        DB::transaction(function () use ($subcategory, $data): void {
            $subcategory->update($data);
            ActivityLogger::record('updated', $subcategory, "Updated subcategory '{$subcategory->name}'.");
        });

        if ($request->hasFile('image') && $previousImage) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory updated successfully.');
    }

    public function destroy(Subcategory $subcategory): RedirectResponse
    {
        if ($subcategory->products()->exists()) {
            return back()->with('error', 'This subcategory still has books. Move them to another subcategory before deleting it.');
        }

        DB::transaction(function () use ($subcategory): void {
            ActivityLogger::record('deleted', $subcategory, "Deleted subcategory '{$subcategory->name}'.");
            $subcategory->delete();
        });

        if ($subcategory->image) {
            Storage::disk('public')->delete($subcategory->image);
        }

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory deleted successfully.');
    }
}
