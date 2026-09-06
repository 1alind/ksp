<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/db.php';

// Handle JSON / AJAX requests (e.g. package confirmation or status update)
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $jsonReq = json_decode($rawInput, true);
    if (is_array($jsonReq)) {
        // Confirm Package Preparation via AJAX
        if (isset($jsonReq['action']) && $jsonReq['action'] === 'confirm_package') {
            header('Content-Type: application/json');
            $oid = trim($jsonReq['order_id'] ?? '');
            $pkgId = trim($jsonReq['tracking_code'] ?? '');
            $courier = trim($jsonReq['courier'] ?? 'Kurdistan Express');
            $notes = trim($jsonReq['dispatch_notes'] ?? '');
            $isEdit = !empty($jsonReq['is_edit_mode']);

            if (!empty($oid) && !empty($pkgId)) {
                $updateData = [
                    'tracking_code' => $pkgId,
                    'courier' => $courier,
                    'estimated_delivery' => 'Estimated Arrival: Within 24 – 72 Hours'
                ];
                if (!$isEdit) {
                    $updateData['order_status'] = 'Ready to Ship';
                }
                if (!empty($notes)) {
                    $updateData['dispatch_notes'] = $notes;
                }
                $updated = update_order_full($oid, $updateData);
                echo json_encode([
                    'success' => (bool)$updated,
                    'order_id' => $oid,
                    'tracking_code' => $pkgId,
                    'courier' => $courier,
                    'order_status' => $isEdit ? null : 'Ready to Ship'
                ]);
                exit;
            }
            echo json_encode(['success' => false, 'error' => 'Missing order ID or Package ID']);
            exit;
        }

        // Standard status update
        if (isset($jsonReq['order_id']) && isset($jsonReq['order_status'])) {
            header('Content-Type: application/json');
            $updated = update_order_status($jsonReq['order_id'], $jsonReq['order_status']);
            echo json_encode(['success' => (bool)$updated, 'order_id' => $jsonReq['order_id'], 'order_status' => $jsonReq['order_status']]);
            exit;
        }
    }
}

// Handle query parameter AJAX actions
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'update_status') {
        header('Content-Type: application/json');
        $oid = trim($_POST['order_id'] ?? $_GET['order_id'] ?? '');
        $status = trim($_POST['order_status'] ?? $_GET['order_status'] ?? 'Waiting');
        $updated = update_order_status($oid, $status);
        echo json_encode(['success' => (bool)$updated, 'order_id' => $oid, 'order_status' => $status]);
        exit;
    }
}

$flashMsg = null;
$flashType = 'success';

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CONFIRM PACKAGE PREPARATION & LINK DELIVERY COMPANY PACKAGE ID
    if (isset($_POST['confirm_package_prepared'])) {
        $oid = trim($_POST['order_id'] ?? '');
        $pkgId = trim($_POST['tracking_code'] ?? '');
        $courier = trim($_POST['courier'] ?? 'Kurdistan Express');
        $notes = trim($_POST['dispatch_notes'] ?? '');
        $isEdit = !empty($_POST['is_edit_mode']);

        if (!empty($oid) && !empty($pkgId)) {
            $updateData = [
                'tracking_code' => $pkgId,
                'courier' => $courier,
                'estimated_delivery' => 'Estimated Arrival: Within 24 – 72 Hours'
            ];
            if (!$isEdit) {
                $updateData['order_status'] = 'Ready to Ship';
            }
            if (!empty($notes)) {
                $updateData['dispatch_notes'] = $notes;
            }

            update_order_full($oid, $updateData);
            if ($isEdit) {
                $flashMsg = "✓ Order #{$oid} Package ID updated to [{$pkgId}] ({$courier}).";
            } else {
                $flashMsg = "✓ Order #{$oid} package confirmed prepared! Status automatically set to \"Ready to Ship\" and linked with Delivery Company Package ID: {$pkgId} ({$courier}).";
            }
        } elseif (empty($pkgId)) {
            $flashMsg = "⚠️ Please enter the delivery company Package ID / Tracking code.";
            $flashType = 'error';
        }
    }
    // 2. DELETE ORDER
    elseif (isset($_POST['delete_order_id'])) {
        $oid = trim($_POST['delete_order_id']);
        if (!empty($oid)) {
            delete_order($oid);
            $flashMsg = "✓ Order #{$oid} was permanently removed.";
        }
    }
}

$pageTitle = 'Orders & Packaging Radar | AURA Luxury Admin';
$adminActive = 'orders';
$ordersList = get_all_orders();
$productsList = get_all_products();

$waitingCount = 0;
$readyCount = 0;
$shippedCount = 0;
$deliveredCount = 0;
$totalRevenueIqd = 0;

foreach ($ordersList as $o) {
    $totalRevenueIqd += ($o['total'] ?? 0);
    $st = $o['order_status'] ?? 'Waiting';
    if ($st === 'Waiting' || $st === 'Pending') {
        $waitingCount++;
    } elseif ($st === 'Ready to Ship' || $st === 'rdy to ship' || $st === 'Processing') {
        $readyCount++;
    } elseif ($st === 'Shipped' || $st === 'Out for Delivery') {
        $shippedCount++;
    } elseif ($st === 'Delivered') {
        $deliveredCount++;
    }
}

$activePage = 'admin';
require_once __DIR__ . '/../header.php';
?>

<section class="admin-section" style="padding: 24px 0 60px;">
    <div class="container">

        <!-- Unified Admin Navigation Bar -->
        <?php require_once __DIR__ . '/nav.php'; ?>

        <?php if ($flashMsg): ?>
            <div style="background:<?php echo $flashType === 'error' ? 'rgba(239,68,68,0.12)' : 'rgba(34,197,94,0.12)'; ?>; border:1px solid <?php echo $flashType === 'error' ? '#ef4444' : '#22c55e'; ?>; color:<?php echo $flashType === 'error' ? '#ef4444' : '#22c55e'; ?>; border-radius:8px; padding:14px 20px; margin-bottom:24px; font-weight:700; display:flex; align-items:center; justify-content:space-between;">
                <span><?php echo $flashMsg; ?></span>
                <button type="button" onclick="this.parentElement.style.display='none'" style="background:none; border:none; color:inherit; cursor:pointer; font-size:16px;">✕</button>
            </div>
        <?php endif; ?>

        <!-- Automated Fulfillment Workflow Banner -->
        <div style="background:linear-gradient(135deg, rgba(217,119,6,0.08), rgba(99,102,241,0.06)); border:1px solid rgba(217,119,6,0.25); border-radius:10px; padding:16px 20px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; border-radius:8px; background:rgba(217,119,6,0.15); color:var(--accent-gold); display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0;">
                    ⚡
                </div>
                <div>
                    <h4 style="margin:0; font-size:14.5px; font-weight:800; color:var(--text-primary);">
                        Automated Delivery Company Fulfillment Pipeline
                    </h4>
                    <p style="margin:3px 0 0; font-size:12.5px; color:var(--text-secondary);">
                        <strong>Step 1:</strong> Order placed (Waiting) → <strong>Step 2:</strong> Click <em>Confirm Package</em> & link delivery company Package ID (sets Ready to Ship) → <strong>Step 3:</strong> Delivery company scans automatically update to <em>Shipped</em>, <em>Out for Delivery</em>, and <em>Delivered</em> via API.
                    </p>
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-outline btn-sm" onclick="openWebhookSimulatorModal()" style="font-size:12px; font-weight:700; border-color:var(--accent-gold); color:var(--accent-gold); display:inline-flex; align-items:center; gap:6px;">
                    ⚡ Delivery Company API & Webhook Simulator
                </button>
            </div>
        </div>

        <!-- Orders Metric Sub-Cards -->
        <div class="admin-metrics-grid" style="margin-bottom:24px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="admin-metric-card">
                <span class="m-icon">📋</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_metric_total_orders', 'Total Client Orders'); ?></span>
                    <strong class="m-value"><?php echo count($ordersList); ?> <?php echo adm_t('admin_nav_orders', 'Orders'); ?></strong>
                    <span class="iqd-price-pill"><?php echo number_format($totalRevenueIqd); ?> IQD</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">⏳</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_status_waiting', 'Waiting Preparation'); ?></span>
                    <strong class="m-value" style="color:#eab308;"><?php echo $waitingCount; ?></strong>
                    <span class="iqd-price-pill" style="color:#eab308;">Awaiting Packaging</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">📦</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_status_ready_to_ship', 'Ready to Ship'); ?></span>
                    <strong class="m-value" style="color:#6366f1;"><?php echo $readyCount; ?></strong>
                    <span class="iqd-price-pill" style="color:#6366f1;">Package ID Linked</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">🚚</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_status_shipped', 'Shipped / In Transit'); ?></span>
                    <strong class="m-value text-primary"><?php echo $shippedCount; ?></strong>
                    <span class="iqd-price-pill"><?php echo adm_t('admin_orders_in_transit', 'Courier Dispatched'); ?></span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">✅</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_status_delivered', 'Delivered'); ?></span>
                    <strong class="m-value" style="color:#22c55e;"><?php echo $deliveredCount; ?></strong>
                    <span class="iqd-price-pill"><?php echo adm_t('admin_orders_verified', '100% Completed'); ?></span>
                </div>
            </div>
        </div>

        <!-- Main Orders Table Card -->
        <div class="admin-table-card">
            <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:12px;">
                <div>
                    <h3 class="admin-card-title" style="margin:0; font-size:18px;">📦 <?php echo adm_t('admin_orders_title', 'Client Orders & Package Fulfillment'); ?></h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Manage prepared packages, link courier tracking IDs, and monitor automated API delivery updates.</p>
                </div>
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <input type="text" id="orderSearchInput" onkeyup="filterOrdersTable()" placeholder="<?php echo adm_t('admin_search_orders', 'Search orders, Package ID, Client...'); ?>" class="form-control" style="max-width:260px; padding:8px 12px; font-size:13px;">
                    <select id="orderStatusFilter" onchange="filterOrdersTable()" class="form-control" style="max-width:180px; padding:8px 12px; font-size:13px;">
                        <option value=""><?php echo adm_t('admin_filter_all_status', 'All Statuses'); ?></option>
                        <option value="Waiting"><?php echo adm_t('admin_status_waiting', 'Waiting'); ?></option>
                        <option value="Ready to Ship"><?php echo adm_t('admin_status_ready_to_ship', 'Ready to Ship'); ?></option>
                        <option value="Shipped"><?php echo adm_t('admin_status_shipped', 'Shipped'); ?></option>
                        <option value="Out for Delivery"><?php echo adm_t('admin_status_out_for_delivery', 'Out for Delivery'); ?></option>
                        <option value="Delivered"><?php echo adm_t('admin_status_delivered', 'Delivered'); ?></option>
                        <option value="Cancelled"><?php echo adm_t('admin_status_cancelled', 'Cancelled'); ?></option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="admin-table" id="ordersTableMain">
                    <thead>
                        <tr>
                            <th><?php echo adm_t('admin_order_col_id', 'Order ID'); ?></th>
                            <th><?php echo adm_t('admin_order_col_date', 'Date'); ?></th>
                            <th><?php echo adm_t('admin_order_col_client', 'Client & Destination'); ?></th>
                            <th><?php echo adm_t('admin_order_col_items', 'Items'); ?></th>
                            <th><?php echo adm_t('admin_order_col_total', 'Total (IQD)'); ?></th>
                            <th><?php echo adm_t('admin_order_col_payment', 'Payment'); ?></th>
                            <th>Courier & Package ID</th>
                            <th>Order Status & Preparation</th>
                            <th><?php echo adm_t('admin_order_col_actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordersList as $ord): 
                            $ordTot = $ord['total'] ?? 0;
                            $itemsCount = count($ord['items'] ?? []);
                            $st = $ord['order_status'] ?? 'Waiting';
                            if ($st === 'Pending') $st = 'Waiting';
                            if ($st === 'Processing') $st = 'Ready to Ship';
                            $pkgId = trim($ord['tracking_code'] ?? '');
                        ?>
                            <tr id="orderRow_<?php echo htmlspecialchars($ord['order_id']); ?>" data-status="<?php echo htmlspecialchars($st); ?>" data-search="<?php echo strtolower($ord['order_id'] . ' ' . $ord['customer_name'] . ' ' . $ord['phone'] . ' ' . $ord['city'] . ' ' . $pkgId); ?>">
                                <td>
                                    <strong><a href="/track.php?order_id=<?php echo urlencode($ord['order_id']); ?>" style="color:var(--accent-gold); text-decoration:none; font-family:monospace; font-size:13.5px;"><?php echo htmlspecialchars($ord['order_id']); ?></a></strong>
                                </td>
                                <td><small><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></small></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($ord['customer_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($ord['city']); ?> • <?php echo htmlspecialchars($ord['phone']); ?></small>
                                </td>
                                <td><?php echo $itemsCount; ?> <?php echo adm_t('admin_pcs', 'pcs'); ?></td>
                                <td>
                                    <strong class="text-primary font-bold"><?php echo number_format($ordTot); ?> IQD</strong>
                                </td>
                                <td>
                                    <span class="badge-tag"><?php echo htmlspecialchars($ord['payment_method']); ?></span><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($ord['payment_status'] ?? 'Pending'); ?></small>
                                </td>
                                <td>
                                    <div class="courier-info-chip" id="courierCell_<?php echo htmlspecialchars($ord['order_id']); ?>">
                                        <?php if (!empty($pkgId)): ?>
                                            <span class="courier-name" style="font-weight:700; color:var(--text-primary); font-size:13px; display:block;">
                                                <?php echo htmlspecialchars(!empty($ord['courier']) ? $ord['courier'] : 'Express Delivery'); ?>
                                            </span>
                                            <div style="margin-top:4px;">
                                                <span style="font-size:11px; color:var(--text-muted);">Package ID:</span>
                                                <code style="font-size:11.5px; font-weight:800; color:var(--accent-gold); background:rgba(217,119,6,0.1); padding:2px 6px; border-radius:4px; border:1px solid rgba(217,119,6,0.25);">
                                                    <?php echo htmlspecialchars($pkgId); ?>
                                                </code>
                                            </div>
                                            <?php if ($st === 'Ready to Ship' || $st === 'Waiting'): ?>
                                                <button type="button" class="btn btn-ghost btn-xs" onclick="openPackageConfirmModal(<?php echo htmlspecialchars(json_encode($ord)); ?>, true)" style="font-size:10.5px; padding:2px 6px; margin-top:4px; color:var(--text-muted); cursor:pointer;">
                                                    ✏️ Edit Package ID
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:12px; font-style:italic;">
                                                ⏳ Unassigned • Awaiting Preparation
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-status-wrapper" id="orderStatusWrap_<?php echo htmlspecialchars($ord['order_id']); ?>">
                                        <?php if ($st === 'Waiting'): ?>
                                            <!-- Waiting Status & Confirm Button -->
                                            <span class="badge-tag" style="background:rgba(234,179,8,0.15); color:#eab308; border:1px solid rgba(234,179,8,0.4); font-weight:700; padding:4px 10px; border-radius:6px; font-size:12px; display:inline-block;">
                                                ⏳ <?php echo adm_t('admin_status_waiting', 'Waiting'); ?>
                                            </span>
                                            <div style="margin-top:6px;">
                                                <button type="button" class="btn btn-primary btn-xs" onclick="openPackageConfirmModal(<?php echo htmlspecialchars(json_encode($ord)); ?>)" style="background:linear-gradient(135deg, #d97706, #b45309); border-color:#d97706; color:#fff; font-weight:700; font-size:12px; padding:6px 12px; border-radius:6px; cursor:pointer; display:inline-flex; align-items:center; gap:5px; box-shadow:0 2px 5px rgba(217,119,6,0.25);">
                                                    📦 Confirm Package
                                                </button>
                                            </div>
                                        <?php elseif ($st === 'Ready to Ship'): ?>
                                            <!-- Ready to Ship Badge & API Waiting note -->
                                            <span class="badge-tag" style="background:rgba(99,102,241,0.15); color:#6366f1; border:1px solid rgba(99,102,241,0.4); font-weight:700; padding:4px 10px; border-radius:6px; font-size:12px; display:inline-block;">
                                                📦 <?php echo adm_t('admin_status_ready_to_ship', 'Ready to Ship'); ?>
                                            </span>
                                            <small class="text-muted" style="display:block; font-size:11px; margin-top:4px;">
                                                🤖 Awaiting Courier Scan (API)
                                            </small>
                                        <?php elseif ($st === 'Shipped'): ?>
                                            <!-- Shipped Status (Automated via Delivery API) -->
                                            <span class="badge-tag" style="background:rgba(168,85,247,0.15); color:#a855f7; border:1px solid rgba(168,85,247,0.4); font-weight:700; padding:4px 10px; border-radius:6px; font-size:12px; display:inline-block;">
                                                🚚 <?php echo adm_t('admin_status_shipped', 'Shipped'); ?>
                                            </span>
                                            <small style="display:block; font-size:11px; color:#a855f7; margin-top:3px; font-weight:600;">
                                                ⚡ Auto-Updated via Delivery API
                                            </small>
                                        <?php elseif ($st === 'Out for Delivery'): ?>
                                            <!-- Out for Delivery Status -->
                                            <span class="badge-tag" style="background:rgba(249,115,22,0.15); color:#f97316; border:1px solid rgba(249,115,22,0.4); font-weight:700; padding:4px 10px; border-radius:6px; font-size:12px; display:inline-block;">
                                                🛵 <?php echo adm_t('admin_status_out_for_delivery', 'Out for Delivery'); ?>
                                            </span>
                                            <small style="display:block; font-size:11px; color:#f97316; margin-top:3px; font-weight:600;">
                                                ⚡ Auto-Updated via Delivery API
                                            </small>
                                        <?php elseif ($st === 'Delivered'): ?>
                                            <!-- Delivered Status -->
                                            <span class="badge-tag" style="background:rgba(34,197,94,0.15); color:#22c55e; border:1px solid rgba(34,197,94,0.4); font-weight:700; padding:4px 10px; border-radius:6px; font-size:12px; display:inline-block;">
                                                ✅ <?php echo adm_t('admin_status_delivered', 'Delivered'); ?>
                                            </span>
                                            <small style="display:block; font-size:11px; color:#22c55e; margin-top:3px; font-weight:600;">
                                                ⚡ Auto-Updated via Delivery API
                                            </small>
                                        <?php else: ?>
                                            <!-- Cancelled / Other -->
                                            <span class="badge-tag" style="background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.4); font-weight:700; padding:4px 10px; border-radius:6px; font-size:12px; display:inline-block;">
                                                🛑 <?php echo htmlspecialchars($st); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        <button type="button" class="btn btn-outline btn-xs" onclick="printOrderInvoice(<?php echo htmlspecialchars(json_encode($ord)); ?>)" title="<?php echo adm_t('admin_orders_tax_invoice', 'Print Luxury Invoice'); ?>">
                                            📄 <?php echo adm_t('admin_orders_btn_invoice', 'Invoice'); ?>
                                        </button>
                                        <a href="/track.php?order_id=<?php echo urlencode($ord['order_id']); ?>" class="btn btn-ghost btn-xs" title="Track Live" target="_blank">👁️</a>
                                        <form action="/admin/orders.php" method="POST" onsubmit="return confirm('<?php echo adm_t('admin_orders_delete_confirm', 'Delete order permanently?'); ?>')" style="display:inline;">
                                            <input type="hidden" name="delete_order_id" value="<?php echo htmlspecialchars($ord['order_id']); ?>">
                                            <button type="submit" class="btn btn-ghost text-danger btn-xs" title="<?php echo adm_t('admin_btn_delete', 'Delete Order'); ?>">✕</button>
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
</section>

<!-- PACKAGE PREPARATION CONFIRMATION MODAL -->
<div class="modal-overlay" id="packageConfirmModalOverlay">
    <div class="modal-card" style="max-width:540px;">
        <div class="modal-header">
            <div>
                <h3 style="margin:0; font-size:18px;" id="pkgModalTitle">📦 Confirm Package Preparation</h3>
                <small class="text-muted" id="pkgModalSubtitle">Link delivery company Package ID to order</small>
            </div>
            <button type="button" class="btn-close-modal" onclick="closePackageConfirmModal()">✕</button>
        </div>

        <div style="background:rgba(217,119,6,0.08); border:1px solid rgba(217,119,6,0.25); border-radius:8px; padding:12px 16px; margin-bottom:18px; font-size:12.5px; color:var(--text-secondary); line-height:1.5;">
            Once the order is prepared, enter the <strong>Package ID / Waybill Code</strong> from your delivery company. The order status will automatically switch to <strong>Ready to Ship</strong> and link for customer search. Later statuses (Shipped, Out for Delivery, Delivered) update automatically via delivery company API.
        </div>

        <form action="/admin/orders.php" method="POST" id="packageConfirmForm" onsubmit="handlePackageFormSubmit(event)">
            <input type="hidden" name="confirm_package_prepared" value="1">
            <input type="hidden" name="order_id" id="pkgConfirmOrderId">
            <input type="hidden" name="is_edit_mode" id="pkgConfirmIsEdit" value="0">
            
            <div class="form-group mb-16">
                <label style="font-weight:700; font-size:13px;">
                    Delivery Company Package ID / Tracking Code <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" name="tracking_code" id="pkgConfirmTrackingId" class="form-control" placeholder="e.g. EXP-9921, DHK-8812, KURD-4019" required style="font-family:monospace; font-weight:700; font-size:14px; letter-spacing:0.5px;">
                <small class="text-muted" style="font-size:11.5px; display:block; margin-top:4px;">
                    The unique barcode or package number provided by your courier company.
                </small>
            </div>

            <div class="form-group mb-16">
                <label style="font-weight:700; font-size:13px;">
                    Delivery Company / Courier Name
                </label>
                <select name="courier" id="pkgConfirmCourier" class="form-control">
                    <option value="Kurdistan Express Logistics" selected>Kurdistan Express Logistics</option>
                    <option value="AURA Direct Fleet">AURA Direct Fleet</option>
                    <option value="Iraq Post & Logistics">Iraq Post & Logistics</option>
                    <option value="Al-Wessam Express Delivery">Al-Wessam Express Delivery</option>
                    <option value="DHL Express Iraq">DHL Express Iraq</option>
                    <option value="Other Express Delivery">Other Delivery Company</option>
                </select>
            </div>

            <div class="form-group mb-20">
                <label style="font-weight:700; font-size:13px;">
                    Packaging & Preparation Notes (Optional)
                </label>
                <textarea name="dispatch_notes" id="pkgConfirmNotes" rows="2" class="form-control" placeholder="e.g. Luxury velvet box sealed. Inspection passed."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn btn-outline" onclick="closePackageConfirmModal()"><?php echo adm_t('admin_btn_cancel', 'Cancel'); ?></button>
                <button type="submit" class="btn btn-primary btn-luxury" id="pkgConfirmSubmitBtn">
                    ✓ Confirm & Set Ready to Ship
                </button>
            </div>
        </form>
    </div>
</div>

<!-- DELIVERY COMPANY API & WEBHOOK SIMULATOR MODAL -->
<div class="modal-overlay" id="webhookSimulatorModalOverlay">
    <div class="modal-card" style="max-width:620px;">
        <div class="modal-header">
            <div>
                <h3 style="margin:0; font-size:18px;">⚡ Delivery Company API Integration</h3>
                <small class="text-muted">Automated status synchronization endpoint & test simulator</small>
            </div>
            <button type="button" class="btn-close-modal" onclick="closeWebhookSimulatorModal()">✕</button>
        </div>

        <div style="font-size:13px; color:var(--text-secondary); margin-bottom:16px; line-height:1.6;">
            Your delivery company's system automatically calls this webhook whenever a courier scans or delivers a package. When called, the order status changes automatically to <strong>Shipped</strong>, <strong>Out for Delivery</strong>, or <strong>Delivered</strong> without any manual action needed in the admin panel!
        </div>

        <div style="background:var(--bg-surface-elevated); border:1px solid var(--border-color); border-radius:8px; padding:12px 16px; margin-bottom:18px;">
            <div style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">
                Live Webhook Endpoint (POST)
            </div>
            <code id="webhookUrlCode" style="font-size:12.5px; color:var(--accent-gold); word-break:break-all;">
                /api/delivery_webhook.php
            </code>
        </div>

        <!-- Interactive Simulator Section -->
        <div style="border-top:1px solid var(--border-color); padding-top:16px;">
            <h4 style="margin:0 0 10px; font-size:14px; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:6px;">
                <span>🧪</span> Interactive Delivery Company Status Ping Simulator
            </h4>
            <p style="margin:0 0 14px; font-size:12px; color:var(--text-muted);">
                Test how the automated API changes order statuses live right now:
            </p>

            <div class="form-row-2 mb-12">
                <div class="form-group">
                    <label style="font-size:12px; font-weight:700;">Select Order to Ping</label>
                    <select id="simSelectOrder" class="form-control" style="font-size:12.5px;">
                        <?php foreach ($ordersList as $ord): ?>
                            <option value="<?php echo htmlspecialchars($ord['tracking_code'] ?: $ord['order_id']); ?>" data-status="<?php echo htmlspecialchars($ord['order_status']); ?>" data-order-id="<?php echo htmlspecialchars($ord['order_id']); ?>">
                                <?php echo htmlspecialchars($ord['order_id']); ?> (<?php echo htmlspecialchars($ord['customer_name']); ?>) — <?php echo htmlspecialchars($ord['order_status']); ?><?php echo !empty($ord['tracking_code']) ? ' [Pkg: ' . htmlspecialchars($ord['tracking_code']) . ']' : ' [No Pkg ID]'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-size:12px; font-weight:700;">Delivery Company API Event</label>
                    <select id="simSelectStatus" class="form-control" style="font-size:12.5px;">
                        <option value="Shipped">🚚 Shipped (Courier Picked Up)</option>
                        <option value="Out for Delivery">🛵 Out for Delivery (Driver on Route)</option>
                        <option value="Delivered">✅ Delivered (Customer Signed)</option>
                        <option value="Cancelled">🛑 Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="form-group mb-16">
                <label style="font-size:12px; font-weight:700;">Checkpoint Note from Courier API</label>
                <input type="text" id="simNotes" class="form-control" value="Scanned at Erbil central distribution center" style="font-size:12px;">
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span id="simStatusFeedback" style="font-size:12px; font-weight:700;"></span>
                <button type="button" class="btn btn-primary btn-sm" id="btnRunSimulator" onclick="runDeliveryWebhookSimulator()" style="font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                    ⚡ Send Simulated Delivery Company Ping
                </button>
            </div>

            <div id="simResponseBox" style="margin-top:14px; display:none; background:#0f172a; color:#38bdf8; padding:12px; border-radius:6px; font-family:monospace; font-size:11.5px; white-space:pre-wrap; max-height:160px; overflow-y:auto;"></div>
        </div>
    </div>
</div>

<!-- INVOICE PREVIEW & PRINT MODAL -->
<div class="modal-overlay" id="invoiceModalOverlay">
    <div class="modal-card" style="max-width:700px; background:#ffffff; color:#111827;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e5e7eb; padding-bottom:12px; margin-bottom:16px;">
            <strong style="color:#111827; font-size:16px;"><?php echo adm_t('admin_orders_tax_invoice', 'Official Tax Invoice & Receipt'); ?></strong>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-primary btn-sm" onclick="window.print()" style="background:#111827; color:#fff; border-color:#111827;">🖨️ <?php echo adm_t('admin_orders_print_invoice', 'Print Invoice'); ?></button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeInvoiceModal()" style="color:#111827;">✕</button>
            </div>
        </div>
        <div id="invoicePrintArea" style="padding:10px 0;"></div>
    </div>
</div>

<script>
// Filter Orders by search query & status
function filterOrdersTable() {
    const q = (document.getElementById('orderSearchInput').value || '').toLowerCase();
    const st = document.getElementById('orderStatusFilter').value;
    const rows = document.querySelectorAll('#ordersTableMain tbody tr');
    rows.forEach(row => {
        const rowSearch = row.getAttribute('data-search') || '';
        const rowStatus = row.getAttribute('data-status') || '';
        const matchesQ = !q || rowSearch.includes(q);
        const matchesSt = !st || rowStatus.toLowerCase() === st.toLowerCase();
        row.style.display = (matchesQ && matchesSt) ? '' : 'none';
    });
}

// Package Confirmation Modal Logic
function openPackageConfirmModal(order, isEdit = false) {
    if (!order) return;
    const oid = order.order_id || '';
    const custName = order.customer_name || '';
    const city = order.city || '';
    
    document.getElementById('pkgConfirmOrderId').value = oid;
    document.getElementById('pkgConfirmIsEdit').value = isEdit ? '1' : '0';
    
    const titleEl = document.getElementById('pkgModalTitle');
    const subEl = document.getElementById('pkgModalSubtitle');
    const submitBtn = document.getElementById('pkgConfirmSubmitBtn');
    
    if (isEdit) {
        titleEl.innerText = '✏️ Edit Package ID';
        subEl.innerText = 'Update Delivery Company Package ID for Order #' + oid + ' (' + custName + ')';
        submitBtn.innerText = '✓ Save Package ID';
    } else {
        titleEl.innerText = '📦 Confirm Package Preparation';
        subEl.innerText = 'Order #' + oid + ' • ' + custName + ' (' + city + ')';
        submitBtn.innerText = '✓ Confirm & Set Ready to Ship';
    }
    
    const trackingInput = document.getElementById('pkgConfirmTrackingId');
    trackingInput.value = order.tracking_code || '';
    
    if (order.courier) {
        document.getElementById('pkgConfirmCourier').value = order.courier;
    }
    document.getElementById('pkgConfirmNotes').value = order.dispatch_notes || '';
    
    document.getElementById('packageConfirmModalOverlay').classList.add('open');
    setTimeout(() => trackingInput.focus(), 150);
}

function closePackageConfirmModal() {
    document.getElementById('packageConfirmModalOverlay').classList.remove('open');
}

function handlePackageFormSubmit(e) {
    const trackingInput = document.getElementById('pkgConfirmTrackingId');
    if (!trackingInput.value.trim()) {
        e.preventDefault();
        alert('Please enter the delivery company Package ID / Tracking code.');
        trackingInput.focus();
        return false;
    }
    const btn = document.getElementById('pkgConfirmSubmitBtn');
    if (btn) btn.innerText = 'Saving Package...';
    return true;
}

// Webhook Simulator Modal
function openWebhookSimulatorModal() {
    const codeEl = document.getElementById('webhookUrlCode');
    if (codeEl) {
        codeEl.innerText = window.location.origin + '/api/delivery_webhook.php';
    }
    document.getElementById('webhookSimulatorModalOverlay').classList.add('open');
}

function closeWebhookSimulatorModal() {
    document.getElementById('webhookSimulatorModalOverlay').classList.remove('open');
}

async function runDeliveryWebhookSimulator() {
    const selectEl = document.getElementById('simSelectOrder');
    const packageId = selectEl.value;
    const newStatus = document.getElementById('simSelectStatus').value;
    const notes = document.getElementById('simNotes').value;
    const btn = document.getElementById('btnRunSimulator');
    const feedback = document.getElementById('simStatusFeedback');
    const respBox = document.getElementById('simResponseBox');

    if (!packageId) {
        alert('Please select an order first.');
        return;
    }

    btn.disabled = true;
    btn.innerText = '⚡ Calling Delivery API...';
    feedback.innerText = 'Sending simulated ping...';
    feedback.style.color = '#38bdf8';

    try {
        const payload = {
            package_id: packageId,
            status: newStatus,
            courier: 'Kurdistan Express Delivery',
            notes: notes
        };

        const res = await fetch('/api/delivery_webhook.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        respBox.style.display = 'block';
        respBox.innerText = JSON.stringify(data, null, 2);

        if (data.success) {
            feedback.innerText = `✓ Webhook ping received! Status updated to "${newStatus}"`;
            feedback.style.color = '#22c55e';
            setTimeout(() => {
                window.location.reload();
            }, 1200);
        } else {
            feedback.innerText = `⚠️ API Error: ${data.error || 'Failed'}`;
            feedback.style.color = '#ef4444';
        }
    } catch (err) {
        respBox.style.display = 'block';
        respBox.innerText = 'Error: ' + err.message;
        feedback.innerText = 'Connection error';
        feedback.style.color = '#ef4444';
    } finally {
        btn.disabled = false;
        btn.innerText = '⚡ Send Simulated Delivery Company Ping';
    }
}

// Invoice Generator
function printOrderInvoice(order) {
    if (!order) return;
    const container = document.getElementById('invoicePrintArea');
    const items = order.items || [];
    let itemsHtml = '';
    const ordTot = order.total || 0;

    const lblQty = "<?php echo adm_t('cart_qty', 'Qty'); ?>";
    const lblSize = "<?php echo adm_t('product_size', 'Size'); ?>";
    const lblItemDesc = "<?php echo adm_t('admin_invoice_item_desc', 'Item Description'); ?>";
    const lblTotal = "<?php echo adm_t('admin_invoice_total', 'Total'); ?>";
    const lblLuxuryStore = "<?php echo adm_t('admin_invoice_luxury_store', 'LUXURY STORE • IRAQ'); ?>";
    const lblCareHub = "<?php echo adm_t('admin_invoice_care_hub', 'Customer Care & Fulfillment Hub'); ?>";
    const lblTaxTitle = "<?php echo adm_t('admin_invoice_tax_title', 'TAX INVOICE'); ?>";
    const lblBilledTo = "<?php echo adm_t('admin_invoice_billed_to', 'Billed & Delivered To:'); ?>";
    const lblLogistics = "<?php echo adm_t('admin_invoice_logistics_payment', 'Logistics & Payment:'); ?>";
    const lblMethod = "<?php echo adm_t('admin_invoice_method', 'Method:'); ?>";
    const lblCourier = "<?php echo adm_t('admin_invoice_courier', 'Courier:'); ?>";
    const lblTracking = "<?php echo adm_t('admin_invoice_tracking', 'Tracking:'); ?>";
    const lblPayTerms = "<?php echo adm_t('admin_invoice_payment_terms', 'Payment Terms:'); ?>";
    const lblOfficialCurrency = "<?php echo adm_t('admin_invoice_official_currency', 'Official Currency: Iraqi Dinar (IQD)'); ?>";
    const lblTotalPayable = "<?php echo adm_t('admin_invoice_total_payable', 'Total Payable:'); ?>";

    items.forEach(it => {
        const itTitle = typeof it.title === 'object' ? (it.title.<?php echo $adminLang ?? 'en'; ?> || it.title.en || it.title) : it.title;
        itemsHtml += `
            <tr style="border-bottom:1px solid #e5e7eb;">
                <td style="padding:10px 0;"><strong>${itTitle}</strong><br><small style="color:#6b7280;">${lblQty}: ${it.quantity} ${it.size ? '• ' + lblSize + ': ' + it.size : ''}</small></td>
                <td style="padding:10px 0; text-align:right;">${Math.round(it.price * it.quantity).toLocaleString()} IQD</td>
            </tr>
        `;
    });

    container.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #111827; padding-bottom:20px; margin-bottom:24px;">
            <div>
                <h1 style="font-size:26px; font-weight:800; letter-spacing:2px; margin:0; color:#111827;">AURA</h1>
                <span style="font-size:12px; letter-spacing:3px; color:#d97706; font-weight:700;">${lblLuxuryStore}</span>
                <p style="font-size:12px; color:#6b7280; margin:4px 0 0;">${lblCareHub}</p>
            </div>
            <div style="text-align:right;">
                <h2 style="font-size:18px; font-weight:800; margin:0; color:#111827;">${lblTaxTitle}</h2>
                <div style="font-family:monospace; font-size:14px; font-weight:700; color:#d97706;">${order.order_id}</div>
                <div style="font-size:12px; color:#6b7280;">${new Date(order.created_at || Date.now()).toLocaleDateString()}</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; font-size:13px;">
            <div>
                <strong style="color:#111827; text-transform:uppercase; font-size:11px; letter-spacing:1px;">${lblBilledTo}</strong>
                <div style="font-weight:700; font-size:15px; margin-top:4px;">${order.customer_name}</div>
                <div style="color:#4b5563;">${order.phone}</div>
                <div style="color:#4b5563;">${order.city}, ${order.address}</div>
            </div>
            <div style="text-align:right;">
                <strong style="color:#111827; text-transform:uppercase; font-size:11px; letter-spacing:1px;">${lblLogistics}</strong>
                <div style="font-weight:700; color:#111827; margin-top:4px;">${lblMethod} ${order.payment_method}</div>
                <div style="color:#4b5563;">${lblCourier} ${order.courier || 'Kurdistan Express'}</div>
                <div style="color:#4b5563;">Package ID / Tracking: <code>${order.tracking_code || order.order_id}</code></div>
            </div>
        </div>

        <table style="width:100%; border-collapse:collapse; font-size:13.5px; margin-bottom:20px;">
            <thead>
                <tr style="border-bottom:2px solid #e5e7eb; text-align:left;">
                    <th style="padding:8px 0;">${lblItemDesc}</th>
                    <th style="padding:8px 0; text-align:right;">${lblTotal}</th>
                </tr>
            </thead>
            <tbody>
                ${itemsHtml}
            </tbody>
        </table>

        <div style="border-top:2px solid #111827; padding-top:16px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <span style="font-size:12px; color:#6b7280;">${lblPayTerms}</span><br>
                <strong style="font-size:14px; color:#111827;">${lblOfficialCurrency}</strong>
            </div>
            <div style="text-align:right;">
                <span style="font-size:12px; color:#6b7280;">${lblTotalPayable}</span><br>
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

<?php require_once __DIR__ . '/../footer.php'; ?>
