<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Dish;
use App\Models\GalleryImage;
use App\Models\Promotion;
use App\Services\SeoService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $hero = Banner::active()
            ->with('translations')
            ->where('position', 'home')
            ->orderBy('sort_order')
            ->first();

        $categories = Category::dish()
            ->active()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(6)
            ->get();

        $featuredDishes = Dish::query()
            ->with(['category.translations', 'translations'])
            ->active()
            ->featured()
            ->orderBy('sort_order')
            ->latest()
            ->limit(8)
            ->get();

        $promotions = Promotion::query()
            ->with('translations')
            ->active()
            ->current()
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        $branchId = active_branch_id();
        $homeGalleryImages = GalleryImage::query()
            ->with(['branch', 'translations'])
            ->active()
            ->where('location', 'space')
            ->where(function ($query) use ($branchId): void {
                $query->whereNull('branch_id');

                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest()
            ->limit(3)
            ->get();

        if ($homeGalleryImages->isEmpty()) {
            $homeGalleryImages = GalleryImage::query()
                ->with(['branch', 'translations'])
                ->active()
                ->where('location', 'space')
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->latest()
                ->limit(3)
                ->get();
        }

        $seo = SeoService::page(
            setting('default_meta_title', 'Paprika'),
            setting('default_meta_description', 'Order Paprika Vietnamese dishes, grilled plates and drinks online in Patras.'),
            setting('default_meta_keywords', 'paprika, Vietnamese food, Greek grilled dishes, online order'),
            localized_route('home')
        );

        return view('storefront.home', compact('hero', 'categories', 'featuredDishes', 'promotions', 'homeGalleryImages', 'seo'));
    }
}
