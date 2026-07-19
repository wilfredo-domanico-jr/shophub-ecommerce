<?php

namespace Tests\Feature\Admin;

use App\Models\FlashSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashSaleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Payday Flash Sale',
            'starts_at' => '2026-08-01 00:00:00',
            'ends_at' => '2026-08-01 12:00:00',
        ], $overrides);
    }

    public function test_admin_can_schedule_a_flash_sale(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/flash-sales', $this->basePayload());

        $response->assertCreated();
        $response->assertJsonPath('title', 'Payday Flash Sale');
        $response->assertJsonPath('is_active', true);
        $this->assertDatabaseHas('flash_sales', ['title' => 'Payday Flash Sale']);
    }

    public function test_all_fields_are_required(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/flash-sales', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'starts_at', 'ends_at']);
    }

    public function test_end_must_be_after_start(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/flash-sales', $this->basePayload([
                'ends_at' => '2026-07-31 00:00:00',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ends_at');
    }

    public function test_admin_can_update_and_disable_a_flash_sale(): void
    {
        $sale = FlashSale::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/flash-sales/{$sale->id}", $this->basePayload([
                'title' => 'Rescheduled Sale',
                'is_active' => false,
            ]));

        $response->assertOk();
        $response->assertJsonPath('title', 'Rescheduled Sale');
        $response->assertJsonPath('is_active', false);
    }

    public function test_admin_can_delete_a_flash_sale(): void
    {
        $sale = FlashSale::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/flash-sales/{$sale->id}")
            ->assertOk();

        $this->assertDatabaseMissing('flash_sales', ['id' => $sale->id]);
    }

    public function test_index_lists_all_events_including_ended_and_inactive(): void
    {
        FlashSale::factory()->create();
        FlashSale::factory()->create(['is_active' => false]);
        FlashSale::factory()->create([
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/flash-sales')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_non_admin_cannot_manage_flash_sales(): void
    {
        $customer = User::factory()->create(['is_admin' => false]);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/admin/flash-sales', $this->basePayload())
            ->assertForbidden();

        $this->assertDatabaseCount('flash_sales', 0);
    }
}
