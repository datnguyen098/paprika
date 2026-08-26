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
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $dish = Dish::query()
            ->with([
                'category',
                'translations',
                'activeOptionGroups.options',
                'activeOptionGroups.translations',
                'timeSlots',
            ])
            ->find($id);

        if (!$dish || !$dish->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Món ăn không tìm thấy',
            ], 404);
        }

        // Thông tin availability
        $branch = active_branch();
        $availability = $branch ? $this->availability->check($dish, $branch) : null;

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết món ăn thành công',
            'data' => [
                'id' => $dish->id,
                'name' => $dish->localized('name'),
                'slug' => $dish->slug,
                'description' => $dish->localized('description'),
                'content' => $dish->localized('content'),
                'ingredients' => $dish->localized('ingredients'),
                'price' => (int) $dish->price,
                'sale_price' => $dish->sale_price ? (int) $dish->sale_price : null,
                'image' => $dish->image ? asset($dish->image) : null,
                'gallery' => $dish->gallery 
                    ? collect($dish->gallery)->map(fn ($img) => asset($img))->all() 
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
                        'options' => $group->options->map(fn ($opt) => [
                            'id' => $opt->id,
                            'name' => $opt->localized('name'),
                            'price_delta' => (int) $opt->price_delta,
                            'is_default' => $opt->is_default,
                        ]),
                    ];
                }),
            ],
        ]);
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
            'image' => $dish->image ? asset($dish->image) : null,
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
