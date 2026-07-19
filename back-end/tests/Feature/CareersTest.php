<?php

namespace Tests\Feature;

use App\Models\JobOpening;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareersTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_returns_active_openings(): void
    {
        JobOpening::factory()->create(['title' => 'Visible Role', 'is_active' => true]);
        JobOpening::factory()->create(['title' => 'Hidden Role', 'is_active' => false]);

        $response = $this->getJson('/api/careers');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.title', 'Visible Role');
    }

    public function test_openings_include_the_fields_the_page_renders(): void
    {
        JobOpening::factory()->create();

        $response = $this->getJson('/api/careers');

        $response->assertOk();
        $response->assertJsonStructure([
            ['id', 'title', 'department', 'location', 'employment_type', 'description'],
        ]);
    }
}
