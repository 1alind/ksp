<?php
// Shared Admin Navigation Bar Component
$currentNavPage = $adminActive ?? 'dashboard';
$adminLang = $lang ?? $_SESSION['lang'] ?? $_COOKIE['aura_lang'] ?? 'en';
$langParam = '?lang=' . urlencode($adminLang);
?>
<div class="admin-tabs-nav" style="display:flex; justify-content:space-between; align-items:center; gap:12px; overflow-x:auto; padding-bottom:12px; margin-bottom:28px; border-bottom:1px solid var(--border-color); flex-wrap:wrap;">
    <div style="display:flex; gap:8px; overflow-x:auto; align-items:center;">
        <a href="/admin/index.php<?php echo $langParam; ?>" class="admin-tab-btn <?php echo $currentNavPage === 'dashboard' ? 'active' : ''; ?>" style="text-decoration:none;">
            📊 <?php echo adm_t('admin_nav_dashboard', 'Dashboard'); ?>
        </a>
        <a href="/admin/orders.php<?php echo $langParam; ?>" class="admin-tab-btn <?php echo $currentNavPage === 'orders' ? 'active' : ''; ?>" style="text-decoration:none;">
            🚚 <?php echo adm_t('admin_nav_orders', 'Orders'); ?> (<?php echo count($ordersList ?? []); ?>)
        </a>
        <a href="/admin/products.php<?php echo $langParam; ?>" class="admin-tab-btn <?php echo $currentNavPage === 'products' ? 'active' : ''; ?>" style="text-decoration:none;">
            💎 <?php echo adm_t('admin_nav_products', 'Products'); ?> (<?php echo count($productsList ?? []); ?>)
        </a>
        <a href="/admin/payments.php<?php echo $langParam; ?>" class="admin-tab-btn <?php echo $currentNavPage === 'payments' ? 'active' : ''; ?>" style="text-decoration:none;">
            💳 <?php echo adm_t('admin_nav_payments', 'Payment Gateways'); ?>
        </a>
        <a href="/admin/inquiries.php<?php echo $langParam; ?>" class="admin-tab-btn <?php echo $currentNavPage === 'inquiries' ? 'active' : ''; ?>" style="text-decoration:none;">
            💬 <?php echo adm_t('admin_nav_inquiries', 'Inquiries'); ?> (<?php echo count($inquiriesList ?? []); ?>)
        </a>
        <a href="/admin/branding.php<?php echo $langParam; ?>" class="admin-tab-btn <?php echo $currentNavPage === 'branding' ? 'active' : ''; ?>" style="text-decoration:none;">
            🎨 <?php echo adm_t('admin_nav_branding', 'Brand & Settings'); ?>
        </a>
    </div>

    <!-- Quick Language Selector Pill inside Admin -->
    <div class="admin-lang-selector-bar" style="display:flex; align-items:center; gap:6px; background:var(--bg-surface); padding:4px 10px; border-radius:20px; border:1px solid var(--border-color); font-size:12px; white-space:nowrap;">
        <span style="color:var(--text-secondary); font-size:12px; display:inline-flex; align-items:center; gap:4px;">
            <span>🌐</span>
            <span style="font-size:11px; font-weight:600;"><?php echo adm_t('admin_language', 'Language'); ?>:</span>
        </span>
        <a href="?lang=en" class="admin-lang-btn <?php echo $adminLang === 'en' ? 'active' : ''; ?>" data-lang-set="en" onclick="window.changeSiteLanguage('en', event);" style="text-decoration:none; cursor:pointer; padding:4px 10px; border-radius:12px; font-weight:700; font-size:12px; color:<?php echo $adminLang === 'en' ? 'var(--accent-gold)' : 'var(--text-secondary)'; ?>; background:<?php echo $adminLang === 'en' ? 'var(--bg-card)' : 'transparent'; ?>; transition:all 0.2s ease;">English</a>
        <span style="color:var(--border-color);">|</span>
        <a href="?lang=ar" class="admin-lang-btn <?php echo $adminLang === 'ar' ? 'active' : ''; ?>" data-lang-set="ar" onclick="window.changeSiteLanguage('ar', event);" style="text-decoration:none; cursor:pointer; padding:4px 10px; border-radius:12px; font-weight:700; font-size:12px; color:<?php echo $adminLang === 'ar' ? 'var(--accent-gold)' : 'var(--text-secondary)'; ?>; background:<?php echo $adminLang === 'ar' ? 'var(--bg-card)' : 'transparent'; ?>; transition:all 0.2s ease;">العربية</a>
        <span style="color:var(--border-color);">|</span>
        <a href="?lang=ku" class="admin-lang-btn <?php echo $adminLang === 'ku' ? 'active' : ''; ?>" data-lang-set="ku" onclick="window.changeSiteLanguage('ku', event);" style="text-decoration:none; cursor:pointer; padding:4px 10px; border-radius:12px; font-weight:700; font-size:12px; color:<?php echo $adminLang === 'ku' ? 'var(--accent-gold)' : 'var(--text-secondary)'; ?>; background:<?php echo $adminLang === 'ku' ? 'var(--bg-card)' : 'transparent'; ?>; transition:all 0.2s ease;">کوردی (بادینی)</a>
    </div>
</div>

