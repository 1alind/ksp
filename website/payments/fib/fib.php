<?php
/**
 * First Iraqi Bank (FIB) PHP SDK & API Gateway
 * 
 * Supports:
 * - Direct Live API connectivity with official FIB endpoints (https://api.fib.iq)
 * - Decoupled Simulation Mode connecting to https://mywebsite.com/payment/fake.php
 */

class FibPayment {

    protected $config;
    protected $isLive;
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $accessToken;

    public function __construct($overrideConfig = []) {
        $defaultConfig = require __DIR__ . '/config.php';
        $this->config = array_merge($defaultConfig, $overrideConfig);
        
        // Check store settings if available
        if (function_exists('get_store_settings')) {
            $storeSettings = get_store_settings();
            $fibSettings = $storeSettings['gateways']['fib'] ?? [];
            if (!empty($fibSettings['enabled'])) {
                $this->clientId = $fibSettings['client_id'] ?? '';
                $this->clientSecret = $fibSettings['client_secret'] ?? '';
            }
        }

        $this->isLive = ($this->config['mode'] === 'live');
        $activeMode = $this->isLive ? 'live' : 'simulation';
        
        $this->baseUrl = $this->config[$activeMode]['base_url'] ?? '';
        $this->clientId = $this->clientId ?: ($this->config[$activeMode]['client_id'] ?? '');
        $this->clientSecret = $this->clientSecret ?: ($this->config[$activeMode]['client_secret'] ?? '');
    }

    /**
     * Get OAuth2 Access Token
     */
    public function getAccessToken() {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        if (!$this->isLive) {
            // Simulation mode: route to fake.php
            $simUrl = $this->getSimulatorUrl(['action' => 'fib_token']);
            $resp = $this->sendRequest($simUrl, 'POST', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ]);
            $this->accessToken = $resp['access_token'] ?? ('fib_sim_token_' . bin2hex(random_bytes(16)));
            return $this->accessToken;
        }

        // Live Production API request to FIB OAuth server
        $authUrl = $this->config['live']['auth_url'];
        $postData = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ]);

        $ch = curl_init($authUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 15
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($result, true);
        if ($httpCode === 200 && !empty($json['access_token'])) {
            $this->accessToken = $json['access_token'];
            return $this->accessToken;
        }

        throw new Exception("FIB OAuth Authentication failed: " . ($json['error_description'] ?? 'Unknown error'));
    }

    /**
     * Create a payment & return QR code payload
     */
    public function createPayment($amount, $currency = 'IQD', $orderId = '', $description = 'Aura Luxury Purchase') {
        $token = $this->getAccessToken();

        if (!$this->isLive) {
            // Decoupled Simulation: Request from fake.php
            $simUrl = $this->getSimulatorUrl([
                'action' => 'fib_create',
                'amount' => $amount,
                'currency' => $currency,
                'order_id' => $orderId
            ]);
            return $this->sendRequest($simUrl, 'POST', [
                'amount' => $amount,
                'currency' => $currency,
                'order_id' => $orderId,
                'description' => $description
            ]);
        }

        // Live Production FIB Payment creation
        $endpoint = rtrim($this->baseUrl, '/') . '/protected/v1/payments';
        $payload = json_encode([
            'monetaryValue' => [
                'amount' => $amount,
                'currency' => $currency
            ],
            'statusCallbackUrl' => $this->config['live']['callback_url'],
            'description' => $description,
            'refundableFor' => $this->config['live']['refundable_for'] ?? 'P7D'
        ]);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ],
            CURLOPT_TIMEOUT => 20
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($result, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'payment_id' => $data['paymentId'] ?? '',
                'qr_code' => $data['qrCode'] ?? '',
                'readable_code' => $data['readableCode'] ?? '',
                'valid_until' => $data['validUntil'] ?? '',
                'amount' => $amount,
                'currency' => $currency
            ];
        }

        throw new Exception("FIB Create Payment Failed (" . $httpCode . "): " . ($data['message'] ?? $result));
    }

    /**
     * Check status of a payment (PAID, UNPAID, DECLINED, EXPIRED)
     */
    public function checkStatus($paymentId) {
        if (!$this->isLive) {
            $simUrl = $this->getSimulatorUrl([
                'action' => 'check_status',
                'payment_id' => $paymentId
            ]);
            return $this->sendRequest($simUrl, 'GET');
        }

        $token = $this->getAccessToken();
        $endpoint = rtrim($this->baseUrl, '/') . '/protected/v1/payments/' . urlencode($paymentId) . '/status';

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token
            ],
            CURLOPT_TIMEOUT => 15
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return json_decode($result, true) ?: ['status' => 'UNKNOWN'];
    }

    /**
     * Cancel an active payment
     */
    public function cancelPayment($paymentId) {
        if (!$this->isLive) {
            return ['success' => true, 'status' => 'CANCELLED'];
        }

        $token = $this->getAccessToken();
        $endpoint = rtrim($this->baseUrl, '/') . '/protected/v1/payments/' . urlencode($paymentId) . '/cancel';

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token]
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true) ?: ['success' => true];
    }

    /**
     * Helper to build simulator URL or local fallback
     */
    protected function getSimulatorUrl($params = []) {
        $base = __DIR__ . '/../fake.php';
        if (strpos($this->baseUrl, 'fake.php') !== false) {
            $parsed = parse_url($this->baseUrl);
            $query = [];
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $query);
            }
            $allParams = array_merge($query, $params);
            return (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] : '') . '/website/payment/fake.php?' . http_build_query($allParams);
        }
        return '/website/payment/fake.php?' . http_build_query(array_merge(['gateway' => 'fib'], $params));
    }

    /**
     * Helper to perform requests
     */
    protected function sendRequest($url, $method = 'GET', $data = []) {
        if (!function_exists('curl_init')) {
            // Fallback to internal simulator inclusion if cURL disabled
            require_once __DIR__ . '/../fake.php';
            return [
                'success' => true,
                'payment_id' => 'FIB-' . strtoupper(bin2hex(random_bytes(4))),
                'status' => 'PAID'
            ];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?: [
            'success' => true,
            'payment_id' => 'FIB-' . rand(10000, 99999),
            'qr_code' => 'fib://pay?simulated=1'
        ];
    }
}
