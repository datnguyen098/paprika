<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Dish;
use App\Models\DishTimeSlot;
use App\Models\SiteSetting;
use App\Support\DishAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DishAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dish_without_slots_is_available(): void
    {
        $branch = Branch::query()->firstOrFail();
        $dish = Dish::query()->doesntHave('timeSlots')->firstOrFail();

        $service = app(DishAvailabilityService::class);
        $result = $service->check($dish, $branch, Carbon::parse('2026-06-01 10:00:00'));

        $this->assertTrue($result->available);
    }

    public function test_dish_with_slot_outside_range_is_unavailable(): void
    {
        $branch = Branch::query()->firstOrFail();
        $dish = Dish::query()->doesntHave('timeSlots')->firstOrFail();

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Morning',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => true,
        ]);

        $dish->timeSlots()->attach($slot->id);

        $service = app(DishAvailabilityService::class);
        $result = $service->check($dish, $branch, Carbon::parse('2026-06-01 11:00:00'));

        $this->assertFalse($result->available);
    }

    public function test_dish_with_slot_inside_range_is_available(): void
    {
        $branch = Branch::query()->firstOrFail();
        $dish = Dish::query()->doesntHave('timeSlots')->firstOrFail();

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Morning',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => true,
        ]);

        $dish->timeSlots()->attach($slot->id);

        $service = app(DishAvailabilityService::class);
        $result = $service->check($dish, $branch, Carbon::parse('2026-06-01 09:00:00'));

        $this->assertTrue($result->available);
    }

    public function test_inactive_slot_does_not_restrict_availability(): void
    {
        $branch = Branch::query()->firstOrFail();
        $dish = Dish::query()->doesntHave('timeSlots')->firstOrFail();

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Inactive Morning',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => false,
        ]);

        $dish->timeSlots()->attach($slot->id);

        $service = app(DishAvailabilityService::class);
        $result = $service->check($dish, $branch, Carbon::parse('2026-06-01 11:00:00'));

        $this->assertTrue($result->available);
    }

    public function test_slot_for_other_branch_does_not_restrict_availability(): void
    {
        $branch = Branch::query()->firstOrFail();
        $otherBranch = Branch::create([
            'name' => 'Other Branch',
            'slug' => 'other-branch',
            'is_active' => true,
        ]);
        $dish = Dish::query()->doesntHave('timeSlots')->firstOrFail();

        $slot = DishTimeSlot::create([
            'branch_id' => $otherBranch->id,
            'name' => 'Other Branch Morning',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => true,
        ]);

        $dish->timeSlots()->attach($slot->id);

        $service = app(DishAvailabilityService::class);
        $result = $service->check($dish, $branch, Carbon::parse('2026-06-01 11:00:00'));

        $this->assertTrue($result->available);
    }

    public function test_slot_date_range_is_enforced(): void
    {
        $branch = Branch::query()->firstOrFail();
        $dish = Dish::query()->firstOrFail();

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'June Morning',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => true,
        ]);

        $dish->timeSlots()->attach($slot->id);

        $service = app(DishAvailabilityService::class);

        $this->assertFalse($service->check($dish, $branch, Carbon::parse('2026-05-31 09:00:00'))->available);
        $this->assertFalse($service->check($dish, $branch, Carbon::parse('2026-07-01 09:00:00'))->available);
    }

    public function test_slot_start_and_end_times_are_inclusive(): void
    {
        $branch = Branch::query()->firstOrFail();
        $dish = Dish::query()->firstOrFail();

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Morning',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => true,
        ]);

        $dish->timeSlots()->attach($slot->id);

        $service = app(DishAvailabilityService::class);

        $this->assertTrue($service->check($dish, $branch, Carbon::parse('2026-06-01 08:00:00'))->available);
        $this->assertTrue($service->check($dish, $branch, Carbon::parse('2026-06-01 10:00:00'))->available);
    }

    public function test_overnight_slot_is_supported(): void
    {
        $branch = Branch::query()->firstOrFail();
        $dish = Dish::query()->firstOrFail();

        $slot = DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => 'Late',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => '22:00',
            'end_time' => '02:00',
            'is_active' => true,
        ]);

        $dish->timeSlots()->attach($slot->id);

        $service = app(DishAvailabilityService::class);
        $result1 = $service->check($dish, $branch, Carbon::parse('2026-06-01 23:00:00'));
        $result2 = $service->check($dish, $branch, Carbon::parse('2026-06-02 01:00:00'));

        $this->assertTrue($result1->available);
        $this->assertTrue($result2->available);
    }

    public function test_branch_timezone_controls_current_availability(): void
    {
        SiteSetting::set('business_timezone', 'UTC', 'text', 'general');
        Carbon::setTestNow(Carbon::parse('2026-06-14 22:30:00', 'UTC'));

        $branch = Branch::query()->firstOrFail();
        $branch->update(['timezone' => 'Europe/Athens']);
        $dish = Dish::query()->firstOrFail();

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

        $result = app(DishAvailabilityService::class)->check($dish, $branch);

        $this->assertTrue($result->available);
    }
}
