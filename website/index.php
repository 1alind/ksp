<?php
$activePage = 'home';
$pageTitle = null; // Will use default site title
require_once __DIR__ . '/header.php';

$products = get_all_products();
$featuredProducts = array_filter($products, function($p) {
    return !empty($p['featured']);
});
?>

<!-- Curated Collection / Featured Products Section -->
<section class="products-showcase-section">
    <div class="container">
        <div class="showcase-header-flex">
            <div>
                <span class="section-kicker"><?php echo t('trending_title', $lang); ?></span>
                <h2 class="section-main-heading"><?php echo t('trending_subtitle', $lang); ?></h2>
            </div>
            
            <!-- Category Filter Tabs -->
            <div class="category-tabs-nav" id="homeFilterTabs">
                <button class="cat-tab-btn active" data-filter="all"><?php echo t('filter_all', $lang); ?></button>
                <button class="cat-tab-btn" data-filter="clothes"><?php echo t('filter_clothes', $lang); ?></button>
                <button class="cat-tab-btn" data-filter="watches"><?php echo t('filter_watches', $lang); ?></button>
                <button class="cat-tab-btn" data-filter="perfumes"><?php echo t('filter_perfumes', $lang); ?></button>
                <button class="cat-tab-btn" data-filter="accessories"><?php echo t('filter_accessories', $lang); ?></button>
            </div>
        </div>

        <div class="products-grid" id="featuredProductsGrid">
            <?php foreach ($products as $item): 
                $titleText = is_array($item['title']) ? ($item['title'][$lang] ?? $item['title']['en']) : $item['title'];
                $badgeKey = 'badge_' . $lang;
                $badgeText = $item[$badgeKey] ?? $item['badge'] ?? '';
                $itemStock = isset($item['stock']) ? (int)$item['stock'] : 0;
                $itemOutOfStock = ($itemStock <= 0);
            ?>
            <div class="product-card <?php echo $itemOutOfStock ? 'is-out-of-stock' : ''; ?>" data-category="<?php echo $item['category']; ?>" data-id="<?php echo $item['id']; ?>">
                <div class="product-image-container">
                    <?php if ($itemOutOfStock): ?>
                        <span class="product-badge-tag out-of-stock-badge"><?php echo t('out_of_stock', $lang); ?></span>
                    <?php elseif (!empty($badgeText)): ?>
                        <span class="product-badge-tag"><?php echo htmlspecialchars($badgeText); ?></span>
                    <?php endif; ?>
                    
                    <a href="product.php?id=<?php echo $item['id']; ?>" class="product-img-link">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($titleText); ?>" class="product-thumb" loading="lazy">
                    </a>

                    <div class="product-hover-actions">
                        <button class="action-btn-circle quick-view-btn" data-id="<?php echo $item['id']; ?>" title="<?php echo t('quick_view', $lang); ?>">
                            👁️
                        </button>
                        <?php if (!$itemOutOfStock): ?>
                            <button class="action-btn-circle add-cart-btn" data-id="<?php echo $item['id']; ?>" title="<?php echo t('add_to_cart', $lang); ?>">
                                🛍️
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="product-details">
                    <div class="product-meta-row">
                        <span class="product-cat-name"><?php echo t('filter_' . $item['category'], $lang); ?></span>
                        <?php if ($itemOutOfStock): ?>
                            <span class="stock-pill-badge out-of-stock"><?php echo t('out_of_stock', $lang); ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 class="product-title">
                        <a href="product.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($titleText); ?></a>
                    </h3>

                    <div class="product-price-row">
                        <div class="price-wrap">
                            <span class="current-price"><?php echo number_format($item['price']); ?> IQD</span>
                            <?php if (!empty($item['old_price']) && $item['old_price'] > $item['price']): ?>
                                <span class="old-price"><?php echo number_format($item['old_price']); ?> IQD</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($itemOutOfStock): ?>
                            <button class="btn-add-cart-mini disabled-stock" disabled title="<?php echo t('out_of_stock', $lang); ?>">
                                <span><?php echo t('out_of_stock', $lang); ?></span>
                            </button>
                        <?php else: ?>
                            <button class="btn-add-cart-mini" onclick="window.AuraStore.addToCart(<?php echo $item['id']; ?>)">
                                <span>+ <?php echo t('add_to_cart', $lang); ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="showcase-catalog-btn-wrap text-center" style="margin-top: 64px !important; padding-top: 20px !important; display: block; width: 100%;">
            <a href="shop.php" class="btn btn-secondary btn-lg" style="display: inline-flex; align-items: center; gap: 10px; padding: 14px 32px;">
                <span><?php echo t('shop_title', $lang); ?></span>
                <span>→</span>
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
