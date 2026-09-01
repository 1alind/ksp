<?php
/**
 * First Iraqi Bank (FIB) Payment Gateway Configuration
 */

return [
    'mode' => 'simulation', // 'simulation' or 'live'
    
    // Live FIB Production Credentials
    'live' => [
        'base_url' => 'https://api.fib.iq',
        'auth_url' => 'https://api.fib.iq/auth/realms/fib-online-shop/protocol/openid-connect/token',
        'client_id' => 'aura_fib_live_client_id',
        'client_secret' => 'aura_fib_live_secret_key',
        'callback_url' => 'https://mywebsite.com/payment/fib/callback.php',
        'refundable_for' => 'P7D'
    ],

    // Local / Server Simulation Mode (Routes to fake.php)
    'simulation' => [
        'base_url' => (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] : '') . '/website/payment/fake.php?gateway=fib',
        'client_id' => 'fib_test_client_aura',
        'client_secret' => 'fib_test_secret_aura',
        'callback_url' => '/website/payment/fib/callback.php'
    ]
];
