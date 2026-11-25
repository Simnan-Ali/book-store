<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BannerGalleryRequest;
use App\Http\Requests\BannerImageRequest;
use App\Models\Banner;
use App\Models\BannerImage;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BannerImageController extends Controller
{
    public function store(BannerGalleryRequest $request, Banner $banner): RedirectResponse
    {
        $uploadedPaths = [];

        try {
            DB::transaction(function () use ($request, $banner, &$uploadedPaths): void {
                $sortOrder = (int) $banner->images()->max('sort_order') + 1;
                $altTexts = $request->input('alt_texts', []);

                foreach ($request->file('images') as $index => $image) {
                    $path = $this->storeImage($image);
                    $uploadedPaths[] = $path;

                    $banner->images()->create([
                        'image_path' => $path,
                        'alt_text' => $altTexts[$index] ?? null,
                        'sort_order' => $sortOrder++,
                        'status' => 'active',
                    ]);
                }

                ActivityLogger::record('updated', $banner, 'Added images to banner gallery.');
            });
        } catch (Throwable $exception) {
            $this->deleteStoredImages($uploadedPaths);
            throw $exception;
        }

        return back()->with('success', 'Gallery images added successfully.');
    }

    public function update(BannerImageRequest $request, Banner $banner, BannerImage $bannerImage): RedirectResponse
    {
        abort_unless($bannerImage->banner_id === $banner->id, 404);

        $data = $request->safe()->except('image');
        $previousPath = $bannerImage->image_path;
        $newPath = null;

        if ($request->hasFile('image')) {
            $newPath = $this->storeImage($request->file('image'));
            $data['image_path'] = $newPath;
        }

        try {
            DB::transaction(function () use ($banner, $bannerImage, $data): void {
                $bannerImage->update($data);
                ActivityLogger::record('updated', $banner, 'Updated a banner gallery image.');
            });
        } catch (Throwable $exception) {
            if ($newPath !== null) {
                $this->deleteStoredImages([$newPath]);
            }

            throw $exception;
        }

        if ($newPath !== null && $newPath !== $previousPath) {
            $this->deleteStoredImages([$previousPath]);
        }

        return back()->with('success', 'Gallery image updated successfully.');
    }

    public function destroy(Banner $banner, BannerImage $bannerImage): RedirectResponse
    {
        abort_unless($bannerImage->banner_id === $banner->id, 404);

        $path = $bannerImage->image_path;

        DB::transaction(function () use ($banner, $bannerImage): void {
            $bannerImage->delete();
            ActivityLogger::record('updated', $banner, 'Removed an image from the banner gallery.');
        });

        $this->deleteStoredImages([$path]);

        return back()->with('success', 'Gallery image removed successfully.');
    }

    public function makeMain(Banner $banner, BannerImage $bannerImage): RedirectResponse
    {
        abort_unless($bannerImage->banner_id === $banner->id, 404);

        $mainImagePath = $banner->main_image;
        $mainImageAlt = $banner->image_alt;
        $sortOrder = $bannerImage->sort_order;
        $galleryPath = $bannerImage->image_path;
        $galleryAlt = $bannerImage->alt_text;

        DB::transaction(function () use (
            $banner,
            $bannerImage,
            $mainImagePath,
            $mainImageAlt,
            $sortOrder,
            $galleryPath,
            $galleryAlt,
        ): void {
            $banner->update([
                'main_image' => $galleryPath,
                'image_alt' => $galleryAlt,
            ]);

            if ($mainImagePath) {
                $bannerImage->update([
                    'image_path' => $mainImagePath,
                    'alt_text' => $mainImageAlt,
                    'sort_order' => $sortOrder,
                    'status' => 'active',
                ]);
            } else {
                $bannerImage->delete();
            }

            ActivityLogger::record('updated', $banner, 'Changed the main banner image.');
        });

        return back()->with('success', 'Main banner image updated successfully.');
    }

    private function storeImage(UploadedFile $image): string
    {
        $path = $image->store('banners/gallery', 'public');

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Unable to store banner gallery image.');
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
