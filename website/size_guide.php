<?php
$activePage = 'shop';
$pageTitle = 'Size & Fit Guide';
require_once __DIR__ . '/header.php';

$variant = strtolower(trim($_GET['v'] ?? 'tshirt'));
if ($variant === 'shirts' || $variant === 'top' || $variant === 'tops' || $variant === 't-shirt') {
    $variant = 'tshirt';
} elseif ($variant === 'pants' || $variant === 'trousers' || $variant === 'jean') {
    $variant = 'jeans';
} elseif ($variant === 'jackets' || $variant === 'coat' || $variant === 'blazer' || $variant === 'outerwear') {
    $variant = 'jacket';
} elseif ($variant === 'shoes' || $variant === 'shoe' || $variant === 'footwear' || $variant === 'sneakers' || $variant === 'foot' || $variant === 'feet') {
    $variant = 'feet';
} else {
    $variant = 'tshirt';
}

$productId = intval($_GET['pid'] ?? ($_GET['id'] ?? 0));
$selectedSize = trim($_GET['size'] ?? '');
$heightParam = trim($_GET['h'] ?? '');
$widthParam = trim($_GET['w'] ?? '');
$backUrl = trim($_GET['from'] ?? '');

$product = null;
$productTitle = '';
$productSizes = [];
$productMeasurements = [];

if ($productId > 0 && function_exists('get_product_by_id')) {
    $product = get_product_by_id($productId);
    if ($product) {
        $productTitle = is_array($product['title']) ? ($product['title'][$lang] ?? $product['title']['en']) : $product['title'];
        $productSizes = $product['sizes'] ?? [];
        
        $rawMeasurements = $product['size_measurements'] ?? [];
        if (is_string($rawMeasurements)) {
            $productMeasurements = json_decode($rawMeasurements, true) ?: [];
        } elseif (is_array($rawMeasurements)) {
            $productMeasurements = $rawMeasurements;
        }

        // Auto-detect variant from product if not explicitly specified in URL
        if (empty($_GET['v'])) {
            $pCat = strtolower($product['category'] ?? '');
            $pTitle = strtolower(is_array($product['title']) ? ($product['title']['en'] ?? '') : $product['title']);
            if (str_contains($pCat, 'shoe') || str_contains($pTitle, 'shoe') || str_contains($pTitle, 'sneaker') || str_contains($pTitle, 'boot') || str_contains($pCat, 'feet')) {
                $variant = 'feet';
            } elseif (str_contains($pTitle, 'jean') || str_contains($pTitle, 'pant') || str_contains($pTitle, 'trouser') || str_contains($pCat, 'pant')) {
                $variant = 'jeans';
            } elseif (str_contains($pTitle, 'jacket') || str_contains($pTitle, 'coat') || str_contains($pTitle, 'blazer') || str_contains($pTitle, 'hoodie')) {
                $variant = 'jacket';
            } else {
                $variant = 'tshirt';
            }
        }
    }
}

// Fallback dimensions logic based on category and size
if (empty($selectedSize) && !empty($productSizes)) {
    $selectedSize = $productSizes[0];
}

$activeDimHeight = $heightParam;
$activeDimWidth = $widthParam;

if (empty($activeDimHeight) || empty($activeDimWidth)) {
    if (!empty($productMeasurements) && !empty($selectedSize) && isset($productMeasurements[$selectedSize])) {
        $mRaw = $productMeasurements[$selectedSize];
        if (preg_match('/(?:Length|Height|Jacket|بلندی|درێژی|الطول)[:\s]*([0-9.]+\s*(?:cm)?)/i', $mRaw, $mH)) {
            $activeDimHeight = trim($mH[1]);
        }
        if (preg_match('/(?:Width|Chest|Trousers|پانی|الصدر|العرض)[:\s]*([0-9.]+\s*(?:cm)?)/i', $mRaw, $mW)) {
            $activeDimWidth = trim($mW[1]);
        }
    }
}

// Category Default Dimensions in CM
if (empty($activeDimHeight) || empty($activeDimWidth)) {
    $sz = strtoupper($selectedSize ?: 'M');
    if ($variant === 'feet') {
        $num = floatval($sz);
        if ($num >= 35 && $num <= 48) {
            $activeDimHeight = number_format(24.0 + ($num - 38) * 0.65, 1) . 'cm';
            $activeDimWidth = '9.8cm';
        } else {
            $activeDimHeight = !empty($activeDimHeight) ? $activeDimHeight : '27.0cm';
            $activeDimWidth = !empty($activeDimWidth) ? $activeDimWidth : '9.8cm';
        }
    } elseif ($variant === 'jeans') {
        if ($sz === '30' || $sz === 'S') { $activeDimHeight = '102cm'; $activeDimWidth = '78cm'; }
        elseif ($sz === '32' || $sz === 'M') { $activeDimHeight = '104cm'; $activeDimWidth = '82cm'; }
        elseif ($sz === '34' || $sz === 'L') { $activeDimHeight = '106cm'; $activeDimWidth = '86cm'; }
        elseif ($sz === '36' || $sz === 'XL') { $activeDimHeight = '108cm'; $activeDimWidth = '92cm'; }
        else { $activeDimHeight = '104cm'; $activeDimWidth = '82cm'; }
    } elseif ($variant === 'jacket') {
        if ($sz === 'S') { $activeDimHeight = '68cm'; $activeDimWidth = '52cm'; }
        elseif ($sz === 'M') { $activeDimHeight = '71cm'; $activeDimWidth = '55cm'; }
        elseif ($sz === 'L') { $activeDimHeight = '74cm'; $activeDimWidth = '58cm'; }
        elseif ($sz === 'XL') { $activeDimHeight = '77cm'; $activeDimWidth = '62cm'; }
        else { $activeDimHeight = '71cm'; $activeDimWidth = '55cm'; }
    } else { // tshirt / tops
        if ($sz === 'XS') { $activeDimHeight = '62cm'; $activeDimWidth = '42cm'; }
        elseif ($sz === 'S') { $activeDimHeight = '65cm'; $activeDimWidth = '45cm'; }
        elseif ($sz === 'M') { $activeDimHeight = '70cm'; $activeDimWidth = '50cm'; }
        elseif ($sz === 'L') { $activeDimHeight = '73cm'; $activeDimWidth = '54cm'; }
        elseif ($sz === 'XL') { $activeDimHeight = '76cm'; $activeDimWidth = '58cm'; }
        elseif ($sz === 'XXL' || $sz === '2XL') { $activeDimHeight = '79cm'; $activeDimWidth = '62cm'; }
        else { $activeDimHeight = '70cm'; $activeDimWidth = '50cm'; }
    }
}

// Clean up any non-cm suffixes
if (!str_contains($activeDimHeight, 'cm') && is_numeric($activeDimHeight)) {
    $activeDimHeight .= 'cm';
}
if (!str_contains($activeDimWidth, 'cm') && is_numeric($activeDimWidth)) {
    $activeDimWidth .= 'cm';
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

// Helper query string for tabs to preserve product context
$tabQuery = '';
if ($productId > 0) $tabQuery .= '&pid=' . $productId;
if (!empty($backUrl)) $tabQuery .= '&from=' . urlencode($backUrl);

// Category localized titles & labels (Only Apparel & Feet Size)
$catMeta = [
    'tshirt' => [
        'name' => ($lang === 'ku' ? 'تیشێرت و سەرپۆش' : ($lang === 'ar' ? 'تيشيرت وقمصان' : 'Shirts & Tops')),
        'icon' => '👕',
        'dim1_label' => ($lang === 'ku' ? 'بلندی / درێژی' : ($lang === 'ar' ? 'الارتفاع / الطول' : 'Total Length')),
        'dim2_label' => ($lang === 'ku' ? 'پانی / سینگ' : ($lang === 'ar' ? 'العرض / الصدر' : 'Chest Width')),
        'step1' => ($lang === 'ku' ? 'پارچەیێ بەردە ل سەر مێزەکا تەخت و بێ چەماندن ڕابکێشە.' : ($lang === 'ar' ? 'افرد القطعة بشكل مستوٍ على سطح صلب بدون طيات.' : 'Lay your favorite garment flat on a firm, smooth surface.')),
        'step2' => ($lang === 'ku' ? 'پانیێ ژ ژێر ملێ چەپێ بۆ یێ ڕاستێ ب سانتیمەتر (cm) بپێڤە.' : ($lang === 'ar' ? 'قس المسافة الأفقية بين الإبطين بالسنتيمتر (cm).' : 'Measure horizontally from pit to pit in centimeters (cm).')),
        'step3' => ($lang === 'ku' ? 'بلندیێ ژ بلندترین خالێ ملان هەتا بنێ جلوبەرگی ب سانتیمەتر (cm) بپێڤە.' : ($lang === 'ar' ? 'قس المسافة الرأسية من أعلى نقطة في الكتف إلى الحافة السفلية بالسنتيمتر (cm).' : 'Measure vertically from shoulder top to bottom hem in centimeters (cm).'))
    ],
    'jeans' => [
        'name' => ($lang === 'ku' ? 'پانتۆل و جینز' : ($lang === 'ar' ? 'بناطيل وجينز' : 'Jeans & Pants')),
        'icon' => '👖',
        'dim1_label' => ($lang === 'ku' ? 'درێژی / درێژیا قاچی' : ($lang === 'ar' ? 'طول البنطال الكامل' : 'Total Inseam / Length')),
        'dim2_label' => ($lang === 'ku' ? 'کەمەر / ناڤتەنگ' : ($lang === 'ar' ? 'محيط الخصر' : 'Waist Width')),
        'step1' => ($lang === 'ku' ? 'پانتۆلەکێ گونجای بەردە ل سەر مێزێ و قۆپچەی دابخە.' : ($lang === 'ar' ? 'افرد بنطالك المفضل مع إغلاق الزر والسحاب بالكامل.' : 'Button up and lay your trousers flat on a flat surface.')),
        'step2' => ($lang === 'ku' ? 'پانییا کەمەرێ (Waist) ژ لایەکێ بۆ لایێ دی ب سانتیمەتر (cm) بپێڤە.' : ($lang === 'ar' ? 'قس عرض الخصر من الحافة إلى الحافة بالسنتيمتر (cm).' : 'Measure across the waistband from side edge to side edge in cm.')),
        'step3' => ($lang === 'ku' ? 'درێژییا دروست ژ سەرێ کەمەرێ هەتا بنی ب سانتیمەتر (cm) بپێڤە.' : ($lang === 'ar' ? 'قس الطول من أعلى حزام الخصر إلى نهاية طرف البنطال بالسنتيمتر (cm).' : 'Measure along outseam from waistband top to hem leg opening in cm.'))
    ],
    'jacket' => [
        'name' => ($lang === 'ku' ? 'چاکەت و قەمسەلە' : ($lang === 'ar' ? 'جاكيتات ومعاطف' : 'Jackets & Coats')),
        'icon' => '🧥',
        'dim1_label' => ($lang === 'ku' ? 'درێژیا چاکەتی' : ($lang === 'ar' ? 'طول الجاكيت' : 'Jacket Length')),
        'dim2_label' => ($lang === 'ku' ? 'پانییا سینگی' : ($lang === 'ar' ? 'عرض الصدر' : 'Chest Width')),
        'step1' => ($lang === 'ku' ? 'قۆپچەیێن چاکەتی دابخە و ل سەر ڕوویەک تەخت دابنێ.' : ($lang === 'ar' ? 'أغلق أزرار الجاكيت وافرده بسلاسة على سطح مستوٍ.' : 'Fasten buttons and lay the blazer flat on a level table.')),
        'step2' => ($lang === 'ku' ? 'پانییا ژێر هەردوو ملان بەرفرەهی ب سانتیمەتر (cm) بپێڤە.' : ($lang === 'ar' ? 'قس عرض الصدر أفقياً بين منطقتي الإبطين بالسنتيمتر (cm).' : 'Measure pit-to-pit across chest with fabric smoothly spread in cm.')),
        'step3' => ($lang === 'ku' ? 'درێژیا پشتا چاکەتی ژ بنێ یەخەی هەتا خوارێ ب سانتیمەتر (cm) بپێڤە.' : ($lang === 'ar' ? 'قس الطول من أسفل ياقة الرقبة حتى نهاية الجاكيت من الخلف بالسنتيمتر (cm).' : 'Measure center back length from below collar down to hem in cm.'))
    ],
    'feet' => [
        'name' => ($lang === 'ku' ? 'قەبارێ پێیان' : ($lang === 'ar' ? 'مقاس وطول القدم' : 'Feet & Foot Size')),
        'icon' => '🦶',
        'dim1_label' => ($lang === 'ku' ? 'درێژیا پێی (سم)' : ($lang === 'ar' ? 'طول القدم (سم)' : 'Foot Length (cm)')),
        'dim2_label' => ($lang === 'ku' ? 'پانییا پێی (سم)' : ($lang === 'ar' ? 'عرض القدم (سم)' : 'Foot Width (cm)')),
        'step1' => ($lang === 'ku' ? 'کاغەزەکێ ل سەر ئەردی دابنێ و پێیێ خۆ ب دورستی ل سەر ڕابگرە.' : ($lang === 'ar' ? 'ضع ورقة بيضاء على الأرض وقف عليها بوزنك الكامل وجورب مناسب.' : 'Place a white paper sheet on the floor and stand firmly on it with normal socks.')),
        'step2' => ($lang === 'ku' ? 'ب قەلەمەکێ دۆروبەرێ پنیا پێی و سەرێ تلیا مەزن هێڵەکێ بکێشە.' : ($lang === 'ar' ? 'حدد بقلم أبعد نقطة في الكعب وأطول إصبع في قدمك.' : 'Mark the backmost edge of your heel and the tip of your longest toe.')),
        'step3' => ($lang === 'ku' ? 'مەودایا ناڤبەرا هەردوو خالان ب سانتیمەتر (cm) بپێڤە.' : ($lang === 'ar' ? 'قس المسافة بين النقطتين بالسنتيمتر (cm) لمعرفة مقاس قدمك الدقيق.' : 'Measure the exact distance between the two points in centimeters (cm).'))
    ]
];

$curCat = $catMeta[$variant] ?? $catMeta['tshirt'];
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">Maison Aura Atelier</span>
            <h1 class="page-banner-title"><?php echo t('how_to_measure_title', $lang); ?></h1>
            <p class="page-banner-subtitle">
                <?php 
                if ($lang === 'ku') echo 'رێبەرێ پێشکەفتی یێ قیاسێن دروست ب سانتیمەتر (cm) بۆ دەستکەفتنا قیاسێ ١٠٠٪ ڕاستەقینە';
                elseif ($lang === 'ar') echo 'دليل المقاسات الهندسي الدقيق بوحدة السنتيمتر (سم) لاختيار مقاسك المثالي بدقة متناهية';
                else echo 'Precision anatomical sizing blueprints and dimension guide measured in Centimeters (cm)';
                ?>
            </p>
        </div>
    </div>
</div>

<section class="size-guide-page-section py-60">
    <div class="container">
        
        <!-- Category Navigation Tabs (Apparel & Feet Size Only) -->
        <div class="size-guide-tabs-nav">
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
                <span class="tab-icon">🦶</span>
                <span><?php echo $lang === 'ku' ? 'قەبارێ پێیان' : ($lang === 'ar' ? 'مقاس وطول القدم' : 'Feet & Foot Size'); ?></span>
            </a>
        </div>

        <div class="size-guide-content-card" dir="<?php echo $dir; ?>">
            
            <!-- Top Controls Bar: Active Product & Metric Standard Indicator -->
            <div class="guide-controls-top-bar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 28px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 20px;">
                
                <?php if ($product): ?>
                    <div class="guide-product-badge-wrap" style="display: inline-flex; align-items: center; gap: 10px; background: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.3); padding: 8px 18px; border-radius: 30px;">
                        <span style="color: #dcb348; font-size: 14px;">✦</span>
                        <span style="color: #f3f4f6; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($productTitle); ?></span>
                        <?php if (!empty($selectedSize)): ?>
                            <span style="color: rgba(255,255,255,0.3);">|</span>
                            <span style="color: #dcb348; font-size: 13.5px; font-weight: 700;"><?php echo $lang === 'ku' ? 'قیاس:' : ($lang === 'ar' ? 'المقاس:' : 'Size:'); ?> <span id="currentActiveSizeBadge"><?php echo htmlspecialchars($selectedSize); ?></span></span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="display: inline-flex; align-items: center; gap: 8px; color: #dcb348; font-size: 14px; font-weight: 700;">
                        <span style="font-size: 18px;"><?php echo $curCat['icon']; ?></span>
                        <span><?php echo $curCat['name']; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Clear Metric Unit Tag (Strictly Centimeters cm) -->
                <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(212, 175, 55, 0.12); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 20px; padding: 6px 14px; font-size: 12.5px; font-weight: 700; color: #dcb348;">
                    <span>📐</span>
                    <span><?php echo $lang === 'ku' ? 'پیڤان ب سانتیمەتر (cm)' : ($lang === 'ar' ? 'القياسات بالسنتيمتر (cm)' : 'Dimensions in Centimeters (cm)'); ?></span>
                </div>

            </div>

            <!-- Product Interactive Size Selector (If viewing a product) -->
            <?php if (!empty($productSizes) && count($productSizes) > 0): ?>
                <div class="guide-product-size-selector-row" style="background: rgba(13, 15, 24, 0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 16px 20px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                    <span style="font-size: 13.5px; font-weight: 700; color: #e5e7eb;">
                        <?php echo $lang === 'ku' ? 'قیاسەکێ بەرهەمی تاقی بکە:' : ($lang === 'ar' ? 'اختر مقاساً لمعاينة أبعاده الهندسية:' : 'Select Size to View Blueprint:'); ?>
                    </span>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php foreach ($productSizes as $sPill): 
                            $isActivePill = (strtoupper(trim($sPill)) === strtoupper(trim($selectedSize)));
                            $pillMeasurement = $productMeasurements[$sPill] ?? '';
                        ?>
                            <button type="button" 
                                    class="guide-size-pill <?php echo $isActivePill ? 'active' : ''; ?>"
                                    data-size="<?php echo htmlspecialchars($sPill); ?>"
                                    data-measurement="<?php echo htmlspecialchars($pillMeasurement); ?>"
                                    onclick="onGuideSizeClicked(this, '<?php echo htmlspecialchars(addslashes($sPill)); ?>')"
                                    style="padding: 7px 16px; border-radius: 10px; font-size: 13.5px; font-weight: 700; cursor: pointer; border: 1px solid <?php echo $isActivePill ? '#dcb348' : 'rgba(255,255,255,0.15)'; ?>; background: <?php echo $isActivePill ? 'linear-gradient(135deg, rgba(212,175,55,0.25), rgba(212,175,55,0.1))' : 'rgba(255,255,255,0.04)'; ?>; color: <?php echo $isActivePill ? '#dcb348' : '#e5e7eb'; ?>; transition: all 0.2s;">
                                <?php echo htmlspecialchars($sPill); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="guide-variant-header">
                <h2><?php echo $curCat['icon']; ?> <?php echo $curCat['name']; ?> — <?php echo $lang === 'ku' ? 'رێبەرێ پیڤانێن تەواو ب سانتیمەتر' : ($lang === 'ar' ? 'دليل القياسات والمخطط الهندسي' : 'Precision Fit Blueprint (cm)'); ?></h2>
                <p><?php echo $lang === 'ku' ? 'بۆ دەستکەفتنا قیاسێ د دروستاهیێ دا، پیڤانێن خۆ ب دووڤ ڤی شێوازێ دیارکری بگرە:' : ($lang === 'ar' ? 'لضمان القياس المثالي الذي يلائمك، اتبع المخطط التوضيحي والخطوات التالية:' : 'Follow our tailored anatomical blueprint below to ensure absolute precision and flawless fit:'); ?></p>
            </div>

            <!-- Grid Layout: Dynamic SVG Schematic + 3 Measurement Steps -->
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

                                <!-- Waist Width Dimension Line & Badge -->
                                <line x1="195" y1="36" x2="305" y2="36" stroke="#dcb348" stroke-width="3" />
                                <circle cx="195" cy="36" r="4.5" fill="#dcb348" />
                                <circle cx="305" cy="36" r="4.5" fill="#dcb348" />
                                <g transform="translate(180, 2)">
                                    <rect width="140" height="28" rx="14" fill="#0b0e17" stroke="#dcb348" stroke-width="1.8" />
                                    <text x="70" y="19" fill="#dcb348" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $curCat['dim2_label']; ?>: <tspan id="svgValWidth"><?php echo htmlspecialchars($activeDimWidth); ?></tspan>
                                    </text>
                                </g>

                                <!-- Total Length Dimension Line & Badge -->
                                <line x1="130" y1="40" x2="130" y2="280" stroke="#f43f5e" stroke-width="2.6" stroke-dasharray="6 4" />
                                <circle cx="130" cy="40" r="4" fill="#f43f5e" />
                                <circle cx="130" cy="280" r="4" fill="#f43f5e" />
                                <line x1="130" y1="40" x2="195" y2="40" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                <line x1="130" y1="280" x2="172" y2="280" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                <g transform="translate(10, 145)">
                                    <rect width="145" height="30" rx="15" fill="#0b0e17" stroke="#f43f5e" stroke-width="1.8" />
                                    <text x="72" y="20" fill="#f43f5e" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $curCat['dim1_label']; ?>: <tspan id="svgValHeight"><?php echo htmlspecialchars($activeDimHeight); ?></tspan>
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

                                <!-- Chest Width Dimension Line & Badge -->
                                <line x1="175" y1="140" x2="325" y2="140" stroke="#dcb348" stroke-width="3" />
                                <circle cx="175" cy="140" r="5" fill="#dcb348" />
                                <circle cx="325" cy="140" r="5" fill="#dcb348" />
                                <g transform="translate(180, 95)">
                                    <rect width="140" height="30" rx="15" fill="#0b0e17" stroke="#dcb348" stroke-width="1.8" />
                                    <text x="70" y="20" fill="#dcb348" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $curCat['dim2_label']; ?>: <tspan id="svgValWidth"><?php echo htmlspecialchars($activeDimWidth); ?></tspan>
                                    </text>
                                </g>

                                <!-- Jacket Length Dimension Line & Badge -->
                                <line x1="70" y1="38" x2="70" y2="288" stroke="#f43f5e" stroke-width="2.6" />
                                <circle cx="70" cy="38" r="4.5" fill="#f43f5e" />
                                <circle cx="70" cy="288" r="4.5" fill="#f43f5e" />
                                <line x1="70" y1="38" x2="230" y2="38" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                <line x1="70" y1="288" x2="195" y2="288" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                <g transform="translate(10, 148)">
                                    <rect width="140" height="30" rx="15" fill="#0b0e17" stroke="#f43f5e" stroke-width="1.8" />
                                    <text x="70" y="20" fill="#f43f5e" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $curCat['dim1_label']; ?>: <tspan id="svgValHeight"><?php echo htmlspecialchars($activeDimHeight); ?></tspan>
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
                                <!-- Heel at Left (x=130), Ball of foot, Big Toe & Toes at Right (x=370) -->
                                <path d="M 130 150 C 130 120, 155 110, 185 110 C 220 110, 240 125, 270 120 C 305 115, 335 110, 365 125 C 380 135, 380 160, 365 175 C 340 190, 310 185, 275 180 C 235 175, 215 190, 180 190 C 150 190, 130 180, 130 150 Z" 
                                      fill="url(#footGrad)" stroke="#dcb348" stroke-width="2.2" filter="url(#footGlow)" />

                                <!-- Anatomical Foot Toes Visual Detailing -->
                                <!-- Big toe -->
                                <ellipse cx="360" cy="135" rx="12" ry="10" fill="#323c57" stroke="#dcb348" stroke-width="1.5" />
                                <!-- 2nd toe -->
                                <ellipse cx="355" cy="152" rx="9" ry="7" fill="#283046" stroke="#3a405a" stroke-width="1.2" />
                                <!-- 3rd toe -->
                                <ellipse cx="346" cy="165" rx="8" ry="6" fill="#283046" stroke="#3a405a" stroke-width="1.2" />
                                <!-- 4th toe -->
                                <ellipse cx="335" cy="174" rx="7" ry="5.5" fill="#283046" stroke="#3a405a" stroke-width="1.2" />
                                <!-- Pinky toe -->
                                <ellipse cx="323" cy="180" rx="6" ry="5" fill="#283046" stroke="#3a405a" stroke-width="1.2" />

                                <!-- Foot Arch & Heel Contour -->
                                <path d="M 175 150 C 175 135, 200 130, 230 140" stroke="rgba(212,175,55,0.4)" stroke-width="1.5" stroke-dasharray="3 3" fill="none" />
                                <circle cx="155" cy="150" r="14" fill="rgba(212,175,55,0.08)" stroke="rgba(212,175,55,0.3)" stroke-width="1.2" />

                                <!-- Foot Width Dimension Line & Badge -->
                                <line x1="280" y1="108" x2="280" y2="192" stroke="#dcb348" stroke-width="2.8" />
                                <circle cx="280" cy="108" r="4.5" fill="#dcb348" />
                                <circle cx="280" cy="192" r="4.5" fill="#dcb348" />
                                <g transform="translate(205, 45)">
                                    <rect width="150" height="30" rx="15" fill="#0b0e17" stroke="#dcb348" stroke-width="1.8" />
                                    <text x="75" y="20" fill="#dcb348" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $curCat['dim2_label']; ?>: <tspan id="svgValWidth"><?php echo htmlspecialchars($activeDimWidth); ?></tspan>
                                    </text>
                                </g>

                                <!-- Foot Length Dimension Line & Badge (Heel to Longest Toe) -->
                                <line x1="130" y1="285" x2="375" y2="285" stroke="#f43f5e" stroke-width="3" />
                                <circle cx="130" cy="285" r="4.5" fill="#f43f5e" />
                                <circle cx="375" cy="285" r="4.5" fill="#f43f5e" />
                                <line x1="130" y1="150" x2="130" y2="285" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                <line x1="375" y1="135" x2="375" y2="285" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                
                                <g transform="translate(175, 268)">
                                    <rect width="160" height="30" rx="15" fill="#0b0e17" stroke="#f43f5e" stroke-width="1.8" />
                                    <text x="80" y="20" fill="#f43f5e" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $curCat['dim1_label']; ?>: <tspan id="svgValHeight"><?php echo htmlspecialchars($activeDimHeight); ?></tspan>
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
                                
                                <!-- Chest Width Dimension & Badge -->
                                <line x1="200" y1="150" x2="340" y2="150" stroke="#dcb348" stroke-width="3.5" />
                                <circle cx="200" cy="150" r="5" fill="#dcb348" />
                                <circle cx="340" cy="150" r="5" fill="#dcb348" />
                                
                                <g transform="translate(195, 98)">
                                    <rect width="150" height="30" rx="15" fill="#0a0d16" stroke="#dcb348" stroke-width="2" />
                                    <text x="75" y="20" fill="#dcb348" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $curCat['dim2_label']; ?>: <tspan id="svgValWidth"><?php echo htmlspecialchars($activeDimWidth); ?></tspan>
                                    </text>
                                </g>

                                <!-- Total Height / Length Dimension & Badge -->
                                <line x1="75" y1="36" x2="270" y2="36" stroke="rgba(244,63,94,0.35)" stroke-width="1.2" stroke-dasharray="4 3" />
                                <line x1="75" y1="290" x2="270" y2="290" stroke="rgba(244,63,94,0.35)" stroke-width="1.2" stroke-dasharray="4 3" />

                                <line x1="75" y1="36" x2="75" y2="290" stroke="#f43f5e" stroke-width="2.8" />
                                <circle cx="75" cy="36" r="4.5" fill="#f43f5e" />
                                <circle cx="75" cy="290" r="4.5" fill="#f43f5e" />

                                <g transform="translate(10, 145)">
                                    <rect width="135" height="30" rx="15" fill="#0a0d16" stroke="#f43f5e" stroke-width="2" />
                                    <text x="67" y="20" fill="#f43f5e" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $curCat['dim1_label']; ?>: <tspan id="svgValHeight"><?php echo htmlspecialchars($activeDimHeight); ?></tspan>
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
                            <strong><?php echo t('how_to_measure_step2_title', $lang); ?> (<?php echo $curCat['dim2_label']; ?>)</strong>
                            <span><?php echo $curCat['step2']; ?></span>
                        </div>
                    </div>
                    <div class="measure-step-item height-accent">
                        <span class="step-num">3</span>
                        <div class="step-text">
                            <strong><?php echo t('how_to_measure_step3_title', $lang); ?> (<?php echo $curCat['dim1_label']; ?>)</strong>
                            <span><?php echo $curCat['step3']; ?></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Smart Return Action Button -->
            <div class="text-center mt-40" style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
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

<script>
// Interactive Size Guide Scripts (Pure Centimeter cm standard)
const activeCategoryVariant = '<?php echo $variant; ?>';

function parseDimNumber(str) {
    if (!str) return 0;
    const clean = String(str).replace(/[^0-9.]/g, '');
    return parseFloat(clean) || 0;
}

function formatCm(val) {
    if (!val || val <= 0) return '';
    return `${val}cm`;
}

function updateSchematicDimensions() {
    const hEl = document.getElementById('svgValHeight');
    const wEl = document.getElementById('svgValWidth');

    if (hEl && window.baseHeightVal) {
        hEl.innerText = window.baseHeightVal;
    }
    if (wEl && window.baseWidthVal) {
        wEl.innerText = window.baseWidthVal;
    }
}

// Extract base dimension values initially
document.addEventListener('DOMContentLoaded', function() {
    window.baseHeightVal = '<?php echo addslashes($activeDimHeight); ?>';
    window.baseWidthVal = '<?php echo addslashes($activeDimWidth); ?>';
});

function onGuideSizeClicked(btn, sizeName) {
    // 1. Highlight pill
    document.querySelectorAll('.guide-size-pill').forEach(b => {
        b.style.borderColor = 'rgba(255,255,255,0.15)';
        b.style.background = 'rgba(255,255,255,0.04)';
        b.style.color = '#e5e7eb';
        b.classList.remove('active');
    });

    btn.style.borderColor = '#dcb348';
    btn.style.background = 'linear-gradient(135deg, rgba(212,175,55,0.25), rgba(212,175,55,0.1))';
    btn.style.color = '#dcb348';
    btn.classList.add('active');

    // 2. Update badge
    const badge = document.getElementById('currentActiveSizeBadge');
    if (badge) badge.innerText = sizeName;

    // 3. Extract dimensions (pure cm)
    const mStr = btn.getAttribute('data-measurement') || '';
    let height = '';
    let width = '';

    if (mStr) {
        const hMatch = mStr.match(/(?:Length|Height|Jacket|بلندی|درێژی|الطول)[:\s]*([0-9.]+\s*(?:cm)?)/i);
        if (hMatch) height = hMatch[1].trim();
        const wMatch = mStr.match(/(?:Width|Chest|Trousers|پانی|الصدر|العرض)[:\s]*([0-9.]+\s*(?:cm)?)/i);
        if (wMatch) width = wMatch[1].trim();
    }

    if (!height || !width) {
        const sz = String(sizeName).toUpperCase().trim();
        if (activeCategoryVariant === 'jeans') {
            if (sz === '28' || sz === 'XS') { height = '98cm'; width = '72cm'; }
            else if (sz === '30' || sz === 'S') { height = '102cm'; width = '78cm'; }
            else if (sz === '32' || sz === 'M') { height = '104cm'; width = '82cm'; }
            else if (sz === '34' || sz === 'L') { height = '106cm'; width = '86cm'; }
            else if (sz === '36' || sz === 'XL') { height = '108cm'; width = '92cm'; }
            else if (sz === '38' || sz === 'XXL') { height = '110cm'; width = '98cm'; }
            else { height = '104cm'; width = '82cm'; }
        } else if (activeCategoryVariant === 'feet') {
            const num = parseFloat(sz);
            if (num && num >= 35 && num <= 48) {
                height = (24.0 + (num - 38) * 0.65).toFixed(1) + 'cm';
                width = '9.8cm';
            } else {
                height = '27.0cm';
                width = '9.8cm';
            }
        } else if (activeCategoryVariant === 'jacket') {
            if (sz === 'S') { height = '68cm'; width = '52cm'; }
            else if (sz === 'M') { height = '71cm'; width = '55cm'; }
            else if (sz === 'L') { height = '74cm'; width = '58cm'; }
            else if (sz === 'XL') { height = '77cm'; width = '62cm'; }
            else { height = '71cm'; width = '55cm'; }
        } else {
            if (sz === 'XS') { height = '62cm'; width = '42cm'; }
            else if (sz === 'S') { height = '65cm'; width = '45cm'; }
            else if (sz === 'M') { height = '70cm'; width = '50cm'; }
            else if (sz === 'L') { height = '73cm'; width = '54cm'; }
            else if (sz === 'XL') { height = '76cm'; width = '58cm'; }
            else if (sz === 'XXL' || sz === '2XL') { height = '79cm'; width = '62cm'; }
            else { height = '70cm'; width = '50cm'; }
        }
    }

    if (!height.includes('cm')) height += 'cm';
    if (!width.includes('cm')) width += 'cm';

    window.baseHeightVal = height;
    window.baseWidthVal = width;
    updateSchematicDimensions();
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
