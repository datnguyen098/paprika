<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherService
{
    public const SESSION_KEY = 'checkout.voucher_code';

    public function publicVouchers(?int $branchId = null): EloquentCollection
    {
        return Voucher::query()
            ->with(['translations', 'branch'])
            ->current()
            ->where('is_public', true)
            ->when($branchId !== null, fn ($query) => $query->forBranch($branchId))
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function defaultVoucher(?int $branchId = null): ?Voucher
    {
        return Voucher::query()
            ->with(['translations', 'branch'])
            ->current()
            ->where('is_default', true)
            ->forBranch($branchId)
            ->orderBy('sort_order')
            ->first();
    }

    public function findByCodeOrId(?string $code = null, ?int $id = null): ?Voucher
    {
        $normalizedCode = $this->normalizeCode($code);

        if (! $id && ! $normalizedCode) {
            return null;
        }

        return Voucher::query()
            ->with(['translations', 'branch'])
            ->when($id, fn ($query) => $query->whereKey($id))
            ->when(! $id, fn ($query) => $query->where('code', $normalizedCode))
            ->first();
    }

    public function quote(
        ?Voucher $voucher,
        int $subtotal,
        int $shippingFee,
        string $fulfillmentMethod,
        ?Branch $branch = null,
        ?string $customerEmail = null,
        ?string $customerPhone = null,
        bool $checkCustomerUsage = false,
    ): VoucherQuote {
        $baseTotal = max(0, $subtotal + $shippingFee);

        if (! $voucher) {
            return new VoucherQuote(null, false, 0, $subtotal, $shippingFee, $baseTotal, __('site.voucher.not_found'));
        }

        $message = $this->invalidReason($voucher, $subtotal, $shippingFee, $fulfillmentMethod, $branch, $customerEmail, $customerPhone, $checkCustomerUsage);
        if ($message) {
            return new VoucherQuote($voucher, false, 0, $subtotal, $shippingFee, $baseTotal, $message);
        }

        $discount = match ($voucher->discount_type) {
            Voucher::TYPE_PERCENT => (int) floor($subtotal * $voucher->discount_value / 10000),
            Voucher::TYPE_FIXED => min($subtotal, $voucher->discount_value),
            Voucher::TYPE_FREE_SHIPPING => $shippingFee,
            default => 0,
        };

        if ($voucher->discount_type === Voucher::TYPE_PERCENT && $voucher->max_discount_amount !== null) {
            $discount = min($discount, $voucher->max_discount_amount);
        }

        $discount = max(0, min($discount, $baseTotal));
        $total = max(0, $baseTotal - $discount);

        return new VoucherQuote(
            $voucher,
            $discount > 0,
            $discount,
            $subtotal,
            $shippingFee,
            $total,
            $discount > 0 ? __('site.voucher.applied', ['code' => $voucher->code]) : __('site.voucher.no_discount')
        );
    }

    public function redeem(Order $order, VoucherQuote $quote): void
    {
        if (! $quote->valid || ! $quote->voucher || $quote->discountTotal < 1) {
            return;
        }

        DB::transaction(function () use ($order, $quote): void {
            $quote->voucher->redemptions()->create([
                'order_id' => $order->id,
                'customer_key' => $this->customerKey($order->customer_email, $order->customer_phone),
                'discount_total' => $quote->discountTotal,
            ]);

            $quote->voucher->newQuery()
                ->whereKey($quote->voucher->id)
                ->increment('used_count');
        });
    }

    public function normalizeCode(?string $code): ?string
    {
        $code = trim((string) $code);

        return $code === '' ? null : Str::upper($code);
    }

    public function customerKey(?string $email, ?string $phone): ?string
    {
        if (filled($email)) {
            return 'email:'.Str::lower(trim((string) $email));
        }

        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits ? 'phone:'.$digits : null;
    }

    private function invalidReason(
        Voucher $voucher,
        int $subtotal,
        int $shippingFee,
        string $fulfillmentMethod,
        ?Branch $branch,
        ?string $customerEmail,
        ?string $customerPhone,
        bool $checkCustomerUsage,
    ): ?string {
        if (! $voucher->is_active) {
            return __('site.voucher.inactive');
        }

        if ($voucher->starts_at && $voucher->starts_at->isFuture()) {
            return __('site.voucher.not_started');
        }

        if ($voucher->ends_at && $voucher->ends_at->isPast()) {
            return __('site.voucher.expired');
        }

        if ($voucher->branch_id && $branch && (int) $voucher->branch_id !== (int) $branch->id) {
            return __('site.voucher.branch_mismatch');
        }

        if ($voucher->branch_id && ! $branch) {
            return __('site.voucher.select_branch');
        }

        if ($voucher->min_order_amount > 0 && $subtotal < $voucher->min_order_amount) {
            return __('site.voucher.min_order', ['amount' => format_money($voucher->min_order_amount)]);
        }

        if ($voucher->usage_limit_total !== null && $voucher->used_count >= $voucher->usage_limit_total) {
            return __('site.voucher.usage_limit');
        }

        if ($checkCustomerUsage && $voucher->usage_limit_per_customer !== null) {
            $customerKey = $this->customerKey($customerEmail, $customerPhone);

            if ($customerKey && $voucher->redemptions()->where('customer_key', $customerKey)->count() >= $voucher->usage_limit_per_customer) {
                return __('site.voucher.customer_usage_limit');
            }
        }

        if ($voucher->discount_type === Voucher::TYPE_FREE_SHIPPING) {
            if ($fulfillmentMethod !== 'delivery') {
                return __('site.voucher.delivery_only');
            }

            if ($shippingFee < 1) {
                return __('site.voucher.no_shipping_fee');
            }
        }

        if (! in_array($voucher->discount_type, Voucher::TYPES, true)) {
            return __('site.voucher.invalid_type');
        }

        return null;
    }
}
