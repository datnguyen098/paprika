<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NavigationMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $menuId = $this->route('menu')?->id;

        return [
            'title' => ['required', 'string', 'max:120'],
            'url' => ['required', 'string', 'max:255'],
            'location' => ['required', Rule::in(['header', 'footer'])],
            'parent_id' => ['nullable', 'exists:navigation_menus,id', Rule::notIn([$menuId])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'open_new_tab' => ['nullable', 'boolean'],
            'translations' => ['nullable', 'array'],
            'translations.en' => ['nullable', 'array'],
            'translations.el' => ['nullable', 'array'],
            'translations.en.title' => ['nullable', 'string', 'max:120'],
            'translations.el.title' => ['nullable', 'string', 'max:120'],
            'translations.en.url' => ['nullable', 'string', 'max:255'],
            'translations.el.url' => ['nullable', 'string', 'max:255'],
        ];
    }
}
