<?php
$activePage = 'admin';
$pageTitle = 'Management Dashboard';
require_once __DIR__ . '/header.php';

$actionMsg = '';

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $orderId = trim($_POST['order_id'] ?? '');
    $newStatus = trim($_POST['order_status'] ?? '');
    if (!empty($orderId) && !empty($newStatus)) {
        if (update_order_status($orderId, $newStatus)) {
            $actionMsg = 'Order ' . htmlspecialchars($orderId) . ' status updated to ' . htmlspecialchars($newStatus);
        }
    }
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

// Handle Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_id'])) {
    $delId = intval($_POST['delete_product_id']);
    delete_product($delId);
    $actionMsg = 'Product ID #' . $delId . ' was deleted from database.';
}

$ordersList = get_all_orders();
$productsList = get_all_products();
$usersList = get_all_users();

// Calculate Revenue
$totalRevenue = 0;
foreach ($ordersList as $o) {
    $totalRevenue += ($o['total'] ?? 0);
}
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">Database Controller</span>
            <h1 class="page-banner-title"><?php echo t('admin_title', $lang); ?></h1>
            <p class="page-banner-subtitle">Real-time database records stored in <code>website/database/</code></p>
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
                    <strong class="m-value text-primary">$<?php echo number_format($totalRevenue, 2); ?></strong>
                </div>
            </div>

            <div class="admin-metric-card">
                <span class="m-icon">📦</span>
                <div class="m-info">
                    <span class="m-label"><?php echo t('admin_stats_orders', $lang); ?></span>
                    <strong class="m-value"><?php echo count($ordersList); ?> Orders</strong>
                </div>
            </div>

            <div class="admin-metric-card">
                <span class="m-icon">💎</span>
                <div class="m-info">
                    <span class="m-label"><?php echo t('admin_stats_products', $lang); ?></span>
                    <strong class="m-value"><?php echo count($productsList); ?> Pieces</strong>
                </div>
            </div>

            <div class="admin-metric-card">
                <span class="m-icon">👥</span>
                <div class="m-info">
                    <span class="m-label"><?php echo t('admin_stats_users', $lang); ?></span>
                    <strong class="m-value"><?php echo count($usersList); ?> Users</strong>
                </div>
            </div>
        </div>

        <!-- Admin Tabs Control -->
        <div class="admin-tabs-nav mt-32">
            <button class="admin-tab-btn active" onclick="switchAdminTab('adm-orders', this)">
                📦 <?php echo t('admin_tab_orders', $lang); ?> (<?php echo count($ordersList); ?>)
            </button>
            <button class="admin-tab-btn" onclick="switchAdminTab('adm-products', this)">
                💎 <?php echo t('admin_tab_products', $lang); ?> (<?php echo count($productsList); ?>)
            </button>
            <button class="admin-tab-btn" onclick="switchAdminTab('adm-add-product', this)">
                + <?php echo t('admin_add_product', $lang); ?>
            </button>
        </div>

        <!-- 1. Orders Management Tab -->
        <div class="admin-tab-pane active" id="adm-orders">
            <div class="admin-table-card">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Customer & City</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordersList as $ord): ?>
                                <tr>
                                    <td><strong><a href="track.php?order_id=<?php echo urlencode($ord['order_id']); ?>"><?php echo htmlspecialchars($ord['order_id']); ?></a></strong></td>
                                    <td><small><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></small></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ord['customer_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($ord['city']); ?> • <?php echo htmlspecialchars($ord['phone']); ?></small>
                                    </td>
                                    <td><?php echo count($ord['items'] ?? []); ?> pcs</td>
                                    <td class="font-bold text-primary">$<?php echo number_format($ord['total'], 2); ?></td>
                                    <td><span class="badge-tag"><?php echo htmlspecialchars($ord['payment_method']); ?></span></td>
                                    <td>
                                        <form action="admin.php" method="POST" class="inline-status-form">
                                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($ord['order_id']); ?>">
                                            <input type="hidden" name="update_order_status" value="1">
                                            <select name="order_status" class="status-select" onchange="this.form.submit()">
                                                <option value="Pending" <?php echo ($ord['order_status'] ?? '') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Processing" <?php echo ($ord['order_status'] ?? '') === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="Shipped" <?php echo ($ord['order_status'] ?? '') === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="Delivered" <?php echo ($ord['order_status'] ?? '') === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="track.php?order_id=<?php echo urlencode($ord['order_id']); ?>" class="btn btn-outline btn-xs">View Track</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. Products Management Tab -->
        <div class="admin-tab-pane" id="adm-products">
            <div class="admin-table-card">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Rating</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productsList as $p): 
                                $pTitle = is_array($p['title']) ? ($p['title'][$lang] ?? $p['title']['en']) : $p['title'];
                            ?>
                                <tr>
                                    <td>#<?php echo $p['id']; ?></td>
                                    <td>
                                        <div class="admin-prod-preview">
                                            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="" class="admin-prod-thumb">
                                            <div>
                                                <strong><?php echo htmlspecialchars($pTitle); ?></strong><br>
                                                <small class="badge-tag"><?php echo htmlspecialchars($p['badge'] ?? ''); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge-tag text-uppercase"><?php echo htmlspecialchars($p['category']); ?></span></td>
                                    <td class="font-bold">$<?php echo number_format($p['price'], 2); ?></td>
                                    <td><?php echo $p['stock']; ?> left</td>
                                    <td>★ <?php echo number_format($p['rating'], 1); ?></td>
                                    <td>
                                        <form action="admin.php" method="POST" onsubmit="return confirm('Delete product permanently?')">
                                            <input type="hidden" name="delete_product_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn btn-ghost text-danger btn-xs">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. Add Product Tab -->
        <div class="admin-tab-pane" id="adm-add-product">
            <div class="admin-form-card">
                <h3 class="admin-card-title">+ Add New Luxury Product to Database</h3>
                <form action="admin.php" method="POST" class="add-product-form">
                    <input type="hidden" name="add_new_product" value="1">

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Product Title (English) <span class="text-danger">*</span></label>
                            <input type="text" name="prod_title_en" required class="form-control" placeholder="e.g. Royal Sapphire Chronograph">
                        </div>
                        <div class="form-group">
                            <label>Product Title (Arabic)</label>
                            <input type="text" name="prod_title_ar" class="form-control" placeholder="مثال: ساعة سافاير الملكية">
                        </div>
                        <div class="form-group">
                            <label>Product Title (Kurdish Badini)</label>
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
                            <label>Price ($ USD) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="prod_price" required class="form-control" placeholder="250.00">
                        </div>
                        <div class="form-group">
                            <label>Old Price ($ USD) (Optional)</label>
                            <input type="number" step="0.01" name="prod_old_price" class="form-control" placeholder="320.00">
                        </div>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Stock Quantity</label>
                            <input type="number" name="prod_stock" value="15" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Badge Tag</label>
                            <input type="text" name="prod_badge" value="New Arrival" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Image URL</label>
                            <input type="url" name="prod_image" value="https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="prod_desc_en" rows="3" class="form-control" placeholder="Detailed product craftsmanship description..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-luxury btn-lg mt-16">
                        💾 Save Product to Database
                    </button>
                </form>
            </div>
        </div>

    </div>
</section>

<script>
function switchAdminTab(tabId, btn) {
    document.querySelectorAll('.admin-tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
