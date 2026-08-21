<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'dish_id',
        'dish_name',
        'base_unit_price',
        'options_total',
        'unit_price',
        'quantity',
        'line_total',
        'options_snapshot',
        'customization_note',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'base_unit_price' => 'integer',
            'options_total' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
            'options_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }
}
