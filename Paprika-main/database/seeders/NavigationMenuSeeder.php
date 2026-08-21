<?php

namespace Database\Seeders;

use App\Models\NavigationMenu;
use Illuminate\Database\Seeder;

class NavigationMenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'title' => 'Trang chủ',
                'url' => 'route:home',
                'location' => 'header',
                'sort_order' => 10,
                'translations' => [
                    'en' => ['title' => 'Home', 'url' => 'route:home'],
                    'el' => ['title' => 'Αρχική', 'url' => 'route:home'],
                ],
            ],
            [
                'title' => 'Thực đơn',
                'url' => 'route:menu.index',
                'location' => 'header',
                'sort_order' => 20,
                'translations' => [
                    'en' => ['title' => 'Menu', 'url' => 'route:menu.index'],
                    'el' => ['title' => 'Μενού', 'url' => 'route:menu.index'],
                ],
            ],
            [
                'title' => 'Không gian',
                'url' => 'route:gallery.index',
                'location' => 'header',
                'sort_order' => 30,
                'translations' => [
                    'en' => ['title' => 'Space', 'url' => 'route:gallery.index'],
                    'el' => ['title' => 'Χώρος', 'url' => 'route:gallery.index'],
                ],
            ],
            [
                'title' => 'Giới thiệu',
                'url' => 'route:about',
                'location' => 'header',
                'sort_order' => 40,
                'translations' => [
                    'en' => ['title' => 'About', 'url' => 'route:about'],
                    'el' => ['title' => 'Σχετικά', 'url' => 'route:about'],
                ],
            ],
            [
                'title' => 'Tra cứu đơn',
                'url' => 'route:order.lookup',
                'location' => 'header',
                'sort_order' => 50,
                'translations' => [
                    'en' => ['title' => 'Track order', 'url' => 'route:order.lookup'],
                    'el' => ['title' => 'Παρακολούθηση', 'url' => 'route:order.lookup'],
                ],
            ],
            [
                'title' => 'Trang chủ',
                'url' => 'route:home',
                'location' => 'footer',
                'sort_order' => 10,
                'translations' => [
                    'en' => ['title' => 'Home', 'url' => 'route:home'],
                    'el' => ['title' => 'Αρχική', 'url' => 'route:home'],
                ],
            ],
            [
                'title' => 'Thực đơn',
                'url' => 'route:menu.index',
                'location' => 'footer',
                'sort_order' => 20,
                'translations' => [
                    'en' => ['title' => 'Menu', 'url' => 'route:menu.index'],
                    'el' => ['title' => 'Μενού', 'url' => 'route:menu.index'],
                ],
            ],
            [
                'title' => 'Không gian',
                'url' => 'route:gallery.index',
                'location' => 'footer',
                'sort_order' => 30,
                'translations' => [
                    'en' => ['title' => 'Space', 'url' => 'route:gallery.index'],
                    'el' => ['title' => 'Χώρος', 'url' => 'route:gallery.index'],
                ],
            ],
            [
                'title' => 'Đặt bàn',
                'url' => 'route:reservations.create',
                'location' => 'footer',
                'sort_order' => 40,
                'translations' => [
                    'en' => ['title' => 'Book table', 'url' => 'route:reservations.create'],
                    'el' => ['title' => 'Κράτηση', 'url' => 'route:reservations.create'],
                ],
            ],
            [
                'title' => 'Tra cứu đơn',
                'url' => 'route:order.lookup',
                'location' => 'footer',
                'sort_order' => 50,
                'translations' => [
                    'en' => ['title' => 'Track order', 'url' => 'route:order.lookup'],
                    'el' => ['title' => 'Παρακολούθηση', 'url' => 'route:order.lookup'],
                ],
            ],
        ];

        foreach ($menus as $menu) {
            $translations = $menu['translations'];
            unset($menu['translations']);

            $model = NavigationMenu::updateOrCreate(
                ['location' => $menu['location'], 'url' => $menu['url']],
                $menu + ['is_active' => true, 'open_new_tab' => false],
            );

            foreach ($translations as $locale => $fields) {
                $model->translations()->updateOrCreate(['locale' => $locale], $fields);
            }
        }
    }
}
