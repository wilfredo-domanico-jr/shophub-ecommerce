<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        Storage::fake('public');
    }

    public function test_admin_can_upload_an_image(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/uploads', [
            'image' => UploadedFile::fake()->image('banner.jpg'),
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['url']);

        $filename = basename($response->json('url'));
        Storage::disk('public')->assertExists("uploads/{$filename}");
    }

    public function test_upload_requires_an_image(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/uploads', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('image');
    }

    public function test_upload_rejects_non_image_files(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/uploads', [
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('image');
    }

    public function test_upload_rejects_images_over_the_size_limit(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/uploads', [
            'image' => UploadedFile::fake()->image('huge.jpg')->size(5000),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('image');
    }

    public function test_non_admin_cannot_upload(): void
    {
        $customer = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($customer, 'sanctum')->postJson('/api/admin/uploads', [
            'image' => UploadedFile::fake()->image('sneaky.jpg'),
        ]);

        $response->assertForbidden();
        $this->assertEmpty(Storage::disk('public')->files('uploads'));
    }

    public function test_guest_cannot_upload(): void
    {
        $this->postJson('/api/admin/uploads', [
            'image' => UploadedFile::fake()->image('anon.jpg'),
        ])->assertUnauthorized();
    }
}
