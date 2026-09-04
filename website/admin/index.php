<?php
$pageTitle = 'Executive Command Suite | AURA Luxury Admin';
$adminActive = 'dashboard';
$ordersDb = json_decode(file_get_contents(__DIR__ . '/../database/orders.json'), true);
$ordersList = $ordersDb['orders'] ?? [];
$productsDb = json_decode(file_get_contents(__DIR__ . '/../database/products.json'), true);
$productsList = $productsDb['products'] ?? [];
$usersDb = json_decode(file_get_contents(__DIR__ . '/../database/users.json'), true);
$usersList = $usersDb['users'] ?? [];
$inquiriesDb = json_decode(file_get_contents(__DIR__ . '/../database/inquiries.json'), true);
$inquiriesList = $inquiriesDb['inquiries'] ?? [];
$settingsDb = json_decode(file_get_contents(__DIR__ . '/../database/settings.json'), true);

$pendingCount = 0;
$shippedCount = 0;
$deliveredCount = 0;
$totalRevenueIqd = 0;

foreach ($ordersList as $o) {
    $totalRevenueIqd += ($o['total'] ?? 0);
    $st = $o['order_status'] ?? 'Pending';
    if ($st === 'Pending' || $st === 'Processing') $pendingCount++;
    if ($st === 'Shipped' || $st === 'Out for Delivery') $shippedCount++;
    if ($st === 'Delivered') $deliveredCount++;
}

$fib = $settingsDb['gateways']['fib'] ?? [];
$zain = $settingsDb['gateways']['zaincash'] ?? [];
$fastpay = $settingsDb['gateways']['fastpay'] ?? [];

$activePage = 'admin';
require_once __DIR__ . '/../header.php';
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">✦ <?php echo adm_t('admin_nav_dashboard', 'Dashboard'); ?></span>
            <h1 class="page-banner-title"><?php echo adm_t('admin_dashboard_title', 'Executive Dashboard & Analytics'); ?></h1>
            <p class="page-banner-subtitle">
                <?php echo adm_t('admin_dashboard_subtitle', 'High-level operational overview, live payment telemetry, rapid dispatch radar, and administrative shortcuts.'); ?>
            </p>
        </div>
    </div>
</div>

<section class="admin-section" style="padding: 40px 0 80px;">
    <div class="container">

        <!-- Unified Admin Navigation Bar -->
        <?php require_once __DIR__ . '/nav.php'; ?>

        <!-- Metric KPI Cards -->
        <div class="admin-metrics-grid" style="margin-bottom:28px;">
            <div class="admin-metric-card">
                <span class="m-icon">💰</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_metric_revenue', 'Total Settled Revenue'); ?></span>
                    <strong class="m-value text-primary"><?php echo number_format($totalRevenueIqd); ?> IQD</strong>
                    <span class="iqd-price-pill"><?php echo adm_t('admin_metric_all_iqd', 'All Orders in Iraqi Dinar'); ?></span>
                </div>
            </div>

            <div class="admin-metric-card">
                <span class="m-icon">📦</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_metric_shipments', 'Active Shipments'); ?></span>
                    <strong class="m-value"><?php echo count($ordersList); ?> <?php echo adm_t('admin_nav_orders', 'Orders'); ?></strong>
                    <span class="iqd-price-pill"><?php echo sprintf(adm_t('admin_metric_transit_delivered', '%s in transit • %s delivered'), $shippedCount, $deliveredCount); ?></span>
                </div>
            </div>

            <div class="admin-metric-card">
                <span class="m-icon">💎</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_metric_pieces', 'Boutique Pieces'); ?></span>
                    <strong class="m-value"><?php echo count($productsList); ?> <?php echo adm_t('admin_nav_products', 'Pieces'); ?></strong>
                    <span class="iqd-price-pill"><?php echo adm_t('admin_metric_catalog_active', 'Luxury Catalog Active'); ?></span>
                </div>
            </div>

            <div class="admin-metric-card">
                <span class="m-icon">👥</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_metric_customers', 'Customer Directory'); ?></span>
                    <strong class="m-value"><?php echo count($usersList); ?> <?php echo adm_t('admin_nav_users', 'Clients'); ?></strong>
                    <span class="iqd-price-pill"><?php echo sprintf(adm_t('admin_metric_inquiries_count', '%s Concierge Inquiries'), count($inquiriesList)); ?></span>
                </div>
            </div>
        </div>

        <!-- Admin Quick Navigation Jump Grid -->
        <div style="margin-bottom:32px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="margin:0; font-size:18px; font-weight:800; color:var(--text-primary);">⚡ <?php echo adm_t('admin_workspaces_title', 'Dedicated Admin Workspaces'); ?></h3>
                <span class="text-muted" style="font-size:12.5px;"><?php echo adm_t('admin_workspaces_subtitle', 'Each section is isolated on its own page for maximum speed and focus'); ?></span>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px;">
                
                <!-- Card 1: Orders -->
                <a href="/admin/orders.php" style="text-decoration:none; color:inherit; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:28px;">🚚</span>
                            <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:800;"><?php echo count($ordersList); ?> <?php echo adm_t('admin_nav_orders', 'Orders'); ?></span>
                        </div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--text-primary);"><?php echo adm_t('admin_orders_title', 'Orders & Logistics Radar'); ?></h4>
                        <p class="text-muted" style="margin:0; font-size:12.5px; line-height:1.5;"><?php echo adm_t('admin_workspace_orders_desc', 'Live dispatch radar, Kurdistan courier updates, WhatsApp status triggers, and invoice prints.'); ?></p>
                    </div>
                    <div style="margin-top:16px; color:var(--accent-gold); font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        <?php echo adm_t('admin_btn_open_workspace', 'Open Workspace'); ?> →
                    </div>
                </a>

                <!-- Card 2: Products -->
                <a href="/admin/products.php" style="text-decoration:none; color:inherit; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:28px;">💎</span>
                            <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:800;"><?php echo count($productsList); ?> <?php echo adm_t('admin_nav_products', 'Pieces'); ?></span>
                        </div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--text-primary);"><?php echo adm_t('admin_products_title', 'Products & Inventory Atelier'); ?></h4>
                        <p class="text-muted" style="margin:0; font-size:12.5px; line-height:1.5;"><?php echo adm_t('admin_workspace_products_desc', 'Catalog management, multi-color variations, luxury badges, and stock level controls.'); ?></p>
                    </div>
                    <div style="margin-top:16px; color:var(--accent-gold); font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        <?php echo adm_t('admin_btn_open_workspace', 'Open Workspace'); ?> →
                    </div>
                </a>

                <!-- Card 3: Payments -->
                <a href="/admin/payments.php" style="text-decoration:none; color:inherit; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:28px;">💳</span>
                            <span class="badge-tag" style="background:rgba(34,197,94,0.15); color:#22c55e; font-weight:800;">FIB, FastPay & ZainCash</span>
                        </div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--text-primary);"><?php echo adm_t('admin_payments_title', 'Payment Gateways & Live Telemetry'); ?></h4>
                        <p class="text-muted" style="margin:0; font-size:12.5px; line-height:1.5;"><?php echo adm_t('admin_workspace_payments_desc', 'Configure FIB, FastPay, ZainCash, and Cash on Delivery with test mode tools.'); ?></p>
                    </div>
                    <div style="margin-top:16px; color:var(--accent-gold); font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        <?php echo adm_t('admin_btn_open_workspace', 'Open Workspace'); ?> →
                    </div>
                </a>

                <!-- Card 4: Customers -->
                <a href="/admin/users.php" style="text-decoration:none; color:inherit; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:28px;">👥</span>
                            <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:800;"><?php echo count($usersList); ?> <?php echo adm_t('admin_nav_users', 'Registered'); ?></span>
                        </div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--text-primary);"><?php echo adm_t('admin_users_title', 'Customer Directory & VIP Accounts'); ?></h4>
                        <p class="text-muted" style="margin:0; font-size:12.5px; line-height:1.5;"><?php echo adm_t('admin_workspace_users_desc', 'Customer ledger, lifetime spend tracking, purchase frequency, and VIP classifications.'); ?></p>
                    </div>
                    <div style="margin-top:16px; color:var(--accent-gold); font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        <?php echo adm_t('admin_btn_open_workspace', 'Open Workspace'); ?> →
                    </div>
                </a>

                <!-- Card 5: Inquiries -->
                <a href="/admin/inquiries.php" style="text-decoration:none; color:inherit; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:28px;">💬</span>
                            <span class="badge-tag" style="background:rgba(59,130,246,0.15); color:#60a5fa; font-weight:800;"><?php echo count($inquiriesList); ?> <?php echo adm_t('admin_nav_inquiries', 'Active'); ?></span>
                        </div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--text-primary);"><?php echo adm_t('admin_inquiries_title', 'VIP Inquiries & Concierge Desk'); ?></h4>
                        <p class="text-muted" style="margin:0; font-size:12.5px; line-height:1.5;"><?php echo adm_t('admin_workspace_inquiries_desc', 'Concierge message inbox, direct WhatsApp responses, and post-delivery issue resolution.'); ?></p>
                    </div>
                    <div style="margin-top:16px; color:var(--accent-gold); font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        <?php echo adm_t('admin_btn_open_workspace', 'Open Workspace'); ?> →
                    </div>
                </a>

                <!-- Card 6: Branding & Settings -->
                <a href="/admin/branding.php" style="text-decoration:none; color:inherit; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:28px;">🎨</span>
                            <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:800;"><?php echo adm_t('admin_branding_badge_global', 'Global'); ?></span>
                        </div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--text-primary);"><?php echo adm_t('admin_branding_title', 'Brand Identity & Store Settings'); ?></h4>
                        <p class="text-muted" style="margin:0; font-size:12.5px; line-height:1.5;"><?php echo adm_t('admin_workspace_branding_desc', 'Boutique identity, logo styling, delivery fee rules (Duhok vs Other), and announcement banners.'); ?></p>
                    </div>
                    <div style="margin-top:16px; color:var(--accent-gold); font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        <?php echo adm_t('admin_btn_open_workspace', 'Open Workspace'); ?> →
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Orders Stream -->
        <div class="admin-table-card">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:12px;">
                <div>
                    <h3 class="admin-card-title" style="margin:0; font-size:17px;">📦 <?php echo adm_t('admin_recent_orders', 'Recent Incoming Orders'); ?></h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_workspace_orders_desc', 'Latest client checkouts and shipments awaiting fulfillment.'); ?></p>
                </div>
                <a href="/admin/orders.php" class="btn btn-primary btn-luxury btn-sm">
                    <?php echo adm_t('admin_view_all_orders', 'View All Orders'); ?> (<?php echo count($ordersList); ?>) →
                </a>
            </div>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?php echo adm_t('admin_order_col_id', 'Order ID'); ?></th>
                            <th><?php echo adm_t('admin_inquiry_col_date', 'Date'); ?></th>
                            <th><?php echo adm_t('admin_order_col_customer', 'Customer'); ?> & <?php echo adm_t('admin_order_col_city', 'City'); ?></th>
                            <th><?php echo adm_t('admin_order_col_total', 'Total Amount'); ?> (IQD)</th>
                            <th><?php echo adm_t('admin_nav_payments', 'Payment'); ?></th>
                            <th><?php echo adm_t('admin_order_col_status', 'Status'); ?></th>
                            <th><?php echo adm_t('admin_order_col_actions', 'Action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recentOrders = array_slice($ordersList, 0, 5);
                        foreach ($recentOrders as $ord): 
                            $ordTot = $ord['total'] ?? 0;
                            $currStatus = $ord['order_status'] ?? 'Pending';
                            $statusKey = 'admin_status_' . strtolower(str_replace(' ', '_', $currStatus));
                        ?>
                            <tr>
                                <td><strong><a href="/track.php?order_id=<?php echo urlencode($ord['order_id']); ?>"><?php echo htmlspecialchars($ord['order_id']); ?></a></strong></td>
                                <td><small><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></small></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($ord['customer_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($ord['city']); ?></small>
                                </td>
                                <td>
                                    <strong class="text-primary font-bold"><?php echo number_format($ordTot); ?> IQD</strong>
                                </td>
                                <td>
                                    <span class="badge-tag"><?php echo htmlspecialchars($ord['payment_method']); ?></span>
                                </td>
                                <td>
                                    <span class="badge-tag" style="font-weight:700; background:var(--bg-subtle);">
                                        <?php echo htmlspecialchars(adm_t($statusKey, $currStatus)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/admin/orders.php" class="btn btn-outline btn-xs"><?php echo adm_t('admin_btn_manage', 'Manage'); ?> →</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../footer.php'; ?>
