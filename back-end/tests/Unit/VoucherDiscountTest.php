<?php

namespace Tests\Unit;

use App\Models\Voucher;
use PHPUnit\Framework\TestCase;

class VoucherDiscountTest extends TestCase
{
    private function voucher(array $attributes): Voucher
    {
        return new Voucher($attributes);
    }

    public function test_percent_discount_is_computed_from_subtotal(): void
    {
        $voucher = $this->voucher(['type' => 'percent', 'value' => 10]);

        $this->assertSame(120.00, $voucher->discountFor(1200.00));
    }

    public function test_percent_discount_rounds_to_two_decimals(): void
    {
        $voucher = $this->voucher(['type' => 'percent', 'value' => 7.5]);

        $this->assertSame(75.00, $voucher->discountFor(999.99)); // 74.99925 rounds up
    }

    public function test_percent_discount_is_capped_by_max_discount(): void
    {
        $voucher = $this->voucher(['type' => 'percent', 'value' => 10, 'max_discount' => 200]);

        $this->assertSame(200.00, $voucher->discountFor(5000.00));
    }

    public function test_percent_discount_below_the_cap_is_untouched(): void
    {
        $voucher = $this->voucher(['type' => 'percent', 'value' => 10, 'max_discount' => 200]);

        $this->assertSame(100.00, $voucher->discountFor(1000.00));
    }

    public function test_fixed_discount_uses_the_full_value(): void
    {
        $voucher = $this->voucher(['type' => 'fixed', 'value' => 50]);

        $this->assertSame(50.00, $voucher->discountFor(500.00));
    }

    public function test_fixed_discount_never_exceeds_the_subtotal(): void
    {
        $voucher = $this->voucher(['type' => 'fixed', 'value' => 500]);

        $this->assertSame(199.00, $voucher->discountFor(199.00));
    }

    public function test_hundred_percent_discount_equals_the_subtotal(): void
    {
        $voucher = $this->voucher(['type' => 'percent', 'value' => 100]);

        $this->assertSame(750.00, $voucher->discountFor(750.00));
    }
}
