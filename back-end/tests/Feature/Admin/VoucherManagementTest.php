<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherManagementTest extends TestCase
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
            'code' => 'SAVE10',
            'type' => 'percent',
            'value' => 10,
        ], $overrides);
    }

    public function test_admin_can_create_a_voucher(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/vouchers', $this->basePayload([
            'description' => '10% off up to 200',
            'max_discount' => 200,
            'min_spend' => 500,
            'usage_limit' => 100,
            'per_customer_limit' => 1,
        ]));

        $response->assertCreated();
        $response->assertJsonPath('code', 'SAVE10');
        $response->assertJsonPath('is_active', true);
        $this->assertDatabaseHas('vouchers', ['code' => 'SAVE10', 'usage_limit' => 100]);
    }

    public function test_code_is_stored_uppercase_and_duplicates_are_rejected_case_insensitively(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/vouchers', $this->basePayload(['code' => 'save10']))
            ->assertCreated()
            ->assertJsonPath('code', 'SAVE10');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/vouchers', $this->basePayload(['code' => 'Save10']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_admin_can_update_a_voucher(): void
    {
        $voucher = Voucher::factory()->create(['code' => 'OLD10']);

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/admin/vouchers/{$voucher->id}", $this->basePayload([
            'code' => 'NEW20',
            'value' => 20,
            'is_active' => false,
        ]));

        $response->assertOk();
        $response->assertJsonPath('code', 'NEW20');
        $response->assertJsonPath('is_active', false);
    }

    public function test_admin_can_delete_a_voucher(): void
    {
        $voucher = Voucher::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/vouchers/{$voucher->id}")
            ->assertOk();

        $this->assertDatabaseMissing('vouchers', ['id' => $voucher->id]);
    }

    public function test_index_lists_vouchers(): void
    {
        Voucher::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/admin/vouchers');

        $response->assertOk();
        $response->assertJsonCount(3);
    }

    public function test_percent_value_cannot_exceed_100_but_fixed_can(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/vouchers', $this->basePayload(['value' => 150]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('value');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/vouchers', $this->basePayload(['type' => 'fixed', 'value' => 150]))
            ->assertCreated();
    }

    public function test_max_discount_is_rejected_for_fixed_vouchers(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/vouchers', $this->basePayload(['type' => 'fixed', 'max_discount' => 100]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('max_discount');
    }

    public function test_expiry_must_be_after_start(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/vouchers', $this->basePayload([
                'starts_at' => '2026-08-01 00:00:00',
                'expires_at' => '2026-07-01 00:00:00',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expires_at');
    }

    public function test_non_admin_cannot_manage_vouchers(): void
    {
        $customer = User::factory()->create(['is_admin' => false]);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/admin/vouchers', $this->basePayload())
            ->assertForbidden();

        $this->assertDatabaseCount('vouchers', 0);
    }
}
