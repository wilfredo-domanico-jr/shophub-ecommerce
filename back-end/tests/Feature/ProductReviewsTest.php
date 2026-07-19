<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductReviewsTest extends TestCase
{
    use RefreshDatabase;

    private function deliveredOrderFor(User $user, Product $product, string $status = 'delivered'): Order
    {
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => $status]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);

        return $order;
    }

    public function test_listing_shows_visible_reviews_newest_first_with_breakdown(): void
    {
        $product = Product::factory()->create();
        $older = Review::factory()->create(['product_id' => $product->id, 'rating' => 4, 'created_at' => now()->subDay()]);
        $newer = Review::factory()->create(['product_id' => $product->id, 'rating' => 5]);

        $response = $this->getJson("/api/products/{$product->slug}/reviews");

        $response->assertOk();
        $response->assertJsonPath('total', 2);
        $response->assertJsonPath('data.0.id', $newer->id);
        $response->assertJsonPath('data.1.id', $older->id);
        $response->assertJsonPath('data.0.user.name', $newer->user->name);
        $response->assertJsonPath('breakdown.5', 1);
        $response->assertJsonPath('breakdown.4', 1);
    }

    public function test_hidden_reviews_are_excluded_from_the_listing(): void
    {
        $product = Product::factory()->create();
        Review::factory()->create(['product_id' => $product->id, 'is_hidden' => true]);
        $visible = Review::factory()->create(['product_id' => $product->id]);

        $response = $this->getJson("/api/products/{$product->slug}/reviews");

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.id', $visible->id);
    }

    public function test_listing_returns_404_for_inactive_product(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $this->getJson("/api/products/{$product->slug}/reviews")->assertNotFound();
    }

    public function test_guest_cannot_post_a_review(): void
    {
        $product = Product::factory()->create();

        $this->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])
            ->assertUnauthorized();
    }

    public function test_non_purchaser_cannot_post_a_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('rating');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_undelivered_orders_do_not_qualify(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->deliveredOrderFor($user, $product, status: 'pending');
        $this->deliveredOrderFor($user, $product, status: 'shipped');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])
            ->assertUnprocessable();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_verified_purchaser_can_post_a_review_and_aggregates_resync(): void
    {
        $product = Product::factory()->create();

        $first = User::factory()->create();
        $this->deliveredOrderFor($first, $product);
        $this->actingAs($first, 'sanctum')
            ->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5, 'comment' => 'Great buy!'])
            ->assertCreated()
            ->assertJsonPath('rating', 5)
            ->assertJsonPath('comment', 'Great buy!')
            ->assertJsonPath('user.name', $first->name);

        $second = User::factory()->create();
        $this->deliveredOrderFor($second, $product);
        $this->actingAs($second, 'sanctum')
            ->postJson("/api/products/{$product->slug}/reviews", ['rating' => 4])
            ->assertCreated();

        $product->refresh();
        $this->assertSame('4.5', (string) $product->rating);
        $this->assertSame(2, $product->reviews_count);
    }

    public function test_duplicate_review_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->deliveredOrderFor($user, $product);
        Review::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])
            ->assertUnprocessable();

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_rating_is_validated(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->deliveredOrderFor($user, $product);

        foreach ([['rating' => 0], ['rating' => 6], []] as $payload) {
            $this->actingAs($user, 'sanctum')
                ->postJson("/api/products/{$product->slug}/reviews", $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('rating');
        }
    }

    public function test_photos_are_validated(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->deliveredOrderFor($user, $product);

        $tooMany = array_map(fn ($i) => UploadedFile::fake()->image("photo{$i}.jpg"), range(1, 5));
        $this->actingAs($user, 'sanctum')
            ->post("/api/products/{$product->slug}/reviews", ['rating' => 5, 'photos' => $tooMany], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photos');

        $this->actingAs($user, 'sanctum')
            ->post("/api/products/{$product->slug}/reviews", ['rating' => 5, 'photos' => [UploadedFile::fake()->create('document.pdf', 100)]], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photos.0');

        $this->actingAs($user, 'sanctum')
            ->post("/api/products/{$product->slug}/reviews", ['rating' => 5, 'photos' => [UploadedFile::fake()->image('huge.jpg')->size(5000)]], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photos.0');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_photos_are_stored_and_returned_as_urls(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->deliveredOrderFor($user, $product);

        $response = $this->actingAs($user, 'sanctum')->post("/api/products/{$product->slug}/reviews", [
            'rating' => 5,
            'photos' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
            ],
        ], ['Accept' => 'application/json']);

        $response->assertCreated();
        $this->assertCount(2, $response->json('photo_urls'));

        $review = Review::first();
        $this->assertCount(2, $review->photos);
        foreach ($review->photos as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_owner_can_update_their_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 5]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/reviews/{$review->id}", ['rating' => 3, 'comment' => 'Changed my mind.'])
            ->assertOk()
            ->assertJsonPath('rating', 3)
            ->assertJsonPath('comment', 'Changed my mind.');

        $product->refresh();
        $this->assertSame('3.0', (string) $product->rating);
    }

    public function test_non_owner_cannot_update_a_review(): void
    {
        $review = Review::factory()->create(['rating' => 5]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->patchJson("/api/reviews/{$review->id}", ['rating' => 1])
            ->assertForbidden();

        $this->assertSame(5, $review->fresh()->rating);
    }

    public function test_owner_can_delete_their_review(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $path = UploadedFile::fake()->image('photo.jpg')->store('review-photos', 'public');
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'photos' => [$path],
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/reviews/{$review->id}")
            ->assertNoContent();

        $this->assertDatabaseCount('reviews', 0);
        Storage::disk('public')->assertMissing($path);

        $product->refresh();
        $this->assertSame(0, $product->reviews_count);
        $this->assertSame('0.0', (string) $product->rating);
    }

    public function test_non_owner_cannot_delete_a_review(): void
    {
        $review = Review::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->deleteJson("/api/reviews/{$review->id}")
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 1);
    }
}
