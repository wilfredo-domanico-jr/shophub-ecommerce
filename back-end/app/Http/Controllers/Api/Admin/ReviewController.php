<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::query()
            ->with(['user:id,name', 'product:id,name,slug'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($sub) => $sub->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->integer('rating'));
        }

        return $query->paginate($request->integer('per_page', 20));
    }

    public function updateVisibility(Request $request, Review $review)
    {
        $validated = $request->validate([
            'is_hidden' => ['required', 'boolean'],
        ]);

        // The model event resyncs the product's rating/reviews_count.
        $review->update(['is_hidden' => $validated['is_hidden']]);

        return $review->load(['user:id,name', 'product:id,name,slug']);
    }

    public function destroy(Review $review)
    {
        Storage::disk('public')->delete($review->photos ?? []);
        $review->delete();

        return response()->noContent();
    }
}
