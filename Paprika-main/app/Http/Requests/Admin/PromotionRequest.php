<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:1200'],
            'badge' => ['nullable', 'string', 'max:80'],
            'button_text' => ['nullable', 'string', 'max:80'],
            'button_link' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.config('uploads.max_image_kb')],
            'placement' => ['required', Rule::in(['home', 'popup'])],
            'template' => ['required', Rule::in(['split', 'center', 'minimal'])],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'show_once' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'translations' => ['nullable', 'array'],
            'translations.en' => ['nullable', 'array'],
            'translations.el' => ['nullable', 'array'],
            'translations.en.title' => ['nullable', 'string', 'max:180'],
            'translations.el.title' => ['nullable', 'string', 'max:180'],
            'translations.en.subtitle' => ['nullable', 'string', 'max:180'],
            'translations.el.subtitle' => ['nullable', 'string', 'max:180'],
            'translations.en.description' => ['nullable', 'string', 'max:1200'],
            'translations.el.description' => ['nullable', 'string', 'max:1200'],
            'translations.en.badge' => ['nullable', 'string', 'max:80'],
            'translations.el.badge' => ['nullable', 'string', 'max:80'],
            'translations.en.button_text' => ['nullable', 'string', 'max:80'],
            'translations.el.button_text' => ['nullable', 'string', 'max:80'],
        ];
    }

}
