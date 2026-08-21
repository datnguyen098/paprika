<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('is_active', true)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s().]{8,20}$/'],
            'email' => ['nullable', 'email:rfc,dns', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ tên.',
            'branch_id.exists' => 'Cơ sở đã chọn không hợp lệ hoặc đang tạm ẩn.',
            'phone.regex' => 'Số điện thoại chưa đúng định dạng.',
            'email.email' => 'Email chưa đúng định dạng.',
            'message.required' => 'Vui lòng nhập nội dung liên hệ.',
            'message.min' => 'Nội dung liên hệ cần ít nhất :min ký tự.',
        ];
    }
}
