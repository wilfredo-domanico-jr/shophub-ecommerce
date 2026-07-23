<?php

return [
    'enabled' => env('DEMO_MODE', false),
    // A second, separately-named flag DEMO_MODE alone can't imply — so an
    // env file accidentally copied from a demo box into production doesn't
    // silently start publishing the admin password over a public endpoint.
    'sandbox_confirmed' => env('DEMO_SANDBOX_CONFIRMED', false),
    'admin_email' => env('DEMO_ADMIN_EMAIL', 'admin@shophub.test'),
    'admin_password' => env('DEMO_ADMIN_PASSWORD', 'password'),
    'customer_email' => env('DEMO_CUSTOMER_EMAIL', 'customer@shophub.test'),
    'customer_password' => env('DEMO_CUSTOMER_PASSWORD', 'password'),
];
