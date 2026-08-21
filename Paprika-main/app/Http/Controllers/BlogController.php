<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\SeoService;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $posts = Post::query()
            ->with(['category.translations', 'translations'])
            ->published()
            ->latest('published_at')
            ->paginate(6);

        $seo = SeoService::page(
            is_english() ? 'Paprika blog | Vietnamese cuisine in Patras' : 'Blog Paprika | Ẩm thực Việt Nam tại Patras',
            is_english() ? 'Updates from Paprika Patras about Vietnamese dishes, restaurant news, ordering and seasonal offers.' : 'Cập nhật từ Paprika Patras về món Việt, tin tức nhà hàng, đặt món và ưu đãi theo mùa.',
            is_english() ? 'Paprika Patras blog, Vietnamese cuisine Patras, pho Patras' : 'blog Paprika, ẩm thực Việt Nam Patras, phở Patras, bánh mì Patras',
            localized_route('blog.index')
        );

        $schemas = [
            SeoService::restaurantSchema(),
        ];

        return view('blog.index', compact('posts', 'seo', 'schemas'));
    }

    public function show(Post|string $post): View
    {
        if (! $post instanceof Post) {
            $post = Post::query()
                ->where('slug', $post)
                ->orWhereHas('translations', fn ($query) => $query->where('locale', current_locale())->where('slug', $post))
                ->firstOrFail();
        }

        abort_if(! $post->is_active || blank($post->published_at) || $post->published_at->isFuture(), 404);

        $post->load(['category.translations', 'translations']);

        $relatedPosts = Post::query()
            ->with(['category.translations', 'translations'])
            ->published()
            ->where('category_id', $post->category_id)
            ->whereKeyNot($post->getKey())
            ->latest('published_at')
            ->limit(3)
            ->get();

        $breadcrumbs = [
            ['label' => __('site.nav.home'), 'url' => localized_route('home')],
            ['label' => __('site.nav.blog'), 'url' => localized_route('blog.index')],
            ['label' => $post->localized('title')],
        ];

        $seo = SeoService::page(
            $post->localized('meta_title') ?: "{$post->localized('title')} | Paprika",
            $post->localized('meta_description') ?: $post->localized('excerpt'),
            $post->localized('meta_keywords') ?: "{$post->localized('title')}, Paprika Patras, Vietnamese cuisine Patras",
            localized_route('blog.show', ['slug' => $post->localizedSlug()]),
            $post->thumbnail,
            'article'
        );

        $schemas = [
            SeoService::restaurantSchema(),
            SeoService::articleSchema($post),
            SeoService::breadcrumbSchema($breadcrumbs),
        ];

        return view('blog.show', compact('post', 'relatedPosts', 'breadcrumbs', 'seo', 'schemas'));
    }
}
