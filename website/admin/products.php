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
        // Process Sizes & Measurements for Edit (Multi-Category Adaptive Dimensions)
        $sizes = [];
        $sizeMeasurements = [];
        if (!empty($_POST['edit_prod_sizes']) && is_array($_POST['edit_prod_sizes'])) {
            foreach ($_POST['edit_prod_sizes'] as $sz) {
                $sz = trim($sz);
                if ($sz !== '') {
                    $sizes[] = $sz;
                    $dim1Val = trim($_POST['edit_prod_size_dim1'][$sz] ?? $_POST['edit_prod_size_height'][$sz] ?? '');
                    $dim2Val = trim($_POST['edit_prod_size_dim2'][$sz] ?? $_POST['edit_prod_size_width'][$sz] ?? '');
                    $dim1Label = trim($_POST['edit_prod_size_dim1_label'][$sz] ?? 'Height');
                    $dim2Label = trim($_POST['edit_prod_size_dim2_label'][$sz] ?? 'Width');
                    $dim1Unit = trim($_POST['edit_prod_size_dim1_unit'][$sz] ?? 'cm');
                    $dim2Unit = trim($_POST['edit_prod_size_dim2_unit'][$sz] ?? 'cm');
                    
                    if ($dim1Val !== '' || $dim2Val !== '') {
                        $dim1Clean = preg_replace('/[^0-9.]/', '', $dim1Val);
                        $dim2Clean = preg_replace('/[^0-9.]/', '', $dim2Val);
                        if ($dim1Clean === '') $dim1Clean = '70';
                        if ($dim2Clean === '') $dim2Clean = '50';
                        $sizeMeasurements[$sz] = "{$dim1Label}: {$dim1Clean}{$dim1Unit} • {$dim2Label}: {$dim2Clean}{$dim2Unit}";
                    }
                }
            }
        } elseif (!empty($_POST['edit_prod_sizes_raw'])) {
            $sizes = array_values(array_filter(array_map('trim', explode(',', $_POST['edit_prod_sizes_raw']))));
        }

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
            'size_measurements' => $sizeMeasurements,
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

        try {
            save_product($productData);
            $flashMsg = "✓ Product #{$pId} ('" . htmlspecialchars($titleEn) . "') was updated successfully!";
            $flashType = 'success';
        } catch (Exception $e) {
            $flashMsg = "⚠ Error updating product: " . htmlspecialchars($e->getMessage());
            $flashType = 'danger';
        }
    }
    // 2. ADD NEW PRODUCT
    elseif (isset($_POST['add_new_product'])) {
        $titleEn = trim($_POST['prod_title_en'] ?? '');
        $titleAr = trim($_POST['prod_title_ar'] ?? '');
        $titleKu = trim($_POST['prod_title_ku'] ?? '');
        if (empty($titleEn)) $titleEn = !empty($titleAr) ? $titleAr : (!empty($titleKu) ? $titleKu : 'New Luxury Item');
        if (empty($titleAr)) $titleAr = $titleEn;
        if (empty($titleKu)) $titleKu = $titleEn;
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

        // Process Sizes & Measurements for Add (Multi-Category Adaptive Dimensions)
        $sizes = [];
        $sizeMeasurements = [];
        if (!empty($_POST['prod_sizes']) && is_array($_POST['prod_sizes'])) {
            foreach ($_POST['prod_sizes'] as $sz) {
                $sz = trim($sz);
                if ($sz !== '') {
                    $sizes[] = $sz;
                    $dim1Val = trim($_POST['prod_size_dim1'][$sz] ?? $_POST['prod_size_height'][$sz] ?? '');
                    $dim2Val = trim($_POST['prod_size_dim2'][$sz] ?? $_POST['prod_size_width'][$sz] ?? '');
                    $dim1Label = trim($_POST['prod_size_dim1_label'][$sz] ?? 'Height');
                    $dim2Label = trim($_POST['prod_size_dim2_label'][$sz] ?? 'Width');
                    $dim1Unit = trim($_POST['prod_size_dim1_unit'][$sz] ?? 'cm');
                    $dim2Unit = trim($_POST['prod_size_dim2_unit'][$sz] ?? 'cm');
                    
                    if ($dim1Val !== '' || $dim2Val !== '') {
                        $dim1Clean = preg_replace('/[^0-9.]/', '', $dim1Val);
                        $dim2Clean = preg_replace('/[^0-9.]/', '', $dim2Val);
                        if ($dim1Clean === '') $dim1Clean = '70';
                        if ($dim2Clean === '') $dim2Clean = '50';
                        $sizeMeasurements[$sz] = "{$dim1Label}: {$dim1Clean}{$dim1Unit} • {$dim2Label}: {$dim2Clean}{$dim2Unit}";
                    }
                }
            }
        } elseif (!empty($_POST['prod_sizes_raw'])) {
            $sizes = array_values(array_filter(array_map('trim', explode(',', $_POST['prod_sizes_raw']))));
        }
        if (empty($sizes)) {
            $sizes = ['S', 'M', 'L', 'XL'];
        }

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
            'sizes' => !empty($sizes) ? $sizes : ['S', 'M', 'L', 'XL'],
            'size_measurements' => $sizeMeasurements,
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

        try {
            $saved = save_product($productData);
            $flashMsg = "✓ New luxury piece #{$saved['id']} ('" . htmlspecialchars($titleEn) . "') was successfully published to the catalog!";
            $flashType = 'success';
        } catch (Exception $e) {
            $flashMsg = "⚠ Error publishing piece: " . htmlspecialchars($e->getMessage());
            $flashType = 'danger';
        }
    }
    // 3. DELETE PRODUCT
    elseif (isset($_POST['delete_product_id'])) {
        $pId = intval($_POST['delete_product_id']);
        delete_product($pId);
        $flashMsg = "✓ Product #{$pId} has been permanently removed from the catalog.";
        $flashType = 'success';
    }
}

$pageTitle = 'Product Catalog & Inventory | AURA Luxury Admin';
$adminActive = 'products';
$productsList = get_all_products();

// Sort by ID descending so newly added pieces appear immediately at the top of the table
usort($productsList, function($a, $b) {
    return (int)($b['id'] ?? 0) - (int)($a['id'] ?? 0);
});
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

<section class="admin-section" style="padding: 24px 0 60px;">
    <div class="container">

        <!-- Unified Admin Navigation Bar -->
        <?php require_once __DIR__ . '/nav.php'; ?>

        <?php if ($flashMsg): ?>
            <?php $isDanger = ($flashType === 'danger'); ?>
            <div style="background:<?php echo $isDanger ? 'rgba(239,68,68,0.14)' : 'rgba(34,197,94,0.14)'; ?>; border:1px solid <?php echo $isDanger ? '#ef4444' : '#22c55e'; ?>; color:<?php echo $isDanger ? '#ef4444' : '#22c55e'; ?>; border-radius:8px; padding:15px 20px; margin-bottom:24px; font-weight:700; display:flex; align-items:center; justify-content:space-between; box-shadow:0 4px 12px rgba(0,0,0,0.06);">
                <span style="font-size:14px;"><?php echo $flashMsg; ?></span>
                <button type="button" onclick="this.parentElement.style.display='none'" style="background:none; border:none; color:inherit; cursor:pointer; font-size:18px;">✕</button>
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

        <!-- Add Product Panel (Hidden by default, toggled via button) -->
        <div class="admin-form-card mb-24" id="addProductCard" style="display:none; border:1px solid var(--border-color); background:var(--bg-card); border-radius:var(--radius-md); padding:24px; box-shadow:var(--shadow-sm); margin-bottom:24px;">
            <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div>
                    <h3 class="admin-card-title" style="margin:0; font-size:18px;"><?php echo adm_t('admin_add_product_title', '+ Add New Luxury Piece or Color Variant'); ?></h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_add_product_subtitle', 'Define multilingual titles, category, high-resolution imagery, and color swatches.'); ?></p>
                </div>
            </div>

            <form action="" method="POST" id="newProductForm">
                <input type="hidden" name="add_new_product" value="1">
                
                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_title_en', 'Product Title (English)'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="prod_title_en" required class="form-control" placeholder="e.g. Royal Midnight Velvet Blazer">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_title_ar', 'Product Title (Arabic / عربي)'); ?></label>
                        <input type="text" name="prod_title_ar" class="form-control" placeholder="مثال: بليزر ملكي كحلي مخملي (اختياري)">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_field_title_ku', 'Product Title (Kurdish / کوردی بادینی)'); ?></label>
                        <input type="text" name="prod_title_ku" class="form-control" placeholder="وەکی: قاتی مخملی یێ شاهانە (بژارده)">
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

                <!-- Primary Image Uploader (x02.me API + WebP Compression) -->
                <div class="form-group mb-16">
                    <div id="addMainImageUploaderBox"></div>
                </div>

                <!-- Additional Gallery Images Multi-Uploader (x02.me API + WebP Compression) -->
                <div class="form-group mb-16">
                    <textarea name="prod_gallery" id="addProdGalleryTextarea" rows="2" style="display:none;"></textarea>
                    <div id="addGalleryUploaderBox"></div>
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
                                <div style="display:flex; gap:6px; align-items:center;">
                                    <input type="text" name="prod_variant_image[]" class="form-control" placeholder="https://image-for-black-version.jpg" style="font-size:13px; padding:6px 10px; flex:1;">
                                    <button type="button" class="btn btn-sm btn-outline" style="padding:6px 9px; font-size:11.5px; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;" onclick="window.X02Uploader.uploadVariantPhoto(this)" title="Upload, compress to WebP & host on x02.me">
                                        <span>☁️</span> Upload
                                    </button>
                                </div>
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
                                <div style="display:flex; gap:6px; align-items:center;">
                                    <input type="text" name="prod_variant_image[]" class="form-control" placeholder="https://image-for-white-version.jpg" style="font-size:13px; padding:6px 10px; flex:1;">
                                    <button type="button" class="btn btn-sm btn-outline" style="padding:6px 9px; font-size:11.5px; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;" onclick="window.X02Uploader.uploadVariantPhoto(this)" title="Upload, compress to WebP & host on x02.me">
                                        <span>☁️</span> Upload
                                    </button>
                                </div>
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

                <!-- Section 4.5: Sizes & Dimensions Engine (Multi-Category Adaptive) -->
                <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:none; margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
                        <div>
                            <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block;">
                                📏 <?php echo adm_t('admin_field_sizes_measurements', 'Available Sizes & Dimension Measurements'); ?>
                            </span>
                            <span style="font-size:12px; color:var(--text-muted);">
                                <?php echo adm_t('admin_sizes_help_multi', 'Select garment type (Shirts, Jeans, Jackets, Shoes, Watches) to load tailored sizes & custom measurement dimensions.'); ?>
                            </span>
                        </div>
                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <span id="addActiveSizesCount" style="font-size:12px; font-weight:700; color:var(--accent-gold); background:rgba(212,175,55,0.1); padding:4px 10px; border-radius:12px;">
                                4 sizes selected
                            </span>
                            <div id="addQuickPresetsContainer" style="display:flex; gap:6px;">
                                <!-- Populated dynamically by preset -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline" onclick="applySizePreset('add', 'clear')" style="padding:4px 10px; font-size:11.5px; color:var(--text-muted);">Clear</button>
                        </div>
                    </div>

                    <!-- Category Preset Tabs (Shirts, Jeans, Jackets, Shoes, Watches) -->
                    <div style="margin-bottom:14px; background:var(--bg-card); padding:8px 10px; border-radius:8px; display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                        <span style="font-size:11.5px; font-weight:700; color:var(--text-secondary); margin-inline-end:6px;">
                            Apparel / Item Blueprint:
                        </span>
                        <button type="button" class="btn btn-sm btn-outline size-cat-tab-add" data-preset="shirts" onclick="switchCategoryPreset('add', 'shirts')" style="padding:5px 11px; font-size:12px; font-weight:700;">
                            👕 Shirts & Tops
                        </button>
                        <button type="button" class="btn btn-sm btn-outline size-cat-tab-add" data-preset="jeans" onclick="switchCategoryPreset('add', 'jeans')" style="padding:5px 11px; font-size:12px; font-weight:700;">
                            👖 Jeans & Pants
                        </button>
                        <button type="button" class="btn btn-sm btn-outline size-cat-tab-add" data-preset="jackets" onclick="switchCategoryPreset('add', 'jackets')" style="padding:5px 11px; font-size:12px; font-weight:700;">
                            🧥 Jackets & Coats
                        </button>
                        <button type="button" class="btn btn-sm btn-outline size-cat-tab-add" data-preset="shoes" onclick="switchCategoryPreset('add', 'shoes')" style="padding:5px 11px; font-size:12px; font-weight:700;">
                            👟 Shoes & Footwear
                        </button>
                        <button type="button" class="btn btn-sm btn-outline size-cat-tab-add" data-preset="watches" onclick="switchCategoryPreset('add', 'watches')" style="padding:5px 11px; font-size:12px; font-weight:700;">
                            ⌚ Watches & Straps
                        </button>
                    </div>

                    <!-- Size Pill Buttons (Click to toggle) -->
                    <div style="margin-bottom:14px;">
                        <label style="font-size:11.5px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:6px;">
                            Click to toggle available sizes:
                        </label>
                        <div id="addSizePillsContainer" style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                            <!-- Populated dynamically via JavaScript -->
                        </div>
                    </div>

                    <!-- Add Custom Size Form -->
                    <div style="display:flex; gap:8px; align-items:center; margin-bottom:16px; max-width:320px;">
                        <input type="text" id="addCustomSizeInput" class="form-control" placeholder="e.g. 36W or 48 EU or 44MM" style="font-size:12px; padding:6px 10px;" onkeydown="if(event.key==='Enter'){event.preventDefault(); addCustomSize('add');}">
                        <button type="button" class="btn btn-sm btn-outline" onclick="addCustomSize('add')" style="padding:6px 12px; font-size:12px; white-space:nowrap; font-weight:700;">
                            + Add Size
                        </button>
                    </div>

                    <!-- Active Size Measurement Cards Grid (Height & Width / Waist & Inseam / Foot Length for each) -->
                    <div style="border-top:1px dashed var(--border-color); padding-top:14px;">
                        <label style="font-size:12px; font-weight:700; color:var(--text-primary); display:block; margin-bottom:10px;">
                            📐 Size Dimension Measurements (<span id="addActiveBlueprintLabel">Shirts & Tops</span>):
                        </label>
                        <div id="addSizesMeasurementsList" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:12px;">
                            <!-- Dynamic Measurement inputs for each active size -->
                        </div>
                    </div>
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
                    <button type="submit" name="add_new_product" value="1" class="btn btn-primary btn-luxury" id="newProdSubmitBtn" style="padding:10px 24px; font-weight:700;">💎 <?php echo adm_t('admin_btn_publish_piece', 'Publish Luxury Piece to Catalog'); ?></button>
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
                                    <div style="display:flex; flex-direction:column; align-items:flex-start; gap:3px;">
                                        <div class="stock-adjuster" id="stockAdjuster_<?php echo $p['id']; ?>" style="display:inline-flex; align-items:center; gap:6px; background:var(--bg-subtle); padding:4px 8px; border-radius:6px; border:1px solid var(--border-color);">
                                            <button type="button" class="btn-stock-stepper" onclick="window.AuraStore.adjustStock(<?php echo $p['id']; ?>, -1)" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border:none; background:var(--bg-surface); color:var(--text-primary); border-radius:4px; font-weight:800; cursor:pointer;" title="<?php echo adm_t('admin_stock_decrease', 'Decrease Stock'); ?>">-</button>
                                            <span class="stock-value-badge font-bold" id="stockBadge_<?php echo $p['id']; ?>" style="min-width:28px; text-align:center; font-size:13px; <?php echo ($pStock <= 0) ? 'color:#ef4444;' : (($pStock <= 3) ? 'color:#f59e0b;' : 'color:var(--text-primary);'); ?>"><?php echo $pStock; ?></span>
                                            <button type="button" class="btn-stock-stepper" onclick="window.AuraStore.adjustStock(<?php echo $p['id']; ?>, 1)" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border:none; background:var(--bg-surface); color:var(--text-primary); border-radius:4px; font-weight:800; cursor:pointer;" title="<?php echo adm_t('admin_stock_increase', 'Increase Stock'); ?>">+</button>
                                        </div>
                                        <?php if ($pStock <= 0): ?>
                                            <span class="badge-tag" id="stockStatusText_<?php echo $p['id']; ?>" style="background:rgba(239,68,68,0.12); color:#ef4444; border:1px solid rgba(239,68,68,0.3); font-size:10px; padding:1px 6px; font-weight:700;">Out of Stock</span>
                                        <?php elseif ($pStock <= 3): ?>
                                            <span class="badge-tag" id="stockStatusText_<?php echo $p['id']; ?>" style="background:rgba(245,158,11,0.12); color:#f59e0b; border:1px solid rgba(245,158,11,0.3); font-size:10px; padding:1px 6px; font-weight:700;">Low Stock (<?php echo $pStock; ?>)</span>
                                        <?php else: ?>
                                            <span class="badge-tag" id="stockStatusText_<?php echo $p['id']; ?>" style="background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.3); font-size:10px; padding:1px 6px; font-weight:700;">In Stock</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="color:#d97706; font-size:13px;">★ <?php echo $p['rating'] ?? '4.9'; ?></span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:nowrap;">
                                        <button type="button" class="btn btn-outline btn-xs" data-id="<?php echo (int)$p['id']; ?>" data-product='<?php echo htmlspecialchars(json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>' onclick='openEditProductModalFromBtn(this)' title="<?php echo adm_t('admin_products_edit_title', 'Edit Product Details & Colors'); ?>">
                                            ✏️ <?php echo adm_t('admin_btn_edit', 'Edit'); ?>
                                        </button>
                                        <a href="/product.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn btn-ghost btn-xs" title="<?php echo adm_t('admin_products_view_boutique', 'View in Boutique'); ?>">👁️</a>
                                        <form action="" method="POST" onsubmit="return confirm('<?php echo adm_t('admin_products_delete_confirm', 'Delete this product permanently?'); ?>')" style="display:inline;">
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

        <form action="" method="POST" id="editProductForm">
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
                            <div id="editMainImageUploaderBox"></div>
                        </div>

                        <div class="form-group">
                            <textarea name="edit_prod_gallery" id="editProdGalleryTextarea" rows="2" style="display:none;"></textarea>
                            <div id="editGalleryUploaderBox"></div>
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

                <!-- Optional: Cross-Product Model Group Linking in Edit Modal -->
                <details style="background:var(--bg-surface); padding:12px 16px; border-radius:8px; border:none; margin-top:8px;">
                    <summary style="font-weight:700; font-size:13px; color:var(--accent-gold); cursor:pointer; user-select:none;">
                        ⚙️ <?php echo adm_t('admin_products_model_grouping', 'Advanced: Link Across Separate Catalog Items (Model Grouping)'); ?>
                    </summary>
                    <div style="margin-top:12px;">
                        <div class="form-row-2 mb-10">
                            <div class="form-group">
                                <label style="font-size:12px; font-weight:600;"><?php echo adm_t('admin_products_model_group_id', 'Shared Model Group Identifier'); ?></label>
                                <input type="text" name="edit_prod_model_group" id="editProdModelGroup" class="form-control" placeholder="e.g. oxford-shirt-2026">
                            </div>
                            <div class="form-group">
                                <label style="font-size:12px; font-weight:600;"><?php echo adm_t('admin_products_model_color_label', 'Primary Color Label for this Item'); ?></label>
                                <input type="text" name="edit_prod_color_name" id="editProdColorName" class="form-control" placeholder="e.g. Obsidian Black">
                            </div>
                        </div>
                        <?php if (!empty($products)): ?>
                        <div style="margin-top:10px;">
                            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:6px;"><?php echo adm_t('admin_products_link_other_items', 'Directly Linked Catalog Items'); ?>:</label>
                            <div style="max-height:140px; overflow-y:auto; display:flex; flex-direction:column; gap:6px; background:var(--bg-card); padding:8px; border-radius:6px;">
                                <?php foreach ($products as $otherP): ?>
                                    <label style="display:flex; align-items:center; gap:8px; font-size:12px; cursor:pointer; margin:0;">
                                        <input type="checkbox" name="edit_prod_linked_products[]" class="edit-linked-cb" value="<?php echo (int)$otherP['id']; ?>">
                                        <span>#<?php echo (int)$otherP['id']; ?> — <?php echo htmlspecialchars(is_array($otherP['title']) ? ($otherP['title']['en'] ?? '') : $otherP['title']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </details>
            </div>

            <!-- Section 4.8: Sizes & Dimensions Engine (Edit Modal - Multi-Category Adaptive) -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:none; margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
                    <div>
                        <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block;">
                            📏 <?php echo adm_t('admin_field_sizes_measurements', 'Available Sizes & Dimension Measurements'); ?>
                        </span>
                        <span style="font-size:12px; color:var(--text-muted);">
                            <?php echo adm_t('admin_sizes_help_multi', 'Select garment type (Shirts, Jeans, Jackets, Shoes, Watches) to load tailored sizes & custom measurement dimensions.'); ?>
                        </span>
                    </div>
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        <span id="editActiveSizesCount" style="font-size:12px; font-weight:700; color:var(--accent-gold); background:rgba(212,175,55,0.1); padding:4px 10px; border-radius:12px;">
                            4 sizes selected
                        </span>
                        <div id="editQuickPresetsContainer" style="display:flex; gap:6px;">
                            <!-- Populated dynamically by preset -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline" onclick="applySizePreset('edit', 'clear')" style="padding:4px 10px; font-size:11.5px; color:var(--text-muted);">Clear</button>
                    </div>
                </div>

                <!-- Category Preset Tabs (Shirts, Jeans, Jackets, Shoes, Watches) -->
                <div style="margin-bottom:14px; background:var(--bg-card); padding:8px 10px; border-radius:8px; display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                    <span style="font-size:11.5px; font-weight:700; color:var(--text-secondary); margin-inline-end:6px;">
                        Apparel / Item Blueprint:
                    </span>
                    <button type="button" class="btn btn-sm btn-outline size-cat-tab-edit" data-preset="shirts" onclick="switchCategoryPreset('edit', 'shirts')" style="padding:5px 11px; font-size:12px; font-weight:700;">
                        👕 Shirts & Tops
                    </button>
                    <button type="button" class="btn btn-sm btn-outline size-cat-tab-edit" data-preset="jeans" onclick="switchCategoryPreset('edit', 'jeans')" style="padding:5px 11px; font-size:12px; font-weight:700;">
                        👖 Jeans & Pants
                    </button>
                    <button type="button" class="btn btn-sm btn-outline size-cat-tab-edit" data-preset="jackets" onclick="switchCategoryPreset('edit', 'jackets')" style="padding:5px 11px; font-size:12px; font-weight:700;">
                        🧥 Jackets & Coats
                    </button>
                    <button type="button" class="btn btn-sm btn-outline size-cat-tab-edit" data-preset="shoes" onclick="switchCategoryPreset('edit', 'shoes')" style="padding:5px 11px; font-size:12px; font-weight:700;">
                        👟 Shoes & Footwear
                    </button>
                    <button type="button" class="btn btn-sm btn-outline size-cat-tab-edit" data-preset="watches" onclick="switchCategoryPreset('edit', 'watches')" style="padding:5px 11px; font-size:12px; font-weight:700;">
                        ⌚ Watches & Straps
                    </button>
                </div>

                <!-- Size Pill Buttons (Click to toggle) -->
                <div style="margin-bottom:14px;">
                    <label style="font-size:11.5px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:6px;">
                        Click to toggle available sizes:
                    </label>
                    <div id="editSizePillsContainer" style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                        <!-- Populated dynamically via JavaScript -->
                    </div>
                </div>

                <!-- Add Custom Size Form -->
                <div style="display:flex; gap:8px; align-items:center; margin-bottom:16px; max-width:320px;">
                    <input type="text" id="editCustomSizeInput" class="form-control" placeholder="e.g. 36W or 48 EU or 44MM" style="font-size:12px; padding:6px 10px;" onkeydown="if(event.key==='Enter'){event.preventDefault(); addCustomSize('edit');}">
                    <button type="button" class="btn btn-sm btn-outline" onclick="addCustomSize('edit')" style="padding:6px 12px; font-size:12px; white-space:nowrap; font-weight:700;">
                        + Add Size
                    </button>
                </div>

                <!-- Active Size Measurement Cards Grid (Height & Width / Waist & Inseam / Foot Length for each) -->
                <div style="border-top:1px dashed var(--border-color); padding-top:14px;">
                    <label style="font-size:12px; font-weight:700; color:var(--text-primary); display:block; margin-bottom:10px;">
                        📐 Size Dimension Measurements (<span id="editActiveBlueprintLabel">Shirts & Tops</span>):
                    </label>
                    <div id="editSizesMeasurementsList" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:12px;">
                        <!-- Dynamic Measurement inputs for each active size -->
                    </div>
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
window.X02_API_KEY = <?php echo json_encode(get_setting('x02_api_key', '36f36ce6fa844e93bda76bb9255070b4')); ?>;
window.X02_UPLOAD_URL = <?php echo json_encode(get_setting('x02_upload_url', 'https://up.x02.me/api/upload?format=json')); ?>;
window.ALL_PRODUCTS = <?php echo json_encode($products); ?>;
window.ALL_PRODUCTS_MAP = {};
if (Array.isArray(window.ALL_PRODUCTS)) {
    window.ALL_PRODUCTS.forEach(function(p) {
        if (p && p.id !== undefined) {
            window.ALL_PRODUCTS_MAP[p.id] = p;
        }
    });
}
</script>
<script src="/admin/x02_uploader.js"></script>
<script>
function toggleAddProductForm() {
    const card = document.getElementById('addProductCard');
    if (!card) return;
    const isHidden = card.style.display === 'none' || getComputedStyle(card).display === 'none';
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
            <div style="display:flex; gap:6px; align-items:center;">
                <input type="text" name="${prefix}_variant_image[]" value="${escapeHtmlAttr(imageUrl)}" class="form-control" placeholder="https://image-for-this-color.jpg" style="font-size:13px; padding:6px 10px; flex:1;">
                <button type="button" class="btn btn-sm btn-outline" style="padding:6px 9px; font-size:11.5px; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;" onclick="window.X02Uploader.uploadVariantPhoto(this)" title="Upload, compress to WebP & host on x02.me">
                    <span>☁️</span> Upload
                </button>
            </div>
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

// -------------------------------------------------------------------------
// Multi-Category Interactive Sizes & Measurements Engine (Shirts, Jeans, Shoes, etc.)
// -------------------------------------------------------------------------
const SIZE_CATALOG_PRESETS = {
    shirts: {
        id: 'shirts',
        name: 'Shirts & Tops (T-Shirts, Polos, Hoodies)',
        icon: '👕',
        dim1Key: 'Height',
        dim1Label: 'Height / بلندی / الارتفاع',
        dim1Unit: 'cm',
        dim2Key: 'Width',
        dim2Label: 'Width (Chest) / پانی / الصدر',
        dim2Unit: 'cm',
        defaultSizes: ['S', 'M', 'L', 'XL'],
        sizes: ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'],
        quickPresets: [
            { label: 'S–XXL', sizes: ['S', 'M', 'L', 'XL', 'XXL'] },
            { label: 'XS–5XL', sizes: ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'] }
        ],
        defaults: {
            'XS': { dim1: '62', dim2: '42' },
            'S': { dim1: '65', dim2: '45' },
            'M': { dim1: '70', dim2: '50' },
            'L': { dim1: '73', dim2: '54' },
            'XL': { dim1: '76', dim2: '58' },
            'XXL': { dim1: '79', dim2: '62' },
            '2XL': { dim1: '79', dim2: '62' },
            '3XL': { dim1: '82', dim2: '66' },
            '4XL': { dim1: '85', dim2: '70' },
            '5XL': { dim1: '88', dim2: '74' }
        }
    },
    jeans: {
        id: 'jeans',
        name: 'Jeans & Trousers (Pants, Chinos, Denim)',
        icon: '👖',
        dim1Key: 'Waist',
        dim1Label: 'Waist / کەمەر / الخصر',
        dim1Unit: 'cm',
        dim2Key: 'Length',
        dim2Label: 'Length (Inseam) / درێژی / الطول',
        dim2Unit: 'cm',
        defaultSizes: ['30', '32', '34', '36'],
        sizes: ['28', '29', '30', '31', '32', '33', '34', '36', '38', '40', 'S', 'M', 'L', 'XL', 'XXL'],
        quickPresets: [
            { label: '30–36 (US)', sizes: ['30', '32', '34', '36'] },
            { label: '28–40 (Full)', sizes: ['28', '29', '30', '31', '32', '33', '34', '36', '38', '40'] },
            { label: 'S–XXL', sizes: ['S', 'M', 'L', 'XL', 'XXL'] }
        ],
        defaults: {
            '28': { dim1: '71', dim2: '98' },
            '29': { dim1: '74', dim2: '100' },
            '30': { dim1: '76', dim2: '102' },
            '31': { dim1: '79', dim2: '103' },
            '32': { dim1: '81', dim2: '104' },
            '33': { dim1: '84', dim2: '105' },
            '34': { dim1: '86', dim2: '106' },
            '36': { dim1: '91', dim2: '108' },
            '38': { dim1: '96', dim2: '110' },
            '40': { dim1: '101', dim2: '112' },
            'S': { dim1: '76', dim2: '102' },
            'M': { dim1: '81', dim2: '104' },
            'L': { dim1: '86', dim2: '106' },
            'XL': { dim1: '91', dim2: '108' },
            'XXL': { dim1: '96', dim2: '110' }
        }
    },
    jackets: {
        id: 'jackets',
        name: 'Jackets, Blazers & Winter Coats',
        icon: '🧥',
        dim1Key: 'Length',
        dim1Label: 'Jacket Length / درێژی / الطول',
        dim1Unit: 'cm',
        dim2Key: 'Chest',
        dim2Label: 'Chest (Width) / سنگ و پانی / الصدر',
        dim2Unit: 'cm',
        defaultSizes: ['S', 'M', 'L', 'XL'],
        sizes: ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '38R', '40R', '42R', '44R', '46R'],
        quickPresets: [
            { label: 'S–XXL', sizes: ['S', 'M', 'L', 'XL', 'XXL'] },
            { label: 'S–4XL (Full)', sizes: ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'] },
            { label: '38R–46R (Tailored)', sizes: ['38R', '40R', '42R', '44R', '46R'] }
        ],
        defaults: {
            'S': { dim1: '68', dim2: '52' },
            'M': { dim1: '71', dim2: '55' },
            'L': { dim1: '74', dim2: '58' },
            'XL': { dim1: '77', dim2: '61' },
            'XXL': { dim1: '80', dim2: '64' },
            '3XL': { dim1: '83', dim2: '67' },
            '4XL': { dim1: '86', dim2: '70' },
            '38R': { dim1: '72', dim2: '53' },
            '40R': { dim1: '74', dim2: '56' },
            '42R': { dim1: '76', dim2: '59' },
            '44R': { dim1: '78', dim2: '62' },
            '46R': { dim1: '80', dim2: '65' }
        }
    },
    shoes: {
        id: 'shoes',
        name: 'Shoes, Sneakers & Boots',
        icon: '👟',
        dim1Key: 'Foot Length',
        dim1Label: 'Foot Length / درێژیا پێی / طول القدم',
        dim1Unit: 'cm',
        dim2Key: 'Width',
        dim2Label: 'Insole Width / پانی / العرض',
        dim2Unit: 'cm',
        defaultSizes: ['40', '41', '42', '43', '44'],
        sizes: ['39', '40', '41', '42', '43', '44', '45', '46', '47', '7 US', '8 US', '9 US', '10 US', '11 US', '12 US'],
        quickPresets: [
            { label: '40–45 (EU Common)', sizes: ['40', '41', '42', '43', '44', '45'] },
            { label: '39–47 (EU Full)', sizes: ['39', '40', '41', '42', '43', '44', '45', '46', '47'] },
            { label: '8–12 (US)', sizes: ['8 US', '9 US', '10 US', '11 US', '12 US'] }
        ],
        defaults: {
            '39': { dim1: '24.5', dim2: '9.2' },
            '40': { dim1: '25.0', dim2: '9.4' },
            '41': { dim1: '25.5', dim2: '9.6' },
            '42': { dim1: '26.0', dim2: '9.8' },
            '43': { dim1: '26.5', dim2: '10.0' },
            '44': { dim1: '27.0', dim2: '10.2' },
            '45': { dim1: '27.5', dim2: '10.4' },
            '46': { dim1: '28.0', dim2: '10.6' },
            '47': { dim1: '28.5', dim2: '10.8' },
            '7 US': { dim1: '25.0', dim2: '9.4' },
            '8 US': { dim1: '26.0', dim2: '9.8' },
            '9 US': { dim1: '27.0', dim2: '10.2' },
            '10 US': { dim1: '28.0', dim2: '10.6' },
            '11 US': { dim1: '29.0', dim2: '11.0' },
            '12 US': { dim1: '30.0', dim2: '11.4' }
        }
    },
    watches: {
        id: 'watches',
        name: 'Watches & Timepiece Straps',
        icon: '⌚',
        dim1Key: 'Case',
        dim1Label: 'Case Diameter / قەبارێ دەمژمێرێ / قطر الساعة',
        dim1Unit: 'mm',
        dim2Key: 'Strap',
        dim2Label: 'Strap Width / پانییا قایشی / عرض السوار',
        dim2Unit: 'mm',
        defaultSizes: ['40mm', '42mm', '44mm'],
        sizes: ['38mm', '40mm', '41mm', '42mm', '44mm', '45mm', '46mm'],
        quickPresets: [
            { label: '40–44mm', sizes: ['40mm', '42mm', '44mm'] },
            { label: '38–46mm (Full)', sizes: ['38mm', '40mm', '41mm', '42mm', '44mm', '45mm', '46mm'] }
        ],
        defaults: {
            '38mm': { dim1: '38', dim2: '18' },
            '40mm': { dim1: '40', dim2: '20' },
            '41mm': { dim1: '41', dim2: '20' },
            '42mm': { dim1: '42', dim2: '22' },
            '44mm': { dim1: '44', dim2: '22' },
            '45mm': { dim1: '45', dim2: '24' },
            '46mm': { dim1: '46', dim2: '24' }
        }
    }
};

function detectCategoryPreset(title, category, sizes, measurements) {
    const text = ((title || '') + ' ' + (category || '')).toLowerCase();
    const sizesStr = Array.isArray(sizes) ? sizes.join(' ') : String(sizes || '');
    const mStr = typeof measurements === 'object' ? JSON.stringify(measurements) : String(measurements || '');

    if (text.includes('shoe') || text.includes('sneaker') || text.includes('boot') || text.includes('loafer') || text.includes('heel') || (sizesStr.match(/\b(39|40|41|42|43|44|45|46|47)\b/) && !sizesStr.match(/\b(28|29|30|31|32|33|34)\b/))) {
        return 'shoes';
    }
    if (text.includes('jean') || text.includes('pant') || text.includes('trouser') || text.includes('denim') || mStr.includes('Waist') || sizesStr.match(/\b(28|29|30|31|32|33|34|36|38|40)\b/)) {
        return 'jeans';
    }
    if (text.includes('jacket') || text.includes('coat') || text.includes('blazer') || text.includes('hoodie') || text.includes('suit') || mStr.includes('Chest') || mStr.includes('Jacket') || sizesStr.match(/\b(38R|40R|42R|44R)\b/)) {
        return 'jackets';
    }
    if (category === 'watches' || text.includes('watch') || text.includes('timepiece') || sizesStr.includes('mm') || mStr.includes('Case')) {
        return 'watches';
    }
    return 'shirts';
}

function parseDimensions(str, sizeName, activePresetKey = 'shirts') {
    const preset = SIZE_CATALOG_PRESETS[activePresetKey] || SIZE_CATALOG_PRESETS.shirts;
    let dim1 = '';
    let dim2 = '';

    if (str && typeof str === 'string') {
        const d1Match = str.match(/(?:Foot Length|Foot|Waist|Case|Diameter|Length|Height|Jacket|بلندی|درێژی|کەمەر|درێژیا پێی|قەبارە|الارتفاع|الطول|الخصر|طول القدم)[:\s]*([0-9.]+)/i);
        if (d1Match) dim1 = d1Match[1];

        const d2Match = str.match(/(?:Foot Width|Strap|Band|Chest|Inseam|Trousers|Width|پانی|الصدر|العرض|قایش)[:\s]*([0-9.]+)/i);
        if (d2Match) dim2 = d2Match[1];
    }

    if (!dim1 || !dim2) {
        if (preset.defaults && preset.defaults[sizeName]) {
            if (!dim1) dim1 = preset.defaults[sizeName].dim1;
            if (!dim2) dim2 = preset.defaults[sizeName].dim2;
        } else {
            // Check cross-preset defaults
            for (const key in SIZE_CATALOG_PRESETS) {
                const other = SIZE_CATALOG_PRESETS[key];
                if (other.defaults && other.defaults[sizeName]) {
                    if (!dim1) dim1 = other.defaults[sizeName].dim1;
                    if (!dim2) dim2 = other.defaults[sizeName].dim2;
                    break;
                }
            }
        }
    }

    if (!dim1) dim1 = preset.dim1Unit === 'mm' ? '42' : '70';
    if (!dim2) dim2 = preset.dim2Unit === 'mm' ? '22' : '50';

    return { dim1, dim2 };
}

window.sizeSelectorState = {
    add: { activePreset: 'shirts', sizes: [], measurements: {}, allOptions: [] },
    edit: { activePreset: 'shirts', sizes: [], measurements: {}, allOptions: [] }
};

function initSizeSelector(prefix, initialSizes = null, initialMeasurements = {}, forcedPreset = null) {
    let detectedPreset = forcedPreset;
    if (!detectedPreset) {
        detectedPreset = detectCategoryPreset('', '', initialSizes, initialMeasurements);
    }
    const presetConfig = SIZE_CATALOG_PRESETS[detectedPreset] || SIZE_CATALOG_PRESETS.shirts;

    // Normalize initial sizes
    let sizesArr = [];
    if (Array.isArray(initialSizes)) {
        sizesArr = initialSizes.map(s => String(s).trim()).filter(Boolean);
    } else if (typeof initialSizes === 'string' && initialSizes.trim()) {
        sizesArr = initialSizes.split(',').map(s => s.trim()).filter(Boolean);
    }

    if (sizesArr.length === 0) {
        sizesArr = [...presetConfig.defaultSizes];
    }

    const allOpts = [...presetConfig.sizes];
    sizesArr.forEach(s => {
        if (!allOpts.includes(s)) allOpts.push(s);
    });

    const state = {
        activePreset: detectedPreset,
        sizes: [...sizesArr],
        measurements: (typeof initialMeasurements === 'object' && initialMeasurements !== null) ? { ...initialMeasurements } : {},
        allOptions: allOpts
    };

    window.sizeSelectorState[prefix] = state;
    renderSizeSelectorUI(prefix);
}

function switchCategoryPreset(prefix, presetKey, preserveExistingSizes = false) {
    const preset = SIZE_CATALOG_PRESETS[presetKey];
    if (!preset) return;

    const state = window.sizeSelectorState[prefix];
    state.activePreset = presetKey;
    state.allOptions = [...preset.sizes];

    if (!preserveExistingSizes) {
        state.sizes = [...preset.defaultSizes];
        // Populate default measurements for this preset
        state.sizes.forEach(sz => {
            const dims = parseDimensions('', sz, presetKey);
            state.measurements[sz] = `${preset.dim1Key}: ${dims.dim1}${preset.dim1Unit} • ${preset.dim2Key}: ${dims.dim2}${preset.dim2Unit}`;
        });
    } else {
        // Keep active sizes but ensure all options include them
        state.sizes.forEach(sz => {
            if (!state.allOptions.includes(sz)) state.allOptions.push(sz);
        });
    }

    renderSizeSelectorUI(prefix);
}

function renderSizeSelectorUI(prefix) {
    updateCategoryTabStyles(prefix);
    renderQuickPresetButtons(prefix);
    renderSizePills(prefix);
    renderMeasurementCards(prefix);
    updateSizesCountBadge(prefix);

    const preset = SIZE_CATALOG_PRESETS[window.sizeSelectorState[prefix].activePreset] || SIZE_CATALOG_PRESETS.shirts;
    const labelEl = document.getElementById(prefix + 'ActiveBlueprintLabel');
    if (labelEl) {
        labelEl.innerText = preset.name;
    }
}

function updateCategoryTabStyles(prefix) {
    const activePreset = window.sizeSelectorState[prefix].activePreset;
    const tabs = document.querySelectorAll('.size-cat-tab-' + prefix);
    tabs.forEach(tab => {
        const pKey = tab.getAttribute('data-preset');
        if (pKey === activePreset) {
            tab.style.background = 'var(--accent-gold)';
            tab.style.borderColor = 'var(--accent-gold)';
            tab.style.color = '#000';
            tab.style.boxShadow = '0 2px 6px rgba(212,175,55,0.25)';
        } else {
            tab.style.background = 'transparent';
            tab.style.borderColor = 'var(--border-color)';
            tab.style.color = 'var(--text-secondary)';
            tab.style.boxShadow = 'none';
        }
    });
}

function renderQuickPresetButtons(prefix) {
    const container = document.getElementById(prefix + 'QuickPresetsContainer');
    if (!container) return;
    const state = window.sizeSelectorState[prefix];
    const preset = SIZE_CATALOG_PRESETS[state.activePreset] || SIZE_CATALOG_PRESETS.shirts;

    container.innerHTML = '';
    (preset.quickPresets || []).forEach(qp => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-outline';
        btn.style.cssText = 'padding:4px 10px; font-size:11.5px; font-weight:600;';
        btn.innerText = qp.label;
        btn.onclick = () => {
            state.sizes = [...qp.sizes];
            qp.sizes.forEach(sz => {
                if (!state.allOptions.includes(sz)) state.allOptions.push(sz);
                if (!state.measurements[sz]) {
                    const dims = parseDimensions('', sz, state.activePreset);
                    state.measurements[sz] = `${preset.dim1Key}: ${dims.dim1}${preset.dim1Unit} • ${preset.dim2Key}: ${dims.dim2}${preset.dim2Unit}`;
                }
            });
            renderSizeSelectorUI(prefix);
        };
        container.appendChild(btn);
    });
}

function renderSizePills(prefix) {
    const container = document.getElementById(prefix + 'SizePillsContainer');
    if (!container) return;

    const state = window.sizeSelectorState[prefix];
    container.innerHTML = '';

    state.allOptions.forEach(size => {
        const isActive = state.sizes.includes(size);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm ' + (isActive ? 'btn-primary' : 'btn-outline');
        btn.style.cssText = 'padding:6px 14px; font-size:13px; font-weight:700; border-radius:6px; transition:all 0.15s ease; display:inline-flex; align-items:center; gap:6px; cursor:pointer;';
        if (isActive) {
            btn.style.background = 'var(--accent-gold)';
            btn.style.borderColor = 'var(--accent-gold)';
            btn.style.color = '#000';
        }
        btn.innerHTML = (isActive ? '✓ ' : '+ ') + escapeHtmlAttr(size);
        btn.onclick = () => toggleSize(prefix, size);
        container.appendChild(btn);
    });
}

function renderMeasurementCards(prefix) {
    const container = document.getElementById(prefix + 'SizesMeasurementsList');
    if (!container) return;

    const state = window.sizeSelectorState[prefix];
    const preset = SIZE_CATALOG_PRESETS[state.activePreset] || SIZE_CATALOG_PRESETS.shirts;
    const formPrefix = prefix === 'add' ? 'prod' : 'edit_prod';
    container.innerHTML = '';

    if (state.sizes.length === 0) {
        container.innerHTML = `<div style="grid-column:1/-1; text-align:center; padding:18px; color:var(--text-muted); font-size:13px; background:var(--bg-surface); border-radius:8px;">
            ⚠️ No sizes selected yet. Click the size buttons above to add available sizes (e.g. ${preset.defaultSizes.join(', ')}).
        </div>`;
        return;
    }

    state.sizes.forEach(size => {
        const currentMeasurement = state.measurements[size] || '';
        const dims = parseDimensions(currentMeasurement, size, state.activePreset);

        const card = document.createElement('div');
        card.className = 'size-measurement-card';
        card.style.cssText = 'background:var(--bg-surface); border:1px solid var(--border-color); border-radius:8px; padding:12px 14px; display:flex; flex-direction:column; gap:10px; box-shadow:0 1px 3px rgba(0,0,0,0.05);';

        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="hidden" name="${formPrefix}_sizes[]" value="${escapeHtmlAttr(size)}">
                    <input type="hidden" name="${formPrefix}_size_dim1_label[${escapeHtmlAttr(size)}]" value="${escapeHtmlAttr(preset.dim1Key)}">
                    <input type="hidden" name="${formPrefix}_size_dim2_label[${escapeHtmlAttr(size)}]" value="${escapeHtmlAttr(preset.dim2Key)}">
                    <input type="hidden" name="${formPrefix}_size_dim1_unit[${escapeHtmlAttr(size)}]" value="${escapeHtmlAttr(preset.dim1Unit)}">
                    <input type="hidden" name="${formPrefix}_size_dim2_unit[${escapeHtmlAttr(size)}]" value="${escapeHtmlAttr(preset.dim2Unit)}">
                    
                    <span style="background:var(--accent-gold); color:#000; font-weight:800; font-size:13px; padding:3px 10px; border-radius:5px; letter-spacing:0.5px; display:inline-block;">${escapeHtmlAttr(size)}</span>
                    <span style="font-size:12px; font-weight:700; color:var(--text-primary);">${escapeHtmlAttr(size)} <?php echo adm_t('admin_size_available_badge', 'Available'); ?></span>
                </div>
                <button type="button" class="btn btn-sm" onclick="toggleSize('${prefix}', '${escapeHtmlAttr(size)}', false)" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:14px; padding:2px 6px;" title="Remove size">✕</button>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <div>
                    <label style="font-size:11px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:3px;">
                        ${preset.dim1Label}
                    </label>
                    <div style="position:relative; display:flex; align-items:center;">
                        <input type="number" step="0.1" name="${formPrefix}_size_dim1[${escapeHtmlAttr(size)}]" value="${escapeHtmlAttr(dims.dim1)}" class="form-control" style="font-size:13px; padding:6px 8px; padding-right:32px; font-weight:700; width:100%;" placeholder="70" min="1" max="500" oninput="updateStoredDimension('${prefix}', '${escapeHtmlAttr(size)}', 'dim1', this.value)">
                        <span style="position:absolute; right:8px; font-size:11px; color:var(--text-muted); font-weight:700; pointer-events:none;">${preset.dim1Unit}</span>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:3px;">
                        ${preset.dim2Label}
                    </label>
                    <div style="position:relative; display:flex; align-items:center;">
                        <input type="number" step="0.1" name="${formPrefix}_size_dim2[${escapeHtmlAttr(size)}]" value="${escapeHtmlAttr(dims.dim2)}" class="form-control" style="font-size:13px; padding:6px 8px; padding-right:32px; font-weight:700; width:100%;" placeholder="50" min="1" max="500" oninput="updateStoredDimension('${prefix}', '${escapeHtmlAttr(size)}', 'dim2', this.value)">
                        <span style="position:absolute; right:8px; font-size:11px; color:var(--text-muted); font-weight:700; pointer-events:none;">${preset.dim2Unit}</span>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(card);
    });
}

function updateStoredDimension(prefix, size, dimKey, val) {
    const state = window.sizeSelectorState[prefix];
    if (!state) return;
    const preset = SIZE_CATALOG_PRESETS[state.activePreset] || SIZE_CATALOG_PRESETS.shirts;
    const current = parseDimensions(state.measurements[size] || '', size, state.activePreset);
    if (dimKey === 'dim1') current.dim1 = val;
    if (dimKey === 'dim2') current.dim2 = val;
    state.measurements[size] = `${preset.dim1Key}: ${current.dim1}${preset.dim1Unit} • ${preset.dim2Key}: ${current.dim2}${preset.dim2Unit}`;
}

function toggleSize(prefix, size, forceState = null) {
    const state = window.sizeSelectorState[prefix];
    if (!state) return;
    const preset = SIZE_CATALOG_PRESETS[state.activePreset] || SIZE_CATALOG_PRESETS.shirts;

    const idx = state.sizes.indexOf(size);
    const shouldBeActive = forceState !== null ? forceState : (idx === -1);

    if (shouldBeActive && idx === -1) {
        state.sizes.push(size);
        if (!state.measurements[size]) {
            const dims = parseDimensions('', size, state.activePreset);
            state.measurements[size] = `${preset.dim1Key}: ${dims.dim1}${preset.dim1Unit} • ${preset.dim2Key}: ${dims.dim2}${preset.dim2Unit}`;
        }
    } else if (!shouldBeActive && idx !== -1) {
        state.sizes.splice(idx, 1);
    }

    renderSizeSelectorUI(prefix);
}

function applySizePreset(prefix, presetType) {
    const state = window.sizeSelectorState[prefix];
    if (!state) return;
    const preset = SIZE_CATALOG_PRESETS[state.activePreset] || SIZE_CATALOG_PRESETS.shirts;

    if (presetType === 'clear') {
        state.sizes = [];
    } else {
        state.sizes = [...preset.defaultSizes];
        state.sizes.forEach(sz => {
            if (!state.measurements[sz]) {
                const dims = parseDimensions('', sz, state.activePreset);
                state.measurements[sz] = `${preset.dim1Key}: ${dims.dim1}${preset.dim1Unit} • ${preset.dim2Key}: ${dims.dim2}${preset.dim2Unit}`;
            }
        });
    }

    renderSizeSelectorUI(prefix);
}

function addCustomSize(prefix) {
    const input = document.getElementById(prefix + 'CustomSizeInput');
    if (!input) return;
    const val = input.value.trim().toUpperCase();
    if (!val) return;

    const state = window.sizeSelectorState[prefix];
    const preset = SIZE_CATALOG_PRESETS[state.activePreset] || SIZE_CATALOG_PRESETS.shirts;

    if (!state.allOptions.includes(val)) {
        state.allOptions.push(val);
    }
    if (!state.sizes.includes(val)) {
        state.sizes.push(val);
        const dims = parseDimensions('', val, state.activePreset);
        state.measurements[val] = `${preset.dim1Key}: ${dims.dim1}${preset.dim1Unit} • ${preset.dim2Key}: ${dims.dim2}${preset.dim2Unit}`;
    }

    input.value = '';
    renderSizeSelectorUI(prefix);
}

function updateSizesCountBadge(prefix) {
    const badge = document.getElementById(prefix + 'ActiveSizesCount');
    if (!badge) return;
    const count = window.sizeSelectorState[prefix].sizes.length;
    badge.innerText = count === 1 ? '1 size selected' : `${count} sizes selected`;
}

function escapeHtmlAttr(str) {
    if (!str) return '';
    return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function openEditProductModalFromBtn(btn) {
    if (!btn) return;
    var product = null;
    var raw = btn.getAttribute('data-product');
    if (raw) {
        try {
            product = JSON.parse(raw);
        } catch(e) {
            console.warn("Failed to parse data-product JSON, falling back to ID lookup", e);
        }
    }
    if (!product) {
        var id = btn.getAttribute('data-id');
        if (id && window.ALL_PRODUCTS_MAP && window.ALL_PRODUCTS_MAP[id]) {
            product = window.ALL_PRODUCTS_MAP[id];
        }
    }
    if (product) {
        openEditProductModal(product);
    } else {
        console.error("No product found for edit button", btn);
    }
}

function openEditProductModal(product) {
    if (!product) return;
    
    var setVal = function(id, val) {
        var el = document.getElementById(id);
        if (el) el.value = (val !== undefined && val !== null) ? val : '';
    };
    var setText = function(id, text) {
        var el = document.getElementById(id);
        if (el) el.innerText = (text !== undefined && text !== null) ? text : '';
    };

    try {
        setVal('editProdId', product.id);
        setText('editProductModalIdBadge', '#' + product.id);
        
        var pTitleEn = typeof product.title === 'object' ? (product.title.en || '') : (product.title || '');
        var pTitleAr = typeof product.title === 'object' ? (product.title.ar || pTitleEn) : pTitleEn;
        var pTitleKu = typeof product.title === 'object' ? (product.title.ku || pTitleEn) : pTitleEn;

        setText('editProductModalSub', 'Editing: ' + pTitleEn + ' (' + (product.category || 'luxury') + ')');
        setVal('editProdTitleEn', pTitleEn);
        setVal('editProdTitleAr', pTitleAr);
        setVal('editProdTitleKu', pTitleKu);

        setVal('editProdCategory', product.category || 'clothes');
        setVal('editProdPrice', product.price !== undefined ? product.price : 0);
        setVal('editProdOldPrice', product.old_price || '');
        setVal('editProdStock', product.stock !== undefined ? product.stock : 10);
        
        var featEl = document.getElementById('editProdFeatured');
        if (featEl) {
            featEl.checked = !!product.featured;
        }

        setVal('editProdBadge', product.badge || '');
        setVal('editProdBadgeAr', product.badge_ar || product.badge || '');
        setVal('editProdBadgeKu', product.badge_ku || product.badge || '');

        var mainImg = product.image || '';
        if (window.editMainUploader && typeof window.editMainUploader.setUrl === 'function') {
            window.editMainUploader.setUrl(mainImg);
        }
        var galleryArr = Array.isArray(product.images) ? product.images : (mainImg ? [mainImg] : []);
        if (window.editGalleryUploader && typeof window.editGalleryUploader.setUrls === 'function') {
            window.editGalleryUploader.setUrls(galleryArr);
        }
        updateEditImagePreview(mainImg);

        // Initialize Interactive Sizes & Measurements in Edit Modal (Auto-detect Category Blueprint)
        var prodMeasurements = product.size_measurements || {};
        if (typeof prodMeasurements === 'string') {
            try { prodMeasurements = JSON.parse(prodMeasurements); } catch(e) { prodMeasurements = {}; }
        }
        if (typeof detectCategoryPreset === 'function' && typeof initSizeSelector === 'function') {
            var detectedPreset = detectCategoryPreset(pTitleEn, product.category, product.sizes, prodMeasurements);
            initSizeSelector('edit', product.sizes, prodMeasurements, detectedPreset);
        }

        // Populate Multi-Color Variants in Edit Modal
        var editColorsList = document.getElementById('editColorVariantsList');
        if (editColorsList && typeof addColorVariantRow === 'function') {
            editColorsList.innerHTML = '';
            var prodColors = Array.isArray(product.colors) ? product.colors : (product.colors ? [product.colors] : []);
            var colorHexes = (typeof product.color_hexes === 'object' && product.color_hexes !== null) ? product.color_hexes : {};
            var colorImages = (typeof product.color_images === 'object' && product.color_images !== null) ? product.color_images : {};

            if (prodColors.length > 0) {
                prodColors.forEach(function(colName, idx) {
                    var hex = colorHexes[colName] || product.color_hex || '#d4af37';
                    var img = colorImages[colName] || (product.images && product.images[idx]) || (idx === 0 ? mainImg : '');
                    addColorVariantRow('editColorVariantsList', colName, hex, img);
                });
            } else {
                var defName = product.color_name || 'Obsidian Black';
                var defHex = product.color_hex || '#111827';
                addColorVariantRow('editColorVariantsList', defName, defHex, mainImg);
            }
        }

        // Color Variations & Model Grouping
        setVal('editProdModelGroup', product.model_group || '');
        setVal('editProdColorName', product.color_name || '');

        // Clear and check linked product checkboxes
        var linkedIds = Array.isArray(product.linked_products) ? product.linked_products.map(Number) : [];
        document.querySelectorAll('.edit-linked-cb').forEach(function(cb) {
            var val = Number(cb.value);
            cb.checked = linkedIds.includes(val);
            // Hide the checkbox for the current product itself
            if (cb.closest('label')) {
                cb.closest('label').style.display = (val === Number(product.id)) ? 'none' : 'flex';
            }
        });

        var pDescEn = typeof product.description === 'object' ? (product.description.en || '') : (product.description || '');
        var pDescAr = typeof product.description === 'object' ? (product.description.ar || pDescEn) : pDescEn;
        var pDescKu = typeof product.description === 'object' ? (product.description.ku || pDescEn) : pDescEn;

        setVal('editProdDescEn', pDescEn);
        setVal('editProdDescAr', pDescAr);
        setVal('editProdDescKu', pDescKu);

        if (typeof calculateDiscountPreview === 'function') {
            calculateDiscountPreview();
        }
    } catch(err) {
        console.error("Error setting up edit modal values:", err);
    } finally {
        var modalOverlay = document.getElementById('editProductModalOverlay');
        if (modalOverlay) {
            modalOverlay.classList.add('open');
        }
    }
}

function closeEditProductModal() {
    var modalOverlay = document.getElementById('editProductModalOverlay');
    if (modalOverlay) {
        modalOverlay.classList.remove('open');
    }
}

// Close modal when clicking dark backdrop or pressing Escape
document.addEventListener('DOMContentLoaded', function() {
    var modalOverlay = document.getElementById('editProductModalOverlay');
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                closeEditProductModal();
            }
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditProductModal();
        }
    });
});

function updateEditImagePreview(customUrl) {
    const url = customUrl || (window.editMainUploader ? window.editMainUploader.getUrl() : '') || '';
    const imgEl = document.getElementById('editImageLivePreview');
    if (imgEl && url) {
        imgEl.src = url;
    }
}

function setEditImagePreset(url) {
    if (window.editMainUploader) {
        window.editMainUploader.setUrl(url);
    }
    const galleryEl = document.getElementById('editProdGalleryTextarea');
    if (galleryEl && (!galleryEl.value || galleryEl.value.indexOf(url) === -1)) {
        const cur = galleryEl.value ? galleryEl.value.split(',').map(s=>s.trim()).filter(Boolean) : [];
        cur.push(url);
        if (window.editGalleryUploader) {
            window.editGalleryUploader.setUrls(cur);
        }
    }
    updateEditImagePreview(url);
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

// Initialize x02.me WebP Image Uploaders & Size Selectors
document.addEventListener('DOMContentLoaded', function() {
    initSizeSelector('add', ['S', 'M', 'L', 'XL'], {}, 'shirts');

    // Auto-switch size preset if admin changes product category or types in title
    const addCatSelect = document.getElementById('prodCategory');
    if (addCatSelect) {
        addCatSelect.addEventListener('change', function() {
            const catVal = this.value;
            if (catVal === 'watches') {
                switchCategoryPreset('add', 'watches');
            } else if (catVal === 'shoes' || catVal === 'footwear') {
                switchCategoryPreset('add', 'shoes');
            }
        });
    }

    const editCatSelect = document.getElementById('editProdCategory');
    if (editCatSelect) {
        editCatSelect.addEventListener('change', function() {
            const catVal = this.value;
            if (catVal === 'watches' && window.sizeSelectorState.edit.activePreset !== 'watches') {
                switchCategoryPreset('edit', 'watches');
            }
        });
    }

    if (window.X02Uploader) {
        window.addMainUploader = window.X02Uploader.initSingleUploader({
            containerId: 'addMainImageUploaderBox',
            inputName: 'prod_image',
            initialUrl: '',
            label: "<?php echo adm_t('admin_field_main_image', 'Main Product Image'); ?>"
        });

        window.addGalleryUploader = window.X02Uploader.initGalleryUploader({
            containerId: 'addGalleryUploaderBox',
            textareaId: 'addProdGalleryTextarea',
            initialUrls: []
        });

        window.editMainUploader = window.X02Uploader.initSingleUploader({
            containerId: 'editMainImageUploaderBox',
            inputName: 'edit_prod_image',
            initialUrl: '',
            label: "<?php echo adm_t('admin_field_main_image', 'Primary Cover Image'); ?>",
            onChange: function(url) {
                updateEditImagePreview(url);
            }
        });

        window.editGalleryUploader = window.X02Uploader.initGalleryUploader({
            containerId: 'editGalleryUploaderBox',
            textareaId: 'editProdGalleryTextarea',
            initialUrls: []
        });
    }
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>
