<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/db.php';

// Handle JSON / AJAX requests (e.g. stock adjustment or quick edit)
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $jsonReq = json_decode($rawInput, true);
    if (is_array($jsonReq)) {
        if (isset($jsonReq['product_id']) && isset($jsonReq['stock_delta'])) {
            header('Content-Type: application/json');
            $newStock = adjust_product_stock($jsonReq['product_id'], $jsonReq['stock_delta']);
            echo json_encode(['success' => true, 'product_id' => (int)$jsonReq['product_id'], 'stock' => $newStock]);
            exit;
        }
    }
}

// Handle query parameter AJAX action
if (isset($_GET['action']) && $_GET['action'] === 'adjust_stock') {
    header('Content-Type: application/json');
    $pId = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
    $delta = intval($_POST['stock_delta'] ?? $_GET['stock_delta'] ?? 0);
    $newStock = adjust_product_stock($pId, $delta);
    echo json_encode(['success' => true, 'product_id' => $pId, 'stock' => $newStock]);
    exit;
}

$flashMsg = null;
$flashType = 'success';

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. UPDATE PRODUCT
    if (isset($_POST['update_product'])) {
        $pId = intval($_POST['edit_prod_id'] ?? 0);
        $titleEn = trim($_POST['edit_prod_title_en'] ?? '');
        $titleAr = trim($_POST['edit_prod_title_ar'] ?? '');
        $titleKu = trim($_POST['edit_prod_title_ku'] ?? '');
        $category = trim($_POST['edit_prod_category'] ?? 'clothes');
        $price = floatval($_POST['edit_prod_price'] ?? 0);
        $oldPrice = !empty($_POST['edit_prod_old_price']) ? floatval($_POST['edit_prod_old_price']) : null;
        $stock = intval($_POST['edit_prod_stock'] ?? 10);
        $featured = !empty($_POST['edit_prod_featured']);
        $badge = trim($_POST['edit_prod_badge'] ?? '');
        $badgeAr = trim($_POST['edit_prod_badge_ar'] ?? '');
        $badgeKu = trim($_POST['edit_prod_badge_ku'] ?? '');
        $image = trim($_POST['edit_prod_image'] ?? '');
        $galleryRaw = trim($_POST['edit_prod_gallery'] ?? '');
        $gallery = array_values(array_filter(array_map('trim', explode(',', $galleryRaw))));
        $sizesRaw = trim($_POST['edit_prod_sizes'] ?? '');
        $sizes = array_values(array_filter(array_map('trim', explode(',', $sizesRaw))));
        $colorsRaw = trim($_POST['edit_prod_colors'] ?? '');
        $colors = array_values(array_filter(array_map('trim', explode(',', $colorsRaw))));
        $descEn = trim($_POST['edit_prod_desc_en'] ?? '');
        $descAr = trim($_POST['edit_prod_desc_ar'] ?? '');
        $descKu = trim($_POST['edit_prod_desc_ku'] ?? '');
        $modelGroup = trim($_POST['edit_prod_model_group'] ?? '');
        $colorName = trim($_POST['edit_prod_color_name'] ?? '');
        $colorHex = trim($_POST['edit_prod_color_hex'] ?? '');
        $linkedProducts = isset($_POST['edit_prod_linked_products']) && is_array($_POST['edit_prod_linked_products']) 
            ? array_map('intval', $_POST['edit_prod_linked_products']) 
            : [];

        $productData = [
            'id' => $pId,
            'title' => [
                'en' => $titleEn,
                'ar' => $titleAr,
                'ku' => $titleKu
            ],
            'category' => $category,
            'price' => $price,
            'old_price' => $oldPrice,
            'stock' => $stock,
            'featured' => $featured,
            'badge' => $badge,
            'badge_ar' => $badgeAr,
            'badge_ku' => $badgeKu,
            'image' => $image,
            'images' => !empty($gallery) ? $gallery : (empty($image) ? [] : [$image]),
            'sizes' => !empty($sizes) ? $sizes : ['S', 'M', 'L', 'XL'],
            'colors' => !empty($colors) ? $colors : (!empty($colorName) ? [$colorName] : ['Default']),
            'model_group' => $modelGroup,
            'color_name' => $colorName,
            'color_hex' => $colorHex,
            'linked_products' => $linkedProducts,
            'description' => [
                'en' => $descEn,
                'ar' => $descAr,
                'ku' => $descKu
            ]
        ];

        save_product($productData);
        $flashMsg = "✓ Product #{$pId} ('" . htmlspecialchars($titleEn) . "') was updated successfully!";
    }
    // 2. ADD NEW PRODUCT
    elseif (isset($_POST['add_new_product'])) {
        $titleEn = trim($_POST['prod_title_en'] ?? '');
        $titleAr = trim($_POST['prod_title_ar'] ?? '');
        $titleKu = trim($_POST['prod_title_ku'] ?? '');
        $category = trim($_POST['prod_category'] ?? 'clothes');
        $price = floatval($_POST['prod_price'] ?? 0);
        $oldPrice = !empty($_POST['prod_old_price']) ? floatval($_POST['prod_old_price']) : null;
        $stock = intval($_POST['prod_stock'] ?? 10);
        $badge = trim($_POST['prod_badge'] ?? '');
        $badgeAr = trim($_POST['prod_badge_ar'] ?? '');
        $badgeKu = trim($_POST['prod_badge_ku'] ?? '');
        $image = trim($_POST['prod_image'] ?? '');
        $galleryRaw = trim($_POST['prod_gallery'] ?? '');
        $gallery = array_values(array_filter(array_map('trim', explode(',', $galleryRaw))));
        $descEn = trim($_POST['prod_desc_en'] ?? '');
        $descAr = trim($_POST['prod_desc_ar'] ?? '');
        $descKu = trim($_POST['prod_desc_ku'] ?? '');
        $modelGroup = trim($_POST['prod_model_group'] ?? '');
        $colorName = trim($_POST['prod_color_name'] ?? '');
        $colorHex = trim($_POST['prod_color_hex'] ?? '');
        $linkedProducts = isset($_POST['prod_linked_products']) && is_array($_POST['prod_linked_products']) 
            ? array_map('intval', $_POST['prod_linked_products']) 
            : [];

        $productData = [
            'title' => [
                'en' => $titleEn,
                'ar' => $titleAr,
                'ku' => $titleKu
            ],
            'category' => $category,
            'price' => $price,
            'old_price' => $oldPrice,
            'stock' => $stock,
            'featured' => false,
            'badge' => $badge,
            'badge_ar' => $badgeAr,
            'badge_ku' => $badgeKu,
            'image' => $image,
            'images' => !empty($gallery) ? $gallery : (empty($image) ? [] : [$image]),
            'sizes' => ['S', 'M', 'L', 'XL'],
            'colors' => !empty($colorName) ? [$colorName] : ['Default Edition'],
            'model_group' => $modelGroup,
            'color_name' => $colorName,
            'color_hex' => $colorHex,
            'linked_products' => $linkedProducts,
            'description' => [
                'en' => $descEn,
                'ar' => $descAr,
                'ku' => $descKu
            ]
        ];

        $saved = save_product($productData);
        $flashMsg = "✓ New luxury piece #{$saved['id']} ('" . htmlspecialchars($titleEn) . "') was published to the catalog!";
    }
    // 3. DELETE PRODUCT
    elseif (isset($_POST['delete_product_id'])) {
        $pId = intval($_POST['delete_product_id']);
        delete_product($pId);
        $flashMsg = "✓ Product #{$pId} has been permanently removed from the catalog.";
    }
}

$pageTitle = 'Product Catalog & Inventory | AURA Luxury Admin';
$adminActive = 'products';
$productsList = get_all_products();
$ordersList = get_all_orders();
$usersList = get_all_users();
$inquiriesList = get_all_inquiries();

$totalStock = 0;
$featuredCount = 0;
foreach ($productsList as $p) {
    $totalStock += ($p['stock'] ?? 0);
    if (!empty($p['featured'])) $featuredCount++;
}

$activePage = 'admin';
require_once __DIR__ . '/../header.php';
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">✦ Executive Command Suite</span>
            <h1 class="page-banner-title">Product Catalog & Inventory</h1>
            <p class="page-banner-subtitle">
                Trilingual luxury catalog management, real-time stock steppers, IQD pricing, and promotional badges.
            </p>
        </div>
    </div>
</div>

<section class="admin-section" style="padding: 40px 0 80px;">
    <div class="container">

        <!-- Unified Admin Navigation Bar -->
        <?php require_once __DIR__ . '/nav.php'; ?>

        <?php if ($flashMsg): ?>
            <div style="background:rgba(34,197,94,0.12); border:1px solid #22c55e; color:#22c55e; border-radius:8px; padding:14px 20px; margin-bottom:24px; font-weight:700; display:flex; align-items:center; justify-content:space-between;">
                <span><?php echo $flashMsg; ?></span>
                <button type="button" onclick="this.parentElement.style.display='none'" style="background:none; border:none; color:#22c55e; cursor:pointer; font-size:16px;">✕</button>
            </div>
        <?php endif; ?>

        <!-- Product Metrics Sub-Cards -->
        <div class="admin-metrics-grid" style="margin-bottom:24px;">
            <div class="admin-metric-card">
                <span class="m-icon">💎</span>
                <div class="m-info">
                    <span class="m-label">Total Catalog Pieces</span>
                    <strong class="m-value"><?php echo count($productsList); ?> Products</strong>
                    <span class="iqd-price-pill">Curated Luxury Collection</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">📊</span>
                <div class="m-info">
                    <span class="m-label">Total Vault Inventory</span>
                    <strong class="m-value" style="color:#22c55e;"><?php echo $totalStock; ?> Units in Stock</strong>
                    <span class="iqd-price-pill">Live Warehouse Count</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">⭐</span>
                <div class="m-info">
                    <span class="m-label">Featured Showcase</span>
                    <strong class="m-value" style="color:var(--accent-gold);"><?php echo $featuredCount; ?> Showcased</strong>
                    <span class="iqd-price-pill">Homepage Luxury Grid</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">🏷️</span>
                <div class="m-info">
                    <span class="m-label">Currencies & Pricing</span>
                    <strong class="m-value">100% IQD</strong>
                    <span class="iqd-price-pill">Official Iraqi Dinar</span>
                </div>
            </div>
        </div>

        <!-- Add Product Panel (Always Ready & Accessible) -->
        <div class="admin-form-card mb-24" id="addProductCard" style="border:2px solid var(--accent-gold); background:var(--bg-card);">
            <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div>
                    <h3 class="admin-card-title" style="margin:0; font-size:18px;">+ Add New Luxury Piece or Color Variant</h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Include trilingual titles in English, Arabic, and Kurdish Badini, pricing in IQD, and color linking options.</p>
                </div>
            </div>

            <form action="/admin/products.php" method="POST" id="newProductForm">
                <input type="hidden" name="add_new_product" value="1">
                
                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label>Title (English) <span class="text-danger">*</span></label>
                        <input type="text" name="prod_title_en" required class="form-control" placeholder="e.g. Royal Midnight Velvet Blazer">
                    </div>
                    <div class="form-group">
                        <label>Title (Arabic - العربية) <span class="text-danger">*</span></label>
                        <input type="text" name="prod_title_ar" required class="form-control" placeholder="مثال: بليزر ملكي كحلي مخملي">
                    </div>
                    <div class="form-group">
                        <label>Title (Kurdish - کوردی بادینی) <span class="text-danger">*</span></label>
                        <input type="text" name="prod_title_ku" required class="form-control" placeholder="وەکی: قاتی مخملی یێ شاهانە">
                    </div>
                </div>

                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label>Category <span class="text-danger">*</span></label>
                        <select name="prod_category" class="form-control">
                            <option value="clothes">Clothes & Apparel</option>
                            <option value="watches">Luxury Timepieces & Watches</option>
                            <option value="perfumes">Arabian Oud & Haute Perfumes</option>
                            <option value="accessories">Handcrafted Leather & Accessories</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Selling Price (IQD) <span class="text-danger">*</span></label>
                        <input type="number" name="prod_price" required class="form-control" placeholder="e.g. 240000" min="1000">
                    </div>
                    <div class="form-group">
                        <label>Original / Strikethrough Price (IQD)</label>
                        <input type="number" name="prod_old_price" class="form-control" placeholder="e.g. 300000 (Optional discount)">
                    </div>
                </div>

                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label>Initial Vault Stock</label>
                        <input type="number" name="prod_stock" class="form-control" value="15" min="0">
                    </div>
                    <div class="form-group">
                        <label>Promotional Badge (English)</label>
                        <input type="text" name="prod_badge" class="form-control" placeholder="e.g. Best Seller, Limited Edition">
                    </div>
                    <div class="form-group">
                        <label>Badge (Arabic)</label>
                        <input type="text" name="prod_badge_ar" class="form-control" placeholder="الأكثر مبيعاً">
                    </div>
                </div>

                <div class="form-group mb-16">
                    <label>Cover Image URL <span class="text-danger">*</span></label>
                    <input type="url" name="prod_image" required class="form-control" placeholder="https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80">
                </div>

                <div class="form-group mb-16">
                    <label>Additional Gallery Images (Comma-separated URLs)</label>
                    <textarea name="prod_gallery" rows="2" class="form-control" placeholder="https://image1.jpg, https://image2.jpg, https://image3.jpg"></textarea>
                </div>

                <!-- SECTION: Product Linking & Color Variants (For linking multiple shirts/colors of same model) -->
                <div id="colorVariantsSection" style="background:rgba(212, 175, 55, 0.08); padding:22px 24px; border-radius:var(--radius-sm); border:2px solid var(--accent-gold); margin-bottom:24px; box-shadow:0 0 15px rgba(212, 175, 55, 0.15);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
                        <div>
                            <span style="font-weight:900; font-size:16px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1.5px; display:flex; align-items:center; gap:8px;">
                                🎨 🔗 Link Product as a Color/Model Variant (Colors & Variations)
                            </span>
                            <p class="text-muted" style="margin:4px 0 0; font-size:13px; line-height:1.5;">
                                <strong>How to add 2 colors of the same product:</strong> Give both products the <em>same Model Group</em> (e.g. <code>classic-shirt</code>) or check the box of the other color below. Customers will see interactive color swatches on the product page to switch between them!
                            </p>
                        </div>
                        <span class="badge-tag" style="background:var(--accent-gold); color:#0a0c10; font-weight:800; padding:6px 12px; font-size:12px; border-radius:6px;">🎨 MULTI-COLOR ENGINE</span>
                    </div>

                    <div class="form-row-3 mb-16">
                        <div class="form-group">
                            <label style="font-weight:700; color:var(--text-primary);">1. Shared Model Group Code <span class="text-muted">(e.g. oxford-shirt)</span></label>
                            <input type="text" name="prod_model_group" id="prodModelGroup" class="form-control" placeholder="e.g. royal-blazer or classic-silk-shirt" style="border-color:var(--accent-gold);">
                            <small class="text-muted" style="font-size:11.5px;">All items with the same group code are linked together automatically as color variants.</small>
                        </div>
                        <div class="form-group">
                            <label style="font-weight:700; color:var(--text-primary);">2. This Item's Color Name <span class="text-danger">*</span></label>
                            <input type="text" name="prod_color_name" id="prodColorName" class="form-control" placeholder="e.g. Pure White, Obsidian Black, Emerald Green">
                        </div>
                        <div class="form-group">
                            <label style="font-weight:700; color:var(--text-primary);">3. Color Swatch Circle (Hex / Visual)</label>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <input type="color" id="prodColorPicker" value="#1e3a8a" style="width:44px; height:40px; padding:0; border:1px solid var(--border-color); border-radius:6px; cursor:pointer;" onchange="document.getElementById('prodColorHex').value = this.value;">
                                <input type="text" name="prod_color_hex" id="prodColorHex" class="form-control" placeholder="#1e3a8a" value="#1e3a8a" onchange="document.getElementById('prodColorPicker').value = this.value;">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="font-weight:700; color:var(--text-primary);">4. Or Direct Instant Link (Check the other color product):</label>
                        <div style="max-height:170px; overflow-y:auto; background:var(--bg-card); padding:12px; border-radius:8px; border:1px solid var(--border-color); display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:10px;">
                            <?php foreach ($productsList as $existingP): 
                                $existingPTitle = is_array($existingP['title']) ? ($existingP['title']['en'] ?? reset($existingP['title'])) : $existingP['title'];
                            ?>
                                <label style="display:flex; align-items:center; gap:10px; font-size:12.5px; cursor:pointer; padding:6px 10px; border-radius:6px; background:var(--bg-subtle); border:1px solid var(--border-color);">
                                    <input type="checkbox" name="prod_linked_products[]" value="<?php echo $existingP['id']; ?>" style="accent-color:var(--accent-gold); width:16px; height:16px;">
                                    <img src="<?php echo htmlspecialchars($existingP['image']); ?>" style="width:28px; height:28px; object-fit:cover; border-radius:4px;">
                                    <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px; font-weight:600;">#<?php echo $existingP['id']; ?> <?php echo htmlspecialchars($existingPTitle); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="form-row-3 mb-20">
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="prod_desc_en" rows="3" class="form-control" placeholder="Handcrafted with Italian cashmere velvet..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Arabic - العربية)</label>
                        <textarea name="prod_desc_ar" rows="3" class="form-control" placeholder="مصنوع يدوياً من أفخر أنواع المخمل الإيطالي..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish - کوردی)</label>
                        <textarea name="prod_desc_ku" rows="3" class="form-control" placeholder="ب دەستان هاتیە چێکرن ژ قوماشێ مخملی یێ ئیتالی..."></textarea>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px;">
                    <button type="button" class="btn btn-outline" onclick="toggleAddProductForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-luxury">Publish Piece to Boutique</button>
                </div>
            </form>
        </div>

        <!-- Products Table Card -->
        <div class="admin-table-card">
            <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:12px;">
                <div>
                    <h3 class="admin-card-title" style="margin:0; font-size:18px;">💎 Luxury Catalog Pieces & Inventory</h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Click (+) or (-) on any product stock for instant inventory adjustment, or Edit for trilingual details.</p>
                </div>
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <button type="button" class="btn btn-primary btn-luxury btn-sm" onclick="toggleAddProductForm()">
                        + Add New Product
                    </button>
                    <input type="text" id="prodSearchInput" onkeyup="filterProductsTable()" placeholder="Search pieces..." class="form-control" style="max-width:200px; padding:8px 12px; font-size:13px;">
                    <select id="prodCategoryFilter" onchange="filterProductsTable()" class="form-control" style="max-width:160px; padding:8px 12px; font-size:13px;">
                        <option value="">All Categories</option>
                        <option value="clothes">Clothes</option>
                        <option value="watches">Watches</option>
                        <option value="perfumes">Perfumes</option>
                        <option value="accessories">Accessories</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="admin-table" id="productsTableMain">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Details</th>
                            <th>Category</th>
                            <th>Price (IQD)</th>
                            <th>Stock Count</th>
                            <th>Rating</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productsList as $p): 
                            $pTitle = is_array($p['title']) ? ($p['title']['en'] ?? reset($p['title'])) : $p['title'];
                            $pPriceIqd = $p['price'] ?? 0;
                            $pOldPriceIqd = $p['old_price'] ?? null;
                            $pStock = $p['stock'] ?? 0;
                            $safeJson = json_encode($p);
                        ?>
                            <tr data-category="<?php echo htmlspecialchars(strtolower($p['category'] ?? '')); ?>" data-search="<?php echo htmlspecialchars(strtolower($pTitle . ' ' . ($p['category'] ?? '') . ' #' . $p['id'])); ?>">
                                <td>#<?php echo $p['id']; ?></td>
                                <td>
                                    <div class="admin-prod-preview">
                                        <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="" class="admin-prod-thumb" id="adminThumb_<?php echo $p['id']; ?>">
                                        <div>
                                            <strong><a href="/product.php?id=<?php echo $p['id']; ?>" target="_blank" style="color:var(--text-primary);"><?php echo htmlspecialchars($pTitle); ?></a></strong><br>
                                            <div style="display:flex; gap:4px; flex-wrap:wrap; margin-top:3px; align-items:center;">
                                                <?php if (!empty($p['color_name'])): ?>
                                                    <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; background:var(--bg-subtle); padding:2px 6px; border-radius:4px; border:1px solid var(--border-color);">
                                                        <span style="width:8px; height:8px; border-radius:50%; background:<?php echo htmlspecialchars(!empty($p['color_hex']) ? $p['color_hex'] : '#d4af37'); ?>; display:inline-block;"></span>
                                                        <?php echo htmlspecialchars($p['color_name']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($p['model_group'])): ?>
                                                    <small class="badge-tag" style="background:rgba(168,85,247,0.12); color:#c084fc; border-color:#a855f7; font-size:10.5px;">🔗 Group: <?php echo htmlspecialchars($p['model_group']); ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($p['badge'])): ?>
                                                    <small class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); border-color:var(--accent-gold); font-weight:700;"><?php echo htmlspecialchars($p['badge']); ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($p['featured'])): ?>
                                                    <small class="badge-tag" style="background:rgba(59,130,246,0.15); color:#60a5fa; border-color:#3b82f6;">⭐ Featured</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-tag text-uppercase"><?php echo htmlspecialchars($p['category'] ?? 'luxury'); ?></span></td>
                                <td>
                                    <div style="display:flex; flex-direction:column;">
                                        <strong class="font-bold" style="color:var(--accent-gold); font-size:14px;"><?php echo number_format($pPriceIqd); ?> IQD</strong>
                                        <?php if ($pOldPriceIqd && $pOldPriceIqd > $pPriceIqd): ?>
                                            <small style="text-decoration:line-through; color:var(--text-muted); font-size:11.5px;"><?php echo number_format($pOldPriceIqd); ?> IQD</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="stock-adjuster" id="stockAdjuster_<?php echo $p['id']; ?>" style="display:inline-flex; align-items:center; gap:6px; background:var(--bg-subtle); padding:4px 8px; border-radius:6px; border:1px solid var(--border-color);">
                                        <button type="button" class="btn-stock-stepper" onclick="window.AuraStore.adjustStock(<?php echo $p['id']; ?>, -1)" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border:none; background:var(--bg-surface); color:var(--text-primary); border-radius:4px; font-weight:800; cursor:pointer;" title="Decrease Stock">-</button>
                                        <span class="stock-value-badge font-bold" id="stockBadge_<?php echo $p['id']; ?>" style="min-width:28px; text-align:center; font-size:13px;"><?php echo $pStock; ?></span>
                                        <button type="button" class="btn-stock-stepper" onclick="window.AuraStore.adjustStock(<?php echo $p['id']; ?>, 1)" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border:none; background:var(--bg-surface); color:var(--text-primary); border-radius:4px; font-weight:800; cursor:pointer;" title="Increase Stock">+</button>
                                    </div>
                                </td>
                                <td>
                                    <span style="color:#d97706; font-size:13px;">★ <?php echo $p['rating'] ?? '4.9'; ?></span>
                                    <small class="text-muted">(<?php echo $p['reviews_count'] ?? '24'; ?>)</small>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:nowrap;">
                                        <button type="button" class="btn btn-outline btn-xs" onclick='openEditProductModal(<?php echo htmlspecialchars($safeJson, ENT_QUOTES, 'UTF-8'); ?>)' title="Edit Product Details & Badges">
                                            ✏️ Edit
                                        </button>
                                        <button type="button" class="btn btn-outline btn-xs" onclick='quickAddColorVariant(<?php echo htmlspecialchars($safeJson, ENT_QUOTES, 'UTF-8'); ?>)' style="border-color:var(--accent-gold); color:var(--accent-gold);" title="Add Another Color for this Product">
                                            🎨 + Color
                                        </button>
                                        <a href="/product.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn btn-ghost btn-xs" title="View in Boutique">👁️</a>
                                        <form action="/admin/products.php" method="POST" onsubmit="return confirm('Delete this product permanently?')" style="display:inline;">
                                            <input type="hidden" name="delete_product_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn btn-ghost text-danger btn-xs" title="Delete Product">✕</button>
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

<!-- EDIT PRODUCT MODAL (COMPLETE TRILINGUAL ENGINE) -->
<div class="modal-overlay" id="editProductModalOverlay">
    <div class="modal-card modal-card-wide" style="max-width:880px; max-height:90vh; overflow-y:auto;">
        <div class="modal-header" style="position:sticky; top:0; background:var(--bg-card); z-index:10; border-bottom:1px solid var(--border-color); padding:16px 24px; margin:-24px -24px 20px -24px;">
            <div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <h3 style="margin:0; font-size:20px; font-weight:800;">✏️ Edit Luxury Piece</h3>
                    <span class="badge-tag" id="editProductModalIdBadge" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:800;">#0</span>
                </div>
                <small class="text-muted" id="editProductModalSub">Update trilingual titles, pricing discounts, badges, and gallery</small>
            </div>
            <button type="button" class="btn-close-modal" onclick="closeEditProductModal()" style="font-size:20px; cursor:pointer;">✕</button>
        </div>

        <form action="/admin/products.php" method="POST" id="editProductForm">
            <input type="hidden" name="update_product" value="1">
            <input type="hidden" name="edit_prod_id" id="editProdId">

            <!-- Section 1: Core Pricing & Category -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:20px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">💰 Pricing & Category</span>
                <div class="form-row-3">
                    <div class="form-group">
                        <label>Category <span class="text-danger">*</span></label>
                        <select name="edit_prod_category" id="editProdCategory" class="form-control">
                            <option value="clothes">Clothes & Apparel</option>
                            <option value="watches">Luxury Watches</option>
                            <option value="perfumes">Arabian Oud & Perfumes</option>
                            <option value="accessories">Leather & Accessories</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Selling Price (IQD) <span class="text-danger">*</span></label>
                        <input type="number" name="edit_prod_price" id="editProdPrice" required class="form-control" oninput="calculateDiscountPreview()">
                    </div>
                    <div class="form-group">
                        <label>Original / Old Price (IQD)</label>
                        <input type="number" name="edit_prod_old_price" id="editProdOldPrice" class="form-control" oninput="calculateDiscountPreview()">
                        <span id="editDiscountBadge" class="badge-tag" style="display:none; margin-top:4px; background:rgba(34,197,94,0.15); color:#22c55e; border-color:#22c55e; font-weight:700;"></span>
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
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">🏷️ Promotional Badge / Ribbon</span>

                <div style="margin-bottom:12px;">
                    <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:6px;">Quick Presets (Click to Auto-fill Trilingual Badges):</label>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('⚡ 50% OFF', '⚡ خصم 50%', '⚡ داشکاندنا %50')">⚡ 50% OFF</button>
                        <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('🔥 Best Seller', '🔥 الأكثر مبيعاً', '🔥 پڕفرۆشترین')">🔥 Best Seller</button>
                        <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('💎 Limited Edition', '💎 إصدار محدود', '💎 وەشانەکا سنوردار')">💎 Limited Edition</button>
                        <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('✨ New Arrival', '✨ وصل حديثاً', '✨ نوی گەهشتی')">✨ New Arrival</button>
                        <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('👑 Royal Luxury', '👑 فاخر ملكي', '👑 شاهانە و نازک')">👑 Royal Luxury</button>
                        <button type="button" class="badge-tag text-danger" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('', '', '')">✕ Clear</button>
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

            <!-- Section 3: Image & Gallery -->
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
                            <input type="url" name="edit_prod_image" id="editProdImage" required class="form-control" oninput="updateEditImagePreview()">
                        </div>

                        <div class="form-group">
                            <label>Additional Gallery Images (Comma-Separated URLs)</label>
                            <textarea name="edit_prod_gallery" id="editProdGallery" rows="2" class="form-control" placeholder="https://image1.jpg, https://image2.jpg, https://image3.jpg"></textarea>
                        </div>
                    </div>
                </div>

                <div>
                    <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:6px;">Sample Photography Presets:</label>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80')">👔 Velvet Blazer</button>
                        <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80')">⌚ Swiss Watch</button>
                        <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80')">✨ Arabian Oud</button>
                        <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80')">👜 Leather Bag</button>
                    </div>
                </div>
            </div>

            <!-- Section 4: Trilingual Titles -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:20px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">🌐 Trilingual Titles</span>
                <div class="form-row-3">
                    <div class="form-group">
                        <label>Title (English) <span class="text-danger">*</span></label>
                        <input type="text" name="edit_prod_title_en" id="editProdTitleEn" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Title (Arabic - العربية) <span class="text-danger">*</span></label>
                        <input type="text" name="edit_prod_title_ar" id="editProdTitleAr" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Title (Kurdish - کوردی بادینی) <span class="text-danger">*</span></label>
                        <input type="text" name="edit_prod_title_ku" id="editProdTitleKu" required class="form-control">
                    </div>
                </div>
            </div>

            <!-- Section 4.5: Color Variations & Model Linking -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1.5px solid var(--accent-gold); margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                    <div>
                        <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block;">
                            🔗 Linked Colors & Model Grouping
                        </span>
                        <small class="text-muted">Connect multiple colors/editions of this same shirt or piece so buyers can pick variations easily on the product page</small>
                    </div>
                    <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:700;">Multi-Color Linking</span>
                </div>
                
                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label>Model Group Identifier <span class="text-muted">(Auto-links matching pieces)</span></label>
                        <input type="text" name="edit_prod_model_group" id="editProdModelGroup" class="form-control" placeholder="e.g. royal-blazer-2026 or oxford-shirt">
                    </div>
                    <div class="form-group">
                        <label>This Item's Color Name</label>
                        <input type="text" name="edit_prod_color_name" id="editProdColorName" class="form-control" placeholder="e.g. Midnight Blue, Obsidian Black">
                    </div>
                    <div class="form-group">
                        <label>Color Swatch Hex / Visual</label>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="color" id="editProdColorPicker" value="#d4af37" style="width:40px; height:38px; padding:0; border:1px solid var(--border-color); border-radius:4px; cursor:pointer;" onchange="document.getElementById('editProdColorHex').value = this.value;">
                            <input type="text" name="edit_prod_color_hex" id="editProdColorHex" class="form-control" placeholder="#d4af37" onchange="document.getElementById('editProdColorPicker').value = this.value;">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Or Directly Link to Catalog Items:</label>
                    <div id="editProdLinkedContainer" style="max-height:150px; overflow-y:auto; background:var(--bg-surface); padding:10px; border-radius:6px; border:1px solid var(--border-color); display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:8px;">
                        <?php foreach ($productsList as $existingP): 
                            $existingPTitle = is_array($existingP['title']) ? ($existingP['title']['en'] ?? reset($existingP['title'])) : $existingP['title'];
                        ?>
                            <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer; padding:4px 6px; border-radius:4px; background:var(--bg-subtle);">
                                <input type="checkbox" name="edit_prod_linked_products[]" value="<?php echo $existingP['id']; ?>" class="edit-linked-cb" id="editLinkedCb_<?php echo $existingP['id']; ?>">
                                <img src="<?php echo htmlspecialchars($existingP['image']); ?>" style="width:24px; height:24px; object-fit:cover; border-radius:4px;">
                                <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:170px;">#<?php echo $existingP['id']; ?> <?php echo htmlspecialchars($existingPTitle); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Section 5: Sizes, Colors & Descriptions -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:24px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">📝 Attributes & Trilingual Descriptions</span>
                
                <div class="form-row-2 mb-16">
                    <div class="form-group">
                        <label>Available Sizes (Comma-separated)</label>
                        <input type="text" name="edit_prod_sizes" id="editProdSizes" class="form-control" placeholder="S, M, L, XL or 42mm, 44mm">
                    </div>
                    <div class="form-group">
                        <label>Available Colors / Editions</label>
                        <input type="text" name="edit_prod_colors" id="editProdColors" class="form-control" placeholder="Midnight Black, Royal Gold, Emerald">
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="edit_prod_desc_en" id="editProdDescEn" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Arabic - العربية)</label>
                        <textarea name="edit_prod_desc_ar" id="editProdDescAr" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish - کوردی)</label>
                        <textarea name="edit_prod_desc_ku" id="editProdDescKu" rows="3" class="form-control"></textarea>
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; padding-top:10px;">
                <button type="button" class="btn btn-outline" onclick="closeEditProductModal()">Cancel</button>
                <button type="submit" class="btn btn-primary btn-luxury" style="padding:10px 24px;">Save & Apply Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAddProductForm() {
    const card = document.getElementById('addProductCard');
    if (!card) return;
    const isHidden = card.style.display === 'none';
    card.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function filterProductsTable() {
    const q = (document.getElementById('prodSearchInput').value || '').toLowerCase();
    const cat = document.getElementById('prodCategoryFilter').value.toLowerCase();
    const rows = document.querySelectorAll('#productsTableMain tbody tr');
    rows.forEach(row => {
        const rowSearch = row.getAttribute('data-search') || '';
        const rowCat = row.getAttribute('data-category') || '';
        const matchesQ = !q || rowSearch.includes(q);
        const matchesCat = !cat || rowCat === cat;
        row.style.display = (matchesQ && matchesCat) ? '' : 'none';
    });
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

    // Color Variations & Model Grouping
    document.getElementById('editProdModelGroup').value = product.model_group || '';
    document.getElementById('editProdColorName').value = product.color_name || '';
    const colHex = product.color_hex || '#d4af37';
    document.getElementById('editProdColorHex').value = colHex;
    document.getElementById('editProdColorPicker').value = colHex;

    // Clear and check linked product checkboxes
    const linkedIds = Array.isArray(product.linked_products) ? product.linked_products.map(Number) : [];
    document.querySelectorAll('.edit-linked-cb').forEach(cb => {
        const val = Number(cb.value);
        cb.checked = linkedIds.includes(val);
        // Hide the checkbox for the current product itself
        if (cb.closest('label')) {
            cb.closest('label').style.display = (val === Number(product.id)) ? 'none' : 'flex';
        }
    });

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

function quickAddColorVariant(product) {
    const pTitleEn = typeof product.title === 'object' ? (product.title.en || '') : product.title;
    const pTitleAr = typeof product.title === 'object' ? (product.title.ar || pTitleEn) : pTitleEn;
    const pTitleKu = typeof product.title === 'object' ? (product.title.ku || pTitleEn) : pTitleEn;

    const groupSlug = product.model_group || ('model-' + pTitleEn.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''));
    
    // Make sure addProductCard is visible
    const addCard = document.getElementById('addProductCard');
    if (addCard) addCard.style.display = 'block';

    // Fill Add Product Form fields
    document.getElementById('prodModelGroup').value = groupSlug;
    document.getElementById('prodColorName').value = '';
    document.getElementById('prodColorName').focus();

    // Check the box for the source product in the add form
    document.querySelectorAll('input[name="prod_linked_products[]"]').forEach(cb => {
        cb.checked = (Number(cb.value) === Number(product.id));
    });

    // Scroll to the Color Variants section
    const section = document.getElementById('colorVariantsSection');
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'center' });
        section.style.animation = 'pulseBorder 1.5s ease-out';
        setTimeout(() => { section.style.animation = ''; }, 2000);
    }
    
    if (window.AdminApp && window.AdminApp.toast) {
        window.AdminApp.toast('Ready to add a new color for "' + pTitleEn + '"! Fill in the new color details and image.', 'info');
    }
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
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>
