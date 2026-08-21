<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishOptionTranslation extends Model
{
    protected $fillable = [
        'dish_option_id',
        'locale',
        'name',
        'description',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(DishOption::class, 'dish_option_id');
    }
}
