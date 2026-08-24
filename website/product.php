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

// Handle Review Submission (if POSTed)
$reviewSuccessMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $reviewerName = trim($_POST['reviewer_name'] ?? 'Anonymous Guest');
    $rating = intval($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');
    
    if (!empty($comment)) {
        $reviewsData = read_json_db('reviews.json');
        $reviewsList = $reviewsData['reviews'] ?? [];
        $newReview = [
            'id' => count($reviewsList) + 1,
            'product_id' => $productId,
            'user_name' => htmlspecialchars($reviewerName),
            'rating' => $rating,
            'comment' => htmlspecialchars($comment),
            'date' => date('Y-m-d')
        ];
        array_unshift($reviewsList, $newReview);
        $reviewsData['reviews'] = $reviewsList;
        write_json_db('reviews.json', $reviewsData);
        $reviewSuccessMsg = $lang === 'ku' ? 'سوپاس بۆ تە! بۆچوونا تە ب سەرکەفتیانە هاتە تۆمارکرن.' : ($lang === 'ar' ? 'شكراً لك! تم إرسال تقييمك بنجاح.' : 'Thank you! Your review has been submitted.');
    }
}

// Fetch Reviews for this product
$reviewsData = read_json_db('reviews.json');
$productReviews = array_filter($reviewsData['reviews'] ?? [], fn($r) => $r['product_id'] == $productId);
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
        
        <?php if (!empty($reviewSuccessMsg)): ?>
            <div class="alert alert-success mb-30"><?php echo $reviewSuccessMsg; ?></div>
        <?php endif; ?>

        <div class="product-view-grid">
            
            <!-- Gallery Images (Main + Thumbnails) -->
            <div class="product-gallery">
                <div class="gallery-main-wrap">
                    <?php if (!empty($badgeText)): ?>
                        <span class="product-badge-tag"><?php echo htmlspecialchars($badgeText); ?></span>
                    <?php endif; ?>
                    <img id="mainProductImage" src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($titleText); ?>" class="gallery-main-img">
                </div>

                <?php if (!empty($product['images']) && count($product['images']) > 1): ?>
                    <div class="gallery-thumbs-row">
                        <?php foreach ($product['images'] as $idx => $imgUrl): ?>
                            <button class="thumb-btn <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="document.getElementById('mainProductImage').src = '<?php echo htmlspecialchars($imgUrl); ?>'; document.querySelectorAll('.thumb-btn').forEach(b => b.classList.remove('active')); this.classList.add('active');">
                                <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Thumbnail">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Purchase Details & Options -->
            <div class="product-buy-info">
                <span class="product-cat-pill"><?php echo t('filter_' . $product['category'], $lang); ?></span>
                
                <h1 class="single-product-title"><?php echo htmlspecialchars($titleText); ?></h1>

                <div class="single-rating-row">
                    <div class="stars">★★★★★</div>
                    <span class="rating-num"><?php echo number_format($product['rating'], 1); ?></span>
                    <span class="reviews-count-link">(<?php echo count($productReviews) ?: ($product['reviews_count'] ?? 1); ?> <?php echo t('reviews', $lang); ?>)</span>
                    <span class="stock-status in-stock">● <?php echo t('in_stock', $lang); ?> (<?php echo $product['stock']; ?> items)</span>
                </div>

                <div class="single-price-box">
                    <span class="current-price-lg">$<?php echo number_format($product['price'], 2); ?></span>
                    <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
                        <span class="old-price-lg">$<?php echo number_format($product['old_price'], 2); ?></span>
                        <span class="save-badge">Save $<?php echo number_format($product['old_price'] - $product['price'], 2); ?></span>
                    <?php endif; ?>
                </div>

                <div class="product-short-desc">
                    <p><?php echo htmlspecialchars($descText); ?></p>
                </div>

                <!-- Sizes Selector (if available) -->
                <?php if (!empty($product['sizes'])): ?>
                    <div class="option-select-group">
                        <label class="option-label"><strong>Size / Edition:</strong> <span id="selectedSizeLabel"><?php echo htmlspecialchars($product['sizes'][0]); ?></span></label>
                        <div class="size-buttons-group">
                            <?php foreach ($product['sizes'] as $i => $size): ?>
                                <button type="button" class="size-pill <?php echo $i === 0 ? 'active' : ''; ?>" onclick="document.querySelectorAll('.size-pill').forEach(b => b.classList.remove('active')); this.classList.add('active'); document.getElementById('selectedSizeLabel').innerText = '<?php echo htmlspecialchars($size); ?>';">
                                    <?php echo htmlspecialchars($size); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Colors Selector (if available) -->
                <?php if (!empty($product['colors'])): ?>
                    <div class="option-select-group">
                        <label class="option-label"><strong>Color / Finish:</strong> <span id="selectedColorLabel"><?php echo htmlspecialchars($product['colors'][0]); ?></span></label>
                        <div class="color-options-group">
                            <?php foreach ($product['colors'] as $i => $color): ?>
                                <button type="button" class="color-badge-pill <?php echo $i === 0 ? 'active' : ''; ?>" onclick="document.querySelectorAll('.color-badge-pill').forEach(b => b.classList.remove('active')); this.classList.add('active'); document.getElementById('selectedColorLabel').innerText = '<?php echo htmlspecialchars($color); ?>';">
                                    <?php echo htmlspecialchars($color); ?>
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

                    <button type="button" class="btn btn-primary btn-add-cart-lg" onclick="window.AuraStore.addToCart(<?php echo $product['id']; ?>, parseInt(document.getElementById('productQty').value), document.getElementById('selectedSizeLabel')?.innerText, document.getElementById('selectedColorLabel')?.innerText)">
                        🛍️ <?php echo t('add_to_cart', $lang); ?>
                    </button>

                    <button type="button" class="btn btn-secondary btn-buy-now-lg" onclick="window.AuraStore.addToCart(<?php echo $product['id']; ?>, parseInt(document.getElementById('productQty').value)); window.location.href='checkout.php';">
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

        <!-- Product Tabs (Description, Specifications, Reviews) -->
        <div class="product-tabs-container">
            <div class="tabs-nav">
                <button class="tab-btn active" onclick="switchProductTab('tab-desc', this)">Description</button>
                <button class="tab-btn" onclick="switchProductTab('tab-specs', this)">Specifications</button>
                <button class="tab-btn" onclick="switchProductTab('tab-reviews', this)"><?php echo t('reviews', $lang); ?> (<?php echo count($productReviews); ?>)</button>
            </div>

            <div class="tab-content active" id="tab-desc">
                <div class="prose-content">
                    <p><?php echo nl2br(htmlspecialchars($descText)); ?></p>
                    <ul class="luxury-features-list">
                        <li>Handcrafted using premium certified luxury materials.</li>
                        <li>Rigorous artisan inspection before packaging in a satin-lined collector box.</li>
                        <li>Includes official Aura Certificate of Authenticity and international warranty.</li>
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

            <div class="tab-content" id="tab-reviews">
                <div class="reviews-wrapper-grid">
                    <!-- Reviews List -->
                    <div class="reviews-list">
                        <?php if (empty($productReviews)): ?>
                            <p class="text-muted">No reviews yet. Be the first to review this luxury piece!</p>
                        <?php else: ?>
                            <?php foreach ($productReviews as $rev): ?>
                                <div class="review-entry">
                                    <div class="review-head">
                                        <strong><?php echo htmlspecialchars($rev['user_name']); ?></strong>
                                        <div class="stars"><?php echo str_repeat('★', $rev['rating']); ?></div>
                                    </div>
                                    <span class="review-date"><?php echo htmlspecialchars($rev['date']); ?></span>
                                    <p class="review-comment"><?php echo htmlspecialchars($rev['comment']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Add Review Form -->
                    <div class="add-review-box">
                        <h3><?php echo t('write_review', $lang); ?></h3>
                        <form action="product.php?id=<?php echo $productId; ?>" method="POST" class="review-form">
                            <div class="form-group">
                                <label><?php echo t('checkout_name', $lang); ?>:</label>
                                <input type="text" name="reviewer_name" required class="form-control" placeholder="Your Name">
                            </div>

                            <div class="form-group">
                                <label><?php echo t('your_rating', $lang); ?>:</label>
                                <select name="rating" class="form-control">
                                    <option value="5">★★★★★ (5/5) Exceptional</option>
                                    <option value="4">★★★★☆ (4/5) Very Good</option>
                                    <option value="3">★★★☆☆ (3/5) Good</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><?php echo t('your_comment', $lang); ?>:</label>
                                <textarea name="comment" rows="4" required class="form-control" placeholder="<?php echo t('your_comment', $lang); ?>"></textarea>
                            </div>

                            <button type="submit" name="submit_review" class="btn btn-primary w-full"><?php echo t('submit_review', $lang); ?></button>
                        </form>
                    </div>
                </div>
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
                                <span class="current-price">$<?php echo number_format($item['price'], 2); ?></span>
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
function switchProductTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(tb => tb.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
