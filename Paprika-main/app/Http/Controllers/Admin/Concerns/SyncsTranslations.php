<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Support\TranslationPayload;
use Illuminate\Database\Eloquent\Model;

trait SyncsTranslations
{
    /**
     * Map of translation table => column whose value is used to derive the slug
     * when the translated slug is empty. Override in the controller if needed.
     */
    protected array $translationSlugSource = [
        'dish_translations' => 'name',
        'category_translations' => 'name',
        'post_translations' => 'title',
        'page_translations' => 'title',
    ];

    private function syncTranslations($request, Model $model): void
    {
        $translations = data_get($request->validated(), 'translations', []);

        foreach ($translations as $locale => $fields) {
            if ($locale === config('locales.default')) {
                continue;
            }
            $values = TranslationPayload::prepare($model, $locale, $fields, $this->translationSlugSource);

            if ($values === null) {
                $model->translations()->where('locale', $locale)->delete();

                continue;
            }

            $model->translations()->updateOrCreate(['locale' => $locale], $values);
        }
    }
}
