<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Branch;
use App\Models\Dish;
use App\Models\GalleryImage;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Testimonial;
use App\Services\SeoService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featuredDishes = Dish::query()
            ->with(['category.translations', 'translations'])
            ->active()
            ->featured()
            ->orderBy('sort_order')
            ->latest()
            ->limit(6)
            ->get();

        $latestPosts = Post::query()
            ->with(['category.translations', 'translations'])
            ->published()
            ->latest('published_at')
            ->limit(3)
            ->get();

        $banners = Banner::active()
            ->with('translations')
            ->where('position', 'home')
            ->orderBy('sort_order')
            ->get();

        $testimonials = Testimonial::active()
            ->with('translations')
            ->orderBy('sort_order')
            ->latest()
            ->limit(10)
            ->get();

        $homePromotions = Promotion::current()
            ->with('translations')
            ->where('placement', 'home')
            ->orderBy('sort_order')
            ->latest()
            ->limit(4)
            ->get();

        $homeGalleryImages = GalleryImage::query()
            ->with('translations')
            ->active()
            ->featured()
            ->orderBy('sort_order')
            ->latest()
            ->limit(5)
            ->get();

        $branches = Branch::query()
            ->active()
            ->orderBy('sort_order')
            ->get();

        $seo = SeoService::page(
            is_english() ? 'Paprika Patras | Vietnamese cuisine and grilled dishes' : setting('default_meta_title', 'Paprika Patras | Ẩm thực Việt Nam và món nướng Hy Lạp'),
            is_english() ? 'Paprika serves Vietnamese cuisine, pho, banh mi, nem, Greek grilled dishes and easy table booking in Patras.' : setting('default_meta_description', 'Paprika phục vụ ẩm thực Việt Nam, phở, bánh mì, nem, món nướng Hy Lạp và đặt bàn tiện lợi tại Patras.'),
            is_english() ? 'Paprika Patras, Vietnamese cuisine Patras, pho Patras, banh mi, Greek grilled dishes' : setting('default_meta_keywords', 'Paprika Patras, ẩm thực Việt Nam Patras, phở, bánh mì, nem, món nướng Hy Lạp, đặt bàn Paprika'),
            localized_route('home')
        );

        $schemas = [
            SeoService::restaurantSchema(),
        ];

        return view('home', compact('featuredDishes', 'latestPosts', 'testimonials', 'homePromotions', 'homeGalleryImages', 'banners', 'branches', 'seo', 'schemas'));
    }
}
