<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    public const STATUSES = ['draft', 'issued', 'cancelled'];

    protected $fillable = [
        'order_id',
        'invoice_number',
        'status',
        'buyer_name',
        'buyer_phone',
        'buyer_email',
        'buyer_address',
        'subtotal',
        'shipping_fee',
        'discount_total',
        'tax_total',
        'total',
        'issued_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'shipping_fee' => 'integer',
            'discount_total' => 'integer',
            'tax_total' => 'integer',
            'total' => 'integer',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
