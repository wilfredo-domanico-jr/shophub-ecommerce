<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VoucherCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create([
            'category_id' => Category::factory(),
            'price' => 500,
            'stock_quantity' => 50,
        ]);
    }

    private function payload(?string $voucherCode = null, int $quantity = 2): array
    {
        $payload = [
            'customer_name' => 'Juan Dela Cruz',
            'customer_email' => 'juan@example.com',
            'customer_phone' => '09171234567',
            'shipping_address' => '123 Mabini St, Manila',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => $quantity],
            ],
        ];

        if ($voucherCode !== null) {
            $payload['voucher_code'] = $voucherCode;
        }

        return $payload;
    }

    private function checkout(array $payload)
    {
        return $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', $payload);
    }

    public function test_percent_voucher_is_applied_at_checkout(): void
    {
        $voucher = Voucher::factory()->create(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10]);

        $response = $this->checkout($this->payload('SAVE10')); // subtotal 1000

        $response->assertCreated();
        $response->assertJsonPath('subtotal', '1000.00');
        $response->assertJsonPath('discount', '100.00');
        $response->assertJsonPath('total', '900.00');
        $response->assertJsonPath('voucher_code', 'SAVE10');

        $this->assertDatabaseHas('orders', [
            'voucher_id' => $voucher->id,
            'voucher_code' => 'SAVE10',
        ]);
        $this->assertSame(1, $voucher->fresh()->used_count);
    }

    public function test_fixed_voucher_is_applied_at_checkout(): void
    {
        Voucher::factory()->create(['code' => 'WELCOME50', 'type' => 'fixed', 'value' => 50]);

        $response = $this->checkout($this->payload('WELCOME50'));

        $response->assertCreated();
        $response->assertJsonPath('discount', '50.00');
        $response->assertJsonPath('total', '950.00');
    }

    public function test_percent_voucher_cap_is_applied_at_checkout(): void
    {
        Voucher::factory()->create(['code' => 'CAP', 'type' => 'percent', 'value' => 50, 'max_discount' => 120]);

        $response = $this->checkout($this->payload('CAP'));

        $response->assertCreated();
        $response->assertJsonPath('discount', '120.00');
        $response->assertJsonPath('total', '880.00');
    }

    public function test_fixed_voucher_larger_than_subtotal_clamps_to_zero_total(): void
    {
        Voucher::factory()->create(['code' => 'HUGE', 'type' => 'fixed', 'value' => 5000]);

        $response = $this->checkout($this->payload('HUGE', quantity: 1)); // subtotal 500

        $response->assertCreated();
        $response->assertJsonPath('discount', '500.00');
        $response->assertJsonPath('total', '0.00');
    }

    public function test_min_spend_not_met_is_rejected(): void
    {
        $voucher = Voucher::factory()->create(['code' => 'BIG', 'min_spend' => 5000]);

        $response = $this->checkout($this->payload('BIG'));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('voucher_code');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $voucher->fresh()->used_count);
    }

    public function test_expired_voucher_is_rejected(): void
    {
        Voucher::factory()->create(['code' => 'OLD', 'expires_at' => now()->subDay()]);

        $this->checkout($this->payload('OLD'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_code');
    }

    public function test_not_yet_started_voucher_is_rejected(): void
    {
        Voucher::factory()->create(['code' => 'SOON', 'starts_at' => now()->addDay()]);

        $this->checkout($this->payload('SOON'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_code');
    }

    public function test_inactive_voucher_is_rejected(): void
    {
        Voucher::factory()->create(['code' => 'OFF', 'is_active' => false]);

        $this->checkout($this->payload('OFF'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_code');
    }

    public function test_voucher_at_its_usage_limit_is_rejected(): void
    {
        Voucher::factory()->create(['code' => 'LIMITED', 'usage_limit' => 3])
            ->forceFill(['used_count' => 3])->save();

        $this->checkout($this->payload('LIMITED'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_code');
    }

    public function test_once_per_customer_voucher_rejects_a_second_use(): void
    {
        Voucher::factory()->create(['code' => 'ONCE', 'per_customer_limit' => 1]);

        $this->checkout($this->payload('ONCE'))->assertCreated();
        $this->checkout($this->payload('ONCE'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_code');

        // A different customer can still redeem it.
        $other = User::factory()->create();
        $this->actingAs($other, 'sanctum')
            ->postJson('/api/orders', $this->payload('ONCE'))
            ->assertCreated();
    }

    public function test_unknown_code_is_rejected(): void
    {
        $this->checkout($this->payload('NOPE'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_code');
    }

    public function test_lowercase_code_matches_uppercase_voucher(): void
    {
        Voucher::factory()->create(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10]);

        $this->checkout($this->payload('  save10 '))
            ->assertCreated()
            ->assertJsonPath('voucher_code', 'SAVE10');
    }

    public function test_order_without_voucher_is_unaffected(): void
    {
        $response = $this->checkout($this->payload());

        $response->assertCreated();
        $response->assertJsonPath('discount', '0.00');
        $response->assertJsonPath('total', '1000.00');
        $response->assertJsonPath('voucher_code', null);
    }

    public function test_preview_returns_discounted_totals_without_redeeming(): void
    {
        $voucher = Voucher::factory()->create(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/vouchers/preview', [
            'code' => 'save10',
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
        ]);

        $response->assertOk();
        $response->assertJson([
            'code' => 'SAVE10',
            'subtotal' => '1000.00',
            'discount' => '100.00',
            'total' => '900.00',
        ]);
        $this->assertSame(0, $voucher->fresh()->used_count);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_preview_rejects_an_ineligible_voucher(): void
    {
        Voucher::factory()->create(['code' => 'BIG', 'min_spend' => 5000]);

        $this->actingAs($this->user, 'sanctum')->postJson('/api/vouchers/preview', [
            'code' => 'BIG',
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_code');
    }

    public function test_preview_requires_authentication(): void
    {
        Voucher::factory()->create(['code' => 'SAVE10']);

        $this->postJson('/api/vouchers/preview', [
            'code' => 'SAVE10',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ])->assertUnauthorized();
    }
}
