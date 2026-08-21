<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\GalleryImage;
use App\Services\SeoService;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function __invoke(): View
    {
        $branches = Branch::query()
            ->active()
            ->with(['galleryImages' => function ($query): void {
                $query->with('translations')
                    ->active()
                    ->where('location', 'space')
                    ->orderBy('sort_order')
                    ->latest();
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $sharedImages = GalleryImage::query()
            ->with('translations')
            ->active()
            ->whereNull('branch_id')
            ->where('location', 'space')
            ->orderBy('sort_order')
            ->latest()
            ->get();

        $firstImage = $branches->flatMap->galleryImages->first() ?: $sharedImages->first();

        $seo = SeoService::page(
            __('site.gallery_page.meta_title'),
            __('site.gallery_page.meta_description'),
            __('site.gallery_page.meta_keywords'),
            localized_route('gallery.index'),
            $firstImage?->image
        );

        $schemas = [
            SeoService::restaurantSchema(),
        ];

        return view('gallery.index', compact('branches', 'sharedImages', 'seo', 'schemas'));
    }
}
