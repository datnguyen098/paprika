<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pages = Page::query()
            ->with('translations')
            ->active()
            ->orderBy('title')
            ->paginate(min($request->integer('per_page', 20), 50));

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách trang thành công',
            'data' => $pages->getCollection()
                ->map(fn (Page $page): array => $this->summaryPayload($page))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
                'per_page' => $pages->perPage(),
                'total' => $pages->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $page = Page::query()
            ->with('translations')
            ->where('slug', $slug)
            ->orWhereHas('translations', fn ($query) => $query->where('slug', $slug))
            ->first();

        if (! $page || ! $page->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Trang không tìm thấy',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết trang thành công',
            'data' => $this->detailPayload($page),
        ]);
    }

    private function summaryPayload(Page $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->localized('title'),
            'slug' => $page->localizedSlug(),
            'excerpt' => Str::limit(strip_tags((string) $page->localized('content')), 160),
            'template' => $page->template,
            'image' => media_url($page->image),
        ];
    }

    private function detailPayload(Page $page): array
    {
        return $this->summaryPayload($page) + [
            'content' => $page->localized('content'),
            'seo' => [
                'title' => $page->localized('meta_title') ?: $page->localized('title'),
                'description' => $page->localized('meta_description'),
                'keywords' => $page->localized('meta_keywords'),
                'og_image' => media_url($page->image),
            ],
        ];
    }
}
