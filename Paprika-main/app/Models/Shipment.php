<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    public const STATUSES = ['pending', 'created', 'shipping', 'delivered', 'failed'];

    protected $fillable = [
        'order_id',
        'carrier',
        'status',
        'address',
        'latitude',
        'longitude',
        'place_id',
        'fee',
        'distance_km',
        'zone_label',
        'quote_source',
        'tracking_code',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'fee' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'distance_km' => 'decimal:2',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function setPlaceIdAttribute(mixed $value): void
    {
        $this->attributes['place_id'] = Order::normalizeExternalPlaceId($value);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
