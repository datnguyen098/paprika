<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'city',
        'timezone',
        'address',
        'phone',
        'hotline',
        'email',
        'opening_hours',
        'open_days',
        'reservation_time_slots',
        'reservation_last_booking_time',
        'reservation_last_order_buffer_minutes',
        'google_map_iframe',
        'description',
        'image',
        'facebook_url',
        'zalo_url',
        'accepts_online_orders',
        'accepts_pickup_orders',
        'accepts_delivery_orders',
        'accepts_offline_payment',
        'order_notification_email',
        'auto_delivery_quote_enabled',
        'delivery_min_order_amount',
        'delivery_free_order_amount',
        'delivery_max_distance_km',
        'delivery_origin_latitude',
        'delivery_origin_longitude',
        'delivery_note',
        'is_active',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'accepts_online_orders' => 'boolean',
            'accepts_pickup_orders' => 'boolean',
            'accepts_delivery_orders' => 'boolean',
            'accepts_offline_payment' => 'boolean',
            'auto_delivery_quote_enabled' => 'boolean',
            'delivery_min_order_amount' => 'integer',
            'delivery_free_order_amount' => 'integer',
            'delivery_max_distance_km' => 'decimal:2',
            'delivery_origin_latitude' => 'decimal:7',
            'delivery_origin_longitude' => 'decimal:7',
            'sort_order' => 'integer',
            'reservation_last_order_buffer_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Branch $branch): void {
            if (blank($branch->slug)) {
                $branch->slug = Str::slug($branch->name);
            }
        });

        static::saved(fn () => Cache::forget('primary_branch_id'));
        static::deleted(fn () => Cache::forget('primary_branch_id'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function restaurantTables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(BranchDeliveryZone::class)->orderBy('sort_order')->orderBy('min_distance_km');
    }

    public function allowsOfflinePayment(): bool
    {
        return (bool) $this->accepts_offline_payment;
    }

    public function notificationEmail(): ?string
    {
        $email = $this->order_notification_email;

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        $fallback = setting('order_notification_email');

        return ($fallback && filter_var($fallback, FILTER_VALIDATE_EMAIL)) ? $fallback : null;
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(GalleryImage::class);
    }
}
