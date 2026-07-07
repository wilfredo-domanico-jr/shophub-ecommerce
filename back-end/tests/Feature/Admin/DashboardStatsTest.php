<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_aggregate_orders_and_products_correctly(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Product::factory()->count(3)->create();

        Order::factory()->create(['status' => 'delivered', 'total' => 100, 'customer_email' => 'a@example.com']);
        Order::factory()->create(['status' => 'pending', 'total' => 50, 'customer_email' => 'b@example.com']);
        Order::factory()->create(['status' => 'cancelled', 'total' => 999, 'customer_email' => 'a@example.com']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/dashboard/stats');

        $response->assertOk();
        $response->assertJsonPath('orders_count', 3);
        $response->assertJsonPath('products_count', 3);
        $response->assertJsonPath('total_sales', 150); // cancelled order excluded
        $response->assertJsonPath('customers_count', 2); // distinct emails
        $response->assertJsonStructure(['sales_series', 'recent_orders']);
    }
}
