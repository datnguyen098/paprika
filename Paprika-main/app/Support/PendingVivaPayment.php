<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\Payment;
use App\Services\Payments\VivaGateway;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PendingVivaPayment
{
    public const SESSION_KEY = 'pending_viva_payment';
    public const HIDDEN_SESSION_KEY = 'pending_viva_payment_hidden';
    public const REMINDER_TTL_MINUTES = 1440;
    public const CHECKOUT_REUSE_MINUTES = 30;
    private const CHECKOUT_LOCK_SECONDS = 90;
    private const CHECKOUT_LOCK_WAIT_SECONDS = 15;

    public function remember(Order $order, Payment $payment): void
    {
        session()->put(self::SESSION_KEY, [
            'order_id' => $order->id,
            'order_code' => $order->code,
            'payment_id' => $payment->id,
            'checkout_url' => data_get($payment->payload, 'checkout_url'),
            'created_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes(self::REMINDER_TTL_MINUTES)->toIso8601String(),
        ]);

        session()->forget(self::HIDDEN_SESSION_KEY);
    }

    public function clear(?Order $order = null): void
    {
        $pending = session(self::SESSION_KEY);

        if (! $order || ! is_array($pending) || (int) ($pending['order_id'] ?? 0) === (int) $order->id) {
            session()->forget([self::SESSION_KEY, self::HIDDEN_SESSION_KEY]);
        }
    }

    public function hide(): void
    {
        session()->put(self::HIDDEN_SESSION_KEY, true);
    }

    public function reminder(): ?array
    {
        if (session(self::HIDDEN_SESSION_KEY)) {
            return null;
        }

        $pending = session(self::SESSION_KEY);

        if (! is_array($pending) || blank($pending['order_id'] ?? null) || blank($pending['expires_at'] ?? null)) {
            $this->clear();

            return null;
        }

        if (now()->greaterThanOrEqualTo(Carbon::parse((string) $pending['expires_at']))) {
            $this->clear();

            return null;
        }

        $order = Order::query()
            ->with('payments')
            ->find((int) $pending['order_id']);

        if (! $order || ! $this->canStartNewCheckout($order)) {
            $this->clear($order);

            return null;
        }

        $payment = $this->latestVivaPayment($order);

        return [
            'order' => $order,
            'payment' => $payment,
            'order_code' => $order->code,
            'continue_url' => localized_route('payments.viva.continue', ['order' => $order->code]),
            'tracking_url' => localized_route('order.track', ['order' => $order->code]),
            'expires_at' => $pending['expires_at'],
            'reuse_existing_checkout' => $payment ? $this->canReuseCheckout($payment) : false,
        ];
    }

    public function continuePayment(Order $order, VivaGateway $viva): RedirectResponse
    {
        $order->loadMissing('payments');
        $redirect = fn () => redirect()->to(localized_route('order.track', ['order' => $order->code]));

        if (! $this->canStartNewCheckout($order)) {
            $this->clear($order);

            return $this->blockedRedirect($redirect, $order);
        }

        $payment = $this->latestVivaPayment($order);

        if ($payment && $this->canReuseCheckout($payment)) {
            $this->remember($order, $payment);

            return redirect()->away((string) data_get($payment->payload, 'checkout_url'));
        }

        return $this->createNewCheckout($order, $viva);
    }

    public function reusableCheckoutPayment(Order $order): ?Payment
    {
        $order->loadMissing('payments');

        if (! $this->canStartNewCheckout($order)) {
            return null;
        }

        $payment = $this->latestVivaPayment($order);

        return $payment && $this->canReuseCheckout($payment) ? $payment : null;
    }

    public function createNewCheckout(Order $order, VivaGateway $viva): RedirectResponse
    {
        $redirect = fn () => redirect()->to(localized_route('order.track', ['order' => $order->code]));

        try {
            return Cache::lock($this->checkoutLockKey($order), self::CHECKOUT_LOCK_SECONDS)
                ->block(self::CHECKOUT_LOCK_WAIT_SECONDS, function () use ($order, $viva, $redirect): RedirectResponse {
                    $freshOrder = Order::query()
                        ->with('payments')
                        ->find($order->id);

                    if (! $freshOrder || ! $this->canStartNewCheckout($freshOrder)) {
                        $this->clear($freshOrder);

                        return $this->blockedRedirect($redirect, $freshOrder ?: $order);
                    }

                    $latestVivaPayment = $this->latestVivaPayment($freshOrder);

                    if ($latestVivaPayment && $this->canReuseCheckout($latestVivaPayment)) {
                        $this->remember($freshOrder, $latestVivaPayment);

                        return redirect()->away((string) data_get($latestVivaPayment->payload, 'checkout_url'));
                    }

                    return $this->createNewCheckoutAfterLock($freshOrder, $latestVivaPayment, $viva, $redirect);
                });
        } catch (LockTimeoutException $exception) {
            report($exception);

            Log::warning('Viva checkout retry lock timed out.', [
                'order_id' => $order->id,
                'order_code' => $order->code,
            ]);

            return $redirect()->with('error', __('site.checkout_success.retry_failed'));
        }
    }

    private function createNewCheckoutAfterLock(Order $order, ?Payment $latestVivaPayment, VivaGateway $viva, \Closure $redirect): RedirectResponse
    {
        $amount = (int) ($latestVivaPayment?->amount ?: $order->total);
        $chargeOrder = $order->replicate()->forceFill(['total' => $amount]);

        try {
            $vivaOrder = $viva->createPaymentOrder($chargeOrder);

            $payment = $order->payments()->create([
                'method' => 'viva',
                'provider' => 'viva',
                'status' => 'pending',
                'amount' => $amount,
                'currency' => 'EUR',
                'reference' => $vivaOrder['order_code'],
                'payload' => $vivaOrder['payload'] + [
                    'checkout_url' => $vivaOrder['checkout_url'],
                    'retry_for_payment_id' => $latestVivaPayment?->id,
                    'viva_environment' => config('services.viva.environment'),
                ],
            ]);

            $this->remember($order, $payment);

            OrderActivity::create([
                'order_id' => $order->id,
                'action' => 'viva_payment_pending',
                'from_status' => $order->status,
                'to_status' => $order->status,
                'note' => 'Customer created a new Viva payment session. Viva order: '.$vivaOrder['order_code'].'.',
            ]);

            Log::info('Viva checkout retry order created.', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'payment_id' => $payment->id,
                'previous_payment_id' => $latestVivaPayment?->id,
                'viva_order_code' => $vivaOrder['order_code'],
                'amount' => $amount,
                'source_code' => config('services.viva.source_code'),
                'checkout_url' => $vivaOrder['checkout_url'],
                'x_viva_correlation_id' => $vivaOrder['payload']['x_viva_correlation_id'] ?? null,
                'environment' => config('services.viva.environment'),
            ]);

            return redirect()->away($vivaOrder['checkout_url']);
        } catch (\Throwable $exception) {
            report($exception);

            Log::error('Viva checkout retry order creation failed.', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'latest_payment_id' => $latestVivaPayment?->id,
                'message' => $exception->getMessage(),
            ]);

            return $redirect()->with('error', __('site.checkout_success.retry_failed'));
        }
    }

    public function canStartNewCheckout(Order $order): bool
    {
        return $order->payment_method === 'viva'
            && $order->status !== 'cancelled'
            && $order->payment_status !== 'paid'
            && $order->created_at
            && $order->created_at->greaterThan(now()->copy()->subMinutes(self::REMINDER_TTL_MINUTES))
            && ! $this->hasPaidVivaPayment($order);
    }

    private function blockedRedirect(\Closure $redirect, Order $order): RedirectResponse
    {
        if ($this->alreadyPaid($order)) {
            return $redirect()->with('info', __('site.checkout_success.already_paid'));
        }

        return $redirect()->with('error', __('site.checkout_success.retry_unavailable'));
    }

    private function alreadyPaid(Order $order): bool
    {
        return $order->payment_status === 'paid'
            || $this->hasPaidVivaPayment($order);
    }

    private function hasPaidVivaPayment(Order $order): bool
    {
        if ($order->exists) {
            return $order->payments()
                ->where('method', 'viva')
                ->where('status', 'paid')
                ->exists();
        }

        return $order->payments->contains(fn (Payment $payment): bool => $payment->method === 'viva' && $payment->status === 'paid');
    }

    private function latestVivaPayment(Order $order): ?Payment
    {
        if ($order->exists) {
            return $order->payments()
                ->where('method', 'viva')
                ->latest('created_at')
                ->latest('id')
                ->first();
        }

        return $order->payments
            ->where('method', 'viva')
            ->sortByDesc(fn (Payment $payment): string => ($payment->created_at?->format('YmdHis') ?? '').str_pad((string) $payment->id, 20, '0', STR_PAD_LEFT))
            ->first();
    }

    private function canReuseCheckout(Payment $payment): bool
    {
        return $payment->status === 'pending'
            && filled(data_get($payment->payload, 'checkout_url'))
            && $payment->created_at
            && $payment->created_at->greaterThan(now()->copy()->subMinutes(self::CHECKOUT_REUSE_MINUTES));
    }

    private function checkoutLockKey(Order $order): string
    {
        return 'viva-checkout:order:'.$order->id;
    }
}
