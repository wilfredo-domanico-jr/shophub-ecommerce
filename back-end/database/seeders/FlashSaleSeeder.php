<?php

namespace Database\Seeders;

use App\Models\FlashSale;
use Illuminate\Database\Seeder;

class FlashSaleSeeder extends Seeder
{
    /**
     * updateOrCreate (not firstOrCreate) so re-seeding always refreshes the
     * window — the demo shows a live sale no matter when it's seeded.
     */
    public function run(): void
    {
        FlashSale::updateOrCreate(
            ['title' => 'Mega Flash Sale'],
            [
                'starts_at' => now()->subHour(),
                'ends_at' => now()->addHours(5),
                'is_active' => true,
            ]
        );
    }
}
