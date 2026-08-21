<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KitchenFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_kitchen_only_shows_paid_orders(): void
    {
        $admin = $this->admin();
        $paid = $this->order('paid', ['code' => 'KITCHEN-PAID']);
        $unpaid = $this->order('unpaid', ['code' => 'KITCHEN-UNPAID']);

        $this->actingAs($admin)
            ->get(route('admin.kitchen.index'))
            ->assertOk()
            ->assertSee($paid->code)
            ->assertDontSee($unpaid->code);
    }

    public function test_kitchen_action_rejects_unpaid_orders(): void
    {
        $admin = $this->admin();
        $unpaid = $this->order('unpaid', ['status' => 'pending']);

        $this->actingAs($admin)
            ->putJson(route('admin.kitchen.update', $unpaid), ['action' => 'preparing'])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        $this->assertSame('pending', $unpaid->fresh()->status);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@paprika-patras.gr')->firstOrFail();
    }

    private function order(string $paymentStatus, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'code' => 'KITCHEN-'.Str::upper(Str::random(8)),
            'branch_id' => Branch::active()->firstOrFail()->id,
            'customer_name' => 'Kitchen Customer',
            'customer_phone' => '306900000000',
            'customer_email' => 'kitchen@example.com',
            'fulfillment_method' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'offline',
            'payment_status' => $paymentStatus,
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'discount_total' => 0,
            'total' => 1000,
            'locale' => 'vi',
        ], $overrides));
    }
}
