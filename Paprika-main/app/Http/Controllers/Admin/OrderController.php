<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\Payment;
use App\Models\Shipment;
use App\Support\BranchAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = BranchAccess::apply(Order::query()->with(['branch', 'items']), $request->user())
            ->when($request->filled('q'), function ($query) use ($request): void {
                $query->where(function ($query) use ($request): void {
                    $query->where('code', 'like', '%'.$request->q.'%')
                        ->orWhere('customer_name', 'like', '%'.$request->q.'%')
                        ->orWhere('customer_phone', 'like', '%'.$request->q.'%');
                });
            })
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->branch_id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::query()
            ->active()
            ->when($request->user()?->branch_id, fn ($query) => $query->where('id', $request->user()->branch_id))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.orders.index', compact('orders', 'branches'));
    }

    public function show(Order $order): View
    {
        BranchAccess::authorize(auth()->user(), $order->branch_id);
        $order->load(['branch', 'items.dish', 'shipment', 'invoice', 'payments', 'activities.user']);

        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        BranchAccess::authorize($request->user(), $order->branch_id);

        $data = $request->validate([
            'workflow_action' => ['nullable', Rule::in(['confirmed', 'preparing', 'ready', 'shipping', 'completed', 'cancelled'])],
            'status' => ['required_without:workflow_action', Rule::in(Order::STATUSES)],
            'payment_status' => ['required_without:workflow_action', Rule::in(['unpaid', 'paid'])],
            'shipping_fee' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'delivery_distance_km' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'delivery_zone_label' => ['nullable', 'string', 'max:255'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'shipment_status' => ['nullable', Rule::in(Shipment::STATUSES)],
            'invoice_status' => ['nullable', Rule::in(Invoice::STATUSES)],
        ]);

        $fromStatus = $order->status;

        if (! empty($data['workflow_action'])) {
            $this->applyWorkflowAction($order, $request, $data['workflow_action'], $data['admin_note'] ?? null);
        } else {
            $this->applyManualUpdate($order, $request, $data, $fromStatus);
        }

        return back()->with('success', 'Đã cập nhật đơn hàng.');
    }

    private function applyWorkflowAction(Order $order, Request $request, string $action, ?string $note): void
    {
        $fromStatus = $order->status;
        $updates = ['admin_note' => $note ?? $order->admin_note];

        $updates = array_merge($updates, match ($action) {
            'confirmed' => ['status' => 'confirmed', 'confirmed_at' => $order->confirmed_at ?: now()],
            'preparing' => ['status' => 'preparing', 'preparing_at' => $order->preparing_at ?: now()],
            'ready' => ['status' => 'ready', 'ready_at' => $order->ready_at ?: now()],
            'shipping' => ['status' => 'shipping', 'shipping_at' => $order->shipping_at ?: now()],
            'completed' => ['status' => 'completed', 'completed_at' => now(), 'payment_status' => 'paid'],
            'cancelled' => ['status' => 'cancelled', 'cancelled_at' => now()],
        });

        $order->update($updates);

        if ($action === 'shipping' && $order->shipment) {
            $order->shipment->update(['status' => 'shipping', 'shipped_at' => $order->shipment->shipped_at ?: now()]);
        }

        if ($action === 'completed') {
            $order->shipment?->update(['status' => 'delivered', 'delivered_at' => $order->shipment?->delivered_at ?: now()]);
            $order->invoice?->update(['status' => 'issued', 'issued_at' => $order->invoice?->issued_at ?: now()]);
            $this->syncOrderPayment($order->refresh(), 'paid', 'workflow_completed');
        }

        $this->logActivity($order, $request, $action, $fromStatus, $order->status, $note);
    }

    private function applyManualUpdate(Order $order, Request $request, array $data, string $fromStatus): void
    {
        $oldShippingFee = (int) $order->shipping_fee;
        $shippingFee = array_key_exists('shipping_fee', $data)
            ? $this->moneyToMinorUnits($data['shipping_fee'])
            : $oldShippingFee;
        $total = max(0, (int) $order->subtotal + $shippingFee - (int) $order->discount_total);

        $updates = [
            'status' => $data['status'],
            'payment_status' => $data['payment_status'],
            'shipping_fee' => $shippingFee,
            'total' => $total,
            'delivery_distance_km' => $data['delivery_distance_km'] ?? $order->delivery_distance_km,
            'delivery_zone_label' => $data['delivery_zone_label'] ?? $order->delivery_zone_label,
            'delivery_fee_overridden' => $shippingFee !== $oldShippingFee ? true : $order->delivery_fee_overridden,
            'admin_note' => $data['admin_note'] ?? null,
        ];

        if ($fromStatus !== $data['status']) {
            $updates = array_merge($updates, match ($data['status']) {
                'confirmed' => ['confirmed_at' => $order->confirmed_at ?: now()],
                'preparing' => ['preparing_at' => $order->preparing_at ?: now()],
                'ready' => ['ready_at' => $order->ready_at ?: now()],
                'shipping' => ['shipping_at' => $order->shipping_at ?: now()],
                'completed' => ['completed_at' => now()],
                'cancelled' => ['cancelled_at' => now()],
                default => [],
            });
        }

        $order->update($updates);

        $order->shipment?->update([
            'status' => $data['shipment_status'] ?? $order->shipment->status,
            'fee' => $shippingFee,
            'distance_km' => $data['delivery_distance_km'] ?? $order->shipment->distance_km,
            'zone_label' => $data['delivery_zone_label'] ?? $order->shipment->zone_label,
        ]);

        $order->invoice?->update([
            'status' => $data['invoice_status'] ?? $order->invoice->status,
            'shipping_fee' => $shippingFee,
            'total' => $total,
            'issued_at' => ($data['invoice_status'] ?? null) === 'issued' ? ($order->invoice->issued_at ?: now()) : $order->invoice->issued_at,
            'cancelled_at' => ($data['invoice_status'] ?? null) === 'cancelled' ? now() : $order->invoice->cancelled_at,
        ]);

        $this->syncOrderPayment($order->refresh(), $data['payment_status'], 'admin_manual_update');

        $this->logActivity($order, $request, 'updated', $fromStatus, $order->status, $data['admin_note'] ?? null);
    }

    private function syncOrderPayment(Order $order, string $paymentStatus, string $source): Payment
    {
        $method = $order->payment_method ?: 'offline';

        $payment = $order->payments()
            ->where('method', $method)
            ->latest()
            ->first();

        if (! $payment) {
            $payment = new Payment([
                'method' => $method,
                'reference' => $order->code,
            ]);
            $payment->order()->associate($order);
        }

        $payment->fill([
            'provider' => $method === 'viva' ? 'viva' : $payment->provider,
            'status' => $paymentStatus === 'paid' ? 'paid' : 'pending',
            'amount' => $order->total,
            'currency' => 'EUR',
            'paid_at' => $paymentStatus === 'paid' ? ($payment->paid_at ?: now()) : null,
            'failed_at' => null,
            'payload' => array_merge($payment->payload ?? [], [
                'last_admin_sync' => [
                    'source' => $source,
                    'status' => $paymentStatus,
                    'synced_at' => now()->toIso8601String(),
                ],
            ]),
        ]);

        $payment->save();

        return $payment;
    }

    private function moneyToMinorUnits(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round(((float) str_replace(',', '.', (string) $value)) * 100);
    }

    private function logActivity(Order $order, Request $request, string $action, ?string $fromStatus, ?string $toStatus, ?string $note): void
    {
        OrderActivity::create([
            'order_id' => $order->id,
            'user_id' => $request->user()?->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
        ]);
    }

    public function destroy(Order $order): RedirectResponse
    {
        BranchAccess::authorize(auth()->user(), $order->branch_id);
        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Đã xóa đơn hàng.');
    }

    public function bulkCancel(Request $request): RedirectResponse
    {
        $ids = $this->validateBulkIds($request);
        if (empty($ids)) {
            return back()->with('error', 'Chưa chọn đơn hàng nào.');
        }

        $cancelled = 0;
        foreach ($ids as $id) {
            $order = Order::find($id);
            if (! $order || $order->status === 'cancelled' || $order->status === 'completed') {
                continue;
            }
            try {
                BranchAccess::authorize(auth()->user(), $order->branch_id);
            } catch (\Throwable) {
                continue;
            }
            $fromStatus = $order->status;
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
            OrderActivity::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'action' => 'bulk_cancelled',
                'from_status' => $fromStatus,
                'to_status' => 'cancelled',
                'note' => 'Hủy hàng loạt từ danh sách.',
            ]);
            $cancelled++;
        }

        return back()->with('success', "Đã hủy {$cancelled} đơn hàng.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $this->validateBulkIds($request);
        if (empty($ids)) {
            return back()->with('error', 'Chưa chọn đơn hàng nào.');
        }

        $deleted = 0;
        foreach ($ids as $id) {
            $order = Order::find($id);
            if (! $order) {
                continue;
            }
            try {
                BranchAccess::authorize(auth()->user(), $order->branch_id);
            } catch (\Throwable) {
                continue;
            }
            $order->delete();
            $deleted++;
        }

        return back()->with('success', "Đã xóa {$deleted} đơn hàng.");
    }

    private function validateBulkIds(Request $request): array
    {
        return array_filter(
            array_map('intval', (array) $request->input('ids', [])),
            fn ($id) => $id > 0
        );
    }
}
