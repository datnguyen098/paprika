<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class StorefrontRouteOptions
{
    public static function all(): Collection
    {
        $labels = [
            'home' => 'Trang chủ',
            'about' => 'Giới thiệu',
            'gallery.index' => 'Không gian quán',
            'menu.index' => 'Thực đơn',
            'cart.index' => 'Giỏ hàng',
            'checkout.create' => 'Thanh toán',
            'order.lookup' => 'Tra cứu đơn hàng',
            'blog.index' => 'Blog',
            'reservations.create' => 'Đặt bàn',
            'contact' => 'Liên hệ',
        ];

        return collect(Route::getRoutes())
            ->filter(function ($route) use ($labels): bool {
                $name = $route->getName();

                if (! $name || ! str_starts_with($name, 'localized.vi.')) {
                    return false;
                }

                $baseName = substr($name, strlen('localized.vi.'));

                return array_key_exists($baseName, $labels)
                    && in_array('GET', $route->methods(), true)
                    && ! str_contains($route->uri(), '{');
            })
            ->map(function ($route) use ($labels): array {
                $baseName = substr($route->getName(), strlen('localized.vi.'));

                return [
                    'name' => $baseName,
                    'value' => 'route:'.$baseName,
                    'label' => $labels[$baseName],
                    'sample' => 'tự đổi theo ngôn ngữ',
                ];
            })
            ->sortBy(fn (array $option): int => array_search($option['name'], array_keys($labels), true))
            ->values();
    }
}
