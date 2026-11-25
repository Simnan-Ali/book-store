<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BannerRequest;
use App\Models\Banner;
use App\Models\BannerImage;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class BannerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'type' => ['nullable', 'in:main,promotional,other'],
            'sort' => ['nullable', 'in:order_asc,order_desc,latest,oldest,title'],
        ]);

        $sort = $filters['sort'] ?? 'order_asc';
        $banners = Banner::query()
            ->withCount('images')
            ->when($filters['q'] ?? null, fn ($query, string $term) => $query
                ->where(fn ($query) => $query
                    ->where('title', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when($sort === 'order_desc', fn ($query) => $query->orderByDesc('sort_order'))
            ->when($sort === 'order_asc', fn ($query) => $query->orderBy('sort_order'))
            ->when($sort === 'latest', fn ($query) => $query->latest())
            ->when($sort === 'oldest', fn ($query) => $query->oldest())
            ->when($sort === 'title', fn ($query) => $query->orderBy('title'))
            ->when(in_array($sort, ['order_asc', 'order_desc'], true), fn ($query) => $query->orderBy('id'))
            ->paginate(10)
            ->withQueryString();

        return view('admin.banners.index', compact('banners', 'filters'));
    }

    public function create(): View
    {
        return view('admin.banners.form', [
            'banner' => new Banner([
                'type' => 'main',
                'status' => 'active',
                'sort_order' => 0,
                'show_button' => false,
                'open_new_tab' => false,
                'text_alignment' => 'left',
            ]),
        ]);
    }

    public function store(BannerRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['main_image', 'gallery_images', 'gallery_alt_texts', 'remove_main_image']);
        $uploadedPaths = [];
        $data['main_image'] = $this->storeImage($request->file('main_image'), 'banners/main');
        $uploadedPaths[] = $data['main_image'];

        try {
            DB::transaction(function () use ($request, $data, &$uploadedPaths): void {
                $banner = Banner::create($data);
                $this->storeGalleryImages($request, $banner, $uploadedPaths);
                ActivityLogger::record('created', $banner, "Created banner '{$banner->name}'.");
            });
        } catch (Throwable $exception) {
            $this->deleteStoredImages($uploadedPaths);
            throw $exception;
        }

        $banner = Banner::where('slug', $data['slug'])->firstOrFail();

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner created. You can manage its gallery below.');
    }

    public function edit(Banner $banner): View
    {
        $banner->load('images');

        return view('admin.banners.form', compact('banner'));
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $data = $request->safe()->except(['main_image', 'gallery_images', 'gallery_alt_texts', 'remove_main_image']);
        $newPaths = [];
        $oldPaths = [];
        $previousMainImage = $banner->main_image;

        if ($request->hasFile('main_image')) {
            $data['main_image'] = $this->storeImage($request->file('main_image'), 'banners/main');
            $newPaths[] = $data['main_image'];
        } elseif ($request->boolean('remove_main_image')) {
            $data['main_image'] = null;
            $data['image_alt'] = null;
        }

        try {
            DB::transaction(function () use ($request, $banner, $data, &$newPaths): void {
                $banner->update($data);

                $this->storeGalleryImages($request, $banner, $newPaths);
                ActivityLogger::record('updated', $banner, "Updated banner '{$banner->name}'.");
            });
        } catch (Throwable $exception) {
            $this->deleteStoredImages($newPaths);
            throw $exception;
        }

        if (($request->hasFile('main_image') || $request->boolean('remove_main_image')) && $previousMainImage) {
            $oldPaths[] = $previousMainImage;
        }

        $this->deleteStoredImages(array_unique($oldPaths));

        return redirect()->route('admin.banners.edit', $banner)
            ->with('success', 'Banner updated successfully.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $paths = $banner->images()->pluck('image_path')->all();
        $paths[] = $banner->main_image;

        DB::transaction(function () use ($banner): void {
            ActivityLogger::record('deleted', $banner, "Deleted banner '{$banner->name}'.");
            $banner->images()->delete();
            $banner->delete();
        });

        $this->deleteStoredImages($paths);

        return redirect()->route('admin.banners.index')->with('success', 'Banner deleted successfully.');
    }

    public function toggleStatus(Banner $banner): RedirectResponse
    {
        $status = $banner->status === 'active' ? 'inactive' : 'active';

        DB::transaction(function () use ($banner, $status): void {
            $banner->update(['status' => $status]);
            ActivityLogger::record(
                'updated',
                $banner,
                ucfirst($status)." banner '{$banner->name}'.",
            );
        });

        return back()->with('success', "Banner {$banner->name} ".($status === 'active' ? 'activated' : 'deactivated').'.');
    }

    private function storeGalleryImages(BannerRequest $request, Banner $banner, array &$uploadedPaths): void
    {
        $sortOrder = (int) $banner->images()->max('sort_order') + 1;
        $altTexts = $request->input('gallery_alt_texts', []);

        foreach ($request->file('gallery_images', []) as $index => $image) {
            $path = $this->storeImage($image, 'banners/gallery');
            $uploadedPaths[] = $path;

            BannerImage::create([
                'banner_id' => $banner->id,
                'image_path' => $path,
                'alt_text' => $altTexts[$index] ?? null,
                'sort_order' => $sortOrder++,
                'status' => 'active',
            ]);
        }
    }

    private function storeImage(UploadedFile $image, string $directory): string
    {
        $path = $image->store($directory, 'public');

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Unable to store banner image.');
        }

        return $path;
    }

    private function deleteStoredImages(array $paths): void
    {
        foreach (array_filter(array_unique($paths)) as $path) {
            if (! Storage::disk('public')->delete($path)) {
                throw new RuntimeException("Unable to remove stored banner image '{$path}'.");
            }
        }
    }
}
