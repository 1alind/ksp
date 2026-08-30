<?php
/**
 * ZainCash Return / Redirect Handler
 */

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/zaincash.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: ../../checkout.php?error=no_token");
    exit;
}

$zain = new ZainCashPayment();
$result = $zain->verifyReturnToken($token);

if ($result['success'] && ($result['status'] === 'success' || $result['status'] === 'PAID')) {
    $orderId = $result['order_id'];
    $txId = $result['transaction_id'];

    // Update order status in orders.json
    $orders = get_orders();
    foreach ($orders as &$ord) {
        if ($ord['order_id'] === $orderId) {
            $ord['payment_status'] = 'Paid (ZainCash Verified)';
            $ord['payment_gateway_tx'] = $txId;
            $ord['order_status'] = 'Processing';
            break;
        }
    }
    file_put_contents(__DIR__ . '/../../database/orders.json', json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    header("Location: ../../track.php?order_id=" . urlencode($orderId) . "&paid=1");
    exit;
} else {
    header("Location: ../../checkout.php?error=payment_failed&status=" . urlencode($result['status'] ?? 'failed'));
    exit;
}
