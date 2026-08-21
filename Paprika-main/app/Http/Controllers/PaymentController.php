<?php

namespace App\Http\Controllers;

use App\Models\OrderActivity;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\VivaGateway;
use App\Support\PendingVivaPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentController extends Controller
{
    public function vivaWebhookVerification(VivaGateway $gateway): JsonResponse
    {
        try {
            return response()
                ->json($gateway->webhookVerificationKey())
                ->header('Cache-Control', 'no-store');
        } catch (Throwable $exception) {
            report($exception);

            Log::error('Viva webhook verification failed.', [
                'environment' => config('services.viva.environment'),
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Viva webhook verification failed.',
                'hint' => 'Check that VIVA_ENV and the Viva Merchant ID/API key are from the same demo or live Viva account.',
            ], 502);
        }
    }

    public function vivaReturn(Request $request, VivaGateway $gateway): RedirectResponse
    {
        $payment = $this->findVivaPayment($request->query('s'));

        if (! $payment) {
            Log::warning('Viva return payment not found.', [
                'query' => $request->query(),
            ]);

            return redirect()->route('home')->with('error', 'Không tìm thấy giao dịch Viva.');
        }

        $transactionId = $request->query('t');

        if (filled($transactionId)) {
            try {
                $this->applyVivaTransaction($payment, [
                    'TransactionId' => $transactionId,
                    'OrderCode' => $request->query('s'),
                ] + $gateway->retrieveTransaction((string) $transactionId));
            } catch (Throwable $exception) {
                Log::warning('Viva return transaction retrieve failed.', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'order_code' => $payment->order->code,
                    'viva_order_code' => $request->query('s'),
                    'transaction_id' => $transactionId,
                    'message' => $exception->getMessage(),
                ]);

                $payment->update([
                    'transaction_code' => (string) $transactionId,
                ]);
            }
        }

        $locale = $payment->order->locale
            ?? (match (true) {
                str_starts_with($request->query('lang'), 'el') => 'el',
                str_starts_with($request->query('lang'), 'en') => 'en',
                default => 'vi',
            });

        app()->setLocale($locale);
        $successPaths = [
            'vi' => "/vi/dat-hang/thanh-cong/{$payment->order->code}",
            'en' => "/en/checkout/success/{$payment->order->code}",
            'el' => "/el/tameio/epitychia/{$payment->order->code}",
        ];

        return redirect($successPaths[$locale] ?? $successPaths['vi'])
            ->with($payment->fresh()->status === 'paid' ? 'success' : 'info', 'Đã quay lại từ Viva. Hệ thống sẽ cập nhật thanh toán sau khi nhận xác nhận.');
    }

    public function vivaContinue(Order $order, VivaGateway $gateway, PendingVivaPayment $pendingViva): RedirectResponse
    {
        app()->setLocale($order->locale ?: app()->getLocale());

        return $pendingViva->continuePayment($order, $gateway);
    }

    public function dismissVivaReminder(PendingVivaPayment $pendingViva): RedirectResponse
    {
        $pendingViva->hide();

        return back();
    }

    public function vivaFailure(Request $request): RedirectResponse
    {
        $payment = $this->findVivaPayment($request->query('s'));

        if (! $payment) {
            Log::warning('Viva failure payment not found.', [
                'query' => $request->query(),
            ]);

            return redirect()->route('home')->with('error', 'Không tìm thấy giao dịch Viva.');
        }

        Log::warning('Viva payment failure return received.', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'order_code' => $payment->order->code,
            'viva_order_code' => $request->query('s'),
            'transaction_id' => $request->query('t'),
            'event_id' => $request->query('eventId'),
            'amount' => $payment->amount,
            'source_code' => data_get($payment->payload, 'request.sourceCode') ?: config('services.viva.source_code'),
            'checkout_url' => data_get($payment->payload, 'checkout_url'),
            'x_viva_correlation_id' => data_get($payment->payload, 'x_viva_correlation_id'),
            'viva_environment' => data_get($payment->payload, 'viva_environment') ?: config('services.viva.environment'),
            'has_transaction_id' => filled($request->query('t')),
            'query' => $request->query(),
        ]);

        $previousPaymentStatus = $payment->status;
        $previousOrderStatus = $payment->order->status;

        $payment->update([
            'status' => 'failed',
            'transaction_code' => (string) ($request->query('t') ?: $payment->transaction_code),
            'payload' => array_merge($payment->payload ?? [], [
                'failure_return' => array_filter([
                    'OrderCode' => $request->query('s'),
                    'TransactionId' => $request->query('t'),
                    'return_payload' => $request->query(),
                ], fn (mixed $value): bool => $value !== null && $value !== ''),
            ]),
            'failed_at' => $payment->failed_at ?: now(),
        ]);

        app(PendingVivaPayment::class)->remember($payment->order, $payment->fresh());

        if ($previousPaymentStatus !== 'failed') {
            $this->logVivaActivity(
                $payment->fresh(['order']),
                'viva_payment_failed',
                $previousOrderStatus,
                $payment->order->status,
                'Viva redirect về trang thất bại trước khi thanh toán hoàn tất. Transaction: '.($request->query('t') ?: 'chưa có mã').'.'
            );
        }

        $locale = $payment->order->locale
            ?? (match (true) {
                str_starts_with($request->query('lang'), 'el') => 'el',
                str_starts_with($request->query('lang'), 'en') => 'en',
                default => 'vi',
            });

        app()->setLocale($locale);
        $successPaths = [
            'vi' => "/vi/dat-hang/thanh-cong/{$payment->order->code}",
            'en' => "/en/checkout/success/{$payment->order->code}",
            'el' => "/el/tameio/epitychia/{$payment->order->code}",
        ];

        return redirect($successPaths[$locale] ?? $successPaths['vi'])
            ->with('error', 'Thanh toán Viva chưa hoàn tất. Bạn có thể thử lại hoặc chọn thanh toán tại quán/khi nhận hàng.');
    }

    public function vivaWebhook(Request $request, VivaGateway $gateway): JsonResponse
    {
        $eventData = $request->input('EventData', []);
        $transactionId = Arr::get($eventData, 'TransactionId');
        $orderCode = Arr::get($eventData, 'OrderCode');

        Log::info('Viva webhook received.', [
            'event_type_id' => $request->input('EventTypeId'),
            'order_code' => $orderCode,
            'transaction_id' => $transactionId,
            'status_id' => $this->firstFilled($eventData, ['StatusId', 'statusId', 'status_id']),
            'response_code' => $this->firstFilled($eventData, ['ResponseCode', 'responseCode', 'response_code']),
            'response_event_id' => $this->firstFilled($eventData, ['ResponseEventId', 'responseEventId', 'response_event_id']),
        ]);

        $payment = $this->findVivaPayment($orderCode);

        if (! $payment) {
            Log::warning('Viva webhook payment not found.', [
                'event_type_id' => $request->input('EventTypeId'),
                'order_code' => $orderCode,
                'transaction_id' => $transactionId,
            ]);

            return response()->json(['ok' => true, 'message' => 'Payment not found']);
        }

        $verifiedData = $eventData;

        if (filled($transactionId)) {
            try {
                $verifiedData = array_merge($eventData, $gateway->retrieveTransaction((string) $transactionId));
            } catch (Throwable $exception) {
                report($exception);
                Log::warning('Viva transaction retrieve failed during webhook.', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'transaction_id' => $transactionId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $this->applyVivaTransaction($payment, $verifiedData + [
            'EventTypeId' => $request->input('EventTypeId'),
            'raw' => $request->all(),
        ]);
        $payment->refresh();

        Log::info('Viva webhook applied.', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'order_code' => $payment->order->code,
            'viva_order_code' => $payment->reference,
            'transaction_id' => $payment->transaction_code,
            'event_type_id' => $request->input('EventTypeId'),
            'status_id' => $this->firstFilled($verifiedData, ['StatusId', 'statusId', 'status_id']),
            'response_code' => $this->firstFilled($verifiedData, ['ResponseCode', 'responseCode', 'response_code']),
            'response_event_id' => $this->firstFilled($verifiedData, ['ResponseEventId', 'responseEventId', 'response_event_id']),
            'payment_status' => $payment->status,
            'order_payment_status' => $payment->order->fresh()->payment_status,
        ]);

        return response()->json(['ok' => true]);
    }

    private function findVivaPayment(mixed $orderCode): ?Payment
    {
        if (blank($orderCode)) {
            return null;
        }

        return Payment::query()
            ->with('order')
            ->where('method', 'viva')
            ->where('reference', (string) $orderCode)
            ->first();
    }

    private function applyVivaTransaction(Payment $payment, array $data): void
    {
        $payment->loadMissing('order');

        $statusId = (string) $this->firstFilled($data, ['StatusId', 'statusId', 'status_id'], '');
        $amount = $this->vivaAmountToMinorUnits($this->firstFilled($data, ['Amount', 'amount'], $payment->amount), (int) $payment->amount);
        $transactionId = (string) $this->firstFilled($data, ['TransactionId', 'transactionId', 'transaction_id'], $payment->transaction_code);
        $isPaid = in_array($statusId, ['F', 'C'], true) && $amount === (int) $payment->amount;
        $isFailed = in_array($statusId, ['E', 'X'], true);
        $previousPaymentStatus = $payment->getOriginal('status');
        $previousOrderStatus = $payment->order->status;
        $payload = array_replace_recursive($payment->payload ?? [], $data);

        $this->logVivaTransactionOutcome($payment, $data, $statusId, $amount, $transactionId, $isPaid, $isFailed);

        if ($isPaid) {
            $paidPayment = null;
            $paidOrder = null;
            $duplicatePayment = null;

            DB::transaction(function () use ($payment, $payload, $transactionId, &$paidPayment, &$paidOrder, &$duplicatePayment): void {
                $lockedPayment = Payment::query()
                    ->whereKey($payment->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedPayment || in_array($lockedPayment->status, ['paid', 'duplicate'], true)) {
                    return;
                }

                $order = Order::query()
                    ->whereKey($lockedPayment->order_id)
                    ->lockForUpdate()
                    ->first();

                if (! $order) {
                    return;
                }

                $existingPaidPaymentId = Payment::query()
                    ->where('order_id', $order->id)
                    ->where('method', 'viva')
                    ->where('status', 'paid')
                    ->where('id', '!=', $lockedPayment->id)
                    ->value('id');

                if ($order->payment_status === 'paid' || $existingPaidPaymentId) {
                    $duplicatePayload = array_replace_recursive($lockedPayment->payload ?? [], $payload, [
                        'duplicate_payment' => [
                            'ignored_at' => now()->toIso8601String(),
                            'reason' => $order->payment_status === 'paid' ? 'order_already_paid' : 'another_viva_payment_already_paid',
                            'paid_payment_id' => $existingPaidPaymentId,
                        ],
                    ]);

                    $updated = Payment::query()
                        ->whereKey($lockedPayment->id)
                        ->whereNotIn('status', ['paid', 'duplicate'])
                        ->update([
                            'status' => 'duplicate',
                            'transaction_code' => $transactionId ?: $lockedPayment->transaction_code,
                            'payload' => $duplicatePayload,
                        ]);

                    if ($updated === 0) {
                        return;
                    }

                    $duplicatePayment = $lockedPayment->fresh(['order']);

                    Log::warning('Viva duplicate paid transaction ignored.', [
                        'payment_id' => $lockedPayment->id,
                        'order_id' => $order->id,
                        'order_code' => $order->code,
                        'viva_order_code' => $lockedPayment->reference,
                        'transaction_id' => $transactionId ?: $lockedPayment->transaction_code,
                        'existing_paid_payment_id' => $existingPaidPaymentId,
                        'order_payment_status' => $order->payment_status,
                    ]);

                    return;
                }

                $updated = Payment::query()
                    ->whereKey($lockedPayment->id)
                    ->whereNotIn('status', ['paid', 'duplicate'])
                    ->update([
                        'status' => 'paid',
                        'transaction_code' => $transactionId ?: $lockedPayment->transaction_code,
                        'payload' => $payload,
                        'paid_at' => $lockedPayment->paid_at ?: now(),
                    ]);

                if ($updated === 0) {
                    return;
                }

                $order->update([
                    'payment_status' => 'paid',
                    'status' => $order->status === 'pending' ? 'confirmed' : $order->status,
                    'confirmed_at' => $order->confirmed_at ?: now(),
                ]);

                $paidPayment = $lockedPayment->fresh(['order']);
                $paidOrder = $order->fresh(['branch']);
            });

            if ($duplicatePayment) {
                $this->logVivaActivity(
                    $duplicatePayment,
                    'viva_payment_duplicate',
                    $previousOrderStatus,
                    $duplicatePayment->order->status,
                    'Viva sent a paid transaction after this order was already paid. Transaction: '.($transactionId ?: 'missing').'.'
                );

                return;
            }

            if (! $paidPayment || ! $paidOrder) {
                return;
            }

            app(PendingVivaPayment::class)->clear($paidOrder);

            $this->logVivaActivity(
                $paidPayment,
                'viva_payment_paid',
                $previousOrderStatus,
                $paidOrder->status,
                'Viva confirmed successful payment. Transaction: '.($transactionId ?: 'missing').'.'
            );

            $order = $paidOrder;

            if ($order->customer_email && filter_var($order->customer_email, FILTER_VALIDATE_EMAIL)) {
                try {
                    \Illuminate\Support\Facades\Mail::to($order->customer_email)
                        ->queue((new \App\Mail\CustomerPaymentConfirmedMail($order, $transactionId ?: ''))
                            ->locale($order->locale ?: app()->getLocale())
                        );
                } catch (\Throwable $exception) {
                    \Illuminate\Support\Facades\Log::error('[CustomerPaymentConfirmedMail] Failed to send on Viva paid', [
                        'order_id' => $order->id,
                        'order_code' => $order->code,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $notifyEmail = $order->branch?->notificationEmail();
            if ($notifyEmail) {
                try {
                    \Illuminate\Support\Facades\Mail::to($notifyEmail)->queue(
                        (new \App\Mail\NewOrderNotificationMail($order->loadMissing(['items', 'branch', 'shipment', 'payments'])))
                            ->locale($order->locale ?: app()->getLocale())
                    );
                } catch (\Throwable $exception) {
                    \Illuminate\Support\Facades\Log::error('[NewOrderNotificationMail] Failed to send on Viva paid', [
                        'order_id' => $order->id,
                        'order_code' => $order->code,
                        'notify_email' => $notifyEmail,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            return;
        }

        $updated = Payment::query()
            ->whereKey($payment->id)
            ->whereNotIn('status', ['paid', 'duplicate'])
            ->update([
                'status' => $isFailed ? 'failed' : 'pending',
                'transaction_code' => $transactionId ?: $payment->transaction_code,
                'payload' => $payload,
                'failed_at' => $isFailed ? ($payment->failed_at ?: now()) : $payment->failed_at,
            ]);

        // Another webhook/job already finalized this payment.
        if ($updated === 0) {
            return;
        }

        $payment->refresh();

        if ($isFailed && $previousPaymentStatus !== 'failed') {
            $this->logVivaActivity(
                $payment->fresh(['order']),
                'viva_payment_failed',
                $previousOrderStatus,
                $payment->order->status,
                'Viva báo thanh toán thất bại hoặc bị hủy. Transaction: '.($transactionId ?: 'chưa có mã').'.'
            );

            return;
        }

        $this->logVivaActivity(
            $payment->fresh(['order']),
            'viva_payment_pending',
            $previousOrderStatus,
            $payment->order->status,
            'Viva gửi dữ liệu nhưng chưa đủ điều kiện đánh dấu đã thanh toán. StatusId: '.($statusId ?: 'trống').', Amount: '.$amount.', Expected: '.$payment->amount.'.'
        );
    }

    private function firstFilled(array $data, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            $value = Arr::get($data, $key);

            if (! blank($value)) {
                return $value;
            }
        }

        return $default;
    }

    private function vivaAmountToMinorUnits(mixed $amount, int $expectedMinorAmount): int
    {
        if (! is_numeric($amount)) {
            return $expectedMinorAmount;
        }

        $numericAmount = (float) $amount;
        $asMinorUnits = (int) round($numericAmount);
        $asMajorUnits = (int) round($numericAmount * 100);

        if ($asMinorUnits === $expectedMinorAmount) {
            return $asMinorUnits;
        }

        if ($asMajorUnits === $expectedMinorAmount) {
            return $asMajorUnits;
        }

        return $asMinorUnits;
    }

    private function logVivaActivity(Payment $payment, string $action, ?string $fromStatus, ?string $toStatus, string $note): void
    {
        OrderActivity::create([
            'order_id' => $payment->order_id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
        ]);
    }

    private function logVivaTransactionOutcome(
        Payment $payment,
        array $data,
        string $statusId,
        int $amount,
        string $transactionId,
        bool $isPaid,
        bool $isFailed
    ): void {
        $context = array_filter([
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'order_code' => $payment->order?->code,
            'viva_order_code' => $payment->reference,
            'transaction_id' => $transactionId ?: null,
            'event_type_id' => $this->firstFilled($data, ['EventTypeId', 'eventTypeId', 'event_type_id', 'raw.EventTypeId']),
            'status_id' => $statusId ?: null,
            'response_code' => $this->firstFilled($data, ['ResponseCode', 'responseCode', 'response_code']),
            'response_event_id' => $this->firstFilled($data, ['ResponseEventId', 'responseEventId', 'response_event_id']),
            'event_id' => $this->firstFilled($data, ['EventId', 'eventId', 'event_id']),
            'amount' => $amount,
            'expected_amount' => (int) $payment->amount,
            'amount_matches' => $amount === (int) $payment->amount,
            'currency_code' => $this->firstFilled($data, ['CurrencyCode', 'currencyCode', 'currency_code']),
            'source_code' => $this->firstFilled($data, ['SourceCode', 'sourceCode', 'source_code'])
                ?: data_get($payment->payload, 'request.sourceCode')
                ?: config('services.viva.source_code'),
            'transaction_type_id' => $this->firstFilled($data, ['TransactionTypeId', 'transactionTypeId', 'transaction_type_id']),
            'card_type_id' => $this->firstFilled($data, ['CardTypeId', 'cardTypeId', 'card_type_id']),
            'card_country_code' => $this->firstFilled($data, ['CardCountryCode', 'cardCountryCode', 'card_country_code']),
            'card_issuing_bank' => $this->firstFilled($data, ['CardIssuingBank', 'cardIssuingBank', 'card_issuing_bank']),
            'merchant_reference' => $this->firstFilled($data, ['MerchantTrns', 'merchantTrns', 'merchant_trns']),
            'viva_environment' => data_get($payment->payload, 'viva_environment') ?: config('services.viva.environment'),
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        if ($isFailed) {
            Log::warning('Viva transaction verified as failed.', $context);

            return;
        }

        Log::info(
            $isPaid ? 'Viva transaction verified as paid.' : 'Viva transaction verified as pending.',
            $context
        );
    }
}
