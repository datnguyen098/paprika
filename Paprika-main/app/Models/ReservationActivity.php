<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationActivity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'reservation_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
        'note',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return [
            'created' => 'Tạo đặt bàn',
            'contact_attempt' => 'Đã gọi khách',
            'confirmed' => 'Đã giữ bàn',
            'seated' => 'Khách đã ngồi',
            'completed' => 'Hoàn tất',
            'no_show' => 'Khách không đến',
            'cancelled' => 'Đã hủy',
            'updated' => 'Cập nhật thông tin',
        ][$this->action] ?? $this->action;
    }
}
