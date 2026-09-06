<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/db.php';

// Handle JSON / AJAX requests (e.g. order status update)
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $jsonReq = json_decode($rawInput, true);
    if (is_array($jsonReq)) {
        if (isset($jsonReq['order_id']) && isset($jsonReq['order_status'])) {
            header('Content-Type: application/json');
            $updated = update_order_status($jsonReq['order_id'], $jsonReq['order_status']);
            echo json_encode(['success' => (bool)$updated, 'order_id' => $jsonReq['order_id'], 'order_status' => $jsonReq['order_status']]);
            exit;
        }
    }
}

// Handle query parameter AJAX action
if (isset($_GET['action']) && $_GET['action'] === 'update_status') {
    header('Content-Type: application/json');
    $oid = trim($_POST['order_id'] ?? $_GET['order_id'] ?? '');
    $status = trim($_POST['order_status'] ?? $_GET['order_status'] ?? 'Pending');
    $updated = update_order_status($oid, $status);
    echo json_encode(['success' => (bool)$updated, 'order_id' => $oid, 'order_status' => $status]);
    exit;
}

$flashMsg = null;
$flashType = 'success';

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. UPDATE DISPATCH / LOGISTICS
    if (isset($_POST['update_order_dispatch'])) {
        $oid = trim($_POST['order_id'] ?? '');
        $status = trim($_POST['order_status'] ?? 'Shipped');
        $courier = trim($_POST['courier'] ?? 'AURA Express Fleet');
        $tracking = trim($_POST['tracking_code'] ?? '');
        $estDelivery = trim($_POST['estimated_delivery'] ?? '');
        if (empty($estDelivery)) {
            $estDelivery = 'Estimated Arrival: Within 24 – 72 Hours';
        }
        $dispatchNotes = trim($_POST['dispatch_notes'] ?? '');

        if (!empty($oid)) {
            update_order_full($oid, [
                'order_status' => $status,
                'courier' => $courier,
                'tracking_code' => $tracking,
                'driver_name' => '',
                'driver_phone' => '',
                'estimated_delivery' => $estDelivery,
                'dispatch_notes' => $dispatchNotes
            ]);
            $flashMsg = "✓ Order #{$oid} logistics details updated and assigned to {$courier}!";
        }
    }
    // 2. DELETE ORDER
    elseif (isset($_POST['delete_order_id'])) {
        $oid = trim($_POST['delete_order_id']);
        if (!empty($oid)) {
            // Delete from orders.json
            $jsonFile = __DIR__ . '/../database/orders.json';
            if (file_exists($jsonFile)) {
                $data = json_decode(file_get_contents($jsonFile), true);
                if (isset($data['orders']) && is_array($data['orders'])) {
                    $data['orders'] = array_values(array_filter($data['orders'], function($o) use ($oid) {
                        return ($o['order_id'] ?? '') !== $oid;
                    }));
                    @file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }
            // Delete from MySQL if available
            $pdo = get_mysql_pdo();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = :oid");
                    $stmt->execute([':oid' => $oid]);
                } catch (Exception $e) {}
            }
            $flashMsg = "✓ Order #{$oid} was permanently removed.";
        }
    }
}

$pageTitle = 'Orders & Logistics Radar | AURA Luxury Admin';
$adminActive = 'orders';
$ordersList = get_all_orders();
$productsList = get_all_products();

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

$activePage = 'admin';
require_once __DIR__ . '/../header.php';
?>

<section class="admin-section" style="padding: 24px 0 60px;">
    <div class="container">

        <!-- Unified Admin Navigation Bar -->
        <?php require_once __DIR__ . '/nav.php'; ?>

        <?php if ($flashMsg): ?>
            <div style="background:rgba(34,197,94,0.12); border:1px solid #22c55e; color:#22c55e; border-radius:8px; padding:14px 20px; margin-bottom:24px; font-weight:700; display:flex; align-items:center; justify-content:space-between;">
                <span><?php echo $flashMsg; ?></span>
                <button type="button" onclick="this.parentElement.style.display='none'" style="background:none; border:none; color:#22c55e; cursor:pointer; font-size:16px;">✕</button>
            </div>
        <?php endif; ?>

        <!-- Orders Metric Sub-Cards -->
        <div class="admin-metrics-grid" style="margin-bottom:24px;">
            <div class="admin-metric-card">
                <span class="m-icon">📦</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_metric_total_orders', 'Total Client Orders'); ?></span>
                    <strong class="m-value"><?php echo count($ordersList); ?> <?php echo adm_t('admin_nav_orders', 'Orders'); ?></strong>
                    <span class="iqd-price-pill"><?php echo number_format($totalRevenueIqd); ?> IQD</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">⏳</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_status_pending', 'Pending'); ?></span>
                    <strong class="m-value" style="color:#eab308;"><?php echo $pendingCount; ?></strong>
                    <span class="iqd-price-pill"><?php echo adm_t('admin_status_pending', 'Pending Dispatch'); ?></span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">🚚</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_status_shipped', 'Shipped / Dispatched'); ?></span>
                    <strong class="m-value text-primary"><?php echo $shippedCount; ?></strong>
                    <span class="iqd-price-pill"><?php echo adm_t('admin_orders_in_transit', 'In Transit (24–72h)'); ?></span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">✅</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_status_delivered', 'Delivered'); ?></span>
                    <strong class="m-value" style="color:#22c55e;"><?php echo $deliveredCount; ?></strong>
                    <span class="iqd-price-pill"><?php echo adm_t('admin_orders_verified', '100% Verified'); ?></span>
                </div>
            </div>
        </div>

        <!-- Main Orders Table Card -->
        <div class="admin-table-card">
            <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:12px;">
                <div>
                    <h3 class="admin-card-title" style="margin:0; font-size:18px;">📦 <?php echo adm_t('admin_orders_title', 'Client Orders & Logistics Operations'); ?></h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_workspace_orders_desc', 'Track shipments, change dispatch status, and manage courier details.'); ?></p>
                </div>
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <input type="text" id="orderSearchInput" onkeyup="filterOrdersTable()" placeholder="<?php echo adm_t('admin_search_orders', 'Search orders by ID, Client, City...'); ?>" class="form-control" style="max-width:240px; padding:8px 12px; font-size:13px;">
                    <select id="orderStatusFilter" onchange="filterOrdersTable()" class="form-control" style="max-width:180px; padding:8px 12px; font-size:13px;">
                        <option value=""><?php echo adm_t('admin_filter_all_status', 'All Statuses'); ?></option>
                        <option value="Pending"><?php echo adm_t('admin_status_pending', 'Pending'); ?></option>
                        <option value="Processing"><?php echo adm_t('admin_status_processing', 'Processing'); ?></option>
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
                            <th><?php echo adm_t('admin_order_col_courier', 'Courier / Tracking'); ?></th>
                            <th><?php echo adm_t('admin_order_col_status', 'Order Status'); ?></th>
                            <th><?php echo adm_t('admin_order_col_actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordersList as $ord): 
                            $ordTot = $ord['total'] ?? 0;
                            $itemsCount = count($ord['items'] ?? []);
                        ?>
                            <tr data-status="<?php echo htmlspecialchars($ord['order_status'] ?? 'Pending'); ?>" data-search="<?php echo strtolower($ord['order_id'] . ' ' . $ord['customer_name'] . ' ' . $ord['phone'] . ' ' . $ord['city']); ?>">
                                <td>
                                    <strong><a href="/track.php?order_id=<?php echo urlencode($ord['order_id']); ?>"><?php echo htmlspecialchars($ord['order_id']); ?></a></strong>
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
                                    <div class="courier-info-chip">
                                        <span class="courier-name"><?php echo htmlspecialchars(!empty($ord['courier']) ? $ord['courier'] : adm_t('admin_unassigned', 'Unassigned')); ?></span>
                                        <small class="text-muted" style="font-size:11.5px; display:block;"><?php echo htmlspecialchars(!empty($ord['estimated_delivery']) ? $ord['estimated_delivery'] : 'Estimated Arrival: Within 24 – 72 Hours'); ?></small>
                                        <?php if (!empty($ord['tracking_code'])): ?>
                                            <code style="font-size:11px;"><?php echo htmlspecialchars($ord['tracking_code']); ?></code>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-status-wrapper" id="orderStatusWrap_<?php echo htmlspecialchars($ord['order_id']); ?>">
                                        <select name="order_status" class="status-select" data-previous-status="<?php echo htmlspecialchars($ord['order_status'] ?? 'Pending'); ?>" onchange="window.AuraStore.updateOrderStatus('<?php echo htmlspecialchars($ord['order_id']); ?>', this.value, this)">
                                            <option value="Pending" <?php echo ($ord['order_status'] ?? '') === 'Pending' ? 'selected' : ''; ?>><?php echo adm_t('admin_status_pending', 'Pending'); ?></option>
                                            <option value="Processing" <?php echo ($ord['order_status'] ?? '') === 'Processing' ? 'selected' : ''; ?>><?php echo adm_t('admin_status_processing', 'Processing'); ?></option>
                                            <option value="Shipped" <?php echo ($ord['order_status'] ?? '') === 'Shipped' ? 'selected' : ''; ?>><?php echo adm_t('admin_status_shipped', 'Shipped'); ?></option>
                                            <option value="Out for Delivery" <?php echo ($ord['order_status'] ?? '') === 'Out for Delivery' ? 'selected' : ''; ?>><?php echo adm_t('admin_status_out_for_delivery', 'Out for Delivery'); ?></option>
                                            <option value="Delivered" <?php echo ($ord['order_status'] ?? '') === 'Delivered' ? 'selected' : ''; ?>><?php echo adm_t('admin_status_delivered', 'Delivered'); ?></option>
                                            <option value="Cancelled" <?php echo ($ord['order_status'] ?? '') === 'Cancelled' ? 'selected' : ''; ?>><?php echo adm_t('admin_status_cancelled', 'Cancelled'); ?></option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        <button type="button" class="btn btn-outline btn-xs" onclick="openDispatchModal(<?php echo htmlspecialchars(json_encode($ord)); ?>)" title="<?php echo adm_t('admin_modal_dispatch', 'Manage Courier & Logistics'); ?>">
                                            🚚 <?php echo adm_t('admin_orders_btn_logistics', 'Logistics'); ?>
                                        </button>
                                        <button type="button" class="btn btn-outline btn-xs" onclick="printOrderInvoice(<?php echo htmlspecialchars(json_encode($ord)); ?>)" title="<?php echo adm_t('admin_orders_tax_invoice', 'Print Luxury Invoice'); ?>">
                                            📄 <?php echo adm_t('admin_orders_btn_invoice', 'Invoice'); ?>
                                        </button>
                                        <a href="/track.php?order_id=<?php echo urlencode($ord['order_id']); ?>" class="btn btn-ghost btn-xs" title="Track Live">👁️</a>
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

<!-- DISPATCH & LOGISTICS MODAL -->
<div class="modal-overlay" id="dispatchModalOverlay">
    <div class="modal-card" style="max-width:560px;">
        <div class="modal-header">
            <div>
                <h3 style="margin:0; font-size:18px;">🚚 <?php echo adm_t('admin_modal_dispatch', 'Logistics & Dispatch Center'); ?></h3>
                <small class="text-muted" id="dispatchModalOrderSub"><?php echo adm_t('admin_orders_assign_courier_desc', 'Assign express courier or internal fleet driver'); ?></small>
            </div>
            <button type="button" class="btn-close-modal" onclick="closeDispatchModal()">✕</button>
        </div>
        <form action="/admin/orders.php" method="POST" id="dispatchForm" onsubmit="handleDispatchFormSubmit(event)">
            <input type="hidden" name="update_order_dispatch" value="1">
            <input type="hidden" name="order_id" id="dispOrderId">
            
            <div class="form-group mb-16">
                <label><?php echo adm_t('admin_order_col_status', 'Update Order Status'); ?></label>
                <select name="order_status" id="dispStatus" class="form-control">
                    <option value="Processing"><?php echo adm_t('admin_status_processing', 'Processing / Packaging'); ?></option>
                    <option value="Shipped" selected><?php echo adm_t('admin_status_shipped', 'Shipped / Handed to Courier Driver'); ?></option>
                    <option value="Out for Delivery"><?php echo adm_t('admin_status_out_for_delivery', 'Out for Delivery'); ?></option>
                    <option value="Delivered"><?php echo adm_t('admin_status_delivered', 'Delivered'); ?></option>
                </select>
            </div>

            <div class="form-row-2 mb-16">
                <div class="form-group">
                    <label><?php echo adm_t('admin_order_col_courier', 'Logistics Courier Company'); ?></label>
                    <select name="courier" id="dispCourier" class="form-control">
                        <option value="AURA Express Fleet">AURA Direct Fleet</option>
                        <option value="Kurdistan Express">Kurdistan Express Delivery</option>
                        <option value="Iraq Post Express">Iraq Post & Logistics</option>
                        <option value="Al-Wessam Courier">Al-Wessam Express</option>
                        <option value="DHL Express Iraq">DHL Express Iraq</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo adm_t('admin_invoice_tracking', 'Tracking Reference Code'); ?></label>
                    <input type="text" name="tracking_code" id="dispTrackingCode" class="form-control" placeholder="AURA-EXP-99201">
                </div>
            </div>

            <div class="form-group mb-16">
                <label><?php echo adm_t('admin_field_est_delivery', 'Estimated Delivery Timeframe'); ?></label>
                <input type="text" name="estimated_delivery" id="dispEstDelivery" class="form-control" value="Estimated Arrival: Within 24 – 72 Hours" placeholder="Estimated Arrival: Within 24 – 72 Hours">
                <small class="text-muted" style="font-size:11.5px; margin-top:4px; display:block;">Standard delivery window: Within 24 – 72 Hours across Iraq & Kurdistan.</small>
            </div>

            <div class="form-group mb-20">
                <label><?php echo adm_t('admin_field_internal_notes', 'Internal Logistics Notes'); ?></label>
                <textarea name="dispatch_notes" id="dispNotes" rows="2" class="form-control"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn btn-outline" onclick="closeDispatchModal()"><?php echo adm_t('admin_btn_cancel', 'Cancel'); ?></button>
                <button type="submit" class="btn btn-primary btn-luxury" id="dispatchSubmitBtn"><?php echo adm_t('admin_btn_save', 'Update Dispatch & Notify Client'); ?></button>
            </div>
        </form>
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

function openDispatchModal(order) {
    if (!order) return;
    document.getElementById('dispOrderId').value = order.order_id || '';
    document.getElementById('dispatchModalOrderSub').innerText = 'Updating shipment details for Order #' + (order.order_id || '') + ' (' + (order.customer_name || '') + ')';
    document.getElementById('dispStatus').value = order.order_status || 'Shipped';
    document.getElementById('dispCourier').value = order.courier || 'AURA Express Fleet';
    document.getElementById('dispTrackingCode').value = order.tracking_code || ('AURA-EXP-' + (order.order_id || '').replace('ORD-', ''));
    document.getElementById('dispEstDelivery').value = order.estimated_delivery || 'Estimated Arrival: Within 24 – 72 Hours';
    document.getElementById('dispNotes').value = order.dispatch_notes || '';
    document.getElementById('dispatchModalOverlay').classList.add('open');
}

function closeDispatchModal() {
    document.getElementById('dispatchModalOverlay').classList.remove('open');
}

function handleDispatchFormSubmit(e) {
    // Allows form to submit normally, while providing instant UI feedback
    const btn = document.getElementById('dispatchSubmitBtn');
    if (btn) btn.innerText = 'Updating Shipment...';
}

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
                <div style="color:#4b5563;">${lblCourier} ${order.courier || 'AURA Express'}</div>
                <div style="color:#4b5563;">${lblTracking} <code>${order.tracking_code || order.order_id}</code></div>
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
