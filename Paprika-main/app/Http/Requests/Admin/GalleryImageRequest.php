<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GalleryImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gallery = $this->route('gallery');
        $galleryId = is_object($gallery) ? $gallery->id : $gallery;

        return [
            'title' => ['required', 'string', 'max:180'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('is_active', true)],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('gallery_images', 'slug')->ignore($galleryId)],
            'description' => ['nullable', 'string', 'max:1200'],
            'image' => [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.config('uploads.max_image_kb')],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'translations' => ['nullable', 'array'],
            'translations.en' => ['nullable', 'array'],
            'translations.en.title' => ['nullable', 'string', 'max:180'],
            'translations.en.slug' => ['nullable', 'string', 'max:200'],
            'translations.en.description' => ['nullable', 'string', 'max:1200'],
            'translations.en.alt_text' => ['nullable', 'string', 'max:255'],
            'translations.en.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.en.meta_description' => ['nullable', 'string', 'max:500'],
            'translations.el' => ['nullable', 'array'],
            'translations.el.title' => ['nullable', 'string', 'max:180'],
            'translations.el.slug' => ['nullable', 'string', 'max:200'],
            'translations.el.description' => ['nullable', 'string', 'max:1200'],
            'translations.el.alt_text' => ['nullable', 'string', 'max:255'],
            'translations.el.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.el.meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
