<?php

namespace App\Support;

use App\Models\NavigationMenu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class StorefrontNavigation
{
    public static function forLocation(string $location): Collection
    {
        $menus = NavigationMenu::query()
            ->with(['translations', 'children.translations'])
            ->active()
            ->location($location)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($menus->isNotEmpty()) {
            return $menus->map(fn (NavigationMenu $menu): array => self::fromModel($menu));
        }

        return collect(self::fallback($location));
    }

    public static function resolveUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return localized_route('home');
        }

        if (str_starts_with($url, 'route:')) {
            $routeName = trim(substr($url, 6));
            $localizedName = 'localized.'.current_locale().'.'.$routeName;

            if (Route::has($localizedName) || Route::has($routeName)) {
                return localized_route($routeName);
            }

            return localized_route('home');
        }

        if (preg_match('/^(https?:)?\/\//i', $url) || preg_match('/^(mailto|tel):/i', $url) || str_starts_with($url, '#')) {
            return $url;
        }

        return localized_url($url);
    }

    public static function isActive(array $item): bool
    {
        $active = $item['active'] ?? null;

        if ($active && request()->routeIs($active)) {
            return true;
        }

        $path = parse_url($item['url'] ?? '', PHP_URL_PATH);

        return $path && trim($path, '/') === trim(request()->path(), '/');
    }

    private static function fromModel(NavigationMenu $menu): array
    {
        return [
            'title' => $menu->localized('title'),
            'url' => self::resolveUrl((string) $menu->localized('url', $menu->url)),
            'open_new_tab' => $menu->open_new_tab,
            'children' => $menu->children
                ->where('is_active', true)
                ->sortBy('sort_order')
                ->map(fn (NavigationMenu $child): array => self::fromModel($child))
                ->values(),
        ];
    }

    private static function fallback(string $location): array
    {
        $items = [
            ['title' => __('site.header.nav_home'), 'url' => localized_route('home'), 'active' => '*.home'],
            ['title' => __('site.header.nav_menu'), 'url' => localized_route('menu.index'), 'active' => '*.menu.*'],
            ['title' => __('site.header.nav_about'), 'url' => localized_route('about'), 'active' => '*.about'],
        ];

        if ($location === 'footer') {
            $items[] = ['title' => __('site.header.nav_booking'), 'url' => localized_route('reservations.create'), 'active' => '*.reservations.*'];
            $items[] = ['title' => __('site.footer_block.nav_order'), 'url' => localized_route('order.lookup'), 'active' => '*.order.*'];
        } else {
            $items[] = ['title' => __('site.footer_block.nav_order'), 'url' => localized_route('order.lookup'), 'active' => '*.order.*', 'featured' => true];
        }

        return $items;
    }
}
