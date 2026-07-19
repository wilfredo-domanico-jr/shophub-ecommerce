<?php

namespace Tests\Feature;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsletterMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_newsletter_mail_renders_content_image_and_unsubscribe_link(): void
    {
        $newsletter = Newsletter::factory()->create([
            'subject' => 'Big Weekend Sale',
            'body' => "First paragraph.\n\nSecond paragraph.",
            'image_url' => 'https://cdn.example.com/banner.jpg',
        ]);
        $subscriber = NewsletterSubscriber::factory()->create();

        $html = (new NewsletterMail($newsletter, $subscriber))->render();

        $this->assertStringContainsString('Big Weekend Sale', $html);
        $this->assertStringContainsString('First paragraph.', $html);
        $this->assertStringContainsString('https://cdn.example.com/banner.jpg', $html);
        $this->assertStringContainsString($subscriber->unsubscribeUrl(), $html);
    }

    public function test_local_image_path_resolves_only_own_storage_uploads(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('banner.jpg');
        $path = $file->store('uploads', 'public');

        $local = Newsletter::factory()->create(['image_url' => '/storage/'.$path]);
        $external = Newsletter::factory()->create(['image_url' => 'https://cdn.example.com/banner.jpg']);
        $missing = Newsletter::factory()->create(['image_url' => '/storage/uploads/does-not-exist.jpg']);

        $this->assertNotNull($local->localImagePath());
        $this->assertNull($external->localImagePath());
        $this->assertNull($missing->localImagePath());
    }
}
