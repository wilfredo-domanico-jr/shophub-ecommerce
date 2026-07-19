<?php

namespace App\Mail;

use App\Models\Newsletter;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Newsletter $newsletter,
        public NewsletterSubscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->newsletter->subject,
        );
    }

    public function content(): Content
    {
        // Plain HTML view (not markdown): markdown mails can't inline-embed
        // images, and locally uploaded banners must travel inside the email.
        return new Content(
            view: 'emails.newsletter',
            with: [
                'newsletter' => $this->newsletter,
                'shopUrl' => config('app.frontend_url'),
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}
