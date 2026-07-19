<?php

namespace Database\Seeders;

use App\Models\JobOpening;
use Illuminate\Database\Seeder;

class JobOpeningSeeder extends Seeder
{
    /**
     * Seed the sample career openings shown on the public Careers page.
     * Idempotent — safe to run on every deploy.
     */
    public function run(): void
    {
        $openings = [
            [
                'title' => 'Frontend Developer (Vue)',
                'department' => 'Engineering',
                'location' => 'Manila / Remote',
                'employment_type' => 'Full-time',
                'description' => 'Build and polish the storefront and admin experiences with Vue 3, TypeScript, and Tailwind. You\'ll own features end to end — from UI details to API integration — and help shape how millions of future orders get placed.',
            ],
            [
                'title' => 'Customer Support Specialist',
                'department' => 'Operations',
                'location' => 'Manila',
                'employment_type' => 'Full-time',
                'description' => 'Be the friendly voice behind help@shophub.test — resolving order issues, coordinating returns, and turning frustrated shoppers into repeat customers.',
            ],
            [
                'title' => 'Warehouse Associate',
                'department' => 'Fulfillment',
                'location' => 'Pasig',
                'employment_type' => 'Part-time',
                'description' => 'Pick, pack, and stage orders for same-day courier handoff. Your attention to detail is what keeps our delivery promise honest.',
            ],
        ];

        foreach ($openings as $opening) {
            JobOpening::firstOrCreate(['title' => $opening['title']], $opening);
        }
    }
}
