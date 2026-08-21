<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\SeoService;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return $this->show('gioi-thieu');
    }

    public function show(Page|string $page): View
    {
        if (! $page instanceof Page) {
            $page = Page::query()
                ->where('slug', $page)
                ->orWhereHas('translations', fn ($query) => $query->where('locale', current_locale())->where('slug', $page))
                ->firstOrFail();
        }

        abort_unless($page->is_active, 404);
        $page->load('translations');

        $seo = SeoService::page(
            $page->localized('meta_title') ?: $page->localized('title'),
            $page->localized('meta_description'),
            $page->localized('meta_keywords') ?: "{$page->localized('title')}, Paprika Patras",
            localized_route('pages.show', ['slug' => $page->localizedSlug()]),
            $page->image
        );

        $schemas = [
            SeoService::restaurantSchema(),
        ];

        return view('page', compact('page', 'seo', 'schemas'));
    }
}
