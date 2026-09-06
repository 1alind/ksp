<?php
// Inquiries and Contact concierge have been permanently removed.
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
header('Location: /admin/orders.php');
exit;
