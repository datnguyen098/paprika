<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderLookupTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_lookup_page_is_available_in_all_locales(): void
    {
        $this->get('/vi/tra-cuu-don-hang')->assertOk();
        $this->get('/en/order-lookup')->assertOk();
        $this->get('/el/anazitisi-parangelias')->assertOk();
    }

    public function test_can_lookup_orders_by_email(): void
    {
        $order = Order::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'customer_name' => 'Test Customer',
            'customer_phone' => '0912345678',
            'customer_email' => 'customer@example.com',
            'fulfillment_method' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'offline',
            'payment_status' => 'unpaid',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 1000,
            'locale' => 'vi',
        ]);

        $this->get('/vi/tra-cuu-don-hang/ket-qua?query=Customer@Example.com')
            ->assertOk()
            ->assertSee($order->code);
    }

    public function test_can_lookup_orders_by_phone_digits(): void
    {
        $order = Order::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'customer_name' => 'Test Customer',
            'customer_phone' => '306912345678',
            'customer_email' => null,
            'fulfillment_method' => 'delivery',
            'status' => 'confirmed',
            'payment_method' => 'offline',
            'payment_status' => 'unpaid',
            'subtotal' => 2500,
            'shipping_fee' => 500,
            'discount_total' => 0,
            'total' => 3000,
            'locale' => 'en',
        ]);

        $this->get('/en/order-lookup/results?query=+30 691 234 5678')
            ->assertOk()
            ->assertSee($order->code);
    }

    public function test_lookup_without_matches_does_not_show_unrelated_orders(): void
    {
        $order = Order::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'customer_name' => 'Test Customer',
            'customer_phone' => '306912345678',
            'customer_email' => 'customer@example.com',
            'fulfillment_method' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'offline',
            'payment_status' => 'unpaid',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 1000,
            'locale' => 'vi',
        ]);

        $this->get('/vi/tra-cuu-don-hang/ket-qua?query=nomatch@example.com')
            ->assertOk()
            ->assertDontSee($order->code);
    }

    public function test_track_page_by_code_is_available(): void
    {
        $order = Order::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'customer_name' => 'Test Customer',
            'customer_phone' => '0912345678',
            'customer_email' => 'customer@example.com',
            'fulfillment_method' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'offline',
            'payment_status' => 'unpaid',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 1000,
            'locale' => 'el',
        ]);

        $order->items()->create([
            'dish_name' => 'Nem Ran',
            'base_unit_price' => 950,
            'options_total' => 100,
            'unit_price' => 1050,
            'quantity' => 2,
            'line_total' => 2100,
            'options_snapshot' => [
                ['group_name' => 'Size', 'name' => 'Large'],
                ['group_name' => 'Sauce', 'name' => 'Extra chili'],
            ],
            'customization_note' => 'No onion',
        ]);

        $this->get("/el/parangelia/{$order->code}")
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('Nem Ran')
            ->assertSee('Size: Large')
            ->assertSee('Sauce: Extra chili')
            ->assertSee('No onion');
    }

    public function test_lookup_results_show_payment_status_without_retry_for_offline_order(): void
    {
        $order = Order::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'customer_name' => 'Offline Customer',
            'customer_phone' => '0912345678',
            'customer_email' => 'offline@example.com',
            'fulfillment_method' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'offline',
            'payment_status' => 'unpaid',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 1000,
            'locale' => 'vi',
        ]);

        $this->get('/vi/tra-cuu-don-hang/ket-qua?query=offline@example.com')
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee(__('site.order_lookup.unpaid'))
            ->assertDontSee(route('payments.viva.continue', $order), false);
    }

    public function test_lookup_results_show_viva_continue_when_checkout_link_is_reusable(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 12:00:00'));

        $order = Order::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'customer_name' => 'Viva Customer',
            'customer_phone' => '0912345678',
            'customer_email' => 'viva-lookup@example.com',
            'fulfillment_method' => 'delivery',
            'status' => 'pending',
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 1000,
            'locale' => 'vi',
        ]);

        $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 1000,
            'currency' => 'EUR',
            'reference' => '2271655739472609',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=2271655739472609',
            ],
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        $this->get('/vi/tra-cuu-don-hang/ket-qua?query=viva-lookup@example.com')
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee(__('site.order_lookup.unpaid'))
            ->assertSee(__('site.pending_viva.continue'))
            ->assertSee(route('payments.viva.continue', $order), false);
    }

    public function test_lookup_results_do_not_show_viva_continue_when_checkout_link_is_stale(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 12:00:00'));

        $order = Order::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'customer_name' => 'Stale Viva Customer',
            'customer_phone' => '0912345678',
            'customer_email' => 'stale-viva@example.com',
            'fulfillment_method' => 'delivery',
            'status' => 'pending',
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 1000,
            'locale' => 'vi',
        ]);

        $payment = $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 1000,
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

        $this->get('/vi/tra-cuu-don-hang/ket-qua?query=stale-viva@example.com')
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee(__('site.order_lookup.unpaid'))
            ->assertDontSee(route('payments.viva.continue', $order), false);
    }

    public function test_lookup_results_show_viva_continue_for_unpaid_order_within_retry_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 12:00:00'));

        $order = Order::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'customer_name' => 'Recent Viva Customer',
            'customer_phone' => '0912345678',
            'customer_email' => 'recent-viva@example.com',
            'fulfillment_method' => 'delivery',
            'status' => 'pending',
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 1000,
            'locale' => 'vi',
        ]);
        $order->forceFill([
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ])->save();

        $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 1000,
            'currency' => 'EUR',
            'reference' => '2271655739472609',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=2271655739472609',
            ],
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        $this->get('/vi/tra-cuu-don-hang/ket-qua?query=recent-viva@example.com')
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee(__('site.order_lookup.unpaid'))
            ->assertSee(route('payments.viva.continue', $order), false);
    }

    public function test_lookup_results_do_not_show_viva_continue_for_expired_unpaid_order(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 12:00:00'));

        $order = Order::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'customer_name' => 'Expired Viva Customer',
            'customer_phone' => '0912345678',
            'customer_email' => 'expired-viva@example.com',
            'fulfillment_method' => 'delivery',
            'status' => 'pending',
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 1000,
            'locale' => 'vi',
        ]);
        $order->forceFill([
            'created_at' => now()->subHours(25),
            'updated_at' => now()->subHours(25),
        ])->save();

        $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'pending',
            'amount' => 1000,
            'currency' => 'EUR',
            'reference' => '2271655739472609',
            'payload' => [
                'checkout_url' => 'https://demo.vivapayments.com/web/checkout?ref=2271655739472609',
            ],
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        $this->get('/vi/tra-cuu-don-hang/ket-qua?query=expired-viva@example.com')
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee(__('site.order_lookup.unpaid'))
            ->assertDontSee(route('payments.viva.continue', $order), false);
    }

    public function test_track_page_hides_retry_for_expired_unpaid_viva_order(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 12:00:00'));

        $order = Order::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'customer_name' => 'Expired Track Viva Customer',
            'customer_phone' => '0912345678',
            'customer_email' => 'expired-track-viva@example.com',
            'fulfillment_method' => 'delivery',
            'status' => 'pending',
            'payment_method' => 'viva',
            'payment_status' => 'unpaid',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 1000,
            'locale' => 'vi',
        ]);
        $order->forceFill([
            'created_at' => now()->subHours(25),
            'updated_at' => now()->subHours(25),
        ])->save();

        $order->payments()->create([
            'method' => 'viva',
            'provider' => 'viva',
            'status' => 'failed',
            'amount' => 1000,
            'currency' => 'EUR',
            'reference' => '2271655739472609',
        ]);

        $this->get("/vi/don-hang/{$order->code}")
            ->assertOk()
            ->assertSee($order->code)
            ->assertDontSee(route('localized.vi.order.retry-payment', $order), false);
    }
}
