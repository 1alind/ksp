<?php
$activePage = 'track';
$pageTitle = 'Order Tracking';
require_once __DIR__ . '/header.php';

$searchOrderId = trim($_GET['order_id'] ?? '');
$foundOrder = null;
$searched = false;

if (!empty($searchOrderId)) {
    $searched = true;
    $orders = get_all_orders();
    foreach ($orders as $ord) {
        if (strcasecmp($ord['order_id'], $searchOrderId) === 0 || strcasecmp($ord['email'] ?? '', $searchOrderId) === 0 || strcasecmp($ord['phone'] ?? '', $searchOrderId) === 0) {
            $foundOrder = $ord;
            break;
        }
    }
}
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">Live Logistics Radar</span>
            <h1 class="page-banner-title"><?php echo t('track_title', $lang); ?></h1>
            <p class="page-banner-subtitle"><?php echo t('track_subtitle', $lang); ?></p>
        </div>
    </div>
</div>

<section class="track-section">
    <div class="container">
        
        <!-- Search Order Form Card -->
        <div class="track-search-card">
            <form action="track.php" method="GET" class="track-form">
                <div class="track-input-group">
                    <input type="text" name="order_id" required class="track-input" placeholder="<?php echo t('track_input_placeholder', $lang); ?>" value="<?php echo htmlspecialchars($searchOrderId); ?>">
                    <button type="submit" class="btn btn-primary btn-luxury btn-lg"><?php echo t('track_button', $lang); ?></button>
                </div>
            </form>
            <div class="sample-ids-hint">
                <small>Try testing with sample orders: <code>ORD-84920</code> or <code>ORD-73195</code></small>
            </div>
        </div>

        <?php if ($searched && $foundOrder): 
            $status = $foundOrder['order_status'] ?? 'Processing';
            $stepMap = ['Pending' => 1, 'Processing' => 2, 'Shipped' => 3, 'Delivered' => 4];
            $currentStep = $stepMap[$status] ?? 2;
        ?>
            <!-- Live Tracking Details & Progress Timeline -->
            <div class="order-track-result-card">
                <div class="track-header-meta">
                    <div>
                        <span class="track-badge"><?php echo htmlspecialchars($status); ?></span>
                        <h2 class="track-order-num"><?php echo htmlspecialchars($foundOrder['order_id']); ?></h2>
                    </div>
                    <div class="track-date-info">
                        <span>Placed on:</span>
                        <strong><?php echo date('M d, Y • h:i A', strtotime($foundOrder['created_at'])); ?></strong>
                    </div>
                </div>

                <!-- 4-Step Timeline -->
                <div class="timeline-stepper">
                    <div class="step-item <?php echo $currentStep >= 1 ? 'completed' : ''; ?> <?php echo $currentStep === 1 ? 'current' : ''; ?>">
                        <div class="step-circle">1</div>
                        <div class="step-info">
                            <strong><?php echo t('status_pending', $lang); ?></strong>
                            <small>Order received</small>
                        </div>
                    </div>

                    <div class="step-line <?php echo $currentStep >= 2 ? 'active' : ''; ?>"></div>

                    <div class="step-item <?php echo $currentStep >= 2 ? 'completed' : ''; ?> <?php echo $currentStep === 2 ? 'current' : ''; ?>">
                        <div class="step-circle">2</div>
                        <div class="step-info">
                            <strong><?php echo t('status_processing', $lang); ?></strong>
                            <small>Satin packaging</small>
                        </div>
                    </div>

                    <div class="step-line <?php echo $currentStep >= 3 ? 'active' : ''; ?>"></div>

                    <div class="step-item <?php echo $currentStep >= 3 ? 'completed' : ''; ?> <?php echo $currentStep === 3 ? 'current' : ''; ?>">
                        <div class="step-circle">3</div>
                        <div class="step-info">
                            <strong><?php echo t('status_shipped', $lang); ?></strong>
                            <small>Courier dispatch</small>
                        </div>
                    </div>

                    <div class="step-line <?php echo $currentStep >= 4 ? 'active' : ''; ?>"></div>

                    <div class="step-item <?php echo $currentStep >= 4 ? 'completed' : ''; ?> <?php echo $currentStep === 4 ? 'current' : ''; ?>">
                        <div class="step-circle">4</div>
                        <div class="step-info">
                            <strong><?php echo t('status_delivered', $lang); ?></strong>
                            <small>Client received</small>
                        </div>
                    </div>
                </div>

                <!-- Details breakdown -->
                <div class="track-details-grid">
                    <div class="track-info-box">
                        <h4>Customer & Destination</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($foundOrder['customer_name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($foundOrder['phone']); ?></p>
                        <p><strong>City:</strong> <?php echo htmlspecialchars($foundOrder['city']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($foundOrder['address']); ?></p>
                    </div>

                    <div class="track-info-box">
                        <h4>Payment Information</h4>
                        <p><strong>Method:</strong> <?php echo htmlspecialchars($foundOrder['payment_method']); ?></p>
                        <p><strong>Status:</strong> <span class="badge-status-paid"><?php echo htmlspecialchars($foundOrder['payment_status'] ?? 'Pending'); ?></span></p>
                        <p><strong>Total Amount:</strong> <span class="text-primary font-bold text-lg">$<?php echo number_format($foundOrder['total'], 2); ?></span></p>
                    </div>
                </div>

                <!-- Items list in order -->
                <div class="track-items-box mt-24">
                    <h4>Package Contents (<?php echo count($foundOrder['items']); ?> items)</h4>
                    <div class="track-items-table">
                        <?php foreach ($foundOrder['items'] as $item): 
                            $itTitle = is_array($item['title']) ? ($item['title'][$lang] ?? $item['title']['en']) : $item['title'];
                        ?>
                            <div class="track-item-row">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($itTitle); ?>" class="track-item-thumb">
                                <div class="track-item-desc">
                                    <strong><?php echo htmlspecialchars($itTitle); ?></strong>
                                    <span>Quantity: <?php echo $item['quantity']; ?> <?php echo !empty($item['size']) ? '• Size: ' . $item['size'] : ''; ?></span>
                                </div>
                                <div class="track-item-price">
                                    $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
