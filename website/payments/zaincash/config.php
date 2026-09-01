<?php
/**
 * ZainCash (زين كاش) Payment Gateway Configuration
 */

return [
    'mode' => 'simulation', // 'simulation' or 'live'
    
    // Live ZainCash Production Credentials
    'live' => [
        'init_url' => 'https://api.zaincash.iq/transaction/init',
        'pay_url' => 'https://api.zaincash.iq/transaction/pay',
        'merchant_id' => '5ff6561142e569f1b470291e',
        'merchant_secret' => 'aura_zaincash_live_secret_key',
        'merchant_msisdn' => '9647835077893',
        'redirect_url' => 'https://mywebsite.com/payment/zaincash/redirect.php'
    ],

    // Local / Server Simulation Mode (Routes to fake.php)
    'simulation' => [
        'init_url' => (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] : '') . '/website/payment/fake.php?gateway=zaincash',
        'merchant_id' => 'test_merchant_aura',
        'merchant_secret' => 'aura_zain_test_secret_2026',
        'merchant_msisdn' => '9647835077893',
        'redirect_url' => '/website/payment/zaincash/redirect.php'
    ]
];
