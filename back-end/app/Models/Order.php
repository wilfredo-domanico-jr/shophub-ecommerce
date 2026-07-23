<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    public const PAYMENT_COD = 'Cash on Delivery';

    public const PAYMENT_CARD = 'Card';

    protected $fillable = [
        'order_number',
        'user_id',
        'voucher_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'status',
        'payment_method',
        'payment_status',
        'paid_at',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'subtotal',
        'shipping_fee',
        'voucher_code',
        'discount',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Still awaiting online payment — the only state "Pay now" may act on.
     */
    public function isPayableByCard(): bool
    {
        return $this->payment_method === self::PAYMENT_CARD
            && $this->payment_status === 'unpaid'
            && $this->status === 'pending';
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        do {
            $candidate = 'SHP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (static::where('order_number', $candidate)->exists());

        return $candidate;
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
