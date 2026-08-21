<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pageId = $this->route('page')?->id;

        return [
            'title' => ['required', 'string', 'max:220'],
            'slug' => ['nullable', 'string', 'max:240', Rule::unique('pages', 'slug')->ignore($pageId)],
            'content' => ['required', 'string'],
            'template' => ['nullable', 'string', 'max:100'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.config('uploads.max_image_kb')],
            'is_active' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'translations' => ['nullable', 'array'],
            'translations.en' => ['nullable', 'array'],
            'translations.el' => ['nullable', 'array'],
            'translations.en.title' => ['nullable', 'string', 'max:220'],
            'translations.el.title' => ['nullable', 'string', 'max:220'],
            'translations.en.slug' => ['nullable', 'string', 'max:240'],
            'translations.el.slug' => ['nullable', 'string', 'max:240'],
            'translations.en.content' => ['nullable', 'string'],
            'translations.el.content' => ['nullable', 'string'],
            'translations.en.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.el.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.en.meta_description' => ['nullable', 'string', 'max:500'],
            'translations.el.meta_description' => ['nullable', 'string', 'max:500'],
            'translations.en.meta_keywords' => ['nullable', 'string', 'max:500'],
            'translations.el.meta_keywords' => ['nullable', 'string', 'max:500'],
        ];
    }
}
