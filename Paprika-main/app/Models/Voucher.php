<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Voucher extends Model
{
    use HasFactory;
    use HasLocalizedContent;

    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';
    public const TYPE_FREE_SHIPPING = 'free_shipping';

    public const TYPES = [
        self::TYPE_PERCENT,
        self::TYPE_FIXED,
        self::TYPE_FREE_SHIPPING,
    ];

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'starts_at',
        'ends_at',
        'usage_limit_total',
        'usage_limit_per_customer',
        'used_count',
        'is_active',
        'is_public',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'branch_id' => 'integer',
            'discount_value' => 'integer',
            'max_discount_amount' => 'integer',
            'min_order_amount' => 'integer',
            'usage_limit_total' => 'integer',
            'usage_limit_per_customer' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Voucher $voucher): void {
            $voucher->code = Str::upper(trim((string) $voucher->code));
        });

        static::saved(function (Voucher $voucher): void {
            if (! $voucher->is_default) {
                return;
            }

            static::query()
                ->whereKeyNot($voucher->getKey())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->active()
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        return $query->where(function (Builder $query) use ($branchId): void {
            $query->whereNull('branch_id');

            if ($branchId) {
                $query->orWhere('branch_id', $branchId);
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(VoucherTranslation::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    public function typeLabel(): string
    {
        return __('site.voucher.types.'.$this->discount_type);
    }

    public function displayValue(): string
    {
        return match ($this->discount_type) {
            self::TYPE_PERCENT => rtrim(rtrim(number_format($this->discount_value / 100, 2, ',', '.'), '0'), ',').'%',
            self::TYPE_FIXED => format_money($this->discount_value),
            self::TYPE_FREE_SHIPPING => __('site.voucher.free_shipping_short'),
            default => (string) $this->discount_value,
        };
    }
}
