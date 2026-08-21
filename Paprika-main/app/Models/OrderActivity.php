<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderActivity extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
        'note',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'created' => 'Khách tạo đơn',
            'updated' => 'Cập nhật thủ công',
            'confirmed' => 'Xác nhận đơn',
            'preparing' => 'Bắt đầu chế biến',
            'ready' => 'Sẵn sàng giao/nhận',
            'shipping' => 'Đang giao hàng',
            'completed' => 'Hoàn tất đơn',
            'cancelled' => 'Hủy đơn',
            'viva_payment_paid' => 'Viva xác nhận thanh toán',
            'viva_payment_failed' => 'Viva báo thanh toán thất bại',
            'viva_payment_pending' => 'Viva chưa đủ điều kiện xác nhận',
            'viva_payment_duplicate' => 'Viva thanh toán trùng',
            default => $this->action,
        };
    }
}
