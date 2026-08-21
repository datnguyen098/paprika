<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Dish;
use App\Models\DishTimeSlot;
use App\Models\Order;
use App\Models\SiteSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutAvailabilityDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_checkout_availability_blocks_unavailable_cart_items(): void
    {
        $branch = Branch::active()->firstOrFail();
        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Breakfast',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'is_active' => true,
        ]);
        $dish->timeSlots()->attach($slot->id);

        $response = $this->postJson(route('localized.vi.checkout.availability'), [
            'branch_id' => $branch->id,
            'requested_time' => '13:00',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('blocked', true)
            ->assertJsonPath('unavailable_items.0.name', $dish->localized('name'))
            ->assertJsonPath('unavailable_items.0.windows.0', '08h00 - 09h00')
            ->assertJsonPath('unavailable_items.0.label', $dish->localized('name').' (08h00 - 09h00)');

        $this->assertStringContainsString('Bạn vẫn có thể thanh toán nếu chọn khung giờ nhận hàng phù hợp', $response->json('message'));
    }

    public function test_checkout_availability_allows_available_cart_items(): void
    {
        $branch = Branch::active()->firstOrFail();
        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Breakfast',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => '12:00',
            'end_time' => '14:00',
            'is_active' => true,
        ]);
        $dish->timeSlots()->attach($slot->id);

        $this->postJson(route('localized.vi.checkout.availability'), [
            'branch_id' => $branch->id,
            'requested_time' => '13:00',
        ])
            ->assertOk()
            ->assertJsonPath('blocked', false)
            ->assertJsonPath('unavailable_names', []);
    }

    public function test_checkout_availability_warning_is_localized(): void
    {
        $branch = Branch::active()->firstOrFail();
        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();

        $this->post(route('localized.en.cart.add', $dish), ['quantity' => 1]);

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Breakfast',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'is_active' => true,
        ]);
        $dish->timeSlots()->attach($slot->id);

        $response = $this->postJson(route('localized.en.checkout.availability'), [
            'branch_id' => $branch->id,
            'requested_time' => '13:00',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('unavailable_items.0.windows.0', '08:00 - 09:00');

        $this->assertStringContainsString('You can still check out if you choose a matching pickup or delivery time', $response->json('message'));
    }

    public function test_checkout_availability_defaults_requested_date_to_branch_business_today(): void
    {
        SiteSetting::set('business_timezone', 'UTC', 'text', 'general');
        Carbon::setTestNow(Carbon::parse('2026-06-14 22:00:00', 'UTC'));

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'timezone' => 'Europe/Athens',
            'reservation_time_slots' => '00:00-03:00',
        ]);
        $dish = Dish::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Early Athens',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-15',
            'start_time' => '01:00',
            'end_time' => '02:00',
            'is_active' => true,
        ]);
        $dish->timeSlots()->attach($slot->id);

        $this->postJson(route('localized.vi.checkout.availability'), [
            'branch_id' => $branch->id,
            'requested_time' => '01:30',
        ])
            ->assertOk()
            ->assertJsonPath('blocked', false)
            ->assertJsonPath('unavailable_names', []);
    }

    public function test_checkout_blocks_closed_open_day(): void
    {
        SiteSetting::set('business_timezone', 'Europe/Athens', 'text', 'general');
        SiteSetting::set('open_days', '1,2,3,4,5', 'text', 'general');
        Carbon::setTestNow(Carbon::parse('2026-06-14 13:00:00', 'Europe/Athens'));

        $branch = Branch::active()->firstOrFail();
        $dish = Dish::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->postJson(route('localized.vi.checkout.availability'), [
            'branch_id' => $branch->id,
            'requested_time' => '18:00',
        ])
            ->assertOk()
            ->assertJsonPath('blocked', true)
            ->assertJsonPath('interactive_note', false);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Closed Day Customer',
            'customer_phone' => '306900000777',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'offline',
            'requested_time' => '18:00',
        ])
            ->assertSessionHasErrors('requested_time');

        $this->assertDatabaseMissing('orders', [
            'customer_phone' => '306900000777',
        ]);
    }

    public function test_checkout_uses_branch_open_days_override(): void
    {
        SiteSetting::set('business_timezone', 'Europe/Athens', 'text', 'general');
        SiteSetting::set('open_days', '1,2,3,4,5', 'text', 'general');
        Carbon::setTestNow(Carbon::parse('2026-06-14 13:00:00', 'Europe/Athens'));

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'open_days' => '0',
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => false,
            'accepts_offline_payment' => true,
        ]);
        $dish = Dish::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Branch Sunday Customer',
            'customer_phone' => '306900000778',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'offline',
            'requested_time' => '18:00',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_checkout_rejects_future_requested_date_and_time_outside_kitchen_window(): void
    {
        SiteSetting::set('business_timezone', 'Europe/Athens', 'text', 'general');
        Carbon::setTestNow(Carbon::parse('2026-06-15 13:00:00', 'Europe/Athens'));

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'reservation_time_slots' => '12:00-20:00',
            'reservation_last_order_buffer_minutes' => 30,
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => false,
            'accepts_offline_payment' => true,
        ]);
        $dish = Dish::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Future Date Customer',
            'customer_phone' => '306900000779',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'offline',
            'requested_date' => '2026-06-16',
            'requested_time' => '18:00',
        ])
            ->assertSessionHasErrors('requested_date');

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Late Kitchen Customer',
            'customer_phone' => '306900000780',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'offline',
            'requested_time' => '19:45',
        ])
            ->assertSessionHasErrors('requested_time');
    }

    public function test_checkout_availability_uses_branch_kitchen_window_without_global_last_booking_override(): void
    {
        SiteSetting::set('business_timezone', 'Europe/Athens', 'text', 'general');
        SiteSetting::set('reservation_time_slots', '12:00-23:00', 'text', 'general');
        SiteSetting::set('reservation_last_booking_time', '22:30', 'text', 'general');
        Carbon::setTestNow(Carbon::parse('2026-06-15 13:00:00', 'Europe/Athens'));

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'reservation_time_slots' => '10:00-23:50',
            'reservation_last_booking_time' => null,
            'reservation_last_order_buffer_minutes' => 0,
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => false,
            'accepts_offline_payment' => true,
        ]);
        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->postJson(route('localized.vi.checkout.availability'), [
            'branch_id' => $branch->id,
            'requested_time' => '23:40',
        ])
            ->assertOk()
            ->assertJsonPath('blocked', false)
            ->assertJsonPath('message', null);
    }

    public function test_checkout_availability_prefers_dish_time_slot_message_over_kitchen_window_message(): void
    {
        SiteSetting::set('business_timezone', 'Europe/Athens', 'text', 'general');
        Carbon::setTestNow(Carbon::parse('2026-06-15 13:00:00', 'Europe/Athens'));

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'reservation_time_slots' => '10:00-23:50',
            'reservation_last_booking_time' => null,
            'reservation_last_order_buffer_minutes' => 0,
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => false,
            'accepts_offline_payment' => true,
        ]);
        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Dinner',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => '18:00',
            'end_time' => '21:00',
            'is_active' => true,
        ]);
        $dish->timeSlots()->attach($slot->id);

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $response = $this->postJson(route('localized.vi.checkout.availability'), [
            'branch_id' => $branch->id,
            'requested_time' => '23:55',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('blocked', true)
            ->assertJsonPath('interactive_note', true)
            ->assertJsonPath('unavailable_items.0.name', $dish->localized('name'))
            ->assertJsonPath('unavailable_items.0.windows.0', '18h00 - 21h00');

        $this->assertStringContainsString($dish->localized('name'), $response->json('message'));
        $this->assertStringContainsString('18h00 - 21h00', $response->json('message'));
        $this->assertStringContainsString(__('site.checkout.time_slot_choose_note'), $response->json('message'));
        $this->assertStringNotContainsString(str(__('site.checkout.kitchen_window', ['hours' => '']))->before(':')->toString(), $response->json('message'));
    }

    public function test_checkout_allows_future_time_today_within_kitchen_window(): void
    {
        SiteSetting::set('business_timezone', 'Europe/Athens', 'text', 'general');
        Carbon::setTestNow(Carbon::parse('2026-06-15 13:00:00', 'Europe/Athens'));

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'reservation_time_slots' => '12:00-20:00',
            'reservation_last_order_buffer_minutes' => 30,
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => false,
            'accepts_offline_payment' => true,
        ]);
        $dish = Dish::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Future Time Customer',
            'customer_phone' => '306900000783',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'offline',
            'requested_time' => '18:00',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order = Order::where('customer_phone', '306900000783')->firstOrFail();

        $this->assertSame('2026-06-15', $order->requested_date->toDateString());
        $this->assertSame('18:00', (string) $order->requested_time);
    }

    public function test_checkout_allows_overnight_kitchen_time_for_current_business_day(): void
    {
        SiteSetting::set('business_timezone', 'Europe/Athens', 'text', 'general');
        SiteSetting::set('reservation_last_booking_time', '', 'text', 'general');
        Carbon::setTestNow(Carbon::parse('2026-06-15 13:00:00', 'Europe/Athens'));

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'reservation_time_slots' => '10:00-01:00',
            'reservation_last_order_buffer_minutes' => 30,
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => false,
            'accepts_offline_payment' => true,
        ]);
        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Late Night',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-15',
            'start_time' => '22:00',
            'end_time' => '01:00',
            'is_active' => true,
        ]);
        $dish->timeSlots()->attach($slot->id);

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->postJson(route('localized.vi.checkout.availability'), [
            'branch_id' => $branch->id,
            'requested_time' => '00:30',
        ])
            ->assertOk()
            ->assertJsonPath('blocked', false)
            ->assertJsonPath('unavailable_names', []);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Overnight Customer',
            'customer_phone' => '306900000784',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'offline',
            'requested_time' => '00:30',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order = Order::where('customer_phone', '306900000784')->firstOrFail();

        $this->assertSame('2026-06-16', $order->requested_date->toDateString());
        $this->assertSame('00:30', (string) $order->requested_time);

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Too Late Overnight',
            'customer_phone' => '306900000785',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'offline',
            'requested_time' => '00:45',
        ])
            ->assertSessionHasErrors('requested_time');
    }

    public function test_checkout_without_requested_time_uses_current_kitchen_window(): void
    {
        SiteSetting::set('business_timezone', 'Europe/Athens', 'text', 'general');
        Carbon::setTestNow(Carbon::parse('2026-06-15 21:00:00', 'Europe/Athens'));

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'reservation_time_slots' => '12:00-20:00',
            'reservation_last_order_buffer_minutes' => 0,
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => false,
            'accepts_offline_payment' => true,
        ]);
        $dish = Dish::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->postJson(route('localized.vi.checkout.availability'), [
            'branch_id' => $branch->id,
        ])
            ->assertOk()
            ->assertJsonPath('blocked', true)
            ->assertJsonPath('interactive_note', false);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Asap Closed Kitchen',
            'customer_phone' => '306900000781',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'offline',
        ])
            ->assertSessionHasErrors('requested_time');

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Later Same Day',
            'customer_phone' => '306900000782',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'offline',
            'requested_time' => '19:00',
        ])
            ->assertSessionHasErrors('requested_time');
    }

    public function test_checkout_order_code_and_invoice_use_branch_business_date(): void
    {
        Mail::fake();
        SiteSetting::set('business_timezone', 'UTC', 'text', 'general');
        Carbon::setTestNow(Carbon::parse('2026-06-14 22:30:00', 'UTC'));

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'timezone' => 'Europe/Athens',
            'accepts_online_orders' => true,
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => false,
            'accepts_offline_payment' => true,
        ]);
        $dish = Dish::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Timezone Customer',
            'customer_phone' => '306900000321',
            'customer_email' => 'timezone@example.com',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'offline',
            'requested_time' => '18:00',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order = Order::with('invoice')->where('customer_phone', '306900000321')->firstOrFail();

        $this->assertStringStartsWith('DH260615', $order->code);
        $this->assertStringStartsWith('INV260615', $order->invoice->invoice_number);
    }

    public function test_delivery_quote_uses_manual_fee_for_manual_branch(): void
    {
        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'accepts_online_orders' => true,
            'accepts_delivery_orders' => true,
            'auto_delivery_quote_enabled' => false,
            'delivery_min_order_amount' => 0,
        ]);
        $dish = Dish::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 2]);

        $this->postJson(route('localized.vi.checkout.delivery-quote'), [
            'branch_id' => $branch->id,
            'fulfillment_method' => 'delivery',
            'delivery_address' => '12 Test Street, Patras',
        ])
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('manual', true)
            ->assertJsonPath('source', 'manual');
    }

    public function test_auto_delivery_quote_failure_returns_validation_error(): void
    {
        config()->set('services.geoapify.key', 'fake-key');
        Http::fake([
            'https://api.geoapify.com/v1/routing*' => Http::response([], 500),
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
        $dish = Dish::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->postJson(route('localized.vi.checkout.delivery-quote'), [
            'branch_id' => $branch->id,
            'fulfillment_method' => 'delivery',
            'delivery_address' => '12 Test Street, Patras',
            'delivery_latitude' => 38.24,
            'delivery_longitude' => 21.73,
        ])
            ->assertStatus(422)
            ->assertJsonPath('available', false);
    }

    public function test_delivery_checkout_can_proceed_with_manual_quote_when_auto_quote_is_skipped(): void
    {
        Mail::fake();

        $branch = Branch::active()->firstOrFail();
        $branch->update([
            'accepts_online_orders' => true,
            'accepts_delivery_orders' => true,
            'accepts_offline_payment' => true,
            'auto_delivery_quote_enabled' => true,
            'delivery_min_order_amount' => 0,
            'delivery_origin_latitude' => 38.2466,
            'delivery_origin_longitude' => 21.7346,
        ]);
        $dish = Dish::active()->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Manual Quote Customer',
            'customer_phone' => '306900000123',
            'customer_email' => 'manual@example.com',
            'fulfillment_method' => 'delivery',
            'payment_method' => 'offline',
            'delivery_address' => '12 Test Street, Patras',
            'delivery_address_final' => '12 Test Street, Patras',
            'proceed_without_quote' => '1',
            'requested_time' => '18:00',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order = Order::with('shipment')->where('customer_phone', '306900000123')->firstOrFail();

        $this->assertSame('delivery', $order->fulfillment_method);
        $this->assertSame(0, $order->shipping_fee);
        $this->assertSame('manual', $order->delivery_quote_source);
        $this->assertTrue($order->delivery_fee_overridden);
        $this->assertSame('manual', $order->shipment->quote_source);
    }

    public function test_auto_delivery_checkout_handles_long_geoapify_place_id(): void
    {
        Mail::fake();

        $longPlaceId = '51'.str_repeat('abcdef1234567890', 24);

        config()->set('services.geoapify.key', 'fake-key');
        Http::fake([
            'https://api.geoapify.com/v1/geocode/search*' => Http::response([
                'features' => [[
                    'properties' => [
                        'formatted' => 'Long Place ID Street, Patras',
                        'place_id' => $longPlaceId,
                    ],
                    'geometry' => [
                        'coordinates' => [21.7346, 38.2466],
                    ],
                ]],
            ]),
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
            'accepts_offline_payment' => true,
            'auto_delivery_quote_enabled' => true,
            'delivery_min_order_amount' => 0,
            'delivery_max_distance_km' => 10,
            'delivery_origin_latitude' => 38.2466,
            'delivery_origin_longitude' => 21.7346,
        ]);
        $dish = Dish::active()->doesntHave('timeSlots')->firstOrFail();

        $this->post(route('localized.vi.cart.add', $dish), ['quantity' => 1]);

        $this->post(route('localized.vi.checkout.store'), [
            'branch_id' => $branch->id,
            'customer_name' => 'Long Place Customer',
            'customer_phone' => '306900000456',
            'customer_email' => 'long-place@example.com',
            'fulfillment_method' => 'delivery',
            'payment_method' => 'offline',
            'delivery_address_final' => 'Long Place ID Street, Patras',
            'requested_time' => '18:00',
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $order = Order::with('shipment')->where('customer_phone', '306900000456')->firstOrFail();

        $this->assertSame(Order::DELIVERY_PLACE_ID_MAX_LENGTH, strlen($order->delivery_place_id));
        $this->assertFalse(str_ends_with($order->delivery_place_id, '...'));
        $this->assertSame($order->delivery_place_id, $order->shipment->place_id);
        $this->assertSame('geoapify', $order->delivery_quote_source);
    }

    public function test_delivery_place_ids_are_normalized_at_model_boundary(): void
    {
        $longPlaceId = '51'.str_repeat('abcdef1234567890', 24);

        $order = new Order();
        $order->delivery_place_id = $longPlaceId;

        $shipment = new \App\Models\Shipment();
        $shipment->place_id = $longPlaceId;

        $this->assertSame(Order::DELIVERY_PLACE_ID_MAX_LENGTH, strlen($order->delivery_place_id));
        $this->assertSame(Order::DELIVERY_PLACE_ID_MAX_LENGTH, strlen($shipment->place_id));
        $this->assertFalse(str_ends_with($order->delivery_place_id, '...'));
        $this->assertFalse(str_ends_with($shipment->place_id, '...'));
    }
}
