<?php
/**
 * ZainCash (زين كاش) PHP SDK & API Client
 * 
 * Supports:
 * - HS256 HMAC JWT signature encoding and verification
 * - Live ZainCash merchant API interaction
 * - Decoupled Simulation Mode connecting to https://mywebsite.com/payment/fake.php
 */

class ZainCashPayment {

    protected $config;
    protected $isLive;
    protected $merchantId;
    protected $merchantSecret;
    protected $merchantMsisdn;
    protected $redirectUrl;

    public function __construct($overrideConfig = []) {
        $defaultConfig = require __DIR__ . '/config.php';
        $this->config = array_merge($defaultConfig, $overrideConfig);

        // Check store settings if available
        if (function_exists('get_store_settings')) {
            $storeSettings = get_store_settings();
            $zainSettings = $storeSettings['gateways']['zaincash'] ?? [];
            if (!empty($zainSettings['merchant_id'])) {
                $this->merchantId = $zainSettings['merchant_id'];
                $this->merchantSecret = $zainSettings['secret_key'] ?? '';
                $this->merchantMsisdn = $zainSettings['msisdn'] ?? '';
            }
        }

        $this->isLive = ($this->config['mode'] === 'live');
        $activeMode = $this->isLive ? 'live' : 'simulation';

        $this->merchantId = $this->merchantId ?: ($this->config[$activeMode]['merchant_id'] ?? '');
        $this->merchantSecret = $this->merchantSecret ?: ($this->config[$activeMode]['merchant_secret'] ?? '');
        $this->merchantMsisdn = $this->merchantMsisdn ?: ($this->config[$activeMode]['merchant_msisdn'] ?? '9647835077893');
        $this->redirectUrl = $this->config[$activeMode]['redirect_url'] ?? '';
    }

    /**
     * Encode payload into HS256 JWT
     */
    public function encodeJWT($payload, $secret) {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", $secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);
        return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
    }

    /**
     * Decode and verify JWT
     */
    public function decodeJWT($jwt, $secret) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return false;
        }

        list($header64, $payload64, $signature64) = $parts;
        $signature = $this->base64UrlDecode($signature64);
        $expectedSignature = hash_hmac('sha256', "$header64.$payload64", $secret, true);

        if (!hash_equals($signature, $expectedSignature)) {
            return false; // Signature invalid
        }

        return json_decode($this->base64UrlDecode($payload64), true);
    }

    /**
     * Initiate payment transaction
     */
    public function initTransaction($amount, $orderId, $serviceType = 'Luxury Goods Purchase') {
        $data = [
            'amount' => $amount,
            'serviceType' => $serviceType,
            'msisdn' => $this->merchantMsisdn,
            'orderId' => $orderId,
            'redirectUrl' => $this->redirectUrl,
            'iat' => time(),
            'exp' => time() + (4 * 3600)
        ];

        $token = $this->encodeJWT($data, $this->merchantSecret);

        if (!$this->isLive) {
            // Route to fake.php simulator
            $simUrl = (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] : '') . 
                      '/website/payment/fake.php?gateway=zaincash&action=init&amount=' . urlencode($amount) . '&order_id=' . urlencode($orderId);

            $ch = curl_init($simUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            curl_close($ch);

            $res = json_decode($response, true);
            if ($res && !empty($res['id'])) {
                return [
                    'success' => true,
                    'transaction_id' => $res['id'],
                    'token' => $token,
                    'redirect_url' => "fake.php?gateway=zaincash&payment_id=" . $res['id'] . "&amount=$amount&order_id=$orderId"
                ];
            }

            // In-process fallback
            $txId = 'ZC-' . rand(10000000, 99999999);
            return [
                'success' => true,
                'transaction_id' => $txId,
                'token' => $token,
                'redirect_url' => "fake.php?gateway=zaincash&payment_id=$txId&amount=$amount&order_id=$orderId"
            ];
        }

        // Live ZainCash API Call
        $postData = [
            'token' => $token,
            'merchantId' => $this->merchantId,
            'lang' => 'ar'
        ];

        $initEndpoint = $this->config['live']['init_url'];
        $ch = curl_init($initEndpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($result, true);
        if ($httpCode === 200 && !empty($json['id'])) {
            return [
                'success' => true,
                'transaction_id' => $json['id'],
                'token' => $token,
                'redirect_url' => $this->config['live']['pay_url'] . '?id=' . $json['id']
            ];
        }

        throw new Exception("ZainCash Transaction Init Failed: " . ($json['message'] ?? $result));
    }

    /**
     * Verify callback token
     */
    public function verifyReturnToken($token) {
        $decoded = $this->decodeJWT($token, $this->merchantSecret);
        if (!$decoded) {
            return ['success' => false, 'error' => 'Invalid JWT Signature'];
        }
        return [
            'success' => true,
            'status' => $decoded['status'] ?? 'unknown',
            'order_id' => $decoded['orderid'] ?? '',
            'transaction_id' => $decoded['id'] ?? '',
            'data' => $decoded
        ];
    }

    protected function base64UrlEncode($text) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }

    protected function base64UrlDecode($text) {
        $b64 = str_replace(['-', '_'], ['+', '/'], $text);
        return base64_decode($b64 . str_repeat('=', (4 - strlen($b64) % 4) % 4));
    }
}
