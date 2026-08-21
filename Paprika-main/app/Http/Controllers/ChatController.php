<?php

namespace App\Http\Controllers;

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
        $this->enforceSpamLimit('chat-start:'.$request->ip(), 3);

        $data = $request->validate([
            'visitor_name' => ['nullable', 'string', 'max:120'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('is_active', true)],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:160'],
            'message' => ['required', 'string', 'min:2', 'max:1200'],
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

        session(['chat_session_id' => $chatSession->public_id]);

        return response()->json([
            'session_id' => $chatSession->public_id,
            'branch_id' => $chatSession->branch_id,
            'messages' => $this->messagesPayload($chatSession),
        ]);
    }

    public function messages(ChatSession $chatSession): JsonResponse
    {
        $this->authorizeVisitorSession($chatSession);

        return response()->json([
            'session_id' => $chatSession->public_id,
            'branch_id' => $chatSession->branch_id,
            'status' => $chatSession->status,
            'messages' => $this->messagesPayload($chatSession),
        ]);
    }

    public function send(Request $request, ChatSession $chatSession): JsonResponse
    {
        $this->authorizeVisitorSession($chatSession);
        $this->enforceSpamLimit('chat-send:'.$chatSession->public_id.':'.$request->ip(), 10);

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
            'messages' => $this->messagesPayload($chatSession),
        ]);
    }

    private function authorizeVisitorSession(ChatSession $chatSession): void
    {
        abort_unless(session('chat_session_id') === $chatSession->public_id, 403);
    }

    private function enforceSpamLimit(string $key, int $maxAttempts): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            abort(response()->json([
                'message' => 'Bạn gửi hơi nhanh. Vui lòng chờ một chút rồi thử lại.',
            ], 429));
        }

        RateLimiter::hit($key, 60);
    }

    private function messagesPayload(ChatSession $chatSession): array
    {
        return $chatSession->messages()
            ->oldest()
            ->get()
            ->map(fn ($message): array => [
                'id' => $message->id,
                'sender' => $message->sender,
                'sender_name' => $message->sender_name,
                'message' => $message->message,
                'created_at' => $message->created_at->format('H:i'),
            ])
            ->all();
    }
}
