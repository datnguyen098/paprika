<?php

namespace App\Models;

use App\Support\GeneratesSlugs;
use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dish extends Model
{
    use GeneratesSlugs;
    use HasFactory;
    use HasLocalizedContent;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'content',
        'ingredients',
        'price',
        'sale_price',
        'image',
        'gallery',
        'is_featured',
        'is_active',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:0',
            'sale_price' => 'decimal:0',
            'gallery' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(DishTranslation::class);
    }

    public function optionGroups(): HasMany
    {
        return $this->hasMany(DishOptionGroup::class)->orderBy('sort_order')->orderBy('id');
    }

    public function timeSlots(): BelongsToMany
    {
        return $this->belongsToMany(DishTimeSlot::class, 'dish_dish_time_slot')->withTimestamps();
    }

    public function activeOptionGroups(): HasMany
    {
        return $this->optionGroups()->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        return $query->when($keyword, function (Builder $query, string $keyword): void {
            $query->where(function (Builder $query) use ($keyword): void {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhere('ingredients', 'like', "%{$keyword}%")
                    ->orWhereHas('translations', function (Builder $translationQuery) use ($keyword): void {
                        $translationQuery->where('name', 'like', "%{$keyword}%")
                            ->orWhere('description', 'like', "%{$keyword}%")
                            ->orWhere('ingredients', 'like', "%{$keyword}%");
                    });
            });
        });
    }
}
