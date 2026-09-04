<?php
$activePage = 'shop';
$pageTitle = 'Size & Fit Guide';
require_once __DIR__ . '/header.php';

// Safe extraction and categorization of variant
$rawV = strtolower(trim($_GET['v'] ?? ''));
if (in_array($rawV, ['jeans', 'jean', 'pants', 'pant', 'trousers', 'trouser', 'denim'])) {
    $variant = 'jeans';
} elseif (in_array($rawV, ['jacket', 'jackets', 'coat', 'coats', 'blazer', 'blazers', 'outerwear', 'hoodie', 'hoodies'])) {
    $variant = 'jacket';
} elseif (in_array($rawV, ['feet', 'foot', 'shoes', 'shoe', 'footwear', 'sneakers', 'sneaker', 'boots', 'boot'])) {
    $variant = 'feet';
} elseif (in_array($rawV, ['tshirt', 't-shirt', 'shirts', 'shirt', 'top', 'tops', 'tee'])) {
    $variant = 'tshirt';
} else {
    $variant = '';
}

$productId = intval($_GET['pid'] ?? ($_GET['id'] ?? 0));
$selectedSize = trim($_GET['size'] ?? '');
$heightParam = trim($_GET['h'] ?? '');
$widthParam = trim($_GET['w'] ?? '');
$backUrl = trim($_GET['from'] ?? '');

$product = null;
$productTitle = '';
$productMatchesVariant = false;

if ($productId > 0 && function_exists('get_product_by_id')) {
    $product = get_product_by_id($productId);
    if ($product) {
        $productTitle = is_array($product['title']) ? ($product['title'][$lang] ?? $product['title']['en']) : $product['title'];
        
        $pCat = strtolower($product['category'] ?? '');
        $pTitle = strtolower(is_array($product['title']) ? ($product['title']['en'] ?? '') : $product['title']);
        
        $detectedProductVariant = 'tshirt';
        if (str_contains($pCat, 'shoe') || str_contains($pTitle, 'shoe') || str_contains($pTitle, 'sneaker') || str_contains($pTitle, 'boot') || str_contains($pCat, 'feet')) {
            $detectedProductVariant = 'feet';
        } elseif (str_contains($pTitle, 'jean') || str_contains($pTitle, 'pant') || str_contains($pTitle, 'trouser') || str_contains($pCat, 'pant')) {
            $detectedProductVariant = 'jeans';
        } elseif (str_contains($pTitle, 'jacket') || str_contains($pTitle, 'coat') || str_contains($pTitle, 'blazer') || str_contains($pTitle, 'hoodie')) {
            $detectedProductVariant = 'jacket';
        }

        if (empty($variant)) {
            $variant = $detectedProductVariant;
        }

        $productMatchesVariant = ($variant === $detectedProductVariant);
    }
}

if (empty($variant)) {
    $variant = 'tshirt';
}

function clean_dim_number($val) {
    if (empty($val)) return '';
    $clean = preg_replace('/[^0-9.]/', '', (string)$val);
    return trim($clean);
}

// Clean incoming dimension numbers (strip 'cm', text, etc.)
$activeDimHeight = clean_dim_number($heightParam);
$activeDimWidth = clean_dim_number($widthParam);

// If product is provided and matches category, check if product has measurements
if ((empty($activeDimHeight) || empty($activeDimWidth)) && $productMatchesVariant && !empty($product)) {
    $rawMeasurements = $product['size_measurements'] ?? [];
    if (is_string($rawMeasurements)) {
        $rawMeasurements = json_decode($rawMeasurements, true) ?: [];
    }
    if (!empty($selectedSize) && isset($rawMeasurements[$selectedSize])) {
        $mRaw = $rawMeasurements[$selectedSize];
        if (preg_match('/(?:Length|Height|Jacket|بلندی|درێژی|الطول)[:\s]*([0-9.]+)/i', $mRaw, $mH)) {
            $activeDimHeight = clean_dim_number($mH[1]);
        }
        if (preg_match('/(?:Width|Chest|Trousers|پانی|الصدر|العرض)[:\s]*([0-9.]+)/i', $mRaw, $mW)) {
            $activeDimWidth = clean_dim_number($mW[1]);
        }
    }
}

// Category Default Dimensions (Clean numbers without unit suffixes)
if (empty($activeDimHeight) || empty($activeDimWidth)) {
    $sz = strtoupper($selectedSize ?: 'M');
    if ($variant === 'feet') {
        $num = floatval($sz);
        if ($num >= 35 && $num <= 48) {
            $activeDimHeight = number_format(24.0 + ($num - 38) * 0.65, 1);
            $activeDimWidth = '9.8';
        } else {
            $activeDimHeight = '27.0';
            $activeDimWidth = '9.8';
        }
    } elseif ($variant === 'jeans') {
        if ($sz === '30' || $sz === 'S') { $activeDimHeight = '102'; $activeDimWidth = '78'; }
        elseif ($sz === '32' || $sz === 'M') { $activeDimHeight = '104'; $activeDimWidth = '82'; }
        elseif ($sz === '34' || $sz === 'L') { $activeDimHeight = '106'; $activeDimWidth = '86'; }
        elseif ($sz === '36' || $sz === 'XL') { $activeDimHeight = '108'; $activeDimWidth = '92'; }
        else { $activeDimHeight = '104'; $activeDimWidth = '82'; }
    } elseif ($variant === 'jacket') {
        if ($sz === 'S') { $activeDimHeight = '68'; $activeDimWidth = '52'; }
        elseif ($sz === 'M') { $activeDimHeight = '71'; $activeDimWidth = '55'; }
        elseif ($sz === 'L') { $activeDimHeight = '74'; $activeDimWidth = '58'; }
        elseif ($sz === 'XL') { $activeDimHeight = '77'; $activeDimWidth = '62'; }
        else { $activeDimHeight = '71'; $activeDimWidth = '55'; }
    } else { // tshirt / tops
        if ($sz === 'XS') { $activeDimHeight = '62'; $activeDimWidth = '42'; }
        elseif ($sz === 'S') { $activeDimHeight = '65'; $activeDimWidth = '45'; }
        elseif ($sz === 'M') { $activeDimHeight = '70'; $activeDimWidth = '50'; }
        elseif ($sz === 'L') { $activeDimHeight = '73'; $activeDimWidth = '54'; }
        elseif ($sz === 'XL') { $activeDimHeight = '76'; $activeDimWidth = '58'; }
        elseif ($sz === 'XXL' || $sz === '2XL') { $activeDimHeight = '79'; $activeDimWidth = '62'; }
        else { $activeDimHeight = '70'; $activeDimWidth = '50'; }
    }
}

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
}

$isReturningToProduct = ($productId > 0 || str_contains($backUrl, 'product.php'));
$backBtnLabel = $isReturningToProduct 
    ? ($lang === 'ku' ? '← ڤەگەر بۆ بەرهەمی' : ($lang === 'ar' ? '← العودة للمنتج' : '← Back to Product'))
    : ($lang === 'ku' ? '← ڤەگەر بۆ فڕۆشگەهێ' : ($lang === 'ar' ? '← العودة للمتجر' : '← Back to Shop'));

// Helper query string for tab navigation
$tabQuery = '';
if (!empty($backUrl)) {
    $tabQuery .= '&from=' . urlencode($backUrl);
}

// Category localized metadata
$catMeta = [
    'tshirt' => [
        'name' => ($lang === 'ku' ? 'تیشێرت و سەرپۆش' : ($lang === 'ar' ? 'تيشيرت وقمصان' : 'Shirts & Tops')),
        'icon' => '👕',
        'step1' => ($lang === 'ku' ? 'پارچەیێ بەردە ل سەر مێزەکا تەخت و بێ چەماندن ڕابکێشە.' : ($lang === 'ar' ? 'افرد القطعة بشكل مستوٍ على سطح صلب بدون طيات.' : 'Lay your favorite garment flat on a firm, smooth surface.')),
        'step2' => ($lang === 'ku' ? 'پانیێ ژ ژێر ملێ چەپێ بۆ یێ ڕاستێ بپێڤە.' : ($lang === 'ar' ? 'قس المسافة الأفقية بين الإبطين.' : 'Measure horizontally from pit to pit.')),
        'step3' => ($lang === 'ku' ? 'بلندیێ ژ سەرێ ملان هەتا بنێ جلوبەرگی بپێڤە.' : ($lang === 'ar' ? 'قس المسافة الرأسية من أعلى نقطة في الكتف إلى الحافة السفلية.' : 'Measure vertically from shoulder top to bottom hem.'))
    ],
    'jeans' => [
        'name' => ($lang === 'ku' ? 'پانتۆل و جینز' : ($lang === 'ar' ? 'بناطيل وجينز' : 'Jeans & Pants')),
        'icon' => '👖',
        'step1' => ($lang === 'ku' ? 'پانتۆلەکێ بەردە ل سەر مێزێ و قۆپچەی دابخە.' : ($lang === 'ar' ? 'افرد بنطالك مع إغلاق الزر والسحاب بالكامل.' : 'Button up and lay your trousers flat on a flat surface.')),
        'step2' => ($lang === 'ku' ? 'پانییا کەمەرێ ژ لایەکێ بۆ لایێ دی بپێڤە.' : ($lang === 'ar' ? 'قس عرض الخصر من الحافة إلى الحافة.' : 'Measure across the waistband from side edge to side edge.')),
        'step3' => ($lang === 'ku' ? 'بلندیێ ژ سەرێ کەمەرێ هەتا بنی بپێڤە.' : ($lang === 'ar' ? 'قس الطول من أعلى حزام الخصر إلى نهاية طرف البنطال.' : 'Measure from waistband top down to hem leg opening.'))
    ],
    'jacket' => [
        'name' => ($lang === 'ku' ? 'چاکەت و قەمسەلە' : ($lang === 'ar' ? 'جاكيتات ومعاطف' : 'Jackets & Coats')),
        'icon' => '🧥',
        'step1' => ($lang === 'ku' ? 'قۆپچەیێن چاکەتی دابخە و ل سەر ڕوویەک تەخت دابنێ.' : ($lang === 'ar' ? 'أغلق أزرار الجاكيت وافرده بسلاسة على سطح مستوٍ.' : 'Fasten buttons and lay the blazer flat on a level table.')),
        'step2' => ($lang === 'ku' ? 'پانییا ژێر هەردوو ملان بەرفرەهی بپێڤە.' : ($lang === 'ar' ? 'قس عرض الصدر أفقياً بين منطقتي الإبطين.' : 'Measure pit-to-pit across chest with fabric smoothly spread.')),
        'step3' => ($lang === 'ku' ? 'بلندیێ ژ بنێ یەخەی هەتا خوارێ بپێڤە.' : ($lang === 'ar' ? 'قس الطول من أسفل ياقة الرقبة حتى نهاية الجاكيت من الخلف.' : 'Measure center back length from below collar down to hem.'))
    ],
    'feet' => [
        'name' => ($lang === 'ku' ? 'پێلاڤ' : ($lang === 'ar' ? 'الأحذية' : 'Footwear')),
        'icon' => '👟',
        'step1' => ($lang === 'ku' ? 'کاغەزەکێ ل سەر ئەردی دابنێ و پێیێ خۆ ب دورستی ل سەر ڕابگرە.' : ($lang === 'ar' ? 'ضع ورقة بيضاء على الأرض وقف عليها بوزنك الكامل.' : 'Place a paper sheet on the floor and stand firmly on it.')),
        'step2' => ($lang === 'ku' ? 'پانییا پێی ل بەرفرەهترین جهـ بپێڤە.' : ($lang === 'ar' ? 'قس عرض القدم عند أعرض نقطة.' : 'Measure the foot width across the widest part.')),
        'step3' => ($lang === 'ku' ? 'بلندی / درێژیا پێی ژ پنیا پێی هەتا سەرێ تلیا مەزن بپێڤە.' : ($lang === 'ar' ? 'قس المسافة من الكعب إلى أطول إصبع في قدمك.' : 'Measure from the backmost heel point to the tip of your longest toe.'))
    ]
];

$curCat = $catMeta[$variant] ?? $catMeta['tshirt'];

// Strictly Width and Height labels
$dimHeightLabel = ($lang === 'ku' ? 'بلندی' : ($lang === 'ar' ? 'الارتفاع' : 'Height'));
$dimWidthLabel = ($lang === 'ku' ? 'پانی' : ($lang === 'ar' ? 'العرض' : 'Width'));
?>

<section class="size-guide-page-section" style="padding-top: 50px; padding-bottom: 120px; margin-bottom: 30px;">
    <div class="container">
        
        <!-- Category Navigation Tabs -->
        <div class="size-guide-tabs-nav" style="margin-top: 36px; margin-bottom: 44px;">
            <a href="size_guide.php?v=tshirt<?php echo $tabQuery; ?>" class="size-tab-btn <?php echo $variant === 'tshirt' ? 'active' : ''; ?>">
                <span class="tab-icon">👕</span>
                <span><?php echo $lang === 'ku' ? 'تیشێرت و سەرپۆش' : ($lang === 'ar' ? 'تيشيرت وقمصان' : 'Shirts & Tops'); ?></span>
            </a>
            <a href="size_guide.php?v=jeans<?php echo $tabQuery; ?>" class="size-tab-btn <?php echo $variant === 'jeans' ? 'active' : ''; ?>">
                <span class="tab-icon">👖</span>
                <span><?php echo $lang === 'ku' ? 'پانتۆل و جینز' : ($lang === 'ar' ? 'بناطيل وجينز' : 'Jeans & Pants'); ?></span>
            </a>
            <a href="size_guide.php?v=jacket<?php echo $tabQuery; ?>" class="size-tab-btn <?php echo $variant === 'jacket' ? 'active' : ''; ?>">
                <span class="tab-icon">🧥</span>
                <span><?php echo $lang === 'ku' ? 'چاکەت و قەمسەلە' : ($lang === 'ar' ? 'جاكيتات ومعاطف' : 'Jackets & Coats'); ?></span>
            </a>
            <a href="size_guide.php?v=feet<?php echo $tabQuery; ?>" class="size-tab-btn <?php echo $variant === 'feet' ? 'active' : ''; ?>">
                <span class="tab-icon">👟</span>
                <span><?php echo $lang === 'ku' ? 'پێلاڤ' : ($lang === 'ar' ? 'الأحذية' : 'Footwear'); ?></span>
            </a>
        </div>

        <div class="size-guide-content-card" dir="<?php echo $dir; ?>">
            
            <?php if ($product && $productMatchesVariant): ?>
                <!-- Product Reference Badge -->
                <div class="guide-controls-top-bar" style="display: flex; justify-content: center; align-items: center; margin-bottom: 20px;">
                    <div class="guide-product-badge-wrap" style="display: inline-flex; align-items: center; gap: 10px; background: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.3); padding: 8px 18px; border-radius: 30px;">
                        <span style="color: #dcb348; font-size: 14px;">✦</span>
                        <span style="color: #f3f4f6; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($productTitle); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="guide-variant-header">
                <h2><?php echo $curCat['icon']; ?> <?php echo $curCat['name']; ?></h2>
                <p><?php echo $lang === 'ku' ? 'بۆ دەستکەفتنا قیاسێ دروست، پیڤانێن خۆ ب دووڤ ڤی شێوازێ دیارکری بگرە:' : ($lang === 'ar' ? 'لضمان القياس المثالي الذي يلائمك، اتبع المخطط التوضيحي والخطوات التالية:' : 'Follow our tailored anatomical blueprint below to ensure absolute precision:'); ?></p>
            </div>

            <!-- Grid Layout: SVG Schematic + 3 Measurement Steps -->
            <div class="guide-grid-layout">
                
                <!-- Left Column: Interactive Vector Schematic -->
                <div class="measure-illustration-box">
                    <div class="measure-svg-wrapper" id="schematicSvgContainer">
                        
                        <?php if ($variant === 'jeans'): ?>
                            <!-- ================= 👖 JEANS & TROUSERS SCHEMATIC ================= -->
                            <svg class="measure-svg-graphic" viewBox="0 0 500 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="jeanGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#1e2438" />
                                        <stop offset="100%" stop-color="#0f1322" />
                                    </linearGradient>
                                    <filter id="jeanGlow" x="-20%" y="-20%" width="140%" height="140%">
                                        <feDropShadow dx="0" dy="6" stdDeviation="8" flood-color="#000000" flood-opacity="0.5" />
                                    </filter>
                                </defs>
                                
                                <!-- Pants Silhouette -->
                                <path d="M 195 40 L 305 40 L 328 280 L 275 280 L 250 120 L 225 280 L 172 280 Z" 
                                      fill="url(#jeanGrad)" stroke="#3a405a" stroke-width="2.2" filter="url(#jeanGlow)" stroke-linejoin="round" />
                                
                                <!-- Waist Band Stitches & Fly -->
                                <line x1="195" y1="58" x2="305" y2="58" stroke="#2a3048" stroke-width="1.5" stroke-dasharray="4 3" />
                                <path d="M 250 40 L 250 100 Q 250 115 260 118" stroke="#dcb348" stroke-width="1.8" fill="none" />
                                
                                <!-- Pocket Details -->
                                <path d="M 205 40 Q 220 70 200 95" stroke="#3a405a" stroke-width="1.5" fill="none" />
                                <path d="M 295 40 Q 280 70 300 95" stroke="#3a405a" stroke-width="1.5" fill="none" />

                                <!-- Width Dimension Line & Badge -->
                                <line x1="195" y1="36" x2="305" y2="36" stroke="#dcb348" stroke-width="3" />
                                <circle cx="195" cy="36" r="4.5" fill="#dcb348" />
                                <circle cx="305" cy="36" r="4.5" fill="#dcb348" />
                                <g transform="translate(195, 4)">
                                    <rect width="110" height="26" rx="13" fill="#0b0e17" stroke="#dcb348" stroke-width="1.8" />
                                    <text x="55" y="17" fill="#dcb348" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $dimWidthLabel; ?>
                                    </text>
                                </g>

                                <!-- Height Dimension Line & Badge -->
                                <line x1="130" y1="40" x2="130" y2="280" stroke="#f43f5e" stroke-width="2.6" stroke-dasharray="6 4" />
                                <circle cx="130" cy="40" r="4" fill="#f43f5e" />
                                <circle cx="130" cy="280" r="4" fill="#f43f5e" />
                                <line x1="130" y1="40" x2="195" y2="40" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                <line x1="130" y1="280" x2="172" y2="280" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                <g transform="translate(15, 145)">
                                    <rect width="110" height="28" rx="14" fill="#0b0e17" stroke="#f43f5e" stroke-width="1.8" />
                                    <text x="55" y="18" fill="#f43f5e" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $dimHeightLabel; ?>
                                    </text>
                                </g>
                            </svg>

                        <?php elseif ($variant === 'jacket'): ?>
                            <!-- ================= 🧥 JACKET & BLAZER SCHEMATIC ================= -->
                            <svg class="measure-svg-graphic" viewBox="0 0 500 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="jacketGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#222738" />
                                        <stop offset="100%" stop-color="#111420" />
                                    </linearGradient>
                                    <filter id="jacketGlow" x="-20%" y="-20%" width="140%" height="140%">
                                        <feDropShadow dx="0" dy="6" stdDeviation="10" flood-color="#000000" flood-opacity="0.5" />
                                    </filter>
                                </defs>
                                
                                <!-- Tailored Jacket Body & Sleeves -->
                                <path d="M 190 45 L 230 38 L 270 38 L 310 45 L 395 85 L 360 160 L 325 140 L 330 280 C 330 285, 320 288, 305 288 L 195 288 C 180 288, 170 285, 170 280 L 175 140 L 140 160 L 105 85 Z" 
                                      fill="url(#jacketGrad)" stroke="#3a405a" stroke-width="2.2" stroke-linejoin="round" filter="url(#jacketGlow)" />
                                
                                <!-- Lapel / Collar V-Line -->
                                <polygon points="250,175 220,70 240,40 250,40 260,40 280,70" fill="#181c2b" stroke="#dcb348" stroke-width="1.8" />
                                <line x1="250" y1="175" x2="250" y2="288" stroke="#dcb348" stroke-width="1.5" />
                                <circle cx="250" cy="195" r="3" fill="#dcb348" />
                                <circle cx="250" cy="225" r="3" fill="#dcb348" />

                                <!-- Width Dimension Line & Badge -->
                                <line x1="175" y1="140" x2="325" y2="140" stroke="#dcb348" stroke-width="3" />
                                <circle cx="175" cy="140" r="5" fill="#dcb348" />
                                <circle cx="325" cy="140" r="5" fill="#dcb348" />
                                <g transform="translate(195, 95)">
                                    <rect width="110" height="28" rx="14" fill="#0b0e17" stroke="#dcb348" stroke-width="1.8" />
                                    <text x="55" y="18" fill="#dcb348" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $dimWidthLabel; ?>
                                    </text>
                                </g>

                                <!-- Height Dimension Line & Badge -->
                                <line x1="70" y1="38" x2="70" y2="288" stroke="#f43f5e" stroke-width="2.6" />
                                <circle cx="70" cy="38" r="4.5" fill="#f43f5e" />
                                <circle cx="70" cy="288" r="4.5" fill="#f43f5e" />
                                <line x1="70" y1="38" x2="230" y2="38" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                <line x1="70" y1="288" x2="195" y2="288" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                <g transform="translate(15, 148)">
                                    <rect width="110" height="28" rx="14" fill="#0b0e17" stroke="#f43f5e" stroke-width="1.8" />
                                    <text x="55" y="18" fill="#f43f5e" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $dimHeightLabel; ?>
                                    </text>
                                </g>
                            </svg>

                        <?php elseif ($variant === 'feet'): ?>
                            <!-- ================= 🦶 ANATOMICAL FEET / FOOT SIZE SCHEMATIC ================= -->
                            <svg class="measure-svg-graphic" viewBox="0 0 500 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="footGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#2a3248" />
                                        <stop offset="100%" stop-color="#141826" />
                                    </linearGradient>
                                    <filter id="footGlow" x="-20%" y="-20%" width="140%" height="140%">
                                        <feDropShadow dx="0" dy="6" stdDeviation="10" flood-color="#000000" flood-opacity="0.55" />
                                    </filter>
                                </defs>
                                
                                <!-- Measuring Paper Grid Background -->
                                <rect x="90" y="30" width="320" height="240" rx="10" fill="rgba(255,255,255,0.02)" stroke="rgba(255,255,255,0.08)" stroke-width="1" />
                                <line x1="90" y1="150" x2="410" y2="150" stroke="rgba(212,175,55,0.15)" stroke-width="1" stroke-dasharray="4 4" />
                                <line x1="250" y1="30" x2="250" y2="270" stroke="rgba(212,175,55,0.15)" stroke-width="1" stroke-dasharray="4 4" />

                                <!-- Clean Anatomical Foot Outline (Top View) -->
                                <path d="M 130 150 C 130 120, 155 110, 185 110 C 220 110, 240 125, 270 120 C 305 115, 335 110, 365 125 C 380 135, 380 160, 365 175 C 340 190, 310 185, 275 180 C 235 175, 215 190, 180 190 C 150 190, 130 180, 130 150 Z" 
                                      fill="url(#footGrad)" stroke="#dcb348" stroke-width="2.2" filter="url(#footGlow)" />

                                <!-- Anatomical Foot Toes Detailing -->
                                <ellipse cx="360" cy="135" rx="12" ry="10" fill="#323c57" stroke="#dcb348" stroke-width="1.5" />
                                <ellipse cx="355" cy="152" rx="9" ry="7" fill="#283046" stroke="#3a405a" stroke-width="1.2" />
                                <ellipse cx="346" cy="165" rx="8" ry="6" fill="#283046" stroke="#3a405a" stroke-width="1.2" />
                                <ellipse cx="335" cy="174" rx="7" ry="5.5" fill="#283046" stroke="#3a405a" stroke-width="1.2" />
                                <ellipse cx="323" cy="180" rx="6" ry="5" fill="#283046" stroke="#3a405a" stroke-width="1.2" />

                                <!-- Foot Arch & Heel Contour -->
                                <path d="M 175 150 C 175 135, 200 130, 230 140" stroke="rgba(212,175,55,0.4)" stroke-width="1.5" stroke-dasharray="3 3" fill="none" />
                                <circle cx="155" cy="150" r="14" fill="rgba(212,175,55,0.08)" stroke="rgba(212,175,55,0.3)" stroke-width="1.2" />

                                <!-- Width Dimension Line & Badge -->
                                <line x1="280" y1="108" x2="280" y2="192" stroke="#dcb348" stroke-width="2.8" />
                                <circle cx="280" cy="108" r="4.5" fill="#dcb348" />
                                <circle cx="280" cy="192" r="4.5" fill="#dcb348" />
                                <g transform="translate(225, 45)">
                                    <rect width="110" height="28" rx="14" fill="#0b0e17" stroke="#dcb348" stroke-width="1.8" />
                                    <text x="55" y="18" fill="#dcb348" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $dimWidthLabel; ?>
                                    </text>
                                </g>

                                <!-- Height Dimension Line & Badge -->
                                <line x1="130" y1="285" x2="375" y2="285" stroke="#f43f5e" stroke-width="3" />
                                <circle cx="130" cy="285" r="4.5" fill="#f43f5e" />
                                <circle cx="375" cy="285" r="4.5" fill="#f43f5e" />
                                <line x1="130" y1="150" x2="130" y2="285" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                <line x1="375" y1="135" x2="375" y2="285" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                
                                <g transform="translate(200, 268)">
                                    <rect width="110" height="28" rx="14" fill="#0b0e17" stroke="#f43f5e" stroke-width="1.8" />
                                    <text x="55" y="18" fill="#f43f5e" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $dimHeightLabel; ?>
                                    </text>
                                </g>
                            </svg>

                        <?php else: ?>
                            <!-- ================= 👕 T-SHIRT & TOPS SCHEMATIC ================= -->
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

                                <!-- T-Shirt Silhouette -->
                                <path d="M 200 48 C 215 40, 240 36, 270 36 C 300 36, 325 40, 340 48 L 415 85 L 380 140 L 340 120 L 340 275 C 340 283, 332 290, 322 290 L 218 290 C 208 290, 200 283, 200 275 L 200 120 L 160 140 L 125 85 Z" 
                                      fill="url(#shirtGradClean)" stroke="#3a405a" stroke-width="2.2" stroke-linejoin="round" filter="url(#shirtGlowClean)" />
                                
                                <!-- Collar Line -->
                                <path d="M 235 48 Q 270 76 305 48" stroke="#dcb348" stroke-width="2" fill="none" />
                                
                                <!-- Stitch Guides -->
                                <path d="M 200 120 L 230 65" stroke="#2a2e42" stroke-width="1.5" stroke-dasharray="4 4" />
                                <path d="M 340 120 L 310 65" stroke="#2a2e42" stroke-width="1.5" stroke-dasharray="4 4" />
                                
                                <!-- Width Dimension & Badge -->
                                <line x1="200" y1="150" x2="340" y2="150" stroke="#dcb348" stroke-width="3.5" />
                                <circle cx="200" cy="150" r="5" fill="#dcb348" />
                                <circle cx="340" cy="150" r="5" fill="#dcb348" />
                                
                                <g transform="translate(215, 98)">
                                    <rect width="110" height="28" rx="14" fill="#0a0d16" stroke="#dcb348" stroke-width="2" />
                                    <text x="55" y="18" fill="#dcb348" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $dimWidthLabel; ?>
                                    </text>
                                </g>

                                <!-- Height Dimension & Badge -->
                                <line x1="75" y1="36" x2="270" y2="36" stroke="rgba(244,63,94,0.35)" stroke-width="1.2" stroke-dasharray="4 3" />
                                <line x1="75" y1="290" x2="270" y2="290" stroke="rgba(244,63,94,0.35)" stroke-width="1.2" stroke-dasharray="4 3" />

                                <line x1="75" y1="36" x2="75" y2="290" stroke="#f43f5e" stroke-width="2.8" />
                                <circle cx="75" cy="36" r="4.5" fill="#f43f5e" />
                                <circle cx="75" cy="290" r="4.5" fill="#f43f5e" />

                                <g transform="translate(20, 145)">
                                    <rect width="110" height="28" rx="14" fill="#0a0d16" stroke="#f43f5e" stroke-width="2" />
                                    <text x="55" y="18" fill="#f43f5e" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $dimHeightLabel; ?>
                                    </text>
                                </g>
                            </svg>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- Right Column: 3 Measurement Instructions -->
                <div class="measure-steps-list">
                    <div class="measure-step-item">
                        <span class="step-num">1</span>
                        <div class="step-text">
                            <strong><?php echo t('how_to_measure_step1_title', $lang); ?></strong>
                            <span><?php echo $curCat['step1']; ?></span>
                        </div>
                    </div>
                    <div class="measure-step-item width-accent">
                        <span class="step-num">2</span>
                        <div class="step-text">
                            <strong><?php echo $lang === 'ku' ? '٢. پانی' : ($lang === 'ar' ? '٢. العرض' : '2. Width'); ?></strong>
                            <span><?php echo $curCat['step2']; ?></span>
                        </div>
                    </div>
                    <div class="measure-step-item height-accent">
                        <span class="step-num">3</span>
                        <div class="step-text">
                            <strong><?php echo $lang === 'ku' ? '٣. بلندی' : ($lang === 'ar' ? '٣. الارتفاع' : '3. Height'); ?></strong>
                            <span><?php echo $curCat['step3']; ?></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Smart Return Action Button -->
            <div class="text-center guide-bottom-actions" style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin-top: 52px; padding-top: 36px; border-top: 1px solid rgba(255, 255, 255, 0.08);">
                <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-primary" style="padding: 15px 36px; border-radius: 12px; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; color: #0c0e14; background: linear-gradient(135deg, #dcb348 0%, #b8932d 100%); font-weight: 700; font-size: 15px; box-shadow: 0 6px 20px rgba(212, 175, 55, 0.3); transition: all 0.2s ease;">
                    <?php echo $backBtnLabel; ?>
                </a>
                <a href="shop.php" class="btn" style="padding: 15px 30px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.05); color: #f3f4f6; text-decoration: none; font-weight: 600; font-size: 14.5px;">
                    <?php echo $lang === 'ku' ? 'گەڕان د ناڤ هەمی کۆلێکشنان دا' : ($lang === 'ar' ? 'تصفح كافة التشكيلات' : 'Explore Collections'); ?>
                </a>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
