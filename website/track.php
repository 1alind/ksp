<?php
$activePage = 'track';
$pageTitle = 'Order Tracking';
require_once __DIR__ . '/header.php';

$searchOrderId = trim($_GET['order_id'] ?? '');
$foundOrder = null;
$searched = false;
$settings = get_store_settings();
$rate = $settings['exchange_rate_usd_to_iqd'] ?? 1320;
$issueSubmitted = false;
$issueTicketId = '';

// Handle Package Issue Claim Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_package_issue'])) {
    $claimOrderId = trim($_POST['claim_order_id'] ?? '');
    $claimCategory = trim($_POST['claim_category'] ?? 'Damaged Package');
    $claimName = trim($_POST['claim_name'] ?? '');
    $claimPhone = trim($_POST['claim_phone'] ?? '');
    $claimDetails = trim($_POST['claim_details'] ?? '');

    if (!empty($claimOrderId) && !empty($claimDetails)) {
        $inqData = read_json_db('inquiries.json');
        $inqList = $inqData['inquiries'] ?? [];
        $issueTicketId = 'CLAIM-' . rand(10000, 99999);
        $newClaim = [
            'id' => $issueTicketId,
            'is_package_claim' => true,
            'order_id' => htmlspecialchars($claimOrderId),
            'category' => htmlspecialchars($claimCategory),
            'name' => htmlspecialchars($claimName),
            'email' => htmlspecialchars($_POST['claim_email'] ?? ''),
            'phone' => htmlspecialchars($claimPhone),
            'subject' => '🚨 DELIVERED PACKAGE CLAIM (' . htmlspecialchars($claimOrderId) . '): ' . htmlspecialchars($claimCategory),
            'message' => htmlspecialchars($claimDetails),
            'status' => 'Pending Inspection',
            'date' => date('Y-m-d H:i:s')
        ];
        array_unshift($inqList, $newClaim);
        $inqData['inquiries'] = $inqList;
        write_json_db('inquiries.json', $inqData);
        $issueSubmitted = true;
    }
}

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

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">✦ Live Logistics Radar &bull; Iraq & Kurdistan</span>
            <h1 class="page-banner-title"><?php echo t('track_title', $lang); ?></h1>
            <p class="page-banner-subtitle"><?php echo t('track_subtitle', $lang); ?></p>
        </div>
    </div>
</div>

<section class="track-section">
    <div class="container">
        
        <!-- Fully Online Automated Fulfillment Guarantee Banner -->
        <div style="background:var(--bg-subtle); border:1px solid var(--border-color); border-radius:12px; padding:16px 20px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <span style="font-size:28px;">⚡</span>
                <div>
                    <strong style="display:block; font-size:14.5px; color:var(--text-primary);"><?php echo t('policy_full_online_badge', $lang); ?></strong>
                    <span style="font-size:12.5px; color:var(--text-secondary);"><?php echo t('policy_full_online_desc', $lang); ?></span>
                </div>
            </div>
            <span class="badge-tag" style="background:rgba(217,119,6,0.15); color:var(--accent-gold); font-size:11.5px; font-weight:700;">
                🛡️ <?php echo $lang === 'ku' ? 'زەمانەتا پشکنینا پاکێجێ' : ($lang === 'ar' ? 'ضمان وسلامة الطرد 100%' : '100% Package Guarantee'); ?>
            </span>
        </div>

        <?php if ($issueSubmitted): ?>
            <div class="alert alert-success mb-24" style="background:rgba(34,197,94,0.12); border:1px solid #22c55e; border-radius:10px; padding:18px; color:var(--text-primary);">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                    <span style="font-size:22px;">✅</span>
                    <h4 style="margin:0; font-size:16px; color:#22c55e;"><?php echo t('issue_success_msg', $lang); ?></h4>
                </div>
                <p style="margin:0; font-size:13.5px; color:var(--text-secondary);">
                    Your Claim Ticket Reference: <strong style="font-family:monospace; color:var(--accent-gold); font-size:15px;"><?php echo htmlspecialchars($issueTicketId); ?></strong>. Our Quality & Replacement Dispatcher will review your case within 2-4 hours.
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
            <div class="sample-ids-hint">
                <small>Try testing with sample orders: <code>ORD-84920</code> (FIB / Out for Delivery) or <code>ORD-73195</code> (ZainCash / Delivered) or <code>ORD-61028</code> (Baghdad / In Transit)</small>
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
                                    <?php echo htmlspecialchars($foundOrder['courier'] ?? 'AURA VIP Express Logistics'); ?>
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

                <!-- Package Issue / Problem Claim Box (Exclusive Concierge Intervention) -->
                <div class="package-issue-claim-card mt-24" style="background:var(--bg-subtle); border:1px solid rgba(217,119,6,0.3); border-radius:12px; padding:24px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                        <div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="font-size:20px;">📦</span>
                                <h3 style="font-size:17px; font-weight:800; color:var(--text-primary); margin:0;"><?php echo t('report_package_issue', $lang); ?></h3>
                            </div>
                            <p style="font-size:12.5px; color:var(--text-secondary); margin:4px 0 0;"><?php echo t('report_package_issue_desc', $lang); ?></p>
                        </div>
                        <button type="button" class="btn btn-outline btn-xs" onclick="const f=document.getElementById('packageIssueForm'); f.style.display = f.style.display === 'none' ? 'block' : 'none';" style="color:var(--accent-gold); border-color:var(--accent-gold); font-weight:700;">
                            ⚠️ <?php echo $lang === 'ku' ? 'تۆمارکرنا کێشەیێ' : ($lang === 'ar' ? 'فتح بلاغ مشكلة' : 'Open Claim Form'); ?>
                        </button>
                    </div>

                    <form action="track.php?order_id=<?php echo urlencode($foundOrder['order_id']); ?>" method="POST" id="packageIssueForm" style="display:none; margin-top:16px; border-top:1px solid var(--border-color); padding-top:16px;">
                        <input type="hidden" name="submit_package_issue" value="1">
                        <input type="hidden" name="claim_order_id" value="<?php echo htmlspecialchars($foundOrder['order_id']); ?>">
                        <input type="hidden" name="claim_name" value="<?php echo htmlspecialchars($foundOrder['customer_name']); ?>">
                        <input type="hidden" name="claim_phone" value="<?php echo htmlspecialchars($foundOrder['phone']); ?>">
                        <input type="hidden" name="claim_email" value="<?php echo htmlspecialchars($foundOrder['email'] ?? ''); ?>">

                        <div class="form-group" style="margin-bottom:12px;">
                            <label style="font-size:12.5px; font-weight:700; color:var(--text-primary); margin-bottom:6px; display:block;"><?php echo t('issue_category', $lang); ?> <span class="text-danger">*</span></label>
                            <select name="claim_category" required class="form-control" style="font-size:13.5px;">
                                <option value="<?php echo t('issue_cat_damaged', $lang); ?>">📦 <?php echo t('issue_cat_damaged', $lang); ?></option>
                                <option value="<?php echo t('issue_cat_wrong_item', $lang); ?>">🔄 <?php echo t('issue_cat_wrong_item', $lang); ?></option>
                                <option value="<?php echo t('issue_cat_defective', $lang); ?>">⚙️ <?php echo t('issue_cat_defective', $lang); ?></option>
                                <option value="<?php echo t('issue_cat_missing', $lang); ?>">🔍 <?php echo t('issue_cat_missing', $lang); ?></option>
                                <option value="<?php echo t('issue_cat_courier', $lang); ?>">🚚 <?php echo t('issue_cat_courier', $lang); ?></option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom:14px;">
                            <label style="font-size:12.5px; font-weight:700; color:var(--text-primary); margin-bottom:6px; display:block;"><?php echo t('issue_details', $lang); ?> <span class="text-danger">*</span></label>
                            <textarea name="claim_details" rows="3" required class="form-control" placeholder="<?php echo $lang === 'ku' ? 'تکایە هویرکاریێن ئاریشەیا رویدای د پاکێجێ دا بنڤیسە...' : ($lang === 'ar' ? 'يرجى كتابة تفاصيل المشكلة التي واجهتها في الطرد المستلم...' : 'Please describe any defect, damage, sizing error, or missing piece...'); ?>" style="font-size:13px;"></textarea>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                            <span class="text-muted" style="font-size:11.5px;">
                                🔒 <?php echo $lang === 'ku' ? 'داخازی دێ هێتە شاندن بۆ بەشێ زەمانەتێ' : ($lang === 'ar' ? 'سيتم إحالة الطلب مباشرة لقسم الضمان والاستبدال' : 'Direct VIP routing to our Quality & Warranty inspector'); ?>
                            </span>
                            <button type="submit" class="btn btn-primary btn-luxury btn-sm">
                                🚀 <?php echo t('issue_submit', $lang); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Direct Concierge Help for Package Issues -->
                <div class="mt-24 text-center">
                    <p class="text-muted" style="font-size:12.5px;"><?php echo t('policy_issue_only_notice', $lang); ?></p>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['contact_whatsapp'] ?? '9647501234567'); ?>?text=<?php echo rawurlencode('Hello AURA Claims Inspector, I have an issue regarding my delivered package for order ' . $foundOrder['order_id']); ?>" target="_blank" class="btn btn-outline btn-sm" style="color:#22c55e; border-color:#22c55e;">
                        💬 <?php echo $lang === 'ku' ? 'پەیوەندیکرن ب بەشێ زەمانەتێ ل سەر واتسئەپ' : ($lang === 'ar' ? 'التواصل المباشر مع مسؤول الضمان عبر واتساب' : 'Contact Package Claims on WhatsApp'); ?>
                    </a>
                </div>

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
