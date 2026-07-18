<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConfigControllerTest extends TestCase
{
    public function test_demo_credentials_are_hidden_when_demo_mode_is_disabled(): void
    {
        config(['demo.enabled' => false]);

        $response = $this->getJson('/api/config');

        $response->assertOk();
        $response->assertJson([
            'demo_mode' => false,
            'demo_admin_email' => null,
            'demo_admin_password' => null,
            'demo_customer_email' => null,
            'demo_customer_password' => null,
        ]);
    }

    public function test_demo_credentials_are_exposed_when_demo_mode_is_enabled(): void
    {
        config([
            'demo.enabled' => true,
            'demo.admin_email' => 'demo@example.com',
            'demo.admin_password' => 'demo-pass',
            'demo.customer_email' => 'demo-customer@example.com',
            'demo.customer_password' => 'demo-customer-pass',
        ]);

        $response = $this->getJson('/api/config');

        $response->assertOk();
        $response->assertJson([
            'demo_mode' => true,
            'demo_admin_email' => 'demo@example.com',
            'demo_admin_password' => 'demo-pass',
            'demo_customer_email' => 'demo-customer@example.com',
            'demo_customer_password' => 'demo-customer-pass',
        ]);
    }
}
