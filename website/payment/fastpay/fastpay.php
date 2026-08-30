<?php
/**
 * FastPay Mobile Wallet PHP SDK & API Client
 * 
 * Supports:
 * - Direct FastPay API gateway initiation
 * - QR code generation & instant wallet transfer
 * - Decoupled Simulation Mode connecting to https://mywebsite.com/payment/fake.php
 */

class FastPayPayment {

    protected $config;
    protected $isLive;
    protected $baseUrl;
    protected $merchantMobile;
    protected $storeId;
    protected $storePassword;

    public function __construct($overrideConfig = []) {
        $defaultConfig = require __DIR__ . '/config.php';
        $this->config = array_merge($defaultConfig, $overrideConfig);

        // Check store settings if available
        if (function_exists('get_store_settings')) {
            $storeSettings = get_store_settings();
            $fpSettings = $storeSettings['gateways']['fastpay'] ?? [];
            if (!empty($fpSettings['store_id'])) {
                $this->storeId = $fpSettings['store_id'];
                $this->merchantMobile = $fpSettings['merchant_mobile'] ?? '';
            }
        }

        $this->isLive = ($this->config['mode'] === 'live');
        $activeMode = $this->isLive ? 'live' : 'simulation';

        $this->baseUrl = $this->config[$activeMode]['base_url'] ?? '';
        $this->merchantMobile = $this->merchantMobile ?: ($this->config[$activeMode]['merchant_mobile'] ?? '07501234567');
        $this->storeId = $this->storeId ?: ($this->config[$activeMode]['store_id'] ?? 'FP_STORE_94821');
        $this->storePassword = $this->config[$activeMode]['store_password'] ?? '';
    }

    /**
     * Initiate FastPay transaction & get QR
     */
    public function initiatePayment($amount, $orderId, $customerMobile = '') {
        if (!$this->isLive) {
            // Decoupled Simulation Mode via fake.php
            $txId = 'FP-' . rand(100000, 999999);
            $qrData = "fastpay://merchant_pay?store=" . urlencode($this->storeId) . "&amount=" . intval($amount) . "&order=" . urlencode($orderId) . "&tx=$txId";
            
            return [
                'success' => true,
                'transaction_id' => $txId,
                'qr_token' => $qrData,
                'qr_image_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrData),
                'amount' => $amount,
                'currency' => 'IQD',
                'simulator_url' => "fake.php?gateway=fastpay&payment_id=$txId&amount=$amount&order_id=$orderId",
                'status' => 'pending'
            ];
        }

        // Live FastPay API Call
        $endpoint = rtrim($this->baseUrl, '/') . '/merchant/generate-payment-token';
        $payload = [
            'store_id' => $this->storeId,
            'store_password' => $this->storePassword,
            'order_id' => $orderId,
            'bill_amount' => $amount,
            'currency' => 'IQD',
            'customer_mobile' => $customerMobile
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);
        if ($httpCode === 200 && ($json['code'] ?? 0) === 200) {
            return [
                'success' => true,
                'transaction_id' => $json['data']['transaction_id'] ?? ('FP-' . rand(100000, 999999)),
                'token' => $json['data']['token'] ?? '',
                'redirect_url' => $json['data']['redirect_url'] ?? '',
                'status' => 'pending'
            ];
        }

        throw new Exception("FastPay Init Failed: " . ($json['messages'][0] ?? $response));
    }

    /**
     * Validate payment status
     */
    public function validatePayment($transactionId) {
        if (!$this->isLive) {
            return [
                'success' => true,
                'status' => 'success',
                'transaction_id' => $transactionId,
                'verified_at' => date('Y-m-d H:i:s')
            ];
        }

        $endpoint = rtrim($this->baseUrl, '/') . '/merchant/payment-validation';
        $payload = [
            'store_id' => $this->storeId,
            'store_password' => $this->storePassword,
            'transaction_id' => $transactionId
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);
        return [
            'success' => (($json['code'] ?? 0) === 200),
            'status' => $json['data']['status'] ?? 'pending',
            'data' => $json
        ];
    }
}
