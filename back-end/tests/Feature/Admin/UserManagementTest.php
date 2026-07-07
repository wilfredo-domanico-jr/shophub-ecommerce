<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_create_another_admin(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/users', [
            'name' => 'New Admin',
            'email' => 'new-admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'new-admin@example.com', 'is_admin' => true]);
    }

    public function test_creating_a_user_requires_a_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/users', [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'password' => 'secret123',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function test_admin_can_update_another_admins_details_without_changing_password(): void
    {
        $other = User::factory()->create(['name' => 'Old Name']);
        $originalPassword = $other->password;

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/admin/users/{$other->id}", [
            'name' => 'Updated Name',
            'email' => $other->email,
        ]);

        $response->assertOk();
        $response->assertJsonPath('name', 'Updated Name');
        $this->assertSame($originalPassword, $other->fresh()->password);
    }

    public function test_admin_can_remove_another_admin(): void
    {
        $other = User::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/admin/users/{$other->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/admin/users/{$this->admin->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }
}
