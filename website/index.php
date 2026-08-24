<?php
$activePage = 'home';
$pageTitle = null; // Will use default site title
require_once __DIR__ . '/header.php';

$products = get_all_products();
$featuredProducts = array_filter($products, function($p) {
    return !empty($p['featured']);
});
?>

<!-- 1. Hero Showcase Section -->
<section class="hero-section">
    <div class="hero-glow-orb hero-glow-1"></div>
    <div class="hero-glow-orb hero-glow-2"></div>
    
    <div class="container hero-grid">
        <div class="hero-text-content">
            <div class="hero-tag-badge">
                <span class="pulse-dot"></span>
                <span><?php echo t('hero_badge', $lang); ?></span>
            </div>
            
            <h1 class="hero-headline">
                <?php echo htmlspecialchars($settings['hero_headline_' . $lang] ?? t('hero_title', $lang)); ?>
            </h1>
            
            <p class="hero-description">
                <?php echo htmlspecialchars($settings['hero_subtitle_' . $lang] ?? t('hero_subtitle', $lang)); ?>
            </p>
            
            <div class="hero-cta-buttons">
                <a href="shop.php" class="btn btn-primary btn-luxury">
                    <span><?php echo t('hero_shop_now', $lang); ?></span>
                    <span class="btn-arrow">→</span>
                </a>
                <a href="shop.php?cat=perfumes" class="btn btn-secondary">
                    <span><?php echo t('hero_explore_perfumes', $lang); ?></span>
                </a>
            </div>

            <div class="hero-trust-metrics">
                <div class="metric-item">
                    <span class="metric-num">100%</span>
                    <span class="metric-label">Original Luxury</span>
                </div>
                <div class="metric-divider"></div>
                <div class="metric-item">
                    <span class="metric-num">24h</span>
                    <span class="metric-label">Express Delivery</span>
                </div>
                <div class="metric-divider"></div>
                <div class="metric-item">
                    <span class="metric-num">4.9★</span>
                    <span class="metric-label">Client Rating</span>
                </div>
            </div>
        </div>

        <div class="hero-visual-card">
            <div class="hero-card-inner">
                <div class="hero-main-image-wrap">
                    <img src="https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=1000&q=80" alt="Luxury Timepiece" class="hero-main-img">
                    <div class="hero-badge-floating floating-1">
                        <span class="floating-icon">⌚</span>
                        <div class="floating-text">
                            <strong>Swiss Mechanical</strong>
                            <span>Skeleton Automatic</span>
                        </div>
                    </div>
                    <div class="hero-badge-floating floating-2">
                        <span class="floating-icon">✨</span>
                        <div class="floating-text">
                            <strong>100% Authentic</strong>
                            <span>Royal Oud & Silk</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. Categories Showcase Grid -->
<section class="categories-section">
    <div class="container">
        <div class="section-title-wrap text-center">
            <span class="section-kicker"><?php echo t('cat_title', $lang); ?></span>
            <h2 class="section-main-heading"><?php echo t('cat_subtitle', $lang); ?></h2>
        </div>

        <div class="category-cards-grid">
            <!-- 1. Clothes -->
            <a href="shop.php?cat=clothes" class="category-card cat-clothes">
                <div class="cat-card-img" style="background-image: url('https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80');"></div>
                <div class="cat-card-overlay"></div>
                <div class="cat-card-info">
                    <span class="cat-badge">24+ Pieces</span>
                    <h3 class="cat-title"><?php echo t('cat_clothes', $lang); ?></h3>
                    <p class="cat-desc"><?php echo t('cat_clothes_desc', $lang); ?></p>
                    <span class="cat-link-label"><?php echo t('browse_cat', $lang); ?> →</span>
                </div>
            </a>

            <!-- 2. Watches -->
            <a href="shop.php?cat=watches" class="category-card cat-watches">
                <div class="cat-card-img" style="background-image: url('https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=800&q=80');"></div>
                <div class="cat-card-overlay"></div>
                <div class="cat-card-info">
                    <span class="cat-badge">18+ Pieces</span>
                    <h3 class="cat-title"><?php echo t('cat_watches', $lang); ?></h3>
                    <p class="cat-desc"><?php echo t('cat_watches_desc', $lang); ?></p>
                    <span class="cat-link-label"><?php echo t('browse_cat', $lang); ?> →</span>
                </div>
            </a>

            <!-- 3. Perfumes -->
            <a href="shop.php?cat=perfumes" class="category-card cat-perfumes">
                <div class="cat-card-img" style="background-image: url('https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80');"></div>
                <div class="cat-card-overlay"></div>
                <div class="cat-card-info">
                    <span class="cat-badge">30+ Scents</span>
                    <h3 class="cat-title"><?php echo t('cat_perfumes', $lang); ?></h3>
                    <p class="cat-desc"><?php echo t('cat_perfumes_desc', $lang); ?></p>
                    <span class="cat-link-label"><?php echo t('browse_cat', $lang); ?> →</span>
                </div>
            </a>

            <!-- 4. Accessories -->
            <a href="shop.php?cat=accessories" class="category-card cat-accessories">
                <div class="cat-card-img" style="background-image: url('https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80');"></div>
                <div class="cat-card-overlay"></div>
                <div class="cat-card-info">
                    <span class="cat-badge">16+ Essentials</span>
                    <h3 class="cat-title"><?php echo t('cat_accessories', $lang); ?></h3>
                    <p class="cat-desc"><?php echo t('cat_accessories_desc', $lang); ?></p>
                    <span class="cat-link-label"><?php echo t('browse_cat', $lang); ?> →</span>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- 3. Flash Sale & Countdown Timer Section -->
<section class="flash-sale-section">
    <div class="container">
        <div class="flash-sale-card">
            <div class="flash-sale-content">
                <span class="sale-badge-pill">🔥 <?php echo t('flash_sale_badge', $lang); ?></span>
                <h2 class="sale-title"><?php echo t('flash_sale_title', $lang); ?></h2>
                <p class="sale-description"><?php echo t('flash_sale_desc', $lang); ?></p>
                
                <!-- Live Countdown Timer -->
                <div class="countdown-timer" id="saleCountdown">
                    <div class="countdown-box">
                        <span class="count-num" id="countDays">03</span>
                        <span class="count-label"><?php echo t('countdown_days', $lang); ?></span>
                    </div>
                    <div class="countdown-colon">:</div>
                    <div class="countdown-box">
                        <span class="count-num" id="countHours">14</span>
                        <span class="count-label"><?php echo t('countdown_hours', $lang); ?></span>
                    </div>
                    <div class="countdown-colon">:</div>
                    <div class="countdown-box">
                        <span class="count-num" id="countMins">28</span>
                        <span class="count-label"><?php echo t('countdown_mins', $lang); ?></span>
                    </div>
                    <div class="countdown-colon">:</div>
                    <div class="countdown-box">
                        <span class="count-num" id="countSecs">45</span>
                        <span class="count-label"><?php echo t('countdown_secs', $lang); ?></span>
                    </div>
                </div>

                <div class="sale-action">
                    <a href="shop.php" class="btn btn-primary btn-luxury"><?php echo t('hero_shop_now', $lang); ?> →</a>
                </div>
            </div>

            <div class="flash-sale-visual">
                <div class="flash-product-preview">
                    <img src="https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=700&q=80" alt="Rose Gold Riviera Chronograph" class="sale-img">
                    <div class="discount-badge-circle">-25%</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4. Trending & Featured Products Section -->
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
            ?>
            <div class="product-card" data-category="<?php echo $item['category']; ?>" data-id="<?php echo $item['id']; ?>">
                <div class="product-image-container">
                    <?php if (!empty($badgeText)): ?>
                        <span class="product-badge-tag"><?php echo htmlspecialchars($badgeText); ?></span>
                    <?php endif; ?>
                    
                    <a href="product.php?id=<?php echo $item['id']; ?>" class="product-img-link">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($titleText); ?>" class="product-thumb" loading="lazy">
                    </a>

                    <div class="product-hover-actions">
                        <button class="action-btn-circle quick-view-btn" data-id="<?php echo $item['id']; ?>" title="<?php echo t('quick_view', $lang); ?>">
                            👁️
                        </button>
                        <button class="action-btn-circle add-cart-btn" data-id="<?php echo $item['id']; ?>" title="<?php echo t('add_to_cart', $lang); ?>">
                            🛍️
                        </button>
                    </div>
                </div>

                <div class="product-details">
                    <div class="product-meta-row">
                        <span class="product-cat-name"><?php echo t('filter_' . $item['category'], $lang); ?></span>
                        <div class="product-rating">
                            <span class="star-icon">★</span>
                            <span class="rating-val"><?php echo number_format($item['rating'], 1); ?></span>
                        </div>
                    </div>

                    <h3 class="product-title">
                        <a href="product.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($titleText); ?></a>
                    </h3>

                    <div class="product-price-row">
                        <div class="price-wrap">
                            <span class="current-price">$<?php echo number_format($item['price'], 2); ?></span>
                            <?php if (!empty($item['old_price']) && $item['old_price'] > $item['price']): ?>
                                <span class="old-price">$<?php echo number_format($item['old_price'], 2); ?></span>
                            <?php endif; ?>
                        </div>

                        <button class="btn-add-cart-mini" onclick="window.AuraStore.addToCart(<?php echo $item['id']; ?>)">
                            <span>+ <?php echo t('add_to_cart', $lang); ?></span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-48">
            <a href="shop.php" class="btn btn-secondary btn-lg">
                <span><?php echo t('shop_title', $lang); ?></span>
                <span>→</span>
            </a>
        </div>
    </div>
</section>

<!-- 5. Client Testimonials / Reviews -->
<section class="testimonials-section">
    <div class="container">
        <div class="section-title-wrap text-center">
            <span class="section-kicker"><?php echo t('reviews', $lang); ?></span>
            <h2 class="section-main-heading"><?php echo $lang === 'ku' ? 'کریارێن مە دەربارەی مە چ دبێژن؟' : ($lang === 'ar' ? 'ماذا يقول عملاؤنا المميزون؟' : 'What Our Connoisseurs Say'); ?></h2>
        </div>

        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="stars-row">★★★★★</div>
                <p class="testimonial-text">
                    <?php echo $lang === 'ku' 
                    ? '"دەمژمێرا سکێلێتۆن یا ئۆتۆماتیک گەلەک جوانە و کوالێتیا وێ یا بێ وێنەیە. زوو گەهشت دەستێ من ل دهۆکێ ب پاکێجەکێ شاهانە."' 
                    : ($lang === 'ar' 
                    ? '"الساعة الأوتوماتيكية ذات جودة سويسرية مذهلة وتفاصيل في غاية الدقة. التوصيل كان سريعاً جداً والتغليف فاخر يليق بالإهداء."' 
                    : '"The Onyx Skeleton Watch exceeded all expectations. Swiss precision mechanics combined with pristine customer care. Delivered to my doorstep within 24 hours."'); ?>
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">KD</div>
                    <div>
                        <h4>Kawa Duhoki</h4>
                        <span>Duhok, Kurdistan</span>
                    </div>
                </div>
            </div>

            <div class="testimonial-card">
                <div class="stars-row">★★★★★</div>
                <p class="testimonial-text">
                    <?php echo $lang === 'ku' 
                    ? '"عەترێ عوودێ کەمبۆدی و عەنبەرێ زێڕین بێهنەکا گەلەک فەخم یا هەی و پتر ژ دوو ڕۆژان ل سەر جلکان دمینیت. دەستێن هەوە خۆش بن."' 
                    : ($lang === 'ar' 
                    ? '"عطر العود والعنبر الملكي ثابت وفواح لأكثر من يومين. كل من حولي يسألني عن سر هذه الرائحة المميزة. متجر راقٍ بكل معنى الكلمة."' 
                    : '"The Smoked Oud and Royal Amber is pure olfactory mastery. Incredibly long-lasting with rich sillage that turns heads wherever I enter."'); ?>
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">TM</div>
                    <div>
                        <h4>Tariq Mansoor</h4>
                        <span>Erbil / Baghdad</span>
                    </div>
                </div>
            </div>

            <div class="testimonial-card">
                <div class="stars-row">★★★★★</div>
                <p class="testimonial-text">
                    <?php echo $lang === 'ku' 
                    ? '"ساکێ مەخمەلی ژ قوماشێ هەرە باش هاتیە درووتن و نەخشێ وی سەد دەرسەد رێک و پێکە. ئەزموونەکا کڕینێ یا بێ کێماسی بوو."' 
                    : ($lang === 'ar' 
                    ? '"بليزر المخمل الملكي بتفصيل راقٍ جداً ينافس أكبر دور الأزياء في ميلانو وباريس. شحن سريع وتعامل في قمة الذوق."' 
                    : '"The bespoke velvet blazer fit like a tailored glove. Elegant silk lapels and impeccable stitch work. Aura Studio is our go-to luxury destination."'); ?>
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">AH</div>
                    <div>
                        <h4>Alexander Hayes</h4>
                        <span>International Collector</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
