<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishTranslation extends Model
{
    protected $fillable = [
        'dish_id',
        'locale',
        'name',
        'slug',
        'description',
        'content',
        'ingredients',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }
}
