<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
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
            ->with(['category.translations', 'translations', 'activeOptionGroups.options.translations'])
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
            ->paginate(24)
            ->withQueryString();

        $seo = SeoService::page(
            'Menu | Paprika',
            'Browse the Paprika menu and start an order.',
            'menu, order online, Paprika',
            localized_route('menu.index')
        );

        return view('storefront.menu.index', compact('categories', 'dishes', 'selectedCategory', 'search', 'seo'));
    }

    public function show(Dish|string $dish): View
    {
        if (! $dish instanceof Dish) {
            $dish = Dish::query()
                ->where('slug', $dish)
                ->orWhereHas('translations', fn ($query) => $query->where('locale', current_locale())->where('slug', $dish))
                ->firstOrFail();
        }

        $dish->load(['category.translations', 'translations', 'activeOptionGroups.activeOptions']);
        abort_unless($dish->is_active, 404);

        $relatedDishes = Dish::query()
            ->with(['category.translations', 'translations', 'activeOptionGroups.options.translations'])
            ->active()
            ->where('category_id', $dish->category_id)
            ->whereKeyNot($dish->getKey())
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        $pairingDishes = Dish::query()
            ->with(['category.translations', 'translations', 'activeOptionGroups.options.translations'])
            ->active()
            ->featured()
            ->whereKeyNot($dish->getKey())
            ->where('category_id', '!=', $dish->category_id)
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        $breadcrumbs = [
            ['label' => 'Trang chủ', 'url' => localized_route('home')],
            ['label' => 'Thực đơn', 'url' => localized_route('menu.index')],
            ['label' => $dish->localized('name')],
        ];

        $dishName = $dish->localized('name');

        $seo = SeoService::page(
            $dish->localized('meta_title') ?: "{$dishName} | Paprika Patras",
            $dish->localized('meta_description') ?: $dish->localized('description'),
            $dish->localized('meta_keywords') ?: "Paprika Patras, {$dishName}, Vietnamese cuisine Patras",
            localized_route('menu.show', ['slug' => $dish->localizedSlug()]),
            $dish->image,
            'article'
        );

        $schemas = [
            SeoService::restaurantSchema(),
            SeoService::dishSchema($dish),
            SeoService::breadcrumbSchema($breadcrumbs),
        ];

        return view('storefront.menu.show', compact('dish', 'relatedDishes', 'pairingDishes', 'breadcrumbs', 'seo', 'schemas'));
    }
}
