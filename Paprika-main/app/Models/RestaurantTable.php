<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
    use HasFactory;

    public const STATUSES = ['active', 'inactive', 'maintenance'];

    public const STATUS_LABELS = [
        'active' => 'Đang dùng',
        'inactive' => 'Tạm ẩn',
        'maintenance' => 'Bảo trì',
    ];

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'seats',
        'zone',
        'status',
        'is_joinable',
        'sort_order',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'is_joinable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'table_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
