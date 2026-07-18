<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Unlike the admin account, the demo customer only exists for demo mode.
        if (!config('demo.enabled')) {
            return;
        }

        User::firstOrCreate(
            ['email' => config('demo.customer_email')],
            [
                'name' => 'Juan Dela Cruz',
                'password' => Hash::make(config('demo.customer_password')),
                'phone' => '09171234567',
                'default_shipping_address' => '123 Mabini St, Manila',
            ]
        );
    }
}
