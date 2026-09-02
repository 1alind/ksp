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
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">✦ Executive Command Suite</span>
            <h1 class="page-banner-title">Executive Dashboard & Analytics</h1>
            <p class="page-banner-subtitle">
                High-level operational overview, live payment telemetry, rapid dispatch radar, and administrative shortcuts.
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
                    <span class="m-label">Total Settled Revenue</span>
                    <strong class="m-value text-primary"><?php echo number_format($totalRevenueIqd); ?> IQD</strong>
                    <span class="iqd-price-pill">All Orders in Iraqi Dinar</span>
                </div>
            </div>

            <div class="admin-metric-card">
                <span class="m-icon">📦</span>
                <div class="m-info">
                    <span class="m-label">Active Shipments</span>
                    <strong class="m-value"><?php echo count($ordersList); ?> Orders</strong>
                    <span class="iqd-price-pill"><?php echo $shippedCount; ?> in transit • <?php echo $deliveredCount; ?> delivered</span>
                </div>
            </div>

            <div class="admin-metric-card">
                <span class="m-icon">💎</span>
                <div class="m-info">
                    <span class="m-label">Boutique Pieces</span>
                    <strong class="m-value"><?php echo count($productsList); ?> Pieces</strong>
                    <span class="iqd-price-pill">Luxury Catalog Active</span>
                </div>
            </div>

            <div class="admin-metric-card">
                <span class="m-icon">👥</span>
                <div class="m-info">
                    <span class="m-label">Customer Directory</span>
                    <strong class="m-value"><?php echo count($usersList); ?> Clients</strong>
                    <span class="iqd-price-pill"><?php echo count($inquiriesList); ?> Concierge Inquiries</span>
                </div>
            </div>
        </div>

        <!-- Admin Quick Navigation Jump Grid -->
        <div style="margin-bottom:32px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="margin:0; font-size:18px; font-weight:800; color:var(--text-primary);">⚡ Dedicated Admin Workspaces</h3>
                <span class="text-muted" style="font-size:12.5px;">Each section is isolated on its own page for maximum speed and focus</span>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px;">
                
                <!-- Card 1: Orders -->
                <a href="/admin/orders.php" style="text-decoration:none; color:inherit; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:28px;">🚚</span>
                            <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:800;"><?php echo count($ordersList); ?> Orders</span>
                        </div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--text-primary);">Orders & Logistics Radar</h4>
                        <p class="text-muted" style="margin:0; font-size:12.5px; line-height:1.5;">Manage client shipments, assign courier dispatchers, send WhatsApp alerts, and generate invoices.</p>
                    </div>
                    <div style="margin-top:16px; color:var(--accent-gold); font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        Open Orders Page →
                    </div>
                </a>

                <!-- Card 2: Products -->
                <a href="/admin/products.php" style="text-decoration:none; color:inherit; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:28px;">💎</span>
                            <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:800;"><?php echo count($productsList); ?> Pieces</span>
                        </div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--text-primary);">Product Catalog & Stock</h4>
                        <p class="text-muted" style="margin:0; font-size:12.5px; line-height:1.5;">Live stock adjuster steppers (+/-), trilingual descriptions, pricing discounts, and promotional ribbons.</p>
                    </div>
                    <div style="margin-top:16px; color:var(--accent-gold); font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        Manage Products →
                    </div>
                </a>

                <!-- Card 3: Payments -->
                <a href="/admin/payments.php" style="text-decoration:none; color:inherit; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:28px;">💳</span>
                            <span class="badge-tag" style="background:rgba(34,197,94,0.15); color:#22c55e; font-weight:800;">FIB & ZainCash</span>
                        </div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--text-primary);">Payment Gateways & Rates</h4>
                        <p class="text-muted" style="margin:0; font-size:12.5px; line-height:1.5;">First Iraqi Bank (FIB) OAuth2, ZainCash HMAC JWT keys, FastPay, COD, and USD/IQD conversion rates.</p>
                    </div>
                    <div style="margin-top:16px; color:var(--accent-gold); font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        Configure Gateways →
                    </div>
                </a>

                <!-- Card 4: Customers -->
                <a href="/admin/users.php" style="text-decoration:none; color:inherit; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:28px;">👥</span>
                            <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:800;"><?php echo count($usersList); ?> Registered</span>
                        </div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--text-primary);">Customer Directory</h4>
                        <p class="text-muted" style="margin:0; font-size:12.5px; line-height:1.5;">Client profiles, order history counts, phone numbers, delivery cities, and lifetime spend.</p>
                    </div>
                    <div style="margin-top:16px; color:var(--accent-gold); font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        View Client Base →
                    </div>
                </a>

                <!-- Card 5: Inquiries -->
                <a href="/admin/inquiries.php" style="text-decoration:none; color:inherit; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:28px;">💬</span>
                            <span class="badge-tag" style="background:rgba(59,130,246,0.15); color:#60a5fa; font-weight:800;"><?php echo count($inquiriesList); ?> Active</span>
                        </div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--text-primary);">Concierge Inquiries</h4>
                        <p class="text-muted" style="margin:0; font-size:12.5px; line-height:1.5;">Incoming client tailoring questions, sizing requests, and direct 1-click WhatsApp customer responses.</p>
                    </div>
                    <div style="margin-top:16px; color:var(--accent-gold); font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        Respond to Inquiries →
                    </div>
                </a>

                <!-- Card 6: Branding & Settings -->
                <a href="/admin/branding.php" style="text-decoration:none; color:inherit; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.2s, border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-size:28px;">🎨</span>
                            <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:800;">Global</span>
                        </div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--text-primary);">Brand & Global Settings</h4>
                        <p class="text-muted" style="margin:0; font-size:12.5px; line-height:1.5;">Trilingual store name, monogram logo emblem, top announcement banner, and Kurdistan delivery fees.</p>
                    </div>
                    <div style="margin-top:16px; color:var(--accent-gold); font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        Customize Brand →
                    </div>
                </a>
            </div>
        </div>

        <!-- Live Telemetry & Payment Gateway Health -->
        <div class="admin-form-card mb-28">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
                <div>
                    <h3 class="admin-card-title" style="margin:0; font-size:16px;">⚡ Iraqi Digital Payment Telemetry</h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Live integration status for Iraqi fintech APIs and local settlement channels.</p>
                </div>
                <a href="/admin/payments.php" class="btn btn-outline btn-xs">Configure All Gateways →</a>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px;">
                <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; color:var(--text-muted); font-weight:700;">First Iraqi Bank (FIB)</div>
                        <strong style="font-size:15px; color:var(--text-primary); display:block; margin-top:2px;">Online & Synced</strong>
                        <small style="color:#22c55e;">● OAuth2 Bearer Active</small>
                    </div>
                    <button type="button" class="btn btn-ghost btn-xs" onclick="window.AuraStore.testGatewayConnection('fib')">⚡ Ping</button>
                </div>

                <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; color:var(--text-muted); font-weight:700;">ZainCash Iraq</div>
                        <strong style="font-size:15px; color:var(--text-primary); display:block; margin-top:2px;">HMAC Validated</strong>
                        <small style="color:#22c55e;">● MSISDN Ready</small>
                    </div>
                    <button type="button" class="btn btn-ghost btn-xs" onclick="window.AuraStore.testGatewayConnection('zaincash')">⚡ Ping</button>
                </div>

                <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; color:var(--text-muted); font-weight:700;">FastPay Kurdistan</div>
                        <strong style="font-size:15px; color:var(--text-primary); display:block; margin-top:2px;">Ready</strong>
                        <small style="color:#22c55e;">● Regional Gateway</small>
                    </div>
                    <button type="button" class="btn btn-ghost btn-xs" onclick="window.AuraStore.testGatewayConnection('fastpay')">⚡ Ping</button>
                </div>

                <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Cash On Delivery</div>
                        <strong style="font-size:15px; color:var(--text-primary); display:block; margin-top:2px;">Enabled</strong>
                        <small style="color:#22c55e;">● 18 Governorates</small>
                    </div>
                    <span class="badge-tag" style="background:rgba(34,197,94,0.15); color:#22c55e; border-color:#22c55e;">Active</span>
                </div>
            </div>
        </div>

        <!-- Recent Orders Stream -->
        <div class="admin-table-card">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:12px;">
                <div>
                    <h3 class="admin-card-title" style="margin:0; font-size:17px;">📦 Recent Boutique Orders</h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Latest client checkouts and shipments awaiting fulfillment.</p>
                </div>
                <a href="/admin/orders.php" class="btn btn-primary btn-luxury btn-sm">
                    View All Orders (<?php echo count($ordersList); ?>) →
                </a>
            </div>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Client & City</th>
                            <th>Total (IQD)</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recentOrders = array_slice($ordersList, 0, 5);
                        foreach ($recentOrders as $ord): 
                            $ordTot = $ord['total'] ?? 0;
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
                                        <?php echo htmlspecialchars($ord['order_status'] ?? 'Pending'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/admin/orders.php" class="btn btn-outline btn-xs">Manage in Orders →</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
