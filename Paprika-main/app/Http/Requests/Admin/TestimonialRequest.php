<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:160'],
            'content' => ['required', 'string', 'max:1200'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.config('uploads.max_image_kb')],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'translations' => ['nullable', 'array'],
            'translations.en' => ['nullable', 'array'],
            'translations.el' => ['nullable', 'array'],
            'translations.en.role' => ['nullable', 'string', 'max:160'],
            'translations.el.role' => ['nullable', 'string', 'max:160'],
            'translations.en.content' => ['nullable', 'string', 'max:1200'],
            'translations.el.content' => ['nullable', 'string', 'max:1200'],
        ];
    }
}
