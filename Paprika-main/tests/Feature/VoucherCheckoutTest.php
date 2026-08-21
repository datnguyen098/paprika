<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Voucher;
use App\Support\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
