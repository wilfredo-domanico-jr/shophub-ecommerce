<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ConfigController extends Controller
{
    public function index()
    {
        $enabled = (bool) config('demo.enabled');

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
        ]);
    }
}
