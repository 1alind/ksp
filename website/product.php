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
        $cleanSz = strtoupper(trim($sz));
        if ($product['category'] === 'clothes') {
            if ($cleanSz === 'S') $sizeMeasurements[$sz] = 'Height: 65cm • Width: 45cm';
            elseif ($cleanSz === 'M') $sizeMeasurements[$sz] = 'Height: 70cm • Width: 50cm';
            elseif ($cleanSz === 'L') $sizeMeasurements[$sz] = 'Height: 73cm • Width: 54cm';
            elseif ($cleanSz === 'XL') $sizeMeasurements[$sz] = 'Height: 76cm • Width: 58cm';
            elseif ($cleanSz === 'XXL' || $cleanSz === '2XL') $sizeMeasurements[$sz] = 'Height: 79cm • Width: 62cm';
            elseif ($cleanSz === 'XS') $sizeMeasurements[$sz] = 'Height: 62cm • Width: 42cm';
            else $sizeMeasurements[$sz] = 'Height: 68cm • Width: 48cm';
        } elseif ($product['category'] === 'watches') {
            $sizeMeasurements[$sz] = 'Height: ' . $sz . ' • Width: 20mm';
        } else {
            $sizeMeasurements[$sz] = 'Height: 65cm • Width: 45cm';
        }
    }
}

$firstSize = !empty($product['sizes']) ? $product['sizes'][0] : 'Standard';
$firstSizeRaw = $sizeMeasurements[$firstSize] ?? '';
$initialHeight = '65cm';
$initialWidth = '45cm';
if (preg_match('/(?:Length|Height|Jacket|بلندی|درێژی|الطول)[:\s]*([0-9.]+\s*(?:cm|mm)?)/i', $firstSizeRaw, $mH)) {
    $initialHeight = str_replace(' ', '', strtolower(trim($mH[1])));
    if (!str_ends_with($initialHeight, 'cm') && !str_ends_with($initialHeight, 'mm')) $initialHeight .= 'cm';
}
if (preg_match('/(?:Width|Chest|Trousers|پانی|الصدر|العرض)[:\s]*([0-9.]+\s*(?:cm|mm)?)/i', $firstSizeRaw, $mW)) {
    $initialWidth = str_replace(' ', '', strtolower(trim($mW[1])));
    if (!str_ends_with($initialWidth, 'cm') && !str_ends_with($initialWidth, 'mm')) $initialWidth .= 'cm';
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

                        <!-- Simple & Clean Height & Width Display Directly Under Size (Clickable to open popup) -->
                        <div class="size-simple-specs-card" id="sizeSpecsCard" onclick="openSizeGuideModal(event)" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); openSizeGuideModal(event); }" title="<?php echo $lang === 'ku' ? 'کلیک بکە بۆ دیتنا رێبەرێ قیاسان' : ($lang === 'ar' ? 'انقر لعرض دليل القياسات' : 'Click to view size guide'); ?>">
                            <div class="size-specs-display">
                                <div class="size-spec-row">
                                    <span class="size-spec-label"><?php echo $lang === 'ku' ? 'بلندی:' : ($lang === 'ar' ? 'الارتفاع:' : 'Height:'); ?></span>
                                    <span class="size-spec-val" id="displaySizeHeight"><?php echo htmlspecialchars($initialHeight); ?></span>
                                </div>
                                <div class="size-spec-row">
                                    <span class="size-spec-label"><?php echo $lang === 'ku' ? 'پانی:' : ($lang === 'ar' ? 'العرض:' : 'Width:'); ?></span>
                                    <span class="size-spec-val" id="displaySizeWidth"><?php echo htmlspecialchars($initialWidth); ?></span>
                                </div>
                            </div>
                            
                            <div class="btn-how-to-know-size" id="btnHowToKnowSize">
                                <span class="how-icon">📏</span>
                                <span class="how-text"><?php echo t('how_to_know_size', $lang); ?></span>
                                <span class="popup-badge-hint">↗</span>
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

<!-- Size Guide & How to Measure Modal (Clean, Simple, with Visual Illustration) -->
<div class="size-guide-modal-overlay" id="sizeGuideModal" onclick="if(event.target === this) closeSizeGuideModal();" style="display:none;">
    <div class="size-guide-modal-dialog">
        <div class="size-guide-modal-header">
            <div class="modal-title-with-icon">
                <span class="modal-ruler-icon">📏</span>
                <h3><?php echo t('how_to_measure_title', $lang); ?></h3>
            </div>
            <button type="button" class="btn-modal-close" onclick="closeSizeGuideModal()" aria-label="Close">✕</button>
        </div>

        <div class="size-guide-modal-body">
            <!-- Crisp Vector Diagram Illustration of How Measurements Are Done -->
            <div class="measure-illustration-box">
                <div class="measure-svg-wrapper">
                    <svg class="measure-svg-graphic" viewBox="0 0 460 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="shirtGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="var(--bg-card, #191c28)" />
                                <stop offset="100%" stop-color="var(--bg-surface, #12141f)" />
                            </linearGradient>
                            <filter id="subtleShadow" x="-10%" y="-10%" width="120%" height="120%">
                                <feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#000000" flood-opacity="0.3" />
                            </filter>
                        </defs>

                        <!-- Garment Background Silhouette -->
                        <path d="M 155 55 L 195 72 Q 230 84 265 72 L 305 55 L 370 115 L 335 150 L 305 125 L 305 285 L 155 285 L 155 125 L 125 150 L 90 115 Z" 
                              fill="url(#shirtGrad)" stroke="var(--border-color, #2a2e42)" stroke-width="2.5" stroke-linejoin="round" filter="url(#subtleShadow)" />
                        
                        <!-- Collar Curved Detail -->
                        <path d="M 195 72 Q 230 100 265 72" stroke="var(--border-color, #383e58)" stroke-width="2" fill="none" />
                        <!-- Left sleeve seam -->
                        <path d="M 155 125 L 190 70" stroke="var(--border-color, #2a2e42)" stroke-width="1.5" stroke-dasharray="3 3" />
                        <!-- Right sleeve seam -->
                        <path d="M 305 125 L 270 70" stroke="var(--border-color, #2a2e42)" stroke-width="1.5" stroke-dasharray="3 3" />

                        <!-- Horizontal Width Indicator (Chest: Armpit to Armpit) -->
                        <line x1="155" y1="135" x2="305" y2="135" stroke="#dcb348" stroke-width="2.5" stroke-dasharray="5 3" />
                        <circle cx="155" cy="135" r="5" fill="#dcb348" />
                        <circle cx="305" cy="135" r="5" fill="#dcb348" />
                        <!-- Arrow tips for width -->
                        <polygon points="163,130 155,135 163,140" fill="#dcb348" />
                        <polygon points="297,130 305,135 297,140" fill="#dcb348" />

                        <!-- Width Measurement Badge -->
                        <g transform="translate(180, 115)">
                            <rect width="100" height="26" rx="13" fill="#0d1017" stroke="#dcb348" stroke-width="1.5" />
                            <text id="modalSvgWidthText" x="50" y="17" fill="#dcb348" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">Width: <?php echo htmlspecialchars($initialWidth); ?></text>
                        </g>

                        <!-- Vertical Height Indicator (Length: Shoulder Collar Seam straight to Hem) -->
                        <line x1="175" y1="65" x2="175" y2="285" stroke="#ef4444" stroke-width="2.5" stroke-dasharray="5 3" />
                        <circle cx="175" cy="65" r="5" fill="#ef4444" />
                        <circle cx="175" cy="285" r="5" fill="#ef4444" />
                        <!-- Arrow tips for height -->
                        <polygon points="170,74 175,65 180,74" fill="#ef4444" />
                        <polygon points="170,276 175,285 180,276" fill="#ef4444" />

                        <!-- Height Measurement Badge -->
                        <g transform="translate(45, 160)">
                            <rect width="105" height="26" rx="13" fill="#0d1017" stroke="#ef4444" stroke-width="1.5" />
                            <text id="modalSvgHeightText" x="52" y="17" fill="#ef4444" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">Height: <?php echo htmlspecialchars($initialHeight); ?></text>
                        </g>
                    </svg>
                </div>
            </div>

            <!-- 3 Clear Measuring Steps -->
            <div class="measure-steps-list">
                <div class="measure-step-item">
                    <div class="step-num-circle">1</div>
                    <div class="step-text-wrap">
                        <strong><?php echo t('how_to_measure_step1_title', $lang); ?></strong>
                        <p><?php echo t('how_to_measure_step1_desc', $lang); ?></p>
                    </div>
                </div>

                <div class="measure-step-item step-width-accent">
                    <div class="step-num-circle">2</div>
                    <div class="step-text-wrap">
                        <strong><?php echo t('how_to_measure_step2_title', $lang); ?></strong>
                        <p><?php echo t('how_to_measure_step2_desc', $lang); ?></p>
                    </div>
                </div>

                <div class="measure-step-item step-height-accent">
                    <div class="step-num-circle">3</div>
                    <div class="step-text-wrap">
                        <strong><?php echo t('how_to_measure_step3_title', $lang); ?></strong>
                        <p><?php echo t('how_to_measure_step3_desc', $lang); ?></p>
                    </div>
                </div>
            </div>

            <!-- Complete Dimensions Matrix Reference Table -->
            <?php if (!empty($product['sizes'])): ?>
                <div class="modal-matrix-container">
                    <h4 class="modal-matrix-heading">📊 <?php echo $lang === 'ku' ? 'خشتێ قیاسێن ڤی بەرهەمی (کلیک بکە بۆ دەستنیشانکرنێ)' : ($lang === 'ar' ? 'جدول كافة القياسات لهذا المنتج (انقر للتحديد)' : 'Available Sizes for this Product (Click row to select)'); ?></h4>
                    <table class="modal-dim-table">
                        <thead>
                            <tr>
                                <th><?php echo $lang === 'ku' ? 'قیاس' : ($lang === 'ar' ? 'المقاس' : 'Size'); ?></th>
                                <th><?php echo $lang === 'ku' ? 'بلندی' : ($lang === 'ar' ? 'الارتفاع / الطول' : 'Height'); ?></th>
                                <th><?php echo $lang === 'ku' ? 'پانی' : ($lang === 'ar' ? 'العرض / الصدر' : 'Width'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($product['sizes'] as $sz): 
                                $mRaw = $sizeMeasurements[$sz] ?? '';
                                $hVal = '65cm';
                                $wVal = '45cm';
                                if (preg_match('/(?:Length|Height|Jacket|بلندی|درێژی|الطول):\s*([^\•,]+)/i', $mRaw, $mH)) {
                                    $hVal = trim($mH[1]);
                                }
                                if (preg_match('/(?:Chest|Width|Trousers|پانی|الصدر|العرض):\s*([^\•,]+)/i', $mRaw, $mW)) {
                                    $wVal = trim($mW[1]);
                                }
                                $safeKey = preg_replace('/[^a-zA-Z0-9]/', '', $sz);
                            ?>
                                <tr id="modalMatrixRow_<?php echo htmlspecialchars($safeKey); ?>" 
                                    class="clickable-matrix-row"
                                    onclick="selectSizeFromModal('<?php echo htmlspecialchars(addslashes($sz)); ?>')">
                                    <td><strong class="matrix-sz-pill"><?php echo htmlspecialchars($sz); ?></strong></td>
                                    <td><?php echo htmlspecialchars($hVal); ?></td>
                                    <td><?php echo htmlspecialchars($wVal); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="size-guide-modal-footer">
            <button type="button" class="btn-modal-got-it" onclick="closeSizeGuideModal()">
                ✓ <?php echo $lang === 'ku' ? 'تەمامە / دەستنیشانکرنا قیاسی' : ($lang === 'ar' ? 'حسناً / اختيار المقاس' : 'Got it / Select Size'); ?>
            </button>
        </div>
    </div>
</div>

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

function extractDimensionValues(mStr, sizeName) {
    let height = '65cm';
    let width = '45cm';

    if (mStr) {
        const hMatch = mStr.match(/(?:Length|Height|Jacket|بلندی|درێژی|الطول)[:\s]*([0-9.]+\s*(?:cm|mm)?)/i);
        if (hMatch) {
            height = hMatch[1].replace(/\s+/g, '').toLowerCase();
            if (!height.endsWith('cm') && !height.endsWith('mm')) height += 'cm';
        }

        const wMatch = mStr.match(/(?:Width|Chest|Trousers|پانی|الصدر|العرض)[:\s]*([0-9.]+\s*(?:cm|mm)?)/i);
        if (wMatch) {
            width = wMatch[1].replace(/\s+/g, '').toLowerCase();
            if (!width.endsWith('cm') && !width.endsWith('mm')) width += 'cm';
        }
    }

    if (sizeName) {
        const sz = String(sizeName).toUpperCase().trim();
        if (sz === 'S') { height = '65cm'; width = '45cm'; }
        else if (sz === 'M') { height = '70cm'; width = '50cm'; }
        else if (sz === 'L') { height = '73cm'; width = '54cm'; }
        else if (sz === 'XL') { height = '76cm'; width = '58cm'; }
        else if (sz === 'XXL' || sz === '2XL') { height = '79cm'; width = '62cm'; }
        else if (sz === 'XS') { height = '62cm'; width = '42cm'; }
        else if (sz.includes('MM')) { height = sz.toLowerCase(); width = '20mm'; }
    }

    return { height, width };
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

    // 4. Update simple Height: ... Width: ... lines
    const mStr = btn.getAttribute('data-measurement') || '';
    const dims = extractDimensionValues(mStr, sizeName);

    const hEl = document.getElementById('displaySizeHeight');
    const wEl = document.getElementById('displaySizeWidth');
    if (hEl) {
        hEl.innerText = dims.height;
        hEl.classList.add('spec-highlight-flash');
        setTimeout(() => hEl.classList.remove('spec-highlight-flash'), 400);
    }
    if (wEl) {
        wEl.innerText = dims.width;
        wEl.classList.add('spec-highlight-flash');
        setTimeout(() => wEl.classList.remove('spec-highlight-flash'), 400);
    }

    // Update diagram SVG badges inside popup
    const svgW = document.getElementById('modalSvgWidthText');
    const svgH = document.getElementById('modalSvgHeightText');
    if (svgW) svgW.textContent = 'Width: ' + dims.width;
    if (svgH) svgH.textContent = 'Height: ' + dims.height;

    // Highlight row in modal matrix table
    document.querySelectorAll('.modal-dim-table tbody tr').forEach(r => r.classList.remove('highlighted'));
    const safeKey = sizeName.replace(/[^a-zA-Z0-9]/g, '');
    const targetRow = document.getElementById('modalMatrixRow_' + safeKey);
    if (targetRow) {
        targetRow.classList.add('highlighted');
        try { targetRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch(e){}
    }
}

function selectSizeFromModal(sizeName) {
    const btn = document.querySelector(`.size-pill[data-size="${sizeName}"]`);
    if (btn) {
        onSizeSelected(btn, sizeName);
    } else {
        window.selectedProductSize = sizeName;
    }
    closeSizeGuideModal();
}

function openSizeGuideModal(e) {
    if (e) {
        if (e.preventDefault) e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
    }
    const modal = document.getElementById('sizeGuideModal');
    if (!modal) return;

    // Detach from parent section and append directly to body to avoid clipping or coordinate issues
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    modal.classList.add('open');
    modal.classList.add('active');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Synchronize diagram and table highlighting
    const activeBtn = document.querySelector('.size-pill.active');
    const selectedSize = activeBtn ? activeBtn.getAttribute('data-size') : (window.selectedProductSize || '');
    if (selectedSize) {
        document.querySelectorAll('.modal-dim-table tbody tr').forEach(r => r.classList.remove('highlighted'));
        const safeKey = selectedSize.replace(/[^a-zA-Z0-9]/g, '');
        const targetRow = document.getElementById('modalMatrixRow_' + safeKey);
        if (targetRow) {
            targetRow.classList.add('highlighted');
            try { targetRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch(err){}
        }
    }
}

function closeSizeGuideModal(e) {
    if (e) {
        if (e.preventDefault) e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
    }
    const modal = document.getElementById('sizeGuideModal');
    if (modal) {
        modal.classList.remove('open');
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Close modal on Escape key press
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
        closeSizeGuideModal();
    }
});

// Auto-relocate modal on document ready
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('sizeGuideModal');
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
});

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
