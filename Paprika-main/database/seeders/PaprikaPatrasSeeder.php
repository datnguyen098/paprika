<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishOptionGroup;
use App\Models\GalleryImage;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\RestaurantTable;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class PaprikaPatrasSeeder extends Seeder
{
    public function run(): void
    {
        $this->removeLegacyData();
        $this->settings();
        $branch = $this->branch();
        $this->tables($branch);
        $this->banner();
        $this->gallery($branch);
        $this->promotions();
        $this->pages();
        $this->navigationMenus();
        $this->menu();
    }

    private function removeLegacyData(): void
    {
        Dish::query()->delete();
        Category::query()->delete();
        Banner::query()->delete();
        GalleryImage::query()->delete();
        Promotion::query()->delete();
        NavigationMenu::query()->delete();
        Page::query()->delete();
        Post::query()->delete();
        Testimonial::query()->delete();
        Branch::query()->where('slug', '!=', 'patras')->delete();
    }

    private function settings(): void
    {
        $settings = [
            ['site_name', 'Paprika Patras | Vietnamese Cuisine'],
            ['restaurant_name', 'Paprika'],
            ['restaurant_name_en', 'Paprika'],
            ['slogan', 'Ẩm thực Việt Nam tại Patras'],
            ['short_description', 'Món Việt ấm áp với tinh thần hiếu khách Hy Lạp, rau thơm tươi, món nướng, phở, bánh mì và đặt món online nhanh tại Patras.'],
            ['phone', '261 031 6200'],
            ['hotline', '694 041 4566'],
            ['email', 'hello@paprika-patras.gr'],
            ['address', 'Patras, Greece'],
            ['opening_hours', '12:00 - 23:00 daily'],
            ['open_days', '1,2,3,4,5,6,0'],
            ['reservation_time_slots', '12:00-23:00'],
            ['reservation_last_booking_time', '22:30'],
            ['reservation_last_order_buffer_minutes', '30'],
            ['reservation_hold_minutes', '15'],
            ['reservation_duration_minutes', '90'],
            ['business_timezone', 'Europe/Athens'],
            ['show_dish_prices', '1'],
            ['facebook_url', 'https://www.facebook.com/paprika.patras'],
            ['instagram_url', 'https://www.instagram.com/paprika_patras'],
            ['zalo_url', null],
            ['tiktok_url', null],
            ['youtube_url', null],
            ['currency_code', 'EUR'],
            ['schema_restaurant_name', 'Paprika'],
            ['schema_address', 'Patras, Greece'],
            ['schema_phone', '+30 694 041 4566'],
            ['schema_price_range', '€€'],
            ['schema_opening_hours', '12:00 - 23:00'],
            ['default_meta_title', 'Paprika Patras | Vietnamese Cuisine'],
            ['default_meta_description', 'Order pho, banh mi, nem, rolls, and grilled Vietnamese dishes from Paprika in Patras.'],
            ['default_meta_keywords', 'Paprika Patras, Vietnamese cuisine Patras, pho Patras, banh mi Patras, Greek Vietnamese restaurant'],
            ['robots_txt_content', "User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml')."\n"],
            ['logo_header', '/paprika/logo-hs.webp'],
            ['logo_footer', '/paprika/logo-hs.webp'],
            ['brand_wordmark', '/paprika/wordmark.webp'],
            ['default_background', '/paprika/hero.jpg'],
            ['og_image', '/paprika/cover.jpg'],
            ['footer_description', 'Ẩm thực Việt Nam tại Patras với rau thơm tươi, phục vụ ấm áp và đặt món online cho tự nhận hoặc giao hàng.'],
            ['copyright', '© '.date('Y').' Paprika. Đã đăng ký bản quyền.'],
            ['translation_enabled', '0'],
            ['translation_provider', 'deepl'],
            ['deepl_api_plan', 'free'],
            ['deepl_source_lang', 'VI'],
            ['deepl_target_lang', 'EN-US'],
            ['microsoft_translator_endpoint', 'https://api.cognitive.microsofttranslator.com'],
            ['microsoft_translator_region', null],
            ['microsoft_translator_target_lang', 'en'],
            ['translation_monthly_limit_warning', '450000'],
        ];

        foreach ($settings as [$key, $value]) {
            $type = in_array($key, ['default_background', 'og_image', 'logo_header', 'logo_footer', 'brand_wordmark'], true)
                ? 'image'
                : (in_array($key, ['show_dish_prices', 'translation_enabled'], true) ? 'boolean' : (str_contains($key, 'limit') || str_contains($key, 'buffer') || str_contains($key, 'minutes') ? 'number' : 'text'));
            $group = str_starts_with($key, 'schema_') || str_starts_with($key, 'default_meta') || in_array($key, ['og_image', 'robots_txt_content'], true)
                ? 'seo'
                : (str_starts_with($key, 'translation_') || str_starts_with($key, 'deepl_') || str_starts_with($key, 'microsoft_') ? 'translation' : 'general');

            SiteSetting::set($key, $value, $type, $group);
        }
    }

    private function branch(): Branch
    {
        $branch = Branch::updateOrCreate(
            ['slug' => 'patras'],
            [
                'name' => 'Paprika Patras',
                'city' => 'Patras',
                'timezone' => null,
                'address' => 'Patras, Greece',
                'phone' => '261 031 6200',
                'hotline' => '694 041 4566',
                'email' => 'hello@paprika-patras.gr',
                'opening_hours' => null,
                'open_days' => null,
                'accepts_online_orders' => true,
                'accepts_pickup_orders' => true,
                'accepts_delivery_orders' => true,
                'auto_delivery_quote_enabled' => false,
                'delivery_min_order_amount' => 1000,
                'delivery_free_order_amount' => 3500,
                'delivery_max_distance_km' => 6,
                'delivery_origin_latitude' => 38.2430666,
                'delivery_origin_longitude' => 21.7296262,
                'delivery_note' => 'site.delivery_quote.branch-note-patras',
                'reservation_time_slots' => null,
                'reservation_last_booking_time' => null,
                'reservation_last_order_buffer_minutes' => null,
                'description' => 'Single Paprika restaurant branch in Patras serving Vietnamese cuisine for dine-in, pickup, and delivery.',
                'image' => '/paprika/store.jpg',
                'is_active' => true,
                'sort_order' => 1,
                'meta_title' => 'Paprika Patras',
                'meta_description' => 'Visit Paprika in Patras for Vietnamese cuisine, pho, banh mi, nem, rolls, and grilled dishes.',
                'google_map_iframe' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3133.570760246776!2d21.729626200000002!3d38.2430666!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x135e49e78492c379%3A0x19af0c6f81ea8d19!2zUMOhcHJpa2E!5e0!3m2!1svi!2s!4v1779844079846!5m2!1svi!2s" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
            ],
        );

        $zones = [
            ['label' => 'Dưới 1km', 'min_distance_km' => 0, 'max_distance_km' => 1, 'fee' => 0, 'sort_order' => 1],
            ['label' => '1-3km', 'min_distance_km' => 1, 'max_distance_km' => 3, 'fee' => 150, 'sort_order' => 2],
            ['label' => '3-5km', 'min_distance_km' => 3, 'max_distance_km' => 5, 'fee' => 300, 'sort_order' => 3],
            ['label' => '5-6km', 'min_distance_km' => 5, 'max_distance_km' => 6, 'fee' => 450, 'sort_order' => 4],
        ];

        foreach ($zones as $zone) {
            $branch->deliveryZones()->updateOrCreate(
                ['label' => $zone['label']],
                array_merge($zone, ['is_active' => true])
            );
        }

        return $branch;
    }

    private function tables(Branch $branch): void
    {
        $tables = [
            ['code' => 'T1', 'name' => 'Bàn 1', 'seats' => 2, 'zone' => 'Sảnh chính'],
            ['code' => 'T2', 'name' => 'Bàn 2', 'seats' => 2, 'zone' => 'Sảnh chính'],
            ['code' => 'T3', 'name' => 'Bàn 3', 'seats' => 2, 'zone' => 'Sảnh chính'],
            ['code' => 'T4', 'name' => 'Bàn 4', 'seats' => 4, 'zone' => 'Sảnh chính'],
            ['code' => 'T5', 'name' => 'Bàn 5', 'seats' => 4, 'zone' => 'Sảnh chính'],
            ['code' => 'T6', 'name' => 'Bàn 6', 'seats' => 4, 'zone' => 'Sảnh chính'],
            ['code' => 'T7', 'name' => 'Bàn 7', 'seats' => 4, 'zone' => 'Sảnh phụ'],
            ['code' => 'T8', 'name' => 'Bàn 8', 'seats' => 4, 'zone' => 'Sảnh phụ'],
            ['code' => 'T9', 'name' => 'Bàn 9', 'seats' => 6, 'zone' => 'Sảnh phụ'],
            ['code' => 'T10', 'name' => 'Bàn 10', 'seats' => 6, 'zone' => 'Sảnh phụ'],
            ['code' => 'T11', 'name' => 'Bàn 11', 'seats' => 8, 'zone' => 'Nhóm đông'],
            ['code' => 'T12', 'name' => 'Bàn 12', 'seats' => 10, 'zone' => 'Nhóm đông'],
        ];

        foreach ($tables as $index => $table) {
            RestaurantTable::updateOrCreate(
                ['branch_id' => $branch->id, 'code' => $table['code']],
                $table + [
                    'branch_id' => $branch->id,
                    'status' => 'active',
                    'is_joinable' => $table['seats'] >= 4,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function navigationMenus(): void
    {
        $this->call(NavigationMenuSeeder::class);
    }

    private function banner(): void
    {
        $model = Banner::updateOrCreate(
            ['title' => 'Paprika - Ẩm thực Việt Nam'],
            [
                'subtitle' => 'Phở, bánh mì, nem và các món nướng tại Patras',
                'description' => 'Món Việt tươi ngon trong không gian nhà hàng ấm cúng, sẵn sàng phục vụ tại chỗ, mang đi hoặc giao hàng.',
                'button_text' => 'Xem thực đơn',
                'button_link' => route('localized.vi.menu.index'),
                'image' => '/paprika/hero.jpg',
                'position' => 'home',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $model->translations()->updateOrCreate(
            ['locale' => 'en'],
            [
                'title' => 'Paprika - Vietnamese Cuisine',
                'subtitle' => 'Pho, banh mi, spring rolls and grilled dishes in Patras',
                'description' => 'Fresh Vietnamese dishes in a warm restaurant setting, ready for dine-in, takeaway or delivery.',
                'button_text' => 'View the menu',
            ]
        );

        $model->translations()->updateOrCreate(
            ['locale' => 'el'],
            [
                'title' => 'Paprika - Βιετναμέζικη Κουζίνα',
                'subtitle' => 'Φο, μπανχ μι, ρολά και ψητά πιάτα στην Πάτρα',
                'description' => 'Φρέσκα βιετναμέζικα πιάτα σε ζεστό περιβάλλον εστιατορίου, έτοιμα για φαγητό στο χώρο, παραλαβή ή παράδοση.',
                'button_text' => 'Δείτε το μενού',
            ]
        );
    }

    private function gallery(Branch $branch): void
    {
        $images = [
            [
                'title' => 'Khu vực bếp mở',
                'slug' => 'khong-gian-01',
                'description' => 'Khu vực bếp mở của Paprika, nơi các món ăn được chế biến trước mặt thực khách.',
                'image' => 'gallery/z7915337770732_2905f213ffe821124d15f0eddf9b2ce5.jpg',
                'alt_text' => 'Khu vực bếp mở tại Paprika',
                'location' => 'space',
                'sort_order' => 1,
                'translations' => [
                    'en' => [
                        'title' => 'Open Kitchen Area',
                        'description' => 'Paprika\'s open kitchen, where dishes are prepared in front of guests.',
                        'alt_text' => 'Open kitchen area at Paprika',
                    ],
                    'el' => [
                        'title' => 'Ανοιχτή Κουζίνα',
                        'description' => 'Η ανοιχτή κουζίνα του Paprika, όπου τα πιάτα παρασκευάζονται μπροστά στους επισκέπτες.',
                        'alt_text' => 'Ανοιχτή κουζίνα στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Bước vào Paprika',
                'slug' => 'khong-gian-02',
                'description' => 'Cửa vào quán Paprika với không gian mở và lối trang trí ấm cúng.',
                'image' => 'gallery/z7915337774690_6a1da0ae6002de9b04310e4604bf787f.jpg',
                'alt_text' => 'Cửa vào quán Paprika',
                'location' => 'space',
                'sort_order' => 2,
                'translations' => [
                    'en' => [
                        'title' => 'Entering Paprika',
                        'description' => 'The entrance to Paprika with an open layout and warm decorative touches.',
                        'alt_text' => 'Entrance to Paprika restaurant',
                    ],
                    'el' => [
                        'title' => 'Είσοδος στο Paprika',
                        'description' => 'Η είσοδος στο Paprika με ανοιχτή διάταξη και ζεστές διακοσμητικές πινελιές.',
                        'alt_text' => 'Είσοδος στο εστιατόριο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Bàn ghế ngoài trời',
                'slug' => 'khong-gian-03',
                'description' => 'Khu vực ngồi ngoài trời của quán, phù hợp cho bữa tối mùa hè tại Patras.',
                'image' => 'gallery/z7915337774912_886902b8ebf3894ab730782e73db475d.jpg',
                'alt_text' => 'Khu vực bàn ghế ngoài trời tại Paprika',
                'location' => 'space',
                'sort_order' => 3,
                'translations' => [
                    'en' => [
                        'title' => 'Outdoor Seating',
                        'description' => 'Paprika\'s outdoor seating area, perfect for summer dinners in Patras.',
                        'alt_text' => 'Outdoor seating at Paprika',
                    ],
                    'el' => [
                        'title' => 'Εξωτερικά Καθίσματα',
                        'description' => 'Ο εξωτερικός χώρος καθισμάτων του Paprika, ιδανικός για καλοκαιρινά δείπνα στην Πάτρα.',
                        'alt_text' => 'Εξωτερικά καθίσματα στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Tầng trên của quán',
                'slug' => 'khong-gian-04',
                'description' => 'Khu vực tầng trên với view nhìn ra phố, lý tưởng cho những bữa ăn yên tĩnh.',
                'image' => 'gallery/z7915337784155_3a4a97ae67650cca13ec701cf702e9f3.jpg',
                'alt_text' => 'Tầng trên của quán Paprika',
                'location' => 'space',
                'sort_order' => 4,
                'translations' => [
                    'en' => [
                        'title' => 'Upper Floor',
                        'description' => 'The upper floor with street views, ideal for a quiet meal.',
                        'alt_text' => 'Upper floor of Paprika restaurant',
                    ],
                    'el' => [
                        'title' => 'Πάνω Όροφος',
                        'description' => 'Ο πάνω όροφος με θέα στο δρόμο, ιδανικός για ένα ήσυχο γεύμα.',
                        'alt_text' => 'Πάνω όροφος του εστιατορίου Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Góc nhìn từ cửa sổ',
                'slug' => 'khong-gian-05',
                'description' => 'Không gian bên trong quán nhìn từ cửa kính, nơi ánh sáng tự nhiên tràn vào.',
                'image' => 'gallery/z7915337789979_ccd5fa3d792fdb69fb8989a07021e401.jpg',
                'alt_text' => 'Không gian bên trong quán nhìn từ cửa kính',
                'location' => 'space',
                'sort_order' => 5,
                'translations' => [
                    'en' => [
                        'title' => 'View from the Window',
                        'description' => 'The interior of Paprika seen from the glass, where natural light flows in.',
                        'alt_text' => 'Interior view from the window at Paprika',
                    ],
                    'el' => [
                        'title' => 'Θέα από το Παράθυρο',
                        'description' => 'Το εσωτερικό του Paprika όπως φαίνεται από το τζάμι, όπου ρέει φυσικό φως.',
                        'alt_text' => 'Θέα εσωτερικού από το παράθυρο στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Khu vực quầy bar',
                'slug' => 'khong-gian-06',
                'description' => 'Quầy bar với các loại nước giải khát và đồ uống đặc trưng của quán.',
                'image' => 'gallery/z7915337802421_8c4099a67a60be51e7ebe71d765e8b99.jpg',
                'alt_text' => 'Khu vực quầy bar tại Paprika',
                'location' => 'space',
                'sort_order' => 6,
                'translations' => [
                    'en' => [
                        'title' => 'Bar Area',
                        'description' => 'The bar counter featuring drinks and signature beverages.',
                        'alt_text' => 'Bar area at Paprika',
                    ],
                    'el' => [
                        'title' => 'Χώρος Μπαρ',
                        'description' => 'Ο πάγκος του μπαρ με ποτά και χαρακτηριστικά αναψυκτικά.',
                        'alt_text' => 'Χώρος μπαρ στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Chi tiết trang trí',
                'slug' => 'khong-gian-07',
                'description' => 'Chi tiết trang trí bên trong quán với các yếu tố văn hóa Việt Nam và Hy Lạp.',
                'image' => 'gallery/z7915337802908_9b6aa1b6ef7c95736173db5053739df9.jpg',
                'alt_text' => 'Chi tiết trang trí tại Paprika',
                'location' => 'space',
                'sort_order' => 7,
                'translations' => [
                    'en' => [
                        'title' => 'Decorative Details',
                        'description' => 'Interior decorative details blending Vietnamese and Greek cultural elements.',
                        'alt_text' => 'Decorative details at Paprika',
                    ],
                    'el' => [
                        'title' => 'Διακοσμητικές Λεπτομέρειες',
                        'description' => 'Διακοσμητικές λεπτομέρειες εσωτερικού που συνδυάζουν βιετναμέζικα και ελληνικά πολιτιστικά στοιχεία.',
                        'alt_text' => 'Διακοσμητικές λεπτομέρειες στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Góc nhìn tổng quan',
                'slug' => 'khong-gian-08',
                'description' => 'Tổng quan không gian bên trong quán với các bàn ghế được bài trí hài hòa.',
                'image' => 'gallery/z7915337814362_8e8fa25ac38d1620a56a97d8953af321.jpg',
                'alt_text' => 'Tổng quan không gian bên trong Paprika',
                'location' => 'space',
                'sort_order' => 8,
                'translations' => [
                    'en' => [
                        'title' => 'Overall Interior View',
                        'description' => 'A comprehensive view of the restaurant interior with harmoniously arranged seating.',
                        'alt_text' => 'Overall interior of Paprika',
                    ],
                    'el' => [
                        'title' => 'Συνολική Εσωτερική Θέα',
                        'description' => 'Μια ολοκληρωμένη άποψη του εσωτερικού του εστιατορίου με αρμονικά διατεταγμένα καθίσματα.',
                        'alt_text' => 'Συνολικό εσωτερικό του Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Khu vực đặc biệt',
                'slug' => 'khong-gian-09',
                'description' => 'Khu vực đặc biệt dành cho nhóm khách hoặc tiệc riêng.',
                'image' => 'gallery/z7915337820895_a827df2fcb9acb4f6b85653a94c52a22.jpg',
                'alt_text' => 'Khu vực đặc biệt tại Paprika',
                'location' => 'space',
                'sort_order' => 9,
                'translations' => [
                    'en' => [
                        'title' => 'Special Area',
                        'description' => 'A special area for groups or private celebrations.',
                        'alt_text' => 'Special area at Paprika',
                    ],
                    'el' => [
                        'title' => 'Ειδικός Χώρος',
                        'description' => 'Ένας ειδικός χώρος για ομάδες ή ιδιωτικές γιορτές.',
                        'alt_text' => 'Ειδικός χώρος στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Bếp nướng than',
                'slug' => 'khong-gian-10',
                'description' => 'Khu vực bếp nướng than với các món nướng đặc trưng của quán.',
                'image' => 'gallery/z7915337827780_bd5dac7052d81a74ca438e6497563306.jpg',
                'alt_text' => 'Khu vực bếp nướng than tại Paprika',
                'location' => 'space',
                'sort_order' => 10,
                'translations' => [
                    'en' => [
                        'title' => 'Charcoal Grill Station',
                        'description' => 'The charcoal grill station serving Paprika\'s signature grilled dishes.',
                        'alt_text' => 'Charcoal grill at Paprika',
                    ],
                    'el' => [
                        'title' => 'Σταθμός Ψησταριάς',
                        'description' => 'Ο σταθμός ψησταριάς με κάρβουνο που σερβίρει τα χαρακτηριστικά ψητά πιάτα του Paprika.',
                        'alt_text' => 'Ψησταριά με κάρβουνο στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Món ăn trên bàn',
                'slug' => 'khong-gian-11',
                'description' => 'Các món ăn được bày trí đẹp mắt trên bàn, sẵn sàng để thưởng thức.',
                'image' => 'gallery/z7915337831243_399a7a1c3ff53b3c7b1e1a8f1b93c0ae.jpg',
                'alt_text' => 'Món ăn được bày trí tại Paprika',
                'location' => 'space',
                'sort_order' => 11,
                'translations' => [
                    'en' => [
                        'title' => 'Dishes on the Table',
                        'description' => 'Beautifully plated dishes ready to be enjoyed.',
                        'alt_text' => 'Plated dishes at Paprika',
                    ],
                    'el' => [
                        'title' => 'Πιάτα στο Τραπέζι',
                        'description' => 'Όμορφα σερβιρισμένα πιάτα έτοιμα για απόλαυση.',
                        'alt_text' => 'Σερβιρισμένα πιάτα στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Chi tiết món ăn',
                'slug' => 'khong-gian-12',
                'description' => 'Món ăn được chế biến cầu kỳ, trình bày đẹp mắt với nguyên liệu tươi ngon.',
                'image' => 'gallery/z7915337836384_fa921f2d56f64ed48b3ca8f21341a457.jpg',
                'alt_text' => 'Chi tiết món ăn tại Paprika',
                'location' => 'space',
                'sort_order' => 12,
                'translations' => [
                    'en' => [
                        'title' => 'Dish Detail',
                        'description' => 'Carefully prepared dishes with fresh ingredients.',
                        'alt_text' => 'Dish detail at Paprika',
                    ],
                    'el' => [
                        'title' => 'Λεπτομέρεια Πιάτου',
                        'description' => 'Προσεκτικά παρασκευασμένα πιάτα με φρέσκα υλικά.',
                        'alt_text' => 'Λεπτομέρεια πιάτου στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Khu vực chờ',
                'slug' => 'khong-gian-13',
                'description' => 'Khu vực chờ thoải mái với ghế ngồi và ánh sáng dịu nhẹ.',
                'image' => 'gallery/z7915337837937_537a3e422ebaa359db6b778c429c0f6a.jpg',
                'alt_text' => 'Khu vực chờ tại Paprika',
                'location' => 'space',
                'sort_order' => 13,
                'translations' => [
                    'en' => [
                        'title' => 'Waiting Area',
                        'description' => 'A comfortable waiting area with seating and soft lighting.',
                        'alt_text' => 'Waiting area at Paprika',
                    ],
                    'el' => [
                        'title' => 'Χώρος Αναμονής',
                        'description' => 'Ένας άνετος χώρος αναμονής με καθίσματα και απαλό φωτισμό.',
                        'alt_text' => 'Χώρος αναμονής στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Góc tối của quán',
                'slug' => 'khong-gian-14',
                'description' => 'Góc tối của quán với không gian riêng tư, lý tưởng cho cặp đôi.',
                'image' => 'gallery/z7915337842667_8785e2eaa642a54a7d8ceaff323ddf4a.jpg',
                'alt_text' => 'Góc riêng tư tại Paprika',
                'location' => 'space',
                'sort_order' => 14,
                'translations' => [
                    'en' => [
                        'title' => 'Cozy Corner',
                        'description' => 'A cozy corner of the restaurant offering privacy, ideal for couples.',
                        'alt_text' => 'Cozy corner at Paprika',
                    ],
                    'el' => [
                        'title' => 'Γωνιά Άνεσης',
                        'description' => 'Μια ζεστή γωνιά του εστιατορίου με ιδιωτικότητα, ιδανική για ζευγάρια.',
                        'alt_text' => 'Γωνιά άνεσης στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Khu vực nhà bếp',
                'slug' => 'khong-gian-15',
                'description' => 'Nhà bếp của quán với các thiết bị hiện đại và không gian sạch sẽ.',
                'image' => 'gallery/z7915337843938_5c5923a3dbe1c7b37bf3540b8c47b95f.jpg',
                'alt_text' => 'Nhà bếp của Paprika',
                'location' => 'space',
                'sort_order' => 15,
                'translations' => [
                    'en' => [
                        'title' => 'Kitchen Area',
                        'description' => 'The restaurant kitchen with modern equipment and a clean workspace.',
                        'alt_text' => 'Kitchen at Paprika',
                    ],
                    'el' => [
                        'title' => 'Περιοχή Κουζίνας',
                        'description' => 'Η κουζίνα του εστιατορίου με σύγχρονο εξοπλισμό και καθαρό χώρο εργασίας.',
                        'alt_text' => 'Κουζίνα στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Khu vực lên đơn',
                'slug' => 'khong-gian-16',
                'description' => 'Khu vực chuẩn bị và đóng gói đơn hàng mang đi.',
                'image' => 'gallery/z7915337853747_6105e996c31cc5daba05420b689a38f8.jpg',
                'alt_text' => 'Khu vực lên đơn tại Paprika',
                'location' => 'space',
                'sort_order' => 16,
                'translations' => [
                    'en' => [
                        'title' => 'Order Preparation Area',
                        'description' => 'The area for preparing and packaging takeaway orders.',
                        'alt_text' => 'Order preparation at Paprika',
                    ],
                    'el' => [
                        'title' => 'Χώρος Προετοιμασίας Παραγγελιών',
                        'description' => 'Ο χώρος προετοιμασίας και συσκευασίας παραγγελιών για παραλαβή.',
                        'alt_text' => 'Προετοιμασία παραγγελίας στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Bảng thực đơn treo tường',
                'slug' => 'khong-gian-17',
                'description' => 'Bảng thực đơn treo tường với các món ăn được trình bày rõ ràng.',
                'image' => 'gallery/z7915337854514_4793916b4237d282e49d5c88b694b700.jpg',
                'alt_text' => 'Bảng thực đơn treo tường tại Paprika',
                'location' => 'space',
                'sort_order' => 17,
                'translations' => [
                    'en' => [
                        'title' => 'Wall Menu Board',
                        'description' => 'The wall menu board with clearly presented dishes.',
                        'alt_text' => 'Wall menu board at Paprika',
                    ],
                    'el' => [
                        'title' => 'Πινακίδα Μενού στον Τοίχο',
                        'description' => 'Η πινακίδα μενού στον τοίχο με σαφώς παρουσιασμένα πιάτα.',
                        'alt_text' => 'Πινακίδα μενού στον τοίχο στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Thông tin trên tường',
                'slug' => 'khong-gian-18',
                'description' => 'Các thông tin và hình ảnh trang trí trên tường của quán.',
                'image' => 'gallery/z7915337862026_e89fedf267dbc2adf7c1b5541ca3fef6.jpg',
                'alt_text' => 'Thông tin trên tường tại Paprika',
                'location' => 'space',
                'sort_order' => 18,
                'translations' => [
                    'en' => [
                        'title' => 'Wall Information',
                        'description' => 'Information and decorative images on the restaurant walls.',
                        'alt_text' => 'Wall information at Paprika',
                    ],
                    'el' => [
                        'title' => 'Πληροφορίες στον Τοίχο',
                        'description' => 'Πληροφορίες και διακοσμητικές εικόνες στους τοίχους του εστιατορίου.',
                        'alt_text' => 'Πληροφορίες στον τοίχο στο Paprika',
                    ],
                ],
            ],
            [
                'title' => 'Bước ra đường',
                'slug' => 'khong-gian-19',
                'description' => 'Lối ra vào của quán với tầm nhìn ra con phố Patras.',
                'image' => 'gallery/z7915339457971_86ede4326e05cee0d0dcb3c6dbd618b2.jpg',
                'alt_text' => 'Lối ra vào quán Paprika hướng ra phố',
                'location' => 'space',
                'sort_order' => 19,
                'translations' => [
                    'en' => [
                        'title' => 'Stepping Out to the Street',
                        'description' => 'The entrance and exit of the restaurant with a view of Patras street.',
                        'alt_text' => 'Exit view from Paprika onto the street',
                    ],
                    'el' => [
                        'title' => 'Βήμα προς το Δρόμο',
                        'description' => 'Η είσοδος και έξοδος του εστιατορίου με θέα στο δρόμο της Πάτρας.',
                        'alt_text' => 'Έξοδος από το Paprika προς τον δρόμο',
                    ],
                ],
            ],
        ];

        foreach ($images as $imageData) {
            $translations = $imageData['translations'];
            unset($imageData['translations']);

            $model = GalleryImage::updateOrCreate(
                ['slug' => $imageData['slug']],
                $imageData + [
                    'branch_id' => $branch->id,
                    'is_featured' => true,
                    'is_active' => true,
                ]
            );

            foreach (['en', 'el'] as $locale) {
                $model->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'title' => $translations[$locale]['title'] ?? $imageData['title'],
                        'slug' => $imageData['slug'],
                        'description' => $translations[$locale]['description'] ?? $imageData['description'],
                        'alt_text' => $translations[$locale]['alt_text'] ?? $imageData['alt_text'],
                    ]
                );
            }
        }
    }

    private function promotions(): void
    {
        foreach ([
            [
                'title' => 'Pho & Banh Mi Lunch',
                'subtitle' => 'Fresh Vietnamese comfort food',
                'description' => 'Pick a warm pho bowl or a crisp banh mi for a quick Paprika lunch in Patras.',
                'badge' => 'Lunch pick',
                'button_text' => 'Order now',
                'button_link' => route('localized.vi.menu.index', ['category' => 'do-an-viet-nam']),
                'image' => '/paprika/menu/beef-pho.webp',
                'placement' => 'home',
                'template' => 'split',
                'accent_color' => '#064E3B',
                'sort_order' => 1,
                'show_once' => false,
                'is_active' => true,
            ],
            [
                'title' => 'Greek Grill Favorites',
                'subtitle' => 'Souvlaki, gyros and salad',
                'description' => 'Greek classics sit beside Vietnamese favorites for an easy mixed table.',
                'badge' => 'Greek side',
                'button_text' => 'View Greek food',
                'button_link' => route('localized.vi.menu.index', ['category' => 'do-an-hy-lap']),
                'image' => '/paprika/menu/souvlaki.webp',
                'placement' => 'home',
                'template' => 'split',
                'accent_color' => '#B91C1C',
                'sort_order' => 2,
                'show_once' => false,
                'is_active' => true,
            ],
            [
                'title' => 'Combo cuối tuần Paprika',
                'subtitle' => 'Phở, bánh mì và món nướng',
                'description' => 'Mở thực đơn và chọn một tô phở nóng, bánh mì giòn hoặc phần nướng Hy Lạp cho bữa Paprika tiếp theo.',
                'badge' => 'Ưu đãi cuối tuần',
                'button_text' => 'Xem thực đơn',
                'button_link' => route('localized.vi.menu.index'),
                'image' => '/paprika/menu/banh-mi.webp',
                'placement' => 'popup',
                'template' => 'split',
                'accent_color' => '#B91C1C',
                'sort_order' => 1,
                'show_once' => true,
                'is_active' => true,
            ],
        ] as $index => $promotion) {
            $model = Promotion::create($promotion);

            if ($promotion['placement'] === 'popup') {
                $model->translations()->updateOrCreate(
                    ['locale' => 'en'],
                    [
                        'title' => 'Paprika Weekend Combo',
                        'subtitle' => 'Pho, banh mi and grill picks',
                        'description' => 'Open the menu and choose a warm Vietnamese bowl, crisp banh mi or Greek grill plate for your next Paprika meal.',
                        'badge' => 'Weekend offer',
                        'button_text' => 'View the menu',
                    ]
                );

                $model->translations()->updateOrCreate(
                    ['locale' => 'el'],
                    [
                        'title' => 'Weekend Combo Paprika',
                        'subtitle' => 'Φο, μπανχ μι και ψητά',
                        'description' => 'Ανοίξτε το μενού και διαλέξτε ένα ζεστό βιετναμέζικο μπολ, τραγανό μπανχ μι ή ελληνικό πιάτο σχάρας.',
                        'badge' => 'Προσφορά Σαββατοκύριακου',
                        'button_text' => 'Δείτε το μενού',
                    ]
                );
            }

            // Add EL translation for Pho & Banh Mi Lunch (index 0)
            if ($index === 0) {
                $model->translations()->updateOrCreate(
                    ['locale' => 'el'],
                    [
                        'title' => 'Φο & Μπανχ Μι Lunch',
                        'subtitle' => 'Φρέσκο βιετναμέζικο comfort food',
                        'description' => 'Επιλέξτε ένα ζεστό μπολ φο ή τραγανό μπανχ μι για ένα γρήγορο μεσημεριανό στο Paprika στην Πάτρα.',
                        'badge' => 'Επιλογή Lunch',
                        'button_text' => 'Παραγγείλετε τώρα',
                    ]
                );
            }

            // Add EL translation for Greek Grill Favorites (index 1)
            if ($index === 1) {
                $model->translations()->updateOrCreate(
                    ['locale' => 'el'],
                    [
                        'title' => 'Αγαπημένα Ελληνικά Ψητά',
                        'subtitle' => 'Σουβλάκι, γύρος και σαλάτα',
                        'description' => 'Ελληνικά κλασικά δίπλα σε βιετναμέζικα αγαπημένα για ένα εύκολο μικτό τραπέζι.',
                        'badge' => 'Ελληνική πλευρά',
                        'button_text' => 'Δείτε ελληνικά φαγητά',
                    ]
                );
            }
        }
    }

    private function pages(): void
    {
        foreach ([
            [
                'title' => 'Giới thiệu Paprika',
                'slug' => 'gioi-thieu',
                'content' => '<p>Paprika là bếp Việt Nam tại Patras, phục vụ phở, bánh mì, nem, món cuốn, các món nướng và một số lựa chọn Hy Lạp quen thuộc.</p><p>Quán tập trung vào hương vị tươi, phục vụ rõ ràng và trải nghiệm đặt món thuận tiện cho khách ăn tại quán, tự nhận hoặc giao hàng.</p>',
                'meta_title' => 'Giới thiệu Paprika | Vietnamese Cuisine Patras',
                'meta_description' => 'Tìm hiểu Paprika Patras, nhà hàng phục vụ món Việt Nam, phở, bánh mì, nem và món nướng tại Patras.',
            ],
            [
                'title' => 'Chính sách đặt bàn',
                'slug' => 'chinh-sach-dat-ban',
                'content' => '<p>Khách có thể gửi yêu cầu đặt bàn trên website. Paprika sẽ liên hệ qua điện thoại để xác nhận thời gian, số khách và ghi chú đặc biệt.</p><p>Quán giữ bàn trong một khoảng thời gian hợp lý sau giờ hẹn. Nếu cần thay đổi số khách hoặc giờ đến, vui lòng báo sớm để quán sắp xếp tốt hơn.</p>',
                'meta_title' => 'Chính sách đặt bàn | Paprika',
                'meta_description' => 'Thông tin chính sách đặt bàn tại Paprika Patras.',
            ],
        ] as $index => $page) {
            $model = Page::updateOrCreate(
                ['slug' => $page['slug']],
                $page + [
                    'is_active' => true,
                    'meta_keywords' => 'Paprika Patras, đặt bàn Paprika, Vietnamese cuisine Patras',
                ]
            );

            // Add EN translation for About page (index 0)
            if ($index === 0) {
                $model->translations()->updateOrCreate(
                    ['locale' => 'en'],
                    [
                        'title' => 'About Paprika',
                        'slug' => 'about',
                        'content' => '<p>Paprika is a Vietnamese kitchen in Patras, serving pho, banh mi, nem, fresh rolls, grilled dishes and some familiar Greek favorites.</p><p>The restaurant focuses on fresh flavors, clear service and a convenient ordering experience for dine-in, takeaway or delivery.</p>',
                        'meta_title' => 'About Paprika | Vietnamese Cuisine Patras',
                        'meta_description' => 'Learn about Paprika Patras, the restaurant serving Vietnamese dishes, pho, banh mi, nem and grilled dishes in Patras.',
                        'meta_keywords' => 'Paprika Patras, about Paprika, Vietnamese cuisine Patras',
                    ]
                );

                $model->translations()->updateOrCreate(
                    ['locale' => 'el'],
                    [
                        'title' => 'Σχετικά με το Paprika',
                        'slug' => 'schetikos',
                        'content' => '<p>Το Paprika είναι ένα βιετναμέζικο εστιατόριο στην Πάτρα, που σερβίρει φο, μπανχ μι, νεμ, φρέσκα ρολά, ψητά πιάτα και μερικά οικεία ελληνικά αγαπημένα.</p><p>Το εστιατόριο εστιάζει στις φρέσκες γεύσεις, την καθαρή εξυπηρέτηση και μια βολική εμπειρία παραγγελίας για φαγητό στο χώρο, παραλαβή ή παράδοση.</p>',
                        'meta_title' => 'Σχετικά με το Paprika | Βιετναμέζικη Κουζίνα Πάτρα',
                        'meta_description' => 'Μάθετε για το Paprika Patras, το εστιατόριο που σερβίρει βιετναμέζικα πιάτα, φο, μπανχ μι, νεμ και ψητά πιάτα στην Πάτρα.',
                        'meta_keywords' => 'Paprika Patras, σχετικά με Paprika, βιετναμέζικη κουζίνα Πάτρα',
                    ]
                );
            }

            // Add EN & EL translation for Reservation Policy page (index 1)
            if ($index === 1) {
                $model->translations()->updateOrCreate(
                    ['locale' => 'en'],
                    [
                        'title' => 'Reservation Policy',
                        'slug' => 'reservation-policy',
                        'content' => '<p>Guests can send a reservation request through the website. Paprika will contact you by phone to confirm the time, number of guests and any special notes.</p><p>The restaurant holds the table for a reasonable time after the booked time. If you need to change the number of guests or arrival time, please inform us early so we can arrange better.</p>',
                        'meta_title' => 'Reservation Policy | Paprika',
                        'meta_description' => 'Reservation policy information at Paprika Patras.',
                        'meta_keywords' => 'Paprika Patras, reservation policy, Vietnamese cuisine Patras',
                    ]
                );

                $model->translations()->updateOrCreate(
                    ['locale' => 'el'],
                    [
                        'title' => 'Πολιτική Κρατήσεων',
                        'slug' => 'politiki-kratiseon',
                        'content' => '<p>Οι πελάτες μπορούν να στείλουν αίτημα κράτησης μέσω της ιστοσελίδας. Το Paprika θα επικοινωνήσει τηλεφωνικά για να επιβεβαιώσει την ώρα, τον αριθμό των καλεσμένων και τυχόν ειδικές σημειώσεις.</p><p>Το εστιατόριο κρατά το τραπέζι για εύλογο χρονικό διάστημα μετά την κλεισμένη ώρα. Αν χρειάζεται να αλλάξετε τον αριθμό των καλεσμένων ή την ώρα άφιξης, παρακαλώ ενημερώστε μας νωρίς για καλύτερη οργάνωση.</p>',
                        'meta_title' => 'Πολιτική Κρατήσεων | Paprika',
                        'meta_description' => 'Πληροφορίες πολιτικής κρατήσεων στο Paprika Patras.',
                        'meta_keywords' => 'Paprika Patras, πολιτική κρατήσεων, βιετναμέζικη κουζίνα Πάτρα',
                    ]
                );
            }
        }
    }

    private function menu(): void
    {
        $categories = collect([
            [
                'name' => 'Món Việt Nam',
                'slug' => 'mon-viet-nam',
                'description' => 'Các món Việt Nam chủ đạo của Paprika: phở, bún, bánh mì, nem và đồ nướng.',
                'sort_order' => 1,
                'en' => 'Vietnamese Food',
                'el' => 'Βιετναμέζικο φαγητό',
            ],
        ])->mapWithKeys(function (array $category): array {
            $englishName = $category['en'];
            $greekName = $category['el'];
            unset($category['en'], $category['el']);

            $model = Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category + [
                    'type' => 'dish',
                    'image' => '/paprika/board.jpg',
                    'is_active' => true,
                    'meta_title' => $category['name'].' | Paprika',
                    'meta_description' => $category['description'],
                ]
            );

            $model->translations()->updateOrCreate(
                ['locale' => 'en'],
                [
                    'name' => $englishName,
                    'slug' => $category['slug'],
                    'description' => $category['description'],
                    'meta_title' => $englishName.' | Paprika',
                    'meta_description' => $category['description'],
                ]
            );

            $model->translations()->updateOrCreate(
                ['locale' => 'el'],
                [
                    'name' => $greekName,
                    'slug' => $category['slug'],
                    'description' => $category['description'],
                    'meta_title' => $greekName.' | Paprika',
                    'meta_description' => $category['description'],
                ]
            );

            return [$category['slug'] => $model];
        });

        foreach ($this->dishes() as $dish) {
            $category = $categories->get($dish['category']);

            $model = Dish::updateOrCreate(
                ['slug' => $dish['slug']],
                [
                    'category_id' => $category->id,
                    'name' => $dish['name'],
                    'description' => $dish['description'],
                    'ingredients' => $dish['ingredients'],
                    'price' => $dish['price'],
                    'sale_price' => null,
                    'image' => $dish['image'],
                    'is_featured' => $dish['featured'],
                    'is_active' => true,
                    'sort_order' => $dish['sort_order'],
                    'meta_title' => $dish['name'].' | Paprika',
                    'meta_description' => $dish['description'],
                ]
            );

            $model->translations()->updateOrCreate(
                ['locale' => 'en'],
                [
                    'name' => $this->dishNameEn($dish['slug'], $dish['name']),
                    'slug' => $dish['slug'],
                    'description' => $this->dishDescriptionEn($dish['slug'], $dish['description']),
                    'ingredients' => $this->dishIngredientsEn($dish['slug'], $dish['ingredients']),
                    'meta_title' => $this->dishNameEn($dish['slug'], $dish['name']).' | Paprika',
                    'meta_description' => $this->dishDescriptionEn($dish['slug'], $dish['description']),
                ]
            );

            $model->translations()->updateOrCreate(
                ['locale' => 'el'],
                [
                    'name' => $this->dishNameEl($dish['slug'], $dish['name']),
                    'slug' => $dish['slug'],
                    'description' => $this->dishDescriptionEl($dish['slug'], $dish['description']),
                    'ingredients' => $this->dishIngredientsEl($dish['slug'], $dish['ingredients']),
                    'meta_title' => $this->dishNameEl($dish['slug'], $dish['name']).' | Paprika',
                    'meta_description' => $this->dishDescriptionEl($dish['slug'], $dish['description']),
                ]
            );
        }
    }

    private function optionGroups(Dish $dish, string $categorySlug): void
    {
        $dish->optionGroups()->delete();

        foreach ($this->optionGroupTemplates($dish, $categorySlug) as $group) {
            $options = $group['options'];
            unset($group['options']);

            $model = $dish->optionGroups()->create($group);

            // Add translations for option group
            $this->translateOptionGroup($model, $categorySlug);

            foreach ($options as $option) {
                $model->options()->create($option);
            }

            // Add translations for options
            $this->translateOptions($model);
        }
    }

    private function translateOptionGroup($model, string $categorySlug): void
    {
        $isVietnamese = $categorySlug === 'do-an-viet-nam';
        $isDrinks = $categorySlug === 'do-uong';

        // EN translations
        $enName = match ($model->slug) {
            'size' => 'Size',
            'spice-level' => 'Spice Level',
            'extras' => 'Extra Toppings',
            'exclude' => 'Exclude Ingredients',
            'ice' => 'Ice',
            default => $model->name,
        };

        $enDesc = match ($model->slug) {
            'size' => $isDrinks ? 'Choose drink size.' : 'Regular for one, Large with extra toppings.',
            'spice-level' => 'Adjust spice level to your taste.',
            'extras' => 'Add extra toppings to your meal.',
            'exclude' => 'Select if you don\'t want an ingredient.',
            'ice' => 'Customize ice level.',
            default => $model->description,
        };

        $model->translations()->updateOrCreate(
            ['locale' => 'en'],
            ['name' => $enName, 'description' => $enDesc]
        );

        // EL translations
        $elName = match ($model->slug) {
            'size' => 'Μέγεθος',
            'spice-level' => 'Επίπεδο Καυτερότητας',
            'extras' => 'Επιπλέον Προσθήκες',
            'exclude' => 'Αφαίρεση Συστατικών',
            'ice' => 'Πάγος',
            default => $model->name,
        };

        $elDesc = match ($model->slug) {
            'size' => $isDrinks ? 'Επιλέξτε μέγεθος ποτού.' : 'Regular για έναν, Large με επιπλέον toppings.',
            'spice-level' => 'Ρυθμίστε την καυτερότητα σύμφωνα με τη γεύση σας.',
            'extras' => 'Προσθέστε επιπλέον toppings στο πιάτο σας.',
            'exclude' => 'Επιλέξτε αν δεν θέλετε κάποιο συστατικό.',
            'ice' => 'Προσαρμόστε τον πάγο.',
            default => $model->description,
        };

        $model->translations()->updateOrCreate(
            ['locale' => 'el'],
            ['name' => $elName, 'description' => $elDesc]
        );
    }

    private function translateOptions($optionGroup): void
    {
        $isDrinks = $optionGroup->slug === 'ice';
        $isVietnamese = $optionGroup->dish->category?->slug === 'do-an-viet-nam';

        foreach ($optionGroup->options as $option) {
            // EN translations
            $enName = match ($option->slug) {
                'regular' => 'Regular',
                'large' => 'Large',
                'normal-ice' => 'Regular ice',
                'less-ice' => 'Less ice',
                'no-ice' => 'No ice',
                'no-spice' => 'No spice',
                'medium-spice' => 'Medium spice',
                'hot-spice' => 'Extra spicy',
                'extra-protein' => $isVietnamese ? 'Extra protein' : 'Extra grilled portion',
                'extra-herbs' => 'Extra herbs',
                'extra-sauce' => $isVietnamese ? 'Paprika spicy sauce' : 'Tzatziki / special sauce',
                'no-onion' => $isVietnamese ? 'No onion' : 'No onion',
                'no-coriander' => 'No coriander',
                'no-tomato' => 'No tomato',
                'sauce-on-side' => 'Sauce on side',
                default => $option->name,
            };

            $option->translations()->updateOrCreate(
                ['locale' => 'en'],
                ['name' => $enName]
            );

            // EL translations
            $elName = match ($option->slug) {
                'regular' => 'Regular',
                'large' => 'Large',
                'normal-ice' => 'Κανονικός πάγος',
                'less-ice' => 'Λίγος πάγος',
                'no-ice' => 'Χωρίς πάγο',
                'no-spice' => 'Χωρίς καυτερό',
                'medium-spice' => 'Μέτρια καυτερό',
                'hot-spice' => 'Πολύ καυτερό',
                'extra-protein' => $isVietnamese ? 'Επιπλέον κρέας' : 'Επιπλέον μερίδα ψητού',
                'extra-herbs' => 'Επιπλέον βότανα',
                'extra-sauce' => $isVietnamese ? 'Καυτερή σάλτσα Paprika' : 'Τζατζίκι / ειδική σάλτσα',
                'no-onion' => 'Χωρίς κρεμμύδι',
                'no-coriander' => 'Χωρίς κόλιανδρο',
                'no-tomato' => 'Χωρίς ντομάτα',
                'sauce-on-side' => 'Σάλτσα στο πλάι',
                default => $option->name,
            };

            $option->translations()->updateOrCreate(
                ['locale' => 'el'],
                ['name' => $elName]
            );
        }
    }

    private function optionGroupTemplates(Dish $dish, string $categorySlug): array
    {
        if ($categorySlug === 'do-uong') {
            return [
                [
                    'name' => 'Kích cỡ',
                    'slug' => 'size',
                    'type' => DishOptionGroup::TYPE_SINGLE,
                    'description' => 'Chọn dung tích đồ uống.',
                    'is_required' => true,
                    'min_select' => 1,
                    'max_select' => 1,
                    'sort_order' => 1,
                    'is_active' => true,
                    'options' => [
                        ['name' => 'Regular', 'slug' => 'regular', 'price_delta' => 0, 'is_default' => true, 'sort_order' => 1, 'is_active' => true],
                        ['name' => 'Large', 'slug' => 'large', 'price_delta' => 80, 'is_default' => false, 'sort_order' => 2, 'is_active' => true],
                    ],
                ],
                [
                    'name' => 'Đá',
                    'slug' => 'ice',
                    'type' => DishOptionGroup::TYPE_SINGLE,
                    'description' => 'Tùy chỉnh lượng đá.',
                    'is_required' => true,
                    'min_select' => 1,
                    'max_select' => 1,
                    'sort_order' => 2,
                    'is_active' => true,
                    'options' => [
                        ['name' => 'Đá bình thường', 'slug' => 'normal-ice', 'price_delta' => 0, 'is_default' => true, 'sort_order' => 1, 'is_active' => true],
                        ['name' => 'Ít đá', 'slug' => 'less-ice', 'price_delta' => 0, 'is_default' => false, 'sort_order' => 2, 'is_active' => true],
                        ['name' => 'Không đá', 'slug' => 'no-ice', 'price_delta' => 0, 'is_default' => false, 'sort_order' => 3, 'is_active' => true],
                    ],
                ],
            ];
        }

        $isVietnamese = $categorySlug === 'do-an-viet-nam';
        $proteinLabel = $isVietnamese ? 'Thêm thịt / nhân' : 'Thêm phần nướng';
        $sauceLabel = $isVietnamese ? 'Sốt cay Paprika' : 'Tzatziki / sốt riêng';

        return [
            [
                'name' => 'Kích cỡ',
                'slug' => 'size',
                'type' => DishOptionGroup::TYPE_SINGLE,
                'description' => 'Regular phù hợp một người, Large nhiều topping hơn.',
                'is_required' => true,
                'min_select' => 1,
                'max_select' => 1,
                'sort_order' => 1,
                'is_active' => true,
                'options' => [
                    ['name' => 'Regular', 'slug' => 'regular', 'price_delta' => 0, 'is_default' => true, 'sort_order' => 1, 'is_active' => true],
                    ['name' => 'Large', 'slug' => 'large', 'price_delta' => 200, 'is_default' => false, 'sort_order' => 2, 'is_active' => true],
                ],
            ],
            [
                'name' => 'Độ cay',
                'slug' => 'spice-level',
                'type' => DishOptionGroup::TYPE_SINGLE,
                'description' => 'Điều chỉnh độ cay theo khẩu vị.',
                'is_required' => true,
                'min_select' => 1,
                'max_select' => 1,
                'sort_order' => 2,
                'is_active' => true,
                'options' => [
                    ['name' => 'Không cay', 'slug' => 'no-spice', 'price_delta' => 0, 'is_default' => false, 'sort_order' => 1, 'is_active' => true],
                    ['name' => 'Cay vừa', 'slug' => 'medium-spice', 'price_delta' => 0, 'is_default' => true, 'sort_order' => 2, 'is_active' => true],
                    ['name' => 'Cay nhiều', 'slug' => 'hot-spice', 'price_delta' => 0, 'is_default' => false, 'sort_order' => 3, 'is_active' => true],
                ],
            ],
            [
                'name' => 'Topping thêm',
                'slug' => 'extras',
                'type' => DishOptionGroup::TYPE_MULTIPLE,
                'description' => 'Chọn thêm topping cho khẩu phần.',
                'is_required' => false,
                'min_select' => 0,
                'max_select' => 3,
                'sort_order' => 3,
                'is_active' => true,
                'options' => [
                    ['name' => $proteinLabel, 'slug' => 'extra-protein', 'price_delta' => 200, 'is_default' => false, 'sort_order' => 1, 'is_active' => true],
                    ['name' => 'Thêm rau thơm', 'slug' => 'extra-herbs', 'price_delta' => 50, 'is_default' => false, 'sort_order' => 2, 'is_active' => true],
                    ['name' => $sauceLabel, 'slug' => 'extra-sauce', 'price_delta' => 50, 'is_default' => false, 'sort_order' => 3, 'is_active' => true],
                ],
            ],
            [
                'name' => 'Bỏ thành phần',
                'slug' => 'exclude',
                'type' => DishOptionGroup::TYPE_EXCLUDE,
                'description' => 'Chọn nếu khách không dùng một thành phần.',
                'is_required' => false,
                'min_select' => 0,
                'max_select' => null,
                'sort_order' => 4,
                'is_active' => true,
                'options' => [
                    ['name' => $isVietnamese ? 'Không hành' : 'Không hành tây', 'slug' => 'no-onion', 'price_delta' => 0, 'is_default' => false, 'sort_order' => 1, 'is_active' => true],
                    ['name' => $isVietnamese ? 'Không rau mùi' : 'Không cà chua', 'slug' => $isVietnamese ? 'no-coriander' : 'no-tomato', 'price_delta' => 0, 'is_default' => false, 'sort_order' => 2, 'is_active' => true],
                    ['name' => 'Để sốt riêng', 'slug' => 'sauce-on-side', 'price_delta' => 0, 'is_default' => false, 'sort_order' => 3, 'is_active' => true],
                ],
            ],
        ];
    }

    private function dishes(): array
    {
        return [
            [
                'name' => 'Phở bò',
                'slug' => 'pho-bo',
                'category' => 'mon-viet-nam',
                'description' => 'Phở bò nóng với nước dùng thơm, bánh phở mềm và thịt bò thái mỏng.',
                'ingredients' => 'Bánh phở, thịt bò, nước dùng, hành, rau thơm, chanh.',
                'price' => 950,
                'image' => '/paprika/menu-vietnamese/pho-bo.jpg',
                'featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Phở gà',
                'slug' => 'pho-ga',
                'category' => 'mon-viet-nam',
                'description' => 'Phở gà thanh nhẹ với nước dùng ấm, thịt gà mềm và rau thơm.',
                'ingredients' => 'Bánh phở, thịt gà, nước dùng, hành, rau thơm, chanh.',
                'price' => 800,
                'image' => '/paprika/menu-vietnamese/pho-ga.jpg',
                'featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Phở cuốn',
                'slug' => 'pho-cuon',
                'category' => 'mon-viet-nam',
                'description' => 'Phở cuốn tươi, gọn vị, ăn kèm nước chấm chua ngọt.',
                'ingredients' => 'Bánh phở cuốn, thịt, rau thơm, rau sống, nước chấm.',
                'price' => 550,
                'image' => '/paprika/menu-vietnamese/pho-cuon.jpg',
                'featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Thịt lợn xiên nướng',
                'slug' => 'thit-lon-xien-nuong',
                'category' => 'mon-viet-nam',
                'description' => 'Xiên thịt lợn nướng đậm vị, thơm mùi than và gia vị Việt.',
                'ingredients' => 'Thịt lợn, gia vị ướp, hành, tỏi, nước chấm.',
                'price' => 230,
                'image' => '/paprika/menu-vietnamese/thit-lon-xien-nuong.jpg',
                'featured' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Thịt gà xiên nướng',
                'slug' => 'thit-ga-xien-nuong',
                'category' => 'mon-viet-nam',
                'description' => 'Xiên gà nướng mềm, thơm gia vị, hợp ăn nhanh hoặc gọi kèm.',
                'ingredients' => 'Thịt gà, gia vị ướp, hành, tỏi, nước chấm.',
                'price' => 230,
                'image' => '/paprika/menu-vietnamese/thit-ga-xien-nuong.jpg',
                'featured' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Cánh gà KFC',
                'slug' => 'canh-ga-kfc',
                'category' => 'mon-viet-nam',
                'description' => 'Cánh gà chiên giòn, lớp vỏ đậm vị và phần thịt mọng bên trong.',
                'ingredients' => 'Cánh gà, bột chiên, gia vị, sốt chấm.',
                'price' => 580,
                'image' => '/paprika/menu-vietnamese/canh-ga-kfc.jpg',
                'featured' => false,
                'sort_order' => 6,
            ],
            [
                'name' => 'Bún trộn thịt nướng',
                'slug' => 'bun-tron-thit-nuong',
                'category' => 'mon-viet-nam',
                'description' => 'Bún trộn thịt nướng với rau tươi, nước mắm pha và topping giòn.',
                'ingredients' => 'Bún, thịt nướng, rau sống, đồ chua, nước mắm pha.',
                'price' => 800,
                'image' => '/paprika/menu-vietnamese/bun-tron-thit-nuong.jpg',
                'featured' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Nem rán',
                'slug' => 'nem-ran',
                'category' => 'mon-viet-nam',
                'description' => 'Nem rán giòn rụm, nhân thịt rau củ, ăn kèm nước chấm.',
                'ingredients' => 'Bánh đa nem, thịt, rau củ, miến, nước chấm.',
                'price' => 550,
                'image' => '/paprika/menu-vietnamese/nem-ran.jpg',
                'featured' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Mực nhồi thịt nướng',
                'slug' => 'muc-nhoi-thit-nuong',
                'category' => 'mon-viet-nam',
                'description' => 'Mực nhồi thịt nướng thơm, chắc vị biển và phần nhân đậm đà.',
                'ingredients' => 'Mực, thịt, gia vị, rau thơm, nước chấm.',
                'price' => 950,
                'image' => '/paprika/menu-vietnamese/muc-nhoi-thit-nuong.jpg',
                'featured' => false,
                'sort_order' => 9,
            ],
            [
                'name' => 'Bánh mỳ thịt nướng',
                'slug' => 'banh-my-thit-nuong',
                'category' => 'mon-viet-nam',
                'description' => 'Bánh mỳ thịt nướng với đồ chua, rau thơm và sốt đậm vị.',
                'ingredients' => 'Bánh mỳ, thịt nướng, đồ chua, rau thơm, sốt.',
                'price' => 580,
                'image' => '/paprika/menu-vietnamese/banh-my-thit-nuong.jpg',
                'featured' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Bánh mỳ pate thịt nguội',
                'slug' => 'banh-my-pate-thit-nguoi',
                'category' => 'mon-viet-nam',
                'description' => 'Bánh mỳ pate thịt nguội kiểu Việt, béo thơm và dễ ăn.',
                'ingredients' => 'Bánh mỳ, pate, thịt nguội, đồ chua, rau thơm, sốt.',
                'price' => 580,
                'image' => '/paprika/menu-vietnamese/banh-my-pate-thit-nguoi.jpg',
                'featured' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'Tôm xiên nướng',
                'slug' => 'tom-xien-nuong',
                'category' => 'mon-viet-nam',
                'description' => 'Tôm xiên nướng thơm, ngọt thịt, hợp gọi kèm bún hoặc món khai vị.',
                'ingredients' => 'Tôm, gia vị ướp, chanh, rau thơm, nước chấm.',
                'price' => 990,
                'image' => '/paprika/menu-vietnamese/tom-xien-nuong.jpg',
                'featured' => false,
                'sort_order' => 12,
            ],
            [
                'name' => 'Phở hải sản',
                'slug' => 'pho-hai-san',
                'category' => 'mon-viet-nam',
                'description' => 'Phở hải sản nóng với nước dùng thơm và topping hải sản.',
                'ingredients' => 'Bánh phở, hải sản, nước dùng, hành, rau thơm, chanh.',
                'price' => 990,
                'image' => '/paprika/menu-vietnamese/pho-hai-san.jpg',
                'featured' => true,
                'sort_order' => 13,
            ],
            [
                'name' => 'Mỳ xào hải sản',
                'slug' => 'my-xao-hai-san',
                'category' => 'mon-viet-nam',
                'description' => 'Mỳ xào hải sản đậm vị, sợi mỳ săn cùng rau củ và topping hải sản.',
                'ingredients' => 'Mỳ, hải sản, rau củ, hành tỏi, sốt xào.',
                'price' => 950,
                'image' => '/paprika/menu-vietnamese/my-xao-hai-san.jpg',
                'featured' => false,
                'sort_order' => 14,
            ],
            [
                'name' => 'Mỳ xào thịt bò',
                'slug' => 'my-xao-thit-bo',
                'category' => 'mon-viet-nam',
                'description' => 'Mỳ xào thịt bò mềm, rau củ giòn và sốt xào thơm.',
                'ingredients' => 'Mỳ, thịt bò, rau củ, hành tỏi, sốt xào.',
                'price' => 850,
                'image' => '/paprika/menu-vietnamese/my-xao-thit-bo.jpg',
                'featured' => false,
                'sort_order' => 15,
            ],
            [
                'name' => 'Chả tôm chiên giòn',
                'slug' => 'cha-tom-chien-gion',
                'category' => 'mon-viet-nam',
                'description' => 'Chả tôm chiên giòn, thơm vị tôm và hợp ăn kèm sốt.',
                'ingredients' => 'Tôm, bột, gia vị, dầu chiên, sốt chấm.',
                'price' => 580,
                'image' => '/paprika/menu-vietnamese/cha-tom-chien-gion.jpg',
                'featured' => false,
                'sort_order' => 16,
            ],
            [
                'name' => 'Bánh bao nhân thịt',
                'slug' => 'banh-bao-nhan-thit',
                'category' => 'mon-viet-nam',
                'description' => 'Bánh bao nhân thịt mềm nóng, phần nhân mặn thơm và đầy đặn.',
                'ingredients' => 'Bột bánh bao, thịt, hành, mộc nhĩ, gia vị.',
                'price' => 620,
                'image' => '/paprika/menu-vietnamese/banh-bao-nhan-thit.jpg',
                'featured' => false,
                'sort_order' => 17,
            ],
            [
                'name' => 'Xá xíu',
                'slug' => 'xa-xiu',
                'category' => 'mon-viet-nam',
                'description' => 'Xá xíu mềm thơm, vị mặn ngọt cân bằng, dùng riêng hoặc ăn kèm.',
                'ingredients' => 'Thịt xá xíu, gia vị ướp, sốt.',
                'price' => 520,
                'image' => '/paprika/menu-vietnamese/xa-xiu.jpg',
                'featured' => false,
                'sort_order' => 18,
            ],
            [
                'name' => 'Chả bò viên chiên',
                'slug' => 'cha-bo-vien-chien',
                'category' => 'mon-viet-nam',
                'description' => 'Chả bò viên chiên nóng giòn, ăn kèm tương chấm.',
                'ingredients' => 'Bò viên, dầu chiên, sốt chấm.',
                'price' => 580,
                'image' => '/paprika/menu-vietnamese/cha-bo-vien-chien.jpg',
                'featured' => false,
                'sort_order' => 19,
            ],
            [
                'name' => 'Chả cá viên chiên',
                'slug' => 'cha-ca-vien-chien',
                'category' => 'mon-viet-nam',
                'description' => 'Chả cá viên chiên dai giòn, món ăn vặt dễ gọi kèm.',
                'ingredients' => 'Cá viên, dầu chiên, sốt chấm.',
                'price' => 580,
                'image' => '/paprika/menu-vietnamese/cha-ca-vien-chien.jpg',
                'featured' => false,
                'sort_order' => 20,
            ],
        ];

        return [
            [
                'name' => 'Phở Bò',
                'slug' => 'beef-pho',
                'category' => 'do-an-viet-nam',
                'description' => 'Phở truyền thống với bánh phở, thịt bò và rau thơm.',
                'ingredients' => 'Bánh phở, thịt bò, nước dùng, rau thơm, chanh.',
                'price' => 950,
                'image' => '/paprika/menu/beef-pho.webp',
                'featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Phở Gà',
                'slug' => 'chicken-pho',
                'category' => 'do-an-viet-nam',
                'description' => 'Phở gà ấm nóng với nước dùng thơm và rau thơm tươi.',
                'ingredients' => 'Bánh phở, thịt gà, nước dùng, rau thơm, chanh.',
                'price' => 800,
                'image' => '/paprika/menu/chicken-pho.webp',
                'featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Nem Rán',
                'slug' => 'fried-nem',
                'category' => 'do-an-viet-nam',
                'description' => 'Nem rán giòn rụm nhân rau củ ăn kèm nước chấm.',
                'ingredients' => 'Bánh tráng, rau củ, rau thơm, nước chấm.',
                'price' => 550,
                'image' => '/paprika/menu/fried-nem.webp',
                'featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Phở Cuốn',
                'slug' => 'pho-rolls',
                'category' => 'do-an-viet-nam',
                'description' => 'Phở cuốn tươi với thịt bò, rau thơm và rau sống.',
                'ingredients' => 'Bánh phở, thịt bò, rau thơm, rau sống, nước chấm.',
                'price' => 550,
                'image' => '/paprika/menu/pho-rolls.webp',
                'featured' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Bánh Mì',
                'slug' => 'banh-mi',
                'category' => 'do-an-viet-nam',
                'description' => 'Bánh mì Việt Nam với thịt, đồ chua, rau thơm và nước sốt.',
                'ingredients' => 'Bánh mì, thịt, đồ chua, rau thơm, nước sốt.',
                'price' => 580,
                'image' => '/paprika/menu/banh-mi.webp',
                'featured' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Salad Hy Lạp',
                'slug' => 'greek-salad',
                'category' => 'do-an-hy-lap',
                'description' => 'Salad Hy Lạp truyền thống với feta, cà chua, dưa chuột, hành tây, olive và oregano.',
                'ingredients' => 'Feta, cà chua, dưa chuột, hành tím, olive, dầu olive, oregano.',
                'price' => 850,
                'image' => '/paprika/menu/greek-salad.webp',
                'featured' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Xiên Souvlaki',
                'slug' => 'souvlaki-skewers',
                'category' => 'do-an-hy-lap',
                'description' => 'Xiên nướng thảo mộc với chanh và nước chấm đặc trưng.',
                'ingredients' => 'Xiên thịt ướp, chanh, thảo mộc, nước chấm.',
                'price' => 230,
                'image' => '/paprika/menu/souvlaki.webp',
                'featured' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Gyros',
                'slug' => 'gyros',
                'category' => 'do-an-hy-lap',
                'description' => 'Bánh pita Hy Lạp kẹp thịt nướng, khoai tây chiên, rau thơm và sốt kem.',
                'ingredients' => 'Pita, thịt nướng, khoai tây chiên, rau thơm, sốt.',
                'price' => 850,
                'image' => '/paprika/menu/gyros.webp',
                'featured' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Bifteki',
                'slug' => 'bifteki',
                'category' => 'do-an-hy-lap',
                'description' => 'Chả nướng Hy Lạp ăn kèm khoai tây chiên, chanh và nước sốt.',
                'ingredients' => 'Chả nướng, khoai tây chiên, chanh, nước sốt.',
                'price' => 950,
                'image' => '/paprika/menu/bifteki.webp',
                'featured' => false,
                'sort_order' => 9,
            ],
            [
                'name' => 'Sườn Cừu Nướng',
                'slug' => 'lamb-chops',
                'category' => 'do-an-hy-lap',
                'description' => 'Sườn cừu nướng thảo mộc với chanh và dầu olive.',
                'ingredients' => 'Sườn cừu, thảo mộc, chanh, dầu olive.',
                'price' => 1450,
                'image' => '/paprika/menu/lamb-chops.webp',
                'featured' => false,
                'sort_order' => 10,
            ],
            [
                'name' => 'Nước Khoáng',
                'slug' => 'mineral-water',
                'category' => 'do-uong',
                'description' => 'Nước khoáng đóng chai mát lạnh.',
                'ingredients' => 'Nước khoáng.',
                'price' => 150,
                'image' => '/paprika/menu/mineral-water.webp',
                'featured' => false,
                'sort_order' => 11,
            ],
            [
                'name' => 'Nước Ngọt',
                'slug' => 'soft-drink',
                'category' => 'do-uong',
                'description' => 'Nước ngọt có gas mát lạnh.',
                'ingredients' => 'Nước ngọt.',
                'price' => 250,
                'image' => '/paprika/menu/soft-drink.webp',
                'featured' => false,
                'sort_order' => 12,
            ],
            [
                'name' => 'Trà Đá',
                'slug' => 'iced-tea',
                'category' => 'do-uong',
                'description' => 'Trà đá giải khát cho bữa ăn nhanh.',
                'ingredients' => 'Trà, đá.',
                'price' => 300,
                'image' => '/paprika/menu/iced-tea.webp',
                'featured' => false,
                'sort_order' => 13,
            ],
            [
                'name' => 'Cà Phê Hy Lạp',
                'slug' => 'greek-coffee',
                'category' => 'do-uong',
                'description' => 'Cà phê Hy Lạp truyền thống pha nóng.',
                'ingredients' => 'Cà phê Hy Lạp.',
                'price' => 250,
                'image' => '/paprika/menu/greek-coffee.webp',
                'featured' => false,
                'sort_order' => 14,
            ],
        ];
    }

    private function dishNameEn(string $slug, string $viName): string
    {
        return match ($slug) {
            'beef-pho' => 'Beef Pho',
            'chicken-pho' => 'Chicken Pho',
            'fried-nem' => 'Fried Spring Rolls',
            'pho-rolls' => 'Fresh Pho Rolls',
            'banh-mi' => 'Vietnamese Baguette',
            'greek-salad' => 'Greek Salad',
            'souvlaki-skewers' => 'Souvlaki Skewers',
            'gyros' => 'Gyros',
            'bifteki' => 'Bifteki',
            'lamb-chops' => 'Grilled Lamb Chops',
            'mineral-water' => 'Mineral Water',
            'soft-drink' => 'Soft Drink',
            'iced-tea' => 'Iced Tea',
            'greek-coffee' => 'Greek Coffee',
            default => $viName,
        };
    }

    private function dishDescriptionEn(string $slug, string $viDescription): string
    {
        return match ($slug) {
            'beef-pho' => 'Traditional Vietnamese pho with rice noodles, tender beef and fresh herbs.',
            'chicken-pho' => 'Warm chicken pho with fragrant broth and fresh herbs.',
            'fried-nem' => 'Crispy fried spring rolls with vegetable filling and dipping sauce.',
            'pho-rolls' => 'Fresh rice paper rolls with beef, herbs and fresh vegetables.',
            'banh-mi' => 'Vietnamese baguette filled with meat, pickled vegetables, herbs and sauce.',
            'greek-salad' => 'Traditional Greek salad with feta, tomato, cucumber, onion, olives and oregano.',
            'souvlaki-skewers' => 'Herb-marinated grilled skewers with lemon and signature sauce.',
            'gyros' => 'Greek pita filled with grilled meat, fries, herbs and creamy sauce.',
            'bifteki' => 'Greek grilled patties served with fries, lemon and sauce.',
            'lamb-chops' => 'Herb-grilled lamb chops with lemon and olive oil.',
            'mineral-water' => 'Refreshing bottled mineral water.',
            'soft-drink' => 'Refreshing chilled soft drink.',
            'iced-tea' => 'Refreshing iced tea for a quick meal.',
            'greek-coffee' => 'Traditional Greek coffee served hot.',
            default => $viDescription,
        };
    }

    private function dishIngredientsEn(string $slug, string $viIngredients): string
    {
        return match ($slug) {
            'beef-pho' => 'Rice noodles, beef, broth, fresh herbs, lime.',
            'chicken-pho' => 'Rice noodles, chicken, broth, fresh herbs, lime.',
            'fried-nem' => 'Rice paper, vegetables, fresh herbs, dipping sauce.',
            'pho-rolls' => 'Rice paper, beef, herbs, fresh vegetables, dipping sauce.',
            'banh-mi' => 'Baguette, meat, pickled vegetables, herbs, sauce.',
            'greek-salad' => 'Feta, tomato, cucumber, red onion, olives, olive oil, oregano.',
            'souvlaki-skewers' => 'Marinated meat skewers, lemon, herbs, dipping sauce.',
            'gyros' => 'Pita, grilled meat, fries, herbs, sauce.',
            'bifteki' => 'Grilled patties, fries, lemon, sauce.',
            'lamb-chops' => 'Lamb chops, herbs, lemon, olive oil.',
            'mineral-water' => 'Mineral water.',
            'soft-drink' => 'Soft drink.',
            'iced-tea' => 'Tea, ice.',
            'greek-coffee' => 'Greek coffee.',
            default => $viIngredients,
        };
    }

    private function dishNameEl(string $slug, string $viName): string
    {
        return match ($slug) {
            'beef-pho' => 'Φο Μπο',
            'chicken-pho' => 'Φο Κο',
            'fried-nem' => 'Τηγανητές Ρολά Νεμ',
            'pho-rolls' => 'Ρολά Φο',
            'banh-mi' => 'Μπανχ Μι',
            'greek-salad' => 'Ελληνική Σαλάτα',
            'souvlaki-skewers' => 'Σουβλάκι',
            'gyros' => 'Γύρος',
            'bifteki' => 'Μπιφτέκι',
            'lamb-chops' => 'Αμνομπριζόλες στη Σχάρα',
            'mineral-water' => 'Μεταλλικό Νερό',
            'soft-drink' => 'Αναψυκτικό',
            'iced-tea' => 'Παγωμένο Τσάι',
            'greek-coffee' => 'Ελληνικός Καφές',
            default => $viName,
        };
    }

    private function dishDescriptionEl(string $slug, string $viDescription): string
    {
        return match ($slug) {
            'beef-pho' => 'Παραδοσιακό φο με μανιτάρια, μοσχαρίσιο κρέας και φρέσκα βότανα.',
            'chicken-pho' => 'Φο κοτόπουλο με αρωματικό ζωμό και φρέσκα βότανα.',
            'fried-nem' => 'Τραγανά τηγανητά ρολά με γέμιση λαχανικών και σάλτσα ντιπ.',
            'pho-rolls' => 'Φρέσκα ρολά φο με μοσχαρίσιο κρέας, βότανα και λαχανικά.',
            'banh-mi' => 'Βιετναμέζικη μπαγκέτα με κρέας, τουρσί, βότανα και σάλτσα.',
            'greek-salad' => 'Παραδοσιακή ελληνική σαλάτα με φέτα, ντομάτα, αγγούρι, κρεμμύδι, ελιές και ρίγανη.',
            'souvlaki-skewers' => 'Σούβλα με μυρωδικά, λεμόνι και ειδική σάλτσα.',
            'gyros' => 'Ελληνική πίτα με ψητό κρέας, πατάτες, λαχανικά και σάλτσα κρέμας.',
            'bifteki' => 'Ελληνικά μπιφτέκια στη σχάρα με πατάτες, λεμόνι και σάλτσα.',
            'lamb-chops' => 'Αμνομπριζόλες στη σχάρα με μυρωδικά, λεμόνι και ελαιόλαδο.',
            'mineral-water' => 'Εμφιαλωμένο δροσερό νερό.',
            'soft-drink' => 'Δροσερό αναψυκτικό με ανθρακικό.',
            'iced-tea' => 'Παγωμένο τσάι για γρήγορο γεύμα.',
            'greek-coffee' => 'Παραδοσιακός ελληνικός καφές ζεστός.',
            default => $viDescription,
        };
    }

    private function dishIngredientsEl(string $slug, string $viIngredients): string
    {
        return match ($slug) {
            'beef-pho' => 'Φιδέ φο, μοσχαρίσιο κρέας, ζωμός, φρέσκα βότανα, λάιμ.',
            'chicken-pho' => 'Φιδέ φο, κοτόπουλο, ζωμός, φρέσκα βότανα, λάιμ.',
            'fried-nem' => 'Ρύζι, λαχανικά, φρέσκα βότανα, σάλτσα ντιπ.',
            'pho-rolls' => 'Ρύζι, μοσχαρίσιο κρέας, βότανα, λαχανικά, σάλτσα ντιπ.',
            'banh-mi' => 'Ψωμί μπαγκέτα, κρέας, τουρσί, βότανα, σάλτσα.',
            'greek-salad' => 'Φέτα, ντομάτα, αγγούρι, κρεμμύδι, ελιές, ελαιόλαδο, ρίγανη.',
            'souvlaki-skewers' => 'Μαριναρισμένο κρέας, λεμόνι, μυρωδικά, σάλτσα.',
            'gyros' => 'Πίτα, ψητό κρέας, πατάτες τηγανητές, σάλτσα.',
            'bifteki' => 'Μπιφτέκι, πατάτες τηγανητές, λεμόνι, σάλτσα.',
            'lamb-chops' => 'Αμνομπριζόλες, μυρωδικά, λεμόνι, ελαιόλαδο.',
            'mineral-water' => 'Μεταλλικό νερό.',
            'soft-drink' => 'Αναψυκτικό.',
            'iced-tea' => 'Τσάι, πάγος.',
            'greek-coffee' => 'Ελληνικός καφές.',
            default => $viIngredients,
        };
    }
}
