<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'button_text' => ['nullable', 'string', 'max:80'],
            'button_link' => ['nullable', 'string', 'max:255'],
            'image' => [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.config('uploads.max_image_kb')],
            'position' => ['required', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'translations' => ['nullable', 'array'],
            'translations.en' => ['nullable', 'array'],
            'translations.el' => ['nullable', 'array'],
            'translations.en.title' => ['nullable', 'string', 'max:180'],
            'translations.el.title' => ['nullable', 'string', 'max:180'],
            'translations.en.subtitle' => ['nullable', 'string', 'max:180'],
            'translations.el.subtitle' => ['nullable', 'string', 'max:180'],
            'translations.en.description' => ['nullable', 'string', 'max:1000'],
            'translations.el.description' => ['nullable', 'string', 'max:1000'],
            'translations.en.button_text' => ['nullable', 'string', 'max:80'],
            'translations.el.button_text' => ['nullable', 'string', 'max:80'],
        ];
    }
}
