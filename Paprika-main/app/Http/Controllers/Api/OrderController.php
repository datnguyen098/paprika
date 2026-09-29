<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Dish;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'customer_name' => ['required', 'string', 'min:2', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'fulfillment_method' => ['required', Rule::in(['pickup', 'delivery'])],
            'delivery_address' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(fn () => $request->input('fulfillment_method') === 'delivery'),
            ],
            'requested_date' => ['nullable', 'date'],
            'requested_time' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.dish_id' => ['required', 'integer', 'exists:dishes,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.option_ids' => ['nullable', 'array'],
            'items.*.option_ids.*' => ['integer', 'distinct'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
        ], [
            'branch_id.required' => 'Vui lòng chọn chi nhánh.',
            'branch_id.exists' => 'Chi nhánh không tồn tại.',
            'customer_name.required' => 'Vui lòng nhập họ tên.',
            'customer_name.min' => 'Họ tên phải có ít nhất :min ký tự.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại.',
            'customer_email.email' => 'Email không đúng định dạng.',
            'fulfillment_method.required' => 'Vui lòng chọn hình thức nhận món.',
            'fulfillment_method.in' => 'Hình thức nhận món không hợp lệ.',
            'delivery_address.required_if' => 'Vui lòng nhập địa chỉ giao hàng.',
            'requested_time.date_format' => 'Giờ nhận mong muốn phải theo định dạng HH:mm.',
            'items.required' => 'Giỏ hàng đang trống.',
            'items.min' => 'Giỏ hàng đang trống.',
            'items.*.dish_id.exists' => 'Một món trong giỏ không còn tồn tại.',
            'items.*.quantity.min' => 'Số lượng món phải lớn hơn 0.',
            'items.*.quantity.max' => 'Số lượng mỗi món tối đa là 99.',
        ]);

        $branch = Branch::query()->active()->find((int) $data['branch_id']);
        if (! $branch) {
            throw ValidationException::withMessages([
                'branch_id' => 'Chi nhánh này hiện không nhận đơn.',
            ]);
        }
        $this->ensureBranchAccepts($branch, $data['fulfillment_method']);

        $items = $this->buildOrderItems($data['items']);
        $subtotal = array_sum(array_column($items, 'line_total'));

        if ($data['fulfillment_method'] === 'delivery'
            && $branch->delivery_min_order_amount
            && $subtotal < $branch->delivery_min_order_amount
        ) {
            throw ValidationException::withMessages([
                'items' => 'Đơn giao hàng tối thiểu '.number_format($branch->delivery_min_order_amount / 100, 2).' €.',
            ]);
        }

        $shippingFee = 0;
        $discountTotal = 0;
        $total = max(0, $subtotal + $shippingFee - $discountTotal);
        $deliveryAddress = $data['fulfillment_method'] === 'delivery'
            ? ($data['delivery_address'] ?? null)
            : null;

        $order = DB::transaction(function () use ($branch, $data, $items, $subtotal, $shippingFee, $discountTotal, $total, $deliveryAddress): Order {
            $order = Order::create([
                'code' => $this->generateOrderCode($branch),
                'branch_id' => $branch->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'fulfillment_method' => $data['fulfillment_method'],
                'requested_date' => $data['requested_date'] ?? null,
                'requested_time' => $data['requested_time'] ?? null,
                'status' => 'pending',
                'payment_method' => 'offline',
                'payment_status' => 'unpaid',
                'locale' => app()->getLocale(),
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount_total' => $discountTotal,
                'total' => $total,
                'delivery_address' => $deliveryAddress,
                'delivery_fee_overridden' => $data['fulfillment_method'] === 'delivery',
                'note' => $data['note'] ?? null,
            ]);

            foreach ($items as $item) {
                $order->items()->create($item);
            }

            if ($data['fulfillment_method'] === 'delivery') {
                $order->shipment()->create([
                    'carrier' => 'internal',
                    'status' => 'pending',
                    'address' => $deliveryAddress,
                    'fee' => $shippingFee,
                    'quote_source' => 'manual',
                ]);
            }

            $order->invoice()->create([
                'invoice_number' => $this->generateInvoiceNumber($branch),
                'status' => 'draft',
                'buyer_name' => $data['customer_name'],
                'buyer_phone' => $data['customer_phone'],
                'buyer_email' => $data['customer_email'] ?? null,
                'buyer_address' => $deliveryAddress,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount_total' => $discountTotal,
                'tax_total' => 0,
                'total' => $total,
            ]);

            $order->payments()->create([
                'method' => 'offline',
                'status' => 'pending',
                'amount' => $total,
                'currency' => 'EUR',
                'reference' => $order->code,
            ]);

            OrderActivity::create([
                'order_id' => $order->id,
                'action' => 'created',
                'to_status' => 'pending',
                'note' => 'Khách đặt hàng từ Flutter và chọn thanh toán offline.',
            ]);

            return $order;
        });

        $order->load(['items', 'branch', 'invoice', 'shipment', 'payments']);

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo đơn hàng. Quán sẽ liên hệ xác nhận sớm.',
            'data' => $this->transformOrder($order),
        ], 201);
    }

    private function ensureBranchAccepts(Branch $branch, string $method): void
    {
        if ($method === 'pickup' && ! $branch->accepts_pickup_orders) {
            throw ValidationException::withMessages([
                'fulfillment_method' => 'Chi nhánh này chưa nhận đơn tự đến lấy.',
            ]);
        }

        if ($method === 'delivery' && ! $branch->accepts_delivery_orders) {
            throw ValidationException::withMessages([
                'fulfillment_method' => 'Chi nhánh này chưa nhận đơn giao hàng.',
            ]);
        }

        if (! $branch->allowsOfflinePayment()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Chi nhánh này chưa bật thanh toán offline.',
            ]);
        }
    }

    /**
     * @param  array<int,array<string,mixed>>  $payloadItems
     * @return array<int,array<string,mixed>>
     */
    private function buildOrderItems(array $payloadItems): array
    {
        $out = [];

        foreach ($payloadItems as $index => $payloadItem) {
            $dish = Dish::query()
                ->with([
                    'optionGroups.translations',
                    'optionGroups.options.translations',
                    'translations',
                ])
                ->where('is_active', true)
                ->find((int) $payloadItem['dish_id']);

            if (! $dish) {
                throw ValidationException::withMessages([
                    "items.{$index}.dish_id" => 'Một món trong giỏ hiện không còn khả dụng.',
                ]);
            }

            $optionIds = collect($payloadItem['option_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $selectedOptions = $dish->optionGroups
                ->filter(fn ($group) => $group->is_active)
                ->flatMap(fn ($group) => $group->options->map(fn ($option) => [$group, $option]))
                ->filter(fn ($pair) => $pair[1]->is_active && $optionIds->contains((int) $pair[1]->id))
                ->values();

            $selectedOptionIds = $selectedOptions
                ->map(fn ($pair) => (int) $pair[1]->id)
                ->unique()
                ->values();

            $invalidOptionIds = $optionIds->diff($selectedOptionIds)->values();
            if ($invalidOptionIds->isNotEmpty()) {
                throw ValidationException::withMessages([
                    "items.{$index}.option_ids" => 'Một tuỳ chọn của món không còn khả dụng.',
                ]);
            }

            $baseUnitPrice = (int) ($dish->sale_price ?: $dish->price);
            $optionsTotal = (int) $selectedOptions->sum(fn ($pair) => (int) $pair[1]->price_delta);
            $unitPrice = max(0, $baseUnitPrice + $optionsTotal);
            $quantity = (int) $payloadItem['quantity'];
            $lineTotal = $unitPrice * $quantity;

            $out[] = [
                'dish_id' => $dish->id,
                'dish_name' => localized_field($dish, 'name', $dish->name),
                'base_unit_price' => $baseUnitPrice,
                'options_total' => $optionsTotal,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
                'options_snapshot' => $selectedOptions
                    ->map(fn ($pair) => [
                        'group_id' => $pair[0]->id,
                        'group_name' => localized_field($pair[0], 'name', $pair[0]->name),
                        'option_id' => $pair[1]->id,
                        'option_name' => localized_field($pair[1], 'name', $pair[1]->name),
                        'price_delta' => (int) $pair[1]->price_delta,
                    ])
                    ->all(),
                'customization_note' => $payloadItem['note'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * @return array<string,mixed>
     */
    private function transformOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'code' => $order->code,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'fulfillment_method' => $order->fulfillment_method,
            'subtotal' => $order->subtotal,
            'shipping_fee' => $order->shipping_fee,
            'discount_total' => $order->discount_total,
            'total' => $order->total,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'delivery_address' => $order->delivery_address,
            'invoice_number' => $order->invoice?->invoice_number,
            'branch' => $order->branch ? [
                'id' => $order->branch->id,
                'name' => localized_field($order->branch, 'name', $order->branch->name),
                'address' => localized_field($order->branch, 'address', $order->branch->address),
                'phone' => $order->branch->phone,
            ] : null,
            'items' => $order->items->map(fn ($item) => [
                'dish_name' => $item->dish_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
            ])->values()->all(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    private function generateOrderCode(?Branch $branch = null): string
    {
        do {
            $code = 'DH'.business_now($branch)->format('ymd').Str::upper(Str::random(5));
        } while (Order::where('code', $code)->exists());

        return $code;
    }

    private function generateInvoiceNumber(?Branch $branch = null): string
    {
        do {
            $number = 'INV'.business_now($branch)->format('ymd').Str::upper(Str::random(5));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }
}
