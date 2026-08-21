<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannerTranslation extends Model
{
    protected $fillable = ['banner_id', 'locale', 'title', 'subtitle', 'description', 'button_text'];

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }
}
