<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function index()
    {
        return response()->json([
            'subscribers_count' => NewsletterSubscriber::active()->count(),
            'newsletters' => Newsletter::latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $newsletter = Newsletter::create($this->validated($request));

        return response()->json($newsletter, 201);
    }

    public function update(Request $request, Newsletter $newsletter)
    {
        if ($newsletter->status === Newsletter::STATUS_SENT) {
            return response()->json(['message' => 'Sent newsletters can no longer be edited.'], 422);
        }

        $newsletter->update($this->validated($request));

        return $newsletter;
    }

    public function destroy(Newsletter $newsletter)
    {
        $newsletter->delete();

        return response()->json(['message' => 'Newsletter removed']);
    }

    public function send(Newsletter $newsletter)
    {
        if ($newsletter->status === Newsletter::STATUS_SENT) {
            return response()->json(['message' => 'This newsletter has already been sent.'], 422);
        }

        // Unsubscribed addresses are kept for the admin's records but never mailed.
        $subscribers = NewsletterSubscriber::active()->get();

        if ($subscribers->isEmpty()) {
            return response()->json(['message' => 'There are no subscribers to send to yet.'], 422);
        }

        foreach ($subscribers as $subscriber) {
            Mail::to($subscriber->email)->queue(new NewsletterMail($newsletter, $subscriber));
        }

        $newsletter->update([
            'status' => Newsletter::STATUS_SENT,
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => "Newsletter queued for {$subscribers->count()} subscribers.",
            'newsletter' => $newsletter,
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ]);
    }
}
