<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function index(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $reviews = $product->reviews()
            ->visible()
            ->with('user:id,name')
            ->latest()
            ->paginate($request->integer('per_page', 10));

        // Extra key rides along on the standard paginator shape so the
        // rating-distribution bars don't need a second request.
        return response()->json(array_merge($reviews->toArray(), [
            'breakdown' => $product->reviews()->visible()
                ->selectRaw('rating, count(*) as total')
                ->groupBy('rating')
                ->pluck('total', 'rating'),
        ]));
    }

    public function store(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:4'],
            'photos.*' => ['image', 'max:4096'],
        ]);

        $user = $request->user();

        $hasDeliveredOrder = $user->orders()
            ->where('status', 'delivered')
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->exists();

        if (! $hasDeliveredOrder) {
            throw ValidationException::withMessages([
                'rating' => 'Only customers with a delivered order for this product can leave a review.',
            ]);
        }

        // The unique(user_id, product_id) index is the race backstop.
        if ($user->reviews()->where('product_id', $product->id)->exists()) {
            throw ValidationException::withMessages([
                'rating' => 'You have already reviewed this product.',
            ]);
        }

        $photoPaths = collect($request->file('photos', []))
            ->map(fn ($file) => $file->store('review-photos', 'public'))
            ->all();

        $review = $user->reviews()->create([
            'product_id' => $product->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'photos' => $photoPaths ?: null,
        ]);

        return response()->json($review->load('user:id,name'), 201);
    }

    public function update(Request $request, Review $review)
    {
        abort_unless($review->user_id === $request->user()->id, 403);

        // Photo edits arrive as a method-spoofed POST (PHP only parses
        // multipart bodies on POST). Removals are expressed as remove_photos
        // rather than a keep-list: FormData can't encode an empty array, so
        // "remove none" and "keep all" must both map to an absent field.
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:4'],
            'photos.*' => ['image', 'max:4096'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['string'],
        ]);

        $removed = array_intersect($review->photos ?? [], $validated['remove_photos'] ?? []);
        $kept = array_values(array_diff($review->photos ?? [], $removed));
        $newFiles = $request->file('photos', []);

        if (count($kept) + count($newFiles) > 4) {
            throw ValidationException::withMessages([
                'photos' => 'A review can have at most 4 photos.',
            ]);
        }

        $newPaths = collect($newFiles)
            ->map(fn ($file) => $file->store('review-photos', 'public'))
            ->all();

        $review->update([
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'photos' => array_merge($kept, $newPaths) ?: null,
        ]);

        Storage::disk('public')->delete($removed);

        return $review->load('user:id,name');
    }

    public function destroy(Request $request, Review $review)
    {
        abort_unless($review->user_id === $request->user()->id, 403);

        Storage::disk('public')->delete($review->photos ?? []);
        $review->delete();

        return response()->noContent();
    }
}
