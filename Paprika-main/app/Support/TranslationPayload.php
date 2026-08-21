<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TranslationPayload
{
    private const REQUIRED_COLUMNS = [
        'banner_translations' => 'title',
        'category_translations' => 'name',
        'dish_translations' => 'name',
        'gallery_image_translations' => 'title',
        'navigation_menu_translations' => 'title',
        'page_translations' => 'title',
        'post_translations' => 'title',
        'promotion_translations' => 'title',
        'testimonial_translations' => 'content',
        'voucher_translations' => 'name',
    ];

    public static function prepare(Model $model, string $locale, array $fields, array $slugSources = []): ?array
    {
        $values = collect($fields)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();

        $hasMeaningfulContent = collect($values)
            ->except(['slug'])
            ->contains(fn ($value): bool => filled($value));

        if (! $hasMeaningfulContent) {
            return null;
        }

        $translationModel = $model->translations()->getRelated();
        $table = $translationModel->getTable();
        $requiredColumn = self::REQUIRED_COLUMNS[$table] ?? null;

        if ($requiredColumn && array_key_exists($requiredColumn, $values) && blank($values[$requiredColumn])) {
            $values[$requiredColumn] = $model->{$requiredColumn} ?? null;
        }

        if ($requiredColumn && array_key_exists($requiredColumn, $values) && blank($values[$requiredColumn])) {
            return null;
        }

        if (array_key_exists('slug', $values)) {
            $values = self::ensureSlug($model, $locale, $values, $slugSources);
        }

        return $values;
    }

    private static function ensureSlug(Model $model, string $locale, array $values, array $slugSources): array
    {
        if (filled($values['slug'])) {
            $base = Str::slug($values['slug']) ?: $values['slug'];
            $values['slug'] = self::makeUniqueSlug($model, $locale, $base);

            return $values;
        }

        $translationModel = $model->translations()->getRelated();
        $table = $translationModel->getTable();
        $sourceColumn = $slugSources[$table] ?? 'name';
        $sourceValue = $values[$sourceColumn] ?? $model->{$sourceColumn} ?? null;
        $base = filled($sourceValue) ? Str::slug($sourceValue) : $locale.'-'.Str::random(8);

        $values['slug'] = self::makeUniqueSlug($model, $locale, $base);

        return $values;
    }

    private static function makeUniqueSlug(Model $model, string $locale, string $base): string
    {
        $translationModel = $model->translations()->getRelated();
        $foreignKey = $model->translations()->getForeignKeyName();
        $parentId = $model->getKey();

        $slug = $base;
        $suffix = 2;

        while (
            $translationModel->newQuery()
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->when($parentId, fn ($query) => $query->where($foreignKey, '!=', $parentId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
