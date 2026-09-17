<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class AboutController extends Controller
{
    /**
     * Slug ứng với từng locale cho trang About.
     *
     * Slug gốc trong bảng `pages` là tiếng Việt (`gioi-thieu`).
     * Bản dịch EN/EL nằm trong bảng `page_translations` với slug
     * tương ứng (`about`, `schetikos`) — Controller quét cả 3 slug
     * để không phụ thuộc vào việc BE có dịch sang ngôn ngữ đó hay
     * chưa.
     */
    private const ABOUT_SLUGS = ['gioi-thieu', 'about', 'schetikos'];

    /**
     * GET /api/v1/about
     *
     * Đọc trực tiếp từ bảng `pages` (slug ∈ {gioi-thieu, about, schetikos}).
     * Trả về đúng 3 cột tồn tại trong bảng: title, content, image.
     * Khi DB chưa có dữ liệu, trả { success: true, data: null }.
     */
    public function index(): JsonResponse
    {
        // Lấy trang About từ bảng `pages` (slug ∈ {gioi-thieu, about, schetikos}).
        // Ưu tiên slug VI gốc (`gioi-thieu`) rồi EN (`about`) rồi EL (`schetikos`).
        // SQLite không hỗ trợ FIELD() nên dùng CASE WHEN.
        $page = Page::query()
            ->whereIn('slug', self::ABOUT_SLUGS)
            ->where('is_active', true)
            ->orderByRaw(
                "CASE slug "
                ."WHEN 'gioi-thieu' THEN 1 "
                ."WHEN 'about'     THEN 2 "
                ."WHEN 'schetikos' THEN 3 "
                ."ELSE 4 END"
            )
            ->first();

        if (! $page) {
            return response()->json([
                'success' => true,
                'data'    => null,
            ]);
        }

        // Trả về đúng 3 cột tồn tại trong bảng `pages`:
        //   title     → tiêu đề trang (từ DB, có localized fallback)
        //   content   → nội dung trang (từ DB, có localized fallback)
        //   image     → ảnh bìa trang (từ DB)
        return response()->json([
            'success' => true,
            'data'    => [
                'title'   => $page->localized('title', $page->title),
                'content' => $page->localized('content', $page->content),
                'image'   => $page->image,
            ],
        ]);
    }

}
