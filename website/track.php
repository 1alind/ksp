<?php
$activePage = 'track';
$pageTitle = 'Order Tracking';
require_once __DIR__ . '/header.php';

$searchOrderId = trim($_GET['order_id'] ?? '');
$foundOrder = null;
$searched = false;
$settings = get_store_settings();
$rate = $settings['exchange_rate_usd_to_iqd'] ?? 1320;

if (!empty($searchOrderId)) {
    $searched = true;
    $orders = get_all_orders();
    foreach ($orders as $ord) {
        if (strcasecmp($ord['order_id'], $searchOrderId) === 0 || 
            strcasecmp($ord['email'] ?? '', $searchOrderId) === 0 || 
            strcasecmp($ord['phone'] ?? '', $searchOrderId) === 0 ||
            strcasecmp($ord['tracking_code'] ?? '', $searchOrderId) === 0) {
            $foundOrder = $ord;
            break;
        }
    }
}
?>

<section class="track-section">
    <div class="container">

        <?php if ($issueSubmitted): ?>
            <div class="alert alert-success mb-24" style="background:rgba(34,197,94,0.12); border:1px solid #22c55e; border-radius:10px; padding:18px; color:var(--text-primary);">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                    <span style="font-size:22px;">✅</span>
                    <h4 style="margin:0; font-size:16px; color:#22c55e;"><?php echo t('issue_success_msg', $lang); ?></h4>
                </div>
                <p style="margin:0; font-size:13.5px; color:var(--text-secondary);">
                    Your Claim Ticket: <strong style="font-family:monospace; color:var(--accent-gold); font-size:15px;"><?php echo htmlspecialchars($issueTicketId); ?></strong>. We will review your claim shortly.
                </p>
            </div>
        <?php endif; ?>

        <!-- Search Order Form Card -->
        <div class="track-search-card">
            <form action="track.php" method="GET" class="track-form">
                <div class="track-input-group">
                    <input type="text" name="order_id" required class="track-input" placeholder="<?php echo t('track_input_placeholder', $lang); ?>" value="<?php echo htmlspecialchars($searchOrderId); ?>">
                    <button type="submit" class="btn btn-primary btn-luxury btn-lg"><?php echo t('track_button', $lang); ?></button>
                </div>
            </form>
            <div class="sample-ids-hint" style="margin-top:14px;">
                <div style="display:flex; flex-wrap:wrap; gap:8px; justify-content:center;">
                    <a href="track.php?order_id=ORD-10942" class="badge-tag" style="background:rgba(234,179,8,0.15); color:#eab308; border:1px solid rgba(234,179,8,0.4); text-decoration:none; padding:4px 10px; font-size:12px; border-radius:6px;">
                        ⏳ Pending: ORD-10942
                    </a>
                    <a href="track.php?order_id=ORD-25814" class="badge-tag" style="background:rgba(59,130,246,0.15); color:#3b82f6; border:1px solid rgba(59,130,246,0.4); text-decoration:none; padding:4px 10px; font-size:12px; border-radius:6px;">
                        📦 Processing: ORD-25814
                    </a>
                    <a href="track.php?order_id=ORD-61028" class="badge-tag" style="background:rgba(168,85,247,0.15); color:#a855f7; border:1px solid rgba(168,85,247,0.4); text-decoration:none; padding:4px 10px; font-size:12px; border-radius:6px;">
                        🚚 Shipped: ORD-61028
                    </a>
                    <a href="track.php?order_id=ORD-84920" class="badge-tag" style="background:rgba(249,115,22,0.15); color:#f97316; border:1px solid rgba(249,115,22,0.4); text-decoration:none; padding:4px 10px; font-size:12px; border-radius:6px;">
                        🛵 Out for Delivery: ORD-84920
                    </a>
                    <a href="track.php?order_id=ORD-73195" class="badge-tag" style="background:rgba(34,197,94,0.15); color:#22c55e; border:1px solid rgba(34,197,94,0.4); text-decoration:none; padding:4px 10px; font-size:12px; border-radius:6px;">
                        ✅ Delivered: ORD-73195
                    </a>
                    <a href="track.php?order_id=ORD-40291" class="badge-tag" style="background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.4); text-decoration:none; padding:4px 10px; font-size:12px; border-radius:6px;">
                        🛑 Cancelled: ORD-40291
                    </a>
                </div>
            </div>
        </div>

        <?php if ($searched && $foundOrder): 
            $status = $foundOrder['order_status'] ?? 'Processing';
            $stepMap = ['Pending' => 1, 'Processing' => 2, 'Shipped' => 3, 'Out for Delivery' => 4, 'Delivered' => 5];
            $currentStep = $stepMap[$status] ?? 2;
            $ordTot = $foundOrder['total'] ?? 0;
            $ordIqd = $foundOrder['total_iqd'] ?? ($ordTot * $rate);
            $waPhone = preg_replace('/[^0-9]/', '', $foundOrder['phone'] ?? '');
            if (strpos($waPhone, '07') === 0) $waPhone = '964' . substr($waPhone, 1);
        ?>
            <!-- Live Tracking Details & Progress Timeline -->
            <div class="order-track-result-card">
                <div class="track-header-meta">
                    <div>
                        <span class="track-badge" style="background:var(--accent-gold-bg); color:var(--accent-gold); border:1px solid var(--accent-gold);">
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                        <h2 class="track-order-num"><?php echo htmlspecialchars($foundOrder['order_id']); ?></h2>
                    </div>
                    <div class="track-date-info">
                        <span>Placed on:</span>
                        <strong><?php echo date('M d, Y • h:i A', strtotime($foundOrder['created_at'])); ?></strong>
                    </div>
                </div>

                <!-- 5-Step Enhanced Logistics Timeline -->
                <div class="timeline-stepper">
                    
                    <div class="step-item <?php echo $currentStep >= 1 ? 'completed' : ''; ?> <?php echo $currentStep === 1 ? 'current' : ''; ?>">
                        <div class="step-circle">1</div>
                        <div class="step-info">
                            <strong><?php echo t('status_pending', $lang); ?></strong>
                            <small>Order Confirmed</small>
                        </div>
                    </div>

                    <div class="step-line <?php echo $currentStep >= 2 ? 'active' : ''; ?>"></div>

                    <div class="step-item <?php echo $currentStep >= 2 ? 'completed' : ''; ?> <?php echo $currentStep === 2 ? 'current' : ''; ?>">
                        <div class="step-circle">2</div>
                        <div class="step-info">
                            <strong><?php echo t('status_processing', $lang); ?></strong>
                            <small>Satin Packaging</small>
                        </div>
                    </div>

                    <div class="step-line <?php echo $currentStep >= 3 ? 'active' : ''; ?>"></div>

                    <div class="step-item <?php echo $currentStep >= 3 ? 'completed' : ''; ?> <?php echo $currentStep === 3 ? 'current' : ''; ?>">
                        <div class="step-circle">3</div>
                        <div class="step-info">
                            <strong><?php echo t('status_shipped', $lang); ?></strong>
                            <small>Hub Dispatch</small>
                        </div>
                    </div>

                    <div class="step-line <?php echo $currentStep >= 4 ? 'active' : ''; ?>"></div>

                    <div class="step-item <?php echo $currentStep >= 4 ? 'completed' : ''; ?> <?php echo $currentStep === 4 ? 'current' : ''; ?>">
                        <div class="step-circle">4</div>
                        <div class="step-info">
                            <strong><?php echo t('status_out_for_delivery', $lang); ?></strong>
                            <small>Driver En Route</small>
                        </div>
                    </div>

                    <div class="step-line <?php echo $currentStep >= 5 ? 'active' : ''; ?>"></div>

                    <div class="step-item <?php echo $currentStep >= 5 ? 'completed' : ''; ?> <?php echo $currentStep === 5 ? 'current' : ''; ?>">
                        <div class="step-circle">5</div>
                        <div class="step-info">
                            <strong><?php echo t('status_delivered', $lang); ?></strong>
                            <small>Handed to Client</small>
                        </div>
                    </div>

                </div>

                <!-- Live Dispatch Logistics Banner -->
                <div style="background:var(--bg-surface-elevated); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; margin:24px 0;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:12px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size:24px;">🚚</span>
                            <div>
                                <h4 style="font-size:16px; font-weight:800; color:var(--text-primary); margin:0;">
                                    <?php echo htmlspecialchars($foundOrder['courier'] ?? 'AURA Express Logistics'); ?>
                                </h4>
                                <span style="font-size:12px; color:var(--accent-gold); font-weight:700;">
                                    Tracking / Manifest: <code><?php echo htmlspecialchars($foundOrder['tracking_code'] ?? $foundOrder['order_id']); ?></code>
                                </span>
                            </div>
                        </div>
                        <?php if (!empty($foundOrder['estimated_delivery'])): ?>
                            <div style="text-align:right;">
                                <span style="font-size:11.5px; color:var(--text-muted);">Estimated Arrival:</span>
                                <div style="font-weight:700; color:var(--text-primary); font-size:13.5px;"><?php echo htmlspecialchars($foundOrder['estimated_delivery']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($foundOrder['driver_name'])): ?>
                        <div style="font-size:13px; color:var(--text-secondary); margin-bottom:8px;">
                            <strong>Assigned Courier Specialist:</strong> <?php echo htmlspecialchars($foundOrder['driver_name']); ?> 
                            <?php if (!empty($foundOrder['driver_phone'])): ?>
                                &bull; <a href="tel:<?php echo htmlspecialchars($foundOrder['driver_phone']); ?>" style="color:var(--accent-gold); font-weight:600;"><?php echo htmlspecialchars($foundOrder['driver_phone']); ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($foundOrder['dispatch_notes'])): ?>
                        <div style="background:var(--bg-subtle); padding:10px 14px; border-radius:6px; font-size:12.5px; color:var(--text-secondary); border-left:3px solid var(--accent-gold);">
                            <strong>Dispatcher Note:</strong> <?php echo htmlspecialchars($foundOrder['dispatch_notes']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Details breakdown -->
                <div class="track-details-grid">
                    <div class="track-info-box">
                        <h4>Recipient & Delivery Address</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($foundOrder['customer_name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($foundOrder['phone']); ?></p>
                        <p><strong>City / Governorate:</strong> <?php echo htmlspecialchars($foundOrder['city']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($foundOrder['address']); ?></p>
                    </div>

                    <div class="track-info-box">
                        <h4>Payment & Gateway Telemetry</h4>
                        <p><strong>Method:</strong> <?php echo htmlspecialchars($foundOrder['payment_method']); ?></p>
                        <p><strong>Status:</strong> <span class="badge-status-paid"><?php echo htmlspecialchars($foundOrder['payment_status'] ?? 'Pending'); ?></span></p>
                        <?php if (!empty($foundOrder['payment_gateway_tx'])): ?>
                            <p><strong>Transaction ID:</strong> <code><?php echo htmlspecialchars($foundOrder['payment_gateway_tx']); ?></code></p>
                        <?php endif; ?>
                        <p><strong>Total Amount:</strong> <span class="text-primary font-bold text-lg"><?php echo number_format($ordTot); ?> IQD</span></p>
                    </div>
                </div>

                <!-- Items list in order -->
                <div class="track-items-box mt-24">
                    <h4>Package Contents (<?php echo count($foundOrder['items'] ?? []); ?> items)</h4>
                    <div class="track-items-table">
                        <?php foreach (($foundOrder['items'] ?? []) as $item): 
                            $itTitle = is_array($item['title']) ? ($item['title'][$lang] ?? $item['title']['en']) : $item['title'];
                        ?>
                            <div class="track-item-row">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($itTitle); ?>" class="track-item-thumb">
                                <div class="track-item-desc">
                                    <strong><?php echo htmlspecialchars($itTitle); ?></strong>
                                    <span>Quantity: <?php echo $item['quantity']; ?> <?php echo !empty($item['size']) ? '• Size: ' . $item['size'] : ''; ?></span>
                                </div>
                                <div class="track-item-price">
                                    <?php echo number_format($item['price'] * $item['quantity']); ?> IQD
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php 
                $isDelivered = (stripos($status, 'delivered') !== false);
                if ($isDelivered): 
                ?>
                    <!-- DELIVERED CONFIRMATION -->
                    <div class="mt-24" style="background:rgba(34,197,94,0.08); border:1.5px solid #22c55e; border-radius:14px; padding:24px;">
                        <div style="display:flex; align-items:center; gap:16px;">
                            <span style="font-size:32px;">🎁</span>
                            <div>
                                <h3 style="font-size:17px; font-weight:800; color:var(--text-primary); margin:0 0 4px;">
                                    <?php echo $lang === 'ku' ? 'پاکێج ب سەرکەفتیانە هاتە گەهاندن' : ($lang === 'ar' ? 'تم تسليم الشحنة بنجاح' : 'Package Delivered Successfully'); ?>
                                </h3>
                                <p style="font-size:13px; color:var(--text-secondary); margin:0; line-height:1.5;">
                                    <?php echo $lang === 'ku' ? 'داخازیا تە گەهشتە ناڤنیشانێ تە. سوپاس بۆ هلبژارتنا تە بۆ AURA Luxury Studio.' : ($lang === 'ar' ? 'تم تسليم طردك إلى العنوان المحدد. شكراً لاختيارك AURA Luxury Studio.' : 'Your luxury order has been completed and safely delivered to your address. Thank you for shopping with AURA.'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- IN TRANSIT / AUTOMATED LOGISTICS NOTICE -->
                    <div class="mt-24" style="background:var(--bg-subtle); border:1px solid var(--border-color); border-radius:12px; padding:22px; display:flex; align-items:flex-start; gap:16px;">
                        <span style="font-size:28px;">⚡</span>
                        <div>
                            <h4 style="font-size:15px; font-weight:800; color:var(--text-primary); margin:0 0 6px;">
                                <?php echo $lang === 'ku' ? 'چاڤدێریکرنا گەهاندنێ یا بلەز یا چالاکە' : ($lang === 'ar' ? 'نظام التتبع المباشر للتوصيل السريع' : 'Active Express Logistics Radar'); ?>
                            </h4>
                            <p style="font-size:13px; color:var(--text-secondary); margin:0; line-height:1.5;">
                                <?php echo $lang === 'ku' ? 'پاکێتا تە د رێکا گەهاندنێ دایە. رەوشا شاندنێ و جهان ب شێوەیەکێ ئێکسەر ل ڤێرە بهێنە نیشاندان.' : ($lang === 'ar' ? 'شحنتك قيد الإرسال والتوزيع المباشر. يمكنك متابعة حالة الطرد وموقعه اللوجستي فورياً هنا.' : 'Your luxury order is being fulfilled and dispatched via direct express courier.'); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        <?php elseif ($searched && !$foundOrder): ?>
            <div class="no-order-found-card">
                <div class="empty-icon">⚠️</div>
                <h3>No order found matching "<?php echo htmlspecialchars($searchOrderId); ?>"</h3>
                <p>Please double check your Order ID (format: <code>ORD-XXXXX</code>) or phone number.</p>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
