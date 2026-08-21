<?php

namespace App\Models;

use App\Support\GeneratesSlugs;
use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use GeneratesSlugs;
    use HasFactory;
    use HasLocalizedContent;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'image',
        'sort_order',
        'is_active',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function scopeDish(Builder $query): Builder
    {
        return $query->where('type', 'dish');
    }

    public function scopePost(Builder $query): Builder
    {
        return $query->where('type', 'post');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeMenu(Builder $query): Builder
    {
        return $this->scopeDish($query);
    }

    public function scopeBlog(Builder $query): Builder
    {
        return $this->scopePost($query);
    }
}
