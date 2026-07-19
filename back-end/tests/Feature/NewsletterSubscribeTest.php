<?php

namespace Tests\Feature;

use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterSubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribing_stores_the_email_and_queues_a_welcome_mail(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'shopper@example.com',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'shopper@example.com']);
        Mail::assertQueued(NewsletterWelcomeMail::class, fn ($mail) => $mail->hasTo('shopper@example.com'));
    }

    public function test_subscribing_twice_does_not_duplicate_or_resend(): void
    {
        Mail::fake();
        NewsletterSubscriber::create(['email' => 'shopper@example.com']);

        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'Shopper@Example.com', // case-insensitive match
        ]);

        $response->assertOk();
        $this->assertSame(1, NewsletterSubscriber::count());
        Mail::assertNothingQueued();
    }

    public function test_subscribing_requires_a_valid_email(): void
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function test_unsubscribe_link_marks_the_subscriber_inactive(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();

        $response = $this->postJson('/api/newsletter/unsubscribe', [
            'token' => $subscriber->unsubscribe_token,
        ]);

        $response->assertOk();
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_unsubscribe_is_idempotent_and_rejects_bad_tokens(): void
    {
        $subscriber = NewsletterSubscriber::factory()->unsubscribed()->create();
        $originalTimestamp = $subscriber->unsubscribed_at;

        $this->postJson('/api/newsletter/unsubscribe', [
            'token' => $subscriber->unsubscribe_token,
        ])->assertOk();
        $this->assertEquals($originalTimestamp, $subscriber->fresh()->unsubscribed_at);

        $this->postJson('/api/newsletter/unsubscribe', [
            'token' => 'not-a-real-token',
        ])->assertNotFound();
    }

    public function test_resubscribing_after_unsubscribing_reactivates_and_rewelcomes(): void
    {
        Mail::fake();
        $subscriber = NewsletterSubscriber::factory()->unsubscribed()->create();

        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => $subscriber->email,
        ]);

        $response->assertOk();
        $this->assertNull($subscriber->fresh()->unsubscribed_at);
        Mail::assertQueued(NewsletterWelcomeMail::class);
    }
}
