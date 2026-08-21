<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'seated', 'completed', 'no_show', 'cancelled'];

    public const ACTIVE_STATUSES = ['pending', 'confirmed', 'seated'];

    public const CLOSED_STATUSES = ['completed', 'cancelled', 'no_show'];

    public const STATUS_LABELS = [
        'pending' => 'Chờ gọi xác nhận',
        'confirmed' => 'Đã giữ bàn',
        'seated' => 'Khách đã ngồi',
        'completed' => 'Hoàn tất',
        'no_show' => 'Không đến',
        'cancelled' => 'Đã hủy',
    ];

    protected $fillable = [
        'name',
        'branch_id',
        'table_id',
        'phone',
        'email',
        'reservation_date',
        'reservation_time',
        'duration_minutes',
        'guests',
        'note',
        'status',
        'admin_note',
        'last_contacted_at',
        'confirmed_at',
        'hold_expires_at',
        'seated_at',
        'no_show_at',
        'completed_at',
        'cancelled_at',
        'contact_attempts',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
            'duration_minutes' => 'integer',
            'last_contacted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'hold_expires_at' => 'datetime',
            'seated_at' => 'datetime',
            'no_show_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'contact_attempts' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ReservationActivity::class)->latest('created_at');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function scheduledAt(): Carbon
    {
        return Carbon::parse(
            $this->reservation_date->toDateString().' '.substr((string) $this->reservation_time, 0, 5),
            business_timezone($this->branch)
        );
    }

    public function waitingMinutes(): int
    {
        if ($this->status !== 'pending') {
            return 0;
        }

        return max(0, (int) business_time($this->created_at, $this->branch)->diffInMinutes(business_now($this->branch)));
    }

    public function needsUrgentCall(): bool
    {
        return $this->status === 'pending'
            && ($this->waitingMinutes() > 30 || $this->scheduledAt()->lt(business_now($this->branch)->addMinutes(90)));
    }

    public function isDueSoon(): bool
    {
        $scheduledAt = $this->scheduledAt();

        return $this->status === 'confirmed'
            && $scheduledAt->gte(business_now($this->branch))
            && $scheduledAt->lte(business_now($this->branch)->addMinutes(90));
    }

    public function isPastServiceTime(): bool
    {
        return ! in_array($this->status, self::CLOSED_STATUSES, true)
            && $this->scheduledAt()->lt(business_now($this->branch));
    }

    public function isHoldExpired(): bool
    {
        return $this->status === 'confirmed'
            && $this->hold_expires_at
            && business_time($this->hold_expires_at, $this->branch)->lt(business_now($this->branch));
    }

    public function tableLabel(): string
    {
        if (! $this->table_id || ! ($this->table instanceof RestaurantTable)) {
            return 'Chưa xếp bàn';
        }

        return $this->table->name.' · '.$this->table->seats.' ghế';
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'confirmed' => 'confirmed',
            'seated' => 'active',
            'completed' => 'completed',
            'no_show' => 'cancelled',
            'cancelled' => 'cancelled',
            default => $this->needsUrgentCall() ? 'urgent' : 'pending',
        };
    }
}
