<?php

namespace Tests\Feature;

use App\Models\FlashSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFlashSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_live_event_with_is_live_true(): void
    {
        FlashSale::factory()->create([
            'title' => 'Mega Flash Sale',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
        ]);

        $response = $this->getJson('/api/flash-sale');

        $response->assertOk();
        $response->assertJsonPath('sale.title', 'Mega Flash Sale');
        $response->assertJsonPath('sale.is_live', true);
    }

    public function test_returns_an_upcoming_event_with_is_live_false(): void
    {
        FlashSale::factory()->create([
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(4),
        ]);

        $response = $this->getJson('/api/flash-sale');

        $response->assertOk();
        $response->assertJsonPath('sale.is_live', false);
    }

    public function test_returns_null_when_nothing_is_scheduled(): void
    {
        $response = $this->getJson('/api/flash-sale');

        $response->assertOk();
        $this->assertNull($response->json('sale'));
    }

    public function test_ended_and_inactive_events_are_ignored(): void
    {
        FlashSale::factory()->create([
            'starts_at' => now()->subHours(4),
            'ends_at' => now()->subHour(),
        ]);
        FlashSale::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/flash-sale');

        $response->assertOk();
        $this->assertNull($response->json('sale'));
    }

    public function test_earliest_upcoming_event_wins(): void
    {
        FlashSale::factory()->create([
            'title' => 'Later Sale',
            'starts_at' => now()->addHours(6),
            'ends_at' => now()->addHours(8),
        ]);
        FlashSale::factory()->create([
            'title' => 'Sooner Sale',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(3),
        ]);

        $this->getJson('/api/flash-sale')
            ->assertOk()
            ->assertJsonPath('sale.title', 'Sooner Sale');
    }

    public function test_live_event_wins_over_upcoming(): void
    {
        FlashSale::factory()->create([
            'title' => 'Upcoming Sale',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(3),
        ]);
        FlashSale::factory()->create([
            'title' => 'Live Sale',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
        ]);

        $this->getJson('/api/flash-sale')
            ->assertOk()
            ->assertJsonPath('sale.title', 'Live Sale')
            ->assertJsonPath('sale.is_live', true);
    }
}
