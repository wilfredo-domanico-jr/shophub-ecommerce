<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ReviewSeeder extends Seeder
{
    /**
     * Seeds reviews directly (the delivered-order gate only guards the API),
     * letting the Review model events derive each product's rating and
     * reviews_count — nothing here sets those columns by hand.
     */
    public function run(): void
    {
        $reviewers = $this->reviewers();
        $products = Product::orderBy('id')->get();

        foreach ($products as $index => $product) {
            foreach ($this->reviewsFor($product, $index, $reviewers) as $review) {
                Review::firstOrCreate(
                    ['user_id' => $review['user_id'], 'product_id' => $product->id],
                    ['rating' => $review['rating'], 'comment' => $review['comment']]
                );
            }
        }

        $this->seedDemoDeliveredOrder();
    }

    /**
     * @return array<int, User>
     */
    private function reviewers(): array
    {
        $people = [
            ['name' => 'Maria Santos', 'email' => 'maria.santos@example.com'],
            ['name' => 'Jose Ramirez', 'email' => 'jose.ramirez@example.com'],
            ['name' => 'Ana Reyes', 'email' => 'ana.reyes@example.com'],
            ['name' => 'Paolo Garcia', 'email' => 'paolo.garcia@example.com'],
            ['name' => 'Liza Mendoza', 'email' => 'liza.mendoza@example.com'],
            ['name' => 'Carlo Aquino', 'email' => 'carlo.aquino@example.com'],
        ];

        return array_map(fn (array $person) => User::firstOrCreate(
            ['email' => $person['email']],
            ['name' => $person['name'], 'password' => Hash::make('password')]
        ), $people);
    }

    /**
     * A deterministic spread: 2–5 reviews per product, mostly 4–5 stars.
     *
     * @param  array<int, User>  $reviewers
     * @return array<int, array{user_id: int, rating: int, comment: string}>
     */
    private function reviewsFor(Product $product, int $index, array $reviewers): array
    {
        $comments = [
            5 => [
                'Excellent quality, exceeded my expectations!',
                'Super worth it for the price. Highly recommended.',
                'Fast delivery and the item matches the photos. Very happy!',
                'Five stars — my second time ordering from this shop.',
            ],
            4 => [
                'Good product overall, minor packaging dents but item is fine.',
                'Works as described. Delivery took a bit longer than expected.',
                'Solid quality for the price point.',
            ],
            3 => [
                'Okay for the price, but the build feels a little flimsy.',
                'Decent, though the color is slightly different from the photo.',
            ],
        ];

        $count = 2 + ($index % 4); // 2–5 reviews
        $reviews = [];

        for ($i = 0; $i < $count; $i++) {
            $reviewer = $reviewers[($index + $i) % count($reviewers)];
            // Weighted toward the top: 5,4,5,3 then repeat.
            $rating = [5, 4, 5, 3][$i % 4];

            $reviews[] = [
                'user_id' => $reviewer->id,
                'rating' => $rating,
                'comment' => $comments[$rating][($index + $i) % count($comments[$rating])],
            ];
        }

        return $reviews;
    }

    /**
     * Demo mode only: give the shared demo customer a delivered order so
     * portfolio visitors can walk the MyOrders -> "Write a review" flow.
     */
    private function seedDemoDeliveredOrder(): void
    {
        if (! config('demo.enabled')) {
            return;
        }

        $customer = User::where('email', config('demo.customer_email'))->first();
        $products = Product::orderBy('id')->limit(2)->get();

        if (! $customer || $products->count() < 2) {
            return;
        }

        $subtotal = $products->sum(fn (Product $product) => (float) $product->price);

        $order = Order::firstOrCreate(
            ['order_number' => 'SHP-DEMO-REVIEW-0001'],
            [
                'user_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone ?? '09171234567',
                'shipping_address' => $customer->default_shipping_address ?? '123 Mabini St, Manila',
                'status' => 'delivered',
                'payment_method' => 'Cash on Delivery',
                'payment_status' => 'paid',
                'subtotal' => $subtotal,
                'shipping_fee' => 0,
                'total' => $subtotal,
            ]
        );

        if ($order->items()->doesntExist()) {
            foreach ($products as $product) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_price' => $product->price,
                    'quantity' => 1,
                    'subtotal' => $product->price,
                ]);
            }
        }
    }
}
