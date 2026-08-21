<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(['dish', 'post'])],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.config('uploads.max_image_kb')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'translations' => ['nullable', 'array'],
            'translations.en' => ['nullable', 'array'],
            'translations.el' => ['nullable', 'array'],
            'translations.en.name' => ['nullable', 'string', 'max:160'],
            'translations.el.name' => ['nullable', 'string', 'max:160'],
            'translations.en.slug' => ['nullable', 'string', 'max:180'],
            'translations.el.slug' => ['nullable', 'string', 'max:180'],
            'translations.en.description' => ['nullable', 'string', 'max:1000'],
            'translations.el.description' => ['nullable', 'string', 'max:1000'],
            'translations.en.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.el.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.en.meta_description' => ['nullable', 'string', 'max:500'],
            'translations.el.meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
