<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Review extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'comment',
        'photos',
        'is_hidden',
    ];

    protected $appends = ['photo_urls'];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'is_hidden' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The product's rating/reviews_count are derived from its visible
        // reviews, so any write here (including hide/unhide) resyncs them.
        static::saved(fn (Review $review) => $review->product?->syncRatingFromReviews());
        static::deleted(fn (Review $review) => $review->product?->syncRatingFromReviews());
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * @return array<int, string>
     */
    public function getPhotoUrlsAttribute(): array
    {
        return array_map(
            fn (string $path) => Storage::disk('public')->url($path),
            $this->photos ?? []
        );
    }
}
