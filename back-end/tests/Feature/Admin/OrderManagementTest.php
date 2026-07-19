<?php

namespace Tests\Feature\Admin;

use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_list_orders_with_item_counts(): void
    {
        $order = Order::factory()->create();
        $order->items()->create([
            'product_id' => null,
            'product_name' => 'Sample Product',
            'product_price' => 100,
            'quantity' => 2,
            'subtotal' => 200,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/admin/orders');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.id', $order->id);
        $response->assertJsonPath('data.0.items_count', 1);
    }

    public function test_index_can_filter_by_status(): void
    {
        Order::factory()->create(['status' => 'pending']);
        $shipped = Order::factory()->create(['status' => 'shipped']);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/admin/orders?status=shipped');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $shipped->id);
    }

    public function test_index_supports_search_by_order_number_and_customer_name(): void
    {
        $target = Order::factory()->create(['customer_name' => 'Juan Dela Cruz']);
        Order::factory()->create(['customer_name' => 'Someone Else']);

        $byName = $this->actingAs($this->admin, 'sanctum')->getJson('/api/admin/orders?search=Dela');
        $byName->assertOk();
        $byName->assertJsonCount(1, 'data');
        $byName->assertJsonPath('data.0.id', $target->id);

        $byNumber = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/orders?search='.$target->order_number);
        $byNumber->assertOk();
        $byNumber->assertJsonCount(1, 'data');
        $byNumber->assertJsonPath('data.0.order_number', $target->order_number);
    }

    public function test_admin_can_view_a_single_order_with_its_items(): void
    {
        $order = Order::factory()->create();
        $order->items()->create([
            'product_id' => null,
            'product_name' => 'Sample Product',
            'product_price' => 100,
            'quantity' => 1,
            'subtotal' => 100,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/admin/orders/{$order->id}");

        $response->assertOk();
        $response->assertJsonPath('id', $order->id);
        $response->assertJsonPath('order_number', $order->order_number);
        $response->assertJsonPath('items.0.product_name', 'Sample Product');
    }

    public function test_admin_can_update_order_status_and_customer_is_emailed(): void
    {
        Mail::fake();
        $order = Order::factory()->create([
            'status' => 'pending',
            'customer_email' => 'buyer@example.com',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'shipped']);

        $response->assertOk();
        $response->assertJsonPath('status', 'shipped');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped']);

        Mail::assertQueued(OrderStatusUpdatedMail::class, fn ($mail) => $mail->hasTo('buyer@example.com'));
    }

    public function test_updating_status_rejects_invalid_values(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['status' => 'pending']);

        $missing = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", []);
        $missing->assertUnprocessable();
        $missing->assertJsonValidationErrors('status');

        $invalid = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'refunded']);
        $invalid->assertUnprocessable();
        $invalid->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        Mail::assertNothingQueued();
    }

    public function test_non_admin_cannot_access_admin_orders(): void
    {
        Mail::fake();
        $customer = User::factory()->create(['is_admin' => false]);
        $order = Order::factory()->create(['status' => 'pending']);

        $this->actingAs($customer, 'sanctum')->getJson('/api/admin/orders')->assertForbidden();
        $this->actingAs($customer, 'sanctum')->getJson("/api/admin/orders/{$order->id}")->assertForbidden();
        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        Mail::assertNothingQueued();
    }

    public function test_guest_cannot_access_admin_orders(): void
    {
        $this->getJson('/api/admin/orders')->assertUnauthorized();
    }
}
