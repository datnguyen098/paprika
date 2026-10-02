<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NewsletterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:150'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
        ]);

        $contact = Contact::query()->create([
            'name' => __('site.footer_block.subscribe_name'),
            'email' => mb_strtolower(trim((string) $data['email'])),
            'message' => '[Newsletter] Khách đăng ký nhận cập nhật thực đơn và ưu đãi.',
            'status' => 'new',
        ]);

        Log::info('newsletter.subscribed', [
            'id' => $contact->id,
            'email' => $contact->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký nhận tin thành công.',
            'data' => [
                'id' => $contact->id,
                'created_at' => $contact->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
