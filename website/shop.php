<?php
$activePage = 'shop';
$pageTitle = 'Collection & Catalog';
require_once __DIR__ . '/header.php';

$allProducts = get_all_products();
$selectedCat = $_GET['cat'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');
$sortOrder = $_GET['sort'] ?? 'featured';
$maxPriceFilter = floatval($_GET['max_price'] ?? 1500000);

// Filter logic
$filteredProducts = array_filter($allProducts, function($p) use ($selectedCat, $searchQuery, $maxPriceFilter, $lang) {
    // 1. Category check
    if ($selectedCat !== 'all' && $p['category'] !== $selectedCat) {
        return false;
    }
    // 2. Price check
    if ($p['price'] > $maxPriceFilter) {
        return false;
    }
    // 3. Search query check
    if (!empty($searchQuery)) {
        $q = mb_strtolower($searchQuery);
        $titleEn = mb_strtolower(is_array($p['title']) ? ($p['title']['en'] ?? '') : $p['title']);
        $titleAr = mb_strtolower(is_array($p['title']) ? ($p['title']['ar'] ?? '') : '');
        $titleKu = mb_strtolower(is_array($p['title']) ? ($p['title']['ku'] ?? '') : '');
        $category = mb_strtolower($p['category']);
        
        if (strpos($titleEn, $q) === false && strpos($titleAr, $q) === false && strpos($titleKu, $q) === false && strpos($category, $q) === false) {
            return false;
        }
    }
    return true;
});

// Sort logic
usort($filteredProducts, function($a, $b) use ($sortOrder) {
    if ($sortOrder === 'price_low') {
        return $a['price'] <=> $b['price'];
    } elseif ($sortOrder === 'price_high') {
        return $b['price'] <=> $a['price'];
    } elseif ($sortOrder === 'rating') {
        return $b['rating'] <=> $a['rating'];
    } elseif ($sortOrder === 'newest') {
        return $b['id'] <=> $a['id'];
    }
    return 0; // Default order
});
?>

<div class="shop-catalog-section">
    <div class="container">
        <div class="shop-layout-grid">
            
            <!-- Sidebar Filter Panel -->
            <aside class="shop-sidebar" id="shopSidebar">
                <div class="sidebar-block">
                    <h3 class="sidebar-heading"><?php echo t('footer_categories', $lang); ?></h3>
                    <ul class="cat-filter-list">
                        <li>
                            <a href="shop.php?cat=all" class="cat-filter-link <?php echo $selectedCat === 'all' ? 'active' : ''; ?>">
                                <span><?php echo t('filter_all', $lang); ?></span>
                                <span class="badge-count"><?php echo count($allProducts); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="shop.php?cat=clothes" class="cat-filter-link <?php echo $selectedCat === 'clothes' ? 'active' : ''; ?>">
                                <span><?php echo t('filter_clothes', $lang); ?></span>
                                <span class="badge-count"><?php echo count(array_filter($allProducts, fn($p) => $p['category'] === 'clothes')); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="shop.php?cat=watches" class="cat-filter-link <?php echo $selectedCat === 'watches' ? 'active' : ''; ?>">
                                <span><?php echo t('filter_watches', $lang); ?></span>
                                <span class="badge-count"><?php echo count(array_filter($allProducts, fn($p) => $p['category'] === 'watches')); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="shop.php?cat=perfumes" class="cat-filter-link <?php echo $selectedCat === 'perfumes' ? 'active' : ''; ?>">
                                <span><?php echo t('filter_perfumes', $lang); ?></span>
                                <span class="badge-count"><?php echo count(array_filter($allProducts, fn($p) => $p['category'] === 'perfumes')); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="shop.php?cat=accessories" class="cat-filter-link <?php echo $selectedCat === 'accessories' ? 'active' : ''; ?>">
                                <span><?php echo t('filter_accessories', $lang); ?></span>
                                <span class="badge-count"><?php echo count(array_filter($allProducts, fn($p) => $p['category'] === 'accessories')); ?></span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="sidebar-block">
                    <h3 class="sidebar-heading"><?php echo t('price_range', $lang); ?></h3>
                    <div class="price-slider-group">
                        <input type="range" id="priceRangeSlider" min="50000" max="1500000" step="25000" value="<?php echo $maxPriceFilter; ?>" class="slider-input" oninput="document.getElementById('priceRangeDisplay').innerText = Number(this.value).toLocaleString() + ' IQD';">
                        <div class="price-range-labels">
                            <span>50,000 IQD</span>
                            <span id="priceRangeDisplay" class="price-active-val"><?php echo number_format($maxPriceFilter); ?> IQD</span>
                            <span>1,500,000 IQD</span>
                        </div>
                    </div>
                </div>

                <div class="sidebar-block">
                    <a href="shop.php" class="btn btn-outline w-full"><?php echo t('clear_filters', $lang); ?></a>
                </div>
            </aside>

            <!-- Product Grid & Toolbar Main Area -->
            <main class="shop-main-content">
                <!-- Shop Toolbar -->
                <div class="shop-toolbar">
                    <div class="results-count">
                        <span>Showing <strong><?php echo count($filteredProducts); ?></strong> pieces</span>
                        <?php if (!empty($searchQuery)): ?>
                            <span class="search-tag-active">"<?php echo htmlspecialchars($searchQuery); ?>" <a href="shop.php">✕</a></span>
                        <?php endif; ?>
                    </div>

                    <div class="toolbar-sort-wrap">
                        <label for="shopSortSelect" class="sort-label"><?php echo t('sort_by', $lang); ?>:</label>
                        <select id="shopSortSelect" class="sort-select" onchange="window.location.href = updateQueryParam('sort', this.value);">
                            <option value="featured" <?php echo $sortOrder === 'featured' ? 'selected' : ''; ?>><?php echo t('sort_featured', $lang); ?></option>
                            <option value="price_low" <?php echo $sortOrder === 'price_low' ? 'selected' : ''; ?>><?php echo t('sort_price_low', $lang); ?></option>
                            <option value="price_high" <?php echo $sortOrder === 'price_high' ? 'selected' : ''; ?>><?php echo t('sort_price_high', $lang); ?></option>
                            <option value="rating" <?php echo $sortOrder === 'rating' ? 'selected' : ''; ?>><?php echo t('sort_rating', $lang); ?></option>
                            <option value="newest" <?php echo $sortOrder === 'newest' ? 'selected' : ''; ?>><?php echo t('sort_newest', $lang); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <?php if (empty($filteredProducts)): ?>
                    <div class="no-products-box">
                        <div class="empty-icon">🔍</div>
                        <h3><?php echo t('no_products_found', $lang); ?></h3>
                        <p><?php echo $lang === 'ku' ? 'تکایە پەیڤەکا دی بکاربینە یان فلتەران پاقژ بکە.' : ($lang === 'ar' ? 'يرجى تجربة كلمات بحث أخرى أو إزالة الفلاتر الحالية.' : 'Please try other keywords or reset your filters.'); ?></p>
                        <a href="shop.php" class="btn btn-primary mt-16"><?php echo t('clear_filters', $lang); ?></a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($filteredProducts as $item): 
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
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<script>
function updateQueryParam(key, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, value);
    return url.toString();
}

document.getElementById('priceRangeSlider')?.addEventListener('change', function() {
    window.location.href = updateQueryParam('max_price', this.value);
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
