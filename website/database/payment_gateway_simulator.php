<?php
/**
 * AURA Luxury E-Commerce — Payment Gateway Simulation Manager (PHP Adapter)
 * 
 * Manages simulation of:
 * - First Iraqi Bank (FIB) OAuth2 & QR Generation
 * - ZainCash JWT Signing & Verification
 * - FastPay QR Payment Simulation
 * 
 * To switch to Live Banks:
 * Set $USE_LIVE_GATEWAYS = true in store settings or pass live merchant credentials.
 */

class PaymentGatewaySimulator {

    public static function generateFIBToken($clientId = '', $clientSecret = '', $mode = 'test') {
        $token = 'fib_tok_' . $mode . '_' . bin2hex(random_bytes(24));
        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'scope' => 'payments:write payments:read accounts:read',
            'created_at' => date('Y-m-d\TH:i:s\Z')
        ];
    }

    public static function createFIBPayment($amount, $currency = 'IQD', $ref = '', $callbackUrl = '') {
        $paymentId = 'FIB-' . strtoupper(bin2hex(random_bytes(5)));
        $qrData = 'fib://pay?pid=' . $paymentId . '&amt=' . intval($amount) . '&cur=' . $currency . '&ref=' . urlencode($ref) . '&t=' . time();
        
        return [
            'success' => true,
            'payment_id' => $paymentId,
            'qr_code' => $qrData,
            'readable_code' => implode('-', str_split($paymentId, 4)),
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'UNPAID',
            'valid_until' => date('Y-m-d\TH:i:s\Z', time() + 900),
            'qr_image_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrData)
        ];
    }

    public static function initZainCash($amount, $orderId, $merchantId = '', $secretKey = '', $msisdn = '9647835077893') {
        $txId = 'ZC-' . rand(10000000, 99999999);
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'amount' => $amount,
            'serviceType' => 'Luxury Goods Purchase',
            'orderId' => $orderId,
            'msisdn' => $msisdn,
            'merchantId' => $merchantId ?: '5ff6561142e569f1b470291e',
            'iat' => time(),
            'exp' => time() + 3600
        ]));

        $secret = $secretKey ?: 'aura_zaincash_secret_2026';
        $signature = hash_hmac('sha256', "$header.$payload", $secret, true);
        $token = "$header.$payload." . base64_encode($signature);

        return [
            'success' => true,
            'transaction_id' => $txId,
            'token' => $token,
            'redirect_url' => "https://api.zaincash.iq/transaction/pay?id=$txId&token=" . urlencode($token),
            'status' => 'pending'
        ];
    }

    public static function initFastPay($amount, $orderId, $storeId = 'FP_STORE_94821') {
        $txId = 'FP-' . rand(100000, 999999);
        $qrData = "fastpay://merchant_pay?store=$storeId&amount=$amount&order=$orderId&tx=$txId";
        return [
            'success' => true,
            'transaction_id' => $txId,
            'qr_token' => $qrData,
            'qr_image_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrData),
            'amount' => $amount,
            'currency' => 'IQD',
            'status' => 'pending'
        ];
    }
}
