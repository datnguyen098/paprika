<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionTranslation extends Model
{
    protected $fillable = ['promotion_id', 'locale', 'title', 'subtitle', 'description', 'badge', 'button_text'];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
