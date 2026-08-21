<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_recent_order_status_labels_stay_vietnamese_when_session_locale_is_not_vietnamese(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();

        $branch = Branch::active()->firstOrFail();

        $order = Order::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'branch_id' => $branch->id,
            'customer_name' => 'Dashboard Customer',
            'customer_phone' => '306900000000',
            'customer_email' => 'dashboard@example.com',
            'fulfillment_method' => 'pickup',
            'status' => 'ready',
            'payment_method' => 'offline',
            'payment_status' => 'unpaid',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 1000,
            'locale' => 'vi',
        ]);
        $order->forceFill([
            'created_at' => business_now($branch),
            'updated_at' => business_now($branch),
        ])->save();

        $viReady = trans('site.order_status.ready', [], 'vi');
        $elReady = trans('site.order_status.ready', [], 'el');

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'el'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Customer')
            ->assertSee($viReady);

        if ($elReady !== $viReady) {
            $response->assertDontSee($elReady);
        }
    }

    public function test_admin_gallery_index_renders_preview_links_to_localized_gallery(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.gallery.index'))
            ->assertOk()
            ->assertSee(localized_route('gallery.index'));
    }

    public function test_admin_menu_form_uses_route_suggestion_datalist(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.menus.create'))
            ->assertOk()
            ->assertSee('admin-menu-route-options', false)
            ->assertSee('list="admin-menu-route-options"', false)
            ->assertSee('route:gallery.index')
            ->assertSee('Không gian quán');
    }

    public function test_admin_can_store_navigation_menu_with_route_value(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.menus.store'), [
                'title' => 'Không gian test',
                'url' => 'route:gallery.index',
                'location' => 'header',
                'sort_order' => 99,
                'is_active' => '1',
                'translations' => [
                    'en' => [
                        'title' => 'Test space',
                        'url' => 'route:gallery.index',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.menus.index'));

        $menu = NavigationMenu::where('title', 'Không gian test')->firstOrFail();

        $this->assertSame('route:gallery.index', $menu->url);
        $this->assertSame('route:gallery.index', $menu->translation('en')?->url);
    }
}
