<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    protected static function booted(): void
    {
        static::saved(fn (): bool => Cache::forget('site_settings'));
        static::deleted(fn (): bool => Cache::forget('site_settings'));
    }

    public static function set(string $key, mixed $value, string $type = 'text', ?string $group = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );
    }
}
