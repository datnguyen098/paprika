<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasLocalizedContent
{
    public function translation(?string $locale = null): ?Model
    {
        $locale = $locale ?: current_locale();

        if ($locale === config('locales.default', 'vi')) {
            return null;
        }

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale);
        }

        return $this->translations()->where('locale', $locale)->first();
    }

    public function localized(string $field, mixed $default = null, ?string $locale = null): mixed
    {
        $translation = $this->translation($locale);
        $translatedValue = $translation?->{$field};

        if (! blank($translatedValue)) {
            return $translatedValue;
        }

        return $this->getAttribute($field) ?? $default;
    }

    public function localizedSlug(?string $locale = null): string
    {
        return (string) $this->localized('slug', $this->getAttribute('slug'), $locale);
    }

    abstract public function translations(): HasMany;
}
