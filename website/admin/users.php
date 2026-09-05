<?php
// Customer registration and customer accounts have been permanently removed.
// In accordance with data minimization, customer details are saved solely within individual orders for delivery purposes.
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
header('Location: /admin/orders.php');
exit;
