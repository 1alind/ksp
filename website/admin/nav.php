<?php
// Shared Admin Navigation Bar Component
$currentNavPage = $adminActive ?? 'dashboard';
?>
<div class="admin-tabs-nav" style="display:flex; gap:10px; overflow-x:auto; padding-bottom:10px; margin-bottom:28px; border-bottom:1px solid var(--border-color);">
    <a href="/admin/index.php" class="admin-tab-btn <?php echo $currentNavPage === 'dashboard' ? 'active' : ''; ?>" style="text-decoration:none;">
        📊 Dashboard
    </a>
    <a href="/admin/orders.php" class="admin-tab-btn <?php echo $currentNavPage === 'orders' ? 'active' : ''; ?>" style="text-decoration:none;">
        🚚 Orders (<?php echo count($ordersList); ?>)
    </a>
    <a href="/admin/products.php" class="admin-tab-btn <?php echo $currentNavPage === 'products' ? 'active' : ''; ?>" style="text-decoration:none;">
        💎 Products (<?php echo count($productsList); ?>)
    </a>
    <a href="/admin/payments.php" class="admin-tab-btn <?php echo $currentNavPage === 'payments' ? 'active' : ''; ?>" style="text-decoration:none;">
        💳 Payment Gateways
    </a>
    <a href="/admin/users.php" class="admin-tab-btn <?php echo $currentNavPage === 'users' ? 'active' : ''; ?>" style="text-decoration:none;">
        👥 Customers (<?php echo count($usersList); ?>)
    </a>
    <a href="/admin/inquiries.php" class="admin-tab-btn <?php echo $currentNavPage === 'inquiries' ? 'active' : ''; ?>" style="text-decoration:none;">
        💬 Inquiries (<?php echo count($inquiriesList); ?>)
    </a>
    <a href="/admin/branding.php" class="admin-tab-btn <?php echo $currentNavPage === 'branding' ? 'active' : ''; ?>" style="text-decoration:none;">
        🎨 Brand & Settings
    </a>
</div>
