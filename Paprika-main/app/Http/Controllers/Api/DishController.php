<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\DishOptionGroup;
use App\Support\DishAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DishController extends Controller
{
    protected DishAvailabilityService $availability;

    public function __construct(DishAvailabilityService $availability)
    {
        $this->availability = $availability;
    }

    /**
     * Tạo URL ảnh với CORS headers qua route /api/v1/images/
     *
     * Fix CORS cho Flutter web: php artisan serve không thêm CORS headers
     * cho static files, nên dùng route này để serve ảnh.
     *
     * @param string|null $path Đường dẫn file, ví dụ: /paprika/menu-catalog/item-001.jpg
     * @return string|null
     */
    protected function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // Bỏ leading slash nếu có
        $path = ltrim($path, '/');

        // Convert sang route mới có CORS
        return url("/api/v1/images/{$path}");
    }

    /**
     * Lấy danh sách món ăn (có filter)
     * 
     * API: GET /api/v1/menu
     * Query params: category_id, category (slug), q (search), featured, sort, dir, page, per_page
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Dish::query()
            ->with(['category', 'translations'])
            ->active();

        // Filter theo category_id
        if ($request->has('category_id') && $request->integer('category_id') > 0) {
            $query->where('category_id', $request->integer('category_id'));
        }

        // Filter theo category slug (alternative)
        if ($request->has('category')) {
            $categorySlug = $request->string('category')->toString();
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug)
                    ->orWhereHas('translations', fn ($tq) => $tq->where('slug', $categorySlug));
            });
        }

        // Search
        if ($request->has('q') && $request->string('q')->isNotEmpty()) {
            $search = $request->string('q')->toString();
            $query->search($search);
        }

        // Featured only
        if ($request->boolean('featured')) {
            $query->featured();
        }

        // Sắp xếp
        $sortBy = $request->string('sort', 'sort_order')->toString();
        $sortDir = $request->string('dir', 'asc')->toString();
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = min($request->integer('per_page', 20), 50);
        $dishes = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách món ăn thành công',
            'data' => $dishes->map(fn ($dish) => $this->transformDishSummary($dish)),
            'meta' => [
                'current_page' => $dishes->currentPage(),
                'last_page' => $dishes->lastPage(),
                'per_page' => $dishes->perPage(),
                'total' => $dishes->total(),
            ],
        ]);
    }

    /**
     * Món nổi bật
     * 
     * API: GET /api/v1/dishes/featured
     * Query params: limit (default: 10)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        
        $dishes = Dish::query()
            ->with(['category'])
            ->active()
            ->featured()
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách món nổi bật thành công',
            'data' => $dishes->map(fn ($dish) => $this->transformDishSummary($dish)),
        ]);
    }

    /**
     * Chi tiết món ăn
     *
     * API: GET /api/v1/dishes/{id}
     *
     * Response đầy đủ để Flutter render trang chi tiết (mirror với
     * storefront/menu/show.blade.php):
     *   - id/name/slug/description/content/ingredients
     *   - price/sale_price/image/gallery + image_srcset + image_fallback
     *   - is_featured + category
     *   - availability.available/label/time_slots
     *   - options (option groups + options)
     *   - nutrition (calories + macros, port từ hardcoded table ở blade)
     *   - allergens (gluten/dairy/soy/sesame/egg/mustard, port từ blade)
     *   - related_dishes (tối đa 4 món, deduped related + pairing)
     *   - breadcrumbs, seo, prep_label/branch_label (cho stats row)
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $dish = Dish::query()
            ->with([
                'category.translations',
                'translations',
                'activeOptionGroups.options.translations',
                'timeSlots',
            ])
            ->find($id);

        if (!$dish || !$dish->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Món ăn không tìm thấy',
            ], 404);
        }

        // Availability theo branch đang active (nếu có).
        $branch = active_branch();
        $availability = $branch ? $this->availability->check($dish, $branch) : null;

        // Related + pairing (giống Storefront\MenuController::show).
        $relatedDishes = $this->resolveRelatedDishes($dish);

        // SEO meta.
        $dishName = $dish->localized('name');
        $ogImage = media_variant_path($dish->image, 'hero') ?? $dish->image;
        $seo = [
            'title' => $dish->localized('meta_title') ?: "{$dishName} | Paprika Patras",
            'description' => $dish->localized('meta_description') ?: $dish->localized('description'),
            'keywords' => $dish->localized('meta_keywords') ?: "Paprika Patras, {$dishName}",
            'og_image' => $this->imageUrl($ogImage),
        ];

        // Breadcrumbs.
        $breadcrumbs = [
            ['label' => __('site.dish_detail.breadcrumb_home'), 'url' => url('/'.current_locale())],
            ['label' => __('site.dish_detail.breadcrumb_menu'), 'url' => url('/'.current_locale().'/thuc-don')],
            ['label' => $dishName, 'url' => null],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết món ăn thành công',
            'data' => [
                'id' => $dish->id,
                'name' => $dishName,
                'slug' => $dish->slug,
                'description' => $dish->localized('description'),
                'content' => $dish->localized('content'),
                'ingredients' => $dish->localized('ingredients'),
                'price' => (int) $dish->price,
                'sale_price' => $dish->sale_price ? (int) $dish->sale_price : null,
                'image' => $this->imageUrl($dish->image),
                'image_srcset' => $this->buildImageSrcset($dish->image),
                'image_fallback' => $this->imageUrl(media_variant_path($dish->image, 'card')) ?? $this->imageUrl('paprika/cover.jpg'),
                'gallery' => $dish->gallery
                    ? collect($dish->gallery)->map(fn ($img) => $this->imageUrl($img))->all()
                    : [],
                'is_featured' => $dish->is_featured,
                'category' => [
                    'id' => $dish->category->id,
                    'name' => $dish->category->localized('name'),
                    'slug' => $dish->category->slug,
                ],
                'availability' => [
                    'available' => $availability?->available ?? true,
                    'label' => $availability?->label(),
                    'time_slots' => $dish->timeSlots->map(fn ($slot) => [
                        'id' => $slot->id,
                        'name' => $slot->localized('name'),
                        'start_time' => $slot->pivot->start_time ?? null,
                        'end_time' => $slot->pivot->end_time ?? null,
                    ]),
                ],
                'options' => $dish->activeOptionGroups->map(function (DishOptionGroup $group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->localized('name'),
                        'description' => $group->localized('description'),
                        'type' => $group->type,
                        'is_required' => $group->is_required,
                        'min_select' => $group->min_select,
                        'max_select' => $group->max_select,
                        'hint' => match ($group->type) {
                            DishOptionGroup::TYPE_SINGLE => __('site.dish_detail.hint_single'),
                            DishOptionGroup::TYPE_EXCLUDE => __('site.dish_detail.hint_exclude'),
                            default => __('site.dish_detail.hint_multiple'),
                        },
                        'options' => $group->options->map(fn ($opt) => [
                            'id' => $opt->id,
                            'name' => $opt->localized('name'),
                            'description' => $opt->localized('description'),
                            'price_delta' => (int) $opt->price_delta,
                            'is_default' => $opt->is_default,
                        ]),
                    ];
                }),
                'nutrition' => $this->buildNutrition($dish),
                'allergens' => $this->buildAllergens($dish),
                'related_dishes' => $relatedDishes->map(fn (Dish $d) => $this->transformDishSummary($d))->values(),
                'stats' => [
                    'prep_label' => __('site.dish_detail.prep'),
                    'prep_value' => __('site.dish_detail.prep_value'),
                    'energy_label' => __('site.dish_detail.energy'),
                    'energy_value' => $this->buildNutrition($dish)['calories'].' kcal',
                    'branch_label' => __('site.dish_detail.branch'),
                    'branch_value' => $branch?->name ?? __('site.dish_detail.branch_value'),
                ],
                'breadcrumbs' => $breadcrumbs,
                'seo' => $seo,
            ],
        ]);
    }

    /**
     * Resolve related + pairing dishes (mirror Storefront\MenuController::show).
     * Trả về tối đa 4 món distinct.
     */
    protected function resolveRelatedDishes(Dish $dish): \Illuminate\Support\Collection
    {
        $related = Dish::query()
            ->with(['category.translations', 'translations', 'activeOptionGroups.options.translations'])
            ->active()
            ->where('category_id', $dish->category_id)
            ->whereKeyNot($dish->getKey())
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        $pairing = Dish::query()
            ->with(['category.translations', 'translations', 'activeOptionGroups.options.translations'])
            ->active()
            ->featured()
            ->whereKeyNot($dish->getKey())
            ->where('category_id', '!=', $dish->category_id)
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        return $related->merge($pairing)->unique('id')->take(4);
    }

    /**
     * Build srcset array từ image variants (card / large / hero).
     * Mỗi entry: { variant, url, width }.
     */
    protected function buildImageSrcset(?string $imagePath): array
    {
        if (!$imagePath) {
            return [];
        }

        $variants = [
            ['variant' => 'card', 'width' => 480],
            ['variant' => 'large', 'width' => 960],
            ['variant' => 'hero', 'width' => 1600],
        ];

        return collect($variants)->map(function ($v) use ($imagePath) {
            $variantPath = media_variant_path($imagePath, $v['variant']);
            return [
                'variant' => $v['variant'],
                // Route qua /api/v1/images/ để có CORS + trả absolute URL cho Flutter.
                'url' => $this->imageUrl($variantPath),
                'width' => $v['width'],
            ];
        })->all();
    }

    /**
     * Port từ storefront/menu/show.blade.php - tính calories/macros theo slug.
     *
     * Cấu trúc trả về:
     *   - calories: int (kcal)
     *   - rows: [{ key, label, value }]
     */
    protected function buildNutrition(Dish $dish): array
    {
        $slug = $dish->slug;
        $categorySlug = $dish->category?->slug ?? 'do-an-viet-nam';

        $caloriesTable = [
            'beef-pho' => 540,
            'chicken-pho' => 470,
            'fried-nem' => 410,
            'pho-rolls' => 320,
            'banh-mi' => 520,
            'greek-salad' => 360,
            'souvlaki-skewers' => 390,
            'gyros' => 640,
            'bifteki' => 610,
            'lamb-chops' => 720,
            'mineral-water' => 0,
            'soft-drink' => 140,
            'iced-tea' => 110,
            'greek-coffee' => 60,
        ];

        $calories = $caloriesTable[$slug] ?? ($categorySlug === 'do-uong' ? 120 : 520);
        $factor = max($calories, 80) / 1000;

        return [
            'calories' => $calories,
            'rows' => [
                ['key' => 'energy', 'label' => __('site.dish_detail.nutrition_energy'), 'value' => number_format($calories * 4.184, 0, ',', '.').' kJ / '.$calories.' kcal'],
                ['key' => 'fat', 'label' => __('site.dish_detail.nutrition_fat'), 'value' => number_format(30 * $factor, 1, ',', '.').' g'],
                ['key' => 'carbs', 'label' => __('site.dish_detail.nutrition_carbs'), 'value' => number_format(46 * $factor, 1, ',', '.').' g'],
                ['key' => 'protein', 'label' => __('site.dish_detail.nutrition_protein'), 'value' => number_format(24 * $factor, 1, ',', '.').' g'],
                ['key' => 'salt', 'label' => __('site.dish_detail.nutrition_salt'), 'value' => number_format(1.7 * $factor, 2, ',', '.').' g'],
            ],
        ];
    }

    /**
     * Port từ storefront/menu/show.blade.php - 6 allergens chính.
     *
     * Cấu trúc trả về:
     *   [{ key, name, contains, warning }]
     */
    protected function buildAllergens(Dish $dish): array
    {
        $slug = $dish->slug;
        $categorySlug = $dish->category?->slug ?? '';

        return [
            ['key' => 'gluten', 'name' => __('site.dish_detail.allergen_gluten'), 'contains' => in_array($slug, ['banh-mi', 'gyros', 'fried-nem'], true)],
            ['key' => 'dairy', 'name' => __('site.dish_detail.allergen_dairy'), 'contains' => in_array($slug, ['greek-salad', 'gyros'], true)],
            ['key' => 'soy', 'name' => __('site.dish_detail.allergen_soy'), 'contains' => $categorySlug === 'do-an-viet-nam'],
            ['key' => 'sesame', 'name' => __('site.dish_detail.allergen_sesame'), 'contains' => in_array($slug, ['banh-mi', 'pho-rolls', 'souvlaki-skewers'], true)],
            ['key' => 'egg', 'name' => __('site.dish_detail.allergen_egg'), 'contains' => in_array($slug, ['fried-nem', 'banh-mi'], true)],
            ['key' => 'mustard', 'name' => __('site.dish_detail.allergen_mustard'), 'contains' => $categorySlug === 'do-an-hy-lap'],
        ];
    }

    /**
     * Tìm kiếm món ăn
     * 
     * API: GET /api/v1/dishes/search?q=keyword
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $keyword = $request->string('q', '')->toString();
        
        if (strlen($keyword) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Từ khóa quá ngắn (tối thiểu 2 ký tự)',
            ]);
        }

        $dishes = Dish::query()
            ->with(['category'])
            ->active()
            ->search($keyword)
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Tìm kiếm thành công',
            'data' => $dishes->map(fn ($dish) => $this->transformDishSummary($dish)),
        ]);
    }

    /**
     * Transform dish cho list (summary)
     * 
     * @param Dish $dish
     * @return array
     */
    protected function transformDishSummary(Dish $dish): array
    {
        $branch = active_branch();
        $availability = $branch ? $this->availability->check($dish, $branch) : null;

        return [
            'id' => $dish->id,
            'name' => $dish->localized('name'),
            'slug' => $dish->slug,
            'description' => $dish->localized('description') 
                ? Str::limit(strip_tags($dish->localized('description')), 100) 
                : null,
            'price' => (int) $dish->price,
            'sale_price' => $dish->sale_price ? (int) $dish->sale_price : null,
                'image' => $this->imageUrl($dish->image),
            'is_featured' => $dish->is_featured,
            'is_available' => $availability?->available ?? true,
            'availability_label' => $availability?->label(),
            'category' => $dish->relationLoaded('category') && $dish->category ? [
                'id' => $dish->category->id,
                'name' => $dish->category->localized('name'),
                'slug' => $dish->category->slug,
            ] : null,
        ];
    }
}
