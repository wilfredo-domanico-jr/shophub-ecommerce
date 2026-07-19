<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    /** @use HasFactory<\Database\Factories\FlashSaleFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Active events that haven't ended, soonest first — first() is the
     * current-or-next event the storefront shows. Overlapping events are
     * allowed; the earliest-starting one wins (a live event always beats
     * an upcoming one because it started earlier).
     */
    public function scopeUpcomingOrLive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('ends_at', '>', now())
            ->orderBy('starts_at');
    }

    public function isLive(): bool
    {
        return now()->between($this->starts_at, $this->ends_at);
    }
}
