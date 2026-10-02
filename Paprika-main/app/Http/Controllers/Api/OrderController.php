<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Dish;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Support\DishAvailabilityService;
use App\Support\DeliveryDistanceService;
use App\Support\DeliveryQuote;
use App\Support\DeliveryQuoteCalculator;
use App\Support\DeliveryRoute;
use App\Support\OpenDays;
use App\Support\OpeningHours;
use App\Support\VoucherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function deliveryQuote(
        Request $request,
        DeliveryDistanceService $deliveryDistances,
        DeliveryQuoteCalculator $deliveryQuotes,
    ): JsonResponse {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'fulfillment_method' => ['required', Rule::in(['pickup', 'delivery'])],
            'subtotal' => ['required', 'integer', 'min:0'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_place_id' => ['nullable', 'string', 'max:255'],
        ], [
            'branch_id.required' => 'Vui lòng chọn chi nhánh.',
            'fulfillment_method.required' => 'Vui lòng chọn hình thức nhận món.',
            'subtotal.required' => 'Giỏ hàng đang trống.',
        ]);

        $branch = Branch::query()
            ->active()
            ->with('deliveryZones')
            ->findOrFail((int) $data['branch_id']);
        $route = null;
        $distanceKm = null;

        if ($data['fulfillment_method'] === 'delivery') {
            try {
                $route = $deliveryDistances->routeFromBranch(
                    $branch,
                    $data['delivery_address'] ?? null,
                    $this->nullableFloat($data['delivery_latitude'] ?? null),
                    $this->nullableFloat($data['delivery_longitude'] ?? null),
                    $data['delivery_place_id'] ?? null,
                );
                $distanceKm = $route->distanceKm;
            } catch (\Throwable $exception) {
                report($exception);

                return response()->json([
                    'available' => false,
                    'message' => $this->deliveryQuoteFailureMessage($exception),
                ], 422);
            }
        }

        $quote = $deliveryQuotes->quote(
            $branch,
            $data['fulfillment_method'],
            (int) $data['subtotal'],
            $distanceKm,
        );

        return response()->json($this->deliveryQuotePayload($quote, (int) $data['subtotal'], $route));
    }

    public function addressSuggest(
        Request $request,
        DeliveryDistanceService $deliveryDistances,
    ): JsonResponse {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:255'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $branch = ! empty($data['branch_id'])
            ? Branch::query()->active()->find((int) $data['branch_id'])
            : null;

        try {
            return response()->json([
                'success' => true,
                'suggestions' => $deliveryDistances->suggestAddresses(
                    $data['q'],
                    $branch,
                    6,
                ),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => $this->deliveryQuoteFailureMessage($exception),
                'suggestions' => [],
            ], 422);
        }
    }

    public function addressReverse(
        Request $request,
        DeliveryDistanceService $deliveryDistances,
    ): JsonResponse {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $result = $deliveryDistances->reverseGeocode(
                (float) $data['latitude'],
                (float) $data['longitude'],
            );

            return response()->json([
                'success' => true,
                'formatted_address' => $result['formatted'] ?? null,
                'place_id' => $this->placeIdForStorage($result['place_id'] ?? null),
                'latitude' => $result['latitude'] ?? (float) $data['latitude'],
                'longitude' => $result['longitude'] ?? (float) $data['longitude'],
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => $this->deliveryQuoteFailureMessage($exception),
            ], 422);
        }
    }

    public function availability(
        Request $request,
        DishAvailabilityService $availability,
    ): JsonResponse {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'requested_date' => ['nullable', 'date'],
            'requested_time' => ['nullable', 'date_format:H:i'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.dish_id' => ['required', 'integer', 'exists:dishes,id'],
        ], [
            'branch_id.required' => 'Vui lòng chọn chi nhánh.',
            'requested_time.date_format' => 'Giờ nhận mong muốn phải theo định dạng HH:mm.',
            'items.required' => 'Giỏ hàng đang trống.',
            'items.min' => 'Giỏ hàng đang trống.',
            'items.*.dish_id.exists' => 'Một món trong giỏ không còn tồn tại.',
        ]);

        $branch = Branch::query()->active()->find((int) $data['branch_id']);
        if (! $branch) {
            throw ValidationException::withMessages([
                'branch_id' => 'Chi nhánh này hiện không nhận đơn.',
            ]);
        }

        return response()->json(
            $this->availabilityPayload(
                $branch,
                $data['items'],
                $data['requested_time'] ?? null,
                $data['requested_date'] ?? null,
                $availability,
            )
        );
    }

    public function voucherPreview(Request $request, VoucherService $vouchers): JsonResponse
    {
        $data = $request->validate([
            'voucher_code' => ['required', 'string', 'max:50'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'fulfillment_method' => ['required', Rule::in(['pickup', 'delivery'])],
            'subtotal' => ['required', 'integer', 'min:0'],
            'shipping_fee' => ['nullable', 'integer', 'min:0'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
        ], [
            'voucher_code.required' => 'Vui lòng nhập mã giảm giá.',
            'fulfillment_method.required' => 'Vui lòng chọn hình thức nhận món.',
            'subtotal.required' => 'Giỏ hàng đang trống.',
        ]);

        $branch = isset($data['branch_id'])
            ? Branch::query()->active()->find((int) $data['branch_id'])
            : null;
        $voucher = $vouchers->findByCodeOrId($data['voucher_code']);
        $quote = $vouchers->quote(
            $voucher,
            (int) $data['subtotal'],
            (int) ($data['shipping_fee'] ?? 0),
            $data['fulfillment_method'],
            $branch,
            $data['customer_email'] ?? null,
            $data['customer_phone'] ?? null,
            true,
        );

        return response()->json($quote->toPayload());
    }

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:190'],
        ], [
            'query.required' => 'Vui lòng nhập mã đơn, email hoặc số điện thoại.',
        ]);

        $queryRaw = trim((string) $data['query']);
        $queryLower = mb_strtolower($queryRaw);
        $digits = preg_replace('/\D+/', '', $queryRaw);

        $orders = Order::query()
            ->where(function ($sub) use ($queryRaw, $queryLower, $digits): void {
                $sub->whereRaw('LOWER(code) = ?', [$queryLower]);

                if (filter_var($queryLower, FILTER_VALIDATE_EMAIL)) {
                    $sub->orWhereRaw('LOWER(customer_email) = ?', [$queryLower]);
                }

                if ($digits) {
                    $sub->orWhere('customer_phone', 'like', '%'.$digits.'%');
                    $sub->orWhereRaw($this->phoneDigitsExpression('customer_phone').' like ?', ['%'.$digits.'%']);
                }

                if ($queryRaw !== $queryLower) {
                    $sub->orWhere('code', $queryRaw);
                }
            })
            ->latest()
            ->take(20)
            ->get()
            ->load(['branch', 'invoice', 'shipment', 'payments', 'items']);

        return response()->json([
            'success' => true,
            'message' => $orders->isEmpty()
                ? 'Không tìm thấy đơn hàng phù hợp.'
                : 'Đã tìm thấy '.$orders->count().' đơn hàng.',
            'data' => $orders->map(fn (Order $order): array => $this->transformOrder($order))->values(),
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $order->loadMissing(['items.dish', 'branch', 'invoice', 'shipment', 'payments', 'activities']);

        return response()->json([
            'success' => true,
            'data' => $this->transformOrder($order),
        ]);
    }

    public function track(Order $order): JsonResponse
    {
        $order->loadMissing(['items.dish', 'branch', 'invoice', 'shipment', 'payments', 'activities']);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $this->transformOrder($order),
                'timeline' => $this->trackingTimeline($order),
            ],
        ]);
    }

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
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_place_id' => ['nullable', 'string', 'max:255'],
            'requested_date' => ['nullable', 'date'],
            'requested_time' => ['nullable', 'date_format:H:i'],
            'voucher_code' => ['nullable', 'string', 'max:50'],
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

        $availabilityPayload = $this->availabilityPayload(
            $branch,
            $data['items'],
            $data['requested_time'] ?? null,
            $data['requested_date'] ?? null,
            app(DishAvailabilityService::class),
        );

        if ($availabilityPayload['blocked']) {
            throw ValidationException::withMessages([
                $availabilityPayload['interactive_note'] ? 'items' : 'requested_time' => $availabilityPayload['message'],
            ]);
        }

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

        $quote = $this->quoteDeliveryForOrder($branch, $data, $subtotal);
        if (! $quote->available) {
            throw ValidationException::withMessages([
                'fulfillment_method' => $quote->localizedMessage() ?: 'Chi nhánh này chưa thể nhận đơn theo hình thức đã chọn.',
            ]);
        }

        $shippingFee = $quote->manualFee ? 0 : $quote->fee;
        $voucherCode = app(VoucherService::class)->normalizeCode($data['voucher_code'] ?? null);
        $voucherQuote = null;
        if ($voucherCode) {
            $voucherService = app(VoucherService::class);
            $voucher = $voucherService->findByCodeOrId($voucherCode);
            $voucherQuote = $voucherService->quote(
                $voucher,
                $subtotal,
                $shippingFee,
                $data['fulfillment_method'],
                $branch,
                $data['customer_email'] ?? null,
                $data['customer_phone'] ?? null,
                true,
            );

            if (! $voucherQuote->valid) {
                throw ValidationException::withMessages([
                    'voucher_code' => $voucherQuote->message ?: 'Mã giảm giá không hợp lệ.',
                ]);
            }
        }
        $discountTotal = $voucherQuote?->discountTotal ?? 0;
        $total = max(0, $subtotal + $shippingFee - $discountTotal);
        $deliveryAddress = $data['fulfillment_method'] === 'delivery'
            ? ($data['delivery_address'] ?? null)
            : null;

        $order = DB::transaction(function () use ($branch, $data, $items, $subtotal, $shippingFee, $discountTotal, $total, $deliveryAddress, $voucherQuote, $quote): Order {
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
                'voucher_id' => $voucherQuote?->voucher?->id,
                'voucher_code' => $voucherQuote?->voucher?->code,
                'voucher_snapshot' => $voucherQuote?->snapshot(),
                'total' => $total,
                'delivery_address' => $deliveryAddress,
                'delivery_latitude' => $data['delivery_latitude'] ?? null,
                'delivery_longitude' => $data['delivery_longitude'] ?? null,
                'delivery_place_id' => $this->placeIdForStorage($data['delivery_place_id'] ?? null),
                'delivery_distance_km' => $quote->distanceKm,
                'delivery_zone_label' => $quote->zoneLabel,
                'delivery_quote_source' => $quote->source,
                'delivery_fee_overridden' => $data['fulfillment_method'] === 'delivery' && $quote->manualFee,
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
                    'latitude' => $data['delivery_latitude'] ?? null,
                    'longitude' => $data['delivery_longitude'] ?? null,
                    'place_id' => $this->placeIdForStorage($data['delivery_place_id'] ?? null),
                    'fee' => $shippingFee,
                    'distance_km' => $quote->distanceKm,
                    'zone_label' => $quote->localizedZoneLabel(),
                    'quote_source' => $quote->source,
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

            if ($voucherQuote?->valid) {
                app(VoucherService::class)->redeem($order, $voucherQuote);
            }

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
     * @param  array<int,array<string,mixed>>  $payloadItems
     * @return array<string,mixed>
     */
    private function availabilityPayload(
        Branch $branch,
        array $payloadItems,
        ?string $requestedTime,
        ?string $requestedDate,
        DishAvailabilityService $availability,
    ): array {
        $openingHours = OpeningHours::fromBranch($branch);
        $checkoutWindowError = $this->checkoutWindowError($branch, $requestedTime, $requestedDate, $openingHours);

        $effectiveAt = null;
        if ($requestedTime) {
            $dateString = $requestedDate ?: $this->checkoutOperatingDate($branch, $openingHours)->toDateString();
            $effectiveAt = $openingHours->scheduledAt($dateString, $requestedTime, $branch);
        }

        $unavailableItems = $this->unavailableCartItems(
            $this->availabilityCartItems($payloadItems),
            $branch,
            $effectiveAt,
            $availability,
        );
        $hasUnavailableItems = $unavailableItems !== [];

        return [
            'blocked' => $hasUnavailableItems || $checkoutWindowError !== null,
            'unavailable_names' => array_column($unavailableItems, 'name'),
            'unavailable_items' => $unavailableItems,
            'items_message' => $hasUnavailableItems
                ? $this->unavailableTimeSlotItemsMessage($unavailableItems)
                : null,
            'note' => $hasUnavailableItems
                ? __('site.checkout.time_slot_choose_note')
                : null,
            'message' => $hasUnavailableItems
                ? $this->unavailableTimeSlotMessage($unavailableItems)
                : $checkoutWindowError,
            'interactive_note' => $hasUnavailableItems,
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $payloadItems
     * @return array<int,array{dish:Dish}>
     */
    private function availabilityCartItems(array $payloadItems): array
    {
        $dishIds = collect($payloadItems)
            ->pluck('dish_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $dishes = Dish::query()
            ->with('translations')
            ->where('is_active', true)
            ->whereIn('id', $dishIds)
            ->get()
            ->keyBy('id');

        return collect($payloadItems)
            ->map(function (array $item) use ($dishes): ?array {
                $dish = $dishes->get((int) $item['dish_id']);

                return $dish ? ['dish' => $dish] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function checkoutWindowError(
        Branch $branch,
        ?string $requestedTime = null,
        ?string $requestedDate = null,
        ?OpeningHours $openingHours = null,
    ): ?string {
        $openingHours ??= OpeningHours::fromBranch($branch);
        $today = $this->checkoutOperatingDate($branch, $openingHours)->toDateString();
        $date = $requestedDate ?: $today;

        if ($date !== $today) {
            return __('site.checkout.today_only');
        }

        if (! OpenDays::isOpenOn($today, $branch)) {
            return __('site.checkout.closed_today');
        }

        $time = $requestedTime ?: business_now($branch)->format('H:i');

        if (! $openingHours->isWithin($time)) {
            return __('site.checkout.kitchen_window', ['hours' => $openingHours->label]);
        }

        if ($requestedTime && $openingHours->isPastToday($today, $time, $branch)) {
            return __('site.checkout.time_already_passed');
        }

        return null;
    }

    private function checkoutOperatingDate(Branch $branch, ?OpeningHours $openingHours = null): Carbon
    {
        $openingHours ??= OpeningHours::fromBranch($branch);

        return $openingHours->operatingDateFor(business_now($branch), $branch);
    }

    /**
     * @param  array<int,array{dish:Dish}>  $items
     * @return array<int,array<string,mixed>>
     */
    private function unavailableCartItems(
        array $items,
        Branch $branch,
        ?Carbon $effectiveAt,
        DishAvailabilityService $availability,
    ): array {
        $unavailable = [];

        foreach ($items as $item) {
            $dish = $item['dish'];
            $result = $availability->at($dish, $branch, $effectiveAt);

            if ($result->available) {
                continue;
            }

            $name = localized_field($dish, 'name', $dish->name);
            $windowLabel = $result->windowLabel(app()->getLocale());

            $unavailable[] = [
                'name' => $name,
                'windows' => $result->windowLabels(app()->getLocale()),
                'label' => $windowLabel ? "{$name} ({$windowLabel})" : $name,
            ];
        }

        return $unavailable;
    }

    private function unavailableTimeSlotMessage(array $unavailableItems): string
    {
        return $this->unavailableTimeSlotItemsMessage($unavailableItems).' '.__('site.checkout.time_slot_choose_note');
    }

    private function unavailableTimeSlotItemsMessage(array $unavailableItems): string
    {
        $itemLabels = collect($unavailableItems)
            ->pluck('label')
            ->filter()
            ->implode(', ');

        return rtrim(__('site.cart.unavailable_time_slot'), ". \t\n\r\0\x0B").': '.$itemLabels;
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
            'status_label' => $order->statusLabel($order->locale),
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'fulfillment_method' => $order->fulfillment_method,
            'fulfillment_label' => $order->fulfillmentLabel(),
            'subtotal' => $order->subtotal,
            'shipping_fee' => $order->shipping_fee,
            'discount_total' => $order->discount_total,
            'voucher_code' => $order->voucher_code,
            'total' => $order->total,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'delivery_address' => $order->delivery_address,
            'delivery_distance_km' => $order->delivery_distance_km !== null ? (float) $order->delivery_distance_km : null,
            'delivery_zone_label' => $order->delivery_zone_label,
            'delivery_quote_source' => $order->delivery_quote_source,
            'delivery_fee_overridden' => $order->delivery_fee_overridden,
            'invoice_number' => $order->invoice?->invoice_number,
            'requested_date' => $order->requested_date?->toDateString(),
            'requested_time' => $order->requested_time ? substr((string) $order->requested_time, 0, 5) : null,
            'branch' => $order->branch ? [
                'id' => $order->branch->id,
                'name' => localized_field($order->branch, 'name', $order->branch->name),
                'address' => localized_field($order->branch, 'address', $order->branch->address),
                'phone' => $order->branch->phone,
            ] : null,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'dish_name' => $item->dish_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
                'options' => $item->options_snapshot ?? [],
                'note' => $item->customization_note,
            ])->values()->all(),
            'timeline' => $this->trackingTimeline($order),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function trackingTimeline(Order $order): array
    {
        $steps = [
            ['status' => 'pending', 'icon' => 'receipt_long'],
            ['status' => 'confirmed', 'icon' => 'task_alt'],
            ['status' => 'preparing', 'icon' => 'restaurant'],
            ['status' => 'ready', 'icon' => 'room_service'],
            [
                'status' => $order->fulfillment_method === 'delivery' ? 'shipping' : 'completed',
                'icon' => $order->fulfillment_method === 'delivery' ? 'delivery_dining' : 'shopping_bag',
            ],
            ['status' => 'completed', 'icon' => 'check_circle'],
        ];
        $statusOrder = ['pending', 'confirmed', 'preparing', 'ready', 'shipping', 'completed'];
        $currentIndex = array_search($order->status, $statusOrder, true);
        $currentIndex = $currentIndex === false ? -1 : $currentIndex;

        return collect($steps)
            ->unique('status')
            ->values()
            ->map(function (array $step) use ($order, $statusOrder, $currentIndex): array {
                $stepIndex = array_search($step['status'], $statusOrder, true);
                $stepIndex = $stepIndex === false ? 999 : $stepIndex;

                return [
                    'status' => $step['status'],
                    'label' => Order::statusLabelFor($step['status'], $order->locale),
                    'icon' => $step['icon'],
                    'completed' => $order->status === 'cancelled' ? false : $stepIndex <= $currentIndex,
                    'current' => $order->status === $step['status'],
                ];
            })
            ->when($order->status === 'cancelled', fn ($timeline) => $timeline->push([
                'status' => 'cancelled',
                'label' => Order::statusLabelFor('cancelled', $order->locale),
                'icon' => 'cancel',
                'completed' => true,
                'current' => true,
            ]))
            ->values()
            ->all();
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

    private function phoneDigitsExpression(string $column): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, ' ', ''), '+', ''), '-', ''), '(', ''), ')', ''), '.', '')";
    }

    private function quoteDeliveryForOrder(Branch $branch, array $data, int $subtotal): DeliveryQuote
    {
        if (($data['fulfillment_method'] ?? 'pickup') !== 'delivery') {
            return app(DeliveryQuoteCalculator::class)->quote($branch, 'pickup', $subtotal);
        }

        $distanceKm = null;
        try {
            $route = app(DeliveryDistanceService::class)->routeFromBranch(
                $branch->loadMissing('deliveryZones'),
                $data['delivery_address'] ?? null,
                $this->nullableFloat($data['delivery_latitude'] ?? null),
                $this->nullableFloat($data['delivery_longitude'] ?? null),
                $data['delivery_place_id'] ?? null,
            );
            $distanceKm = $route->distanceKm;
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'delivery_address' => $this->deliveryQuoteFailureMessage($exception),
            ]);
        }

        return app(DeliveryQuoteCalculator::class)->quote(
            $branch->loadMissing('deliveryZones'),
            'delivery',
            $subtotal,
            $distanceKm,
        );
    }

    private function deliveryQuotePayload(DeliveryQuote $quote, int $subtotal, ?DeliveryRoute $route = null): array
    {
        $total = $quote->total($subtotal);

        return [
            'available' => $quote->available,
            'manual' => $quote->manualFee,
            'source' => $quote->source,
            'message' => $quote->localizedMessage(
                $quote->distanceKm !== null ? ['distance' => number_format($quote->distanceKm, 1, ',', '.')] : []
            ),
            'fee' => $quote->manualFee ? 0 : $quote->fee,
            'fee_formatted' => $quote->manualFee ? __('site.checkout.delivery_pending') : format_money($quote->fee),
            'distance_km' => $quote->distanceKm,
            'distance_label' => $quote->distanceKm !== null ? number_format($quote->distanceKm, 1, ',', '.').' km' : null,
            'zone_label' => $quote->localizedZoneLabel(),
            'total' => $quote->manualFee ? $subtotal : $total,
            'total_formatted' => format_money($quote->manualFee ? $subtotal : $total),
            'latitude' => $route?->latitude,
            'longitude' => $route?->longitude,
            'place_id' => $this->placeIdForStorage($route?->placeId),
            'formatted_address' => $route?->formattedAddress,
        ];
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function placeIdForStorage(mixed $value): ?string
    {
        return Order::normalizeExternalPlaceId($value);
    }

    private function deliveryQuoteFailureMessage(\Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'Missing Geoapify API key')) {
            return 'Chưa cấu hình GEOAPIFY_API_KEY nên hệ thống chưa thể tự tính phí giao hàng.';
        }

        if (str_contains($message, 'Branch delivery origin coordinates are missing')) {
            return 'Chi nhánh chưa có tọa độ xuất phát nên chưa thể tính phí giao hàng.';
        }

        if (str_contains($message, 'Geoapify could not find this delivery address')) {
            return 'Không tìm thấy địa chỉ giao hàng. Vui lòng nhập địa chỉ cụ thể hơn.';
        }

        if (str_contains($message, 'Delivery address is required')) {
            return 'Vui lòng nhập địa chỉ giao hàng để hệ thống tính phí ship.';
        }

        if (str_contains($message, 'Geoapify did not return a route distance')) {
            return 'Không tính được khoảng cách giao hàng cho địa chỉ này.';
        }

        return 'Chưa thể tính phí giao hàng. Vui lòng kiểm tra địa chỉ hoặc thử lại.';
    }
}
