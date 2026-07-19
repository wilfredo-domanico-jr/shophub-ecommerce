<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    /**
     * Sample vouchers so the demo shows off every restriction type.
     * Idempotent — safe to re-run.
     */
    public function run(): void
    {
        $vouchers = [
            [
                'code' => 'SAVE10',
                'description' => '10% off any order (up to ₱200)',
                'type' => Voucher::TYPE_PERCENT,
                'value' => 10,
                'max_discount' => 200,
                'is_public' => true,
            ],
            [
                'code' => 'WELCOME50',
                'description' => '₱50 off your first order',
                'type' => Voucher::TYPE_FIXED,
                'value' => 50,
                'per_customer_limit' => 1,
                'is_public' => true,
            ],
            [
                'code' => 'PAYDAY25',
                'description' => '25% off orders of ₱2,000 or more',
                'type' => Voucher::TYPE_PERCENT,
                'value' => 25,
                'min_spend' => 2000,
                'usage_limit' => 100,
                'is_public' => true,
            ],
        ];

        foreach ($vouchers as $voucher) {
            Voucher::firstOrCreate(['code' => $voucher['code']], $voucher);
        }
    }
}
