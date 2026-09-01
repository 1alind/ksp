<?php
/**
 * ZainCash Return / Redirect Handler
 * Direct MySQL database updates (No JSON reliance)
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

    // Update order status in MySQL database
    $pdo = get_mysql_pdo();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'Paid (ZainCash Verified)', 
                    order_status = 'Processing',
                    tracking_code = IF(tracking_code IS NULL OR tracking_code = '', :tx, tracking_code)
                WHERE order_id = :oid
            ");
            $stmt->execute([
                ':tx' => $txId,
                ':oid' => $orderId
            ]);
        } catch (Exception $e) {
            // Log error silently
        }
    }

    header("Location: ../../track.php?order_id=" . urlencode($orderId) . "&paid=1");
    exit;
} else {
    header("Location: ../../checkout.php?error=payment_failed&status=" . urlencode($result['status'] ?? 'failed'));
    exit;
}
?>
