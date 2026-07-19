<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $subscriber = NewsletterSubscriber::firstOrCreate([
            'email' => strtolower($validated['email']),
        ]);

        $resubscribed = ! $subscriber->wasRecentlyCreated && $subscriber->unsubscribed_at !== null;

        if ($resubscribed) {
            $subscriber->update(['unsubscribed_at' => null]);
        }

        // Greet first-time and returning subscribers — repeat submissions from
        // an active subscriber get the same response but no duplicate email.
        if ($subscriber->wasRecentlyCreated || $resubscribed) {
            Mail::to($subscriber->email)->queue(new NewsletterWelcomeMail($subscriber));
        }

        return response()->json([
            'message' => 'Thanks for subscribing! Check your inbox for a welcome email.',
        ]);
    }

    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $validated['token'])->first();

        if (! $subscriber) {
            return response()->json(['message' => 'This unsubscribe link is invalid or no longer active.'], 404);
        }

        // Idempotent — clicking the link twice is fine.
        if ($subscriber->unsubscribed_at === null) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return response()->json([
            'message' => "You've been unsubscribed. You won't receive any more newsletters.",
        ]);
    }
}
