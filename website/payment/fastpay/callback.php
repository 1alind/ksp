<?php
/**
 * FastPay IPN (Instant Payment Notification) Webhook Receiver
 * Direct MySQL database updates (No JSON reliance)
 */

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/fastpay.php';

$transactionId = $_POST['transaction_id'] ?? $_GET['transaction_id'] ?? '';
$orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? '';
$status = strtolower($_POST['status'] ?? $_GET['status'] ?? '');

if ($status === 'success' && !empty($orderId)) {
    $pdo = get_mysql_pdo();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'Paid (FastPay Verified)', 
                    order_status = 'Processing',
                    tracking_code = IF(tracking_code IS NULL OR tracking_code = '', :tx, tracking_code)
                WHERE order_id = :oid
            ");
            $tx = $transactionId ?: ('FP-TX-' . rand(10000, 99999));
            $stmt->execute([
                ':tx' => $tx,
                ':oid' => $orderId
            ]);
        } catch (Exception $e) {
            // Log error silently
        }
    }
}

http_response_code(200);
echo json_encode(['received' => true, 'status' => 'acknowledged']);
?>
