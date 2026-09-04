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
        // Process Color Variants for Edit
        $colors = [];
        $colorHexes = [];
        $colorImages = [];
        
        if (!empty($_POST['edit_variant_name']) && is_array($_POST['edit_variant_name'])) {
            foreach ($_POST['edit_variant_name'] as $idx => $vName) {
                $vName = trim($vName);
                if ($vName !== '') {
                    $colors[] = $vName;
                    $vHex = trim($_POST['edit_variant_hex'][$idx] ?? '#d4af37');
                    $vImg = trim($_POST['edit_variant_image'][$idx] ?? '');
                    if ($vHex !== '') $colorHexes[$vName] = $vHex;
                    if ($vImg !== '') {
                        $colorImages[$vName] = $vImg;
                        if (!in_array($vImg, $gallery)) $gallery[] = $vImg;
                    }
                }
            }
        }
        
        if (empty($colors) && !empty($_POST['edit_prod_colors'])) {
            $colors = array_values(array_filter(array_map('trim', explode(',', $_POST['edit_prod_colors']))));
        }
        if (empty($colors) && !empty($colorName)) {
            $colors = [$colorName];
            if ($colorHex) $colorHexes[$colorName] = $colorHex;
            if ($image) $colorImages[$colorName] = $image;
        }
        if (empty($colors)) {
            $colors = ['Default Edition'];
        }
        if (empty($colorName) && !empty($colors[0])) {
            $colorName = $colors[0];
            $colorHex = $colorHexes[$colorName] ?? '#d4af37';
        }
        if (empty($image) && !empty($colorImages[$colorName])) {
            $image = $colorImages[$colorName];
        }

        $descEn = trim($_POST['edit_prod_desc_en'] ?? '');
        $descAr = trim($_POST['edit_prod_desc_ar'] ?? '');
        $descKu = trim($_POST['edit_prod_desc_ku'] ?? '');
        $modelGroup = trim($_POST['edit_prod_model_group'] ?? '');
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
            'colors' => $colors,
            'color_hexes' => $colorHexes,
            'color_images' => $colorImages,
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

        // Process Color Variants for Add
        $colors = [];
        $colorHexes = [];
        $colorImages = [];
        
        if (!empty($_POST['prod_variant_name']) && is_array($_POST['prod_variant_name'])) {
            foreach ($_POST['prod_variant_name'] as $idx => $vName) {
                $vName = trim($vName);
                if ($vName !== '') {
                    $colors[] = $vName;
                    $vHex = trim($_POST['prod_variant_hex'][$idx] ?? '#d4af37');
                    $vImg = trim($_POST['prod_variant_image'][$idx] ?? '');
                    if ($vHex !== '') $colorHexes[$vName] = $vHex;
                    if ($vImg !== '') {
                        $colorImages[$vName] = $vImg;
                        if (!in_array($vImg, $gallery)) $gallery[] = $vImg;
                    }
                }
            }
        }

        $colorName = trim($_POST['prod_color_name'] ?? '');
        $colorHex = trim($_POST['prod_color_hex'] ?? '');

        if (empty($colors) && !empty($_POST['prod_colors_comma'])) {
            $colors = array_values(array_filter(array_map('trim', explode(',', $_POST['prod_colors_comma']))));
        }
        if (empty($colors) && !empty($colorName)) {
            $colors = [$colorName];
            if ($colorHex) $colorHexes[$colorName] = $colorHex;
            if ($image) $colorImages[$colorName] = $image;
        }
        if (empty($colors)) {
            $colors = ['Default Edition'];
        }
        if (empty($colorName) && !empty($colors[0])) {
            $colorName = $colors[0];
            $colorHex = $colorHexes[$colorName] ?? '#d4af37';
        }
        if (empty($image) && !empty($colorImages[$colorName])) {
            $image = $colorImages[$colorName];
        }

        $descEn = trim($_POST['prod_desc_en'] ?? '');
        $descAr = trim($_POST['prod_desc_ar'] ?? '');
        $descKu = trim($_POST['prod_desc_ku'] ?? '');
        $modelGroup = trim($_POST['prod_model_group'] ?? '');
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
            'colors' => $colors,
            'color_hexes' => $colorHexes,
            'color_images' => $colorImages,
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
            <span class="section-kicker">✦ <?php echo adm_t('admin_nav_products', 'Products'); ?></span>
            <h1 class="page-banner-title"><?php echo adm_t('admin_products_title', 'Products & Inventory Atelier'); ?></h1>
            <p class="page-banner-subtitle">
                <?php echo adm_t('admin_products_subtitle', 'Manage high-jewelry, haute couture, luxury watches and artisan perfumes. Edit pricing, inventory, colors, and promotional badges.'); ?>
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
                    <span class="m-label"><?php echo adm_t('admin_metric_pieces', 'Boutique Pieces'); ?></span>
                    <strong class="m-value"><?php echo count($productsList); ?> <?php echo adm_t('admin_nav_products', 'Products'); ?></strong>
                    <span class="iqd-price-pill"><?php echo adm_t('admin_metric_catalog_active', 'Luxury Catalog Active'); ?></span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">📊</span>
                <div class="m-info">
                    <span class="m-label"><?php echo adm_t('admin_field_stock', 'Stock Quantity'); ?></span>
                    <strong class="m-value" style="color:#22c55e;"><?php echo $totalStock; ?> IQD</strong>
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
                    <span class="m-label"><?php echo adm_t('admin_metric_all_iqd', 'All Orders in Iraqi Dinar'); ?></span>
                    <strong class="m-value">100% IQD</strong>
                    <span class="iqd-price-pill"><?php echo adm_t('admin_products_official_iqd', 'Official Iraqi Dinar'); ?></span>
                </div>
            </div>
        </div>

        <!-- Add Product Panel (Always Ready & Accessible) -->
        <div class="admin-form-card mb-24" id="addProductCard" style="border:none; background:var(--bg-card); box-shadow:none;">
            <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div>
                    <h3 class="admin-card-title" style="margin:0; font-size:18px;"><?php echo adm_t('admin_add_product_title', '+ Add New Luxury Piece or Color Variant'); ?></h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_add_product_subtitle', 'Define multilingual titles, category, high-resolution imagery, and color swatches.'); ?></p>
                </div>
            </div>

            <form action="/admin/products.php" method="POST" id="newProductForm">
                <input type="hidden" name="add_new_product" value="1">
                
                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_title_en', 'Product Title (English)'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="prod_title_en" required class="form-control" placeholder="e.g. Royal Midnight Velvet Blazer">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_title_ar', 'Product Title (Arabic / عربي)'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="prod_title_ar" required class="form-control" placeholder="مثال: بليزر ملكي كحلي مخملي">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_title_ku', 'Product Title (Kurdish / کوردی بادینی)'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="prod_title_ku" required class="form-control" placeholder="وەکی: قاتی مخملی یێ شاهانە">
                    </div>
                </div>

                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_category', 'Category'); ?> <span class="text-danger">*</span></label>
                        <select name="prod_category" class="form-control">
                            <option value="clothes"><?php echo adm_t('cat_clothes', 'Clothes & Apparel'); ?></option>
                            <option value="watches"><?php echo adm_t('cat_watches', 'Luxury Timepieces & Watches'); ?></option>
                            <option value="perfumes"><?php echo adm_t('cat_perfumes', 'Arabian Oud & Haute Perfumes'); ?></option>
                            <option value="accessories"><?php echo adm_t('cat_accessories', 'Handcrafted Leather & Accessories'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_price', 'Current Price (IQD)'); ?> <span class="text-danger">*</span></label>
                        <input type="number" name="prod_price" required class="form-control" placeholder="e.g. 240000" min="1000">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_old_price', 'Original / Old Price (IQD)'); ?></label>
                        <input type="number" name="prod_old_price" class="form-control" placeholder="e.g. 300000">
                    </div>
                </div>

                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_stock', 'Stock Quantity'); ?></label>
                        <input type="number" name="prod_stock" class="form-control" value="15" min="0">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_badge_en', 'Promo Badge (English)'); ?></label>
                        <input type="text" name="prod_badge" class="form-control" placeholder="e.g. Best Seller, Limited Edition">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_badge_ar', 'Promo Badge (Arabic / عربي)'); ?></label>
                        <input type="text" name="prod_badge_ar" class="form-control" placeholder="الأكثر مبيعاً">
                    </div>
                </div>

                <div class="form-group mb-16">
                    <label><?php echo adm_t('admin_field_main_image', 'Main Product Image URL'); ?> <span class="text-danger">*</span></label>
                    <input type="url" name="prod_image" required class="form-control" placeholder="https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80">
                </div>

                <div class="form-group mb-16">
                    <label><?php echo adm_t('admin_field_gallery_images', 'Additional Gallery Images (Comma-separated URLs)'); ?></label>
                    <textarea name="prod_gallery" rows="2" class="form-control" placeholder="https://image1.jpg, https://image2.jpg, https://image3.jpg"></textarea>
                </div>

                <!-- SECTION: Product Colors & Swatches (Add 2 or More Colors Directly) -->
                <div id="colorVariantsSection" style="background:var(--bg-subtle); padding:20px 24px; border-radius:var(--radius-md); border:none; margin-bottom:24px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
                        <div>
                            <span style="font-weight:700; font-size:15px; color:var(--text-primary); text-transform:uppercase; letter-spacing:0.5px; display:flex; align-items:center; gap:8px;">
                                🎨 <?php echo adm_t('admin_field_colors_section', 'Product Colors & Editions'); ?>
                            </span>
                            <p class="text-muted" style="margin:4px 0 0; font-size:13px; line-height:1.5;">
                                <?php echo adm_t('admin_field_colors_section_desc', 'Configure multiple color variations with individual swatch colors and corresponding photos.'); ?>
                            </p>
                        </div>
                        <span class="badge-tag" style="background:var(--bg-card); border:none; color:var(--text-primary); font-weight:700; padding:6px 12px; font-size:12px; border-radius:6px;"><?php echo adm_t('admin_product_col_colors', 'Colors / Swatches'); ?></span>
                    </div>

                    <!-- Dynamic List of Colors for this Product -->
                    <div id="addColorVariantsList" style="display:flex; flex-direction:column; gap:10px; margin-bottom:14px;">
                        <!-- Row 1: Default Color 1 -->
                        <div class="color-variant-row" style="display:grid; grid-template-columns:180px 140px 1fr 40px; gap:10px; align-items:center; background:var(--bg-card); padding:10px 14px; border-radius:8px; border:none;">
                            <div>
                                <label style="font-size:11px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:2px;"><?php echo adm_t('admin_field_color_name', 'Color Name'); ?> 1</label>
                                <input type="text" name="prod_variant_name[]" value="Obsidian Black" class="form-control" placeholder="e.g. Obsidian Black" required style="font-size:13px; padding:6px 10px;">
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:2px;"><?php echo adm_t('admin_field_color_swatch', 'Color Swatch'); ?></label>
                                <div style="display:flex; gap:6px; align-items:center;">
                                    <input type="color" value="#111827" style="width:36px; height:34px; padding:0; border:1px solid var(--border-color); border-radius:4px; cursor:pointer;" onchange="this.nextElementSibling.value = this.value;">
                                    <input type="text" name="prod_variant_hex[]" value="#111827" class="form-control" style="font-size:12px; padding:6px 6px; font-family:monospace;" onchange="this.previousElementSibling.value = this.value;">
                                </div>
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:2px;"><?php echo adm_t('admin_field_color_image', 'Color Photo URL'); ?></label>
                                <input type="url" name="prod_variant_image[]" class="form-control" placeholder="https://image-for-black-version.jpg" style="font-size:13px; padding:6px 10px;">
                            </div>
                            <div style="padding-top:16px; text-align:center;">
                                <button type="button" class="btn btn-sm btn-outline" style="color:var(--text-muted); padding:6px 8px;" onclick="removeColorVariantRow(this)" title="<?php echo adm_t('admin_btn_remove_color', 'Remove color'); ?>">✕</button>
                            </div>
                        </div>

                        <!-- Row 2: Default Color 2 -->
                        <div class="color-variant-row" style="display:grid; grid-template-columns:180px 140px 1fr 40px; gap:10px; align-items:center; background:var(--bg-card); padding:10px 14px; border-radius:8px; border:none;">
                            <div>
                                <label style="font-size:11px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:2px;"><?php echo adm_t('admin_field_color_name', 'Color Name'); ?> 2</label>
                                <input type="text" name="prod_variant_name[]" value="Pure White" class="form-control" placeholder="e.g. Pure White" style="font-size:13px; padding:6px 10px;">
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:2px;"><?php echo adm_t('admin_field_color_swatch', 'Color Swatch'); ?></label>
                                <div style="display:flex; gap:6px; align-items:center;">
                                    <input type="color" value="#ffffff" style="width:36px; height:34px; padding:0; border:1px solid var(--border-color); border-radius:4px; cursor:pointer;" onchange="this.nextElementSibling.value = this.value;">
                                    <input type="text" name="prod_variant_hex[]" value="#ffffff" class="form-control" style="font-size:12px; padding:6px 6px; font-family:monospace;" onchange="this.previousElementSibling.value = this.value;">
                                </div>
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:2px;"><?php echo adm_t('admin_field_color_image', 'Color Photo URL'); ?></label>
                                <input type="url" name="prod_variant_image[]" class="form-control" placeholder="https://image-for-white-version.jpg" style="font-size:13px; padding:6px 10px;">
                            </div>
                            <div style="padding-top:16px; text-align:center;">
                                <button type="button" class="btn btn-sm btn-outline" style="color:var(--text-muted); padding:6px 8px;" onclick="removeColorVariantRow(this)" title="<?php echo adm_t('admin_btn_remove_color', 'Remove color'); ?>">✕</button>
                            </div>
                        </div>
                    </div>

                    <!-- Button to Add More Colors -->
                    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:18px;">
                        <button type="button" class="btn btn-sm btn-primary" onclick="addColorVariantRow('addColorVariantsList')" style="display:inline-flex; align-items:center; gap:6px; font-weight:700; padding:8px 16px;">
                            <span>➕</span> <?php echo adm_t('admin_btn_add_color', 'Add Another Color'); ?>
                        </button>
                    </div>

                    <!-- Optional: Cross-Product Model Group Linking -->
                    <details style="background:var(--bg-surface); padding:12px 16px; border-radius:8px; border:none;">
                        <summary style="font-weight:700; font-size:13px; color:var(--accent-gold); cursor:pointer; user-select:none;">
                            ⚙️ <?php echo adm_t('admin_products_model_grouping', 'Advanced: Link Across Separate Catalog Items (Model Grouping)'); ?>
                        </summary>
                        <div style="margin-top:12px;">
                            <div class="form-row-2 mb-10">
                                <div class="form-group">
                                    <label style="font-size:12px; font-weight:600;"><?php echo adm_t('admin_products_model_group_id', 'Shared Model Group Identifier'); ?></label>
                                    <input type="text" name="prod_model_group" id="prodModelGroup" class="form-control" placeholder="e.g. oxford-shirt-2026">
                                </div>
                                <div class="form-group">
                                    <label style="font-size:12px; font-weight:600;"><?php echo adm_t('admin_products_model_color_label', 'Primary Color Label for this Item'); ?></label>
                                    <input type="text" name="prod_color_name" id="prodColorName" class="form-control" placeholder="e.g. Obsidian Black">
                                </div>
                            </div>
                        </div>
                    </details>
                </div>

                <div class="form-row-3 mb-20">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_desc_en', 'Description (English)'); ?></label>
                        <textarea name="prod_desc_en" rows="3" class="form-control" placeholder="Handcrafted with Italian cashmere velvet..."></textarea>
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_desc_ar', 'Description (Arabic / عربي)'); ?></label>
                        <textarea name="prod_desc_ar" rows="3" class="form-control" placeholder="مصنوع يدوياً من أفخر أنواع المخمل الإيطالي..."></textarea>
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_desc_ku', 'Description (Kurdish / کوردی بادینی)'); ?></label>
                        <textarea name="prod_desc_ku" rows="3" class="form-control" placeholder="ب دەستان هاتیە چێکرن ژ قوماشێ مخملی یێ ئیتالی..."></textarea>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px;">
                    <button type="button" class="btn btn-outline" onclick="toggleAddProductForm()"><?php echo adm_t('admin_btn_cancel', 'Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary btn-luxury"><?php echo adm_t('admin_btn_save', 'Save Changes'); ?></button>
                </div>
            </form>
        </div>

        <!-- Products Table Card -->
        <div class="admin-table-card">
            <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:12px;">
                <div>
                    <h3 class="admin-card-title" style="margin:0; font-size:18px;">💎 <?php echo adm_t('admin_products_title', 'Products & Inventory Atelier'); ?></h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_workspace_products_desc', 'Catalog management, multi-color variations, luxury badges, and stock level controls.'); ?></p>
                </div>
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <button type="button" class="btn btn-primary btn-luxury btn-sm" onclick="toggleAddProductForm()">
                        <?php echo adm_t('admin_add_product_title', '+ Add New Product'); ?>
                    </button>
                    <input type="text" id="prodSearchInput" onkeyup="filterProductsTable()" placeholder="<?php echo adm_t('admin_search_products', 'Search pieces...'); ?>" class="form-control" style="max-width:200px; padding:8px 12px; font-size:13px;">
                    <select id="prodCategoryFilter" onchange="filterProductsTable()" class="form-control" style="max-width:160px; padding:8px 12px; font-size:13px;">
                        <option value=""><?php echo adm_t('admin_filter_all_cats', 'All Categories'); ?></option>
                        <option value="clothes"><?php echo adm_t('cat_clothes', 'Clothes'); ?></option>
                        <option value="watches"><?php echo adm_t('cat_watches', 'Watches'); ?></option>
                        <option value="perfumes"><?php echo adm_t('cat_perfumes', 'Perfumes'); ?></option>
                        <option value="accessories"><?php echo adm_t('cat_accessories', 'Accessories'); ?></option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="admin-table" id="productsTableMain">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo adm_t('admin_product_col_piece', 'Luxury Piece'); ?></th>
                            <th><?php echo adm_t('admin_product_col_cat', 'Category'); ?></th>
                            <th><?php echo adm_t('admin_product_col_price', 'Price (IQD)'); ?></th>
                            <th><?php echo adm_t('admin_product_col_stock', 'Stock / Status'); ?></th>
                            <th><?php echo adm_t('admin_product_col_badges', 'Badges'); ?></th>
                            <th><?php echo adm_t('admin_order_col_actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productsList as $p): 
                            $pTitle = is_array($p['title']) ? ($p['title'][$adminLang ?? 'en'] ?? $p['title']['en'] ?? reset($p['title'])) : $p['title'];
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
                                                <?php if (!empty($p['badge'])): ?>
                                                    <small class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); border-color:var(--accent-gold); font-weight:700;"><?php echo htmlspecialchars($p['badge']); ?></small>
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
                                        <button type="button" class="btn-stock-stepper" onclick="window.AuraStore.adjustStock(<?php echo $p['id']; ?>, -1)" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border:none; background:var(--bg-surface); color:var(--text-primary); border-radius:4px; font-weight:800; cursor:pointer;" title="<?php echo adm_t('admin_stock_decrease', 'Decrease Stock'); ?>">-</button>
                                        <span class="stock-value-badge font-bold" id="stockBadge_<?php echo $p['id']; ?>" style="min-width:28px; text-align:center; font-size:13px;"><?php echo $pStock; ?></span>
                                        <button type="button" class="btn-stock-stepper" onclick="window.AuraStore.adjustStock(<?php echo $p['id']; ?>, 1)" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border:none; background:var(--bg-surface); color:var(--text-primary); border-radius:4px; font-weight:800; cursor:pointer;" title="<?php echo adm_t('admin_stock_increase', 'Increase Stock'); ?>">+</button>
                                    </div>
                                </td>
                                <td>
                                    <span style="color:#d97706; font-size:13px;">★ <?php echo $p['rating'] ?? '4.9'; ?></span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:nowrap;">
                                        <button type="button" class="btn btn-outline btn-xs" onclick='openEditProductModal(<?php echo htmlspecialchars($safeJson, ENT_QUOTES, 'UTF-8'); ?>)' title="<?php echo adm_t('admin_products_edit_title', 'Edit Product Details & Colors'); ?>">
                                            ✏️ <?php echo adm_t('admin_btn_edit', 'Edit'); ?>
                                        </button>
                                        <a href="/product.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn btn-ghost btn-xs" title="<?php echo adm_t('admin_products_view_boutique', 'View in Boutique'); ?>">👁️</a>
                                        <form action="/admin/products.php" method="POST" onsubmit="return confirm('<?php echo adm_t('admin_products_delete_confirm', 'Delete this product permanently?'); ?>')" style="display:inline;">
                                            <input type="hidden" name="delete_product_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn btn-ghost text-danger btn-xs" title="<?php echo adm_t('admin_btn_delete', 'Delete Product'); ?>">✕</button>
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
                    <h3 style="margin:0; font-size:20px; font-weight:800;">✏️ <?php echo adm_t('admin_modal_edit_product', 'Edit Luxury Piece'); ?></h3>
                    <span class="badge-tag" id="editProductModalIdBadge" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:800;">#0</span>
                </div>
                <small class="text-muted" id="editProductModalSub"><?php echo adm_t('admin_products_subtitle', 'Trilingual titles, pricing, colors and images'); ?></small>
            </div>
            <button type="button" class="btn-close-modal" onclick="closeEditProductModal()" style="font-size:20px; cursor:pointer;">✕</button>
        </div>

        <form action="/admin/products.php" method="POST" id="editProductForm">
            <input type="hidden" name="update_product" value="1">
            <input type="hidden" name="edit_prod_id" id="editProdId">

            <!-- Section 1: Core Pricing & Category -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:none; margin-bottom:20px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">💰 <?php echo adm_t('admin_field_price', 'Pricing'); ?> & <?php echo adm_t('admin_field_category', 'Category'); ?></span>
                <div class="form-row-3">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_category', 'Category'); ?> <span class="text-danger">*</span></label>
                        <select name="edit_prod_category" id="editProdCategory" class="form-control">
                            <option value="clothes"><?php echo adm_t('cat_clothes', 'Clothes & Apparel'); ?></option>
                            <option value="watches"><?php echo adm_t('cat_watches', 'Luxury Watches'); ?></option>
                            <option value="perfumes"><?php echo adm_t('cat_perfumes', 'Arabian Oud & Perfumes'); ?></option>
                            <option value="accessories"><?php echo adm_t('cat_accessories', 'Leather & Accessories'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_price', 'Selling Price (IQD)'); ?> <span class="text-danger">*</span></label>
                        <input type="number" name="edit_prod_price" id="editProdPrice" required class="form-control" oninput="calculateDiscountPreview()">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_old_price', 'Original / Old Price (IQD)'); ?></label>
                        <input type="number" name="edit_prod_old_price" id="editProdOldPrice" class="form-control" oninput="calculateDiscountPreview()">
                        <span id="editDiscountBadge" class="badge-tag" style="display:none; margin-top:4px; background:rgba(34,197,94,0.15); color:#22c55e; border-color:#22c55e; font-weight:700;"></span>
                    </div>
                </div>

                <div class="form-row-3" style="margin-top:12px;">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_stock', 'Stock Count'); ?></label>
                        <input type="number" name="edit_prod_stock" id="editProdStock" class="form-control" value="10">
                    </div>
                    <div class="form-group" style="display:flex; align-items:center; gap:10px; margin-top:24px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13.5px;">
                            <input type="checkbox" name="edit_prod_featured" id="editProdFeatured" value="1" style="width:18px; height:18px; accent-color:var(--accent-gold);">
                            <span>⭐ <?php echo adm_t('admin_products_featured_showcase_check', 'Featured on Homepage Showcase'); ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Section 2: Badges & Promotion Tags -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:none; margin-bottom:20px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">🏷️ <?php echo adm_t('admin_product_col_badges', 'Promotional Badges'); ?></span>

                <div class="form-row-3">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_badge_en', 'Badge (English)'); ?></label>
                        <input type="text" name="edit_prod_badge" id="editProdBadge" class="form-control" placeholder="e.g. Best Seller">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_badge_ar', 'Badge (Arabic / عربي)'); ?></label>
                        <input type="text" name="edit_prod_badge_ar" id="editProdBadgeAr" class="form-control" placeholder="مثال: الأكثر مبيعاً">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_badge_ku', 'Badge (Kurdish / کوردی)'); ?></label>
                        <input type="text" name="edit_prod_badge_ku" id="editProdBadgeKu" class="form-control" placeholder="وەکی: پڕفرۆشترین">
                    </div>
                </div>
            </div>

            <!-- Section 3: Image & Gallery -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:none; margin-bottom:20px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">🖼️ <?php echo adm_t('admin_field_main_image', 'Product Imagery & Gallery'); ?></span>
                
                <div style="display:grid; grid-template-columns:100px 1fr; gap:16px; align-items:start; margin-bottom:14px;">
                    <div style="text-align:center;">
                        <img id="editImageLivePreview" src="https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80" alt="Preview" style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:none;">
                        <small class="text-muted" style="display:block; font-size:10.5px; margin-top:4px;"><?php echo adm_t('admin_products_main_preview', 'Main Preview'); ?></small>
                    </div>

                    <div>
                        <div class="form-group mb-12">
                            <label><?php echo adm_t('admin_field_main_image', 'Primary Cover Image URL'); ?> <span class="text-danger">*</span></label>
                            <input type="url" name="edit_prod_image" id="editProdImage" required class="form-control" oninput="updateEditImagePreview()">
                        </div>

                        <div class="form-group">
                            <label><?php echo adm_t('admin_field_gallery_images', 'Additional Gallery Images (Comma-Separated URLs)'); ?></label>
                            <textarea name="edit_prod_gallery" id="editProdGallery" rows="2" class="form-control" placeholder="https://image1.jpg, https://image2.jpg, https://image3.jpg"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Trilingual Titles -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:none; margin-bottom:20px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">🌐 <?php echo adm_t('admin_product_col_piece', 'Trilingual Titles'); ?></span>
                <div class="form-row-3">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_title_en', 'Title (English)'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="edit_prod_title_en" id="editProdTitleEn" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_title_ar', 'Title (Arabic / عربي)'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="edit_prod_title_ar" id="editProdTitleAr" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_title_ku', 'Title (Kurdish / کوردی)'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="edit_prod_title_ku" id="editProdTitleKu" required class="form-control">
                    </div>
                </div>
            </div>

            <!-- Section 4.5: Color Variations & Multi-Color Builder -->
            <div id="editColorVariantsSection" style="background:var(--bg-subtle); padding:18px; border-radius:var(--radius-sm); border:none; margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <div>
                        <span style="font-weight:700; font-size:14px; color:var(--text-primary); text-transform:uppercase; letter-spacing:0.5px; display:block;">
                            🎨 <?php echo adm_t('admin_field_colors_section', 'Product Colors & Swatches'); ?>
                        </span>
                        <small class="text-muted"><?php echo adm_t('admin_field_colors_section_desc', 'Manage all colors for this piece with swatches and photos.'); ?></small>
                    </div>
                    <span class="badge-tag" style="background:var(--bg-card); border:none; color:var(--text-primary); font-weight:700;"><?php echo adm_t('admin_product_col_colors', 'Colors / Swatches'); ?></span>
                </div>

                <!-- Dynamic Container for Edit Modal Colors -->
                <div id="editColorVariantsList" style="display:flex; flex-direction:column; gap:8px; margin-bottom:12px;">
                    <!-- Populated dynamically via JS openEditProductModal -->
                </div>

                <!-- Add Another Color Button in Edit Modal -->
                <div style="margin-bottom:16px;">
                    <button type="button" class="btn btn-sm btn-primary" onclick="addColorVariantRow('editColorVariantsList')" style="display:inline-flex; align-items:center; gap:6px; font-weight:700; padding:6px 14px; font-size:12px;">
                        <span>➕</span> <?php echo adm_t('admin_btn_add_color', 'Add Another Color'); ?>
                    </button>
                </div>
            </div>

            <!-- Section 5: Sizes & Descriptions -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:none; margin-bottom:24px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">📝 <?php echo adm_t('admin_field_desc_en', 'Descriptions'); ?></span>

                <div class="form-row-3">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_desc_en', 'Description (English)'); ?></label>
                        <textarea name="edit_prod_desc_en" id="editProdDescEn" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_desc_ar', 'Description (Arabic / عربي)'); ?></label>
                        <textarea name="edit_prod_desc_ar" id="editProdDescAr" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_desc_ku', 'Description (Kurdish / کوردی)'); ?></label>
                        <textarea name="edit_prod_desc_ku" id="editProdDescKu" rows="3" class="form-control"></textarea>
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; padding-top:10px;">
                <button type="button" class="btn btn-outline" onclick="closeEditProductModal()"><?php echo adm_t('admin_btn_cancel', 'Cancel'); ?></button>
                <button type="submit" class="btn btn-primary btn-luxury" style="padding:10px 24px;"><?php echo adm_t('admin_btn_save', 'Save & Apply Changes'); ?></button>
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

// Multi-Color Dynamic Row Engine
function addColorVariantRow(containerId, colorName = '', colorHex = '#1e3a8a', imageUrl = '', isRemovable = true) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const isAdd = containerId.startsWith('add');
    const prefix = isAdd ? 'prod' : 'edit';
    const rowCount = container.querySelectorAll('.color-variant-row').length + 1;

    const row = document.createElement('div');
    row.className = 'color-variant-row';
    row.style.cssText = 'display:grid; grid-template-columns:180px 140px 1fr 40px; gap:10px; align-items:center; background:var(--bg-card); padding:10px 14px; border-radius:8px; border:none;';

    const lblName = "<?php echo adm_t('admin_field_color_name', 'Color Name'); ?>";
    const lblSwatch = "<?php echo adm_t('admin_field_color_swatch', 'Color Swatch'); ?>";
    const lblPhoto = "<?php echo adm_t('admin_field_color_image', 'Color Photo URL'); ?>";
    const lblRemove = "<?php echo adm_t('admin_btn_remove_color', 'Remove this color'); ?>";

    row.innerHTML = `
        <div>
            <label style="font-size:11px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:2px;">${lblName} ${rowCount}</label>
            <input type="text" name="${prefix}_variant_name[]" value="${escapeHtmlAttr(colorName)}" class="form-control" placeholder="e.g. Royal Navy" required style="font-size:13px; padding:6px 10px;">
        </div>
        <div>
            <label style="font-size:11px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:2px;">${lblSwatch}</label>
            <div style="display:flex; gap:6px; align-items:center;">
                <input type="color" value="${escapeHtmlAttr(colorHex)}" style="width:36px; height:34px; padding:0; border:1px solid var(--border-color); border-radius:4px; cursor:pointer;" onchange="this.nextElementSibling.value = this.value;">
                <input type="text" name="${prefix}_variant_hex[]" value="${escapeHtmlAttr(colorHex)}" class="form-control" style="font-size:12px; padding:6px 6px; font-family:monospace;" onchange="this.previousElementSibling.value = this.value;">
            </div>
        </div>
        <div>
            <label style="font-size:11px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:2px;">${lblPhoto}</label>
            <input type="url" name="${prefix}_variant_image[]" value="${escapeHtmlAttr(imageUrl)}" class="form-control" placeholder="https://image-for-this-color.jpg" style="font-size:13px; padding:6px 10px;">
        </div>
        <div style="padding-top:16px; text-align:center;">
            <button type="button" class="btn btn-sm btn-outline" style="color:var(--text-muted); padding:6px 8px;" onclick="removeColorVariantRow(this)" title="${lblRemove}">✕</button>
        </div>
    `;

    container.appendChild(row);
    const nameInput = row.querySelector('input[type="text"]');
    if (nameInput && !colorName) nameInput.focus();
}

function removeColorVariantRow(btn) {
    const row = btn.closest('.color-variant-row');
    if (!row) return;
    const container = row.parentElement;
    if (container && container.querySelectorAll('.color-variant-row').length <= 1) {
        if (window.AdminApp && window.AdminApp.toast) {
            window.AdminApp.toast('<?php echo adm_t('admin_color_must_have_one', 'A piece must have at least one color option.'); ?>', 'warning');
        }
        return;
    }
    row.remove();
}

function escapeHtmlAttr(str) {
    if (!str) return '';
    return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
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

    // Populate Multi-Color Variants in Edit Modal
    const editColorsList = document.getElementById('editColorVariantsList');
    if (editColorsList) {
        editColorsList.innerHTML = '';
        const prodColors = Array.isArray(product.colors) ? product.colors : (product.colors ? [product.colors] : []);
        const colorHexes = (typeof product.color_hexes === 'object' && product.color_hexes !== null) ? product.color_hexes : {};
        const colorImages = (typeof product.color_images === 'object' && product.color_images !== null) ? product.color_images : {};

        if (prodColors.length > 0) {
            prodColors.forEach((colName, idx) => {
                const hex = colorHexes[colName] || product.color_hex || '#d4af37';
                const img = colorImages[colName] || (product.images && product.images[idx]) || (idx === 0 ? mainImg : '');
                addColorVariantRow('editColorVariantsList', colName, hex, img);
            });
        } else {
            const defName = product.color_name || 'Obsidian Black';
            const defHex = product.color_hex || '#111827';
            addColorVariantRow('editColorVariantsList', defName, defHex, mainImg);
        }
    }

    // Color Variations & Model Grouping
    document.getElementById('editProdModelGroup').value = product.model_group || '';
    document.getElementById('editProdColorName').value = product.color_name || '';

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
