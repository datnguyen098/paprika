<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Dish;
use App\Models\DishOptionGroup;
use App\Models\DishTimeSlot;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\SiteSetting;
use App\Models\User;
use App\Mail\CustomerPaymentConfirmedMail;
use App\Mail\CustomerOrderConfirmationMail;
use App\Mail\NewOrderNotificationMail;
use App\Services\UploadService;
use App\Support\PendingVivaPayment;
use App\Support\StorefrontNavigation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_public_pages_are_available(): void
    {
        $this->get('/')->assertRedirect('/vi');

        foreach (['/vi', '/vi/gioi-thieu', '/vi/khong-gian', '/vi/thuc-don', '/vi/blog', '/vi/dat-ban', '/vi/lien-he', '/sitemap.xml', '/robots.txt', '/vi/trang/chinh-sach-dat-ban'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_storefront_helpers_do_not_require_a_session_store(): void
    {
        $originalRequest = request();
        $this->app->instance('request', HttpRequest::create('/missing-page', 'GET'));

        try {
            $this->assertNotNull(active_branch_id());
            $this->assertNotNull(active_branch());
            $this->assertSame(0, app(\App\Services\CartService::class)->count());
            $this->assertSame(0, app(\App\Services\CartService::class)->subtotal());
        } finally {
            $this->app->instance('request', $originalRequest);
        }
    }

    public function test_seeded_menu_contains_catalog_dishes_with_categories_images_and_evening_slot(): void
    {
        $greekCategory = Category::where('slug', 'do-an-hy-lap')->firstOrFail();
        $vietnameseCategory = Category::where('slug', 'do-an-viet-nam')->firstOrFail();
        $drinkCategory = Category::where('slug', 'do-uong')->firstOrFail();

        $this->assertDatabaseCount('categories', 3);
        $this->assertDatabaseCount('dishes', 75);
        $this->assertDatabaseCount('category_translations', 6);
        $this->assertDatabaseCount('dish_translations', 150);
        $this->assertSame(39, $greekCategory->dishes()->count());
        $this->assertSame(20, $vietnameseCategory->dishes()->count());
        $this->assertSame(16, $drinkCategory->dishes()->count());
        $this->assertSame(0, DB::table('dish_option_groups')->count());

        $this->assertDatabaseHas(Dish::class, [
            'name' => 'Bánh phô mai Skopelos',
            'slug' => 'banh-pho-mai-skopelos',
            'price' => 600,
            'image' => '/paprika/menu-catalog/item-001.jpg',
        ]);

        $this->assertDatabaseHas(Dish::class, [
            'name' => 'Phở bò',
            'slug' => 'pho-bo',
            'price' => 950,
            'image' => '/paprika/menu-catalog/item-040.jpg',
        ]);

        $this->assertDatabaseHas(Dish::class, [
            'name' => 'Tuborg soda 330 ml',
            'slug' => 'tuborg-soda-330-ml',
            'price' => 200,
            'image' => '/paprika/board.jpg',
        ]);

        $this->assertDatabaseHas(Dish::class, [
            'name' => 'Chả cá viên chiên',
            'slug' => 'cha-ca-vien-chien',
            'price' => 580,
            'image' => '/paprika/menu-catalog/item-059.jpg',
        ]);

        $this->assertDatabaseHas('category_translations', [
            'category_id' => $vietnameseCategory->id,
            'locale' => 'en',
            'name' => 'Vietnamese Food',
        ]);

        $this->assertDatabaseHas('dish_translations', [
            'locale' => 'en',
            'slug' => 'pho-bo',
            'name' => 'Beef pho',
        ]);

        $this->assertDatabaseHas('dish_translations', [
            'locale' => 'el',
            'slug' => 'pho-bo',
            'name' => 'Μοσχαρίσιο Φο',
        ]);

        $eveningSlot = DishTimeSlot::where('name', 'Buổi tối')->firstOrFail();
        $this->assertSame('18:00', (string) $eveningSlot->start_time);
        $this->assertSame('00:30', (string) $eveningSlot->end_time);
        $this->assertSame(21, DB::table('dish_dish_time_slot')->where('dish_time_slot_id', $eveningSlot->id)->count());

        Dish::query()->each(function (Dish $dish): void {
            $this->assertTrue(file_exists(public_path(ltrim((string) $dish->image, '/'))), "Missing image for {$dish->name}");
        });
    }

    public function test_homepage_links_to_gallery_page(): void
    {
        $this->get('/vi')
            ->assertOk()
            ->assertSee('/vi/khong-gian', false)
            ->assertSee(__('site.header.nav_gallery'));
    }

    public function test_home_service_cards_are_clickable_links(): void
    {
        $response = $this->get('/vi')
            ->assertOk();

        $response->assertSee('href="'.route('localized.vi.menu.index').'" class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#064E3B]', false);
        $response->assertSee('href="'.route('localized.vi.menu.index').'" class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#B91C1C]', false);
        $response->assertSee('href="'.route('localized.vi.reservations.create').'" class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#92400E]', false);
    }

    public function test_public_pages_include_favicon_fallbacks(): void
    {
        $this->get('/vi')
            ->assertOk()
            ->assertSee('rel="icon" href="'.asset('favicon.ico').'" sizes="any"', false)
            ->assertSee('rel="icon" type="image/png" sizes="48x48" href="'.asset('favicon-48x48.png').'"', false)
            ->assertSee('rel="apple-touch-icon" sizes="180x180" href="'.asset('apple-touch-icon.png').'"', false);
    }

    public function test_storefront_uses_built_assets_instead_of_runtime_tailwind_cdn(): void
    {
        $response = $this->get('/vi')
            ->assertOk();

        $response->assertDontSee('cdn.tailwindcss.com', false);
        $response->assertDontSee('fonts.googleapis.com', false);
        $response->assertSee('rel="preload" href="'.asset('storefront/chat-widget.css'), false);
    }

    public function test_gallery_page_uses_localized_copy(): void
    {
        $this->get('/vi/khong-gian')
            ->assertOk()
            ->assertSee('Không gian quán')
            ->assertSee('Không gian Paprika')
            ->assertSee('Một vài góc nhỏ');

        $this->get('/en/space')
            ->assertOk()
            ->assertSee('Restaurant space')
            ->assertSee('Paprika Space')
            ->assertSee('A few real corners');

        $this->get('/el/choros')
            ->assertOk()
            ->assertSee('Ο χώρος μας')
            ->assertSee('Ο χώρος της Paprika')
            ->assertSee('Μερικές αληθινές γωνιές');
    }

    public function test_about_page_uses_current_restaurant_timeline_copy(): void
    {
        $this->get('/vi/gioi-thieu')
            ->assertOk()
            ->assertSee('2018')
            ->assertSee('Năm 2018, nhà hàng bắt đầu hành trình')
            ->assertSee('Hương Vị Việt Ở Patras')
            ->assertSee('Đầu bếp A BUU')
            ->assertSee('/paprika/chef-a-buu.webp')
            ->assertSee('Eleni Papadopoulou')
            ->assertSee('/paprika/nutrition-specialist-ai.webp')
            ->assertSee('Theodoris Malataras')
            ->assertSee('/paprika/founder-theodoris-malataras.webp');

        $this->assertFileExists(public_path('paprika/chef-a-buu.webp'));
        $this->assertFileExists(public_path('paprika/nutrition-specialist-ai.webp'));
        $this->assertFileExists(public_path('paprika/founder-theodoris-malataras.webp'));
    }

    public function test_storefront_navigation_uses_admin_menu_configuration(): void
    {
        NavigationMenu::query()->delete();

        $headerMenu = NavigationMenu::create([
            'title' => 'Không gian admin',
            'url' => 'route:gallery.index',
            'location' => 'header',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $headerMenu->translations()->create([
            'locale' => 'en',
            'title' => 'Admin space',
            'url' => 'route:gallery.index',
        ]);

        NavigationMenu::create([
            'title' => 'Footer đặt bàn',
            'url' => 'route:reservations.create',
            'location' => 'footer',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get('/vi')
            ->assertOk()
            ->assertSee('Không gian admin')
            ->assertSee('/vi/khong-gian', false)
            ->assertSee('Footer đặt bàn')
            ->assertSee('/vi/dat-ban', false);

        $this->get('/en')
            ->assertOk()
            ->assertSee('Admin space')
            ->assertSee('/en/space', false);
    }

    public function test_seeded_navigation_uses_locale_aware_route_urls(): void
    {
        $this->assertDatabaseHas(NavigationMenu::class, [
            'title' => 'Không gian',
            'url' => 'route:gallery.index',
            'location' => 'header',
        ]);

        $this->get('/vi')
            ->assertOk()
            ->assertSee('/vi/khong-gian', false);

        $this->get('/en')
            ->assertOk()
            ->assertSee('/en/space', false);

        $this->get('/el')
            ->assertOk()
            ->assertSee('/el/choros', false);
    }

    public function test_footer_shows_branch_hotline_and_phone(): void
    {
        $branch = Branch::active()->orderBy('sort_order')->orderBy('name')->firstOrFail();
        $branch->update([
            'hotline' => '694 041 4566',
            'phone' => '261 031 6200',
        ]);
        SiteSetting::set('phone', '210 000 0000', 'text', 'general');

        $this->get('/vi')
            ->assertOk()
            ->assertSee('694 041 4566')
            ->assertSee('261 031 6200')
            ->assertSee('aria-hidden="true">-</span>', false);
    }

    public function test_default_navigation_fallback_does_not_include_gallery_link(): void
    {
        NavigationMenu::query()->delete();
        app()->setLocale('vi');

        $headerUrls = StorefrontNavigation::forLocation('header')->pluck('url');
        $footerUrls = StorefrontNavigation::forLocation('footer')->pluck('url');

        $this->assertFalse($headerUrls->contains(fn (string $url): bool => str_ends_with($url, '/vi/khong-gian')));
        $this->assertFalse($footerUrls->contains(fn (string $url): bool => str_ends_with($url, '/vi/khong-gian')));
    }

    public function test_admin_area_requires_admin_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_login_redirects_admin_to_dashboard(): void
    {
        $this->post(route('login.store'), [
            'email' => 'admin@paprika-patras.gr',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_chat_widget_can_start_and_send_message(): void
    {
        $startResponse = $this->postJson(route('chat.start'), [
            'visitor_name' => 'Mai Lan',
            'phone' => '0912000000',
            'message' => 'Tôi muốn tư vấn đặt bàn tối nay.',
        ])->assertOk();

        $sessionId = $startResponse->json('session_id');

        $this->withSession(['chat_session_id' => $sessionId])
            ->postJson(route('chat.send', $sessionId), [
                'message' => 'Nhà hàng còn bàn cho 4 người không?',
            ])
            ->assertOk()
            ->assertJsonCount(2, 'messages');

        $this->assertDatabaseHas(ChatSession::class, [
            'public_id' => $sessionId,
            'visitor_name' => 'Mai Lan',
        ]);
    }

    public function test_admin_notifications_return_pending_counts(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();

        $this->postJson(route('chat.start'), [
            'visitor_name' => 'Mai Lan',
            'phone' => '0912000000',
            'message' => 'Tôi muốn tư vấn đặt bàn tối nay.',
        ])->assertOk();

        Reservation::create([
            'name' => 'Khách đặt bàn',
            'phone' => '0909000000',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '18:00',
            'guests' => 2,
            'status' => 'pending',
        ]);

        Contact::create([
            'name' => 'Khách liên hệ',
            'message' => 'Tôi cần hỏi thông tin thực đơn.',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.notifications.index'))
            ->assertOk()
            ->assertJsonPath('counts.chat', 1)
            ->assertJsonPath('counts.reservations', 1)
            ->assertJsonPath('counts.contacts', 1);
    }

    public function test_dish_detail_page_is_available(): void
    {
        $dish = Dish::firstOrFail();

        $this->get(route('localized.vi.menu.show', $dish))
            ->assertOk()
            ->assertSee($dish->name);
    }

    public function test_home_popup_promotion_renders_and_uses_translations(): void
    {
        $promotion = Promotion::create([
            'title' => 'Ưu đãi Paprika hôm nay',
            'subtitle' => 'Combo nóng',
            'description' => 'Chọn món Việt hoặc món nướng Hy Lạp trong hôm nay.',
            'badge' => 'Ưu đãi',
            'button_text' => 'Xem ưu đãi',
            'button_link' => route('localized.vi.menu.index'),
            'placement' => 'popup',
            'template' => 'split',
            'accent_color' => '#B91C1C',
            'sort_order' => 1,
            'show_once' => true,
            'is_active' => true,
        ]);

        $promotion->translations()->create([
            'locale' => 'en',
            'title' => 'Today at Paprika',
            'subtitle' => 'Hot combo',
            'description' => 'Pick Vietnamese food or Greek grill today.',
            'badge' => 'Offer',
            'button_text' => 'See offer',
        ]);

        $this->get(route('localized.vi.home'))
            ->assertOk()
            ->assertSee('data-promo-popup', false)
            ->assertSee('Ưu đãi Paprika hôm nay');

        $this->get(route('localized.en.home'))
            ->assertOk()
            ->assertSee('data-promo-popup', false)
            ->assertSee('Today at Paprika')
            ->assertSee('See offer');
    }

    public function test_contact_form_stores_message(): void
    {
        $payload = [
            'name' => 'Nguyễn An',
            'phone' => '0912345678',
            'message' => 'Tôi muốn hỏi thêm về thực đơn Paprika cuối tuần.',
        ];

        $this->post(route('localized.vi.contact.store'), $payload)
            ->assertRedirect(route('localized.vi.contact'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas(Contact::class, [
            'name' => 'Nguyễn An',
            'phone' => '0912345678',
        ]);
    }

    public function test_reservation_form_stores_reservation(): void
    {
        $payload = [
            'name' => 'Trần Bình',
            'phone' => '0987654321',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '18:30',
            'guests' => 4,
            'note' => 'Ưu tiên bàn gần cửa sổ.',
        ];

        $this->post(route('localized.vi.reservations.store'), $payload)
            ->assertRedirect(route('localized.vi.reservations.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas(Reservation::class, [
            'name' => 'Trần Bình',
            'phone' => '0987654321',
            'status' => 'pending',
        ]);
    }

    public function test_customer_can_checkout_offline_order_from_cart(): void
    {
        $dish = Dish::active()->doesntHave('timeSlots')->where('price', '>=', 500)->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 2])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('localized.vi.cart.index'))
            ->assertOk()
            ->assertSee($dish->name);

        $this->get(route('localized.vi.checkout.create'))
            ->assertOk()
            ->assertDontSee('data-chat-widget', false)
            ->assertDontSee('floating-contact-panel', false);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Khach dat mon',
            'customer_phone' => '0900000000',
            'customer_email' => 'khach@example.com',
            'fulfillment_method' => 'delivery',
            'delivery_address' => '12 Duong Test',
            'delivery_address_final' => '12 Duong Test',
            'delivery_distance_km' => 2.5,
            'requested_time' => '18:00',
            'note' => 'Giao sau 18h',
        ])->assertRedirect()
            ->assertSessionHas('success');

        $order = Order::with(['items', 'branch', 'shipment', 'invoice', 'payments'])->where('customer_phone', '0900000000')->firstOrFail();

        $this->assertSame('pending', $order->status);
        $this->assertSame('offline', $order->payment_method);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('delivery', $order->fulfillment_method);
        $this->assertSame(0, $order->shipping_fee);
        $this->assertSame('Shipper xác nhận phí ship', $order->delivery_zone_label);
        $this->assertSame('manual', $order->delivery_quote_source);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertNotNull($order->shipment);
        $this->assertNotNull($order->invoice);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'offline',
            'status' => 'pending',
            'amount' => $order->total,
        ]);

        $this->get(route('localized.vi.checkout.success', $order))
            ->assertOk()
            ->assertDontSee('data-chat-widget', false)
            ->assertDontSee('floating-contact-panel', false);

        $this->assertStringContainsString($order->code, (new NewOrderNotificationMail($order))->render());
        $this->assertStringContainsString($order->code, (new CustomerOrderConfirmationMail($order))->render());
    }

    public function test_delivery_quote_uses_manual_shipper_fee_when_branch_auto_quote_is_off(): void
    {
        $dish = Dish::active()->doesntHave('timeSlots')->where('price', '>=', 500)->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 2]);

        $subtotal = $dish->price * 2;

        $this->postJson(route('localized.vi.checkout.delivery-quote'), [
            'branch_id' => $branch->id,
            'fulfillment_method' => 'delivery',
            'delivery_address' => '12 Test Street, Patras',
        ])->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('manual', true)
            ->assertJsonPath('source', 'manual')
            ->assertJsonPath('fee', 0)
            ->assertJsonPath('total', $subtotal);
    }

    public function test_customer_can_checkout_customized_cart_line(): void
    {
        $dish = Dish::where('slug', 'pho-bo')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $sizeGroup = $dish->optionGroups()->create([
            'name' => 'Size',
            'slug' => 'size',
            'type' => DishOptionGroup::TYPE_SINGLE,
            'is_required' => true,
            'min_select' => 1,
            'max_select' => 1,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $large = $sizeGroup->options()->create([
            'name' => 'Large',
            'slug' => 'large',
            'price_delta' => 200,
            'is_default' => false,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $spiceGroup = $dish->optionGroups()->create([
            'name' => 'Spice level',
            'slug' => 'spice-level',
            'type' => DishOptionGroup::TYPE_SINGLE,
            'is_required' => false,
            'min_select' => 0,
            'max_select' => 1,
            'sort_order' => 2,
            'is_active' => true,
        ]);
        $spice = $spiceGroup->options()->create([
            'name' => 'Hot spice',
            'slug' => 'hot-spice',
            'price_delta' => 0,
            'is_default' => false,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $extrasGroup = $dish->optionGroups()->create([
            'name' => 'Extras',
            'slug' => 'extras',
            'type' => DishOptionGroup::TYPE_MULTIPLE,
            'is_required' => false,
            'min_select' => 0,
            'max_select' => 4,
            'sort_order' => 3,
            'is_active' => true,
        ]);
        $extraProtein = $extrasGroup->options()->create([
            'name' => 'Extra protein',
            'slug' => 'extra-protein',
            'price_delta' => 200,
            'is_default' => false,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $excludeGroup = $dish->optionGroups()->create([
            'name' => 'Exclude',
            'slug' => 'exclude',
            'type' => DishOptionGroup::TYPE_EXCLUDE,
            'is_required' => false,
            'min_select' => 0,
            'max_select' => 4,
            'sort_order' => 4,
            'is_active' => true,
        ]);
        $noOnion = $excludeGroup->options()->create([
            'name' => 'No onion',
            'slug' => 'no-onion',
            'price_delta' => 0,
            'is_default' => false,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $dish->load('activeOptionGroups.activeOptions');

        $this->post(route('localized.vi.cart.add', $dish), [
            'quantity' => 2,
            'option_ids' => [$large->id, $spice->id, $extraProtein->id, $noOnion->id],
            'customization_note' => 'Để rau riêng',
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('localized.vi.cart.index'))
            ->assertOk()
            ->assertSee('Large')
            ->assertSee('Để rau riêng');

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Khach chon tuy chinh',
            'customer_phone' => '0900000001',
            'customer_email' => 'custom@example.com',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:30',
        ])->assertRedirect()
            ->assertSessionHas('success');

        $order = Order::with('items')->where('customer_phone', '0900000001')->firstOrFail();
        $item = $order->items->first();

        $this->assertSame(2, $item->quantity);
        $this->assertSame(950, $item->base_unit_price);
        $this->assertSame(400, $item->options_total);
        $this->assertSame(1350, $item->unit_price);
        $this->assertSame(2700, $item->line_total);
        $this->assertSame('Để rau riêng', $item->customization_note);
        $this->assertNotEmpty($item->options_snapshot);
        $this->assertSame('Large', collect($item->options_snapshot)->firstWhere('id', $large->id)['name']);
    }

    public function test_admin_can_view_order_management(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Đơn hàng');
    }

    public function test_admin_can_update_dish_option_groups(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();
        $dish = Dish::where('slug', 'pho-bo')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.dishes.update', $dish), [
                'name' => $dish->name,
                'slug' => $dish->slug,
                'category_id' => $dish->category_id,
                'description' => $dish->description,
                'content' => $dish->content,
                'ingredients' => $dish->ingredients,
                'price' => number_format($dish->price / 100, 2, '.', ''),
                'sort_order' => $dish->sort_order,
                'is_active' => 1,
                'option_groups' => [
                    [
                        'name' => 'Spice level',
                        'type' => 'single',
                        'is_required' => 1,
                        'is_active' => 1,
                        'min_select' => 0,
                        'max_select' => 1,
                        'sort_order' => 0,
                        'options' => [
                            [
                                'name' => 'Mild',
                                'price_delta' => '0.00',
                                'is_default' => 1,
                                'is_active' => 1,
                                'sort_order' => 0,
                            ],
                            [
                                'name' => 'Extra chili',
                                'price_delta' => '1.20',
                                'is_active' => 1,
                                'sort_order' => 1,
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.dishes.index'))
            ->assertSessionHasNoErrors();

        $dish->refresh()->load('optionGroups.options');
        $group = $dish->optionGroups->firstWhere('name', 'Spice level');

        $this->assertNotNull($group);
        $this->assertTrue($group->is_required);
        $this->assertSame(120, $group->options->firstWhere('name', 'Extra chili')->price_delta);
    }

    public function test_admin_can_update_dish_option_presets(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.dish-option-settings.edit'))
            ->assertOk()
            ->assertSee('Cấu hình biến thể');

        $this->actingAs($admin)
            ->put(route('admin.dish-option-settings.update'), [
                'presets' => [
                    [
                        'name' => 'Đồ nướng',
                        'slug' => 'do-nuong',
                        'description' => 'Preset cho các món nướng.',
                        'groups' => [
                            [
                                'name' => 'Độ cay',
                                'type' => 'single',
                                'is_required' => 1,
                                'is_active' => 1,
                                'min_select' => 0,
                                'max_select' => 1,
                                'sort_order' => 0,
                                'options' => [
                                    [
                                        'name' => 'Cay vừa',
                                        'price_delta' => '0.00',
                                        'is_default' => 1,
                                        'is_active' => 1,
                                        'sort_order' => 0,
                                    ],
                                    [
                                        'name' => 'Thêm ớt',
                                        'price_delta' => '0.50',
                                        'is_active' => 1,
                                        'sort_order' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $setting = SiteSetting::where('key', 'dish_option_presets')->firstOrFail();
        $presets = json_decode($setting->value, true);

        $this->assertSame('Đồ nướng', $presets[0]['name']);
        $this->assertSame('do-nuong', $presets[0]['slug']);
        $this->assertSame('Độ cay', $presets[0]['groups'][0]['name']);
        $this->assertSame('0.50', $presets[0]['groups'][0]['options'][1]['price_delta']);
    }

    public function test_admin_can_update_default_contact_phone_settings(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('name="hotline"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="open_days[]"', false);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'Paprika',
                'restaurant_name' => 'Paprika',
                'slogan' => 'Vietnamese food',
                'short_description' => 'Fallback contact test',
                'hotline' => '694 041 4566',
                'phone' => '261 031 6200',
                'show_dish_prices' => '1',
                'default_locale' => 'vi',
                'business_timezone' => 'Europe/Athens',
                'open_days' => [1, 2, 3, 4, 5, 6, 0],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('694 041 4566', setting('hotline'));
        $this->assertSame('261 031 6200', setting('phone'));
        $this->assertSame('1,2,3,4,5,6,0', setting('open_days'));
    }

    public function test_admin_timezone_selects_show_offsets_and_european_zones(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();

        $settingsResponse = $this->actingAs($admin)->get(route('admin.settings.edit'));

        $settingsResponse
            ->assertOk()
            ->assertSee('Europe/Athens (UTC+', false)
            ->assertSee('Europe/Madrid (UTC+', false)
            ->assertSee('Europe/Lisbon (UTC+', false)
            ->assertSee('Europe/Berlin (UTC+', false);

        $branchResponse = $this->actingAs($admin)->get(route('admin.branches.create'));

        $branchResponse
            ->assertOk()
            ->assertSee('Europe/Athens (UTC+', false)
            ->assertSee('Europe/Madrid (UTC+', false)
            ->assertSee('Europe/Lisbon (UTC+', false)
            ->assertSee('Europe/Berlin (UTC+', false);
    }

    public function test_admin_can_update_branch_open_days_override(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.branches.update', $branch), [
                'name' => $branch->name,
                'slug' => $branch->slug,
                'city' => $branch->city,
                'timezone' => $branch->timezone,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'hotline' => $branch->hotline,
                'email' => $branch->email,
                'opening_hours' => $branch->opening_hours,
                'open_days' => [1, 2, 3, 4, 5],
                'reservation_time_slots' => $branch->reservation_time_slots,
                'reservation_last_booking_time' => $branch->reservation_last_booking_time,
                'reservation_last_order_buffer_minutes' => $branch->reservation_last_order_buffer_minutes,
                'google_map_iframe' => $branch->google_map_iframe,
                'description' => $branch->description,
                'facebook_url' => $branch->facebook_url,
                'zalo_url' => $branch->zalo_url,
                'accepts_online_orders' => $branch->accepts_online_orders ? '1' : '0',
                'accepts_pickup_orders' => $branch->accepts_pickup_orders ? '1' : '0',
                'accepts_delivery_orders' => $branch->accepts_delivery_orders ? '1' : '0',
                'accepts_offline_payment' => $branch->accepts_offline_payment ? '1' : '0',
                'order_notification_email' => $branch->order_notification_email,
                'auto_delivery_quote_enabled' => $branch->auto_delivery_quote_enabled ? '1' : '0',
                'delivery_min_order_amount' => '10.00',
                'delivery_free_order_amount' => '',
                'delivery_max_distance_km' => $branch->delivery_max_distance_km,
                'delivery_origin_latitude' => $branch->delivery_origin_latitude,
                'delivery_origin_longitude' => $branch->delivery_origin_longitude,
                'delivery_note' => $branch->delivery_note,
                'sort_order' => $branch->sort_order,
                'is_active' => '1',
                'meta_title' => $branch->meta_title,
                'meta_description' => $branch->meta_description,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('1,2,3,4,5', $branch->fresh()->open_days);
    }

    public function test_admin_can_mark_offline_order_as_paid(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();
        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Khach thanh toan',
            'customer_phone' => '0911111111',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
        ])->assertRedirect();

        $order = Order::where('customer_phone', '0911111111')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), [
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'shipping_fee' => 0,
                'invoice_status' => 'draft',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'offline',
            'status' => 'paid',
            'amount' => $order->fresh()->total,
        ]);
    }

    public function test_admin_can_mark_viva_order_as_paid(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();
        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Viva Admin',
            'customer_phone' => '306922222222',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
        ])->assertRedirect();

        $order = Order::where('customer_phone', '306922222222')->firstOrFail();
        $order->update(['payment_method' => 'viva']);
        $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => $order->total,
            'currency' => 'EUR',
            'reference' => '2271655739472609',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), [
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'shipping_fee' => 0,
                'invoice_status' => 'draft',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'paid',
            'amount' => $order->fresh()->total,
            'reference' => '2271655739472609',
        ]);
    }

    public function test_customer_can_start_viva_checkout_from_cart(): void
    {
        Mail::fake();

        Http::fake([
            'https://demo-accounts.vivapayments.com/connect/token' => Http::response([
                'access_token' => 'fake-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            'https://demo-api.vivapayments.com/checkout/v2/orders' => Http::response([
                'orderCode' => 2271655739472609,
            ]),
        ]);

        config()->set('services.viva.client_id', 'client-id');
        config()->set('services.viva.client_secret', 'client-secret');
        config()->set('services.viva.source_code', '8362');

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $response = $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Viva Customer',
            'customer_phone' => '306900000000',
            'customer_email' => 'viva@example.com',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'viva',
        ]);

        $response->assertRedirect();
        $this->assertStringStartsWith(
            'https://demo.vivapayments.com/web/checkout?ref=2271655739472609',
            $response->headers->get('Location')
        );

        $order = Order::where('customer_phone', '306900000000')->firstOrFail();

        $this->assertSame('viva', $order->payment_method);
        $this->assertSame($order->id, session(PendingVivaPayment::SESSION_KEY)['order_id']);
        $this->assertStringContainsString('ref=2271655739472609', session(PendingVivaPayment::SESSION_KEY)['checkout_url']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'reference' => '2271655739472609',
        ]);

        Mail::assertNotQueued(CustomerOrderConfirmationMail::class);
        Mail::assertNotQueued(NewOrderNotificationMail::class);
    }

    public function test_pending_viva_reminder_renders_and_can_be_dismissed(): void
    {
        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Reminder Viva',
            'customer_phone' => '306900111111',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306900111111')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
        ]);

        $payment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => $order->total,
            'currency' => 'EUR',
            'reference' => '2271655739472609',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=2271655739472609',
            ],
        ]);

        app(PendingVivaPayment::class)->remember($order, $payment);

        $this->get(route('localized.vi.home'))
            ->assertOk()
            ->assertSee('Đơn chưa thanh toán')
            ->assertSee($order->code)
            ->assertSee(route('payments.viva.continue', $order), false);

        $this->post(route('payments.viva.reminder.dismiss'))
            ->assertRedirect();

        $this->assertTrue(session(PendingVivaPayment::HIDDEN_SESSION_KEY));

        $this->get(route('localized.vi.home'))
            ->assertOk()
            ->assertDontSee('Đơn chưa thanh toán');
    }

    public function test_pending_viva_continue_reuses_recent_checkout_url(): void
    {
        Http::fake();

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Continue Recent Viva',
            'customer_phone' => '306900222222',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306900222222')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
        ]);

        $payment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => $order->total,
            'currency' => 'EUR',
            'reference' => '2271655739472609',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=2271655739472609',
            ],
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        app(PendingVivaPayment::class)->remember($order, $payment);

        $this->post(route('payments.viva.continue', $order))
            ->assertRedirect('https://demo.vivapayments.com/web/checkout?ref=2271655739472609');

        $this->assertSame(1, $order->payments()->where('method', 'viva')->count());
        Http::assertNothingSent();
    }

    public function test_pending_viva_continue_creates_new_checkout_when_old_link_is_stale(): void
    {
        Http::fake([
            'https://demo-accounts.vivapayments.com/connect/token' => Http::response([
                'access_token' => 'fake-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            'https://demo-api.vivapayments.com/checkout/v2/orders' => Http::response([
                'orderCode' => 9931655739472609,
            ]),
        ]);

        config()->set('services.viva.client_id', 'client-id');
        config()->set('services.viva.client_secret', 'client-secret');
        config()->set('services.viva.source_code', '8362');

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Continue Stale Viva',
            'customer_phone' => '306900333333',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306900333333')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'total' => 615,
        ]);

        $payment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 615,
            'currency' => 'EUR',
            'reference' => '2271655739472609',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=2271655739472609',
            ],
        ]);
        $payment->forceFill([
            'created_at' => now()->subMinutes(31),
            'updated_at' => now()->subMinutes(31),
        ])->save();

        app(PendingVivaPayment::class)->remember($order, $payment);

        $this->post(route('payments.viva.continue', $order))
            ->assertRedirect('https://demo.vivapayments.com/web/checkout?ref=9931655739472609');

        $this->assertSame(2, $order->payments()->where('method', 'viva')->count());
        $this->assertSame($order->id, session(PendingVivaPayment::SESSION_KEY)['order_id']);
        $this->assertSame('https://demo.vivapayments.com/web/checkout?ref=9931655739472609', session(PendingVivaPayment::SESSION_KEY)['checkout_url']);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://demo-api.vivapayments.com/checkout/v2/orders'
            && $request['amount'] === 615
            && $request['merchantTrns'] === $order->code);
    }

    public function test_customer_can_create_new_viva_payment_session_for_unpaid_order(): void
    {
        Http::fake([
            'https://demo-accounts.vivapayments.com/connect/token' => Http::response([
                'access_token' => 'fake-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            'https://demo-api.vivapayments.com/checkout/v2/orders' => Http::response([
                'orderCode' => 9931655739472609,
            ]),
        ]);

        config()->set('services.viva.client_id', 'client-id');
        config()->set('services.viva.client_secret', 'client-secret');
        config()->set('services.viva.source_code', '8362');

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Retry Viva',
            'customer_phone' => '306955555555',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306955555555')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'total' => 615,
        ]);

        $previousPayment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'failed',
            'amount' => 615,
            'currency' => 'EUR',
            'reference' => '9226373489772604',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=9226373489772604',
            ],
        ]);

        $response = $this->post(route('localized.vi.order.retry-payment', $order));

        $response->assertRedirect('https://demo.vivapayments.com/web/checkout?ref=9931655739472609');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 615,
            'reference' => '9931655739472609',
        ]);

        $retryPayment = $order->payments()->where('reference', '9931655739472609')->firstOrFail();
        $this->assertSame($previousPayment->id, $retryPayment->payload['retry_for_payment_id']);
        $this->assertSame('https://demo.vivapayments.com/web/checkout?ref=9931655739472609', $retryPayment->payload['checkout_url']);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'action' => 'viva_payment_pending',
        ]);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://demo-api.vivapayments.com/checkout/v2/orders'
            && $request['amount'] === 615
            && $request['merchantTrns'] === $order->code);
    }

    public function test_customer_retry_payment_route_reuses_recent_viva_checkout_url(): void
    {
        Http::fake();

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Retry Recent Viva',
            'customer_phone' => '306955555556',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306955555556')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'total' => 615,
        ]);

        $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 615,
            'currency' => 'EUR',
            'reference' => '9226373489772604',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=9226373489772604',
            ],
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        $this->post(route('localized.vi.order.retry-payment', $order))
            ->assertRedirect('https://demo.vivapayments.com/web/checkout?ref=9226373489772604');

        $this->assertSame(1, $order->payments()->where('method', 'viva')->count());
        Http::assertNothingSent();
    }

    public function test_pending_viva_create_new_checkout_reuses_link_created_while_waiting_for_lock(): void
    {
        Http::fake();

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Retry Locked Viva',
            'customer_phone' => '306955555557',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306955555557')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'total' => 615,
        ]);

        $stalePayment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'failed',
            'amount' => 615,
            'currency' => 'EUR',
            'reference' => '9226373489772604',
        ]);

        $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 615,
            'currency' => 'EUR',
            'reference' => '9931655739472609',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=9931655739472609',
                'retry_for_payment_id' => $stalePayment->id,
            ],
            'created_at' => now()->subMinutes(1),
            'updated_at' => now()->subMinutes(1),
        ]);

        $response = app(PendingVivaPayment::class)->createNewCheckout($order, app(\App\Services\Payments\VivaGateway::class));

        $this->assertSame('https://demo.vivapayments.com/web/checkout?ref=9931655739472609', $response->headers->get('Location'));

        $this->assertSame(2, $order->payments()->where('method', 'viva')->count());
        Http::assertNothingSent();
    }

    public function test_customer_cannot_create_new_viva_payment_session_for_paid_order(): void
    {
        Http::fake();

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Paid Viva',
            'customer_phone' => '306966666666',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306966666666')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'paid',
        ]);

        $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'paid',
            'amount' => $order->total,
            'currency' => 'EUR',
            'reference' => '2271655739472609',
        ]);

        $this->post(route('localized.vi.order.retry-payment', $order))
            ->assertRedirect(route('localized.vi.order.track', $order))
            ->assertSessionHas('info');

        $this->assertSame(1, $order->payments()->where('method', 'viva')->count());
        Http::assertNothingSent();
    }

    public function test_customer_cannot_retry_viva_payment_for_offline_or_cancelled_order(): void
    {
        Http::fake();

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Offline Retry',
            'customer_phone' => '306988888888',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $offlineOrder = Order::where('customer_phone', '306988888888')->firstOrFail();
        $offlineOrder->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'failed',
            'amount' => $offlineOrder->total,
            'currency' => 'EUR',
            'reference' => '1111655739472609',
        ]);

        $this->post(route('localized.vi.order.retry-payment', $offlineOrder))
            ->assertRedirect(route('localized.vi.order.track', $offlineOrder))
            ->assertSessionHas('error');

        $offlineOrder->update(['payment_method' => 'viva', 'status' => 'cancelled']);

        $this->post(route('localized.vi.order.retry-payment', $offlineOrder))
            ->assertRedirect(route('localized.vi.order.track', $offlineOrder))
            ->assertSessionHas('error');

        $this->assertSame(1, $offlineOrder->payments()->where('method', 'viva')->count());
        Http::assertNothingSent();
    }

    public function test_customer_can_retry_viva_payment_for_unpaid_order_within_retry_window(): void
    {
        Http::fake([
            'https://demo-accounts.vivapayments.com/connect/token' => Http::response([
                'access_token' => 'fake-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            'https://demo-api.vivapayments.com/checkout/v2/orders' => Http::response([
                'orderCode' => 9931655739472609,
            ]),
        ]);

        config()->set('services.viva.client_id', 'client-id');
        config()->set('services.viva.client_secret', 'client-secret');
        config()->set('services.viva.source_code', '8362');

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Recent Retry Viva',
            'customer_phone' => '306977777776',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306977777776')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'total' => 615,
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        $previousPayment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'failed',
            'amount' => 615,
            'currency' => 'EUR',
            'reference' => '2221655739472608',
        ]);

        $this->post(route('localized.vi.order.retry-payment', $order))
            ->assertRedirect('https://demo.vivapayments.com/web/checkout?ref=9931655739472609');

        $retryPayment = $order->payments()->where('reference', '9931655739472609')->firstOrFail();
        $this->assertSame($previousPayment->id, $retryPayment->payload['retry_for_payment_id']);
        $this->assertSame(2, $order->payments()->where('method', 'viva')->count());
    }

    public function test_customer_cannot_retry_viva_payment_for_expired_unpaid_order(): void
    {
        Http::fake();

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Old Retry Viva',
            'customer_phone' => '306977777777',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306977777777')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'created_at' => now()->subHours(25),
            'updated_at' => now()->subHours(25),
        ]);

        $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'failed',
            'amount' => $order->total,
            'currency' => 'EUR',
            'reference' => '2221655739472609',
        ]);

        $this->post(route('localized.vi.order.retry-payment', $order))
            ->assertRedirect(route('localized.vi.order.track', $order))
            ->assertSessionHas('error');

        $this->assertSame(1, $order->payments()->where('method', 'viva')->count());
        Http::assertNothingSent();
    }

    public function test_viva_retry_api_failure_does_not_create_new_payment(): void
    {
        Http::fake([
            'https://demo-accounts.vivapayments.com/connect/token' => Http::response([
                'access_token' => 'fake-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            'https://demo-api.vivapayments.com/checkout/v2/orders' => Http::response(['message' => 'Viva down'], 502),
        ]);

        config()->set('services.viva.client_id', 'client-id');
        config()->set('services.viva.client_secret', 'client-secret');
        config()->set('services.viva.source_code', '8362');

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Retry Fails',
            'customer_phone' => '306999999999',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306999999999')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'total' => 615,
        ]);

        $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'failed',
            'amount' => 615,
            'currency' => 'EUR',
            'reference' => '4441655739472609',
        ]);

        $this->post(route('localized.vi.order.retry-payment', $order))
            ->assertRedirect(route('localized.vi.order.track', $order))
            ->assertSessionHas('error');

        $this->assertSame(1, $order->payments()->where('method', 'viva')->count());
    }

    public function test_paid_webhook_for_viva_retry_session_confirms_order(): void
    {
        Mail::fake();

        Http::fake([
            'https://demo-api.vivapayments.com/checkout/v2/transactions/*' => Http::response([
                'statusId' => 'F',
                'amount' => 6.15,
                'orderCode' => 9931655739472609,
                'transactionId' => 'bb7ab1e3-e6ce-45c9-970d-4d902f27ce71',
            ]),
        ]);

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();
        $branch->update(['order_notification_email' => 'retry-paid-admin@example.com']);

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Retry Paid',
            'customer_phone' => '306922222222',
            'customer_email' => 'retry-paid@example.com',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306922222222')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'subtotal' => 615,
            'total' => 615,
        ]);

        $failedPayment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'failed',
            'amount' => 615,
            'currency' => 'EUR',
            'reference' => '9226373489772604',
        ]);

        $retryPayment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 615,
            'currency' => 'EUR',
            'reference' => '9931655739472609',
            'payload' => [
                'retry_for_payment_id' => $failedPayment->id,
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=9931655739472609',
            ],
        ]);

        $this->postJson(route('payments.viva.webhook'), [
            'EventData' => [
                'OrderCode' => '9931655739472609',
                'TransactionId' => 'bb7ab1e3-e6ce-45c9-970d-4d902f27ce71',
                'StatusId' => 'F',
                'Amount' => 6.15,
            ],
            'EventTypeId' => 1796,
        ])->assertOk();

        $this->assertSame('failed', $failedPayment->fresh()->status);
        $this->assertSame('paid', $retryPayment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('https://demo.vivapayments.com/web/checkout?ref=9931655739472609', $retryPayment->fresh()->payload['checkout_url']);

        Mail::assertQueued(CustomerPaymentConfirmedMail::class, fn (CustomerPaymentConfirmedMail $mail): bool => $mail->hasTo('retry-paid@example.com'));
        Mail::assertQueued(NewOrderNotificationMail::class, fn (NewOrderNotificationMail $mail): bool => $mail->hasTo('retry-paid-admin@example.com'));
    }

    public function test_paid_viva_webhook_clears_pending_payment_reminder(): void
    {
        Mail::fake();

        Http::fake([
            'https://demo-api.vivapayments.com/checkout/v2/transactions/*' => Http::response([
                'statusId' => 'F',
                'amount' => 6.15,
            ]),
        ]);

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Reminder Cleared',
            'customer_phone' => '306900444444',
            'customer_email' => 'reminder-cleared@example.com',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306900444444')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'subtotal' => 615,
            'total' => 615,
        ]);

        $payment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 615,
            'currency' => 'EUR',
            'reference' => '8831655739472609',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=8831655739472609',
            ],
        ]);

        app(PendingVivaPayment::class)->remember($order, $payment);
        $this->assertNotNull(session(PendingVivaPayment::SESSION_KEY));

        $this->postJson(route('payments.viva.webhook'), [
            'EventData' => [
                'OrderCode' => '8831655739472609',
                'TransactionId' => 'cc7ab1e3-e6ce-45c9-970d-4d902f27ce71',
                'StatusId' => 'F',
                'Amount' => 6.15,
            ],
            'EventTypeId' => 1796,
        ])->assertOk();

        $this->assertNull(session(PendingVivaPayment::SESSION_KEY));
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_viva_webhook_marks_matching_payment_as_paid(): void
    {
        Mail::fake();

        Http::fake([
            'https://demo-api.vivapayments.com/checkout/v2/transactions/*' => Http::response([
                'statusId' => 'F',
                'amount' => 16.50,
            ]),
        ]);

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();
        $branch->update(['order_notification_email' => 'paid-admin@example.com']);

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Webhook Customer',
            'customer_phone' => '306911111111',
            'customer_email' => 'webhook@example.com',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306911111111')->firstOrFail();
        $payment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 1650,
            'currency' => 'EUR',
            'reference' => '2271655739472609',
        ]);

        $order->update([
            'subtotal' => 1650,
            'total' => 1650,
        ]);

        $this->postJson(route('payments.viva.webhook'), [
            'EventData' => [
                'OrderCode' => '2271655739472609',
                'TransactionId' => '997ab1e3-e6ce-45c9-970d-4d902f27ce71',
                'StatusId' => 'F',
                'Amount' => $payment->amount,
            ],
            'EventTypeId' => 1796,
        ])->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'action' => 'viva_payment_paid',
        ]);

        Mail::assertQueued(CustomerPaymentConfirmedMail::class, fn (CustomerPaymentConfirmedMail $mail): bool => $mail->hasTo($order->customer_email));
        Mail::assertQueued(NewOrderNotificationMail::class, fn (NewOrderNotificationMail $mail): bool => $mail->hasTo('paid-admin@example.com'));
    }

    public function test_viva_paid_webhook_does_not_confirm_already_paid_order_twice(): void
    {
        Mail::fake();

        Http::fake([
            'https://demo-api.vivapayments.com/checkout/v2/transactions/*' => Http::response([
                'statusId' => 'F',
                'amount' => 16.50,
            ]),
        ]);

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();
        $branch->update(['order_notification_email' => 'paid-admin@example.com']);

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Already Paid Webhook',
            'customer_phone' => '306977777777',
            'customer_email' => 'already-paid@example.com',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306977777777')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'subtotal' => 1650,
            'total' => 1650,
        ]);

        $payment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 1650,
            'currency' => 'EUR',
            'reference' => '7731655739472609',
        ]);

        Mail::fake();

        $this->postJson(route('payments.viva.webhook'), [
            'EventData' => [
                'OrderCode' => '7731655739472609',
                'TransactionId' => 'aa7ab1e3-e6ce-45c9-970d-4d902f27ce71',
                'StatusId' => 'F',
                'Amount' => $payment->amount,
            ],
            'EventTypeId' => 1796,
        ])->assertOk();

        $this->assertSame('duplicate', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'action' => 'viva_payment_duplicate',
        ]);
        $this->assertDatabaseMissing('order_activities', [
            'order_id' => $order->id,
            'action' => 'viva_payment_paid',
            'note' => 'Viva xác nhận thanh toán thành công. Transaction: aa7ab1e3-e6ce-45c9-970d-4d902f27ce71.',
        ]);

        Mail::assertNotQueued(CustomerPaymentConfirmedMail::class);
        Mail::assertNotQueued(NewOrderNotificationMail::class);
    }

    public function test_second_paid_viva_payment_for_same_order_is_marked_duplicate(): void
    {
        Mail::fake();

        Http::fake([
            'https://demo-api.vivapayments.com/checkout/v2/transactions/first-paid-transaction' => Http::response([
                'statusId' => 'F',
                'amount' => 16.50,
                'transactionId' => 'first-paid-transaction',
            ]),
            'https://demo-api.vivapayments.com/checkout/v2/transactions/second-paid-transaction' => Http::response([
                'statusId' => 'F',
                'amount' => 16.50,
                'transactionId' => 'second-paid-transaction',
            ]),
        ]);

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();
        $branch->update(['order_notification_email' => 'double-paid-admin@example.com']);

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Double Paid Webhook',
            'customer_phone' => '306977777778',
            'customer_email' => 'double-paid@example.com',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        Mail::fake();

        $order = Order::where('customer_phone', '306977777778')->firstOrFail();
        $order->update([
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'subtotal' => 1650,
            'total' => 1650,
        ]);

        $firstPayment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 1650,
            'currency' => 'EUR',
            'reference' => '1111655739472609',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=1111655739472609',
            ],
        ]);

        $secondPayment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 1650,
            'currency' => 'EUR',
            'reference' => '2221655739472609',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=2221655739472609',
                'retry_for_payment_id' => $firstPayment->id,
            ],
        ]);

        $this->postJson(route('payments.viva.webhook'), [
            'EventData' => [
                'OrderCode' => '1111655739472609',
                'TransactionId' => 'first-paid-transaction',
                'StatusId' => 'F',
                'Amount' => $firstPayment->amount,
            ],
            'EventTypeId' => 1796,
        ])->assertOk();

        $this->postJson(route('payments.viva.webhook'), [
            'EventData' => [
                'OrderCode' => '2221655739472609',
                'TransactionId' => 'second-paid-transaction',
                'StatusId' => 'F',
                'Amount' => $secondPayment->amount,
            ],
            'EventTypeId' => 1796,
        ])->assertOk();

        $this->assertSame('paid', $firstPayment->fresh()->status);
        $this->assertSame('duplicate', $secondPayment->fresh()->status);
        $this->assertSame('second-paid-transaction', $secondPayment->fresh()->transaction_code);
        $this->assertSame('order_already_paid', $secondPayment->fresh()->payload['duplicate_payment']['reason']);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame(1, $order->payments()->where('method', 'viva')->where('status', 'paid')->count());
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'action' => 'viva_payment_paid',
        ]);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'action' => 'viva_payment_duplicate',
        ]);

        Mail::assertQueued(CustomerPaymentConfirmedMail::class, 1);
        Mail::assertQueued(NewOrderNotificationMail::class, 1);
    }

    public function test_viva_failed_webhook_records_transaction_id_and_payload(): void
    {
        Log::spy();

        config()->set('services.viva.client_id', 'client-id');
        config()->set('services.viva.client_secret', 'client-secret');
        config()->set('services.viva.source_code', '9288');

        Http::fake([
            '*connect/token' => Http::response([
                'access_token' => 'fake-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            '*checkout/v2/transactions/*' => Http::response([
                'statusId' => 'E',
                'amount' => 8.20,
                'orderCode' => 5064406125072606,
                'transactionId' => 'df3cdd86-9bfd-46bf-b1f7-f7cd8777ca50',
                'responseCode' => '05',
                'responseEventId' => '10051',
                'cardNumber' => '441029XXXXXX7040',
                'cardIssuingBank' => 'Piraeus Bank S.A.',
                'sourceCode' => '9288',
            ]),
        ]);

        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Failed Webhook',
            'customer_phone' => '306944444444',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306944444444')->firstOrFail();
        $payment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 820,
            'currency' => 'EUR',
            'reference' => '5064406125072606',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=5064406125072606',
            ],
        ]);

        $this->postJson(route('payments.viva.webhook'), [
            'EventData' => [
                'OrderCode' => '5064406125072606',
                'TransactionId' => 'df3cdd86-9bfd-46bf-b1f7-f7cd8777ca50',
                'StatusId' => 'E',
                'Amount' => 8.20,
            ],
            'EventTypeId' => 1798,
        ])->assertOk();

        $payment->refresh();

        $this->assertSame('failed', $payment->status);
        $this->assertSame('df3cdd86-9bfd-46bf-b1f7-f7cd8777ca50', $payment->transaction_code);
        $this->assertSame('https://demo.vivapayments.com/web/checkout?ref=5064406125072606', $payment->payload['checkout_url']);
        $this->assertSame('05', $payment->payload['responseCode']);
        $this->assertSame('Piraeus Bank S.A.', $payment->payload['cardIssuingBank']);
        Log::shouldHaveReceived('warning')
            ->with('Viva transaction verified as failed.', \Mockery::on(function (array $context) use ($payment, $order): bool {
                return $context['payment_id'] === $payment->id
                    && $context['order_id'] === $order->id
                    && $context['order_code'] === $order->code
                    && $context['viva_order_code'] === '5064406125072606'
                    && $context['transaction_id'] === 'df3cdd86-9bfd-46bf-b1f7-f7cd8777ca50'
                    && $context['event_type_id'] === 1798
                    && $context['status_id'] === 'E'
                    && $context['response_code'] === '05'
                    && $context['response_event_id'] === '10051'
                    && $context['amount'] === 820
                    && $context['expected_amount'] === 820
                    && $context['amount_matches'] === true
                    && $context['source_code'] === '9288'
                    && $context['card_issuing_bank'] === 'Piraeus Bank S.A.';
            }))
            ->once();
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'action' => 'viva_payment_failed',
        ]);
    }

    public function test_viva_failure_return_marks_payment_failed_and_preserves_retry_url(): void
    {
        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();
        $branch = Branch::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Failed Viva',
            'customer_phone' => '306933333333',
            'fulfillment_method' => 'pickup',
            'requested_time' => '18:00',
            'payment_method' => 'offline',
        ]);

        $order = Order::where('customer_phone', '306933333333')->firstOrFail();
        $order->update(['payment_method' => 'viva']);
        $payment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => $order->total,
            'currency' => 'EUR',
            'reference' => '5932834538772602',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=5932834538772602',
            ],
        ]);

        $this->get(route('payments.viva.failure', [
            's' => '5932834538772602',
            'lang' => 'el-GR',
            'eventId' => '0',
        ]))
            ->assertRedirect("/vi/dat-hang/thanh-cong/{$order->code}")
            ->assertSessionHas('error');

        $payment->refresh();

        $this->assertSame('failed', $payment->status);
        $this->assertSame('https://demo.vivapayments.com/web/checkout?ref=5932834538772602', $payment->payload['checkout_url']);
        $this->assertSame('0', $payment->payload['failure_return']['return_payload']['eventId']);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'action' => 'viva_payment_failed',
        ]);

        $this->get(route('localized.vi.checkout.success', $order))
            ->assertOk()
            ->assertSee('Thanh toán chưa hoàn tất')
            ->assertSee('Thử lại thanh toán');
    }

    public function test_viva_webhook_verification_returns_key(): void
    {
        Http::fake([
            'https://demo.vivapayments.com/api/messages/config/token' => Http::response([
                'Key' => 'B3248222FDCD1885AEAFE51CCC1B5607F00903F6',
            ]),
        ]);

        config()->set('services.viva.merchant_id', 'merchant-id');
        config()->set('services.viva.api_key', 'api-key');

        $this->getJson(route('payments.viva.webhook.verify'))
            ->assertOk()
            ->assertJson([
                'key' => 'B3248222FDCD1885AEAFE51CCC1B5607F00903F6',
            ]);
    }

    public function test_viva_webhook_verification_uses_live_endpoint_in_production(): void
    {
        Http::fake([
            'https://www.vivapayments.com/api/messages/config/token' => Http::response([
                'Key' => 'LIVEKEY',
            ]),
        ]);

        config()->set('services.viva.environment', 'production');
        config()->set('services.viva.merchant_id', 'live-merchant-id');
        config()->set('services.viva.api_key', 'live-api-key');

        $this->getJson(route('payments.viva.webhook.verify'))
            ->assertOk()
            ->assertJson([
                'key' => 'LIVEKEY',
            ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://www.vivapayments.com/api/messages/config/token');
    }

    public function test_viva_webhook_verification_can_use_configured_key_override(): void
    {
        Http::fake();

        config()->set('services.viva.webhook_verification_key', 'CONFIGUREDKEY');

        $this->getJson(route('payments.viva.webhook.verify'))
            ->assertOk()
            ->assertJson([
                'key' => 'CONFIGUREDKEY',
            ]);

        Http::assertNothingSent();
    }

    public function test_viva_webhook_verification_failure_returns_diagnostic_json(): void
    {
        Http::fake([
            'https://www.vivapayments.com/api/messages/config/token' => Http::response(['message' => 'Not found'], 404),
        ]);

        config()->set('services.viva.environment', 'production');
        config()->set('services.viva.merchant_id', 'live-merchant-id');
        config()->set('services.viva.api_key', 'live-api-key');

        $this->getJson(route('payments.viva.webhook.verify'))
            ->assertStatus(502)
            ->assertJson([
                'message' => 'Viva webhook verification failed.',
            ])
            ->assertJsonPath('hint', 'Check that VIVA_ENV and the Viva Merchant ID/API key are from the same demo or live Viva account.');
    }

    public function test_reservation_rejects_time_outside_opening_hours(): void
    {
        SiteSetting::set('opening_hours', '09:00 - 21:30 hằng ngày', 'text', 'general');

        $this->post(route('localized.vi.reservations.store'), [
            'name' => 'Khách đặt sớm',
            'phone' => '0987654321',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '08:30',
            'guests' => 2,
        ])->assertSessionHasErrors('reservation_time');
    }

    public function test_reservation_rejects_closed_open_day(): void
    {
        $branch = Branch::active()->firstOrFail();
        SiteSetting::set('business_timezone', 'Europe/Athens', 'text', 'general');
        SiteSetting::set('open_days', '1,2,3,4,5', 'text', 'general');
        Carbon::setTestNow(Carbon::parse('2026-06-13 10:00:00', 'Europe/Athens'));

        $this->post(route('localized.vi.reservations.store'), [
            'name' => 'Khách đặt ngày đóng cửa',
            'phone' => '0987654321',
            'branch_id' => $branch->id,
            'reservation_date' => '2026-06-14',
            'reservation_time' => '18:00',
            'guests' => 2,
        ])->assertSessionHasErrors('reservation_date');

        Carbon::setTestNow();
    }

    public function test_reservation_rejects_past_time_today(): void
    {
        $branch = Branch::active()->firstOrFail();

        Carbon::setTestNow(Carbon::create(2026, 5, 18, 15, 0, 0, business_timezone($branch)));
        SiteSetting::set('opening_hours', '09:00 - 21:30 hằng ngày', 'text', 'general');

        $this->post(route('localized.vi.reservations.store'), [
            'name' => 'Khách đặt hôm nay',
            'phone' => '0987654321',
            'branch_id' => $branch->id,
            'reservation_date' => business_today($branch)->toDateString(),
            'reservation_time' => '14:30',
            'guests' => 2,
        ])->assertSessionHasErrors('reservation_time');

        Carbon::setTestNow();
    }

    public function test_reservation_respects_split_slots_and_last_booking_time(): void
    {
        SiteSetting::set('reservation_time_slots', '09:00-14:00,16:00-21:00', 'text', 'general');
        SiteSetting::set('reservation_last_booking_time', '20:30', 'text', 'general');
        SiteSetting::set('reservation_last_order_buffer_minutes', '30', 'text', 'general');

        $payload = [
            'name' => 'Khách kiểm tra khung giờ',
            'phone' => '0987654321',
            'reservation_date' => now()->addDay()->toDateString(),
            'guests' => 2,
        ];

        $this->post(route('localized.vi.reservations.store'), $payload + ['reservation_time' => '15:00'])
            ->assertSessionHasErrors('reservation_time');

        $this->post(route('localized.vi.reservations.store'), $payload + ['reservation_time' => '20:45'])
            ->assertSessionHasErrors('reservation_time');

        $this->post(route('localized.vi.reservations.store'), $payload + ['reservation_time' => '20:30'])
            ->assertSessionHasNoErrors();
    }

    public function test_uploaded_images_are_resized_and_converted_to_webp(): void
    {
        Storage::fake('public');

        $path = app(UploadService::class)->uploadImage(
            UploadedFile::fake()->image('large-menu-photo.png', 2600, 1800)->size(9000),
            'dishes'
        );

        Storage::disk('public')->assertExists($path);

        [$width, $height] = getimagesize(Storage::disk('public')->path($path));
        $folderProfile = config('uploads.folder_profiles.dishes', 'default');
        $profile = config("uploads.profiles.{$folderProfile}", config('uploads.profiles.default'));

        $this->assertStringEndsWith('.webp', $path);
        $this->assertLessThanOrEqual((int) ($profile['width'] ?? config('uploads.resize_width')), $width);
        $this->assertLessThanOrEqual((int) ($profile['height'] ?? config('uploads.resize_height')), $height);
    }

    public function test_admin_user_avatar_accepts_large_png_upload(): void
    {
        Storage::fake('public');

        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();
        $avatar = UploadedFile::fake()->image('avatar.png', 2390, 1792)->size(3700);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
                'avatar' => $avatar,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $admin->refresh();

        $this->assertStringEndsWith('.webp', $admin->avatar);
        Storage::disk('public')->assertExists($admin->avatar);
    }
}
