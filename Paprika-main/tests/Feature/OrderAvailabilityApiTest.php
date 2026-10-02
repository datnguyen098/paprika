<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishTimeSlot;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAvailabilityApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_order_availability_api_blocks_unavailable_cart_items(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');

        [$branch, $dish] = $this->branchAndTimedDish('08:00', '09:00');

        $this->postJson('/api/v1/orders/availability', [
            'branch_id' => $branch->id,
            'requested_time' => '13:00',
            'items' => [
                ['dish_id' => $dish->id, 'quantity' => 1],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('blocked', true)
            ->assertJsonPath('unavailable_items.0.name', 'Breakfast bowl')
            ->assertJsonPath('unavailable_items.0.windows.0', '08:00 - 09:00');
    }

    public function test_order_store_rejects_unavailable_cart_items(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');

        [$branch, $dish] = $this->branchAndTimedDish('08:00', '09:00');

        $this->postJson('/api/v1/orders', [
            'branch_id' => $branch->id,
            'customer_name' => 'Khach Test',
            'customer_phone' => '0900000000',
            'fulfillment_method' => 'pickup',
            'requested_time' => '13:00',
            'items' => [
                ['dish_id' => $dish->id, 'quantity' => 1],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    /**
     * @return array{0:Branch,1:Dish}
     */
    private function branchAndTimedDish(string $startTime, string $endTime): array
    {
        $branch = Branch::create([
            'name' => 'Paprika Test',
            'slug' => 'paprika-test',
            'address' => 'Patras',
            'phone' => '+30 000',
            'opening_hours' => '08:00 - 22:00',
            'open_days' => '0,1,2,3,4,5,6',
            'accepts_pickup_orders' => true,
            'accepts_delivery_orders' => true,
            'accepts_offline_payment' => true,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Breakfast',
            'slug' => 'breakfast',
            'type' => 'dish',
            'is_active' => true,
        ]);

        $dish = Dish::create([
            'category_id' => $category->id,
            'name' => 'Breakfast bowl',
            'slug' => 'breakfast-bowl',
            'description' => 'Only available in the morning.',
            'price' => 900,
            'is_active' => true,
        ]);

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Breakfast',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => true,
        ]);

        $dish->timeSlots()->attach($slot->id);

        return [$branch, $dish];
    }
}
