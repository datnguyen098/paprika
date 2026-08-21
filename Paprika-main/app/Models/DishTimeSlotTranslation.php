<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishTimeSlotTranslation extends Model
{
    protected $fillable = [
        'dish_time_slot_id',
        'locale',
        'name',
    ];

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(DishTimeSlot::class, 'dish_time_slot_id');
    }
}
