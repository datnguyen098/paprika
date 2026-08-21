<?php

namespace App\Support;

use App\Models\Voucher;

class VoucherQuote
{
    public function __construct(
        public readonly ?Voucher $voucher,
        public readonly bool $valid,
        public readonly int $discountTotal,
        public readonly int $subtotal,
        public readonly int $shippingFee,
        public readonly int $total,
        public readonly ?string $message = null,
    ) {}

    public function snapshot(): ?array
    {
        if (! $this->voucher || ! $this->valid) {
            return null;
        }

        return [
            'id' => $this->voucher->id,
            'code' => $this->voucher->code,
            'name' => $this->voucher->name,
            'localized_name' => $this->voucher->localized('name'),
            'discount_type' => $this->voucher->discount_type,
            'discount_value' => $this->voucher->discount_value,
            'max_discount_amount' => $this->voucher->max_discount_amount,
            'min_order_amount' => $this->voucher->min_order_amount,
            'discount_total' => $this->discountTotal,
        ];
    }

    public function toPayload(): array
    {
        return [
            'valid' => $this->valid,
            'message' => $this->message,
            'voucher' => $this->voucher ? [
                'id' => $this->voucher->id,
                'code' => $this->voucher->code,
                'name' => $this->voucher->localized('name'),
                'description' => $this->voucher->localized('description'),
                'type' => $this->voucher->discount_type,
                'value_label' => $this->voucher->displayValue(),
            ] : null,
            'discount_total' => $this->discountTotal,
            'discount_total_formatted' => format_money($this->discountTotal),
            'subtotal' => $this->subtotal,
            'shipping_fee' => $this->shippingFee,
            'total' => $this->total,
            'total_formatted' => format_money($this->total),
        ];
    }
}
