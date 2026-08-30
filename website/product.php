<?php
$activePage = 'shop';
require_once __DIR__ . '/header.php';

$productId = intval($_GET['id'] ?? 1);
$product = get_product_by_id($productId);

if (!$product) {
    echo '<div class="container text-center py-60"><h2>Product Not Found</h2><a href="shop.php" class="btn btn-primary mt-20">Back to Shop</a></div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$titleText = is_array($product['title']) ? ($product['title'][$lang] ?? $product['title']['en']) : $product['title'];
$descText = is_array($product['description']) ? ($product['description'][$lang] ?? $product['description']['en']) : $product['description'];
$badgeKey = 'badge_' . $lang;
$badgeText = $product[$badgeKey] ?? $product['badge'] ?? '';

$allProducts = get_all_products();
$relatedProducts = array_filter($allProducts, function($p) use ($product) {
    return $p['category'] === $product['category'] && $p['id'] !== $product['id'];
});
$relatedProducts = array_slice($relatedProducts, 0, 4);

// Prepare Size Measurements map
$sizeMeasurements = $product['size_measurements'] ?? [];
if (is_string($sizeMeasurements)) {
    $sizeMeasurements = json_decode($sizeMeasurements, true) ?: [];
}
if (!is_array($sizeMeasurements)) {
    $sizeMeasurements = [];
}
if (!empty($product['sizes'])) {
    foreach ($product['sizes'] as $sz) {
        if (!isset($sizeMeasurements[$sz])) {
            if ($product['category'] === 'clothes') {
                if ($sz === 'S') $sizeMeasurements['S'] = 'Length: 68 cm • Chest: 96 cm • Shoulder: 44 cm';
                elseif ($sz === 'M') $sizeMeasurements['M'] = 'Length: 70 cm • Chest: 102 cm • Shoulder: 46 cm';
                elseif ($sz === 'L') $sizeMeasurements['L'] = 'Length: 73 cm • Chest: 108 cm • Shoulder: 48 cm';
                elseif ($sz === 'XL') $sizeMeasurements['XL'] = 'Length: 76 cm • Chest: 114 cm • Shoulder: 50 cm';
                elseif ($sz === 'XXL') $sizeMeasurements['XXL'] = 'Length: 79 cm • Chest: 120 cm • Shoulder: 52 cm';
                else $sizeMeasurements[$sz] = 'Length: 72 cm • Chest: 104 cm • Shoulder: 46 cm';
            } elseif ($product['category'] === 'watches') {
                $sizeMeasurements[$sz] = 'Case Diameter: ' . $sz . ' • Height/Thickness: 11.5 mm • Width/Strap: 20 mm';
            } else {
                $sizeMeasurements[$sz] = 'Standard edition dimension: ' . $sz;
            }
        }
    }
}

// Map each color to its corresponding image
$colorImages = [];
if (!empty($product['colors'])) {
    foreach ($product['colors'] as $i => $c) {
        if (!empty($product['images'][$i])) {
            $colorImages[$c] = $product['images'][$i];
        } else {
            $colorImages[$c] = $product['image'];
        }
    }
}
?>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="index.php"><?php echo t('nav_home', $lang); ?></a>
            <span class="bc-sep">/</span>
            <a href="shop.php"><?php echo t('nav_shop', $lang); ?></a>
            <span class="bc-sep">/</span>
            <a href="shop.php?cat=<?php echo $product['category']; ?>"><?php echo t('filter_' . $product['category'], $lang); ?></a>
            <span class="bc-sep">/</span>
            <span class="bc-current"><?php echo htmlspecialchars($titleText); ?></span>
        </nav>
    </div>
</div>

<section class="single-product-section">
    <div class="container">

        <div class="product-view-grid">
            
            <!-- Gallery Images (Main + Thumbnails) -->
            <div class="product-gallery">
                <div class="gallery-main-wrap">
                    <?php if (!empty($badgeText)): ?>
                        <span class="product-badge-tag"><?php echo htmlspecialchars($badgeText); ?></span>
                    <?php endif; ?>
                    <img id="mainProductImage" src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($titleText); ?>" class="gallery-main-img" style="transition: opacity 0.25s ease, transform 0.25s ease;">
                </div>

                <?php if (!empty($product['images']) && count($product['images']) > 1): ?>
                    <div class="gallery-thumbs-row" id="galleryThumbsRow">
                        <?php foreach ($product['images'] as $idx => $imgUrl): ?>
                            <button type="button" class="thumb-btn <?php echo $idx === 0 ? 'active' : ''; ?>" data-img="<?php echo htmlspecialchars($imgUrl); ?>" onclick="switchMainImage('<?php echo htmlspecialchars(addslashes($imgUrl)); ?>', this)">
                                <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Thumbnail">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Purchase Details & Options -->
            <div class="product-buy-info">
                <div class="product-meta-header">
                    <span class="product-cat-pill"><?php echo t('filter_' . $product['category'], $lang); ?></span>
                    <span class="stock-status in-stock">● <?php echo t('in_stock', $lang); ?></span>
                </div>
                
                <h1 class="single-product-title"><?php echo htmlspecialchars($titleText); ?></h1>

                <div class="single-price-box">
                    <span class="current-price-lg"><?php echo number_format($product['price']); ?> IQD</span>
                    <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
                        <span class="old-price-lg"><?php echo number_format($product['old_price']); ?> IQD</span>
                        <span class="save-badge">Save <?php echo number_format($product['old_price'] - $product['price']); ?> IQD</span>
                    <?php endif; ?>
                </div>

                <div class="product-short-desc">
                    <p><?php echo htmlspecialchars($descText); ?></p>
                </div>

                <!-- Sizes Selector (Manual selection required, not chosen by default) -->
                <?php if (!empty($product['sizes'])): ?>
                    <div class="option-select-group" id="sizeSelectGroup">
                        <div class="option-header-row">
                            <label class="option-label">
                                <strong><?php echo $lang === 'ku' ? 'قیاس / دیزاین:' : ($lang === 'ar' ? 'المقاس / الإصدار:' : 'Size / Edition:'); ?></strong> 
                                <span id="selectedSizeLabel" class="selected-val-badge unselected"><?php echo $lang === 'ku' ? 'تکایە هەلبژێرە (پێدڤیە)' : ($lang === 'ar' ? 'يرجى التحديد (إلزامي)' : 'Please select (Required)'); ?></span>
                            </label>
                        </div>
                        
                        <div class="size-buttons-group" id="sizeButtonsContainer">
                            <?php foreach ($product['sizes'] as $i => $size): 
                                $mText = $sizeMeasurements[$size] ?? '';
                            ?>
                                <button type="button" 
                                        class="size-pill" 
                                        data-size="<?php echo htmlspecialchars($size); ?>"
                                        data-measurement="<?php echo htmlspecialchars($mText); ?>"
                                        onclick="onSizeSelected(this, '<?php echo htmlspecialchars(addslashes($size)); ?>')">
                                    <?php echo htmlspecialchars($size); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Height & Width Measurements Display Box Directly Under Size -->
                        <div class="dimension-guide-card" id="sizeMeasurementCard">
                            <div class="dim-card-header">
                                <div class="dim-header-title">
                                    <span class="dim-icon">📐</span>
                                    <span class="dim-title-text" id="dimensionBoxTitle"><?php echo $lang === 'ku' ? 'پیڤانێن بلندی و پانی یێن قیاسی (سم)' : ($lang === 'ar' ? 'أبعاد الطول والعرض للقطعة (سم)' : 'Dimensions Guide (Height & Width)'); ?></span>
                                </div>
                                <button type="button" class="btn-toggle-size-chart" id="toggleSizeChartBtn" onclick="toggleSizeMatrixTable()">
                                    <?php echo $lang === 'ku' ? '📊 خشتێ هەمی قیاسان' : ($lang === 'ar' ? '📊 جدول كافة القياسات' : '📊 View All Sizes Matrix'); ?>
                                </button>
                            </div>

                            <div class="dim-active-display" id="dimActiveDisplay">
                                <p class="dim-placeholder-notice" id="dimPlaceholderNotice">
                                    👉 <?php echo $lang === 'ku' ? 'تکایە قیاسەکێ ل سەر ڤە هەلبژێرە دا کو بلندی و پانی و پیڤانێن دروست ببینی.' : ($lang === 'ar' ? 'انقر على أي مقاس أعلاه لعرض أبعاد الطول والعرض والتفاصيل بدقة.' : 'Select a size above to view its specific Height (Length) & Width (Chest) measurements.'); ?>
                                </p>
                                <div class="dim-chips-grid" id="dimChipsGrid" style="display:none;">
                                    <!-- Populated dynamically on size click -->
                                </div>
                            </div>

                            <!-- Expandable Full Sizing Matrix Table -->
                            <div class="all-sizes-matrix-wrap" id="allSizesMatrixWrap" style="display:none;">
                                <table class="dim-matrix-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo $lang === 'ku' ? 'قیاس' : ($lang === 'ar' ? 'المقاس' : 'Size'); ?></th>
                                            <th><?php echo $lang === 'ku' ? 'بلندی / درێژی' : ($lang === 'ar' ? 'الارتفاع / الطول' : 'Height / Length'); ?></th>
                                            <th><?php echo $lang === 'ku' ? 'پانی / دەورێ سینگی' : ($lang === 'ar' ? 'العرض / الصدر' : 'Width / Chest'); ?></th>
                                            <th><?php echo $lang === 'ku' ? 'هویرکاریێن دی' : ($lang === 'ar' ? 'تفاصيل إضافية' : 'Details / Shoulders'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($product['sizes'] as $sz): 
                                            $mRaw = $sizeMeasurements[$sz] ?? '';
                                            $hVal = '-';
                                            $wVal = '-';
                                            $otherVal = '-';
                                            
                                            if (preg_match('/(?:Length|Height|Jacket|بلندی|درێژی|الطول):\s*([^\•,]+)/i', $mRaw, $mH)) {
                                                $hVal = trim($mH[1]);
                                            }
                                            if (preg_match('/(?:Chest|Width|Trousers|پانی|الصدر|العرض):\s*([^\•,]+)/i', $mRaw, $mW)) {
                                                $wVal = trim($mW[1]);
                                            }
                                            if (preg_match('/(?:Shoulder|Strap|Sleeve|مل|الكتف):\s*([^\•,]+)/i', $mRaw, $mO)) {
                                                $otherVal = trim($mO[1]);
                                            }
                                            if ($hVal === '-' && $wVal === '-') {
                                                $hVal = $mRaw ?: 'Standard fit';
                                            }
                                        ?>
                                            <tr id="matrixRow_<?php echo htmlspecialchars(preg_replace('/[^a-zA-Z0-9]/', '', $sz)); ?>">
                                                <td><strong class="matrix-sz-badge"><?php echo htmlspecialchars($sz); ?></strong></td>
                                                <td><?php echo htmlspecialchars($hVal); ?></td>
                                                <td><?php echo htmlspecialchars($wVal); ?></td>
                                                <td><?php echo htmlspecialchars($otherVal); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Colors Selector (Manual selection required, switches image on click) -->
                <?php if (!empty($product['colors'])): ?>
                    <div class="option-select-group" id="colorSelectGroup">
                        <div class="option-header-row">
                            <label class="option-label">
                                <strong><?php echo $lang === 'ku' ? 'رەنگ / شێواز:' : ($lang === 'ar' ? 'اللون / الإصدار:' : 'Color / Finish:'); ?></strong> 
                                <span id="selectedColorLabel" class="selected-val-badge unselected"><?php echo $lang === 'ku' ? 'تکایە هەلبژێرە (پێدڤیە)' : ($lang === 'ar' ? 'يرجى التحديد (إلزامي)' : 'Please select (Required)'); ?></span>
                            </label>
                        </div>
                        <div class="color-options-group" id="colorButtonsContainer">
                            <?php foreach ($product['colors'] as $i => $color): 
                                $colImg = $colorImages[$color] ?? $product['image'];
                            ?>
                                <button type="button" 
                                        class="color-badge-pill" 
                                        data-color="<?php echo htmlspecialchars($color); ?>"
                                        data-image="<?php echo htmlspecialchars($colImg); ?>"
                                        onclick="onColorSelected(this, '<?php echo htmlspecialchars(addslashes($color)); ?>', '<?php echo htmlspecialchars(addslashes($colImg)); ?>')">
                                    <span class="color-dot-indicator"></span>
                                    <span class="color-name-text"><?php echo htmlspecialchars($color); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quantity and Add to Cart Action -->
                <div class="purchase-action-row">
                    <div class="quantity-picker">
                        <button type="button" class="qty-btn" onclick="let q = document.getElementById('productQty'); if(parseInt(q.value) > 1) q.value = parseInt(q.value) - 1;">−</button>
                        <input type="number" id="productQty" value="1" min="1" max="<?php echo $product['stock']; ?>" class="qty-input">
                        <button type="button" class="qty-btn" onclick="let q = document.getElementById('productQty'); if(parseInt(q.value) < <?php echo $product['stock']; ?>) q.value = parseInt(q.value) + 1;">+</button>
                    </div>

                    <button type="button" class="btn btn-primary btn-add-cart-lg" id="addToBagMainBtn" onclick="handleProductAction(false)">
                        🛍️ <?php echo t('add_to_cart', $lang); ?>
                    </button>

                    <button type="button" class="btn btn-secondary btn-buy-now-lg" id="buyNowMainBtn" onclick="handleProductAction(true)">
                        ⚡ <?php echo t('buy_now', $lang); ?>
                    </button>
                </div>

                <div class="guarantee-box">
                    <div class="g-item">📦 <?php echo t('features_shipping_title', $lang); ?></div>
                    <div class="g-item">💎 <?php echo t('features_quality_title', $lang); ?></div>
                    <div class="g-item">🛡️ <?php echo t('features_payment_title', $lang); ?></div>
                </div>
            </div>
        </div>

        <!-- Product Details & Specifications Tabs (No Reviews) -->
        <div class="product-tabs-container">
            <div class="tabs-nav">
                <button class="tab-btn active" onclick="switchProductTab('tab-desc', this)">Details & Craftsmanship</button>
                <button class="tab-btn" onclick="switchProductTab('tab-specs', this)">Specifications & Origin</button>
            </div>

            <div class="tab-content active" id="tab-desc">
                <div class="prose-content">
                    <p><?php echo nl2br(htmlspecialchars($descText)); ?></p>
                    <ul class="luxury-features-list">
                        <li>Handcrafted using certified luxury materials and precision finishing.</li>
                        <li>Individually inspected and packaged in an authentic signature collector box.</li>
                        <li>Includes Certificate of Authenticity and international guarantee.</li>
                    </ul>
                </div>
            </div>

            <div class="tab-content" id="tab-specs">
                <table class="specs-table">
                    <tbody>
                        <tr>
                            <td><strong>Category</strong></td>
                            <td><?php echo ucfirst($product['category']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Authenticity</strong></td>
                            <td>100% Genuine Certified Origin</td>
                        </tr>
                        <tr>
                            <td><strong>Packaging</strong></td>
                            <td>Aura Signature Luxury Gift Box & Ribbon</td>
                        </tr>
                        <tr>
                            <td><strong>Origin</strong></td>
                            <td>Geneva / Milan / Paris / Grasse Artisan Workshops</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Related Products Section -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="related-products-section">
                <h2 class="section-main-heading mb-30"><?php echo $lang === 'ku' ? 'بەرهەمێن نێزیک و پەیوەندیدار' : ($lang === 'ar' ? 'منتجات مشابهة قد تنال إعجابك' : 'You May Also Adore'); ?></h2>
                <div class="products-grid">
                    <?php foreach ($relatedProducts as $item): 
                        $tTitle = is_array($item['title']) ? ($item['title'][$lang] ?? $item['title']['en']) : $item['title'];
                    ?>
                    <div class="product-card">
                        <div class="product-image-container">
                            <a href="product.php?id=<?php echo $item['id']; ?>">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($tTitle); ?>" class="product-thumb">
                            </a>
                        </div>
                        <div class="product-details">
                            <h3 class="product-title"><a href="product.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($tTitle); ?></a></h3>
                            <div class="product-price-row">
                                <span class="current-price"><?php echo number_format($item['price']); ?> IQD</span>
                                <button class="btn-add-cart-mini" onclick="window.AuraStore.addToCart(<?php echo $item['id']; ?>)">+ Add</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
window.selectedProductSize = null;
window.selectedProductColor = null;
window.productSizeMeasurements = <?php echo json_encode($sizeMeasurements); ?>;
window.productColorImages = <?php echo json_encode($colorImages); ?>;

function switchProductTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(tb => tb.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}

function switchMainImage(imgUrl, thumbBtn) {
    const mainImg = document.getElementById('mainProductImage');
    if (!mainImg || !imgUrl) return;

    mainImg.style.opacity = '0.3';
    mainImg.style.transform = 'scale(0.98)';
    setTimeout(() => {
        mainImg.src = imgUrl;
        mainImg.style.opacity = '1';
        mainImg.style.transform = 'scale(1)';
    }, 150);

    if (thumbBtn) {
        document.querySelectorAll('.thumb-btn').forEach(b => b.classList.remove('active'));
        thumbBtn.classList.add('active');
    }
}

function onSizeSelected(btn, sizeName) {
    // 1. Remove active state from all size buttons
    document.querySelectorAll('.size-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    window.selectedProductSize = sizeName;
    
    // 2. Update label
    const label = document.getElementById('selectedSizeLabel');
    if (label) {
        label.innerText = sizeName;
        label.classList.remove('unselected');
        label.classList.add('selected');
    }
    
    // 3. Clear error highlights if any
    const sizeGroup = document.getElementById('sizeSelectGroup');
    if (sizeGroup) {
        sizeGroup.classList.remove('has-error');
        sizeGroup.classList.remove('option-error-shake');
    }

    // 4. Update Height & Width Measurement box dynamically
    updateDimensionDisplay(sizeName, btn.getAttribute('data-measurement') || '');

    // Highlight row in matrix table if open
    document.querySelectorAll('.dim-matrix-table tbody tr').forEach(r => r.classList.remove('highlighted'));
    const safeKey = sizeName.replace(/[^a-zA-Z0-9]/g, '');
    const targetRow = document.getElementById('matrixRow_' + safeKey);
    if (targetRow) targetRow.classList.add('highlighted');
}

function updateDimensionDisplay(sizeName, measurementStr) {
    const placeholder = document.getElementById('dimPlaceholderNotice');
    const chipsGrid = document.getElementById('dimChipsGrid');
    if (!chipsGrid) return;

    if (placeholder) placeholder.style.display = 'none';
    chipsGrid.style.display = 'grid';

    let height = '';
    let width = '';
    let extra = '';

    // RegEx patterns to extract height / length / width / chest
    const lenMatch = measurementStr.match(/(?:Length|Height|Jacket|بلندی|درێژی|الطول):\s*([^\•,]+)/i);
    const widthMatch = measurementStr.match(/(?:Chest|Width|Trousers|پانی|الصدر|العرض):\s*([^\•,]+)/i);
    const extraMatch = measurementStr.match(/(?:Shoulder|Strap|Sleeve|مل|الكتف):\s*([^\•,]+)/i);

    if (lenMatch) height = lenMatch[1].trim();
    if (widthMatch) width = widthMatch[1].trim();
    if (extraMatch) extra = extraMatch[1].trim();

    if (!height && !width) {
        height = measurementStr || 'Tailored luxury fit';
    }

    const isKu = window.AURA_LANG === 'ku';
    const isAr = window.AURA_LANG === 'ar';

    const lblHeight = isKu ? 'بلندی / درێژی' : (isAr ? 'الارتفاع / الطول' : 'Height / Length');
    const lblWidth = isKu ? 'پانی / دەورێ سینگی' : (isAr ? 'العرض / الصدر' : 'Width / Chest');
    const lblExtra = isKu ? 'مل / هویرکاری' : (isAr ? 'الكتف / التفاصيل' : 'Shoulders / Details');
    const lblActiveSz = isKu ? 'قیاسێ هەلبژارتی:' : (isAr ? 'المقاس المختار:' : 'Selected Size:');

    let html = `
        <div class="dim-chip dim-chip-size">
            <span class="dim-chip-lbl">${lblActiveSz}</span>
            <span class="dim-chip-val font-bold highlight">${sizeName}</span>
        </div>
    `;

    if (height) {
        html += `
            <div class="dim-chip">
                <span class="dim-chip-lbl">📐 ${lblHeight}</span>
                <span class="dim-chip-val font-bold">${height}</span>
            </div>
        `;
    }

    if (width) {
        html += `
            <div class="dim-chip">
                <span class="dim-chip-lbl">↔️ ${lblWidth}</span>
                <span class="dim-chip-val font-bold">${width}</span>
            </div>
        `;
    }

    if (extra) {
        html += `
            <div class="dim-chip">
                <span class="dim-chip-lbl">📏 ${lblExtra}</span>
                <span class="dim-chip-val font-bold">${extra}</span>
            </div>
        `;
    }

    chipsGrid.innerHTML = html;
}

function toggleSizeMatrixTable() {
    const wrap = document.getElementById('allSizesMatrixWrap');
    const btn = document.getElementById('toggleSizeChartBtn');
    if (!wrap) return;

    const isHidden = wrap.style.display === 'none' || wrap.style.display === '';
    wrap.style.display = isHidden ? 'block' : 'none';
    
    if (btn) {
        const isKu = window.AURA_LANG === 'ku';
        const isAr = window.AURA_LANG === 'ar';
        if (isHidden) {
            btn.innerText = isKu ? '✕ ڤەشارتنا خشتەی' : (isAr ? '✕ إخفاء الجدول' : '✕ Hide Sizing Matrix');
        } else {
            btn.innerText = isKu ? '📊 خشتێ هەمی قیاسان' : (isAr ? '📊 جدول كافة القياسات' : '📊 View All Sizes Matrix');
        }
    }
}

function onColorSelected(btn, colorName, imageUrl) {
    // 1. Remove active state from all color buttons
    document.querySelectorAll('.color-badge-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    window.selectedProductColor = colorName;
    
    // 2. Update label
    const label = document.getElementById('selectedColorLabel');
    if (label) {
        label.innerText = colorName;
        label.classList.remove('unselected');
        label.classList.add('selected');
    }

    // 3. Clear error state
    const colorGroup = document.getElementById('colorSelectGroup');
    if (colorGroup) {
        colorGroup.classList.remove('has-error');
        colorGroup.classList.remove('option-error-shake');
    }

    // 4. Switch the main product image to the exact color image
    const mainImg = document.getElementById('mainProductImage');
    if (mainImg && imageUrl) {
        mainImg.style.opacity = '0.3';
        mainImg.style.transform = 'scale(0.98)';
        setTimeout(() => {
            mainImg.src = imageUrl;
            mainImg.style.opacity = '1';
            mainImg.style.transform = 'scale(1)';
        }, 150);
    }

    // 5. Sync thumbnail button active state
    document.querySelectorAll('.thumb-btn').forEach(tb => {
        const tImg = tb.querySelector('img');
        if (tImg && tImg.getAttribute('src') === imageUrl) {
            tb.classList.add('active');
        } else {
            tb.classList.remove('active');
        }
    });

    // 6. Provide brief helpful toast notification
    const isKu = window.AURA_LANG === 'ku';
    const isAr = window.AURA_LANG === 'ar';
    const msg = isKu ? `رەنگ هاتە گوهۆڕین بۆ: ${colorName}` : (isAr ? `تم تبديل العرض للون: ${colorName}` : `Viewing color: ${colorName}`);
    if (window.AuraStore && window.AuraStore.showToast) {
        window.AuraStore.showToast(msg, 'info');
    }
}

function handleProductAction(buyNow = false) {
    const hasSizes = <?= !empty($product['sizes']) ? 'true' : 'false' ?>;
    const hasColors = <?= !empty($product['colors']) ? 'true' : 'false' ?>;
    
    const sizeGroup = document.getElementById('sizeSelectGroup');
    const colorGroup = document.getElementById('colorSelectGroup');
    
    const isKu = window.AURA_LANG === 'ku';
    const isAr = window.AURA_LANG === 'ar';

    if (hasSizes && !window.selectedProductSize) {
        if (sizeGroup) {
            sizeGroup.classList.add('option-error-shake');
            sizeGroup.classList.add('has-error');
            sizeGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => sizeGroup.classList.remove('option-error-shake'), 700);
        }
        const msg = isKu ? '⚠️ تکایە قیاسێ خۆ هەلبژێرە بەری زێدەکرنێ بۆ سەبەتێ' : (isAr ? '⚠️ يرجى تحديد المقاس المطلوب أولاً' : '⚠️ Please select a size before adding to bag');
        if (window.AuraStore && window.AuraStore.showToast) {
            window.AuraStore.showToast(msg, 'error');
        }
        return;
    }
    
    if (hasColors && !window.selectedProductColor) {
        if (colorGroup) {
            colorGroup.classList.add('option-error-shake');
            colorGroup.classList.add('has-error');
            colorGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => colorGroup.classList.remove('option-error-shake'), 700);
        }
        const msg = isKu ? '⚠️ تکایە رەنگێ بەرهەمی هەلبژێرە بەری زێدەکرنێ بۆ سەبەتێ' : (isAr ? '⚠️ يرجى اختيار اللون المطلوب أولاً' : '⚠️ Please select a color before adding to bag');
        if (window.AuraStore && window.AuraStore.showToast) {
            window.AuraStore.showToast(msg, 'error');
        }
        return;
    }
    
    const qtyInput = document.getElementById('productQty');
    const qty = parseInt(qtyInput ? qtyInput.value : '1', 10) || 1;
    
    window.AuraStore.addToCart(<?= (int)$product['id'] ?>, qty, window.selectedProductSize || '', window.selectedProductColor || '');
    
    if (buyNow) {
        setTimeout(() => {
            window.location.href = 'checkout.php';
        }, 300);
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
