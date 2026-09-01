<?php
$activePage = 'shop';
$pageTitle = 'Size & Fit Guide';
require_once __DIR__ . '/header.php';

$variant = $_GET['v'] ?? 'tshirt';
$productId = intval($_GET['pid'] ?? ($_GET['id'] ?? 0));
$selectedSize = trim($_GET['size'] ?? '');
$heightParam = trim($_GET['h'] ?? '');
$widthParam = trim($_GET['w'] ?? '');
$backUrl = trim($_GET['from'] ?? '');

$product = null;
$productTitle = '';

if ($productId > 0 && function_exists('get_product_by_id')) {
    $product = get_product_by_id($productId);
    if ($product) {
        $productTitle = is_array($product['title']) ? ($product['title'][$lang] ?? $product['title']['en']) : $product['title'];
        
        // If height or width not provided in URL, extract from product size data
        if (empty($heightParam) || empty($widthParam)) {
            $sizeMeasurements = $product['size_measurements'] ?? [];
            if (is_string($sizeMeasurements)) {
                $sizeMeasurements = json_decode($sizeMeasurements, true) ?: [];
            }
            
            $lookupSize = !empty($selectedSize) ? $selectedSize : (!empty($product['sizes']) ? $product['sizes'][0] : 'M');
            $mRaw = $sizeMeasurements[$lookupSize] ?? '';
            
            if (empty($mRaw) && !empty($product['sizes'])) {
                $cleanSz = strtoupper(trim($lookupSize));
                if ($cleanSz === 'S') $mRaw = 'Height: 65cm • Width: 45cm';
                elseif ($cleanSz === 'M') $mRaw = 'Height: 70cm • Width: 50cm';
                elseif ($cleanSz === 'L') $mRaw = 'Height: 73cm • Width: 54cm';
                elseif ($cleanSz === 'XL') $mRaw = 'Height: 76cm • Width: 58cm';
                elseif ($cleanSz === 'XXL' || $cleanSz === '2XL') $mRaw = 'Height: 79cm • Width: 62cm';
                elseif ($cleanSz === 'XS') $mRaw = 'Height: 62cm • Width: 42cm';
                else $mRaw = 'Height: 70cm • Width: 50cm';
            }
            
            if (empty($heightParam) && preg_match('/(?:Length|Height|Jacket|بلندی|درێژی|الطول)[:\s]*([0-9.]+\s*(?:cm|mm)?)/i', $mRaw, $mH)) {
                $heightParam = trim($mH[1]);
            }
            if (empty($widthParam) && preg_match('/(?:Width|Chest|Trousers|پانی|الصدر|العرض)[:\s]*([0-9.]+\s*(?:cm|mm)?)/i', $mRaw, $mW)) {
                $widthParam = trim($mW[1]);
            }
        }
    }
}

// Fallback default dimensions if not specified
$heightDisplay = !empty($heightParam) ? $heightParam : '70cm';
$widthDisplay = !empty($widthParam) ? $widthParam : '50cm';
if (is_numeric($heightDisplay)) $heightDisplay .= 'cm';
if (is_numeric($widthDisplay)) $widthDisplay .= 'cm';

// Smart Return Link Resolution
if (empty($backUrl)) {
    if ($productId > 0) {
        $backUrl = 'product.php?id=' . $productId;
    } elseif (!empty($_SERVER['HTTP_REFERER'])) {
        $ref = $_SERVER['HTTP_REFERER'];
        $parsed = parse_url($ref);
        $path = basename($parsed['path'] ?? '');
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        if (!empty($path) && $path !== 'size_guide.php') {
            $backUrl = $path . $query;
        } else {
            $backUrl = 'shop.php';
        }
    } else {
        $backUrl = 'shop.php';
    }
} else {
    // If an absolute URL was passed, parse it to stay safe and relative
    if (str_starts_with($backUrl, 'http://') || str_starts_with($backUrl, 'https://')) {
        $parsed = parse_url($backUrl);
        $path = $parsed['path'] ?? 'shop.php';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $backUrl = $path . $query;
    }
}

$isReturningToProduct = ($productId > 0 || str_contains($backUrl, 'product.php'));
$backBtnLabel = $isReturningToProduct 
    ? ($lang === 'ku' ? '← ڤەگەر بۆ بەرهەمی' : ($lang === 'ar' ? '← العودة للمنتج' : '← Back to Product'))
    : ($lang === 'ku' ? '← ڤەگەر بۆ فڕۆشگەهێ' : ($lang === 'ar' ? '← العودة للمتجر' : '← Back to Shop'));

// Helper query string for tabs to preserve product context
$tabQuery = '';
if ($productId > 0) $tabQuery .= '&pid=' . $productId;
if (!empty($selectedSize)) $tabQuery .= '&size=' . urlencode($selectedSize);
if (!empty($heightParam)) $tabQuery .= '&h=' . urlencode($heightParam);
if (!empty($widthParam)) $tabQuery .= '&w=' . urlencode($widthParam);
if (!empty($backUrl)) $tabQuery .= '&from=' . urlencode($backUrl);
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">Maison Aura Atelier</span>
            <h1 class="page-banner-title"><?php echo t('how_to_measure_title', $lang); ?></h1>
            <p class="page-banner-subtitle">
                <?php 
                if ($lang === 'ku') echo 'رێبەرێ قیاس و بەرگدروویێ بۆ دەستکەفتنا قیاسێ ڕاستەقینە';
                elseif ($lang === 'ar') echo 'دليل المقاسات والقياسات الدقيقة لضمان المقاس المثالي';
                else echo 'Precision sizing blueprint and measurement instructions';
                ?>
            </p>
        </div>
    </div>
</div>

<section class="size-guide-page-section py-60">
    <div class="container">
        
        <!-- Category Guide Tabs for future expansion -->
        <div class="size-guide-tabs-nav">
            <a href="size_guide.php?v=tshirt<?php echo $tabQuery; ?>" class="size-tab-btn <?php echo $variant === 'tshirt' ? 'active' : ''; ?>">
                <span class="tab-icon">👕</span>
                <span><?php echo $lang === 'ku' ? 'تیشێرت و سەرپۆش' : ($lang === 'ar' ? 'تيشيرت وقمصان' : 'T-Shirts & Tops'); ?></span>
            </a>
            <a href="size_guide.php?v=jeans<?php echo $tabQuery; ?>" class="size-tab-btn <?php echo $variant === 'jeans' ? 'active' : ''; ?>">
                <span class="tab-icon">👖</span>
                <span><?php echo $lang === 'ku' ? 'پانتۆل و جینز' : ($lang === 'ar' ? 'بناطيل وجينز' : 'Jeans & Trousers'); ?></span>
            </a>
            <a href="size_guide.php?v=jacket<?php echo $tabQuery; ?>" class="size-tab-btn <?php echo $variant === 'jacket' ? 'active' : ''; ?>">
                <span class="tab-icon">🧥</span>
                <span><?php echo $lang === 'ku' ? 'چاکەت و قەمسەلە' : ($lang === 'ar' ? 'جاكيتات ومعاطف' : 'Jackets & Coats'); ?></span>
            </a>
            <a href="size_guide.php?v=shoes<?php echo $tabQuery; ?>" class="size-tab-btn <?php echo $variant === 'shoes' ? 'active' : ''; ?>">
                <span class="tab-icon">👟</span>
                <span><?php echo $lang === 'ku' ? 'پێلاڤ و قۆندەرە' : ($lang === 'ar' ? 'أحذية وسنيكرز' : 'Shoes & Footwear'); ?></span>
            </a>
        </div>

        <div class="size-guide-content-card" dir="<?php echo $dir; ?>">
            
            <?php if ($product): ?>
                <div class="guide-product-badge-wrap" style="margin-bottom: 24px; display: inline-flex; align-items: center; gap: 10px; background: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.3); padding: 8px 18px; border-radius: 30px;">
                    <span style="color: #dcb348; font-size: 13px;">✦</span>
                    <span style="color: #f3f4f6; font-size: 13.5px; font-weight: 600;"><?php echo htmlspecialchars($productTitle); ?></span>
                    <?php if (!empty($selectedSize)): ?>
                        <span style="color: rgba(255,255,255,0.3);">|</span>
                        <span style="color: #dcb348; font-size: 13px; font-weight: 700;"><?php echo $lang === 'ku' ? 'قیاس:' : ($lang === 'ar' ? 'المقاس:' : 'Size:'); ?> <?php echo htmlspecialchars($selectedSize); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="guide-variant-header">
                <h2><?php echo $lang === 'ku' ? 'رێبەرێ قیاسا جلوبەرگان' : ($lang === 'ar' ? 'دليل القياس والخطوات' : 'How to Measure'); ?></h2>
                <p><?php echo $lang === 'ku' ? 'بۆ دەستکەفتنا قیاسێ د دروستاهیێ دا، پارچەکا جلوبەرگێن خۆ ل سەر ڕوویەک تەخت ڕابکێشە و ب ڤی شێوەی پیڤانێ بکە:' : ($lang === 'ar' ? 'لضمان المقاس المثالي، افرد إحدى قطعك المفضلة على سطح مستوٍ وقم بالقياس كالتالي:' : 'For the most accurate fit, lay your favorite garment flat on a smooth surface and follow the simple steps below:'); ?></p>
            </div>

            <div class="guide-grid-layout">
                <div class="measure-illustration-box">
                    <div class="measure-svg-wrapper">
                        <?php if ($variant === 'jeans'): ?>
                            <!-- Jeans Schematic -->
                            <svg class="measure-svg-graphic" viewBox="0 0 500 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="jeanGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#192036" />
                                        <stop offset="100%" stop-color="#0f1322" />
                                    </linearGradient>
                                    <filter id="jeanGlow" x="-20%" y="-20%" width="140%" height="140%">
                                        <feDropShadow dx="0" dy="6" stdDeviation="8" flood-color="#000000" flood-opacity="0.4" />
                                    </filter>
                                </defs>
                                <!-- Pants Silhouette -->
                                <path d="M 190 40 L 310 40 L 330 280 L 275 280 L 250 120 L 225 280 L 170 280 Z" 
                                      fill="url(#jeanGrad)" stroke="#3a405a" stroke-width="2" filter="url(#jeanGlow)" stroke-linejoin="round" />
                                
                                <!-- Waist Width Line & Badge -->
                                <line x1="190" y1="40" x2="310" y2="40" stroke="#dcb348" stroke-width="3" />
                                <circle cx="190" cy="40" r="4.5" fill="#dcb348" />
                                <circle cx="310" cy="40" r="4.5" fill="#dcb348" />
                                <rect x="200" y="10" width="100" height="24" rx="12" fill="#0b0e17" stroke="#dcb348" stroke-width="1.5" />
                                <text x="250" y="26" fill="#dcb348" font-size="11" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif"><?php echo $lang === 'ku' ? 'ناڤتەنگ (Waist)' : ($lang === 'ar' ? 'الخصر (Waist)' : 'Waist Width'); ?></text>

                                <!-- Length Line & Badge -->
                                <line x1="140" y1="40" x2="140" y2="280" stroke="#f43f5e" stroke-width="2.5" stroke-dasharray="6 4" />
                                <circle cx="140" cy="40" r="4" fill="#f43f5e" />
                                <circle cx="140" cy="280" r="4" fill="#f43f5e" />
                                <line x1="140" y1="40" x2="190" y2="40" stroke="rgba(244,63,94,0.3)" stroke-width="1" stroke-dasharray="3 3" />
                                <line x1="140" y1="280" x2="170" y2="280" stroke="rgba(244,63,94,0.3)" stroke-width="1" stroke-dasharray="3 3" />
                                <rect x="65" y="145" width="110" height="26" rx="13" fill="#0b0e17" stroke="#f43f5e" stroke-width="1.5" />
                                <text x="120" y="162" fill="#f43f5e" font-size="11.5" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif"><?php echo $lang === 'ku' ? 'درێژی (Length)' : ($lang === 'ar' ? 'الطول (Length)' : 'Length'); ?></text>
                            </svg>
                        <?php else: ?>
                            <!-- T-Shirt Schematic with Clear Height & Width Dimension Badges -->
                            <svg class="measure-svg-graphic" viewBox="0 0 500 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="shirtGradClean" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#1e2235" />
                                        <stop offset="100%" stop-color="#12141f" />
                                    </linearGradient>
                                    <filter id="shirtGlowClean" x="-20%" y="-20%" width="140%" height="140%">
                                        <feDropShadow dx="0" dy="6" stdDeviation="10" flood-color="#000000" flood-opacity="0.5" />
                                    </filter>
                                </defs>

                                <!-- T-Shirt Silhouette centered at X=270, body width=140px (from X=200 to X=340) -->
                                <path d="M 200 48 C 215 40, 240 36, 270 36 C 300 36, 325 40, 340 48 L 415 85 L 380 140 L 340 120 L 340 275 C 340 283, 332 290, 322 290 L 218 290 C 208 290, 200 283, 200 275 L 200 120 L 160 140 L 125 85 Z" 
                                      fill="url(#shirtGradClean)" stroke="#3a405a" stroke-width="2.2" stroke-linejoin="round" filter="url(#shirtGlowClean)" />
                                
                                <!-- Collar Line -->
                                <path d="M 235 48 Q 270 76 305 48" stroke="#dcb348" stroke-width="2" fill="none" />
                                
                                <!-- Stitch Guides -->
                                <path d="M 200 120 L 230 65" stroke="#2a2e42" stroke-width="1.5" stroke-dasharray="4 4" />
                                <path d="M 340 120 L 310 65" stroke="#2a2e42" stroke-width="1.5" stroke-dasharray="4 4" />
                                
                                <!-- ================= WIDTH DIMENSION & BADGE ================= -->
                                <!-- Width Horizontal Dimension Line Across Chest -->
                                <line x1="200" y1="150" x2="340" y2="150" stroke="#dcb348" stroke-width="3.5" />
                                <circle cx="200" cy="150" r="5" fill="#dcb348" />
                                <circle cx="340" cy="150" r="5" fill="#dcb348" />
                                <polygon points="209,145 200,150 209,155" fill="#dcb348" />
                                <polygon points="331,145 340,150 331,155" fill="#dcb348" />
                                
                                <!-- Width Center Badge -->
                                <g transform="translate(200, 100)">
                                    <rect width="140" height="30" rx="15" fill="#0a0d16" stroke="#dcb348" stroke-width="2" />
                                    <text x="70" y="20" fill="#dcb348" font-size="12.5" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $lang === 'ku' ? 'پانی: ' . htmlspecialchars($widthDisplay) : ($lang === 'ar' ? 'العرض: ' . htmlspecialchars($widthDisplay) : 'Width: ' . htmlspecialchars($widthDisplay)); ?>
                                    </text>
                                </g>

                                <!-- ================= HEIGHT DIMENSION & BADGE ================= -->
                                <!-- Height Horizontal Extension Guide Lines -->
                                <line x1="75" y1="36" x2="270" y2="36" stroke="rgba(244,63,94,0.35)" stroke-width="1.2" stroke-dasharray="4 3" />
                                <line x1="75" y1="290" x2="270" y2="290" stroke="rgba(244,63,94,0.35)" stroke-width="1.2" stroke-dasharray="4 3" />

                                <!-- Height Vertical Dimension Line -->
                                <line x1="75" y1="36" x2="75" y2="290" stroke="#f43f5e" stroke-width="2.8" />
                                <circle cx="75" cy="36" r="4.5" fill="#f43f5e" />
                                <circle cx="75" cy="290" r="4.5" fill="#f43f5e" />
                                <polygon points="70,47 75,36 80,47" fill="#f43f5e" />
                                <polygon points="70,279 75,290 80,279" fill="#f43f5e" />

                                <!-- Height Side Badge -->
                                <g transform="translate(10, 145)">
                                    <rect width="130" height="30" rx="15" fill="#0a0d16" stroke="#f43f5e" stroke-width="2" />
                                    <text x="65" y="20" fill="#f43f5e" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $lang === 'ku' ? 'بلندی: ' . htmlspecialchars($heightDisplay) : ($lang === 'ar' ? 'الارتفاع: ' . htmlspecialchars($heightDisplay) : 'Height: ' . htmlspecialchars($heightDisplay)); ?>
                                    </text>
                                </g>
                            </svg>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3 Clear Measurement Steps -->
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
            </div>

            <!-- Smart Return Action Button -->
            <div class="text-center mt-40">
                <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-primary" style="padding: 15px 36px; border-radius: 12px; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; color: #0c0e14; background: linear-gradient(135deg, #dcb348 0%, #b8932d 100%); font-weight: 700; font-size: 15px; box-shadow: 0 6px 20px rgba(212, 175, 55, 0.3); transition: all 0.2s ease;">
                    <?php echo $backBtnLabel; ?>
                </a>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
