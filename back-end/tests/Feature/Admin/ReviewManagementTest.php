<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_lists_reviews_including_hidden_with_relations(): void
    {
        $visible = Review::factory()->create();
        $hidden = Review::factory()->create(['is_hidden' => true]);

        $response = $this->actingAs($this->admin(), 'sanctum')->getJson('/api/admin/reviews');

        $response->assertOk();
        $response->assertJsonPath('total', 2);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($visible->id));
        $this->assertTrue($ids->contains($hidden->id));

        $first = collect($response->json('data'))->firstWhere('id', $visible->id);
        $this->assertArrayHasKey('user', $first);
        $this->assertArrayHasKey('product', $first);
    }

    public function test_admin_can_filter_reviews_by_search_and_rating(): void
    {
        $product = Product::factory()->create(['name' => 'Gaming Mouse']);
        $match = Review::factory()->create(['product_id' => $product->id, 'rating' => 5]);
        Review::factory()->create(['rating' => 2, 'comment' => 'Unrelated feedback']);

        $bySearch = $this->actingAs($this->admin(), 'sanctum')->getJson('/api/admin/reviews?search=Gaming');
        $bySearch->assertOk();
        $bySearch->assertJsonPath('total', 1);
        $bySearch->assertJsonPath('data.0.id', $match->id);

        $byRating = $this->actingAs($this->admin(), 'sanctum')->getJson('/api/admin/reviews?rating=5');
        $byRating->assertOk();
        $byRating->assertJsonPath('total', 1);
        $byRating->assertJsonPath('data.0.id', $match->id);
    }

    public function test_hiding_a_review_removes_it_from_public_listing_and_resyncs_the_product(): void
    {
        $product = Product::factory()->create();
        $review = Review::factory()->create(['product_id' => $product->id, 'rating' => 5]);
        Review::factory()->create(['product_id' => $product->id, 'rating' => 3]);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/reviews/{$review->id}/visibility", ['is_hidden' => true])
            ->assertOk()
            ->assertJsonPath('is_hidden', true);

        $this->getJson("/api/products/{$product->slug}/reviews")->assertJsonPath('total', 1);

        $product->refresh();
        $this->assertSame(1, $product->reviews_count);
        $this->assertSame('3.0', (string) $product->rating);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/reviews/{$review->id}/visibility", ['is_hidden' => false])
            ->assertOk();

        $product->refresh();
        $this->assertSame(2, $product->reviews_count);
        $this->assertSame('4.0', (string) $product->rating);
    }

    public function test_admin_can_delete_a_review_with_its_photos(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $path = UploadedFile::fake()->image('photo.jpg')->store('review-photos', 'public');
        $review = Review::factory()->create(['product_id' => $product->id, 'photos' => [$path]]);

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/admin/reviews/{$review->id}")
            ->assertNoContent();

        $this->assertDatabaseCount('reviews', 0);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame(0, $product->fresh()->reviews_count);
    }

    public function test_deleting_a_user_cleans_up_their_review_photos_and_resyncs_products(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $reviewer = User::factory()->create();
        $path = UploadedFile::fake()->image('photo.jpg')->store('review-photos', 'public');
        Review::factory()->create(['user_id' => $reviewer->id, 'product_id' => $product->id, 'rating' => 5, 'photos' => [$path]]);
        Review::factory()->create(['product_id' => $product->id, 'rating' => 3]);

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/admin/users/{$reviewer->id}")
            ->assertOk();

        Storage::disk('public')->assertMissing($path);
        $product->refresh();
        $this->assertSame(1, $product->reviews_count);
        $this->assertSame('3.0', (string) $product->rating);
    }

    public function test_deleting_a_product_cleans_up_review_photos(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $path = UploadedFile::fake()->image('photo.jpg')->store('review-photos', 'public');
        Review::factory()->create(['product_id' => $product->id, 'photos' => [$path]]);

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/admin/products/{$product->id}")
            ->assertOk();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_non_admin_cannot_access_review_moderation(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/admin/reviews')->assertForbidden();
        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/admin/reviews/{$review->id}/visibility", ['is_hidden' => true])
            ->assertForbidden();
        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/admin/reviews/{$review->id}")
            ->assertForbidden();
    }

    public function test_guest_cannot_access_review_moderation(): void
    {
        $this->getJson('/api/admin/reviews')->assertUnauthorized();
    }
}
