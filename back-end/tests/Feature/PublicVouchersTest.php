<?php

namespace Tests\Feature;

use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicVouchersTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_claimable_public_vouchers(): void
    {
        Voucher::factory()->create(['code' => 'PUBLIC1', 'is_public' => true]);
        Voucher::factory()->create(['code' => 'PRIVATE1', 'is_public' => false]);
        Voucher::factory()->create(['code' => 'INACTIVE1', 'is_public' => true, 'is_active' => false]);
        Voucher::factory()->create(['code' => 'EXPIRED1', 'is_public' => true, 'expires_at' => now()->subDay()]);
        Voucher::factory()->create(['code' => 'FUTURE1', 'is_public' => true, 'starts_at' => now()->addDay()]);
        Voucher::factory()->create(['code' => 'USEDUP1', 'is_public' => true, 'usage_limit' => 5])
            ->forceFill(['used_count' => 5])->save();

        $response = $this->getJson('/api/vouchers');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.code', 'PUBLIC1');
    }

    public function test_index_is_public_and_exposes_only_safe_fields(): void
    {
        Voucher::factory()->create([
            'code' => 'SAVE10',
            'is_public' => true,
            'usage_limit' => 100,
        ]);

        $response = $this->getJson('/api/vouchers'); // no auth

        $response->assertOk();
        $voucher = $response->json('0');
        $this->assertArrayHasKey('code', $voucher);
        $this->assertArrayHasKey('min_spend', $voucher);
        $this->assertArrayHasKey('expires_at', $voucher);
        $this->assertArrayNotHasKey('used_count', $voucher);
        $this->assertArrayNotHasKey('usage_limit', $voucher);
        $this->assertArrayNotHasKey('id', $voucher);
    }

    public function test_voucher_within_usage_limit_is_still_listed(): void
    {
        Voucher::factory()->create(['code' => 'ALMOST', 'is_public' => true, 'usage_limit' => 5])
            ->forceFill(['used_count' => 4])->save();

        $this->getJson('/api/vouchers')
            ->assertOk()
            ->assertJsonCount(1);
    }
}
