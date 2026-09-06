<?php
// Contact page has been permanently removed.
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
header('Location: /index.php');
exit;
