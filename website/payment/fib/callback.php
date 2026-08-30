<?php
/**
 * First Iraqi Bank (FIB) Webhook & Callback Receiver
 */

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/fib.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid webhook payload']);
    exit;
}

$paymentId = $data['id'] ?? $data['paymentId'] ?? '';
$status = strtoupper($data['status'] ?? '');

if ($paymentId && $status === 'PAID') {
    // Look up orders matching payment gateway tx
    $orders = get_orders();
    foreach ($orders as &$ord) {
        if (isset($ord['payment_gateway_tx']) && strpos($ord['payment_gateway_tx'], $paymentId) !== false) {
            $ord['payment_status'] = 'Paid (FIB Verified)';
            $ord['order_status'] = 'Processing';
            break;
        }
    }
    file_put_contents(__DIR__ . '/../../database/orders.json', json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

http_response_code(200);
echo json_encode(['received' => true, 'status' => 'acknowledged']);
