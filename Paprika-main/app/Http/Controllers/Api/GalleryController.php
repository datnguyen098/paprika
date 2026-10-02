<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GalleryImage;
use Illuminate\Http\JsonResponse;

class GalleryController extends Controller
{
    public function index(): JsonResponse
    {
        $branches = Branch::query()
            ->active()
            ->with(['galleryImages' => function ($query): void {
                $query->with('translations')
                    ->active()
                    ->where('location', 'space')
                    ->orderByDesc('is_featured')
                    ->orderBy('sort_order')
                    ->latest();
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $sharedImages = GalleryImage::query()
            ->with(['translations', 'branch'])
            ->active()
            ->whereNull('branch_id')
            ->where('location', 'space')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest()
            ->get();

        $branchGroups = $branches
            ->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
                'images' => $branch->galleryImages
                    ->map(fn (GalleryImage $image): array => $this->imagePayload($image))
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $branch): bool => $branch['images'] !== [])
            ->values();

        $images = $branchGroups
            ->flatMap(fn (array $branch): array => $branch['images'])
            ->merge($sharedImages->map(fn (GalleryImage $image): array => $this->imagePayload($image)))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Lấy thư viện ảnh thành công',
            'data' => [
                'images' => $images,
                'branches' => $branchGroups,
                'shared_images' => $sharedImages
                    ->map(fn (GalleryImage $image): array => $this->imagePayload($image))
                    ->values()
                    ->all(),
            ],
        ]);
    }

    private function imagePayload(GalleryImage $image): array
    {
        $branch = $image->branch;

        return [
            'id' => $image->id,
            'title' => $image->localized('title'),
            'slug' => $image->localizedSlug(),
            'description' => $image->localized('description'),
            'alt_text' => $image->localized('alt_text') ?: $image->localized('title'),
            'image' => media_url($image->image),
            'is_featured' => (bool) $image->is_featured,
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name,
            ] : null,
        ];
    }
}
