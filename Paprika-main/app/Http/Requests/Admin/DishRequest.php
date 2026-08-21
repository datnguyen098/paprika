<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dishId = $this->route('dish')?->id;

        return [
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('dishes', 'slug')->ignore($dishId)],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['required', 'string', 'max:1200'],
            'content' => ['nullable', 'string'],
            'ingredients' => ['nullable', 'string', 'max:1600'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.config('uploads.max_image_kb')],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['file', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.config('uploads.max_image_kb')],
            'remove_gallery' => ['nullable', 'array'],
            'remove_gallery.*' => ['string'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'translations' => ['nullable', 'array'],
            'translations.en' => ['nullable', 'array'],
            'translations.el' => ['nullable', 'array'],
            'translations.en.name' => ['nullable', 'string', 'max:180'],
            'translations.el.name' => ['nullable', 'string', 'max:180'],
            'translations.en.slug' => ['nullable', 'string', 'max:200'],
            'translations.el.slug' => ['nullable', 'string', 'max:200'],
            'translations.en.description' => ['nullable', 'string', 'max:1200'],
            'translations.el.description' => ['nullable', 'string', 'max:1200'],
            'translations.en.content' => ['nullable', 'string'],
            'translations.el.content' => ['nullable', 'string'],
            'translations.en.ingredients' => ['nullable', 'string', 'max:1600'],
            'translations.el.ingredients' => ['nullable', 'string', 'max:1600'],
            'translations.en.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.el.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.en.meta_description' => ['nullable', 'string', 'max:500'],
            'translations.el.meta_description' => ['nullable', 'string', 'max:500'],
            'translations.en.meta_keywords' => ['nullable', 'string', 'max:500'],
            'translations.el.meta_keywords' => ['nullable', 'string', 'max:500'],
            'option_groups' => ['nullable', 'array'],
            'option_groups.*.id' => ['nullable', 'integer', 'exists:dish_option_groups,id'],
            'option_groups.*.name' => ['nullable', 'string', 'max:120'],
            'option_groups.*.type' => ['nullable', Rule::in(['single', 'multiple', 'exclude'])],
            'option_groups.*.description' => ['nullable', 'string', 'max:500'],
            'option_groups.*.is_required' => ['nullable', 'boolean'],
            'option_groups.*.is_active' => ['nullable', 'boolean'],
            'option_groups.*.min_select' => ['nullable', 'integer', 'min:0', 'max:20'],
            'option_groups.*.max_select' => ['nullable', 'integer', 'min:0', 'max:20'],
            'option_groups.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'option_groups.*.options' => ['nullable', 'array'],
            'option_groups.*.options.*.id' => ['nullable', 'integer', 'exists:dish_options,id'],
            'option_groups.*.options.*.name' => ['nullable', 'string', 'max:120'],
            'option_groups.*.options.*.description' => ['nullable', 'string', 'max:300'],
            'option_groups.*.options.*.price_delta' => ['nullable', 'numeric', 'min:-999', 'max:999'],
            'option_groups.*.options.*.is_default' => ['nullable', 'boolean'],
            'option_groups.*.options.*.is_active' => ['nullable', 'boolean'],
            'option_groups.*.options.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'option_groups.*.translations' => ['nullable', 'array'],
            'option_groups.*.translations.en' => ['nullable', 'array'],
            'option_groups.*.translations.en.name' => ['nullable', 'string', 'max:120'],
            'option_groups.*.translations.en.description' => ['nullable', 'string', 'max:500'],
            'option_groups.*.translations.el' => ['nullable', 'array'],
            'option_groups.*.translations.el.name' => ['nullable', 'string', 'max:120'],
            'option_groups.*.translations.el.description' => ['nullable', 'string', 'max:500'],
            'option_groups.*.options.*.translations' => ['nullable', 'array'],
            'option_groups.*.options.*.translations.en' => ['nullable', 'array'],
            'option_groups.*.options.*.translations.en.name' => ['nullable', 'string', 'max:120'],
            'option_groups.*.options.*.translations.en.description' => ['nullable', 'string', 'max:300'],
            'option_groups.*.options.*.translations.el' => ['nullable', 'array'],
            'option_groups.*.options.*.translations.el.name' => ['nullable', 'string', 'max:120'],
            'option_groups.*.options.*.translations.el.description' => ['nullable', 'string', 'max:300'],
        ];
    }
}
