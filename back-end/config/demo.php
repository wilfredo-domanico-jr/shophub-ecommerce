<?php

return [
    'enabled' => env('DEMO_MODE', false),
    'admin_email' => env('DEMO_ADMIN_EMAIL', 'admin@shophub.test'),
    'admin_password' => env('DEMO_ADMIN_PASSWORD', 'password'),
    'customer_email' => env('DEMO_CUSTOMER_EMAIL', 'customer@shophub.test'),
    'customer_password' => env('DEMO_CUSTOMER_PASSWORD', 'password'),
];
