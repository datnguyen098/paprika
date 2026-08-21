<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Services\CartService;
use App\Services\Payments\VivaGateway;
use App\Support\DishAvailabilityService;
use App\Support\DeliveryDistanceService;
use App\Support\DeliveryQuote;
use App\Support\DeliveryQuoteCalculator;
use App\Support\DeliveryRoute;
use App\Support\OpenDays;
use App\Support\OpeningHours;
use App\Support\PendingVivaPayment;
use Carbon\Carbon;
use App\Support\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly DeliveryQuoteCalculator $deliveryQuotes,
        private readonly DeliveryDistanceService $deliveryDistances,
    ) {}

    public function create(CartService $cart): View|RedirectResponse
    {
        if ($cart->items()->isEmpty()) {
            $cartRoutes = ['vi' => '/vi/gio-hang', 'en' => '/en/cart', 'el' => '/el/kalaithi'];
            return redirect($cartRoutes[app()->getLocale()] ?? '/vi/gio-hang')->with('error', 'Giỏ hàng đang trống.');
        }

        $items = $cart->items();
        $branches = Branch::active()->with('deliveryZones')->orderBy('sort_order')->orderBy('name')->get();
        $voucherService = app(VoucherService::class);
        $defaultVoucher = $voucherService->defaultVoucher($branches->first()?->id);
        $selectedVoucherCode = old('voucher_code', session(VoucherService::SESSION_KEY, $defaultVoucher?->code));

        return view('storefront.checkout.create', [
            'items' => $items,
            'subtotal' => (int) $items->sum('line_total'),
            'branches' => $branches,
            'publicVouchers' => $voucherService->publicVouchers(),
            'defaultVoucher' => $defaultVoucher,
            'selectedVoucherCode' => $selectedVoucherCode,
        ]);
    }

    public function voucherPreview(Request $request, CartService $cart, VoucherService $vouchers): JsonResponse
    {
        $data = $request->validate([
            'voucher_id' => ['nullable', 'integer', 'exists:vouchers,id'],
            'voucher_code' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'fulfillment_method' => ['nullable', Rule::in(['pickup', 'delivery'])],
            'shipping_fee' => ['nullable', 'integer', 'min:0'],
        ]);

        $voucher = $vouchers->findByCodeOrId($data['voucher_code'] ?? null, isset($data['voucher_id']) ? (int) $data['voucher_id'] : null);
        $branch = ! empty($data['branch_id']) ? Branch::query()->active()->find((int) $data['branch_id']) : null;
        $subtotal = (int) $cart->items()->sum('line_total');
        $quote = $vouchers->quote(
            $voucher,
            $subtotal,
            (int) ($data['shipping_fee'] ?? 0),
            $data['fulfillment_method'] ?? 'pickup',
            $branch,
        );

        if ($quote->valid && $voucher) {
            session([VoucherService::SESSION_KEY => $voucher->code]);
        }

        return response()->json($quote->toPayload());
    }

    public function clearVoucher(): JsonResponse
    {
        session()->forget(VoucherService::SESSION_KEY);

        return response()->json(['cleared' => true]);
    }

    public function deliveryQuote(Request $request, CartService $cart): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'fulfillment_method' => ['nullable', Rule::in(['pickup', 'delivery'])],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_place_id' => ['nullable', 'string', 'max:255'],
            'delivery_quote_address' => ['nullable', 'string', 'max:1000'],
            'delivery_quote_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_quote_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_quote_place_id' => ['nullable', 'string', 'max:255'],
        ]);

        $branch = Branch::query()->active()->with('deliveryZones')->findOrFail($data['branch_id']);
        $subtotal = $cart->subtotal();
        $route = null;
        $distanceKm = null;

        if (($data['fulfillment_method'] ?? 'delivery') === 'delivery' && $branch->auto_delivery_quote_enabled) {
            // Prefer _quote_* fields (set when fee is confirmed); fall back to main fields
            $calcAddress  = $data['delivery_quote_address']   ?? $data['delivery_address']   ?? null;
            $calcLatitude  = $this->nullableFloat($data['delivery_quote_latitude']  ?? $data['delivery_latitude']  ?? null);
            $calcLongitude = $this->nullableFloat($data['delivery_quote_longitude'] ?? $data['delivery_longitude'] ?? null);
            $calcPlaceId  = $data['delivery_quote_place_id']   ?? $data['delivery_place_id']   ?? null;

            try {
                $route = $this->deliveryDistances->routeFromBranch(
                    $branch,
                    $calcAddress,
                    $calcLatitude,
                    $calcLongitude,
                    $calcPlaceId,
                );
                $distanceKm = $route->distanceKm;
            } catch (\Throwable $exception) {
                report($exception);

                return response()->json([
                    'available' => false,
                    'message' => $this->deliveryQuoteFailureMessage(),
                ], 422);
            }
        }

        $quote = $this->deliveryQuotes->quote(
            $branch,
            $data['fulfillment_method'] ?? 'delivery',
            $subtotal,
            $distanceKm
        );

        return response()->json($this->quotePayload($quote, $subtotal, $route));
    }

    public function availability(Request $request, CartService $cart, DishAvailabilityService $availability): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'requested_date' => ['nullable', 'date'],
            'requested_time' => ['nullable', 'date_format:H:i'],
        ]);

        $branch = Branch::query()->active()->findOrFail((int) $data['branch_id']);
        $openingHours = OpeningHours::fromBranch($branch);
        $checkoutWindowError = $this->checkoutWindowError($branch, $data['requested_time'] ?? null, $data['requested_date'] ?? null, $openingHours);

        $effectiveAt = null;
        if (! empty($data['requested_time'])) {
            $dateString = $data['requested_date'] ?? $this->checkoutOperatingDate($branch, $openingHours)->toDateString();
            $effectiveAt = $openingHours->scheduledAt($dateString, $data['requested_time'], $branch);
        }

        $items = $cart->items();

        $unavailableItems = $this->unavailableCartItems($items, $branch, $effectiveAt, $availability);
        $unavailableNames = array_column($unavailableItems, 'name');
        $hasUnavailableItems = $unavailableItems !== [];

        return response()->json([
            'blocked' => $hasUnavailableItems || $checkoutWindowError !== null,
            'unavailable_names' => $unavailableNames,
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
        ]);
    }

    public function addressSuggest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:255'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $branch = null;
        if (!empty($data['branch_id'])) {
            $branch = Branch::query()->active()->find($data['branch_id']);
        }

        try {
            $suggestions = $this->deliveryDistances->suggestAddresses(
                $data['q'],
                $branch,
                6
            );

            return response()->json([
                'suggestions' => $suggestions,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

                return response()->json([
                    'message' => $this->deliveryQuoteFailureMessage(),
                    'suggestions' => [],
                ], 422);
        }
    }

    public function addressReverse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $result = $this->deliveryDistances->reverseGeocode(
                (float) $data['latitude'],
                (float) $data['longitude']
            );

            return response()->json([
                'formatted_address' => $result['formatted'] ?? null,
                'place_id' => $result['place_id'] ?? null,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

                return response()->json([
                    'message' => $this->deliveryQuoteFailureMessage(),
                ], 422);
        }
    }

    public function store(Request $request, CartService $cart, VivaGateway $viva, DishAvailabilityService $availability, VoucherService $vouchers): RedirectResponse
    {
        $items = $cart->items();

        if ($items->isEmpty()) {
            $cartPaths = ['vi' => '/vi/gio-hang', 'en' => '/en/cart', 'el' => '/el/kalaithi'];
            return redirect($cartPaths[app()->getLocale()] ?? '/vi/gio-hang')->with('error', 'Giỏ hàng đang trống.');
        }

        $subtotal = (int) $items->sum('line_total');
        $branch = Branch::query()->active()->with('deliveryZones')->findOrFail($request->input('branch_id'));

        $openingHours = OpeningHours::fromBranch($branch);
        $checkoutDate = $this->checkoutOperatingDate($branch, $openingHours)->toDateString();

        $rules = [
            'branch_id' => ['required', 'exists:branches,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'fulfillment_method' => ['required', 'string'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_place_id' => ['nullable', 'string', 'max:255'],
            'delivery_distance_km' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'delivery_address_final' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(fn () => ($request->input('fulfillment_method') ?? '') === 'delivery'),
            ],
            'delivery_quote_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_quote_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_quote_place_id' => ['nullable', 'string', 'max:255'],
            'proceed_without_quote' => ['nullable', 'string', 'in:0,1'],
            'voucher_code' => ['nullable', 'string', 'max:50'],
            'requested_date' => ['nullable', 'date', 'date_equals:'.$checkoutDate],
            'requested_time' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];

        $allowedPayments = $branch->allowsOfflinePayment() ? ['offline', 'viva'] : ['viva'];
        $rules['payment_method'] = ['nullable', Rule::in($allowedPayments)];

        $data = $request->validate($rules, $this->validationMessages());
        $data['payment_method'] ??= $branch->allowsOfflinePayment() ? 'offline' : 'viva';
        $submittedRequestedDate = $data['requested_date'] ?? $checkoutDate;

        $checkoutWindowError = $this->checkoutWindowError($branch, $data['requested_time'] ?? null, $submittedRequestedDate, $openingHours);
        if ($checkoutWindowError) {
            throw ValidationException::withMessages([
                'requested_time' => $checkoutWindowError,
            ]);
        }

        // Enforce dish time-slot availability at checkout time.
        // Rule: requested_time (if provided) decides availability; otherwise treat as ASAP (now).
        $effectiveAt = null;
        if (! empty($data['requested_time'])) {
            $effectiveAt = $openingHours->scheduledAt($checkoutDate, $data['requested_time'], $branch);
            $data['requested_date'] = $effectiveAt->toDateString();
        } else {
            $data['requested_date'] = business_now($branch)->toDateString();
        }

        $unavailableItems = $this->unavailableCartItems($items, $branch, $effectiveAt, $availability);
        if ($unavailableItems !== []) {
            throw ValidationException::withMessages([
                'cart' => $this->unavailableTimeSlotMessage($unavailableItems),
            ]);
        }

        $allowedMethods = [];
        if ($branch->accepts_pickup_orders)  $allowedMethods[] = 'pickup';
        if ($branch->accepts_delivery_orders) $allowedMethods[] = 'delivery';
        $allowedMethods = $allowedMethods ?: ['delivery'];

        if (! in_array($data['fulfillment_method'], $allowedMethods, true)) {
            return back()->withInput()->withErrors([
                'fulfillment_method' => __('site.delivery_quote.invalid_method'),
            ]);
        }

        if ($data['fulfillment_method'] === 'delivery'
            && $branch->delivery_min_order_amount
            && $branch->delivery_min_order_amount > 0
            && $subtotal < $branch->delivery_min_order_amount
        ) {
            return back()->withInput()->withErrors([
                'min_order' => __('site.checkout.min_order_error', [
                    'amount' => format_money($branch->delivery_min_order_amount),
                ]),
            ]);
        }

        $route = null;
        $distanceKm = null;

        // Determine actual delivery address: final input > main input
        $deliveryAddress = $data['delivery_address_final'] ?? $data['delivery_address'] ?? null;

        if ($data['fulfillment_method'] === 'delivery') {
            if ($branch->auto_delivery_quote_enabled) {
                // If the customer explicitly chose to proceed without an automatic shipping quote,
                // skip route calculation and create a manual pending quote instead.
                if (! empty($data['proceed_without_quote'])) {
                    $quote = DeliveryQuote::manualUnavailable();

                    return $this->createOrderWithManualQuote(
                        $branch, $data, $items, $subtotal, $quote, $route, $deliveryAddress,
                        $cart, $viva, $vouchers,
                    );
                }

                // Prefer _quote_* fields (fee-confirmed address); fall back to main fields
                $calcLatitude  = $this->nullableFloat($data['delivery_quote_latitude']  ?? $data['delivery_latitude']  ?? null);
                $calcLongitude = $this->nullableFloat($data['delivery_quote_longitude'] ?? $data['delivery_longitude'] ?? null);
                $calcPlaceId  = $data['delivery_quote_place_id']   ?? $data['delivery_place_id']   ?? null;

                try {
                    $route = $this->deliveryDistances->routeFromBranch(
                        $branch,
                        $deliveryAddress,
                        $calcLatitude,
                        $calcLongitude,
                        $calcPlaceId,
                    );
                    $distanceKm = $route->distanceKm;
                } catch (\Throwable $exception) {
                    report($exception);

                    throw ValidationException::withMessages([
                        'delivery_address' => $this->deliveryQuoteFailureMessage(),
                    ]);
                }
            } else {
                $distanceKm = $this->nullableFloat($data['delivery_distance_km'] ?? null);
            }
        }

        $quote = $this->deliveryQuotes->quote(
            $branch,
            $data['fulfillment_method'],
            $subtotal,
            $distanceKm
        );

        if (! $quote->available) {
            return back()
                ->withInput()
                ->withErrors(['fulfillment_method' => $quote->message ?: 'Cơ sở này chưa thể nhận đơn theo hình thức đã chọn.']);
        }

        $shippingFee = $quote->manualFee ? 0 : $quote->fee;
        $voucherCode = $vouchers->normalizeCode($data['voucher_code'] ?? session(VoucherService::SESSION_KEY));
        $voucher = $voucherCode ? $vouchers->findByCodeOrId($voucherCode) : null;
        $voucherQuote = $voucher
            ? $vouchers->quote($voucher, $subtotal, $shippingFee, $data['fulfillment_method'], $branch, $data['customer_email'] ?? null, $data['customer_phone'] ?? null, true)
            : null;

        if ($voucherCode && (! $voucherQuote || ! $voucherQuote->valid)) {
            return back()->withInput()->withErrors([
                'voucher_code' => $voucherQuote?->message ?: __('site.voucher.not_found'),
            ]);
        }

        $discountTotal = $voucherQuote?->discountTotal ?? 0;
        $total = max(0, $subtotal + $shippingFee - $discountTotal);

        $order = DB::transaction(function () use ($branch, $data, $items, $subtotal, $shippingFee, $discountTotal, $total, $quote, $route, $deliveryAddress, $voucherQuote): Order {
            $order = Order::create([
                'code' => $this->generateOrderCode($branch),
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
                'locale' => app()->getLocale(),
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount_total' => $discountTotal,
                'voucher_id' => $voucherQuote?->voucher?->id,
                'voucher_code' => $voucherQuote?->voucher?->code,
                'voucher_snapshot' => $voucherQuote?->snapshot(),
                'total' => $total,
                'delivery_address' => $deliveryAddress,
                'delivery_latitude' => $route?->latitude ?? $data['delivery_latitude'] ?? null,
                'delivery_longitude' => $route?->longitude ?? $data['delivery_longitude'] ?? null,
                'delivery_place_id' => $this->placeIdForStorage($route?->placeId ?? $data['delivery_place_id'] ?? null),
                'delivery_distance_km' => $quote->distanceKm,
                'delivery_zone_label' => $quote->zoneLabel,
                'delivery_quote_source' => $quote->source,
                'delivery_fee_overridden' => false,
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
                    'address' => $deliveryAddress,
                    'latitude' => $data['delivery_latitude'] ?? null,
                    'longitude' => $data['delivery_longitude'] ?? null,
                    'place_id' => $this->placeIdForStorage($route?->placeId ?? $data['delivery_place_id'] ?? null),
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

        if ($voucherQuote?->valid) {
            $vouchers->redeem($order, $voucherQuote);
            session()->forget(VoucherService::SESSION_KEY);
        }

        $order->load(['items', 'branch', 'shipment', 'payments']);

        if ($data['payment_method'] !== 'viva') {
        // 1. Gửi email xác nhận cho khách
        if ($order->customer_email && filter_var($order->customer_email, FILTER_VALIDATE_EMAIL)) {
            try {
                \Illuminate\Support\Facades\Mail::to($order->customer_email)
                    ->queue((new \App\Mail\CustomerOrderConfirmationMail($order))
                        ->locale($order->locale ?: app()->getLocale())
                    );
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::error('[CustomerOrderConfirmationMail] Failed to send customer confirmation', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'customer_email' => $order->customer_email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        // 2. Gửi email thông báo cho admin
        $notifyEmail = $branch->notificationEmail();

        if ($notifyEmail) {
            try {
                \Illuminate\Support\Facades\Mail::to($notifyEmail)->queue(
                    (new \App\Mail\NewOrderNotificationMail($order))
                        ->locale($order->locale ?: app()->getLocale())
                );
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::error('[NewOrderNotificationMail] Failed to send order notification', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'notify_email' => $notifyEmail,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        }

        if ($data['payment_method'] === 'viva') {
            try {
                // When shipping fee is pending (customer chose "shop will call"), charge only items subtotal.
                // Shipping will be handled manually/offline later.
                $chargeOrder = $quote->manualFee ? $order->forceFill(['total' => max(0, $order->subtotal - $order->discount_total)]) : $order;

                $vivaOrder = $viva->createPaymentOrder($chargeOrder);

                $payment = $order->payments()->where('method', 'viva')->latest()->first();
                $payment?->update([
                    'provider' => 'viva',
                    'reference' => $vivaOrder['order_code'],
                    'amount' => $chargeOrder->total,
                    'payload' => $vivaOrder['payload'] + [
                        'checkout_url' => $vivaOrder['checkout_url'],
                        'viva_environment' => config('services.viva.environment'),
                    ],
                ]);

                \Illuminate\Support\Facades\Log::info('Viva checkout order created.', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'payment_id' => $payment?->id,
                    'viva_order_code' => $vivaOrder['order_code'],
                    'amount' => $chargeOrder->total,
                    'source_code' => config('services.viva.source_code'),
                    'checkout_url' => $vivaOrder['checkout_url'],
                    'x_viva_correlation_id' => $vivaOrder['payload']['x_viva_correlation_id'] ?? null,
                    'environment' => config('services.viva.environment'),
                ]);

                if ($payment) {
                    app(PendingVivaPayment::class)->remember($order, $payment->fresh());
                }
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

        $locale = app()->getLocale();
        $successPaths = [
            'vi' => "/vi/dat-hang/thanh-cong/{$order->code}",
            'en' => "/en/checkout/success/{$order->code}",
            'el' => "/el/tameio/epitychia/{$order->code}",
        ];

        return redirect($successPaths[$locale] ?? $successPaths['vi'])
            ->with('success', 'Đã gửi đơn hàng. Quán sẽ liên hệ xác nhận sớm.');
    }

    public function success(Order $order): View
    {
        $order->load(['items', 'branch', 'shipment', 'invoice', 'payments']);

        return view('storefront.checkout.success', compact('order'));
    }

    private function checkoutWindowError(Branch $branch, ?string $requestedTime = null, ?string $requestedDate = null, ?OpeningHours $openingHours = null): ?string
    {
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

    private function unavailableCartItems(mixed $items, Branch $branch, ?Carbon $effectiveAt, DishAvailabilityService $availability): array
    {
        $unavailable = [];

        foreach ($items as $item) {
            $dish = $item['dish'];
            $result = $availability->at($dish, $branch, $effectiveAt);

            if ($result->available) {
                continue;
            }

            $name = $dish->localized('name');
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

    private function quotePayload(DeliveryQuote $quote, int $subtotal, ?DeliveryRoute $route = null): array
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

    private function deliveryQuoteFailureMessage(): string
    {
        return __('site.delivery_quote.calculating_failed');
    }

    private function validationMessages(): array
    {
        return [
            'delivery_address_final.required_if' => __('site.checkout.address_required_when_delivery'),
            'fulfillment_method.in' => __('site.delivery_quote.invalid_method'),
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

    /**
     * Create a delivery order when the customer explicitly chose to proceed
     * without an automatic shipping quote (e.g. Geoapify could not geocode the address).
     * The shipping fee is set to 0 and marked as manual/pending.
     */
    private function createOrderWithManualQuote(
        Branch $branch,
        array $data,
        \Illuminate\Support\Collection $items,
        int $subtotal,
        \App\Support\DeliveryQuote $quote,
        mixed $route,
        ?string $deliveryAddress,
        CartService $cart,
        VivaGateway $viva,
        VoucherService $vouchers,
    ): RedirectResponse {
        $shippingFee = 0;
        $voucherCode = $vouchers->normalizeCode($data['voucher_code'] ?? session(VoucherService::SESSION_KEY));
        $voucher = $voucherCode ? $vouchers->findByCodeOrId($voucherCode) : null;
        $voucherQuote = $voucher
            ? $vouchers->quote($voucher, $subtotal, $shippingFee, $data['fulfillment_method'], $branch, $data['customer_email'] ?? null, $data['customer_phone'] ?? null, true)
            : null;

        if ($voucherCode && (! $voucherQuote || ! $voucherQuote->valid)) {
            return back()->withInput()->withErrors([
                'voucher_code' => $voucherQuote?->message ?: __('site.voucher.not_found'),
            ]);
        }

        $discountTotal = $voucherQuote?->discountTotal ?? 0;
        $total = max(0, $subtotal + $shippingFee - $discountTotal);

        $order = DB::transaction(function () use ($branch, $data, $items, $subtotal, $shippingFee, $discountTotal, $total, $quote, $route, $deliveryAddress, $voucherQuote): Order {
            $order = Order::create([
                'code' => $this->generateOrderCode($branch),
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
                'locale' => app()->getLocale(),
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount_total' => $discountTotal,
                'voucher_id' => $voucherQuote?->voucher?->id,
                'voucher_code' => $voucherQuote?->voucher?->code,
                'voucher_snapshot' => $voucherQuote?->snapshot(),
                'total' => $total,
                'delivery_address' => $deliveryAddress,
                'delivery_latitude' => $route?->latitude ?? $data['delivery_latitude'] ?? null,
                'delivery_longitude' => $route?->longitude ?? $data['delivery_longitude'] ?? null,
                'delivery_place_id' => $this->placeIdForStorage($route?->placeId ?? $data['delivery_place_id'] ?? null),
                'delivery_distance_km' => $quote->distanceKm,
                'delivery_zone_label' => $quote->zoneLabel,
                'delivery_quote_source' => $quote->source,
                'delivery_fee_overridden' => true,
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
                    'address' => $deliveryAddress,
                    'latitude' => $data['delivery_latitude'] ?? null,
                    'longitude' => $data['delivery_longitude'] ?? null,
                    'place_id' => $this->placeIdForStorage($route?->placeId ?? $data['delivery_place_id'] ?? null),
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
                    ? 'Manual shipping fee — order created without auto-quote (customer proceeded).'
                    : null,
            ]);

            return $order;
        });

        if ($voucherQuote?->valid) {
            $vouchers->redeem($order, $voucherQuote);
            session()->forget(VoucherService::SESSION_KEY);
        }

        if ($data['payment_method'] !== 'viva') {
        // Gửi email xác nhận cho khách
        if ($order->customer_email && filter_var($order->customer_email, FILTER_VALIDATE_EMAIL)) {
            try {
                \Illuminate\Support\Facades\Mail::to($order->customer_email)
                    ->queue((new \App\Mail\CustomerOrderConfirmationMail($order))
                        ->locale($order->locale ?: app()->getLocale())
                    );
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::error('[CustomerOrderConfirmationMail] Failed to send (manual quote flow)', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'customer_email' => $order->customer_email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        // Gửi email thông báo cho admin
        $notifyEmail = $branch->notificationEmail();
        if ($notifyEmail) {
            try {
                \Illuminate\Support\Facades\Mail::to($notifyEmail)->queue(
                    (new \App\Mail\NewOrderNotificationMail($order))
                        ->locale($order->locale ?: app()->getLocale())
                );
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::error('[NewOrderNotificationMail] Failed to send (manual quote flow)', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'notify_email' => $notifyEmail,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        }

        // For Viva, we still need to create the payment order
        if ($data['payment_method'] === 'viva') {
            try {
                $vivaOrder = $viva->createPaymentOrder($order);

                $payment = $order->payments()->where('method', 'viva')->latest()->first();
                $payment?->update([
                    'provider' => 'viva',
                    'reference' => $vivaOrder['order_code'],
                    'payload' => $vivaOrder['payload'] + [
                        'checkout_url' => $vivaOrder['checkout_url'],
                        'viva_environment' => config('services.viva.environment'),
                    ],
                ]);

                \Illuminate\Support\Facades\Log::info('Viva checkout order created.', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'payment_id' => $payment?->id,
                    'viva_order_code' => $vivaOrder['order_code'],
                    'amount' => $order->total,
                    'source_code' => config('services.viva.source_code'),
                    'checkout_url' => $vivaOrder['checkout_url'],
                    'x_viva_correlation_id' => $vivaOrder['payload']['x_viva_correlation_id'] ?? null,
                    'environment' => config('services.viva.environment'),
                ]);

                if ($payment) {
                    app(PendingVivaPayment::class)->remember($order, $payment->fresh());
                }
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

        $locale = app()->getLocale();
        $successPaths = [
            'vi' => "/vi/dat-hang/thanh-cong/{$order->code}",
            'en' => "/en/checkout/success/{$order->code}",
            'el' => "/el/tameio/epitychia/{$order->code}",
        ];

        return redirect($successPaths[$locale] ?? $successPaths['vi'])
            ->with('success', 'Đã gửi đơn hàng. Quán sẽ liên hệ xác nhận sớm.');
    }
}
