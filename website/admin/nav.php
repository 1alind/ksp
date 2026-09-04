<?php
// Shared Admin Navigation Bar Component
$currentNavPage = $adminActive ?? 'dashboard';
$adminLang = $lang ?? $_SESSION['lang'] ?? $_COOKIE['aura_lang'] ?? 'en';
?>
<div class="admin-tabs-nav" style="display:flex; justify-content:space-between; align-items:center; gap:12px; overflow-x:auto; padding-bottom:12px; margin-bottom:28px; border-bottom:1px solid var(--border-color); flex-wrap:wrap;">
    <div style="display:flex; gap:8px; overflow-x:auto; align-items:center;">
        <a href="/admin/index.php" class="admin-tab-btn <?php echo $currentNavPage === 'dashboard' ? 'active' : ''; ?>" style="text-decoration:none;">
            📊 <?php echo adm_t('admin_nav_dashboard', 'Dashboard'); ?>
        </a>
        <a href="/admin/orders.php" class="admin-tab-btn <?php echo $currentNavPage === 'orders' ? 'active' : ''; ?>" style="text-decoration:none;">
            🚚 <?php echo adm_t('admin_nav_orders', 'Orders'); ?> (<?php echo count($ordersList ?? []); ?>)
        </a>
        <a href="/admin/products.php" class="admin-tab-btn <?php echo $currentNavPage === 'products' ? 'active' : ''; ?>" style="text-decoration:none;">
            💎 <?php echo adm_t('admin_nav_products', 'Products'); ?> (<?php echo count($productsList ?? []); ?>)
        </a>
        <a href="/admin/payments.php" class="admin-tab-btn <?php echo $currentNavPage === 'payments' ? 'active' : ''; ?>" style="text-decoration:none;">
            💳 <?php echo adm_t('admin_nav_payments', 'Payment Gateways'); ?>
        </a>
        <a href="/admin/users.php" class="admin-tab-btn <?php echo $currentNavPage === 'users' ? 'active' : ''; ?>" style="text-decoration:none;">
            👥 <?php echo adm_t('admin_nav_users', 'Customers'); ?> (<?php echo count($usersList ?? []); ?>)
        </a>
        <a href="/admin/inquiries.php" class="admin-tab-btn <?php echo $currentNavPage === 'inquiries' ? 'active' : ''; ?>" style="text-decoration:none;">
            💬 <?php echo adm_t('admin_nav_inquiries', 'Inquiries'); ?> (<?php echo count($inquiriesList ?? []); ?>)
        </a>
        <a href="/admin/branding.php" class="admin-tab-btn <?php echo $currentNavPage === 'branding' ? 'active' : ''; ?>" style="text-decoration:none;">
            🎨 <?php echo adm_t('admin_nav_branding', 'Brand & Settings'); ?>
        </a>
    </div>

    <!-- Quick Language Selector Pill inside Admin -->
    <div style="display:flex; align-items:center; gap:6px; background:var(--bg-surface); padding:4px 8px; border-radius:20px; border:1px solid var(--border-color); font-size:12px; white-space:nowrap;">
        <span style="color:var(--text-secondary); font-size:11px;">🌐</span>
        <a href="?lang=en" style="text-decoration:none; padding:3px 8px; border-radius:12px; font-weight:700; color:<?php echo $adminLang === 'en' ? 'var(--accent-gold)' : 'var(--text-secondary)'; ?>; background:<?php echo $adminLang === 'en' ? 'var(--bg-card)' : 'transparent'; ?>;">EN</a>
        <span style="color:var(--border-color);">|</span>
        <a href="?lang=ar" style="text-decoration:none; padding:3px 8px; border-radius:12px; font-weight:700; color:<?php echo $adminLang === 'ar' ? 'var(--accent-gold)' : 'var(--text-secondary)'; ?>; background:<?php echo $adminLang === 'ar' ? 'var(--bg-card)' : 'transparent'; ?>;">العربية</a>
        <span style="color:var(--border-color);">|</span>
        <a href="?lang=ku" style="text-decoration:none; padding:3px 8px; border-radius:12px; font-weight:700; color:<?php echo $adminLang === 'ku' ? 'var(--accent-gold)' : 'var(--text-secondary)'; ?>; background:<?php echo $adminLang === 'ku' ? 'var(--bg-card)' : 'transparent'; ?>;">کوردی</a>
    </div>
</div>

