<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $staticUrls = collect([
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => now()],
            ['loc' => route('localized.vi.about'), 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()],
            ['loc' => route('localized.vi.gallery.index'), 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()],
            ['loc' => route('localized.vi.menu.index'), 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => now()],
            ['loc' => route('localized.vi.blog.index'), 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => now()],
            ['loc' => route('localized.vi.reservations.create'), 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => now()],
            ['loc' => route('localized.vi.contact'), 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => now()],
        ]);

        $categories = Category::query()->active()->latest('updated_at')->get();
        $dishes = Dish::query()->active()->latest('updated_at')->get();
        $posts = Post::query()->published()->latest('updated_at')->get();
        $pages = Page::query()->active()->latest('updated_at')->get();

        return response()
            ->view('sitemap', compact('staticUrls', 'categories', 'dishes', 'posts', 'pages'))
            ->header('Content-Type', 'application/xml');
    }
}
