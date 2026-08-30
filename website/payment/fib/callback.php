<?php
/**
 * First Iraqi Bank (FIB) Webhook & Callback Receiver
 * Direct MySQL database updates (No JSON reliance)
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
    $pdo = get_mysql_pdo();
    if ($pdo) {
        try {
            // Update order status in MySQL database
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'Paid (FIB Verified)', 
                    order_status = 'Processing' 
                WHERE dispatch_notes LIKE :pid OR tracking_code LIKE :pid2 OR order_id LIKE :pid3
            ");
            $stmt->execute([
                ':pid' => "%$paymentId%",
                ':pid2' => "%$paymentId%",
                ':pid3' => "%$paymentId%"
            ]);
        } catch (Exception $e) {
            // Log error silently
        }
    }
}

http_response_code(200);
echo json_encode(['received' => true, 'status' => 'acknowledged']);
?>
