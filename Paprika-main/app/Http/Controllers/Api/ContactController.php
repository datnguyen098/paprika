<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    /**
     * POST /api/v1/contact
     *
     * Mock thuần — validate input, log payload, trả về id giả. Sau này
     * nối DB bảng contact_messages + queue email cho bếp/manager.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'min:2', 'max:120'],
            'email'   => ['required', 'email:rfc', 'max:150'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ], [
            'name.required'    => 'Vui lòng nhập họ tên.',
            'name.min'         => 'Họ tên phải có ít nhất :min ký tự.',
            'email.required'   => 'Vui lòng nhập email.',
            'email.email'      => 'Email không đúng định dạng.',
            'message.required' => 'Vui lòng nhập nội dung liên hệ.',
            'message.min'      => 'Nội dung phải có ít nhất :min ký tự.',
        ]);

        // Mock: log để kiểm tra payload từ mobile khi dev.
        Log::info('contact.messages.received', $validated);

        return response()->json([
            'success' => true,
            'message' => 'Gửi liên hệ thành công. Chúng tôi sẽ phản hồi sớm nhất.',
            'data'    => [
                'id'         => (string) Str::uuid(),
                'created_at' => now()->toIso8601String(),
            ],
        ], 201);
    }
}
