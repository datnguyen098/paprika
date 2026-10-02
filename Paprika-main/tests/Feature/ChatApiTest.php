<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_flutter_api_can_start_and_continue_chat(): void
    {
        $startResponse = $this->postJson('/api/v1/chat/start', [
            'visitor_name' => 'Flutter Guest',
            'phone' => '+306900000000',
            'message' => 'Xin chào quán.',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.messages.0.sender', 'visitor');

        $sessionId = $startResponse->json('data.session_id');

        $this->postJson("/api/v1/chat/{$sessionId}/messages", [
            'message' => 'Tôi muốn hỏi về đặt bàn.',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.messages');

        $this->getJson("/api/v1/chat/{$sessionId}/messages")
            ->assertOk()
            ->assertJsonPath('data.session_id', $sessionId)
            ->assertJsonCount(2, 'data.messages');

        $this->assertDatabaseHas(ChatSession::class, [
            'public_id' => $sessionId,
            'visitor_name' => 'Flutter Guest',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas(ChatMessage::class, [
            'sender' => 'visitor',
            'message' => 'Tôi muốn hỏi về đặt bàn.',
        ]);
    }

    public function test_flutter_api_rejects_empty_chat_message(): void
    {
        $this->postJson('/api/v1/chat/start', [
            'message' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
    }
}
