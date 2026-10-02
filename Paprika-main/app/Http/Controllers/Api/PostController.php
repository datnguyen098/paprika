<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = Post::query()
            ->with(['category.translations', 'translations'])
            ->published()
            ->latest('published_at')
            ->paginate(min($request->integer('per_page', 10), 30));

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách bài viết thành công',
            'data' => $posts->getCollection()
                ->map(fn (Post $post): array => $this->summaryPayload($post))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $post = Post::query()
            ->with(['category.translations', 'translations'])
            ->where('slug', $slug)
            ->orWhereHas('translations', fn ($query) => $query->where('slug', $slug))
            ->first();

        if (! $post
            || ! $post->is_active
            || blank($post->published_at)
            || $post->published_at->isFuture()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Bài viết không tìm thấy',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết bài viết thành công',
            'data' => $this->detailPayload($post),
        ]);
    }

    private function summaryPayload(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->localized('title'),
            'slug' => $post->localizedSlug(),
            'excerpt' => $post->localized('excerpt')
                ?: Str::limit(strip_tags((string) $post->localized('content')), 160),
            'thumbnail' => media_url($post->thumbnail),
            'is_featured' => (bool) $post->is_featured,
            'published_at' => $post->published_at?->toIso8601String(),
            'category' => $post->category ? [
                'id' => $post->category->id,
                'name' => $post->category->localized('name'),
                'slug' => $post->category->localizedSlug(),
            ] : null,
        ];
    }

    private function detailPayload(Post $post): array
    {
        $related = Post::query()
            ->with(['category.translations', 'translations'])
            ->published()
            ->whereKeyNot($post->getKey())
            ->when(
                $post->category_id,
                fn ($query) => $query->where('category_id', $post->category_id)
            )
            ->latest('published_at')
            ->limit(3)
            ->get();

        return $this->summaryPayload($post) + [
            'content' => $post->localized('content'),
            'seo' => [
                'title' => $post->localized('meta_title') ?: $post->localized('title'),
                'description' => $post->localized('meta_description')
                    ?: $post->localized('excerpt'),
                'keywords' => $post->localized('meta_keywords'),
                'og_image' => media_url($post->thumbnail),
            ],
            'related_posts' => $related
                ->map(fn (Post $relatedPost): array => $this->summaryPayload($relatedPost))
                ->values()
                ->all(),
        ];
    }
}
