<?php

namespace Tests\Feature;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_flutter_api_stores_newsletter_subscription(): void
    {
        $this->postJson('/api/v1/newsletter', [
            'email' => 'NEWS@example.com',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Đăng ký nhận tin thành công.');

        $contact = Contact::query()->where('email', 'news@example.com')->firstOrFail();

        $this->assertSame('new', $contact->status);
        $this->assertStringContainsString('[Newsletter]', $contact->message);
    }

    public function test_flutter_api_rejects_invalid_newsletter_email(): void
    {
        $this->postJson('/api/v1/newsletter', [
            'email' => 'not-an-email',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
