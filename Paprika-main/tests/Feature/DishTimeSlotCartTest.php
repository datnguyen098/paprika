<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Dish;
use App\Models\DishTimeSlot;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DishTimeSlotCartTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_add_to_cart_is_blocked_outside_time_slot(): void
    {
        $branch = Branch::query()->firstOrFail();
        $dish = Dish::query()->firstOrFail();

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Morning',
            'start_date' => business_today($branch)->toDateString(),
            'end_date' => business_today($branch)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'is_active' => true,
        ]);
        $dish->timeSlots()->attach($slot->id);

        Carbon::setTestNow(Carbon::parse(business_today($branch)->toDateString().' 10:00:00', business_timezone($branch)));

        $this->withSession(['active_branch_id' => $branch->id])
            ->post('/vi/gio-hang/'.$dish->slug, ['quantity' => 1])
            ->assertSessionHas('warning');
    }

    public function test_add_to_cart_is_allowed_inside_time_slot(): void
    {
        $branch = Branch::query()->firstOrFail();
        $dish = Dish::query()->firstOrFail();

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Morning',
            'start_date' => business_today($branch)->toDateString(),
            'end_date' => business_today($branch)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '11:00',
            'is_active' => true,
        ]);
        $dish->timeSlots()->attach($slot->id);

        Carbon::setTestNow(Carbon::parse(business_today($branch)->toDateString().' 10:00:00', business_timezone($branch)));

        $this->withSession(['active_branch_id' => $branch->id])
            ->post('/vi/gio-hang/'.$dish->slug, ['quantity' => 1])
            ->assertSessionHas('success');
    }

    public function test_json_add_to_cart_marks_unavailable_time_slot_as_soft_warning(): void
    {
        $branch = Branch::query()->firstOrFail();
        $dish = Dish::query()->firstOrFail();

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Morning',
            'start_date' => business_today($branch)->toDateString(),
            'end_date' => business_today($branch)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'is_active' => true,
        ]);
        $dish->timeSlots()->attach($slot->id);

        Carbon::setTestNow(Carbon::parse(business_today($branch)->toDateString().' 10:00:00', business_timezone($branch)));

        $this->withSession(['active_branch_id' => $branch->id])
            ->postJson('/vi/gio-hang/'.$dish->slug, ['quantity' => 1])
            ->assertOk()
            ->assertJsonPath('soft_warning', true)
            ->assertJsonPath('warning_code', 'unavailable_time_slot_now');
    }
}
