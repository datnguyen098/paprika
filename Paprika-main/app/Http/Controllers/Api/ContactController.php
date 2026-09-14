<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * POST /api/v1/contact
     *
     * Validate payload, lưu vào bảng contacts (status = 'new'),
     * log để team theo dõi, trả về id + created_at thật.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'min:2', 'max:120'],
            'email'   => ['required', 'email:rfc', 'max:150'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ], [
            'name.required'    => 'Vui lòng nhập họ tên.',
            'name.min'         => 'Họ tên phải có ít nhất :min ký tự.',
            'email.required'   => 'Vui lòng nhập email.',
            'email.email'      => 'Email không đúng định dạng.',
            'message.required' => 'Vui lòng nhập nội dung liên hệ.',
            'message.min'      => 'Nội dung phải có ít nhất :min ký tự.',
            'branch_id.exists' => 'Chi nhánh không tồn tại.',
        ]);

        // Lưu DB — subject hiện không có cột riêng, nhúng vào message để admin xem.
        $payload = [
            'name'    => $validated['name'],
            'email'   => $validated['email'],
            'phone'   => $validated['phone'] ?? null,
            'message' => $this->composeMessage($validated),
            'status'  => 'new',
        ];

        if (! empty($validated['branch_id'])) {
            $payload['branch_id'] = $validated['branch_id'];
        }

        $contact = Contact::query()->create($payload);

        Log::info('contact.messages.received', [
            'id'        => $contact->id,
            'email'     => $contact->email,
            'branch_id' => $contact->branch_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gửi liên hệ thành công. Chúng tôi sẽ phản hồi sớm nhất.',
            'data'    => [
                'id'         => $contact->id,
                'created_at' => $contact->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * @param  array<string,mixed>  $validated
     */
    private function composeMessage(array $validated): string
    {
        $subject = trim((string) ($validated['subject'] ?? ''));

        if ($subject === '') {
            return (string) $validated['message'];
        }

        return "[{$subject}] ".$validated['message'];
    }
}
