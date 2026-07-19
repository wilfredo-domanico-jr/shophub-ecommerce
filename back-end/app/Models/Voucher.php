<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Voucher extends Model
{
    /** @use HasFactory<\Database\Factories\VoucherFactory> */
    use HasFactory;

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'max_discount',
        'min_spend',
        'starts_at',
        'expires_at',
        'usage_limit',
        'per_customer_limit',
        'is_active',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'min_spend' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'usage_limit' => 'integer',
            'per_customer_limit' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Public vouchers that are currently claimable: active, inside their
     * validity window, and not exhausted.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('is_public', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
    }

    /**
     * Codes are stored uppercase, so uppercasing the lookup keeps behavior
     * identical on MySQL (CI collation) and SQLite (CS) alike.
     */
    public static function findByCode(string $code, bool $lockForUpdate = false): ?self
    {
        return static::query()
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->where('code', Str::upper(trim($code)))
            ->first();
    }

    /**
     * Shared by the checkout and the preview endpoint so both reject a
     * voucher for exactly the same reasons.
     *
     * Cancelled orders still count toward limits — an accepted simplification;
     * admins can raise a limit if a cancellation should free a redemption.
     *
     * @throws ValidationException
     */
    public function validateFor(?User $user, float $subtotal, string $key = 'voucher_code'): void
    {
        $fail = fn (string $message) => throw ValidationException::withMessages([$key => $message]);

        if (! $this->is_active) {
            $fail('This voucher is no longer active.');
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            $fail('This voucher is not active yet.');
        }

        if ($this->expires_at && now()->gte($this->expires_at)) {
            $fail('This voucher has expired.');
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            $fail('This voucher has reached its usage limit.');
        }

        if ($this->min_spend && $subtotal < (float) $this->min_spend) {
            $fail('This voucher requires a minimum spend of ₱'.number_format((float) $this->min_spend, 2).'.');
        }

        if ($this->per_customer_limit && $user
            && $user->orders()->where('voucher_id', $this->id)->count() >= $this->per_customer_limit) {
            $fail("You've already used this voucher.");
        }
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === self::TYPE_PERCENT
            ? round($subtotal * ((float) $this->value) / 100, 2)
            : (float) $this->value;

        if ($this->type === self::TYPE_PERCENT && $this->max_discount !== null) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return min($discount, $subtotal); // the total can never go negative
    }
}
