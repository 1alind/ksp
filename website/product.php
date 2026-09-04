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

$linkedVariants = get_linked_color_variants($product);

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
        if (empty($sizeMeasurements[$sz])) {
            if ($product['category'] === 'clothes') {
                if ($cleanSz === 'XS') $sizeMeasurements[$sz] = 'Height: 62cm • Width: 42cm';
                elseif ($cleanSz === 'S') $sizeMeasurements[$sz] = 'Height: 65cm • Width: 45cm';
                elseif ($cleanSz === 'M') $sizeMeasurements[$sz] = 'Height: 70cm • Width: 50cm';
                elseif ($cleanSz === 'L') $sizeMeasurements[$sz] = 'Height: 73cm • Width: 54cm';
                elseif ($cleanSz === 'XL') $sizeMeasurements[$sz] = 'Height: 76cm • Width: 58cm';
                elseif ($cleanSz === 'XXL' || $cleanSz === '2XL') $sizeMeasurements[$sz] = 'Height: 79cm • Width: 62cm';
                elseif ($cleanSz === '3XL' || $cleanSz === 'XXXL') $sizeMeasurements[$sz] = 'Height: 82cm • Width: 66cm';
                elseif ($cleanSz === '4XL') $sizeMeasurements[$sz] = 'Height: 85cm • Width: 70cm';
                elseif ($cleanSz === '5XL') $sizeMeasurements[$sz] = 'Height: 88cm • Width: 74cm';
                else $sizeMeasurements[$sz] = 'Height: 70cm • Width: 50cm';
            } elseif ($product['category'] === 'watches') {
                $sizeMeasurements[$sz] = 'Height: ' . $sz . ' • Width: 20mm';
            } else {
                $sizeMeasurements[$sz] = 'Height: 65cm • Width: 45cm';
            }
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

// Map each color to its corresponding image and hex color swatch
$colorImages = [];
$colorHexes = [];
if (!empty($product['colors'])) {
    foreach ($product['colors'] as $i => $c) {
        if (!empty($product['color_images'][$c])) {
            $colorImages[$c] = $product['color_images'][$c];
        } elseif (!empty($product['images'][$i])) {
            $colorImages[$c] = $product['images'][$i];
        } else {
            $colorImages[$c] = $product['image'];
        }

        if (!empty($product['color_hexes'][$c])) {
            $colorHexes[$c] = $product['color_hexes'][$c];
        } else {
            // Auto-detect common color hexes if not explicitly defined
            $cLower = strtolower($c);
            if (str_contains($cLower, 'white') || str_contains($cLower, 'سپی') || str_contains($cLower, 'أبيض')) $colorHexes[$c] = '#ffffff';
            elseif (str_contains($cLower, 'black') || str_contains($cLower, 'رەش') || str_contains($cLower, 'أسود') || str_contains($cLower, 'obsidian')) $colorHexes[$c] = '#111827';
            elseif (str_contains($cLower, 'blue') || str_contains($cLower, 'شین') || str_contains($cLower, 'أزرق') || str_contains($cLower, 'navy') || str_contains($cLower, 'midnight')) $colorHexes[$c] = '#1e3a8a';
            elseif (str_contains($cLower, 'red') || str_contains($cLower, 'سۆر') || str_contains($cLower, 'أحمر') || str_contains($cLower, 'burgundy') || str_contains($cLower, 'wine')) $colorHexes[$c] = '#881337';
            elseif (str_contains($cLower, 'green') || str_contains($cLower, 'کەسک') || str_contains($cLower, 'أخضر') || str_contains($cLower, 'emerald') || str_contains($cLower, 'olive')) $colorHexes[$c] = '#065f46';
            elseif (str_contains($cLower, 'gold') || str_contains($cLower, 'زێڕ') || str_contains($cLower, 'ذهب') || str_contains($cLower, 'champagne')) $colorHexes[$c] = '#d4af37';
            elseif (str_contains($cLower, 'silver') || str_contains($cLower, 'gray') || str_contains($cLower, 'grey') || str_contains($cLower, 'رصاص')) $colorHexes[$c] = '#64748b';
            elseif (str_contains($cLower, 'brown') || str_contains($cLower, 'قاوە') || str_contains($cLower, 'بني') || str_contains($cLower, 'chocolate')) $colorHexes[$c] = '#78350f';
            elseif (str_contains($cLower, 'beige') || str_contains($cLower, 'بيج') || str_contains($cLower, 'sand') || str_contains($cLower, 'cream')) $colorHexes[$c] = '#f5f5dc';
            else $colorHexes[$c] = !empty($product['color_hex']) ? $product['color_hex'] : '#d4af37';
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

                <!-- Linked Color / Model Variations (For multi-color editions of the same shirt/item) -->
                <?php if (!empty($linkedVariants)): ?>
                    <div class="option-select-group linked-variants-group" id="linkedVariantsGroup">
                        <div class="option-header-row">
                            <label class="option-label">
                                <strong><?php echo $lang === 'ku' ? '🎨 رەنگێن دی یێن ڤی مۆدێلی:' : ($lang === 'ar' ? '🎨 ألوان وإصدارات هذا الموديل:' : '🎨 Colors & Variations of this Model:'); ?></strong> 
                                <span class="selected-val-badge selected">
                                    <?php echo htmlspecialchars(!empty($product['color_name']) ? $product['color_name'] : (!empty($product['colors']) ? $product['colors'][0] : 'Current Color')); ?>
                                </span>
                            </label>
                            <span style="font-size:12px; color:var(--text-muted); font-weight:600;"><?php echo count($linkedVariants); ?> <?php echo $lang === 'ku' ? 'رەنگ بەردەستن' : ($lang === 'ar' ? 'ألوان متوفرة' : 'colors available'); ?></span>
                        </div>
                        
                        <div class="linked-variants-grid">
                            <?php foreach ($linkedVariants as $v): 
                                $vTitle = is_array($v['title']) ? ($v['title'][$lang] ?? $v['title']['en']) : $v['title'];
                                $isCurrent = !empty($v['is_current']);
                                $vHex = !empty($v['color_hex']) ? $v['color_hex'] : '#d4af37';
                            ?>
                                <a href="<?php echo $isCurrent ? 'javascript:void(0)' : 'product.php?id=' . $v['id']; ?>" 
                                   class="linked-variant-card <?php echo $isCurrent ? 'active' : ''; ?>"
                                   title="<?php echo htmlspecialchars($vTitle); ?> — <?php echo htmlspecialchars($v['color_name']); ?>">
                                    <div class="variant-img-wrap">
                                        <img src="<?php echo htmlspecialchars($v['image']); ?>" alt="<?php echo htmlspecialchars($v['color_name']); ?>">
                                        <?php if ($isCurrent): ?>
                                            <span class="current-variant-tag" title="Currently Viewing">✓</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="variant-info">
                                        <div class="variant-color-row">
                                            <span class="variant-dot" style="background-color: <?php echo htmlspecialchars($vHex); ?>;"></span>
                                            <strong class="variant-name"><?php echo htmlspecialchars($v['color_name']); ?></strong>
                                        </div>
                                        <span class="variant-price"><?php echo number_format($v['price']); ?> IQD</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

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

<?php
                        $guideVariant = 'tshirt';
                        $titleLower = strtolower($titleText . ' ' . ($product['category'] ?? ''));
                        if (str_contains($titleLower, 'jean') || str_contains($titleLower, 'pant') || str_contains($titleLower, 'trousers')) {
                            $guideVariant = 'jeans';
                        } elseif (str_contains($titleLower, 'jacket') || str_contains($titleLower, 'hoodie') || str_contains($titleLower, 'coat') || str_contains($titleLower, 'blazer')) {
                            $guideVariant = 'jacket';
                        } elseif (str_contains($titleLower, 'shoe') || str_contains($titleLower, 'sneaker') || str_contains($titleLower, 'boot')) {
                            $guideVariant = 'shoes';
                        }
                        ?>
                        <!-- Simple & Clean Height & Width Display Directly Under Size (Clickable to navigate to standalone size guide page) -->
                        <a href="size_guide.php?v=<?php echo $guideVariant; ?>&pid=<?php echo (int)$productId; ?>&size=<?php echo urlencode($firstSize); ?>&h=<?php echo urlencode($initialHeight); ?>&w=<?php echo urlencode($initialWidth); ?>&from=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="size-simple-specs-card" id="sizeSpecsCard" title="<?php echo $lang === 'ku' ? 'کلیک بکە بۆ دیتنا رێبەرێ قیاسان' : ($lang === 'ar' ? 'انقر لعرض دليل القياسات' : 'Click to view size guide'); ?>">
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
                                <span class="popup-badge-hint" id="sizeGuideChevron">↗</span>
                            </div>
                        </a>
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
                                $colHex = $colorHexes[$color] ?? '#d4af37';
                                $isLight = in_array(strtolower(trim($colHex)), ['#ffffff', '#fff', '#f8fafc', '#f1f5f9', '#f5f5dc', 'white']);
                            ?>
                                <button type="button" 
                                        class="color-badge-pill" 
                                        data-color="<?php echo htmlspecialchars($color); ?>"
                                        data-image="<?php echo htmlspecialchars($colImg); ?>"
                                        onclick="onColorSelected(this, '<?php echo htmlspecialchars(addslashes($color)); ?>', '<?php echo htmlspecialchars(addslashes($colImg)); ?>')">
                                    <span class="color-dot-indicator" style="background-color: <?php echo htmlspecialchars($colHex); ?>; <?php echo $isLight ? 'border:1.5px solid #94a3b8; box-shadow:0 0 0 1px rgba(0,0,0,0.1);' : ''; ?>"></span>
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
    let height = '';
    let width = '';

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

    if (!height || !width) {
        if (sizeName) {
            const sz = String(sizeName).toUpperCase().trim();
            if (sz === 'XS') { if (!height) height = '62cm'; if (!width) width = '42cm'; }
            else if (sz === 'S') { if (!height) height = '65cm'; if (!width) width = '45cm'; }
            else if (sz === 'M') { if (!height) height = '70cm'; if (!width) width = '50cm'; }
            else if (sz === 'L') { if (!height) height = '73cm'; if (!width) width = '54cm'; }
            else if (sz === 'XL') { if (!height) height = '76cm'; if (!width) width = '58cm'; }
            else if (sz === 'XXL' || sz === '2XL') { if (!height) height = '79cm'; if (!width) width = '62cm'; }
            else if (sz === '3XL' || sz === 'XXXL') { if (!height) height = '82cm'; if (!width) width = '66cm'; }
            else if (sz === '4XL') { if (!height) height = '85cm'; if (!width) width = '70cm'; }
            else if (sz === '5XL') { if (!height) height = '88cm'; if (!width) width = '74cm'; }
            else if (sz.includes('MM')) { if (!height) height = sz.toLowerCase(); if (!width) width = '20mm'; }
        }
    }

    if (!height) height = '65cm';
    if (!width) width = '45cm';

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

    // Update size specs card link with chosen size, dimensions, and current URI
    const specsCard = document.getElementById('sizeSpecsCard');
    if (specsCard) {
        const currentUrl = encodeURIComponent(window.location.pathname + window.location.search);
        const pid = '<?php echo (int)$productId; ?>';
        const variant = '<?php echo $guideVariant; ?>';
        specsCard.href = `size_guide.php?v=${variant}&pid=${pid}&size=${encodeURIComponent(sizeName)}&h=${encodeURIComponent(dims.height)}&w=${encodeURIComponent(dims.width)}&from=${currentUrl}`;
    }
}

function selectColor(colorName, btn) {
    const colorBtn = document.querySelector(`.color-badge-pill[data-color="${colorName}"]`);
    if (colorBtn) {
        const img = colorBtn.getAttribute('data-image') || '';
        onColorSelected(colorBtn, colorName, img);
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
    const hasSizes = <?php echo (!empty($product['sizes']) && count($product['sizes']) > 0) ? 'true' : 'false'; ?>;
    const hasColors = <?php echo (!empty($product['colors']) && count($product['colors']) > 0) ? 'true' : 'false'; ?>;
    
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
    const prodId = <?php echo (int)($product['id'] ?? 1); ?>;
    
    window.AuraStore.addToCart(prodId, qty, window.selectedProductSize || '', window.selectedProductColor || '');
    
    if (buyNow) {
        setTimeout(() => {
            window.location.href = 'checkout.php';
        }, 300);
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
