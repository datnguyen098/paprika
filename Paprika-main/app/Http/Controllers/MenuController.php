<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Dish;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::dish()
            ->active()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $selectedCategory = $request->string('category')->toString();
        $search = $request->string('q')->toString();

        $dishes = Dish::query()
            ->with(['category.translations', 'translations'])
            ->active()
            ->when($selectedCategory, function ($query) use ($selectedCategory): void {
                $query->whereHas('category', function ($categoryQuery) use ($selectedCategory): void {
                    $categoryQuery->where('slug', $selectedCategory)
                        ->orWhereHas('translations', fn ($translationQuery) => $translationQuery->where('slug', $selectedCategory));
                });
            })
            ->search($search)
            ->orderBy('sort_order')
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $schemaCategories = Category::dish()
            ->active()
            ->with(['translations', 'dishes' => fn ($query) => $query->active()->with('translations')->orderBy('name')])
            ->orderBy('id')
            ->get();

        $seo = SeoService::page(
            is_english() ? 'Paprika menu in Patras | Vietnamese and Greek dishes' : 'Thực đơn Paprika Patras | Món Việt Nam và món Hy Lạp',
            is_english() ? 'Explore Paprika dishes in Patras with pho, banh mi, nem, grilled plates, salads and drinks.' : 'Khám phá thực đơn Paprika tại Patras với phở, bánh mì, nem, món nướng, salad và đồ uống.',
            is_english() ? 'Paprika menu Patras, Vietnamese food Patras, pho, banh mi, souvlaki, drinks' : 'thực đơn Paprika, món Việt Nam Patras, phở, bánh mì, nem, souvlaki, đồ uống',
            localized_route('menu.index')
        );

        $schemas = [
            SeoService::restaurantSchema(),
            SeoService::menuSchema($schemaCategories),
        ];

        return view('menu.index', compact('categories', 'dishes', 'selectedCategory', 'search', 'seo', 'schemas'));
    }

    public function show(Dish|string $dish): View
    {
        if (! $dish instanceof Dish) {
            $dish = Dish::query()
                ->where('slug', $dish)
                ->orWhereHas('translations', fn ($query) => $query->where('locale', current_locale())->where('slug', $dish))
                ->firstOrFail();
        }

        $dish->load(['category.translations', 'translations']);
        abort_unless($dish->is_active, 404);

        $relatedDishes = Dish::query()
            ->with(['category.translations', 'translations'])
            ->active()
            ->where('category_id', $dish->category_id)
            ->whereKeyNot($dish->getKey())
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        $pairingDishes = Dish::query()
            ->with(['category.translations', 'translations'])
            ->active()
            ->featured()
            ->whereKeyNot($dish->getKey())
            ->where('category_id', '!=', $dish->category_id)
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        $breadcrumbs = [
            ['label' => __('site.nav.home'), 'url' => localized_route('home')],
            ['label' => __('site.nav.menu'), 'url' => localized_route('menu.index')],
            ['label' => $dish->localized('name')],
        ];

        $seo = SeoService::page(
            $dish->localized('meta_title') ?: "{$dish->localized('name')} | Paprika Patras",
            $dish->localized('meta_description') ?: $dish->localized('description'),
            $dish->localized('meta_keywords') ?: "{$dish->localized('name')}, Paprika Patras, Vietnamese cuisine Patras",
            localized_route('menu.show', ['slug' => $dish->localizedSlug()]),
            $dish->image,
            'article'
        );

        $schemas = [
            SeoService::restaurantSchema(),
            SeoService::dishSchema($dish),
            SeoService::breadcrumbSchema($breadcrumbs),
        ];

        return view('menu.show', compact('dish', 'relatedDishes', 'pairingDishes', 'breadcrumbs', 'seo', 'schemas'));
    }
}
