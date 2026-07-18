<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = config('demo.admin_email');
        $password = config('demo.admin_password');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'ShopHub Admin',
                'password' => Hash::make($password),
            ]
        );

        if (!$user->is_admin) {
            $user->forceFill(['is_admin' => true])->save();
        }
    }
}
