<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DishTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],

            'translations' => ['nullable', 'array'],
            'translations.en' => ['nullable', 'array'],
            'translations.el' => ['nullable', 'array'],
            'translations.en.name' => ['nullable', 'string', 'max:120'],
            'translations.el.name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
