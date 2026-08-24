<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Lấy danh sách danh mục món ăn (chỉ loại "dish")
     * 
     * API: GET /api/v1/categories
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // 1. Query categories có type = 'dish' (không lấy type = 'post')
        // 2. Chỉ lấy categories đang active (is_active = true)
        // 3. Sắp xếp theo sort_order, sau đó theo id
        $categories = Category::dish()
            ->active()
            ->with('translations')  // Load translation để lấy tên theo ngôn ngữ
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // 2. Transform data - trả về JSON theo format chuẩn
        $data = $categories->map(function (Category $category) {
            return [
                'id' => $category->id,
                'name' => $category->localized('name'),      // Tên theo ngôn ngữ hiện tại (vi/en/el)
                'slug' => $category->slug,                   // URL-friendly slug
                'description' => $category->localized('description'),
                'image' => $category->image
                    ? asset($category->image)  // Chuyển thành full URL
                    : null,
                'dishes_count' => $category->dishes()->active()->count(), // Đếm số món trong category
            ];
        });

        // 3. Trả về response JSON
        return response()->json([
            'success' => true,                    // Flag cho biết request thành công
            'message' => 'Lấy danh sách danh mục thành công',
            'data' => $data,
        ]);
    }
}
