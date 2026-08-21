<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait GeneratesSlugs
{
    protected static function bootGeneratesSlugs(): void
    {
        static::saving(function (Model $model): void {
            $sourceColumn = $model->getSlugSourceColumn();

            if (blank($model->slug) && filled($model->{$sourceColumn})) {
                $model->slug = static::makeUniqueSlug($model, $model->{$sourceColumn});
            }
        });
    }

    protected function getSlugSourceColumn(): string
    {
        return property_exists($this, 'slugSource') ? $this->slugSource : 'name';
    }

    protected static function makeUniqueSlug(Model $model, string $value): string
    {
        $baseSlug = Str::slug($value) ?: Str::random(8);
        $slug = $baseSlug;
        $suffix = 2;

        while (static::slugExists($model, $slug)) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected static function slugExists(Model $model, string $slug): bool
    {
        $query = $model->newQuery()->where('slug', $slug);

        if ($model->exists) {
            $query->whereKeyNot($model->getKey());
        }

        return $query->exists();
    }
}
