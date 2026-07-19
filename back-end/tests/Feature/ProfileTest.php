<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '09171234567',
            'default_shipping_address' => '123 Mabini St, Manila',
        ]);

        $response->assertOk();
        $response->assertJsonPath('name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'updated@example.com',
            'phone' => '09171234567',
        ]);
    }

    public function test_demo_account_cannot_update_profile_in_demo_mode(): void
    {
        config(['demo.enabled' => true, 'demo.customer_email' => 'demo-customer@example.com']);
        $user = User::factory()->create(['email' => 'demo-customer@example.com']);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/profile', [
            'name' => 'Hijacked',
            'email' => 'hijacked@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Profile changes are disabled for the shared demo account.');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'demo-customer@example.com']);
    }

    public function test_demo_account_cannot_change_password_in_demo_mode(): void
    {
        config(['demo.enabled' => true, 'demo.customer_email' => 'demo-customer@example.com']);
        $user = User::factory()->create([
            'email' => 'demo-customer@example.com',
            'password' => Hash::make('original-pass'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/profile/password', [
            'current_password' => 'original-pass',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertStatus(422);
        $this->assertTrue(Hash::check('original-pass', $user->fresh()->password));
    }

    public function test_email_uniqueness_ignores_own_email(): void
    {
        $user = User::factory()->create(['email' => 'juan@example.com']);

        $this->actingAs($user, 'sanctum')->patchJson('/api/profile', [
            'name' => $user->name,
            'email' => 'juan@example.com',
        ])->assertOk();
    }

    public function test_cannot_take_another_users_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->patchJson('/api/profile', [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_guest_cannot_update_profile(): void
    {
        $this->patchJson('/api/profile', ['name' => 'X'])->assertUnauthorized();
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('oldsecret1')]);
        $token = $user->createToken('vue-token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson('/api/profile/password', [
                'current_password' => 'wrong-password',
                'password' => 'newsecret123',
                'password_confirmation' => 'newsecret123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');
    }

    public function test_password_change_revokes_other_tokens_but_keeps_current(): void
    {
        $user = User::factory()->create(['password' => bcrypt('oldsecret1')]);
        $currentToken = $user->createToken('current')->plainTextToken;
        $user->createToken('other-device');

        $this->withHeader('Authorization', "Bearer {$currentToken}")
            ->patchJson('/api/profile/password', [
                'current_password' => 'oldsecret1',
                'password' => 'newsecret123',
                'password_confirmation' => 'newsecret123',
            ])
            ->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('newsecret123', $user->password));
        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('current', $user->tokens()->first()->name);
    }
}
