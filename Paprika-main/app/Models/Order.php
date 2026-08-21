<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'preparing', 'ready', 'shipping', 'completed', 'cancelled'];

    public const STATUS_LABELS = [
        'pending' => 'pending',
        'confirmed' => 'confirmed',
        'preparing' => 'preparing',
        'ready' => 'ready',
        'shipping' => 'shipping',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
    ];

    public const FULFILLMENT_LABELS = [
        'pickup' => 'pickup',
        'delivery' => 'delivery',
    ];

    public const DELIVERY_PLACE_ID_MAX_LENGTH = 190;

    protected $fillable = [
        'code',
        'branch_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'fulfillment_method',
        'requested_date',
        'requested_time',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'shipping_fee',
        'discount_total',
        'voucher_id',
        'voucher_code',
        'voucher_snapshot',
        'total',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_place_id',
        'delivery_distance_km',
        'delivery_zone_label',
        'delivery_quote_source',
        'locale',
        'delivery_fee_overridden',
        'note',
        'admin_note',
        'confirmed_at',
        'preparing_at',
        'ready_at',
        'shipping_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'subtotal' => 'integer',
            'shipping_fee' => 'integer',
            'discount_total' => 'integer',
            'voucher_snapshot' => 'array',
            'total' => 'integer',
            'delivery_latitude' => 'decimal:7',
            'delivery_longitude' => 'decimal:7',
            'delivery_distance_km' => 'decimal:2',
            'delivery_fee_overridden' => 'boolean',
            'confirmed_at' => 'datetime',
            'preparing_at' => 'datetime',
            'ready_at' => 'datetime',
            'shipping_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public static function normalizeExternalPlaceId(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Str::limit((string) $value, self::DELIVERY_PLACE_ID_MAX_LENGTH, '');
    }

    public function setDeliveryPlaceIdAttribute(mixed $value): void
    {
        $this->attributes['delivery_place_id'] = self::normalizeExternalPlaceId($value);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('created_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class)->latest('created_at');
    }

    public static function statusLabelFor(string $status, ?string $locale = null): string
    {
        $key = self::STATUS_LABELS[$status] ?? $status;

        return trans('site.order_status.' . $key, [], $locale);
    }

    public function statusLabel(?string $locale = null): string
    {
        return self::statusLabelFor($this->status, $locale);
    }

    public function fulfillmentLabel(): string
    {
        $key = self::FULFILLMENT_LABELS[$this->fulfillment_method] ?? $this->fulfillment_method;
        return __('site.fulfillment.' . $key);
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'confirmed', 'preparing', 'ready', 'shipping' => 'confirmed',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
