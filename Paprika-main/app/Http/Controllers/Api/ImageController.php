<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller để serve static files (ảnh) với CORS headers
 *
 * Vấn đề: php artisan serve serve static files trực tiếp không qua Laravel,
 * nên ảnh trong public/ không có Access-Control-Allow-Origin headers.
 * Fix: Dùng route này để serve ảnh qua Laravel middleware (có CORS).
 *
 * Usage: /api/v1/images/paprika/menu-catalog/item-001.jpg
 */
class ImageController extends Controller
{
    /**
     * Serve file với CORS headers
     *
     * @param Request $request
     * @param string $path Đường dẫn file, ví dụ: paprika/menu-catalog/item-001.jpg
     * @return StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function serve(Request $request, string $path): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        // Bảo mật: loại bỏ null bytes và path traversal
        $path = str_replace(chr(0), '', $path);
        $path = preg_replace('#\.\.#', '', $path) ?? '';

        // Chỉ cho phép các thư mục chính
        $allowedPrefixes = ['paprika/', 'images/', 'uploads/'];
        $isAllowed = false;

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Invalid path',
            ], 403);
        }

        // Kiểm tra file tồn tại trong storage/app/public/
        if (!Storage::disk('public')->exists($path)) {
            // Thử trong public/ nếu không có trong storage
            $publicPath = public_path($path);
            if (!file_exists($publicPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found',
                ], 404);
            }

            // Serve từ public/
            $file = file_get_contents($publicPath);
            $mimeType = mime_content_type($publicPath);

            return $this->streamWithCors($file, $mimeType);
        }

        // Serve từ storage/app/public/
        $file = Storage::disk('public')->get($path);
        $mimeType = Storage::disk('public')->mimeType($path);

        return $this->streamWithCors($file, $mimeType);
    }

    /**
     * Stream file với CORS headers
     *
     * @param string $content Nội dung file
     * @param string $mimeType MIME type
     * @return StreamedResponse
     */
    protected function streamWithCors(string $content, string $mimeType): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($content) {
            echo $content;
        });

        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Length', strlen($content));

        // ========== CORS HEADERS ==========
        // Lấy origin từ request (hoặc fallback *)
        $origin = request()->header('Origin', '*');

        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '86400');
        $response->headers->set('Cache-Control', 'public, max-age=31536000'); // Cache 1 năm

        return $response;
    }
}
