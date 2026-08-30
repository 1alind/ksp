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

                        <!-- Simple & Clean Height & Width Display Directly Under Size (Clickable to toggle inline guide) -->
                        <div class="size-simple-specs-card" id="sizeSpecsCard" onclick="toggleSizeGuideInline(event)" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); toggleSizeGuideInline(event); }" title="<?php echo $lang === 'ku' ? 'کلیک بکە بۆ دیتنا رێبەرێ قیاسان' : ($lang === 'ar' ? 'انقر لعرض دليل القياسات' : 'Click to view size guide'); ?>">
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
                                <span class="popup-badge-hint" id="sizeGuideChevron">▼</span>
                            </div>
                        </div>

                        <!-- Inline Expandable Size Guide Panel with Massive Breathing Room -->
                        <div class="size-guide-inline-panel" id="sizeGuideInlinePanel" dir="<?php echo $dir; ?>">
                            <div class="inline-guide-header">
                                <div class="modal-title-with-icon">
                                    <span class="modal-ruler-icon">✨</span>
                                    <h3><?php echo t('how_to_measure_title', $lang); ?></h3>
                                </div>
                                <button type="button" class="btn-inline-close" onclick="toggleSizeGuideInline(event)" aria-label="Close">
                                    ✕
                                </button>
                            </div>

                            <div class="inline-guide-body">
                                <!-- Vector Garment Illustration -->
                                <div class="measure-illustration-box">
                                    <div class="measure-svg-wrapper">
                                        <svg class="measure-svg-graphic" viewBox="0 0 460 280" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <defs>
                                                <linearGradient id="luxuryShirtGradInline" x1="0%" y1="0%" x2="100%" y2="100%">
                                                    <stop offset="0%" stop-color="#1e2235" />
                                                    <stop offset="100%" stop-color="#12141f" />
                                                </linearGradient>
                                                <filter id="luxuryGlowInline" x="-20%" y="-20%" width="140%" height="140%">
                                                    <feDropShadow dx="0" dy="6" stdDeviation="10" flood-color="#000000" flood-opacity="0.45" />
                                                </filter>
                                            </defs>
                                            <path d="M 160 40 C 175 32, 200 28, 230 28 C 260 28, 285 32, 300 40 L 375 75 L 340 130 L 300 110 L 300 255 C 300 263, 292 270, 282 270 L 178 270 C 168 270, 160 263, 160 255 L 160 110 L 120 130 L 85 75 Z" 
                                                  fill="url(#luxuryShirtGradInline)" stroke="#3a405a" stroke-width="2" stroke-linejoin="round" filter="url(#luxuryGlowInline)" />
                                            <path d="M 195 40 Q 230 68 265 40" stroke="#dcb348" stroke-width="2" fill="none" />
                                            <path d="M 160 110 L 190 55" stroke="#2a2e42" stroke-width="1.5" stroke-dasharray="4 4" />
                                            <path d="M 300 110 L 270 55" stroke="#2a2e42" stroke-width="1.5" stroke-dasharray="4 4" />
                                            <!-- Width Line -->
                                            <line x1="160" y1="135" x2="300" y2="135" stroke="#dcb348" stroke-width="3.5" />
                                            <circle cx="160" cy="135" r="5" fill="#dcb348" />
                                            <circle cx="300" cy="135" r="5" fill="#dcb348" />
                                            <polygon points="168,130 160,135 168,140" fill="#dcb348" />
                                            <polygon points="292,130 300,135 292,140" fill="#dcb348" />
                                            <g transform="translate(175, 100)">
                                                <rect width="110" height="26" rx="13" fill="#0d1017" stroke="#dcb348" stroke-width="2" />
                                                <text id="inlineSvgWidthText" x="55" y="17" fill="#dcb348" font-size="11.5" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">Width: <?php echo htmlspecialchars($initialWidth); ?></text>
                                            </g>
                                            <!-- Height Line -->
                                            <line x1="178" y1="46" x2="178" y2="270" stroke="#f43f5e" stroke-width="2.5" stroke-dasharray="6 4" />
                                            <circle cx="178" cy="46" r="4" fill="#f43f5e" />
                                            <circle cx="178" cy="270" r="4" fill="#f43f5e" />
                                            <polygon points="173,54 178,46 183,54" fill="#f43f5e" />
                                            <polygon points="173,262 178,270 183,262" fill="#f43f5e" />
                                            <g transform="translate(38, 140)">
                                                <rect width="115" height="24" rx="12" fill="#0d1017" stroke="#f43f5e" stroke-width="1.5" />
                                                <text id="inlineSvgHeightText" x="57" y="16" fill="#f43f5e" font-size="11" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">Height: <?php echo htmlspecialchars($initialHeight); ?></text>
                                            </g>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Steps -->
                                <div class="measure-steps-list">
                                    <div class="measure-step-item">
                                        <span class="step-num">1</span>
                                        <div class="step-text">
                                            <strong><?php echo t('how_to_measure_step1_title', $lang); ?></strong>
                                            <span><?php echo t('how_to_measure_step1_desc', $lang); ?></span>
                                        </div>
                                    </div>
                                    <div class="measure-step-item width-accent">
                                        <span class="step-num">2</span>
                                        <div class="step-text">
                                            <strong><?php echo t('how_to_measure_step2_title', $lang); ?></strong>
                                            <span><?php echo t('how_to_measure_step2_desc', $lang); ?></span>
                                        </div>
                                    </div>
                                    <div class="measure-step-item height-accent">
                                        <span class="step-num">3</span>
                                        <div class="step-text">
                                            <strong><?php echo t('how_to_measure_step3_title', $lang); ?></strong>
                                            <span><?php echo t('how_to_measure_step3_desc', $lang); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Matrix Table -->
                                <?php if (!empty($product['sizes'])): ?>
                                    <div class="modal-matrix-container">
                                        <div class="matrix-heading-wrap">
                                            <span class="matrix-sparkle">✦</span>
                                            <h4><?php echo $lang === 'ku' ? 'خشتێ قیاسێن ڤی بەرهەمی' : ($lang === 'ar' ? 'جدول مقاسات هذا المنتج' : 'Product Size Matrix'); ?></h4>
                                        </div>
                                        <table class="modal-dim-table" id="inlineDimTable">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $lang === 'ku' ? 'قیاس' : ($lang === 'ar' ? 'المقاس' : 'Size'); ?></th>
                                                    <th><?php echo $lang === 'ku' ? 'بلندی' : ($lang === 'ar' ? 'الارتفاع' : 'Height'); ?></th>
                                                    <th><?php echo $lang === 'ku' ? 'پانی' : ($lang === 'ar' ? 'العرض' : 'Width'); ?></th>
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
                                                    <tr id="inlineMatrixRow_<?php echo htmlspecialchars($safeKey); ?>" 
                                                        class="clickable-matrix-row"
                                                        onclick="selectSizeFromInline('<?php echo htmlspecialchars(addslashes($sz)); ?>')">
                                                        <td><span class="matrix-sz-pill"><?php echo htmlspecialchars($sz); ?></span></td>
                                                        <td><?php echo htmlspecialchars($hVal); ?></td>
                                                        <td><?php echo htmlspecialchars($wVal); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
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

    // Update diagram SVG badges inside inline panel
    const svgW = document.getElementById('inlineSvgWidthText');
    const svgH = document.getElementById('inlineSvgHeightText');
    if (svgW) svgW.textContent = 'Width: ' + dims.width;
    if (svgH) svgH.textContent = 'Height: ' + dims.height;

    // Highlight row in inline matrix table
    document.querySelectorAll('#inlineDimTable tbody tr').forEach(r => r.classList.remove('highlighted'));
    const safeKey = sizeName.replace(/[^a-zA-Z0-9]/g, '');
    const targetRow = document.getElementById('inlineMatrixRow_' + safeKey);
    if (targetRow) {
        targetRow.classList.add('highlighted');
        try { targetRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch(e){}
    }
}

function selectSizeFromInline(sizeName) {
    const btn = document.querySelector(`.size-pill[data-size="${sizeName}"]`);
    if (btn) {
        onSizeSelected(btn, sizeName);
    } else {
        window.selectedProductSize = sizeName;
    }
}

function toggleSizeGuideInline(e) {
    if (e) {
        if (e.preventDefault) e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
    }
    const panel = document.getElementById('sizeGuideInlinePanel');
    if (!panel) return;
    const isOpen = panel.classList.contains('open');
    if (isOpen) {
        panel.classList.remove('open');
    } else {
        panel.classList.add('open');
        // Synchronize table highlighting
        const activeBtn = document.querySelector('.size-pill.active');
        const selectedSize = activeBtn ? activeBtn.getAttribute('data-size') : (window.selectedProductSize || '');
        if (selectedSize) {
            document.querySelectorAll('#inlineDimTable tbody tr').forEach(r => r.classList.remove('highlighted'));
            const safeKey = selectedSize.replace(/[^a-zA-Z0-9]/g, '');
            const targetRow = document.getElementById('inlineMatrixRow_' + safeKey);
            if (targetRow) {
                targetRow.classList.add('highlighted');
                try { targetRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch(err){}
            }
        }
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
