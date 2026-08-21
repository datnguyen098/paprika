<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branch = $this->route('branch');
        $branchId = is_object($branch) ? $branch->id : $branch;

        return [
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('branches', 'slug')->ignore($branchId)],
            'city' => ['nullable', 'string', 'max:120'],
            'timezone' => ['nullable', 'timezone'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'hotline' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:180'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'open_days' => ['nullable', 'array'],
            'open_days.*' => ['integer', 'between:0,6'],
            'reservation_time_slots' => ['nullable', 'string', 'max:500'],
            'reservation_last_booking_time' => ['nullable', 'date_format:H:i'],
            'reservation_last_order_buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'google_map_iframe' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:1600'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:'.config('uploads.max_image_kb')],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'zalo_url' => ['nullable', 'url', 'max:255'],
            'accepts_online_orders' => ['nullable', 'boolean'],
            'accepts_pickup_orders' => ['nullable', 'boolean'],
            'accepts_delivery_orders' => ['nullable', 'boolean'],
            'accepts_offline_payment' => ['nullable', 'boolean'],
            'order_notification_email' => ['nullable', 'email', 'max:255'],
            'auto_delivery_quote_enabled' => ['nullable', 'boolean'],
            'delivery_min_order_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'delivery_free_order_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'delivery_max_distance_km' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'delivery_origin_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_origin_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_note' => ['nullable', 'string', 'max:2000'],
            'delivery_zones' => ['nullable', 'array'],
            'delivery_zones.*.id' => ['nullable', 'integer', 'exists:branch_delivery_zones,id'],
            'delivery_zones.*.label' => ['nullable', 'string', 'max:120'],
            'delivery_zones.*.min_distance_km' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'delivery_zones.*.max_distance_km' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'delivery_zones.*.fee' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'delivery_zones.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'delivery_zones.*.is_active' => ['nullable', 'boolean'],
            'delivery_zones.*.delete' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }

}
