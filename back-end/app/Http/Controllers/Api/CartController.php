<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['items' => $this->cartItems($request)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $product = Product::with('variants')->find($validated['product_id']);

        if (! $product->is_active) {
            throw ValidationException::withMessages([
                'product_id' => 'This product is no longer available.',
            ]);
        }

        // Same variant rules as checkout: required when the product has
        // options, rejected when it doesn't.
        $variant = null;

        if ($product->variants->isNotEmpty()) {
            $variant = $product->variants->firstWhere('id', $validated['variant_id'] ?? null);

            if (! $variant) {
                throw ValidationException::withMessages([
                    'variant_id' => "Please choose options for \"{$product->name}\".",
                ]);
            }
        } elseif (! empty($validated['variant_id'])) {
            throw ValidationException::withMessages([
                'variant_id' => "\"{$product->name}\" does not have options to choose.",
            ]);
        }

        $line = CartItem::firstOrNew([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ]);

        $line->fill([
            'product_name' => $product->name,
            'variant_label' => $variant?->labelFor($product),
            'quantity' => ($line->exists ? $line->quantity : 0) + ($validated['quantity'] ?? 1),
        ])->save();

        return response()->json(['items' => $this->cartItems($request)], 201);
    }

    public function update(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $cartItem->update(['quantity' => $validated['quantity']]);

        return response()->json(['items' => $this->cartItems($request)]);
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === $request->user()->id, 404);

        $cartItem->delete();

        return response()->json(['items' => $this->cartItems($request)]);
    }

    public function clear(Request $request)
    {
        CartItem::where('user_id', $request->user()->id)->delete();

        return response()->noContent();
    }

    /**
     * Lines with live product data where it exists, snapshots where it
     * doesn't. is_available drives the "Item no longer available" state in
     * the SPA; unavailable lines are excluded from totals and checkout.
     *
     * @return array<int, array<string, mixed>>
     */
    private function cartItems(Request $request): array
    {
        return CartItem::where('user_id', $request->user()->id)
            ->with(['product', 'variant'])
            ->orderBy('id')
            ->get()
            ->map(function (CartItem $item) {
                $product = $item->product;
                $variant = $item->variant;

                $price = $variant?->price ?? $product?->price;
                $stock = (int) ($variant?->stock_quantity ?? $product?->stock_quantity ?? 0);

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->product_variant_id,
                    'name' => $product?->name ?? $item->product_name,
                    'variant_label' => $variant && $product
                        ? $variant->labelFor($product)
                        : $item->variant_label,
                    'price' => $price !== null ? (float) $price : null,
                    'image' => $variant?->image ?? $product?->image,
                    'slug' => $product?->slug,
                    'quantity' => $item->quantity,
                    'stock' => $stock,
                    'is_available' => $product !== null && $product->is_active && $stock > 0,
                ];
            })
            ->values()
            ->all();
    }
}
