<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VoucherController extends Controller
{
    /**
     * Publicly claimable vouchers for the storefront. Only safe fields —
     * never expose usage counters or private codes.
     */
    public function index()
    {
        return Voucher::available()
            ->latest()
            ->get()
            ->map(fn (Voucher $voucher) => [
                'code' => $voucher->code,
                'description' => $voucher->description,
                'type' => $voucher->type,
                'value' => $voucher->value,
                'max_discount' => $voucher->max_discount,
                'min_spend' => $voucher->min_spend,
                'expires_at' => $voucher->expires_at,
                'per_customer_limit' => $voucher->per_customer_limit,
            ]);
    }

    /**
     * Price a voucher against the cart before checkout. Purely cosmetic —
     * the order endpoint re-validates everything under lock, so this does
     * no locking, no stock checks, and never increments used_count.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        // Subtotal is recomputed from DB prices — mirrors OrderController::store
        // so the preview matches what checkout will actually charge.
        $products = Product::query()
            ->whereIn('id', collect($validated['items'])->pluck('product_id'))
            ->with('variants')
            ->get()
            ->keyBy('id');

        $subtotal = 0.0;

        foreach ($validated['items'] as $item) {
            $product = $products->get($item['product_id']);

            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages([
                    'items' => 'One of the products in your cart is no longer available.',
                ]);
            }

            $variant = null;

            if ($product->variants->isNotEmpty()) {
                $variant = $product->variants->firstWhere('id', $item['variant_id'] ?? null);

                if (! $variant) {
                    throw ValidationException::withMessages([
                        'items' => "Please choose options for \"{$product->name}\".",
                    ]);
                }
            } elseif (! empty($item['variant_id'])) {
                throw ValidationException::withMessages([
                    'items' => "\"{$product->name}\" does not have options to choose.",
                ]);
            }

            $price = $variant ? ($variant->price ?? $product->price) : $product->price;
            $subtotal += $price * $item['quantity'];
        }

        $voucher = Voucher::findByCode($validated['code']);

        if (! $voucher) {
            throw ValidationException::withMessages([
                'voucher_code' => 'That voucher code is not valid.',
            ]);
        }

        $voucher->validateFor($request->user(), $subtotal);
        $discount = $voucher->discountFor($subtotal);

        $format = fn (float $amount) => number_format($amount, 2, '.', '');

        return response()->json([
            'code' => $voucher->code,
            'description' => $voucher->description,
            'subtotal' => $format($subtotal),
            'discount' => $format($discount),
            'total' => $format($subtotal - $discount),
        ]);
    }
}
