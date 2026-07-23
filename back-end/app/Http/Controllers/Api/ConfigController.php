<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ConfigController extends Controller
{
    public function index()
    {
        // Both flags required: DEMO_MODE alone (e.g. a demo env copied into
        // production by accident) must never be enough to publish working
        // admin credentials over this unauthenticated endpoint.
        $enabled = (bool) config('demo.enabled') && (bool) config('demo.sandbox_confirmed');

        return response()->json([
            'demo_mode' => $enabled,
            'demo_admin_email' => $enabled ? config('demo.admin_email') : null,
            'demo_admin_password' => $enabled ? config('demo.admin_password') : null,
            'demo_customer_email' => $enabled ? config('demo.customer_email') : null,
            'demo_customer_password' => $enabled ? config('demo.customer_password') : null,
            'social_providers' => array_values(array_filter(
                ['google', 'facebook'],
                fn ($provider) => (bool) config("services.$provider.client_id")
            )),
            'card_payments_enabled' => (bool) config('services.stripe.secret'),
        ]);
    }
}
