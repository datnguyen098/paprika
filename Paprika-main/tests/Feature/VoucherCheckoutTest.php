<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Voucher;
use App\Support\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VoucherCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_voucher_service_calculates_percent_fixed_and_free_shipping(): void
    {
        $branch = Branch::active()->firstOrFail();
        $service = app(VoucherService::class);

        $percent = Voucher::create([
            'code' => 'SAVE20',
            'name' => 'Save 20',
            'discount_type' => Voucher::TYPE_PERCENT,
            'discount_value' => 2000,
            'max_discount_amount' => 300,
            'is_active' => true,
            'is_public' => true,
        ]);
        $fixed = Voucher::create([
            'code' => 'FIXED',
            'name' => 'Fixed',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 800,
            'is_active' => true,
            'is_public' => true,
        ]);
        $shipping = Voucher::create([
            'code' => 'SHIP',
            'name' => 'Ship',
            'discount_type' => Voucher::TYPE_FREE_SHIPPING,
            'discount_value' => 0,
            'is_active' => true,
            'is_public' => true,
        ]);

        $this->assertSame(300, $service->quote($percent, 5000, 500, 'pickup', $branch)->discountTotal);
        $this->assertSame(500, $service->quote($fixed, 500, 500, 'pickup', $branch)->discountTotal);
        $this->assertSame(450, $service->quote($shipping, 5000, 450, 'delivery', $branch)->discountTotal);
        $this->assertFalse($service->quote($shipping, 5000, 0, 'delivery', $branch)->valid);
    }

    public function test_checkout_shows_public_vouchers_and_hides_private_codes(): void
    {
        $dish = Dish::active()->firstOrFail();
        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        Voucher::create([
            'code' => 'PUBLIC10',
            'name' => 'Public voucher',
            'discount_type' => Voucher::TYPE_PERCENT,
            'discount_value' => 1000,
            'is_active' => true,
            'is_public' => true,
            'is_default' => true,
        ]);
        Voucher::create([
            'code' => 'PRIVATE10',
            'name' => 'Private voucher',
            'discount_type' => Voucher::TYPE_PERCENT,
            'discount_value' => 1000,
            'is_active' => true,
            'is_public' => false,
        ]);

        $this->get(route('localized.vi.checkout.create'))
            ->assertOk()
            ->assertSee('PUBLIC10')
            ->assertDontSee('PRIVATE10')
            ->assertSee('value="PUBLIC10"', false);
    }

    public function test_checkout_places_voucher_panel_before_submit_button(): void
    {
        $dish = Dish::active()->firstOrFail();
        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $response = $this->get(route('localized.vi.checkout.create'))
            ->assertOk()
            ->assertSee('form="checkout-order-form"', false)
            ->assertSee('function applyManualVoucher()', false)
            ->assertSee('if (!code) return;', false);

        $content = $response->getContent();
        $voucherPanelPosition = strpos($content, 'data-voucher-panel');
        $submitButtonPosition = strpos($content, 'data-checkout-submit');

        $this->assertNotFalse($voucherPanelPosition);
        $this->assertNotFalse($submitButtonPosition);
        $this->assertLessThan($submitButtonPosition, $voucherPanelPosition);
    }

    public function test_private_voucher_code_can_be_previewed_but_min_order_is_enforced(): void
    {
        $branch = Branch::active()->firstOrFail();
        $dish = Dish::active()->firstOrFail();
        $dish->update(['price' => 900, 'sale_price' => null]);
        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        Voucher::create([
            'code' => 'SECRET',
            'name' => 'Secret',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 200,
            'min_order_amount' => 1000,
            'is_active' => true,
            'is_public' => false,
        ]);

        $this->postJson(route('localized.vi.checkout.voucher-preview'), [
            'voucher_code' => 'SECRET',
            'branch_id' => $branch->id,
            'fulfillment_method' => 'pickup',
            'shipping_fee' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('valid', false);

        $dish->update(['price' => 1200]);

        $this->postJson(route('localized.vi.checkout.voucher-preview'), [
            'voucher_code' => 'SECRET',
            'branch_id' => $branch->id,
            'fulfillment_method' => 'pickup',
            'shipping_fee' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('discount_total', 200);
    }

    public function test_flutter_api_can_preview_private_voucher_code(): void
    {
        $branch = Branch::active()->firstOrFail();

        Voucher::create([
            'code' => 'APP300',
            'name' => 'App discount',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 300,
            'is_active' => true,
            'is_public' => false,
        ]);

        $this->postJson('/api/v1/orders/voucher', [
            'voucher_code' => 'app300',
            'branch_id' => $branch->id,
            'fulfillment_method' => 'pickup',
            'subtotal' => 2000,
            'shipping_fee' => 0,
            'customer_phone' => '306900007777',
        ])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('voucher.code', 'APP300')
            ->assertJsonPath('discount_total', 300)
            ->assertJsonPath('total', 1700);
    }

    public function test_flutter_api_lists_only_current_public_vouchers(): void
    {
        Voucher::create([
            'code' => 'PUBLICAPP',
            'name' => 'Public app',
            'description' => 'Visible in app',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 300,
            'min_order_amount' => 1000,
            'is_active' => true,
            'is_public' => true,
            'is_default' => true,
        ]);
        Voucher::create([
            'code' => 'PRIVATEAPP',
            'name' => 'Private app',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 300,
            'is_active' => true,
            'is_public' => false,
        ]);
        Voucher::create([
            'code' => 'EXPIREDAPP',
            'name' => 'Expired app',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 300,
            'ends_at' => now()->subDay(),
            'is_active' => true,
            'is_public' => true,
        ]);

        $this->getJson('/api/v1/vouchers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'code' => 'PUBLICAPP',
                'value_label' => '€3,00',
            ])
            ->assertJsonMissing(['code' => 'PRIVATEAPP'])
            ->assertJsonMissing(['code' => 'EXPIREDAPP']);
    }

    public function test_flutter_api_preview_returns_message_when_voucher_is_not_eligible(): void
    {
        $branch = Branch::active()->firstOrFail();

        Voucher::create([
            'code' => 'MIN20',
            'name' => 'Minimum order',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 300,
            'min_order_amount' => 2000,
            'is_active' => true,
            'is_public' => false,
        ]);

        $this->postJson('/api/v1/orders/voucher', [
            'voucher_code' => 'MIN20',
            'branch_id' => $branch->id,
            'fulfillment_method' => 'pickup',
            'subtotal' => 1000,
            'shipping_fee' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonStructure(['message']);
    }

    public function test_flutter_api_stores_voucher_totals_when_creating_order(): void
    {
        Mail::fake();

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => false,
            'accepts_offline_payment' => true,
        ]);

        $dish = Dish::active()->firstOrFail();
        $dish->update(['price' => 1000, 'sale_price' => null]);

        $voucher = Voucher::create([
            'code' => 'APP3',
            'name' => 'App 3',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 300,
            'is_active' => true,
            'is_public' => false,
        ]);

        $this->postJson('/api/v1/orders', [
            'branch_id' => $branch->id,
            'customer_name' => 'Flutter Voucher',
            'customer_phone' => '306900008888',
            'customer_email' => 'flutter-voucher@example.com',
            'fulfillment_method' => 'pickup',
            'voucher_code' => 'APP3',
            'requested_time' => '18:00',
            'items' => [
                [
                    'dish_id' => $dish->id,
                    'quantity' => 1,
                    'option_ids' => [],
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.voucher_code', 'APP3')
            ->assertJsonPath('data.discount_total', 300)
            ->assertJsonPath('data.total', 700);

        $order = Order::where('customer_phone', '306900008888')->firstOrFail();

        $this->assertSame($voucher->id, $order->voucher_id);
        $this->assertSame(300, $order->discount_total);
        $this->assertSame(700, $order->total);
        $this->assertSame(1, $voucher->fresh()->used_count);
    }

    public function test_flutter_api_rejects_unknown_voucher_when_creating_order(): void
    {
        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => false,
            'accepts_offline_payment' => true,
        ]);

        $dish = Dish::active()->firstOrFail();
        $dish->update(['price' => 1000, 'sale_price' => null]);

        $this->postJson('/api/v1/orders', [
            'branch_id' => $branch->id,
            'customer_name' => 'Invalid Voucher',
            'customer_phone' => '306900006666',
            'customer_email' => 'invalid-voucher@example.com',
            'fulfillment_method' => 'pickup',
            'voucher_code' => 'NOTREAL',
            'requested_time' => '18:00',
            'items' => [
                [
                    'dish_id' => $dish->id,
                    'quantity' => 1,
                    'option_ids' => [],
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_code');

        $this->assertDatabaseMissing('orders', [
            'customer_phone' => '306900006666',
        ]);
    }

    public function test_flutter_api_calculates_delivery_quote(): void
    {
        config()->set('services.geoapify.key', 'fake-key');
        Http::fake([
            'https://api.geoapify.com/v1/routing*' => Http::response([
                'results' => [[
                    'distance' => 1500,
                    'time' => 600,
                ]],
            ]),
        ]);

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'accepts_online_orders' => true,
            'accepts_delivery_orders' => true,
            'auto_delivery_quote_enabled' => true,
            'delivery_min_order_amount' => 0,
            'delivery_origin_latitude' => 38.2466,
            'delivery_origin_longitude' => 21.7346,
        ]);
        $branch->deliveryZones()->create([
            'label' => '0 - 2 km',
            'min_distance_km' => 0,
            'max_distance_km' => 2,
            'fee' => 250,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/orders/delivery-quote', [
            'branch_id' => $branch->id,
            'fulfillment_method' => 'delivery',
            'subtotal' => 1000,
            'delivery_address' => '12 Test Street, Patras',
            'delivery_latitude' => 38.24,
            'delivery_longitude' => 21.73,
        ])
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('manual', false)
            ->assertJsonPath('fee', 250)
            ->assertJsonPath('total', 1250)
            ->assertJsonPath('distance_label', '1,5 km');
    }

    public function test_flutter_api_calculates_delivery_quote_even_when_auto_flag_is_disabled(): void
    {
        config()->set('services.geoapify.key', 'fake-key');
        Http::fake([
            'https://api.geoapify.com/v1/routing*' => Http::response([
                'results' => [[
                    'distance' => 1500,
                    'time' => 600,
                ]],
            ]),
        ]);

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'accepts_online_orders' => true,
            'accepts_delivery_orders' => true,
            'auto_delivery_quote_enabled' => false,
            'delivery_min_order_amount' => 0,
            'delivery_origin_latitude' => 38.2466,
            'delivery_origin_longitude' => 21.7346,
        ]);
        $branch->deliveryZones()->create([
            'label' => '0 - 2 km',
            'min_distance_km' => 0,
            'max_distance_km' => 2,
            'fee' => 250,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/orders/delivery-quote', [
            'branch_id' => $branch->id,
            'fulfillment_method' => 'delivery',
            'subtotal' => 1000,
            'delivery_address' => '12 Test Street, Patras',
            'delivery_latitude' => 38.24,
            'delivery_longitude' => 21.73,
        ])
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('manual', false)
            ->assertJsonPath('fee', 250)
            ->assertJsonPath('total', 1250)
            ->assertJsonPath('source', 'geoapify');
    }

    public function test_flutter_api_delivery_order_uses_shipping_fee_and_free_shipping_voucher(): void
    {
        Mail::fake();
        config()->set('services.geoapify.key', 'fake-key');
        Http::fake([
            'https://api.geoapify.com/v1/routing*' => Http::response([
                'results' => [[
                    'distance' => 1500,
                    'time' => 600,
                ]],
            ]),
        ]);

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => true,
            'accepts_offline_payment' => true,
            'auto_delivery_quote_enabled' => true,
            'delivery_min_order_amount' => 0,
            'delivery_origin_latitude' => 38.2466,
            'delivery_origin_longitude' => 21.7346,
        ]);
        $branch->deliveryZones()->create([
            'label' => '0 - 2 km',
            'min_distance_km' => 0,
            'max_distance_km' => 2,
            'fee' => 250,
            'is_active' => true,
        ]);

        $dish = Dish::active()->firstOrFail();
        $dish->update(['price' => 1000, 'sale_price' => null]);

        $voucher = Voucher::create([
            'code' => 'SHIPFREE',
            'name' => 'Free shipping',
            'discount_type' => Voucher::TYPE_FREE_SHIPPING,
            'discount_value' => 0,
            'is_active' => true,
            'is_public' => true,
        ]);

        $this->postJson('/api/v1/orders', [
            'branch_id' => $branch->id,
            'customer_name' => 'Delivery Voucher',
            'customer_phone' => '306900005555',
            'customer_email' => 'delivery-voucher@example.com',
            'fulfillment_method' => 'delivery',
            'delivery_address' => '12 Test Street, Patras',
            'delivery_latitude' => 38.24,
            'delivery_longitude' => 21.73,
            'voucher_code' => 'SHIPFREE',
            'requested_time' => '18:00',
            'items' => [
                [
                    'dish_id' => $dish->id,
                    'quantity' => 1,
                    'option_ids' => [],
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.shipping_fee', 250)
            ->assertJsonPath('data.discount_total', 250)
            ->assertJsonPath('data.total', 1000);

        $order = Order::with('shipment')->where('customer_phone', '306900005555')->firstOrFail();

        $this->assertSame(250, $order->shipping_fee);
        $this->assertSame(250, $order->discount_total);
        $this->assertSame(1000, $order->total);
        $this->assertSame('geoapify', $order->delivery_quote_source);
        $this->assertFalse($order->delivery_fee_overridden);
        $this->assertSame(250, $order->shipment->fee);
        $this->assertSame(1, $voucher->fresh()->used_count);
    }

    public function test_checkout_stores_voucher_totals_on_order_invoice_and_payment(): void
    {
        Mail::fake();

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => false,
            'accepts_offline_payment' => true,
        ]);
        $dish = Dish::active()->firstOrFail();
        $dish->update(['price' => 1000, 'sale_price' => null]);
        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 2]);

        $voucher = Voucher::create([
            'code' => 'SAVE3',
            'name' => 'Save 3',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 300,
            'is_active' => true,
            'is_public' => false,
        ]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Voucher Customer',
            'customer_phone' => '306900009999',
            'customer_email' => 'voucher@example.com',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'offline',
            'voucher_code' => 'SAVE3',
            'requested_time' => '18:00',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order = Order::with(['invoice', 'payments'])->where('customer_phone', '306900009999')->firstOrFail();

        $this->assertSame($voucher->id, $order->voucher_id);
        $this->assertSame('SAVE3', $order->voucher_code);
        $this->assertSame(2000, $order->subtotal);
        $this->assertSame(300, $order->discount_total);
        $this->assertSame(1700, $order->total);
        $this->assertSame(300, $order->invoice->discount_total);
        $this->assertSame(1700, $order->invoice->total);
        $this->assertSame(1700, $order->payments->first()->amount);
        $this->assertSame(1, $voucher->fresh()->used_count);
        $this->assertDatabaseHas('voucher_redemptions', [
            'voucher_id' => $voucher->id,
            'order_id' => $order->id,
            'customer_key' => 'email:voucher@example.com',
            'discount_total' => 300,
        ]);
    }

    public function test_customer_usage_limit_blocks_repeat_customer(): void
    {
        Mail::fake();

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => false,
            'accepts_offline_payment' => true,
        ]);
        $dish = Dish::active()->firstOrFail();
        $dish->update(['price' => 1000, 'sale_price' => null]);

        Voucher::create([
            'code' => 'ONCE',
            'name' => 'Once',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 100,
            'usage_limit_per_customer' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        foreach ([1, 2] as $attempt) {
            $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);
            $response = $this->post(route('localized.vi.checkout.store'), [
                'branch_id' => $branch->id,
                'customer_name' => 'Repeat Customer',
                'customer_phone' => '306900001111',
                'customer_email' => 'repeat@example.com',
                'fulfillment_method' => 'pickup',
                'payment_method' => 'offline',
                'voucher_code' => 'ONCE',
                'requested_time' => '18:00',
            ]);

            if ($attempt === 1) {
                $response->assertRedirect()->assertSessionHas('success');
            } else {
                $response->assertSessionHasErrors('voucher_code');
            }
        }
    }

    public function test_setting_default_voucher_unsets_previous_default(): void
    {
        $first = Voucher::create([
            'code' => 'FIRST',
            'name' => 'First',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 100,
            'is_active' => true,
            'is_public' => true,
            'is_default' => true,
        ]);

        $second = Voucher::create([
            'code' => 'SECOND',
            'name' => 'Second',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 100,
            'is_active' => true,
            'is_public' => true,
            'is_default' => true,
        ]);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }
}
