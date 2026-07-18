<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_their_own_orders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $mine = Order::factory()->count(2)->create(['user_id' => $user->id]);
        Order::factory()->create(['user_id' => $other->id]);
        Order::factory()->create(['user_id' => null]); // guest order

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/my/orders');

        $response->assertOk();
        $response->assertJsonPath('total', 2);

        $returnedIds = collect($response->json('data'))->pluck('id')->sort()->values();
        $this->assertSame($mine->pluck('id')->sort()->values()->all(), $returnedIds->all());
    }

    public function test_orders_include_items_and_are_paginated(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $order->items()->create([
            'product_id' => null,
            'product_name' => 'Sample Product',
            'product_price' => 100,
            'quantity' => 1,
            'subtotal' => 100,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/my/orders');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
        $response->assertJsonPath('data.0.items.0.product_name', 'Sample Product');
    }

    public function test_guest_cannot_list_my_orders(): void
    {
        $this->getJson('/api/my/orders')->assertUnauthorized();
    }
}
