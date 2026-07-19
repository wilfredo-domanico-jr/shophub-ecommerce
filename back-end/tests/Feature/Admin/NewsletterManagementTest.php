<?php

namespace Tests\Feature\Admin;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_create_a_draft(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/newsletters', [
            'subject' => 'July Flash Sale',
            'body' => 'Up to 50% off this weekend only.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('newsletters', [
            'subject' => 'July Flash Sale',
            'status' => 'draft',
            'sent_at' => null,
        ]);
    }

    public function test_creating_requires_subject_and_body(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/newsletters', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['subject', 'body']);
    }

    public function test_admin_can_update_a_draft(): void
    {
        $newsletter = Newsletter::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/admin/newsletters/{$newsletter->id}", [
            'subject' => 'Updated Subject',
            'body' => $newsletter->body,
            'image_url' => 'https://example.com/banner.jpg',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('newsletters', [
            'id' => $newsletter->id,
            'subject' => 'Updated Subject',
            'image_url' => 'https://example.com/banner.jpg',
        ]);
    }

    public function test_sent_newsletters_cannot_be_edited(): void
    {
        $newsletter = Newsletter::factory()->sent()->create(['subject' => 'Original']);

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/admin/newsletters/{$newsletter->id}", [
            'subject' => 'Tampered',
            'body' => $newsletter->body,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('newsletters', ['id' => $newsletter->id, 'subject' => 'Original']);
    }

    public function test_admin_can_delete_a_newsletter(): void
    {
        $newsletter = Newsletter::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/admin/newsletters/{$newsletter->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('newsletters', ['id' => $newsletter->id]);
    }

    public function test_sending_queues_mail_to_every_subscriber_and_marks_sent(): void
    {
        Mail::fake();
        $newsletter = Newsletter::factory()->create();
        NewsletterSubscriber::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/admin/newsletters/{$newsletter->id}/send");

        $response->assertOk();
        Mail::assertQueued(NewsletterMail::class, 3);

        $newsletter->refresh();
        $this->assertSame('sent', $newsletter->status);
        $this->assertNotNull($newsletter->sent_at);
    }

    public function test_a_newsletter_cannot_be_sent_twice(): void
    {
        Mail::fake();
        $newsletter = Newsletter::factory()->sent()->create();
        NewsletterSubscriber::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/admin/newsletters/{$newsletter->id}/send");

        $response->assertStatus(422);
        Mail::assertNothingQueued();
    }

    public function test_sending_with_no_subscribers_fails_and_stays_draft(): void
    {
        Mail::fake();
        $newsletter = Newsletter::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/admin/newsletters/{$newsletter->id}/send");

        $response->assertStatus(422);
        $this->assertSame('draft', $newsletter->fresh()->status);
    }

    public function test_index_returns_newsletters_and_active_subscriber_count(): void
    {
        Newsletter::factory()->count(2)->create();
        NewsletterSubscriber::factory()->count(5)->create();
        NewsletterSubscriber::factory()->unsubscribed()->count(2)->create();

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/admin/newsletters');

        $response->assertOk();
        $response->assertJsonPath('subscribers_count', 5);
        $response->assertJsonCount(2, 'newsletters');
    }

    public function test_sending_skips_unsubscribed_addresses(): void
    {
        Mail::fake();
        $newsletter = Newsletter::factory()->create();
        NewsletterSubscriber::factory()->count(2)->create();
        $unsubscribed = NewsletterSubscriber::factory()->unsubscribed()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/newsletters/{$newsletter->id}/send")
            ->assertOk();

        Mail::assertQueued(NewsletterMail::class, 2);
        Mail::assertNotQueued(NewsletterMail::class, fn ($mail) => $mail->hasTo($unsubscribed->email));
    }

    public function test_admin_can_list_subscribers_with_status(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'active@example.com']);
        NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'gone@example.com']);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/admin/newsletter-subscribers');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $emails = collect($response->json('data'))->keyBy('email');
        $this->assertNull($emails['active@example.com']['unsubscribed_at']);
        $this->assertNotNull($emails['gone@example.com']['unsubscribed_at']);
        // The unsubscribe token must never leak through the API.
        $this->assertArrayNotHasKey('unsubscribe_token', $emails['active@example.com']);
    }

    public function test_admin_can_search_and_delete_subscribers(): void
    {
        $keep = NewsletterSubscriber::factory()->create(['email' => 'keep@example.com']);
        $remove = NewsletterSubscriber::factory()->create(['email' => 'remove@example.com']);

        $search = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/newsletter-subscribers?search=remove');
        $search->assertOk();
        $search->assertJsonCount(1, 'data');

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/newsletter-subscribers/{$remove->id}")
            ->assertOk();

        $this->assertDatabaseMissing('newsletter_subscribers', ['id' => $remove->id]);
        $this->assertDatabaseHas('newsletter_subscribers', ['id' => $keep->id]);
    }

    public function test_non_admin_cannot_manage_newsletters(): void
    {
        $customer = User::factory()->create(['is_admin' => false]);

        $this->actingAs($customer, 'sanctum')->getJson('/api/admin/newsletters')->assertForbidden();
        $this->actingAs($customer, 'sanctum')->postJson('/api/admin/newsletters', [])->assertForbidden();
    }
}
