<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        $this->enforceSpamLimit('api-chat-start:'.$request->ip(), 5);

        $data = $request->validate([
            'visitor_name' => ['nullable', 'string', 'max:120'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('is_active', true)],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:160'],
            'message' => ['required', 'string', 'min:1', 'max:1200'],
            'website' => ['nullable', 'prohibited'],
        ]);

        $branchId = $data['branch_id'] ?? Branch::query()
            ->active()
            ->orderBy('sort_order')
            ->value('id');

        $chatSession = ChatSession::create([
            'branch_id' => $branchId,
            'visitor_name' => $data['visitor_name'] ?? 'Khách ghé thăm',
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $chatSession->messages()->create([
            'sender' => 'visitor',
            'sender_name' => $chatSession->visitor_name,
            'message' => $data['message'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã bắt đầu hội thoại.',
            'data' => $this->sessionPayload($chatSession),
        ], 201);
    }

    public function messages(ChatSession $chatSession): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Đã tải tin nhắn.',
            'data' => $this->sessionPayload($chatSession),
        ]);
    }

    public function send(Request $request, ChatSession $chatSession): JsonResponse
    {
        $this->enforceSpamLimit('api-chat-send:'.$chatSession->public_id.':'.$request->ip(), 12);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:1200'],
        ]);

        $chatSession->messages()->create([
            'sender' => 'visitor',
            'sender_name' => $chatSession->visitor_name ?: 'Khách ghé thăm',
            'message' => $data['message'],
        ]);

        $chatSession->update([
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi tin nhắn.',
            'data' => $this->sessionPayload($chatSession),
        ]);
    }

    private function enforceSpamLimit(string $key, int $maxAttempts): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            abort(response()->json([
                'success' => false,
                'message' => 'Bạn gửi hơi nhanh. Vui lòng chờ một chút rồi thử lại.',
            ], 429));
        }

        RateLimiter::hit($key, 60);
    }

    private function sessionPayload(ChatSession $chatSession): array
    {
        return [
            'session_id' => $chatSession->public_id,
            'branch_id' => $chatSession->branch_id,
            'status' => $chatSession->status,
            'messages' => $chatSession->messages()
                ->oldest()
                ->get()
                ->map(fn ($message): array => [
                    'id' => $message->id,
                    'sender' => $message->sender,
                    'sender_name' => $message->sender_name,
                    'message' => $message->message,
                    'created_at' => $message->created_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }
}
