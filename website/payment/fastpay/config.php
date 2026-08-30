<?php
/**
 * FastPay Mobile Wallet Gateway Configuration
 */

return [
    'mode' => 'simulation', // 'simulation' or 'live'
    
    // Live FastPay Production Credentials
    'live' => [
        'base_url' => 'https://apigw.fast-pay.iq',
        'merchant_mobile' => '07501234567',
        'store_id' => 'FP_STORE_94821',
        'store_password' => 'aura_fastpay_live_pass',
        'callback_url' => 'https://mywebsite.com/payment/fastpay/callback.php'
    ],

    // Local / Server Simulation Mode (Routes to fake.php)
    'simulation' => [
        'base_url' => (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] : '') . '/website/payment/fake.php?gateway=fastpay',
        'merchant_mobile' => '07501234567',
        'store_id' => 'FP_STORE_94821',
        'store_password' => 'test_pass_aura',
        'callback_url' => '/website/payment/fastpay/callback.php'
    ]
];
