<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishOptionGroupTranslation extends Model
{
    protected $fillable = [
        'dish_option_group_id',
        'locale',
        'name',
        'description',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(DishOptionGroup::class, 'dish_option_group_id');
    }
}
