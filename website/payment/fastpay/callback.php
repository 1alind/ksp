<?php
/**
 * FastPay IPN (Instant Payment Notification) Webhook Receiver
 */

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/fastpay.php';

$transactionId = $_POST['transaction_id'] ?? $_GET['transaction_id'] ?? '';
$orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? '';
$status = strtolower($_POST['status'] ?? $_GET['status'] ?? '');

if ($status === 'success' && !empty($orderId)) {
    $orders = get_orders();
    foreach ($orders as &$ord) {
        if ($ord['order_id'] === $orderId) {
            $ord['payment_status'] = 'Paid (FastPay Verified)';
            $ord['payment_gateway_tx'] = $transactionId ?: ('FP-TX-' . rand(10000, 99999));
            $ord['order_status'] = 'Processing';
            break;
        }
    }
    file_put_contents(__DIR__ . '/../../database/orders.json', json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

http_response_code(200);
echo json_encode(['received' => true, 'status' => 'acknowledged']);
