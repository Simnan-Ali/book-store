<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Support\ActivityLogger;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $categories = Category::query()
            ->withCount(['subcategories', 'products'])
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('name', 'like', "%{$term}%"))
            ->when(isset($filters['status']), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true])]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['slug'] = UniqueSlug::make($data['name'], 'categories');
        $data['image'] = $request->file('image')?->store('categories', 'public');

        DB::transaction(function () use ($data): void {
            $category = Category::create($data);
            ActivityLogger::record('created', $category, "Created category '{$category->name}'.");
        });

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['slug'] = UniqueSlug::make($data['name'], 'categories', $category->id);
        $previousImage = $category->image;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        DB::transaction(function () use ($category, $data): void {
            $category->update($data);
            ActivityLogger::record('updated', $category, "Updated category '{$category->name}'.");
        });

        if ($request->hasFile('image') && $previousImage) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->subcategories()->exists() || $category->products()->exists()) {
            return back()->with('error', 'This category still has subcategories or books. Move or remove them before deleting it.');
        }

        DB::transaction(function () use ($category): void {
            ActivityLogger::record('deleted', $category, "Deleted category '{$category->name}'.");
            $category->delete();
        });

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }
}
