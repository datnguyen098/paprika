<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'paid', 'failed', 'refunded', 'duplicate'];

    public const STATUS_LABELS = [
        'pending' => 'Chờ thanh toán',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thất bại',
        'refunded' => 'Đã hoàn tiền',
    ];

    public const METHOD_LABELS = [
        'offline' => 'Thanh toán offline',
        'viva' => 'Viva Wallet',
        'vnpay' => 'VNPAY',
        'momo' => 'MoMo',
    ];

    protected $fillable = [
        'order_id',
        'method',
        'provider',
        'status',
        'amount',
        'currency',
        'transaction_code',
        'reference',
        'payload',
        'paid_at',
        'failed_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function statusLabel(): string
    {
        if ($this->status === 'duplicate') {
            return 'Thanh toán trùng';
        }

        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function methodLabel(): string
    {
        return self::METHOD_LABELS[$this->method] ?? $this->method;
    }
}
