<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Dish;
use App\Models\DishTimeSlot;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_bulk_cancel_cancels_only_cancelable_orders_and_logs_activity(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();
        $pending = $this->createOrder('pending');
        $completed = $this->createOrder('completed');
        $alreadyCancelled = $this->createOrder('cancelled');

        $this->actingAs($admin)
            ->post(route('admin.orders.bulk-cancel'), [
                'ids' => [$pending->id, $completed->id, $alreadyCancelled->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $pending->fresh()->status);
        $this->assertNotNull($pending->fresh()->cancelled_at);
        $this->assertSame('completed', $completed->fresh()->status);
        $this->assertSame('cancelled', $alreadyCancelled->fresh()->status);
        $this->assertDatabaseHas(OrderActivity::class, [
            'order_id' => $pending->id,
            'action' => 'bulk_cancelled',
            'from_status' => 'pending',
            'to_status' => 'cancelled',
        ]);
    }

    public function test_bulk_destroy_deletes_selected_orders(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();
        $first = $this->createOrder('pending');
        $second = $this->createOrder('confirmed');
        $untouched = $this->createOrder('pending');

        $this->actingAs($admin)
            ->post(route('admin.orders.bulk-destroy'), [
                'ids' => [$first->id, $second->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing(Order::class, ['id' => $first->id]);
        $this->assertDatabaseMissing(Order::class, ['id' => $second->id]);
        $this->assertDatabaseHas(Order::class, ['id' => $untouched->id]);
    }

    public function test_bulk_order_actions_require_a_selection(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.orders.bulk-cancel'), ['ids' => []])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->post(route('admin.orders.bulk-destroy'), ['ids' => []])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_bulk_dish_time_slot_replace_ignores_slots_from_other_branches(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();
        $branch = Branch::active()->firstOrFail();
        $otherBranch = Branch::create(['name' => 'Other Branch', 'slug' => 'other-branch', 'is_active' => true]);
        [$firstDish, $secondDish] = Dish::active()->take(2)->get();
        $targetSlot = $this->createSlot($branch, 'Dinner', '17:00', '21:00');
        $oldSlot = $this->createSlot($branch, 'Lunch', '11:00', '14:00');
        $otherBranchSlot = $this->createSlot($otherBranch, 'Other Branch Dinner', '17:00', '21:00');

        $firstDish->timeSlots()->attach([$oldSlot->id, $otherBranchSlot->id]);
        $secondDish->timeSlots()->attach($oldSlot->id);

        $this->actingAs($admin)
            ->put(route('admin.dishes.bulk-time-slots.update'), [
                'ids' => [$firstDish->id, $secondDish->id],
                'branch_id' => $branch->id,
                'mode' => 'replace',
                'time_slot_ids' => [$targetSlot->id, $otherBranchSlot->id],
            ])
            ->assertRedirect(route('admin.dishes.index'))
            ->assertSessionHas('success');

        $this->assertTrue($firstDish->fresh()->timeSlots->contains($targetSlot->id));
        $this->assertFalse($firstDish->fresh()->timeSlots->contains($oldSlot->id));
        $this->assertTrue($firstDish->fresh()->timeSlots->contains($otherBranchSlot->id));
        $this->assertTrue($secondDish->fresh()->timeSlots->contains($targetSlot->id));
        $this->assertFalse($secondDish->fresh()->timeSlots->contains($otherBranchSlot->id));
    }

    public function test_bulk_dish_time_slot_add_preserves_existing_slots(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();
        $branch = Branch::active()->firstOrFail();
        $dish = Dish::active()->firstOrFail();
        $existingSlot = $this->createSlot($branch, 'Lunch', '11:00', '14:00');
        $addedSlot = $this->createSlot($branch, 'Dinner', '17:00', '21:00');

        $dish->timeSlots()->attach($existingSlot->id);

        $this->actingAs($admin)
            ->put(route('admin.dishes.bulk-time-slots.update'), [
                'ids' => [$dish->id],
                'branch_id' => $branch->id,
                'mode' => 'add',
                'time_slot_ids' => [$addedSlot->id],
            ])
            ->assertRedirect(route('admin.dishes.index'))
            ->assertSessionHas('success');

        $slotIds = $dish->fresh()->timeSlots->pluck('id');

        $this->assertTrue($slotIds->contains($existingSlot->id));
        $this->assertTrue($slotIds->contains($addedSlot->id));
    }

    public function test_bulk_dish_time_slot_clear_removes_only_selected_branch_slots(): void
    {
        $admin = User::where('email', 'admin@paprika-patras.gr')->firstOrFail();
        $branch = Branch::active()->firstOrFail();
        $otherBranch = Branch::create(['name' => 'Other Branch', 'slug' => 'other-branch', 'is_active' => true]);
        $dish = Dish::active()->firstOrFail();
        $branchSlot = $this->createSlot($branch, 'Lunch', '11:00', '14:00');
        $otherBranchSlot = $this->createSlot($otherBranch, 'Other Branch Lunch', '11:00', '14:00');

        $dish->timeSlots()->attach([$branchSlot->id, $otherBranchSlot->id]);

        $this->actingAs($admin)
            ->put(route('admin.dishes.bulk-time-slots.update'), [
                'ids' => [$dish->id],
                'branch_id' => $branch->id,
                'mode' => 'clear',
            ])
            ->assertRedirect(route('admin.dishes.index'))
            ->assertSessionHas('success');

        $slotIds = $dish->fresh()->timeSlots->pluck('id');

        $this->assertFalse($slotIds->contains($branchSlot->id));
        $this->assertTrue($slotIds->contains($otherBranchSlot->id));
    }

    private function createOrder(string $status): Order
    {
        return Order::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'branch_id' => Branch::active()->firstOrFail()->id,
            'customer_name' => 'Bulk Customer',
            'customer_phone' => '306900000000',
            'customer_email' => 'bulk@example.com',
            'fulfillment_method' => 'pickup',
            'status' => $status,
            'payment_method' => 'offline',
            'payment_status' => 'unpaid',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 1000,
            'locale' => 'vi',
        ]);
    }

    private function createSlot(Branch $branch, string $name, string $startTime, string $endTime): DishTimeSlot
    {
        return DishTimeSlot::create([
            'branch_id' => $branch->id,
            'name' => $name,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => true,
        ]);
    }
}
