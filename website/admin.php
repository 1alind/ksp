<?php
$activePage = 'admin';
$pageTitle = 'Management Dashboard';
require_once __DIR__ . '/header.php';

$actionMsg = '';
$actionType = 'success';

// Handle Order Status & Delivery Dispatch Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_dispatch'])) {
    $orderId = trim($_POST['order_id'] ?? '');
    $newStatus = trim($_POST['order_status'] ?? '');
    $courier = trim($_POST['courier'] ?? '');
    $driverName = trim($_POST['driver_name'] ?? '');
    $driverPhone = trim($_POST['driver_phone'] ?? '');
    $trackingCode = trim($_POST['tracking_code'] ?? '');
    $dispatchNotes = trim($_POST['dispatch_notes'] ?? '');
    $estimatedDelivery = trim($_POST['estimated_delivery'] ?? '');

    if (!empty($orderId)) {
        $updateFields = [
            'order_status' => $newStatus,
            'courier' => $courier,
            'driver_name' => $driverName,
            'driver_phone' => $driverPhone,
            'tracking_code' => $trackingCode,
            'dispatch_notes' => $dispatchNotes,
            'estimated_delivery' => $estimatedDelivery
        ];
        if (update_order_full($orderId, $updateFields)) {
            $actionMsg = 'Order ' . htmlspecialchars($orderId) . ' dispatch & logistics details updated successfully!';
        }
    }
}

// Handle Direct Order Status Quick Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $orderId = trim($_POST['order_id'] ?? '');
    $newStatus = trim($_POST['order_status'] ?? '');
    if (!empty($orderId) && !empty($newStatus)) {
        if (update_order_status($orderId, $newStatus)) {
            $actionMsg = 'Order ' . htmlspecialchars($orderId) . ' status updated to ' . htmlspecialchars($newStatus);
        }
    }
}

// Handle Order Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_id'])) {
    $delOrdId = trim($_POST['delete_order_id']);
    if (delete_order($delOrdId)) {
        $actionMsg = 'Order #' . htmlspecialchars($delOrdId) . ' removed from records.';
    }
}

// Handle Gateway Settings Update (FIB, ZainCash, FastPay, Store Config)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gateway_settings'])) {
    $currentSettings = get_store_settings();
    
    // FIB Update
    $currentSettings['gateways']['fib']['enabled'] = isset($_POST['fib_enabled']);
    $currentSettings['gateways']['fib']['mode'] = $_POST['fib_mode'] ?? 'test';
    $currentSettings['gateways']['fib']['client_id'] = trim($_POST['fib_client_id'] ?? '');
    $currentSettings['gateways']['fib']['client_secret'] = trim($_POST['fib_client_secret'] ?? '');
    $currentSettings['gateways']['fib']['account_iban'] = trim($_POST['fib_account_iban'] ?? '');
    $currentSettings['gateways']['fib']['account_holder'] = trim($_POST['fib_account_holder'] ?? '');
    $currentSettings['gateways']['fib']['callback_url'] = trim($_POST['fib_callback_url'] ?? '');

    // ZainCash Update
    $currentSettings['gateways']['zaincash']['enabled'] = isset($_POST['zaincash_enabled']);
    $currentSettings['gateways']['zaincash']['mode'] = $_POST['zaincash_mode'] ?? 'test';
    $currentSettings['gateways']['zaincash']['merchant_id'] = trim($_POST['zaincash_merchant_id'] ?? '');
    $currentSettings['gateways']['zaincash']['secret_key'] = trim($_POST['zaincash_secret_key'] ?? '');
    $currentSettings['gateways']['zaincash']['msisdn'] = trim($_POST['zaincash_msisdn'] ?? '');

    // FastPay Update
    $currentSettings['gateways']['fastpay']['enabled'] = isset($_POST['fastpay_enabled']);
    $currentSettings['gateways']['fastpay']['merchant_mobile'] = trim($_POST['fastpay_merchant_mobile'] ?? '');
    $currentSettings['gateways']['fastpay']['store_id'] = trim($_POST['fastpay_store_id'] ?? '');

    // Global Exchange Rate & Store details
    if (isset($_POST['exchange_rate_usd_to_iqd'])) {
        $currentSettings['exchange_rate_usd_to_iqd'] = intval($_POST['exchange_rate_usd_to_iqd']);
    }
    if (isset($_POST['contact_phone'])) {
        $currentSettings['contact_phone'] = trim($_POST['contact_phone']);
    }
    if (isset($_POST['contact_whatsapp'])) {
        $currentSettings['contact_whatsapp'] = trim($_POST['contact_whatsapp']);
    }

    save_store_settings($currentSettings);
    $actionMsg = 'FIB, ZainCash & Gateway configurations saved successfully!';
}

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new_product'])) {
    $titleEn = trim($_POST['prod_title_en'] ?? '');
    $titleAr = trim($_POST['prod_title_ar'] ?? $titleEn);
    $titleKu = trim($_POST['prod_title_ku'] ?? $titleEn);
    $cat = trim($_POST['prod_category'] ?? 'clothes');
    $price = floatval($_POST['prod_price'] ?? 0);
    $oldPrice = floatval($_POST['prod_old_price'] ?? 0);
    $stock = intval($_POST['prod_stock'] ?? 10);
    $image = trim($_POST['prod_image'] ?? 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80');
    $descEn = trim($_POST['prod_desc_en'] ?? '');
    $descAr = trim($_POST['prod_desc_ar'] ?? $descEn);
    $descKu = trim($_POST['prod_desc_ku'] ?? $descEn);
    $badge = trim($_POST['prod_badge'] ?? 'New Arrival');

    if (!empty($titleEn) && $price > 0) {
        $newProd = [
            'title' => [
                'en' => $titleEn,
                'ar' => $titleAr,
                'ku' => $titleKu
            ],
            'category' => $cat,
            'price' => $price,
            'old_price' => $oldPrice ?: null,
            'rating' => 5.0,
            'reviews_count' => 1,
            'badge' => $badge,
            'badge_ar' => $badge,
            'badge_ku' => $badge,
            'stock' => $stock,
            'image' => $image,
            'images' => [$image],
            'sizes' => $cat === 'clothes' ? ['S', 'M', 'L', 'XL'] : ($cat === 'watches' ? ['42mm Case'] : ['100ml / 3.4 oz']),
            'colors' => ['Luxury Edition'],
            'description' => [
                'en' => $descEn,
                'ar' => $descAr,
                'ku' => $descKu
            ],
            'featured' => true
        ];
        save_product($newProd);
        $actionMsg = 'New product "' . htmlspecialchars($titleEn) . '" added to catalog!';
    }
}

// Handle Update / Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $prodId = intval($_POST['edit_prod_id'] ?? 0);
    if ($prodId > 0) {
        $titleEn = trim($_POST['edit_prod_title_en'] ?? '');
        $titleAr = trim($_POST['edit_prod_title_ar'] ?? $titleEn);
        $titleKu = trim($_POST['edit_prod_title_ku'] ?? $titleEn);
        $cat = trim($_POST['edit_prod_category'] ?? 'clothes');
        $price = floatval($_POST['edit_prod_price'] ?? 0);
        $oldPrice = !empty($_POST['edit_prod_old_price']) ? floatval($_POST['edit_prod_old_price']) : null;
        $stock = intval($_POST['edit_prod_stock'] ?? 10);
        $image = trim($_POST['edit_prod_image'] ?? '');
        $galleryRaw = trim($_POST['edit_prod_gallery'] ?? '');
        $galleryImages = !empty($galleryRaw) ? array_values(array_filter(array_map('trim', explode(',', $galleryRaw)))) : [$image];
        if (!empty($image) && !in_array($image, $galleryImages)) {
            array_unshift($galleryImages, $image);
        }
        $badge = trim($_POST['edit_prod_badge'] ?? '');
        $badgeAr = trim($_POST['edit_prod_badge_ar'] ?? $badge);
        $badgeKu = trim($_POST['edit_prod_badge_ku'] ?? $badge);
        $descEn = trim($_POST['edit_prod_desc_en'] ?? '');
        $descAr = trim($_POST['edit_prod_desc_ar'] ?? $descEn);
        $descKu = trim($_POST['edit_prod_desc_ku'] ?? $descEn);
        $featured = isset($_POST['edit_prod_featured']);
        $sizesRaw = trim($_POST['edit_prod_sizes'] ?? '');
        $sizes = !empty($sizesRaw) ? array_values(array_filter(array_map('trim', explode(',', $sizesRaw)))) : ($cat === 'clothes' ? ['S', 'M', 'L', 'XL'] : ($cat === 'watches' ? ['42mm Case'] : ['100ml / 3.4 oz']));
        $colorsRaw = trim($_POST['edit_prod_colors'] ?? '');
        $colors = !empty($colorsRaw) ? array_values(array_filter(array_map('trim', explode(',', $colorsRaw)))) : ['Luxury Edition'];

        $existingProduct = get_product_by_id($prodId) ?: [];

        $updatedProduct = array_merge($existingProduct, [
            'id' => $prodId,
            'title' => [
                'en' => $titleEn ?: ($existingProduct['title']['en'] ?? 'Luxury Item'),
                'ar' => $titleAr ?: ($existingProduct['title']['ar'] ?? $titleEn),
                'ku' => $titleKu ?: ($existingProduct['title']['ku'] ?? $titleEn)
            ],
            'category' => $cat,
            'price' => $price,
            'old_price' => $oldPrice,
            'badge' => $badge,
            'badge_ar' => $badgeAr,
            'badge_ku' => $badgeKu,
            'stock' => $stock,
            'image' => $image ?: ($existingProduct['image'] ?? ''),
            'images' => $galleryImages,
            'sizes' => $sizes,
            'colors' => $colors,
            'description' => [
                'en' => $descEn ?: ($existingProduct['description']['en'] ?? ''),
                'ar' => $descAr ?: ($existingProduct['description']['ar'] ?? ''),
                'ku' => $descKu ?: ($existingProduct['description']['ku'] ?? '')
            ],
            'featured' => $featured
        ]);

        save_product($updatedProduct);
        $actionMsg = 'Product #' . $prodId . ' (' . htmlspecialchars($titleEn) . ') updated successfully!';
    }
}

// Handle Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_id'])) {
    $delId = intval($_POST['delete_product_id']);
    delete_product($delId);
    $actionMsg = 'Product ID #' . $delId . ' was deleted from database.';
}

// Handle Quick Stock Stepper
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_stock_adjust'])) {
    $pId = intval($_POST['product_id']);
    $delta = intval($_POST['stock_delta']);
    $prods = get_all_products();
    foreach ($prods as &$p) {
        if ($p['id'] === $pId) {
            $p['stock'] = max(0, ($p['stock'] ?? 0) + $delta);
            save_product($p);
            $actionMsg = 'Updated stock for ' . htmlspecialchars($p['title']['en'] ?? 'Product') . ' to ' . $p['stock'];
            break;
        }
    }
}

// Handle Inquiry Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inquiry_status'])) {
    $inqId = trim($_POST['inquiry_id']);
    $inqStatus = trim($_POST['inquiry_status']);
    if (update_inquiry_status($inqId, $inqStatus)) {
        $actionMsg = 'Inquiry ' . htmlspecialchars($inqId) . ' marked as ' . htmlspecialchars($inqStatus);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_inquiry_id'])) {
    $inqId = trim($_POST['delete_inquiry_id']);
    if (delete_inquiry($inqId)) {
        $actionMsg = 'Inquiry ' . htmlspecialchars($inqId) . ' removed.';
    }
}

$ordersList = get_all_orders();
$productsList = get_all_products();
$usersList = get_all_users();
$inquiriesList = get_all_inquiries();
$settings = get_store_settings();

// Calculate Revenue & Rates
$rate = $settings['exchange_rate_usd_to_iqd'] ?? 1320;
$totalRevenueUsd = 0;
$pendingCount = 0;
$deliveredCount = 0;
$inTransitCount = 0;

foreach ($ordersList as $o) {
    $tot = ($o['total'] ?? 0);
    $totalRevenueUsd += $tot;
    $st = $o['order_status'] ?? 'Pending';
    if ($st === 'Pending' || $st === 'Processing') $pendingCount++;
    if ($st === 'Delivered') $deliveredCount++;
    if ($st === 'Shipped' || $st === 'Out for Delivery') $inTransitCount++;
}

$totalRevenueIqd = $totalRevenueUsd * $rate;
$fib = $settings['gateways']['fib'] ?? [];
$zain = $settings['gateways']['zaincash'] ?? [];
$fastpay = $settings['gateways']['fastpay'] ?? [];
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">✦ Executive Command Suite</span>
            <h1 class="page-banner-title"><?php echo t('admin_title', $lang); ?></h1>
            <p class="page-banner-subtitle">
                Unified management of FIB & ZainCash gateways, Iraq & Kurdistan delivery radar, VIP clients, and live inventory.
            </p>
        </div>
    </div>
</div>

<section class="admin-section">
    <div class="container">
        
        <?php if (!empty($actionMsg)): ?>
            <div class="alert alert-success mb-24">✓ <?php echo $actionMsg; ?></div>
        <?php endif; ?>

        <!-- Metric KPI Cards -->
        <div class="admin-metrics-grid">
            <div class="admin-metric-card">
                <span class="m-icon">💰</span>
                <div class="m-info">
                    <span class="m-label"><?php echo t('admin_stats_revenue', $lang); ?></span>
                    <strong class="m-value text-primary"><?php echo number_format($totalRevenueUsd); ?> IQD</strong>
                    <span class="iqd-price-pill">All Orders Settled in IQD</span>
                </div>
            </div>

            <div class="admin-metric-card">
                <span class="m-icon">📦</span>
                <div class="m-info">
                    <span class="m-label"><?php echo t('admin_stats_orders', $lang); ?></span>
                    <strong class="m-value"><?php echo count($ordersList); ?> Orders</strong>
                    <span class="iqd-price-pill"><?php echo $inTransitCount; ?> in transit • <?php echo $deliveredCount; ?> delivered</span>
                </div>
            </div>

            <div class="admin-metric-card">
                <span class="m-icon">💎</span>
                <div class="m-info">
                    <span class="m-label"><?php echo t('admin_stats_products', $lang); ?></span>
                    <strong class="m-value"><?php echo count($productsList); ?> Pieces</strong>
                    <span class="iqd-price-pill">Luxury Catalog Active</span>
                </div>
            </div>

            <div class="admin-metric-card">
                <span class="m-icon">👑</span>
                <div class="m-info">
                    <span class="m-label"><?php echo t('admin_stats_users', $lang); ?></span>
                    <strong class="m-value"><?php echo count($usersList); ?> VIP Clients</strong>
                    <span class="iqd-price-pill"><?php echo count($inquiriesList); ?> Concierge Inquiries</span>
                </div>
            </div>
        </div>

        <!-- Admin Tabs Control -->
        <div class="admin-tabs-nav mt-32" id="adminTabsNav">
            <button class="admin-tab-btn active" onclick="switchAdminTab('adm-dashboard', this)">
                📊 <?php echo t('admin_tab_dashboard', $lang); ?>
            </button>
            <button class="admin-tab-btn" onclick="switchAdminTab('adm-orders', this)">
                🚚 <?php echo t('admin_tab_orders', $lang); ?> (<?php echo count($ordersList); ?>)
            </button>
            <button class="admin-tab-btn" onclick="switchAdminTab('adm-gateways', this)">
                💳 <?php echo t('admin_tab_gateways', $lang); ?>
            </button>
            <button class="admin-tab-btn" onclick="switchAdminTab('adm-products', this)">
                💎 <?php echo t('admin_tab_products', $lang); ?> (<?php echo count($productsList); ?>)
            </button>
            <button class="admin-tab-btn" onclick="switchAdminTab('adm-add-product', this)">
                + <?php echo t('admin_tab_add_product', $lang); ?>
            </button>
            <button class="admin-tab-btn" onclick="switchAdminTab('adm-users', this)">
                👑 <?php echo t('admin_tab_users', $lang); ?> (<?php echo count($usersList); ?>)
            </button>
            <button class="admin-tab-btn" onclick="switchAdminTab('adm-inquiries', this)">
                💬 <?php echo t('admin_tab_inquiries', $lang); ?> (<?php echo count($inquiriesList); ?>)
            </button>
            <button class="admin-tab-btn" onclick="switchAdminTab('adm-settings', this)">
                🎨 Brand & Settings
            </button>
        </div>

        <!-- TAB 1: EXECUTIVE DASHBOARD & ANALYTICS -->
        <div class="admin-tab-pane active" id="adm-dashboard">
            <div class="admin-form-card mb-24">
                <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <div>
                        <h3 class="admin-card-title">Executive Summary & Live Store Health</h3>
                        <p class="text-muted">Real-time telemetry of transactions, delivery routing, and gateway throughput across Iraq & Kurdistan Region.</p>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <span class="badge-tag" style="background:rgba(34,197,94,0.15); color:#22c55e; border-color:#22c55e;">● All Systems Operational</span>
                        <span class="badge-tag">USD / IQD: 1 = <?php echo number_format($rate); ?></span>
                    </div>
                </div>

                <div class="gateway-cards-grid">
                    <div class="gateway-card">
                        <div class="gateway-header">
                            <div class="gateway-brand">
                                <span class="gateway-icon-badge">🏦</span>
                                <div>
                                    <h3>First Iraqi Bank (FIB) Status</h3>
                                    <p><?php echo ($fib['enabled'] ?? false) ? 'Active Gateway' : 'Inactive'; ?> • <?php echo strtoupper($fib['mode'] ?? 'test'); ?> Mode</p>
                                </div>
                            </div>
                            <span class="gateway-status-pill <?php echo ($fib['enabled'] ?? false) ? 'online' : 'offline'; ?>">
                                <?php echo ($fib['enabled'] ?? false) ? 'ONLINE' : 'OFFLINE'; ?>
                            </span>
                        </div>
                        <div style="font-size:13px; color:var(--text-secondary); line-height:1.6;">
                            <div><strong>Account:</strong> <?php echo htmlspecialchars($fib['account_holder'] ?? 'Aura Trading Ltd'); ?></div>
                            <div><strong>IBAN:</strong> <code><?php echo htmlspecialchars($fib['account_iban'] ?? 'IQ44FIBQ...'); ?></code></div>
                            <div><strong>Supported:</strong> QR Code Scan (FIB App), Direct Debit, IQD / USD</div>
                        </div>
                        <div class="gateway-actions-row">
                            <button type="button" class="btn-test-api" onclick="window.AuraStore.testGatewayConnection('fib')">
                                ⚡ Ping FIB API Gateway
                            </button>
                            <button type="button" class="btn btn-outline btn-xs" onclick="switchAdminTab('adm-gateways', document.querySelectorAll('.admin-tab-btn')[2])">
                                Manage Keys →
                            </button>
                        </div>
                    </div>

                    <div class="gateway-card">
                        <div class="gateway-header">
                            <div class="gateway-brand">
                                <span class="gateway-icon-badge">📱</span>
                                <div>
                                    <h3>ZainCash (زين كاش) Status</h3>
                                    <p><?php echo ($zain['enabled'] ?? false) ? 'Active Gateway' : 'Inactive'; ?> • <?php echo strtoupper($zain['mode'] ?? 'test'); ?> Mode</p>
                                </div>
                            </div>
                            <span class="gateway-status-pill <?php echo ($zain['enabled'] ?? false) ? 'online' : 'offline'; ?>">
                                <?php echo ($zain['enabled'] ?? false) ? 'ONLINE' : 'OFFLINE'; ?>
                            </span>
                        </div>
                        <div style="font-size:13px; color:var(--text-secondary); line-height:1.6;">
                            <div><strong>Merchant MSISDN:</strong> <code><?php echo htmlspecialchars($zain['msisdn'] ?? '9647835077893'); ?></code></div>
                            <div><strong>Merchant ID:</strong> <code><?php echo htmlspecialchars($zain['merchant_id'] ?? '5ff656...'); ?></code></div>
                            <div><strong>Supported:</strong> Mobile Wallet, OTP Pin Verification, Iraqi Dinar (IQD)</div>
                        </div>
                        <div class="gateway-actions-row">
                            <button type="button" class="btn-test-api" onclick="window.AuraStore.testGatewayConnection('zaincash')">
                                ⚡ Ping ZainCash API Gateway
                            </button>
                            <button type="button" class="btn btn-outline btn-xs" onclick="switchAdminTab('adm-gateways', document.querySelectorAll('.admin-tab-btn')[2])">
                                Manage Keys →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Feed -->
                <h4 style="margin: 20px 0 12px; font-size:15px;">Recent Orders & Logistics Queue</h4>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Client & Destination</th>
                                <th>Method</th>
                                <th>Total (USD & IQD)</th>
                                <th>Logistics Status</th>
                                <th>Quick Dispatch</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($ordersList, 0, 5) as $ord): 
                                $ordTot = $ord['total'] ?? 0;
                                $ordIqd = $ord['total_iqd'] ?? ($ordTot * $rate);
                            ?>
                                <tr>
                                    <td>
                                        <strong><a href="track.php?order_id=<?php echo urlencode($ord['order_id']); ?>"><?php echo htmlspecialchars($ord['order_id']); ?></a></strong><br>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ord['customer_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($ord['city']); ?> • <?php echo htmlspecialchars($ord['phone']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-tag"><?php echo htmlspecialchars($ord['payment_method']); ?></span>
                                    </td>
                                    <td>
                                        <span class="font-bold text-primary">$<?php echo number_format($ordTot, 2); ?></span><br>
                                        <small class="text-muted"><?php echo number_format($ordIqd); ?> IQD</small>
                                    </td>
                                    <td>
                                        <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); border-color:var(--accent-gold);">
                                            <?php echo htmlspecialchars($ord['order_status'] ?? 'Pending'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-xs" onclick="openDispatchModal(<?php echo htmlspecialchars(json_encode($ord)); ?>)">
                                            🚚 Dispatch / Track
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: ORDERS & REAL-TIME LOGISTICS RADAR -->
        <div class="admin-tab-pane" id="adm-orders">
            <div class="admin-table-card">
                <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:12px;">
                    <div>
                        <h3 class="admin-card-title" style="margin:0;">📦 Orders & Real-time Delivery Radar</h3>
                        <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Manage client shipments, assign courier dispatchers, send WhatsApp alerts, and generate invoices.</p>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="text" id="orderSearchInput" onkeyup="filterOrdersTable()" placeholder="Filter by Name, ID, Phone..." class="form-control" style="max-width:240px; padding:6px 12px; font-size:13px;">
                        <select id="orderStatusFilter" onchange="filterOrdersTable()" class="form-control" style="max-width:180px; padding:6px 12px; font-size:13px;">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Processing">Processing</option>
                            <option value="Shipped">Shipped (Dispatched)</option>
                            <option value="Out for Delivery">Out for Delivery</option>
                            <option value="Delivered">Delivered</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="admin-table" id="ordersTableMain">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Client & Destination</th>
                                <th>Items</th>
                                <th>Total (IQD)</th>
                                <th>Payment & Status</th>
                                <th>Courier & Tracking</th>
                                <th>Quick Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordersList as $ord): 
                                $ordTot = $ord['total'] ?? 0;
                                $ordIqd = $ord['total_iqd'] ?? $ordTot;
                                $itemsCount = count($ord['items'] ?? []);
                                $waPhone = preg_replace('/[^0-9]/', '', $ord['phone'] ?? '');
                                if (strpos($waPhone, '07') === 0) {
                                    $waPhone = '964' . substr($waPhone, 1);
                                }
                                $waMsg = rawurlencode("Hello " . $ord['customer_name'] . ", greetings from AURA Luxury Store. Your order #" . $ord['order_id'] . " status is currently: " . ($ord['order_status'] ?? 'Processing') . ". Track live: https://aurastore.iq/track.php?order_id=" . $ord['order_id']);
                            ?>
                                <tr data-status="<?php echo htmlspecialchars($ord['order_status'] ?? 'Pending'); ?>" data-search="<?php echo strtolower($ord['order_id'] . ' ' . $ord['customer_name'] . ' ' . $ord['phone'] . ' ' . $ord['city']); ?>">
                                    <td>
                                        <strong><a href="track.php?order_id=<?php echo urlencode($ord['order_id']); ?>"><?php echo htmlspecialchars($ord['order_id']); ?></a></strong>
                                    </td>
                                    <td><small><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></small></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ord['customer_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($ord['city']); ?> • <?php echo htmlspecialchars($ord['phone']); ?></small>
                                    </td>
                                    <td><?php echo $itemsCount; ?> pcs</td>
                                    <td>
                                        <strong class="text-primary font-bold"><?php echo number_format($ordTot); ?> IQD</strong>
                                    </td>
                                    <td>
                                        <span class="badge-tag"><?php echo htmlspecialchars($ord['payment_method']); ?></span><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($ord['payment_status'] ?? 'Pending'); ?></small>
                                    </td>
                                    <td>
                                        <div class="courier-info-chip">
                                            <span class="courier-name"><?php echo htmlspecialchars($ord['courier'] ?? 'Unassigned'); ?></span>
                                            <?php if (!empty($ord['driver_name'])): ?>
                                                <span class="courier-driver">👤 <?php echo htmlspecialchars($ord['driver_name']); ?> (<?php echo htmlspecialchars($ord['driver_phone'] ?? ''); ?>)</span>
                                            <?php endif; ?>
                                            <?php if (!empty($ord['tracking_code'])): ?>
                                                <code style="font-size:11px;"><?php echo htmlspecialchars($ord['tracking_code']); ?></code>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="order-status-wrapper" id="orderStatusWrap_<?php echo htmlspecialchars($ord['order_id']); ?>">
                                            <select name="order_status" class="status-select" data-previous-status="<?php echo htmlspecialchars($ord['order_status'] ?? 'Pending'); ?>" onchange="window.AuraStore.updateOrderStatus('<?php echo htmlspecialchars($ord['order_id']); ?>', this.value, this)">
                                                <option value="Pending" <?php echo ($ord['order_status'] ?? '') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Processing" <?php echo ($ord['order_status'] ?? '') === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="Shipped" <?php echo ($ord['order_status'] ?? '') === 'Shipped' ? 'selected' : ''; ?>>Shipped (Dispatched)</option>
                                                <option value="Out for Delivery" <?php echo ($ord['order_status'] ?? '') === 'Out for Delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                                <option value="Delivered" <?php echo ($ord['order_status'] ?? '') === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="Cancelled" <?php echo ($ord['order_status'] ?? '') === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                            <button type="button" class="btn btn-outline btn-xs" onclick="openDispatchModal(<?php echo htmlspecialchars(json_encode($ord)); ?>)" title="Manage Courier & Logistics">
                                                🚚 Logistics
                                            </button>
                                            <button type="button" class="btn btn-outline btn-xs" onclick="printOrderInvoice(<?php echo htmlspecialchars(json_encode($ord)); ?>)" title="Print Luxury Invoice">
                                                📄 Invoice
                                            </button>
                                            <?php if (!empty($waPhone)): ?>
                                                <a href="https://wa.me/<?php echo $waPhone; ?>?text=<?php echo $waMsg; ?>" target="_blank" class="btn btn-outline btn-xs" style="color:#22c55e;" title="Send WhatsApp Update">
                                                    💬 WA
                                                </a>
                                            <?php endif; ?>
                                            <form action="admin.php" method="POST" onsubmit="return confirm('Delete order permanently?')" style="display:inline;">
                                                <input type="hidden" name="delete_order_id" value="<?php echo htmlspecialchars($ord['order_id']); ?>">
                                                <button type="submit" class="btn btn-ghost text-danger btn-xs" title="Delete Order">✕</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: PAYMENT GATEWAYS & API TOKENS (FIB & ZAINCASH) -->
        <div class="admin-tab-pane" id="adm-gateways">
            <form action="admin.php" method="POST" id="gatewaySettingsForm">
                <input type="hidden" name="save_gateway_settings" value="1">

                <div class="gateway-cards-grid">
                    
                    <!-- 1. FIB Gateway Card -->
                    <div class="gateway-card active-gateway">
                        <div class="gateway-header">
                            <div class="gateway-brand">
                                <span class="gateway-icon-badge">🏦</span>
                                <div>
                                    <h3>First Iraqi Bank (FIB API Suite)</h3>
                                    <p>Direct banking, dynamic QR scans, and Iraqi Dinar transactions</p>
                                </div>
                            </div>
                            <div class="gateway-toggle-wrap">
                                <label class="switch-toggle" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="checkbox" name="fib_enabled" value="1" <?php echo ($fib['enabled'] ?? false) ? 'checked' : ''; ?>>
                                    <span style="font-size:12px; font-weight:700;">Enable FIB</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label>Environment Mode</label>
                                <select name="fib_mode" class="form-control">
                                    <option value="test" <?php echo ($fib['mode'] ?? '') === 'test' ? 'selected' : ''; ?>>Sandbox / Test (api.test.fib.iq)</option>
                                    <option value="prod" <?php echo ($fib['mode'] ?? '') === 'prod' ? 'selected' : ''; ?>>Production Live (api.fib.iq)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Account IBAN (Kurdistan/Iraq)</label>
                                <input type="text" name="fib_account_iban" value="<?php echo htmlspecialchars($fib['account_iban'] ?? 'IQ44FIBQ0000001009283741'); ?>" class="form-control" placeholder="IQ44FIBQ...">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>FIB Client ID / App Key <span class="text-danger">*</span></label>
                            <div class="input-with-action">
                                <input type="text" name="fib_client_id" id="fibClientIdInput" value="<?php echo htmlspecialchars($fib['client_id'] ?? 'fib_live_client_89420ab92c'); ?>" required class="form-control" placeholder="fib_live_client_...">
                                <button type="button" class="btn btn-outline btn-xs" onclick="window.AuraStore.copyToClipboard('fibClientIdInput', 'FIB Client ID copied')">📋 Copy</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>FIB Client Secret Key <span class="text-danger">*</span></label>
                            <div class="input-with-action">
                                <input type="password" name="fib_client_secret" id="fibSecretInput" value="<?php echo htmlspecialchars($fib['client_secret'] ?? 'fib_sec_9941a87b32f9104c99a0'); ?>" required class="form-control" placeholder="fib_sec_...">
                                <button type="button" class="btn-toggle-eye" onclick="togglePasswordVisibility('fibSecretInput')">👁️</button>
                                <button type="button" class="btn btn-outline btn-xs" onclick="window.AuraStore.copyToClipboard('fibSecretInput', 'FIB Secret copied')">📋</button>
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label>Account Holder Name</label>
                                <input type="text" name="fib_account_holder" value="<?php echo htmlspecialchars($fib['account_holder'] ?? 'AURA LUXURY TRADING LTD'); ?>" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Webhook / Callback URL</label>
                                <input type="url" name="fib_callback_url" value="<?php echo htmlspecialchars($fib['callback_url'] ?? 'https://aurastore.iq/api/fib/callback'); ?>" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Active Bearer Access Token (OAuth2)</label>
                            <div class="input-with-action">
                                <input type="text" name="fib_access_token" id="fibAccessTokenInput" value="<?php echo htmlspecialchars($fib['access_token'] ?? 'fib_bearer_eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL2F1dGguZmliLmlxIiwic3ViIjoiZmliX2NsaWVudF9saXZlIn0.sig_live'); ?>" class="form-control" style="font-family:monospace; font-size:12px;">
                                <button type="button" class="btn btn-outline btn-xs" onclick="window.AuraStore.copyToClipboard('fibAccessTokenInput', 'Bearer Token copied')">📋 Copy</button>
                            </div>
                        </div>

                        <div class="gateway-actions-row" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                            <div style="display:flex; gap:8px;">
                                <button type="button" class="btn-test-api" onclick="window.AuraStore.testGatewayConnection('fib')">
                                    ⚡ Test FIB Connection & Ping
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="window.AuraStore.generateFibToken()" style="color:var(--accent-gold); border-color:var(--accent-gold);">
                                    🔑 Generate Dynamic Token
                                </button>
                            </div>
                            <span class="text-muted" style="font-size:11.5px;">API v1 OAuth2 Bearer</span>
                        </div>
                    </div>

                    <!-- 2. ZainCash Gateway Card -->
                    <div class="gateway-card active-gateway">
                        <div class="gateway-header">
                            <div class="gateway-brand">
                                <span class="gateway-icon-badge">📱</span>
                                <div>
                                    <h3>ZainCash (زين كاش API Suite)</h3>
                                    <p>Iraq's premier mobile wallet & HMAC-SHA256 JWT authorization</p>
                                </div>
                            </div>
                            <div class="gateway-toggle-wrap">
                                <label class="switch-toggle" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="checkbox" name="zaincash_enabled" value="1" <?php echo ($zain['enabled'] ?? false) ? 'checked' : ''; ?>>
                                    <span style="font-size:12px; font-weight:700;">Enable ZainCash</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label>Environment Mode</label>
                                <select name="zaincash_mode" class="form-control">
                                    <option value="test" <?php echo ($zain['mode'] ?? '') === 'test' ? 'selected' : ''; ?>>Sandbox / Test (test.zaincash.iq)</option>
                                    <option value="prod" <?php echo ($zain['mode'] ?? '') === 'prod' ? 'selected' : ''; ?>>Production Live (api.zaincash.iq)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Merchant MSISDN (Wallet Phone) <span class="text-danger">*</span></label>
                                <input type="text" name="zaincash_msisdn" value="<?php echo htmlspecialchars($zain['msisdn'] ?? '9647835077893'); ?>" class="form-control" placeholder="964780xxxxxxx">
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label>ZainCash Merchant ID <span class="text-danger">*</span></label>
                                <input type="text" name="zaincash_merchant_id" id="zainMerchantIdInput" value="<?php echo htmlspecialchars($zain['merchant_id'] ?? '5ff6561082c3f8109c11f2a3'); ?>" required class="form-control" placeholder="5ff6561082c3f8109c11f2a3">
                            </div>
                            <div class="form-group">
                                <label>Wallet Security PIN</label>
                                <input type="password" name="zaincash_pin" id="zainPinInput" value="<?php echo htmlspecialchars($zain['pin'] ?? '1234'); ?>" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ZainCash Secret Key (JWT HMAC-SHA256 Secret) <span class="text-danger">*</span></label>
                            <div class="input-with-action">
                                <input type="password" name="zaincash_secret_key" id="zainSecretInput" value="<?php echo htmlspecialchars($zain['secret_key'] ?? '$2y$10$hBbAZo2GfWge2j0xEv3q8.8Vo5AeaJk6m3mG0a.a2K9p8N.O0s1qG'); ?>" required class="form-control">
                                <button type="button" class="btn-toggle-eye" onclick="togglePasswordVisibility('zainSecretInput')">👁️</button>
                                <button type="button" class="btn btn-outline btn-xs" onclick="window.AuraStore.copyToClipboard('zainSecretInput', 'ZainCash Secret copied')">📋 Copy</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Transaction Redirect & Return URL</label>
                            <input type="url" name="zaincash_redirect_url" value="<?php echo htmlspecialchars($zain['redirect_url'] ?? 'https://aurastore.iq/api/zaincash/redirect'); ?>" class="form-control">
                        </div>

                        <div class="gateway-actions-row" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                            <div style="display:flex; gap:8px;">
                                <button type="button" class="btn-test-api" onclick="window.AuraStore.testGatewayConnection('zaincash')">
                                    ⚡ Test ZainCash Handshake
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="window.AuraStore.verifyZaincashSignature()" style="color:var(--accent-gold); border-color:var(--accent-gold);">
                                    🛡️ Verify JWT Signature & Encoding
                                </button>
                            </div>
                            <span class="text-muted" style="font-size:11.5px;">HS256 HMAC Signature Auth</span>
                        </div>
                    </div>

                    <!-- 3. FastPay Gateway Card -->
                    <div class="gateway-card">
                        <div class="gateway-header">
                            <div class="gateway-brand">
                                <span class="gateway-icon-badge">⚡</span>
                                <div>
                                    <h3>FastPay Mobile Wallet</h3>
                                    <p>Kurdistan Region & Iraq fast mobile wallet checkout</p>
                                </div>
                            </div>
                            <div class="gateway-toggle-wrap">
                                <label class="switch-toggle" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="checkbox" name="fastpay_enabled" value="1" <?php echo ($fastpay['enabled'] ?? false) ? 'checked' : ''; ?>>
                                    <span style="font-size:12px; font-weight:700;">Enable FastPay</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label>Merchant Mobile Number</label>
                                <input type="text" name="fastpay_merchant_mobile" value="<?php echo htmlspecialchars($fastpay['merchant_mobile'] ?? '07501234567'); ?>" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Store ID</label>
                                <input type="text" name="fastpay_store_id" value="<?php echo htmlspecialchars($fastpay['store_id'] ?? 'FP_STORE_94821'); ?>" class="form-control">
                            </div>
                        </div>

                        <div class="gateway-actions-row">
                            <button type="button" class="btn-test-api" onclick="window.AuraStore.testGatewayConnection('fastpay')">
                                ⚡ Test FastPay Integration
                            </button>
                            <span class="text-muted" style="font-size:11.5px;">Mobile Direct Transfer</span>
                        </div>
                    </div>

                    <!-- 4. Currency Exchange & Delivery Config -->
                    <div class="gateway-card">
                        <div class="gateway-header">
                            <div class="gateway-brand">
                                <span class="gateway-icon-badge">💱</span>
                                <div>
                                    <h3>Exchange Rate & Iraq Delivery Engine</h3>
                                    <p>Set dynamic USD to IQD peg for checkout conversions</p>
                                </div>
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label>1 USD = Iraqi Dinar (IQD) <span class="text-danger">*</span></label>
                                <input type="number" name="exchange_rate_usd_to_iqd" value="<?php echo htmlspecialchars($rate); ?>" required class="form-control" style="font-size:16px; font-weight:800; color:var(--accent-gold);">
                            </div>
                            <div class="form-group">
                                <label>Currency Display Format</label>
                                <select class="form-control">
                                    <option value="both" selected>Both USD ($) & IQD (د.ع)</option>
                                    <option value="usd">USD Only</option>
                                    <option value="iqd">IQD Only</option>
                                </select>
                            </div>
                        </div>

                        <div style="background:var(--bg-subtle); padding:12px; border-radius:8px; font-size:12.5px;">
                            <strong>Conversion Preview:</strong> $100.00 = <span class="text-primary font-bold"><?php echo number_format(100 * $rate); ?> IQD</span> &bull; $480.00 = <span class="text-primary font-bold"><?php echo number_format(480 * $rate); ?> IQD</span>
                        </div>
                    </div>

                </div>

                <!-- API Logs Console Card -->
                <div class="api-logs-card">
                    <div class="api-logs-header">
                        <h4><span>🖥️</span> Live Payment Gateway Connectivity & Transaction Telemetry</h4>
                        <span class="text-muted" style="font-size:11.5px;">Auto-logging real API requests & webhooks</span>
                    </div>
                    <div class="logs-stream" id="gatewayLogsStream">
                        <div class="log-entry success">
                            <span class="log-time">[2026-08-23 19:22:04]</span>
                            <span class="log-tag fib">FIB_API</span>
                            <span>OAuth token check validated: <code>Bearer eyJhbGciOi...</code> (Latency: 38ms)</span>
                        </div>
                        <div class="log-entry success">
                            <span class="log-time">[2026-08-23 19:21:50]</span>
                            <span class="log-tag zain">ZAINCASH</span>
                            <span>Merchant JWT signature validated for MSISDN 9647835077893 (Status: 200 OK)</span>
                        </div>
                        <div class="log-entry info">
                            <span class="log-time">[2026-08-23 19:15:10]</span>
                            <span class="log-tag fib">FIB_QR</span>
                            <span>Generated dynamic payment QR code for Order #ORD-84920 (745,800 IQD)</span>
                        </div>
                    </div>
                </div>

                <div class="mt-24 text-right" style="display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary btn-luxury btn-lg">
                        💾 Save Gateway & API Configuration
                    </button>
                </div>
            </form>
        </div>

        <!-- TAB 4: PRODUCTS CATALOG -->
        <div class="admin-tab-pane" id="adm-products">
            <div class="admin-table-card">
                <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid var(--border-color);">
                    <div>
                        <h3 class="admin-card-title" style="margin:0;">💎 Luxury Product Catalog (<?php echo count($productsList); ?> Pieces)</h3>
                        <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Live inventory across Clothes, Swiss Watches, Haute Perfumes, and Accessories.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="switchAdminTab('adm-add-product', document.querySelectorAll('.admin-tab-btn')[4])">
                        + Add New Piece
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price (IQD)</th>
                                <th>Stock Stepper</th>
                                <th>Rating</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productsList as $p): 
                                $pTitle = is_array($p['title']) ? ($p['title'][$lang] ?? $p['title']['en']) : $p['title'];
                                $pPriceIqd = $p['price'] ?? 0;
                                $pOldPriceIqd = $p['old_price'] ?? null;
                            ?>
                                <tr>
                                    <td>#<?php echo $p['id']; ?></td>
                                    <td>
                                        <div class="admin-prod-preview">
                                            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="" class="admin-prod-thumb" id="adminThumb_<?php echo $p['id']; ?>">
                                            <div>
                                                <strong><a href="product.php?id=<?php echo $p['id']; ?>" target="_blank" style="color:var(--text-primary);"><?php echo htmlspecialchars($pTitle); ?></a></strong><br>
                                                <?php if (!empty($p['badge'])): ?>
                                                    <small class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); border-color:var(--accent-gold); font-weight:700;"><?php echo htmlspecialchars($p['badge']); ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($p['featured'])): ?>
                                                    <small class="badge-tag" style="background:rgba(59,130,246,0.15); color:#60a5fa; border-color:#3b82f6;">⭐ Featured</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge-tag text-uppercase"><?php echo htmlspecialchars($p['category']); ?></span></td>
                                    <td>
                                        <div style="display:flex; flex-direction:column;">
                                            <strong class="font-bold" style="color:var(--accent-gold); font-size:14px;"><?php echo number_format($pPriceIqd); ?> IQD</strong>
                                            <?php if (!empty($pOldPriceIqd) && $pOldPriceIqd > $pPriceIqd): ?>
                                                <small style="text-decoration:line-through; color:var(--text-muted); font-size:11.5px;"><?php echo number_format($pOldPriceIqd); ?> IQD</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="stock-stepper" id="stockStepper_<?php echo $p['id']; ?>">
                                            <button type="button" class="btn-stock-step" title="Decrease stock" onclick="window.AuraStore.adjustStock(<?php echo $p['id']; ?>, -1, this)">-</button>
                                            <span class="stock-count-num" id="stockCount_<?php echo $p['id']; ?>" style="<?php echo ($p['stock'] < 5) ? 'color:#ef4444;' : ''; ?>"><?php echo $p['stock']; ?></span>
                                            <button type="button" class="btn-stock-step" title="Increase stock" onclick="window.AuraStore.adjustStock(<?php echo $p['id']; ?>, 1, this)">+</button>
                                        </div>
                                    </td>
                                    <td>★ <?php echo number_format($p['rating'], 1); ?> (<?php echo $p['reviews_count'] ?? 1; ?>)</td>
                                    <td>
                                        <div style="display:flex; gap:6px; align-items:center;">
                                            <button type="button" class="btn btn-outline btn-xs" style="color:var(--accent-gold); border-color:var(--accent-gold); font-weight:700;" onclick='openEditProductModal(<?php echo htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8"); ?>)'>
                                                ✏️ Edit
                                            </button>
                                            <a href="product.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn btn-ghost btn-xs" title="View product in boutique">
                                                👁️
                                            </a>
                                            <form action="admin.php" method="POST" onsubmit="return confirm('Delete product permanently?')" style="display:inline; margin:0;">
                                                <input type="hidden" name="delete_product_id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" class="btn btn-ghost text-danger btn-xs" title="Delete product">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 5: ADD PRODUCT -->
        <div class="admin-tab-pane" id="adm-add-product">
            <div class="admin-form-card">
                <h3 class="admin-card-title">+ Add New Luxury Piece to Catalog</h3>
                <p class="text-muted mb-20">Trilingual titles, IQD pricing, custom discount badge tags, and multi-angle imagery for English, Arabic, and Kurdish Badini audiences.</p>
                <form action="admin.php" method="POST" class="add-product-form">
                    <input type="hidden" name="add_new_product" value="1">

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Title (English) <span class="text-danger">*</span></label>
                            <input type="text" name="prod_title_en" required class="form-control" placeholder="e.g. Royal Sapphire Chronograph">
                        </div>
                        <div class="form-group">
                            <label>Title (Arabic - العربية)</label>
                            <input type="text" name="prod_title_ar" class="form-control" placeholder="مثال: ساعة سافاير الملكية">
                        </div>
                        <div class="form-group">
                            <label>Title (Kurdish - کوردی بادینی)</label>
                            <input type="text" name="prod_title_ku" class="form-control" placeholder="وەکی: دەمژمێرا یاقووتی یا شاهانە">
                        </div>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Category <span class="text-danger">*</span></label>
                            <select name="prod_category" required class="form-control">
                                <option value="clothes">Clothes (جلوبەرگ)</option>
                                <option value="watches">Watches (دەمژمێر)</option>
                                <option value="perfumes">Perfumes (عەتر و بێهن)</option>
                                <option value="accessories">Accessories (ئەکسسوارات)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price (IQD) <span class="text-danger">*</span></label>
                            <input type="number" step="500" name="prod_price" required class="form-control" placeholder="e.g. 240000">
                            <small class="text-muted">Enter price in Iraqi Dinar (IQD)</small>
                        </div>
                        <div class="form-group">
                            <label>Old Price (IQD) (Optional Discount)</label>
                            <input type="number" step="500" name="prod_old_price" class="form-control" placeholder="e.g. 310000">
                            <small class="text-muted">Original price before discount</small>
                        </div>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Stock Inventory Count</label>
                            <input type="number" name="prod_stock" value="15" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Badge / Promotional Tag (e.g. New Arrival, 50% OFF)</label>
                            <input type="text" name="prod_badge" value="New Arrival" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Primary Image URL</label>
                            <input type="url" name="prod_image" value="https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80" class="form-control">
                        </div>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Description (English)</label>
                            <textarea name="prod_desc_en" rows="3" class="form-control" placeholder="Craftsmanship details, materials, origin..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Description (Arabic)</label>
                            <textarea name="prod_desc_ar" rows="3" class="form-control" placeholder="تفاصيل الصناعة الفاخرة، المواد، المنشأ..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Description (Kurdish)</label>
                            <textarea name="prod_desc_ku" rows="3" class="form-control" placeholder="هویرکاریێن دروستکرنێ، کەرەستە و ژێدەر..."></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-luxury btn-lg mt-16">
                        💾 Publish Piece to Database
                    </button>
                </form>
            </div>
        </div>

        <!-- TAB 6: VIP CLIENTS DIRECTORY -->
        <div class="admin-tab-pane" id="adm-users">
            <div class="admin-table-card">
                <div class="admin-header-row" style="padding:20px; border-bottom:1px solid var(--border-color);">
                    <h3 class="admin-card-title" style="margin:0;">👑 VIP Clients & Loyalty Tiers</h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Registered clients and high-net-worth patrons across Kurdistan Region & Iraq.</p>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Contact & City</th>
                                <th>VIP Tier</th>
                                <th>Total Spent</th>
                                <th>Orders Count</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usersList as $u): 
                                $tier = $u['vip_tier'] ?? 'Gold Patron';
                                $badgeColor = $tier === 'Platinum Royal' ? 'var(--accent-gold)' : ($tier === 'Diamond Elite' ? '#38bdf8' : '#e2e8f0');
                            ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div class="author-avatar" style="width:36px; height:36px; font-size:14px;">
                                                <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($u['email']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($u['city'] ?? 'Duhok'); ?> • <?php echo htmlspecialchars($u['phone'] ?? '+964 750 000 0000'); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-tag" style="border-color:<?php echo $badgeColor; ?>; color:<?php echo $badgeColor; ?>;">
                                            👑 <?php echo htmlspecialchars($tier); ?>
                                        </span>
                                    </td>
                                    <td class="font-bold text-primary"><?php echo number_format($u['total_spent'] ?? 0); ?> IQD</td>
                                    <td><?php echo $u['orders_count'] ?? 1; ?> orders</td>
                                    <td><small><?php echo date('M Y', strtotime($u['joined_at'] ?? '2026-01-01')); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 7: CONCIERGE INQUIRIES -->
        <div class="admin-tab-pane" id="adm-inquiries">
            <div class="admin-table-card">
                <div class="admin-header-row" style="padding:20px; border-bottom:1px solid var(--border-color);">
                    <h3 class="admin-card-title" style="margin:0;">💬 Customer Messages & Concierge Desk</h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Live contact inquiries sent by visitors via the VIP Concierge form.</p>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Inquiry ID</th>
                                <th>Date</th>
                                <th>Client Details</th>
                                <th>Subject & Message</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inquiriesList as $inq): 
                                $inqPhone = preg_replace('/[^0-9]/', '', $inq['phone'] ?? '');
                                if (strpos($inqPhone, '07') === 0) $inqPhone = '964' . substr($inqPhone, 1);
                                $inqWaMsg = rawurlencode("Hello " . $inq['name'] . ", greetings from AURA Concierge. In response to your inquiry regarding: " . $inq['subject']);
                            ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($inq['id'] ?? 'INQ-101'); ?></code></td>
                                    <td><small><?php echo date('M d, Y', strtotime($inq['created_at'] ?? $inq['date'] ?? '2026-08-20')); ?></small></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($inq['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($inq['email'] ?? ''); ?> • <?php echo htmlspecialchars($inq['phone'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($inq['subject'] ?? 'General Inquiry'); ?></strong>
                                        <p style="font-size:13px; color:var(--text-secondary); margin:4px 0 0; max-width:400px;"><?php echo nl2br(htmlspecialchars($inq['message'])); ?></p>
                                    </td>
                                    <td>
                                        <form action="admin.php" method="POST" class="inline-status-form">
                                            <input type="hidden" name="inquiry_id" value="<?php echo htmlspecialchars($inq['id']); ?>">
                                            <input type="hidden" name="update_inquiry_status" value="1">
                                            <select name="inquiry_status" class="status-select" onchange="this.form.submit()">
                                                <option value="New" <?php echo ($inq['status'] ?? '') === 'New' ? 'selected' : ''; ?>>New</option>
                                                <option value="Replied" <?php echo ($inq['status'] ?? '') === 'Replied' ? 'selected' : ''; ?>>Replied</option>
                                                <option value="Resolved" <?php echo ($inq['status'] ?? '') === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:6px;">
                                            <?php if (!empty($inqPhone)): ?>
                                                <a href="https://wa.me/<?php echo $inqPhone; ?>?text=<?php echo $inqWaMsg; ?>" target="_blank" class="btn btn-outline btn-xs" style="color:#22c55e;">
                                                    💬 WhatsApp
                                                </a>
                                            <?php endif; ?>
                                            <form action="admin.php" method="POST" onsubmit="return confirm('Delete message?')" style="display:inline;">
                                                <input type="hidden" name="delete_inquiry_id" value="<?php echo htmlspecialchars($inq['id']); ?>">
                                                <button type="submit" class="btn btn-ghost text-danger btn-xs">✕</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 8: WEBSITE BRANDING, LOGO & GLOBAL SETTINGS -->
        <div class="admin-tab-pane" id="adm-settings">
            <form action="admin.php" method="POST" id="websiteBrandingForm">
                <input type="hidden" name="save_website_branding" value="1">

                <!-- 1. Live Brand & Logo Customizer -->
                <div class="admin-form-card mb-24">
                    <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <div>
                            <h3 class="admin-card-title" style="margin:0;">👑 Live Brand Identity & Logo Customizer</h3>
                            <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Choose between Monogram / Luxury Emblem or Custom Image Logo with real-time preview.</p>
                        </div>
                        <span class="badge-tag" style="background:rgba(217,119,6,0.15); color:var(--accent-gold);">Real-time Sync</span>
                    </div>

                    <!-- Live Preview Panel -->
                    <div style="background:var(--bg-subtle); padding:20px; border-radius:12px; border:1px solid var(--border-color); margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
                        <div>
                            <span style="font-size:11px; text-transform:uppercase; letter-spacing:1.5px; color:var(--text-secondary); font-weight:700; display:block; margin-bottom:8px;">Live Header Logo Preview</span>
                            <div id="adminLogoLivePreview" style="padding:10px 16px; background:var(--card-bg); border-radius:8px; border:1px solid var(--border-color); display:inline-block;">
                                <?php if (($settings['logo_type'] ?? 'emblem') === 'image' && !empty($settings['logo_image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($settings['logo_image_url']); ?>" alt="Brand Logo" style="max-height:48px; object-fit:contain;">
                                <?php else: ?>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div class="logo-emblem" style="background:<?php echo htmlspecialchars($settings['brand_accent_color'] ?? '#d97706'); ?>; color:#fff; width:44px; height:44px; display:flex; align-items:center; justify-content:center; border-radius:10px; font-weight:800; font-size:22px; font-family:'Alexandria',sans-serif;"><?php echo htmlspecialchars($settings['logo_emblem'] ?? 'A'); ?></div>
                                        <div class="logo-text-group" style="display:flex; flex-direction:column; line-height:1.1;">
                                            <span class="logo-main" style="font-weight:800; font-size:20px; letter-spacing:2px; color:var(--text-primary); font-family:'Alexandria',sans-serif;"><?php echo htmlspecialchars($settings['logo_main'] ?? 'AURA'); ?></span>
                                            <span class="logo-sub" style="font-size:10px; letter-spacing:3px; color:var(--accent-gold); font-weight:700;"><?php echo htmlspecialchars($settings['logo_sub'] ?? 'STUDIO'); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Emblem Quick Presets -->
                        <div>
                            <span style="font-size:11px; text-transform:uppercase; letter-spacing:1.5px; color:var(--text-secondary); font-weight:700; display:block; margin-bottom:8px;">1-Click Emblem Presets</span>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                <?php $emblems = ['A', '👑', '💎', '⚜️', '🦅', '✦', '🏛️', '🌙', '⚡', '⌚', '🦁', '🌟'];
                                foreach ($emblems as $emb): ?>
                                    <button type="button" class="btn btn-outline btn-xs" onclick="document.getElementById('logoEmblemInput').value='<?php echo $emb; ?>'; document.getElementById('logoTypeEmblem').checked=true; window.AuraStore.updateLogoPreview();" style="font-size:14px; min-width:32px; padding:4px 8px;">
                                        <?php echo $emb; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Logo Presentation Mode</label>
                            <div style="display:flex; gap:20px; align-items:center; margin-top:8px;">
                                <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                                    <input type="radio" name="logo_type" id="logoTypeEmblem" value="emblem" <?php echo ($settings['logo_type'] ?? 'emblem') === 'emblem' ? 'checked' : ''; ?> onchange="window.AuraStore.updateLogoPreview()">
                                    <span>Luxury Emblem / Monogram</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                                    <input type="radio" name="logo_type" id="logoTypeImage" value="image" <?php echo ($settings['logo_type'] ?? 'emblem') === 'image' ? 'checked' : ''; ?> onchange="window.AuraStore.updateLogoPreview()">
                                    <span>Custom Image URL</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Emblem Symbol / Letter</label>
                            <input type="text" name="logo_emblem" id="logoEmblemInput" value="<?php echo htmlspecialchars($settings['logo_emblem'] ?? 'A'); ?>" class="form-control" oninput="window.AuraStore.updateLogoPreview()" placeholder="e.g. A, ✦, 👑">
                        </div>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Logo Main Text (Primary)</label>
                            <input type="text" name="logo_main" id="logoMainInput" value="<?php echo htmlspecialchars($settings['logo_main'] ?? 'AURA'); ?>" class="form-control" oninput="window.AuraStore.updateLogoPreview()" placeholder="e.g. AURA">
                        </div>
                        <div class="form-group">
                            <label>Logo Sub Text (Subtitle)</label>
                            <input type="text" name="logo_sub" id="logoSubInput" value="<?php echo htmlspecialchars($settings['logo_sub'] ?? 'STUDIO'); ?>" class="form-control" oninput="window.AuraStore.updateLogoPreview()" placeholder="e.g. STUDIO or LUXURY STORE">
                        </div>
                        <div class="form-group">
                            <label>Brand Accent Color</label>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <input type="color" name="brand_accent_color" id="brandAccentInput" value="<?php echo htmlspecialchars($settings['brand_accent_color'] ?? '#d97706'); ?>" style="height:42px; width:50px; padding:2px; border-radius:6px; border:1px solid var(--border-color); cursor:pointer;" oninput="window.AuraStore.updateLogoPreview()">
                                <input type="text" value="<?php echo htmlspecialchars($settings['brand_accent_color'] ?? '#d97706'); ?>" class="form-control" readonly style="font-family:monospace;">
                            </div>
                        </div>
                    </div>

                    <div class="form-row-2 mt-12">
                        <div class="form-group">
                            <label>Custom Logo Image URL (Optional)</label>
                            <input type="url" name="logo_image_url" id="logoImageInput" value="<?php echo htmlspecialchars($settings['logo_image_url'] ?? ''); ?>" class="form-control" oninput="window.AuraStore.updateLogoPreview()" placeholder="https://example.com/assets/logo.png">
                        </div>
                        <div class="form-group">
                            <label>Custom Favicon URL (Optional)</label>
                            <input type="url" name="favicon_url" id="faviconInput" value="<?php echo htmlspecialchars($settings['favicon_url'] ?? ''); ?>" class="form-control" placeholder="https://example.com/assets/favicon.ico">
                        </div>
                    </div>
                </div>

                <!-- 2. Store Name & Multilingual Brand Names -->
                <div class="admin-form-card mb-24">
                    <h3 class="admin-card-title">🌐 Store Name & Multilingual Brand Titles</h3>
                    <p class="text-muted" style="font-size:12.5px; margin-bottom:16px;">Set your official store name rendered across all headers, titles, invoices, and payment receipts.</p>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Store Name (English) <span class="text-danger">*</span></label>
                            <input type="text" name="store_name" value="<?php echo htmlspecialchars($settings['store_name'] ?? 'AURA Luxury Store'); ?>" required class="form-control" placeholder="AURA Luxury Store">
                        </div>
                        <div class="form-group">
                            <label>Store Name (Arabic - العربية)</label>
                            <input type="text" name="store_name_ar" value="<?php echo htmlspecialchars($settings['store_name_ar'] ?? 'متجر أورا الفاخر'); ?>" class="form-control" dir="rtl" placeholder="متجر أورا الفاخر">
                        </div>
                        <div class="form-group">
                            <label>Store Name (Kurdish - بادینی)</label>
                            <input type="text" name="store_name_ku" value="<?php echo htmlspecialchars($settings['store_name_ku'] ?? 'فروشگەها لوکس یا ئۆرا'); ?>" class="form-control" dir="rtl" placeholder="فروشگەها لوکس یا ئۆرا">
                        </div>
                    </div>

                    <div class="form-row-3 mt-16">
                        <div class="form-group">
                            <label>Store Tagline / Slogan (English)</label>
                            <input type="text" name="store_tagline_en" value="<?php echo htmlspecialchars($settings['store_tagline_en'] ?? 'Exclusive Fashion & Swiss Watches'); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Store Tagline (Arabic - العربية)</label>
                            <input type="text" name="store_tagline_ar" value="<?php echo htmlspecialchars($settings['store_tagline_ar'] ?? 'أزياء راقية وساعات سويسرية حصرية'); ?>" class="form-control" dir="rtl">
                        </div>
                        <div class="form-group">
                            <label>Store Tagline (Kurdish - بادینی)</label>
                            <input type="text" name="store_tagline_ku" value="<?php echo htmlspecialchars($settings['store_tagline_ku'] ?? 'جل و بەرگێن شاهانە و دەمژمێرێن سویسری'); ?>" class="form-control" dir="rtl">
                        </div>
                    </div>
                </div>

                <!-- 3. Hero Section Headlines & Descriptions -->
                <div class="admin-form-card mb-24">
                    <h3 class="admin-card-title">✨ Homepage Hero Banner Headlines & Subtitles</h3>
                    <p class="text-muted" style="font-size:12.5px; margin-bottom:16px;">Customize the main headline displayed on the homepage showcase banner.</p>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Hero Headline (English)</label>
                            <input type="text" name="hero_headline_en" value="<?php echo htmlspecialchars($settings['hero_headline_en'] ?? 'Timeless Elegance & Modern Luxury'); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Hero Headline (Arabic)</label>
                            <input type="text" name="hero_headline_ar" value="<?php echo htmlspecialchars($settings['hero_headline_ar'] ?? 'الأناقة الخالدة والفخامة المعاصرة'); ?>" class="form-control" dir="rtl">
                        </div>
                        <div class="form-group">
                            <label>Hero Headline (Kurdish)</label>
                            <input type="text" name="hero_headline_ku" value="<?php echo htmlspecialchars($settings['hero_headline_ku'] ?? 'جوانییا بێ دوماهیک و لوکسیا هەڤچەرخ'); ?>" class="form-control" dir="rtl">
                        </div>
                    </div>

                    <div class="form-row-3 mt-16">
                        <div class="form-group">
                            <label>Hero Subtitle (English)</label>
                            <textarea name="hero_subtitle_en" rows="2" class="form-control"><?php echo htmlspecialchars($settings['hero_subtitle_en'] ?? 'Discover curated couture, haute horology, and signature fragrances delivered across Iraq & Kurdistan.'); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Hero Subtitle (Arabic)</label>
                            <textarea name="hero_subtitle_ar" rows="2" class="form-control" dir="rtl"><?php echo htmlspecialchars($settings['hero_subtitle_ar'] ?? 'اكتشف مجموعتنا المختارة من الأزياء الراقية والساعات السويسرية والعطور الفاخرة مع توصيل VIP لجميع مدن العراق وكردستان.'); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Hero Subtitle (Kurdish)</label>
                            <textarea name="hero_subtitle_ku" rows="2" class="form-control" dir="rtl"><?php echo htmlspecialchars($settings['hero_subtitle_ku'] ?? 'دیزاینێن تایبەت یێن جل و بەرگان، دەمژمێرێن ناڤدار و بهێنێن شاهانە ب گەهاندنا VIP بۆ هەمی پارێزگەهێن کوردستان و ئیراقێ.'); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- 4. Website Description & About (Footer & Meta) -->
                <div class="admin-form-card mb-24">
                    <h3 class="admin-card-title">📝 Store Description & About Text (Footer & SEO)</h3>
                    <p class="text-muted" style="font-size:12.5px; margin-bottom:16px;">Rendered in the store footer and search engine meta descriptions.</p>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Store Description (English)</label>
                            <textarea name="store_description_en" rows="3" class="form-control"><?php echo htmlspecialchars($settings['store_description_en'] ?? 'The premier destination for authenticated haute couture, Swiss timepieces, and luxury goods across Kurdistan Region and Federal Iraq.'); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Store Description (Arabic)</label>
                            <textarea name="store_description_ar" rows="3" class="form-control" dir="rtl"><?php echo htmlspecialchars($settings['store_description_ar'] ?? 'الوجهة الأولى للأزياء الراقية والساعات السويسرية الأصلية والمنتجات الفاخرة في إقليم كردستان وجميع محافظات العراق.'); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Store Description (Kurdish)</label>
                            <textarea name="store_description_ku" rows="3" class="form-control" dir="rtl"><?php echo htmlspecialchars($settings['store_description_ku'] ?? 'جهێ ئێکێ بۆ مۆدێلا شاهانە، دەمژمێرێن سویسری یێن ئەسلی و بەرهەمێن لوکس ل هەرێما کوردستانێ و هەمی باژێرێن ئیراقێ.'); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- 5. Top Announcement Bar & Contacts -->
                <div class="admin-form-card mb-24">
                    <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <div>
                            <h3 class="admin-card-title" style="margin:0;">📢 Top Announcement Bar & Boutique Contacts</h3>
                            <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Manage the top ticker text and official contact channels.</p>
                        </div>
                        <label class="switch-toggle" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" name="announcement_enabled" value="1" <?php echo ($settings['announcement_enabled'] ?? true) ? 'checked' : ''; ?>>
                            <span style="font-size:12px; font-weight:700;">Enable Announcement Bar</span>
                        </label>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Announcement Text (English)</label>
                            <input type="text" name="announcement_text_en" value="<?php echo htmlspecialchars($settings['announcement_text_en'] ?? '✨ Complimentary VIP Delivery on orders above $150 in Kurdistan & Iraq'); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Announcement Text (Arabic)</label>
                            <input type="text" name="announcement_text_ar" value="<?php echo htmlspecialchars($settings['announcement_text_ar'] ?? '✨ توصيل VIP مجاني للطلبات الأكثر من 150$ في كردستان والعراق'); ?>" class="form-control" dir="rtl">
                        </div>
                        <div class="form-group">
                            <label>Announcement Text (Kurdish)</label>
                            <input type="text" name="announcement_text_ku" value="<?php echo htmlspecialchars($settings['announcement_text_ku'] ?? '✨ گەهاندنا بێ بەرامبەر بۆ داخوازیێن ژ 150$ پتر ل کوردستان و ئیراقێ'); ?>" class="form-control" dir="rtl">
                        </div>
                    </div>

                    <div class="form-row-3 mt-16">
                        <div class="form-group">
                            <label>Official Store Contact Phone</label>
                            <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? '+964 750 123 4567'); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Official WhatsApp Number</label>
                            <input type="text" name="contact_whatsapp" value="<?php echo htmlspecialchars($settings['contact_whatsapp'] ?? '9647501234567'); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Support Email Address</label>
                            <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? 'concierge@aurastore.iq'); ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-row-3 mt-16">
                        <div class="form-group">
                            <label>Boutique Location (English)</label>
                            <input type="text" name="boutique_location_en" value="<?php echo htmlspecialchars($settings['boutique_location_en'] ?? 'Dream City Avenue, Erbil & KRO Luxury District, Duhok'); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Boutique Location (Arabic)</label>
                            <input type="text" name="boutique_location_ar" value="<?php echo htmlspecialchars($settings['boutique_location_ar'] ?? 'شارع دريم سيتي، أربيل ومجمع كرو الفاخر، دهوك'); ?>" class="form-control" dir="rtl">
                        </div>
                        <div class="form-group">
                            <label>Boutique Location (Kurdish)</label>
                            <input type="text" name="boutique_location_ku" value="<?php echo htmlspecialchars($settings['boutique_location_ku'] ?? 'جادا دریم ستی، هەولێر و تاخێ کەی ئاڕ ئۆ، دهۆک'); ?>" class="form-control" dir="rtl">
                        </div>
                    </div>
                </div>

                <!-- 6. Financials & Delivery Rules -->
                <div class="admin-form-card mb-24">
                    <h3 class="admin-card-title">📍 Financial Peg & Geographic Delivery Rules</h3>
                    
                    <div class="form-row-4" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
                        <div class="form-group">
                            <label>USD to IQD Exchange Rate <span class="text-danger">*</span></label>
                            <input type="number" name="exchange_rate_usd_to_iqd" value="<?php echo htmlspecialchars($rate); ?>" required class="form-control" style="font-weight:800; color:var(--accent-gold);">
                        </div>
                        <div class="form-group">
                            <label>Kurdistan Delivery Fee ($)</label>
                            <input type="number" step="0.5" name="delivery_kurdistan_fee" value="<?php echo htmlspecialchars($settings['delivery_kurdistan_fee'] ?? '5.00'); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Federal Iraq Delivery Fee ($)</label>
                            <input type="number" step="0.5" name="delivery_iraq_fee" value="<?php echo htmlspecialchars($settings['delivery_iraq_fee'] ?? '8.00'); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Free Delivery Threshold ($)</label>
                            <input type="number" step="10" name="free_delivery_threshold" value="<?php echo htmlspecialchars($settings['free_delivery_threshold'] ?? '150.00'); ?>" class="form-control">
                        </div>
                    </div>

                    <div class="mt-16" style="background:var(--bg-subtle); padding:16px; border-radius:8px; border:1px solid var(--border-color);">
                        <h4 style="margin-bottom:8px; color:var(--accent-gold);">📍 Geographic Delivery Rule Enforcement</h4>
                        <p style="font-size:13.5px; color:var(--text-secondary); margin:0;">
                            Store delivery is strictly locked to destinations inside <strong>Kurdistan Region</strong> (Duhok, Erbil, Sulaymaniyah, Zakho, Halabja, Soran, Akre) and <strong>Federal Iraq</strong> (Baghdad, Basra, Mosul, Kirkuk, Najaf, Karbala, Anbar, Babil, Diyala, Wasit, Maysan, Dhi Qar, Muthanna, Qadisiyyah, Saladin).
                        </p>
                    </div>
                </div>

                <div class="mt-24 text-right" style="display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary btn-luxury btn-lg">
                        💾 Save Website Branding & Store Settings
                    </button>
                </div>
            </form>
        </div>

    </div>
</section>

<!-- Dispatch & Logistics Manager Modal -->
<div class="modal-overlay" id="dispatchModalOverlay">
    <div class="modal-dialog" style="max-width:640px;">
        <button class="modal-close-btn" onclick="closeDispatchModal()">✕</button>
        <div class="modal-body">
            <h3 style="font-size:20px; font-weight:800; color:var(--accent-gold); margin-bottom:6px;">🚚 Courier Dispatch & Logistics Radar</h3>
            <p class="text-muted mb-20" id="dispatchModalOrderSub">Assign logistics details for order</p>

            <form action="admin.php" method="POST" id="dispatchForm">
                <input type="hidden" name="update_order_dispatch" value="1">
                <input type="hidden" name="order_id" id="dispOrderId" value="">

                <div class="form-row-2">
                    <div class="form-group">
                        <label>Delivery Status <span class="text-danger">*</span></label>
                        <select name="order_status" id="dispStatus" class="form-control">
                            <option value="Pending">Pending (Received)</option>
                            <option value="Processing">Processing (Satin Packaging)</option>
                            <option value="Shipped">Shipped (Dispatched to Courier)</option>
                            <option value="Out for Delivery">Out for Delivery (En Route)</option>
                            <option value="Delivered">Delivered (Completed)</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Logistics Courier Company</label>
                        <select name="courier" id="dispCourier" class="form-control">
                            <option value="AURA VIP Express Logistics">AURA VIP Express Logistics</option>
                            <option value="Lezzoo Express VIP">Lezzoo Express VIP</option>
                            <option value="Fast Iraq Express Cargo">Fast Iraq Express Cargo</option>
                            <option value="Aramex Iraq">Aramex Iraq</option>
                            <option value="DHL Express Iraq">DHL Express Iraq</option>
                        </select>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label>Assigned Driver Name</label>
                        <input type="text" name="driver_name" id="dispDriverName" placeholder="e.g. Rebwar Duhoki" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Driver Phone Number</label>
                        <input type="text" name="driver_phone" id="dispDriverPhone" placeholder="+964 750 998 1234" class="form-control">
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label>Tracking Code / Manifest ID</label>
                        <input type="text" name="tracking_code" id="dispTrackingCode" placeholder="e.g. AURA-EXP-84920" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Estimated Delivery Window</label>
                        <input type="text" name="estimated_delivery" id="dispEstDelivery" placeholder="e.g. Today, 4:00 PM - 6:00 PM" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label>Live Dispatcher Note (Visible to client on tracker)</label>
                    <textarea name="dispatch_notes" id="dispNotes" rows="3" class="form-control" placeholder="Package sealed in luxury presentation box. Courier en route to client address..."></textarea>
                </div>

                <div class="mt-20 text-right" style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeDispatchModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-luxury">💾 Save Logistics Dispatch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Luxury Invoice Printable Modal -->
<div class="modal-overlay" id="invoiceModalOverlay">
    <div class="modal-dialog" style="max-width:780px;">
        <button class="modal-close-btn" onclick="closeInvoiceModal()">✕</button>
        <div class="modal-body" style="padding:24px;">
            <div id="invoicePrintArea" class="printable-invoice-wrap">
                <!-- Injected via JS -->
            </div>
            <div class="mt-20 text-center">
                <button type="button" class="btn btn-primary btn-luxury" onclick="window.print()">🖨️ Print Luxury Invoice</button>
            </div>
        </div>
    </div>
</div>

<!-- Luxury Product Edit Modal -->
<div class="modal-overlay" id="editProductModalOverlay">
    <div class="modal-dialog" style="max-width:860px; max-height:92vh; overflow-y:auto;">
        <div class="modal-header" style="border-bottom:1px solid var(--border-color); padding:18px 24px; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; border-radius:8px; background:var(--accent-gold-bg); color:var(--accent-gold); display:flex; align-items:center; justify-content:center; font-size:20px;">
                    ✏️
                </div>
                <div>
                    <h3 class="modal-title" style="margin:0; font-size:18px;">Edit Luxury Piece <span id="editProductModalIdBadge" class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); border-color:var(--accent-gold);">#1</span></h3>
                    <p class="text-muted" style="margin:2px 0 0; font-size:12px;" id="editProductModalSub">Update pricing, old price, badges, images, multilingual details, and inventory</p>
                </div>
            </div>
            <button class="modal-close-btn" type="button" onclick="closeEditProductModal()">✕</button>
        </div>
        
        <div class="modal-body" style="padding:24px;">
            <form action="admin.php" method="POST" id="editProductForm">
                <input type="hidden" name="update_product" value="1">
                <input type="hidden" name="edit_prod_id" id="editProdId" value="">

                <!-- Section 1: Pricing, Old Price & Category -->
                <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px;">💰 Pricing & Inventory (IQD)</span>
                        <div id="editDiscountBadge" style="display:none; font-size:11.5px; padding:3px 10px; border-radius:12px; background:#10b981; color:#ffffff; font-weight:700;">
                            30% OFF
                        </div>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Category <span class="text-danger">*</span></label>
                            <select name="edit_prod_category" id="editProdCategory" required class="form-control">
                                <option value="clothes">Clothes (جلوبەرگ)</option>
                                <option value="watches">Watches (دەمژمێر)</option>
                                <option value="perfumes">Perfumes (عەتر و بێهن)</option>
                                <option value="accessories">Accessories (ئەکسسوارات)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Current Price (IQD) <span class="text-danger">*</span></label>
                            <input type="number" step="500" name="edit_prod_price" id="editProdPrice" required class="form-control" placeholder="240000" oninput="calculateDiscountPreview()">
                            <small class="text-muted">Selling price in Iraqi Dinar</small>
                        </div>
                        <div class="form-group">
                            <label>Old Price (IQD) (Original / Discounted)</label>
                            <input type="number" step="500" name="edit_prod_old_price" id="editProdOldPrice" class="form-control" placeholder="310000" oninput="calculateDiscountPreview()">
                            <small class="text-muted">Shows strikethrough price if higher than current price</small>
                        </div>
                    </div>

                    <div class="form-row-3" style="margin-top:12px;">
                        <div class="form-group">
                            <label>Stock Count</label>
                            <input type="number" name="edit_prod_stock" id="editProdStock" class="form-control" value="10">
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:10px; margin-top:24px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13.5px;">
                                <input type="checkbox" name="edit_prod_featured" id="editProdFeatured" value="1" style="width:18px; height:18px; accent-color:var(--accent-gold);">
                                <span>⭐ Featured on Homepage Showcase</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label>Quick Price Helpers (IQD)</label>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                <button type="button" class="btn btn-ghost btn-xs" onclick="document.getElementById('editProdPrice').value = Math.round(Number(document.getElementById('editProdPrice').value || 100000) * 0.9); calculateDiscountPreview();">10% Off</button>
                                <button type="button" class="btn btn-ghost btn-xs" onclick="document.getElementById('editProdPrice').value = Math.round(Number(document.getElementById('editProdPrice').value || 100000) * 0.8); calculateDiscountPreview();">20% Off</button>
                                <button type="button" class="btn btn-ghost btn-xs" onclick="document.getElementById('editProdPrice').value = Math.round(Number(document.getElementById('editProdPrice').value || 100000) * 0.5); calculateDiscountPreview();">50% Off</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Badges & Promotion Tags -->
                <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px;">🏷️ Promotional Badge / Ribbon</span>
                    </div>

                    <div style="margin-bottom:12px;">
                        <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:6px;">Quick Presets (Click to Auto-fill Trilingual Badges):</label>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('⚡ 50% OFF', '⚡ خصم 50%', '⚡ داشکاندنا %50')">⚡ 50% OFF</button>
                            <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('🔥 Best Seller', '🔥 الأكثر مبيعاً', '🔥 پڕفرۆشترین')">🔥 Best Seller</button>
                            <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('💎 Limited Edition', '💎 إصدار محدود', '💎 وەشانەکا سنوردار')">💎 Limited Edition</button>
                            <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('✨ New Arrival', '✨ وصل حديثاً', '✨ نوی گەهشتی')">✨ New Arrival</button>
                            <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('👑 Royal VIP', '👑 فاخر ملكي', '👑 شاهانە و نازک')">👑 Royal VIP</button>
                            <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('🏷️ Special Deal', '🏷️ عرض خاص', '🏷️ پێشنیارا تایبەت')">🏷️ Special Deal</button>
                            <button type="button" class="badge-tag text-danger" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('', '', '')">✕ Clear Badge</button>
                        </div>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Badge (English)</label>
                            <input type="text" name="edit_prod_badge" id="editProdBadge" class="form-control" placeholder="e.g. Best Seller">
                        </div>
                        <div class="form-group">
                            <label>Badge (Arabic - العربية)</label>
                            <input type="text" name="edit_prod_badge_ar" id="editProdBadgeAr" class="form-control" placeholder="مثال: الأكثر مبيعاً">
                        </div>
                        <div class="form-group">
                            <label>Badge (Kurdish - کوردی بادینی)</label>
                            <input type="text" name="edit_prod_badge_ku" id="editProdBadgeKu" class="form-control" placeholder="وەکی: پڕفرۆشترین">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Image & Media Gallery -->
                <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:20px;">
                    <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">🖼️ Product Imagery & Gallery</span>
                    
                    <div style="display:grid; grid-template-columns:100px 1fr; gap:16px; align-items:start; margin-bottom:14px;">
                        <div style="text-align:center;">
                            <img id="editImageLivePreview" src="https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80" alt="Preview" style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:2px solid var(--accent-gold);">
                            <small class="text-muted" style="display:block; font-size:10.5px; margin-top:4px;">Main Preview</small>
                        </div>

                        <div>
                            <div class="form-group mb-12">
                                <label>Primary Cover Image URL <span class="text-danger">*</span></label>
                                <input type="url" name="edit_prod_image" id="editProdImage" required class="form-control" placeholder="https://images.unsplash.com/..." oninput="updateEditImagePreview()">
                            </div>

                            <div class="form-group">
                                <label>Additional Gallery Images (Comma-Separated URLs)</label>
                                <textarea name="edit_prod_gallery" id="editProdGallery" rows="2" class="form-control" placeholder="https://image1.jpg, https://image2.jpg, https://image3.jpg"></textarea>
                                <small class="text-muted">Enter multiple image URLs separated by commas for multi-angle thumbnail slider</small>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Luxury Presets -->
                    <div>
                        <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:6px;">Sample Luxury Photography Presets:</label>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80')">👔 Velvet Blazer</button>
                            <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80')">⌚ Swiss Watch</button>
                            <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80')">✨ Arabian Oud Perfume</button>
                            <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80')">👜 Italian Leather Bag</button>
                            <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1617127365659-c47fa864d8bc?auto=format&fit=crop&w=800&q=80')">🎩 Double-Breasted Suit</button>
                            <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=800&q=80')">⏱️ Chronograph Watch</button>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Trilingual Titles -->
                <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:20px;">
                    <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">🌐 Trilingual Product Titles</span>
                    
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Title (English) <span class="text-danger">*</span></label>
                            <input type="text" name="edit_prod_title_en" id="editProdTitleEn" required class="form-control" placeholder="e.g. Royal Midnight Velvet Blazer">
                        </div>
                        <div class="form-group">
                            <label>Title (Arabic - العربية)</label>
                            <input type="text" name="edit_prod_title_ar" id="editProdTitleAr" class="form-control" placeholder="مثال: بليزر مخملي ملكي">
                        </div>
                        <div class="form-group">
                            <label>Title (Kurdish - کوردی بادینی)</label>
                            <input type="text" name="edit_prod_title_ku" id="editProdTitleKu" class="form-control" placeholder="وەکی: ساکێ مەخمەلی یێ شاهانە">
                        </div>
                    </div>
                </div>

                <!-- Section 5: Sizes, Colors & Specifications -->
                <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:20px;">
                    <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">📐 Sizes & Color Editions</span>
                    
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Available Sizes (Comma-separated)</label>
                            <input type="text" name="edit_prod_sizes" id="editProdSizes" class="form-control" placeholder="e.g. S, M, L, XL or 41mm Case or 100ml / 3.4 oz">
                        </div>
                        <div class="form-group">
                            <label>Colors / Finishes (Comma-separated)</label>
                            <input type="text" name="edit_prod_colors" id="editProdColors" class="form-control" placeholder="e.g. Midnight Blue, Obsidian Black, Gold Edition">
                        </div>
                    </div>
                </div>

                <!-- Section 6: Trilingual Descriptions -->
                <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:20px;">
                    <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">📝 Trilingual Descriptions & Craftsmanship</span>
                    
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Description (English)</label>
                            <textarea name="edit_prod_desc_en" id="editProdDescEn" rows="3" class="form-control" placeholder="Craftsmanship details, materials, origins..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Description (Arabic)</label>
                            <textarea name="edit_prod_desc_ar" id="editProdDescAr" rows="3" class="form-control" placeholder="تفاصيل الصناعة اليدوية الفاخرة والمواد..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Description (Kurdish)</label>
                            <textarea name="edit_prod_desc_ku" id="editProdDescKu" rows="3" class="form-control" placeholder="هویرکاریێن دروستکرنێ و کەرەستەیێن رەسەن..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:12px; padding-top:10px; border-top:1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary" onclick="closeEditProductModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-luxury btn-lg">💾 Save Product Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function switchAdminTab(tabId, btn) {
    document.querySelectorAll('.admin-tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
    const pane = document.getElementById(tabId);
    if (pane) pane.classList.add('active');
    if (btn) btn.classList.add('active');
}

function togglePasswordVisibility(inputId) {
    const el = document.getElementById(inputId);
    if (el) el.type = el.type === 'password' ? 'text' : 'password';
}

function openEditProductModal(product) {
    if (!product) return;
    document.getElementById('editProdId').value = product.id;
    document.getElementById('editProductModalIdBadge').innerText = '#' + product.id;
    
    const pTitleEn = typeof product.title === 'object' ? (product.title.en || '') : product.title;
    const pTitleAr = typeof product.title === 'object' ? (product.title.ar || pTitleEn) : pTitleEn;
    const pTitleKu = typeof product.title === 'object' ? (product.title.ku || pTitleEn) : pTitleEn;

    document.getElementById('editProductModalSub').innerText = 'Editing: ' + pTitleEn + ' (' + (product.category || 'luxury') + ')';
    document.getElementById('editProdTitleEn').value = pTitleEn;
    document.getElementById('editProdTitleAr').value = pTitleAr;
    document.getElementById('editProdTitleKu').value = pTitleKu;

    document.getElementById('editProdCategory').value = product.category || 'clothes';
    document.getElementById('editProdPrice').value = product.price || 0;
    document.getElementById('editProdOldPrice').value = product.old_price || '';
    document.getElementById('editProdStock').value = product.stock !== undefined ? product.stock : 10;
    document.getElementById('editProdFeatured').checked = !!product.featured;

    document.getElementById('editProdBadge').value = product.badge || '';
    document.getElementById('editProdBadgeAr').value = product.badge_ar || product.badge || '';
    document.getElementById('editProdBadgeKu').value = product.badge_ku || product.badge || '';

    const mainImg = product.image || '';
    document.getElementById('editProdImage').value = mainImg;
    const gallery = Array.isArray(product.images) ? product.images.join(', ') : (mainImg || '');
    document.getElementById('editProdGallery').value = gallery;
    updateEditImagePreview();

    const sizes = Array.isArray(product.sizes) ? product.sizes.join(', ') : (product.sizes || '');
    document.getElementById('editProdSizes').value = sizes;

    const colors = Array.isArray(product.colors) ? product.colors.join(', ') : (product.colors || '');
    document.getElementById('editProdColors').value = colors;

    const pDescEn = typeof product.description === 'object' ? (product.description.en || '') : (product.description || '');
    const pDescAr = typeof product.description === 'object' ? (product.description.ar || pDescEn) : pDescEn;
    const pDescKu = typeof product.description === 'object' ? (product.description.ku || pDescEn) : pDescEn;

    document.getElementById('editProdDescEn').value = pDescEn;
    document.getElementById('editProdDescAr').value = pDescAr;
    document.getElementById('editProdDescKu').value = pDescKu;

    calculateDiscountPreview();
    document.getElementById('editProductModalOverlay').classList.add('open');
}

function closeEditProductModal() {
    document.getElementById('editProductModalOverlay').classList.remove('open');
}

function updateEditImagePreview() {
    const url = document.getElementById('editProdImage').value;
    const imgEl = document.getElementById('editImageLivePreview');
    if (imgEl && url) {
        imgEl.src = url;
    }
}

function setEditImagePreset(url) {
    document.getElementById('editProdImage').value = url;
    const galleryEl = document.getElementById('editProdGallery');
    if (!galleryEl.value || galleryEl.value.indexOf(url) === -1) {
        galleryEl.value = url;
    }
    updateEditImagePreview();
}

function setEditBadgePreset(en, ar, ku) {
    document.getElementById('editProdBadge').value = en;
    document.getElementById('editProdBadgeAr').value = ar;
    document.getElementById('editProdBadgeKu').value = ku;
}

function calculateDiscountPreview() {
    const price = Number(document.getElementById('editProdPrice').value) || 0;
    const oldPrice = Number(document.getElementById('editProdOldPrice').value) || 0;
    const badgeEl = document.getElementById('editDiscountBadge');
    if (oldPrice > price && price > 0) {
        const pct = Math.round(((oldPrice - price) / oldPrice) * 100);
        const saveIqd = oldPrice - price;
        badgeEl.style.display = 'inline-block';
        badgeEl.innerText = pct + '% OFF (Save ' + saveIqd.toLocaleString() + ' IQD)';
    } else {
        badgeEl.style.display = 'none';
    }
}

function openDispatchModal(order) {
    document.getElementById('dispOrderId').value = order.order_id || '';
    document.getElementById('dispatchModalOrderSub').innerText = 'Updating shipment details for Order #' + (order.order_id || '') + ' (' + (order.customer_name || '') + ')';
    document.getElementById('dispStatus').value = order.order_status || 'Pending';
    document.getElementById('dispCourier').value = order.courier || 'AURA VIP Express Logistics';
    document.getElementById('dispDriverName').value = order.driver_name || '';
    document.getElementById('dispDriverPhone').value = order.driver_phone || '';
    document.getElementById('dispTrackingCode').value = order.tracking_code || ('AURA-EXP-' + order.order_id.replace('ORD-', ''));
    document.getElementById('dispEstDelivery').value = order.estimated_delivery || '';
    document.getElementById('dispNotes').value = order.dispatch_notes || '';
    document.getElementById('dispatchModalOverlay').classList.add('open');
}

function closeDispatchModal() {
    document.getElementById('dispatchModalOverlay').classList.remove('open');
}

function filterOrdersTable() {
    const q = (document.getElementById('orderSearchInput').value || '').toLowerCase();
    const st = document.getElementById('orderStatusFilter').value;
    const rows = document.querySelectorAll('#ordersTableMain tbody tr');
    rows.forEach(row => {
        const rowSearch = row.getAttribute('data-search') || '';
        const rowStatus = row.getAttribute('data-status') || '';
        const matchesQ = !q || rowSearch.includes(q);
        const matchesSt = !st || rowStatus === st;
        row.style.display = (matchesQ && matchesSt) ? '' : 'none';
    });
}

function printOrderInvoice(order) {
    const container = document.getElementById('invoicePrintArea');
    const items = order.items || [];
    let itemsHtml = '';
    const ordTot = order.total || 0;
    const ordIqd = order.total_iqd || ordTot;

    items.forEach((it, idx) => {
        const itTitle = typeof it.title === 'object' ? (it.title.en || it.title) : it.title;
        itemsHtml += `
            <tr style="border-bottom:1px solid #e5e7eb;">
                <td style="padding:10px 0;"><strong>${itTitle}</strong><br><small style="color:#6b7280;">Qty: ${it.quantity} ${it.size ? '• Size: ' + it.size : ''}</small></td>
                <td style="padding:10px 0; text-align:right;">${Math.round(it.price * it.quantity).toLocaleString()} IQD</td>
            </tr>
        `;
    });

    container.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #111827; padding-bottom:20px; margin-bottom:24px;">
            <div>
                <h1 style="font-size:26px; font-weight:800; letter-spacing:2px; margin:0; color:#111827;">AURA</h1>
                <span style="font-size:12px; letter-spacing:3px; color:#d97706; font-weight:700;">LUXURY STORE • IRAQ</span>
                <p style="font-size:12px; color:#6b7280; margin:4px 0 0;">VIP Concierge & Fulfillment Hub</p>
            </div>
            <div style="text-align:right;">
                <h2 style="font-size:18px; font-weight:800; margin:0; color:#111827;">TAX INVOICE</h2>
                <div style="font-family:monospace; font-size:14px; font-weight:700; color:#d97706;">${order.order_id}</div>
                <div style="font-size:12px; color:#6b7280;">${new Date(order.created_at).toLocaleDateString()}</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; font-size:13px;">
            <div>
                <strong style="color:#111827; text-transform:uppercase; font-size:11px; letter-spacing:1px;">Billed & Delivered To:</strong>
                <div style="font-weight:700; font-size:15px; margin-top:4px;">${order.customer_name}</div>
                <div style="color:#4b5563;">${order.phone}</div>
                <div style="color:#4b5563;">${order.city}, ${order.address}</div>
            </div>
            <div style="text-align:right;">
                <strong style="color:#111827; text-transform:uppercase; font-size:11px; letter-spacing:1px;">Logistics & Payment:</strong>
                <div style="font-weight:700; color:#111827; margin-top:4px;">Method: ${order.payment_method}</div>
                <div style="color:#4b5563;">Courier: ${order.courier || 'AURA Express'}</div>
                <div style="color:#4b5563;">Tracking: <code>${order.tracking_code || order.order_id}</code></div>
            </div>
        </div>

        <table style="width:100%; border-collapse:collapse; font-size:13.5px; margin-bottom:20px;">
            <thead>
                <tr style="border-bottom:2px solid #e5e7eb; text-align:left;">
                    <th style="padding:8px 0;">Item Description</th>
                    <th style="padding:8px 0; text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                ${itemsHtml}
            </tbody>
        </table>

        <div style="border-top:2px solid #111827; padding-top:16px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <span style="font-size:12px; color:#6b7280;">Payment Terms:</span><br>
                <strong style="font-size:14px; color:#111827;">Official Currency: Iraqi Dinar (IQD)</strong>
            </div>
            <div style="text-align:right;">
                <span style="font-size:12px; color:#6b7280;">Total Payable:</span><br>
                <strong style="font-size:24px; font-weight:800; color:#d97706;">${Math.round(ordTot).toLocaleString()} IQD</strong>
            </div>
        </div>
    `;

    document.getElementById('invoiceModalOverlay').classList.add('open');
}

function closeInvoiceModal() {
    document.getElementById('invoiceModalOverlay').classList.remove('open');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
