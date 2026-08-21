<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Services\CartService;
use App\Services\Payments\VivaGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(CartService $cart): View|RedirectResponse
    {
        if ($cart->items()->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống.');
        }

        return view('checkout.create', [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
            'branches' => Branch::active()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CartService $cart, VivaGateway $viva): RedirectResponse
    {
        $items = $cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống.');
        }

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'fulfillment_method' => ['required', Rule::in(['pickup', 'delivery'])],
            'delivery_address' => ['required_if:fulfillment_method,delivery', 'nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', Rule::in(['offline', 'viva'])],
            'requested_date' => ['nullable', 'date', 'after_or_equal:today'],
            'requested_time' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['payment_method'] ??= 'offline';

        $shippingFee = 0;
        $subtotal = (int) $items->sum('line_total');
        $total = $subtotal + $shippingFee;

        $order = DB::transaction(function () use ($data, $items, $subtotal, $shippingFee, $total): Order {
            $order = Order::create([
                'code' => $this->generateOrderCode(),
                'branch_id' => $data['branch_id'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'fulfillment_method' => $data['fulfillment_method'],
                'requested_date' => $data['requested_date'] ?? null,
                'requested_time' => $data['requested_time'] ?? null,
                'status' => 'pending',
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount_total' => 0,
                'total' => $total,
                'delivery_address' => $data['delivery_address'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'dish_id' => $item['dish']->id,
                    'dish_name' => $item['dish']->name,
                    'base_unit_price' => $item['base_unit_price'],
                    'options_total' => $item['options_total'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                    'options_snapshot' => $item['options_snapshot'],
                    'customization_note' => $item['customization_note'],
                ]);
            }

            if ($data['fulfillment_method'] === 'delivery') {
                $order->shipment()->create([
                    'carrier' => 'internal',
                    'status' => 'pending',
                    'address' => $data['delivery_address'] ?? null,
                    'fee' => $shippingFee,
                ]);
            }

            $order->invoice()->create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'status' => 'draft',
                'buyer_name' => $data['customer_name'],
                'buyer_phone' => $data['customer_phone'],
                'buyer_email' => $data['customer_email'] ?? null,
                'buyer_address' => $data['delivery_address'] ?? null,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => $total,
            ]);

            $order->payments()->create([
                'method' => $data['payment_method'],
                'status' => 'pending',
                'amount' => $total,
                'currency' => 'EUR',
                'reference' => $order->code,
            ]);

            OrderActivity::create([
                'order_id' => $order->id,
                'action' => 'created',
                'to_status' => 'pending',
                'note' => $data['payment_method'] === 'viva'
                    ? 'Khách đặt hàng và chọn thanh toán Viva.'
                    : 'Khách đặt hàng và chọn thanh toán offline.',
            ]);

            return $order;
        });

        if ($data['payment_method'] === 'viva') {
            try {
                $vivaOrder = $viva->createPaymentOrder($order);
                $order->payments()->where('method', 'viva')->latest()->first()?->update([
                    'provider' => 'viva',
                    'reference' => $vivaOrder['order_code'],
                    'payload' => $vivaOrder['payload'],
                ]);
            } catch (\Throwable $exception) {
                report($exception);
                $order->delete();

                return back()
                    ->withInput()
                    ->with('error', 'Chưa thể tạo thanh toán Viva. Vui lòng kiểm tra cấu hình Viva hoặc chọn thanh toán offline.');
            }

            $cart->clear();

            return redirect()->away($vivaOrder['checkout_url']);
        }

        $cart->clear();

        return redirect()->route('checkout.success', ['order' => $order->code])->with('success', 'Đã gửi đơn hàng. Quán sẽ liên hệ xác nhận sớm.');
    }

    public function success(Order $order): View
    {
        $order->load(['items', 'branch', 'shipment', 'invoice']);

        return view('checkout.success', compact('order'));
    }

    private function generateOrderCode(): string
    {
        do {
            $code = 'DH'.now()->format('ymd').Str::upper(Str::random(5));
        } while (Order::where('code', $code)->exists());

        return $code;
    }

    private function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV'.now()->format('ymd').Str::upper(Str::random(5));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }
}
