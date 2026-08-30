<?php
/**
 * AURA Luxury E-Commerce — Payment Gateway Simulation Manager (PHP Adapter)
 * 
 * Bridges to modular SDK classes in /website/payment/:
 * - First Iraqi Bank (FIB): /website/payment/fib/fib.php
 * - ZainCash: /website/payment/zaincash/zaincash.php
 * - FastPay: /website/payment/fastpay/fastpay.php
 * - Universal Simulator Endpoint: /website/payment/fake.php
 */

require_once __DIR__ . '/../payment/fib/fib.php';
require_once __DIR__ . '/../payment/zaincash/zaincash.php';
require_once __DIR__ . '/../payment/fastpay/fastpay.php';

class PaymentGatewaySimulator {

    public static function generateFIBToken($clientId = '', $clientSecret = '', $mode = 'test') {
        try {
            $fib = new FibPayment();
            $token = $fib->getAccessToken();
            return [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 86400,
                'scope' => 'payments:write payments:read accounts:read',
                'created_at' => date('Y-m-d\TH:i:s\Z')
            ];
        } catch (Exception $e) {
            return [
                'access_token' => 'fib_tok_' . bin2hex(random_bytes(16)),
                'token_type' => 'Bearer',
                'expires_in' => 86400
            ];
        }
    }

    public static function createFIBPayment($amount, $currency = 'IQD', $ref = '', $callbackUrl = '') {
        try {
            $fib = new FibPayment();
            return $fib->createPayment($amount, $currency, $ref);
        } catch (Exception $e) {
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
    }

    public static function initZainCash($amount, $orderId, $merchantId = '', $secretKey = '', $msisdn = '9647835077893') {
        try {
            $zain = new ZainCashPayment();
            return $zain->initTransaction($amount, $orderId);
        } catch (Exception $e) {
            $txId = 'ZC-' . rand(10000000, 99999999);
            return [
                'success' => true,
                'transaction_id' => $txId,
                'token' => 'zc_jwt_sim_' . bin2hex(random_bytes(16)),
                'redirect_url' => "payment/fake.php?gateway=zaincash&payment_id=$txId&amount=$amount&order_id=$orderId",
                'status' => 'pending'
            ];
        }
    }

    public static function initFastPay($amount, $orderId, $storeId = 'FP_STORE_94821') {
        try {
            $fastpay = new FastPayPayment();
            return $fastpay->initiatePayment($amount, $orderId);
        } catch (Exception $e) {
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
}

