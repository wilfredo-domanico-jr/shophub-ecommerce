<?php

namespace Tests\Feature\Admin;

use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobOpeningManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_create_an_opening(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/careers', [
            'title' => 'Backend Developer',
            'department' => 'Engineering',
            'location' => 'Remote',
            'employment_type' => 'Full-time',
            'description' => 'Build the Laravel API.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('job_openings', ['title' => 'Backend Developer', 'is_active' => true]);
    }

    public function test_creating_an_opening_requires_all_fields(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/careers', [
            'title' => 'Incomplete',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['department', 'location', 'employment_type', 'description']);
    }

    public function test_admin_can_update_an_opening(): void
    {
        $opening = JobOpening::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/admin/careers/{$opening->id}", [
            'title' => 'Renamed Role',
            'department' => $opening->department,
            'location' => $opening->location,
            'employment_type' => $opening->employment_type,
            'description' => $opening->description,
            'is_active' => false,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('job_openings', [
            'id' => $opening->id,
            'title' => 'Renamed Role',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_an_opening(): void
    {
        $opening = JobOpening::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/admin/careers/{$opening->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('job_openings', ['id' => $opening->id]);
    }

    public function test_admin_index_includes_inactive_openings(): void
    {
        JobOpening::factory()->create(['is_active' => true]);
        JobOpening::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/admin/careers');

        $response->assertOk();
        $response->assertJsonCount(2);
    }

    public function test_non_admin_cannot_manage_openings(): void
    {
        $customer = User::factory()->create(['is_admin' => false]);

        $this->actingAs($customer, 'sanctum')->getJson('/api/admin/careers')->assertForbidden();
        $this->actingAs($customer, 'sanctum')->postJson('/api/admin/careers', [])->assertForbidden();
    }
}
