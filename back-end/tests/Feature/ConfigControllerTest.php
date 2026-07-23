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

    public function test_demo_credentials_are_exposed_when_demo_mode_and_sandbox_are_both_confirmed(): void
    {
        config([
            'demo.enabled' => true,
            'demo.sandbox_confirmed' => true,
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

    public function test_demo_credentials_stay_hidden_when_sandbox_is_not_confirmed(): void
    {
        // DEMO_MODE alone (e.g. an env file copied from a demo box into a
        // real deployment) must never be sufficient to publish credentials.
        config([
            'demo.enabled' => true,
            'demo.sandbox_confirmed' => false,
            'demo.admin_email' => 'demo@example.com',
            'demo.admin_password' => 'demo-pass',
        ]);

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

    public function test_social_providers_lists_only_configured_providers(): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.facebook.client_id' => null,
        ]);

        $response = $this->getJson('/api/config');

        $response->assertOk();
        $response->assertJsonPath('social_providers', ['google']);
    }

    public function test_social_providers_is_empty_when_none_are_configured(): void
    {
        config([
            'services.google.client_id' => null,
            'services.facebook.client_id' => null,
        ]);

        $response = $this->getJson('/api/config');

        $response->assertOk();
        $response->assertJsonPath('social_providers', []);
    }

    public function test_card_payments_flag_reflects_stripe_configuration(): void
    {
        config(['services.stripe.secret' => 'sk_test_fake']);
        $this->getJson('/api/config')->assertJsonPath('card_payments_enabled', true);

        config(['services.stripe.secret' => null]);
        $this->getJson('/api/config')->assertJsonPath('card_payments_enabled', false);
    }
}
