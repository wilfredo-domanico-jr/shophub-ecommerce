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
        ]);
    }
}
