<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'voucher_code' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();

        // Demo visitors order as the seeded demo identity — the checkout
        // form is locked in the UI, and enforced here for direct API calls.
        if ($user->isProtectedDemoAccount()) {
            $validated['customer_name'] = $user->name;
            $validated['customer_email'] = $user->email;
            $validated['customer_phone'] = $user->phone ?? $validated['customer_phone'];
            $validated['shipping_address'] = $user->default_shipping_address ?? $validated['shipping_address'];
        }

        // Merge duplicate lines (same product + variant) so the stock check
        // sees the true total — separate lines could each pass individually
        // and then drive the unsigned stock column negative on decrement.
        $validated['items'] = collect($validated['items'])
            ->groupBy(fn ($item) => $item['product_id'].':'.($item['variant_id'] ?? ''))
            ->map(fn ($group) => [
                'product_id' => $group->first()['product_id'],
                'variant_id' => $group->first()['variant_id'] ?? null,
                'quantity' => $group->sum('quantity'),
            ])
            ->values()
            ->all();

        $order = DB::transaction(function () use ($validated, $user) {
            $productIds = collect($validated['items'])->pluck('product_id');

            // The product-row lock serializes every stock mutation for these
            // products, so variant rows don't need their own lock.
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $products->load('variants');

            $subtotal = 0;
            $orderItemsData = [];

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

                $availableStock = $variant ? $variant->stock_quantity : $product->stock_quantity;

                if ($availableStock < $item['quantity']) {
                    $label = $variant ? "{$product->name} ({$variant->labelFor($product)})" : $product->name;

                    throw ValidationException::withMessages([
                        'items' => "Not enough stock for \"{$label}\" (only {$availableStock} left).",
                    ]);
                }

                $price = $variant ? ($variant->price ?? $product->price) : $product->price;
                $lineSubtotal = $price * $item['quantity'];
                $subtotal += $lineSubtotal;

                $orderItemsData[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $lineSubtotal,
                ];
            }

            $voucher = null;
            $discount = 0.0;

            if (! empty($validated['voucher_code'])) {
                // lockForUpdate serializes concurrent redemptions against usage_limit.
                $voucher = Voucher::findByCode($validated['voucher_code'], lockForUpdate: true);

                if (! $voucher) {
                    throw ValidationException::withMessages([
                        'voucher_code' => 'That voucher code is not valid.',
                    ]);
                }

                $voucher->validateFor($user, $subtotal);
                $discount = $voucher->discountFor($subtotal);
            }

            $shippingFee = 0;
            $total = $subtotal - $discount + $shippingFee;

            $order = Order::create([
                'user_id' => $user->id,
                'voucher_id' => $voucher?->id,
                'voucher_code' => $voucher?->code,
                'discount' => $discount,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'shipping_address' => $validated['shipping_address'],
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $total,
            ]);

            foreach ($orderItemsData as $data) {
                $product = $data['product'];
                $variant = $data['variant'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'variant_label' => $variant?->labelFor($product),
                    'product_price' => $data['price'],
                    'quantity' => $data['quantity'],
                    'subtotal' => $data['subtotal'],
                ]);

                // Product stock stays the variant sum, so decrement both.
                $variant?->decrement('stock_quantity', $data['quantity']);
                $product->decrement('stock_quantity', $data['quantity']);
                $product->increment('sold_count', $data['quantity']);
            }

            $voucher?->increment('used_count');

            return $order;
        });

        // refresh() picks up DB defaults (status, payment fields) so the
        // response matches what GET /my/orders returns for the same order.
        $order->refresh()->load('items');

        Mail::to($order->customer_email)->queue(new OrderConfirmationMail($order));

        return response()->json($order, 201);
    }

    public function myOrders(Request $request)
    {
        return $request->user()
            ->orders()
            // The product slug lets delivered items link to a review form;
            // items whose product was deleted come back with product null.
            ->with('items.product:id,slug')
            ->latest()
            ->paginate($request->integer('per_page', 10));
    }

    public function track(Request $request)
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        $order = Order::query()
            ->where('order_number', $validated['order_number'])
            ->where('customer_email', $validated['email'])
            ->with('items')
            ->first();

        if (! $order) {
            return response()->json(['message' => 'No matching order found.'], 404);
        }

        // Tracking only proves knowledge of order number + email, so keep the
        // payload to status info — no phone, address, or notes.
        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'created_at' => $order->created_at,
            'voucher_code' => $order->voucher_code,
            'discount' => $order->discount,
            'total' => $order->total,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'variant_label' => $item->variant_label,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
            ])->values(),
        ]);
    }
}
