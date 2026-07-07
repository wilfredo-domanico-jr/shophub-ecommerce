<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.id', $user->id);
        $response->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_fails_for_unknown_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_own_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/me');

        $response->assertOk();
        $response->assertJsonPath('id', $user->id);
    }

    public function test_guest_cannot_access_me(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $response->assertOk();
    }

    public function test_non_admin_is_forbidden_from_admin_routes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/admin/dashboard/stats');

        $response->assertForbidden();
    }

    public function test_guest_is_unauthorized_on_admin_routes(): void
    {
        $this->getJson('/api/admin/dashboard/stats')->assertUnauthorized();
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/dashboard/stats');

        $response->assertOk();
    }
}
