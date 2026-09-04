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
} elseif ($variant === 'shoe' || $variant === 'footwear' || $variant === 'sneakers') {
    $variant = 'shoes';
} elseif ($variant === 'watch' || $variant === 'accessories' || $variant === 'timepiece') {
    $variant = 'watches';
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
            if (str_contains($pCat, 'watch') || str_contains($pTitle, 'watch')) {
                $variant = 'watches';
            } elseif (str_contains($pCat, 'shoe') || str_contains($pTitle, 'shoe') || str_contains($pTitle, 'sneaker') || str_contains($pTitle, 'boot')) {
                $variant = 'shoes';
            } elseif (str_contains($pTitle, 'jean') || str_contains($pTitle, 'pant') || str_contains($pTitle, 'trouser') || str_contains($pCat, 'pant')) {
                $variant = 'jeans';
            } elseif (str_contains($pTitle, 'jacket') || str_contains($pTitle, 'coat') || str_contains($pTitle, 'blazer') || str_contains($pTitle, 'hoodie')) {
                $variant = 'jacket';
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
        if (preg_match('/(?:Length|Height|Jacket|بلندی|درێژی|الطول)[:\s]*([0-9.]+\s*(?:cm|mm)?)/i', $mRaw, $mH)) {
            $activeDimHeight = trim($mH[1]);
        }
        if (preg_match('/(?:Width|Chest|Trousers|پانی|الصدر|العرض)[:\s]*([0-9.]+\s*(?:cm|mm)?)/i', $mRaw, $mW)) {
            $activeDimWidth = trim($mW[1]);
        }
    }
}

// Category Default Dimensions if not set
if (empty($activeDimHeight) || empty($activeDimWidth)) {
    $sz = strtoupper($selectedSize ?: 'M');
    if ($variant === 'watches') {
        $activeDimHeight = !empty($activeDimHeight) ? $activeDimHeight : '42mm';
        $activeDimWidth = !empty($activeDimWidth) ? $activeDimWidth : '22mm';
    } elseif ($variant === 'shoes') {
        $activeDimHeight = !empty($activeDimHeight) ? $activeDimHeight : '27.0cm'; // Foot length
        $activeDimWidth = !empty($activeDimWidth) ? $activeDimWidth : '9.8cm';  // Foot width
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
        elseif ($sz === '3XL') { $activeDimHeight = '82cm'; $activeDimWidth = '66cm'; }
        elseif ($sz === '4XL') { $activeDimHeight = '85cm'; $activeDimWidth = '70cm'; }
        elseif ($sz === '5XL') { $activeDimHeight = '88cm'; $activeDimWidth = '74cm'; }
        else { $activeDimHeight = '70cm'; $activeDimWidth = '50cm'; }
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

// Helper query string for tabs to preserve product context
$tabQuery = '';
if ($productId > 0) $tabQuery .= '&pid=' . $productId;
if (!empty($backUrl)) $tabQuery .= '&from=' . urlencode($backUrl);

// Category localized titles & labels
$catMeta = [
    'tshirt' => [
        'name' => ($lang === 'ku' ? 'تیشێرت و سەرپۆش' : ($lang === 'ar' ? 'تيشيرت وقمصان' : 'T-Shirts & Tops')),
        'icon' => '👕',
        'dim1_label' => ($lang === 'ku' ? 'بلندی / درێژی' : ($lang === 'ar' ? 'الارتفاع / الطول' : 'Total Length')),
        'dim2_label' => ($lang === 'ku' ? 'پانی / سینگ' : ($lang === 'ar' ? 'العرض / الصدر' : 'Chest Width')),
        'step1' => ($lang === 'ku' ? 'پارچەیێ بەردە ل سەر مێزەکا تەخت و بێ چەماندن ڕابکێشە.' : ($lang === 'ar' ? 'افرد القطعة بشكل مستوٍ على سطح صلب بدون طيات.' : 'Lay your favorite garment flat on a firm, smooth surface.')),
        'step2' => ($lang === 'ku' ? 'پانیێ ژ ژێر ملێ چەپێ بۆ یێ ڕاستێ ڕاستەوخۆ بپێڤە.' : ($lang === 'ar' ? 'قس المسافة الأفقية من أسفل الإبط الأيمن للأيسر.' : 'Measure horizontally from pit to pit across the fullest chest area.')),
        'step3' => ($lang === 'ku' ? 'بلندیێ ژ بلندترین خالێ ملان هەتا بنێ جلوبەرگی بپێڤە.' : ($lang === 'ar' ? 'قس المسافة الرأسية من أعلى نقطة في الكتف إلى الحافة السفلية.' : 'Measure vertically from highest shoulder point straight down to bottom hem.'))
    ],
    'jeans' => [
        'name' => ($lang === 'ku' ? 'پانتۆل و جینز' : ($lang === 'ar' ? 'بناطيل وجينز' : 'Jeans & Trousers')),
        'icon' => '👖',
        'dim1_label' => ($lang === 'ku' ? 'درێژی / درێژیا قاچی' : ($lang === 'ar' ? 'طول البنطال الكامل' : 'Total Inseam / Length')),
        'dim2_label' => ($lang === 'ku' ? 'کەمەر / ناڤتەنگ' : ($lang === 'ar' ? 'محيط الخصر' : 'Waist Width')),
        'step1' => ($lang === 'ku' ? 'پانتۆلەکێ گونجای بەردە ل سەر مێزێ و قۆپچەی دابخە.' : ($lang === 'ar' ? 'افرد بنطالك المفضل مع إغلاق الزر والسحاب بالكامل.' : 'Button up and lay your trousers flat on a flat surface.')),
        'step2' => ($lang === 'ku' ? 'پانییا کەمەرێ (Waist) ژ لایەکێ بۆ لایێ دی بپێڤە.' : ($lang === 'ar' ? 'قس عرض الخصر من الحافة إلى الحافة واضرب في 2 للمحيط.' : 'Measure across the waistband from side edge to side edge.')),
        'step3' => ($lang === 'ku' ? 'درێژییا دروست ژ سەرێ کەمەرێ هەتا بنی بپێڤە.' : ($lang === 'ar' ? 'قس الطول من أعلى حزام الخصر إلى نهاية طرف البنطال.' : 'Measure along the outseam from waistband top to hem leg opening.'))
    ],
    'jacket' => [
        'name' => ($lang === 'ku' ? 'چاکەت و قەمسەلە' : ($lang === 'ar' ? 'جاكيتات ومعاطف' : 'Jackets & Blazers')),
        'icon' => '🧥',
        'dim1_label' => ($lang === 'ku' ? 'درێژیا چاکەتی' : ($lang === 'ar' ? 'طول الجاكيت' : 'Jacket Length')),
        'dim2_label' => ($lang === 'ku' ? 'پانییا سینگی' : ($lang === 'ar' ? 'عرض الصدر' : 'Chest Width')),
        'step1' => ($lang === 'ku' ? 'قۆپچەیێن چاکەتی دابخە و ل سەر ڕوویەک تەخت دابنێ.' : ($lang === 'ar' ? 'أغلق أزرار الجاكيت وافرده بسلاسة على سطح مستوٍ.' : 'Fasten buttons and lay the blazer flat on a level table.')),
        'step2' => ($lang === 'ku' ? 'پانییا ژێر هەردوو ملان بەرفرەهی بپێڤە.' : ($lang === 'ar' ? 'قس عرض الصدر أفقياً بين منطقتي الإبطين.' : 'Measure pit-to-pit across chest with fabric smoothly spread.')),
        'step3' => ($lang === 'ku' ? 'درێژیا پشتا چاکەتی ژ بنێ یەخەی هەتا خوارێ بپێڤە.' : ($lang === 'ar' ? 'قس الطول من أسفل ياقة الرقبة حتى نهاية الجاكيت من الخلف.' : 'Measure center back length from below collar down to jacket hem.'))
    ],
    'shoes' => [
        'name' => ($lang === 'ku' ? 'پێلاڤ و قۆندەرە' : ($lang === 'ar' ? 'أحذية وسنيكرز' : 'Shoes & Footwear')),
        'icon' => '👟',
        'dim1_label' => ($lang === 'ku' ? 'درێژیا پێی (سم)' : ($lang === 'ar' ? 'طول القدم (سم)' : 'Foot Length (cm)')),
        'dim2_label' => ($lang === 'ku' ? 'پانییا بنکێ پێی' : ($lang === 'ar' ? 'عرض القدم' : 'Insole Width')),
        'step1' => ($lang === 'ku' ? 'کاغەزەکێ ل سەر ئەردی دابنێ و پێیێ خۆ ب دورستی ل سەر ڕابگرە.' : ($lang === 'ar' ? 'ضع ورقة بيضاء على الأرض وقف عليها بوزنك الكامل وجورب مناسب.' : 'Place a white paper sheet on the floor and stand firmly with normal socks.')),
        'step2' => ($lang === 'ku' ? 'ب قەلەمەکێ دۆروبەرێ پنیا پێی و سەرێ تلیا مەزن هێڵەک بکێشە.' : ($lang === 'ar' ? 'حدد بقلم أبعد نقطة في الكعب وأطول إصبع في قدمك.' : 'Mark the backmost edge of your heel and tip of your longest toe.')),
        'step3' => ($lang === 'ku' ? 'مەودایا ناڤبەرا هەردوو خالان ب مەترێ ب سانتیمەتر بپێڤە.' : ($lang === 'ar' ? 'قس المسافة بالسنتيمتر بين النقطتين وطابقها مع الجدول.' : 'Measure distance in cm and compare with our EU/US chart below.'))
    ],
    'watches' => [
        'name' => ($lang === 'ku' ? 'دەمژمێر و قایش' : ($lang === 'ar' ? 'ساعات وأساور' : 'Watches & Straps')),
        'icon' => '⌚',
        'dim1_label' => ($lang === 'ku' ? 'قەبارێ دەمژمێرێ (ملم)' : ($lang === 'ar' ? 'قطر الساعة (ملم)' : 'Case Diameter (mm)')),
        'dim2_label' => ($lang === 'ku' ? 'پانییا قایشی (ملم)' : ($lang === 'ar' ? 'عرض السوار (ملم)' : 'Strap Width (mm)')),
        'step1' => ($lang === 'ku' ? 'قەبارێ بازنەیا دەمژمێرێ ب ملم (بێ دوگمەیا دەستی) بپێڤە.' : ($lang === 'ar' ? 'قس قطر إطار الساعة الدائري بالملم باستثناء زر الضبط.' : 'Measure case diameter across without crown/pushers.')),
        'step2' => ($lang === 'ku' ? 'پانییا قایشێ ل جهێ گرێدانا دگەل دەمژمێرێ بپێڤە.' : ($lang === 'ar' ? 'قس عرض السوار بالملم عند نقطة اتصاله بهيكل الساعة.' : 'Measure lug width where strap connects to the timepiece case.')),
        'step3' => ($lang === 'ku' ? 'دەورەبەرا مەچەکێ خۆ ب دەزگەکێ بپێڤە دا دەمژمێرا گونجای بزانی.' : ($lang === 'ar' ? 'قس محيط معصم يدك للتأكد من تناسق حجم الإطار مع يدك.' : 'Measure your wrist circumference to choose 38mm, 40mm, 42mm, or 44mm.'))
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
                if ($lang === 'ku') echo 'رێبەرێ پێشکەفتی یێ قیاس و بەرگدروویێ بۆ دەستکەفتنا قیاسێ ١٠٠٪ ڕاستەقینە و بێ کێماسی';
                elseif ($lang === 'ar') echo 'دليل المقاسات الذكي والمخططات الهندسية لاختيار مقاسك المثالي بدقة متناهية';
                else echo 'Interactive sizing blueprints, precision dimension guide, and fit calculator';
                ?>
            </p>
        </div>
    </div>
</div>

<section class="size-guide-page-section py-60">
    <div class="container">
        
        <!-- Category Navigation Tabs -->
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
            <a href="size_guide.php?v=shoes<?php echo $tabQuery; ?>" class="size-tab-btn <?php echo $variant === 'shoes' ? 'active' : ''; ?>">
                <span class="tab-icon">👟</span>
                <span><?php echo $lang === 'ku' ? 'پێلاڤ و قۆندەرە' : ($lang === 'ar' ? 'أحذية وسنيكرز' : 'Shoes & Footwear'); ?></span>
            </a>
            <a href="size_guide.php?v=watches<?php echo $tabQuery; ?>" class="size-tab-btn <?php echo $variant === 'watches' ? 'active' : ''; ?>">
                <span class="tab-icon">⌚</span>
                <span><?php echo $lang === 'ku' ? 'دەمژمێر و قایش' : ($lang === 'ar' ? 'ساعات وإكسسوارات' : 'Watches & Straps'); ?></span>
            </a>
        </div>

        <div class="size-guide-content-card" dir="<?php echo $dir; ?>">
            
            <!-- Top Controls Bar: Active Product & Unit Switcher -->
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
                        <span><?php echo $curCat['icon']; ?></span>
                        <span><?php echo $curCat['name']; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Unit Switcher (CM vs INCH / MM) -->
                <div class="unit-switcher-toggle" style="display: inline-flex; align-items: center; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.12); border-radius: 20px; padding: 3px;">
                    <span style="font-size: 12px; color: #9ca3af; padding: 0 10px; font-weight: 600;"><?php echo $lang === 'ku' ? 'یەکە:' : ($lang === 'ar' ? 'الوحدة:' : 'Unit:'); ?></span>
                    <button type="button" class="unit-btn active" id="unitBtnMetric" onclick="switchMeasurementUnit('metric')" style="padding: 5px 14px; border-radius: 16px; border: none; background: #dcb348; color: #0c0e14; font-weight: 800; font-size: 12px; cursor: pointer; transition: all 0.2s;">
                        <?php echo $variant === 'watches' ? 'MM' : 'CM'; ?>
                    </button>
                    <button type="button" class="unit-btn" id="unitBtnImperial" onclick="switchMeasurementUnit('imperial')" style="padding: 5px 14px; border-radius: 16px; border: none; background: transparent; color: #9ca3af; font-weight: 700; font-size: 12px; cursor: pointer; transition: all 0.2s;">
                        INCH (in)
                    </button>
                </div>

            </div>

            <!-- Product Interactive Size Selector (If viewing a product) -->
            <?php if (!empty($productSizes) && count($productSizes) > 0): ?>
                <div class="guide-product-size-selector-row" style="background: rgba(13, 15, 24, 0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 16px 20px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                    <span style="font-size: 13.5px; font-weight: 700; color: #e5e7eb;">
                        <?php echo $lang === 'ku' ? 'قیاسەکێ بەرهەمی تاقی بکە:' : ($lang === 'ar' ? 'اختر مقاساً لمعاينة أبعاده الهندسية:' : 'Test Product Size Dimensions:'); ?>
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
                <h2><?php echo $curCat['icon']; ?> <?php echo $curCat['name']; ?> — <?php echo $lang === 'ku' ? 'رێبەرێ پیڤانێن تەواو' : ($lang === 'ar' ? 'دليل القياسات والمخطط الهندسي' : 'Precision Fit Blueprint'); ?></h2>
                <p><?php echo $lang === 'ku' ? 'بۆ دەستکەفتنا قیاسێ د دروستاهیێ دا، پیڤانێن خۆ ب دووڤ ڤی شێوازێ دیارکری بگرە:' : ($lang === 'ar' ? 'لضمان القياس المثالي الذي يلائمك، اتبع المخطط التوضيحي والخطوات التالية:' : 'Follow our tailored anatomical blueprint below to ensure absolute precision and flawless drape:'); ?></p>
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
                                    <text x="70" y="19" fill="#dcb348" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif" id="svgDimWidthText">
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
                                    <text x="72" y="20" fill="#f43f5e" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif" id="svgDimHeightText">
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

                        <?php elseif ($variant === 'shoes'): ?>
                            <!-- ================= 👟 SHOES & FOOTWEAR SCHEMATIC ================= -->
                            <svg class="measure-svg-graphic" viewBox="0 0 500 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="shoeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#242a3e" />
                                        <stop offset="100%" stop-color="#121624" />
                                    </linearGradient>
                                    <filter id="shoeGlow" x="-20%" y="-20%" width="140%" height="140%">
                                        <feDropShadow dx="0" dy="6" stdDeviation="10" flood-color="#000000" flood-opacity="0.5" />
                                    </filter>
                                </defs>
                                
                                <!-- Sneaker / Luxury Shoe Silhouette -->
                                <path d="M 120 180 C 120 140, 150 110, 190 110 C 210 110, 230 130, 250 150 L 330 160 C 370 165, 410 200, 420 220 L 415 240 C 400 250, 360 250, 330 250 L 130 250 C 110 250, 105 230, 110 210 Z" 
                                      fill="url(#shoeGrad)" stroke="#3a405a" stroke-width="2.2" filter="url(#shoeGlow)" stroke-linejoin="round" />
                                
                                <!-- Sole Cushion -->
                                <path d="M 105 240 L 420 240 L 415 260 L 115 260 Z" fill="#dcb348" opacity="0.85" />
                                
                                <!-- Collar & Laces -->
                                <path d="M 170 125 Q 230 155 270 170" stroke="#dcb348" stroke-width="2" fill="none" />
                                <line x1="210" y1="135" x2="230" y2="155" stroke="#ffffff" stroke-width="1.8" />
                                <line x1="230" y1="145" x2="250" y2="165" stroke="#ffffff" stroke-width="1.8" />
                                <line x1="250" y1="155" x2="270" y2="175" stroke="#ffffff" stroke-width="1.8" />

                                <!-- Foot Length Dimension Line & Badge (Heel to Toe) -->
                                <line x1="105" y1="285" x2="420" y2="285" stroke="#f43f5e" stroke-width="3" />
                                <circle cx="105" cy="285" r="4.5" fill="#f43f5e" />
                                <circle cx="420" cy="285" r="4.5" fill="#f43f5e" />
                                <line x1="105" y1="240" x2="105" y2="285" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                <line x1="420" y1="240" x2="420" y2="285" stroke="rgba(244,63,94,0.4)" stroke-width="1.2" stroke-dasharray="3 3" />
                                
                                <g transform="translate(180, 268)">
                                    <rect width="150" height="30" rx="15" fill="#0b0e17" stroke="#f43f5e" stroke-width="1.8" />
                                    <text x="75" y="20" fill="#f43f5e" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $curCat['dim1_label']; ?>: <tspan id="svgValHeight"><?php echo htmlspecialchars($activeDimHeight); ?></tspan>
                                    </text>
                                </g>

                                <!-- Insole Width Dimension Badge -->
                                <g transform="translate(180, 45)">
                                    <rect width="140" height="30" rx="15" fill="#0b0e17" stroke="#dcb348" stroke-width="1.8" />
                                    <text x="70" y="20" fill="#dcb348" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $curCat['dim2_label']; ?>: <tspan id="svgValWidth"><?php echo htmlspecialchars($activeDimWidth); ?></tspan>
                                    </text>
                                </g>
                            </svg>

                        <?php elseif ($variant === 'watches'): ?>
                            <!-- ================= ⌚ WATCHES & TIMEPIECES SCHEMATIC ================= -->
                            <svg class="measure-svg-graphic" viewBox="0 0 500 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="watchDialGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#1e2235" />
                                        <stop offset="100%" stop-color="#0b0d14" />
                                    </linearGradient>
                                    <linearGradient id="strapGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" stop-color="#3d2f1f" />
                                        <stop offset="100%" stop-color="#1a140d" />
                                    </linearGradient>
                                    <filter id="watchGlow" x="-20%" y="-20%" width="140%" height="140%">
                                        <feDropShadow dx="0" dy="6" stdDeviation="10" flood-color="#000000" flood-opacity="0.6" />
                                    </filter>
                                </defs>
                                
                                <!-- Top & Bottom Leather / Steel Straps -->
                                <rect x="220" y="20" width="60" height="70" rx="4" fill="url(#strapGrad)" stroke="#5c452b" stroke-width="1.5" />
                                <rect x="220" y="230" width="60" height="70" rx="4" fill="url(#strapGrad)" stroke="#5c452b" stroke-width="1.5" />
                                <line x1="225" y1="25" x2="225" y2="85" stroke="#8c6a42" stroke-width="1" stroke-dasharray="3 3" />
                                <line x1="275" y1="25" x2="275" y2="85" stroke="#8c6a42" stroke-width="1" stroke-dasharray="3 3" />
                                <line x1="225" y1="235" x2="225" y2="295" stroke="#8c6a42" stroke-width="1" stroke-dasharray="3 3" />
                                <line x1="275" y1="235" x2="275" y2="295" stroke="#8c6a42" stroke-width="1" stroke-dasharray="3 3" />

                                <!-- Watch Case Outer Bezel -->
                                <circle cx="250" cy="160" r="75" fill="url(#watchDialGrad)" stroke="#dcb348" stroke-width="4" filter="url(#watchGlow)" />
                                <circle cx="250" cy="160" r="66" fill="#080a10" stroke="#3a405a" stroke-width="1.5" />
                                
                                <!-- Crown & Pushers -->
                                <rect x="325" y="152" width="10" height="16" rx="2" fill="#dcb348" />
                                <rect x="320" y="130" width="8" height="10" rx="2" fill="#9ca3af" />
                                <rect x="320" y="180" width="8" height="10" rx="2" fill="#9ca3af" />

                                <!-- Dial Hour Markers & Hands -->
                                <line x1="250" y1="100" x2="250" y2="108" stroke="#dcb348" stroke-width="2.5" />
                                <line x1="250" y1="212" x2="250" y2="220" stroke="#dcb348" stroke-width="2.5" />
                                <line x1="190" y1="160" x2="198" y2="160" stroke="#dcb348" stroke-width="2.5" />
                                <line x1="302" y1="160" x2="310" y2="160" stroke="#dcb348" stroke-width="2.5" />
                                
                                <!-- Hands -->
                                <line x1="250" y1="160" x2="250" y2="120" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" />
                                <line x1="250" y1="160" x2="280" y2="175" stroke="#dcb348" stroke-width="2" stroke-linecap="round" />
                                <circle cx="250" cy="160" r="4" fill="#dcb348" />

                                <!-- Case Diameter Dimension (Height / Dia) Line & Badge -->
                                <line x1="175" y1="160" x2="325" y2="160" stroke="#f43f5e" stroke-width="3" />
                                <circle cx="175" cy="160" r="4.5" fill="#f43f5e" />
                                <circle cx="325" cy="160" r="4.5" fill="#f43f5e" />
                                
                                <g transform="translate(10, 145)">
                                    <rect width="145" height="30" rx="15" fill="#0b0e17" stroke="#f43f5e" stroke-width="1.8" />
                                    <text x="72" y="20" fill="#f43f5e" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $curCat['dim1_label']; ?>: <tspan id="svgValHeight"><?php echo htmlspecialchars($activeDimHeight); ?></tspan>
                                    </text>
                                </g>

                                <!-- Strap Width Dimension Line & Badge -->
                                <line x1="220" y1="25" x2="280" y2="25" stroke="#dcb348" stroke-width="3" />
                                <circle cx="220" cy="25" r="4" fill="#dcb348" />
                                <circle cx="280" cy="25" r="4" fill="#dcb348" />
                                <g transform="translate(340, 15)">
                                    <rect width="140" height="30" rx="15" fill="#0b0e17" stroke="#dcb348" stroke-width="1.8" />
                                    <text x="70" y="20" fill="#dcb348" font-size="12" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">
                                        <?php echo $curCat['dim2_label']; ?>: <tspan id="svgValWidth"><?php echo htmlspecialchars($activeDimWidth); ?></tspan>
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

            <!-- Comprehensive Sizing Matrix Chart for this Category -->
            <div class="category-sizing-chart-section" style="margin-top: 40px; background: rgba(13, 15, 24, 0.7); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 28px 32px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #f3f4f6; display: flex; align-items: center; gap: 8px;">
                        <span style="color: #dcb348;">📐</span>
                        <span><?php echo $lang === 'ku' ? 'خشتەی گشتیێ قیاسان بۆ ڤی جۆری' : ($lang === 'ar' ? 'جدول المقاسات الشامل لهذه الفئة' : 'Standard Sizing & Conversion Matrix'); ?></span>
                    </h3>
                    <span style="font-size: 12.5px; color: #9ca3af;">
                        <?php echo $lang === 'ku' ? '* پیڤان ب سانتیمەتر (cm) هاتینە دەستنیشانکرن' : ($lang === 'ar' ? '* جميع القياسات بالسنتيمتر ما لم يذكر خلاف ذلك' : '* Standard measurements in cm'); ?>
                    </span>
                </div>

                <div style="overflow-x: auto;">
                    <table class="size-matrix-table" style="width: 100%; border-collapse: collapse; text-align: center; font-size: 13.5px;">
                        <thead>
                            <tr style="background: rgba(255,255,255,0.04); border-bottom: 1px solid rgba(255,255,255,0.12); color: #dcb348;">
                                <th style="padding: 12px 16px; font-weight: 700;"><?php echo $lang === 'ku' ? 'قیاس' : ($lang === 'ar' ? 'المقاس' : 'Size'); ?></th>
                                <th style="padding: 12px 16px; font-weight: 700;"><?php echo $curCat['dim1_label']; ?></th>
                                <th style="padding: 12px 16px; font-weight: 700;"><?php echo $curCat['dim2_label']; ?></th>
                                <th style="padding: 12px 16px; font-weight: 700;"><?php echo $lang === 'ku' ? 'کێشێ گونجای' : ($lang === 'ar' ? 'الوزن التقريبي' : 'Approx. Weight'); ?></th>
                                <th style="padding: 12px 16px; font-weight: 700;"><?php echo $lang === 'ku' ? 'بەژنێ گونجای' : ($lang === 'ar' ? 'الطول التقريبي' : 'Recommended Height'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="matrixTableBody">
                            <?php if ($variant === 'jeans'): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">28 US / XS</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">98 cm (38.5 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">72 cm (28.3 in)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">50 – 58 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">160 – 168 cm</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">30 US / S</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">102 cm (40.1 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">78 cm (30.7 in)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">58 – 67 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">168 – 174 cm</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(212,175,55,0.05);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">32 US / M</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">104 cm (40.9 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">82 cm (32.3 in)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">68 – 76 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">172 – 178 cm</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">34 US / L</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">106 cm (41.7 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">86 cm (33.8 in)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">77 – 85 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">176 – 184 cm</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">36 US / XL</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">108 cm (42.5 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">92 cm (36.2 in)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">86 – 96 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">180 – 190 cm</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">38 US / XXL</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">110 cm (43.3 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">98 cm (38.5 in)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">97 – 110 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">182 – 195 cm</td>
                                </tr>

                            <?php elseif ($variant === 'shoes'): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">40 EU / 7.5 US</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">25.5 cm (10.0 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">9.4 cm</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">UK 6.5</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">Standard Width</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">41 EU / 8.0 US</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">26.0 cm (10.2 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">9.6 cm</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">UK 7.0</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">Standard Width</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(212,175,55,0.05);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">42 EU / 8.5 US</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">26.5 cm (10.4 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">9.8 cm</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">UK 7.5</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">Standard Width</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">43 EU / 9.5 US</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">27.5 cm (10.8 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">10.0 cm</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">UK 8.5</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">Standard Width</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">44 EU / 10.0 US</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">28.0 cm (11.0 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">10.2 cm</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">UK 9.0</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">Standard Width</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">45 EU / 11.0 US</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">29.0 cm (11.4 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">10.4 cm</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">UK 10.0</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">Comfort Fit</td>
                                </tr>

                            <?php elseif ($variant === 'watches'): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">38 MM</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">38 mm (Case)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">20 mm (Strap)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">Slim / Classic</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">14.0 – 16.5 cm Wrist</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">40 MM</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">40 mm (Case)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">20 mm (Strap)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">Universal Fit</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">15.5 – 18.0 cm Wrist</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(212,175,55,0.05);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">42 MM</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">42 mm (Case)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">22 mm (Strap)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">Executive / Sport</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">16.5 – 19.5 cm Wrist</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">44 MM+</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">44 mm (Case)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">24 mm (Strap)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">Statement / Diver</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">18.0 – 22.0 cm Wrist</td>
                                </tr>

                            <?php else: // Tops / T-Shirts ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">XS</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">62 cm (24.4 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">42 cm (16.5 in)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">45 – 54 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">155 – 165 cm</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">S</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">65 cm (25.6 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">45 cm (17.7 in)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">55 – 65 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">165 – 172 cm</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(212,175,55,0.05);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">M</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">70 cm (27.5 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">50 cm (19.7 in)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">65 – 75 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">170 – 178 cm</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">L</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">73 cm (28.7 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">54 cm (21.2 in)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">75 – 85 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">175 – 183 cm</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">XL</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">76 cm (29.9 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">58 cm (22.8 in)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">85 – 95 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">180 – 188 cm</td>
                                </tr>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">XXL</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">79 cm (31.1 in)</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">62 cm (24.4 in)</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">95 – 108 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">182 – 195 cm</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-weight: 800; color: #dcb348;"><span class="matrix-sz-pill">3XL – 5XL</span></td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">82 – 88 cm</td>
                                    <td style="padding: 12px 16px; color: #e5e7eb;">66 – 74 cm</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">108 – 130 kg</td>
                                    <td style="padding: 12px 16px; color: #9ca3af;">Plus Sizing</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
// Interactive Size Guide Scripts
let currentUnit = 'metric'; // metric (cm/mm) or imperial (in)
const activeCategoryVariant = '<?php echo $variant; ?>';

function switchMeasurementUnit(unit) {
    currentUnit = unit;
    const btnMetric = document.getElementById('unitBtnMetric');
    const btnImp = document.getElementById('unitBtnImperial');

    if (unit === 'imperial') {
        btnImp.style.background = '#dcb348';
        btnImp.style.color = '#0c0e14';
        btnImp.style.fontWeight = '800';
        btnMetric.style.background = 'transparent';
        btnMetric.style.color = '#9ca3af';
        btnMetric.style.fontWeight = '700';
    } else {
        btnMetric.style.background = '#dcb348';
        btnMetric.style.color = '#0c0e14';
        btnMetric.style.fontWeight = '800';
        btnImp.style.background = 'transparent';
        btnImp.style.color = '#9ca3af';
        btnImp.style.fontWeight = '700';
    }

    updateSchematicDimensions();
}

function parseDimNumber(str) {
    if (!str) return 0;
    const clean = String(str).replace(/[^0-9.]/g, '');
    return parseFloat(clean) || 0;
}

function formatDim(val, isMm = false) {
    if (val <= 0) return '';
    if (currentUnit === 'imperial') {
        if (isMm) {
            // mm to inches
            const inches = (val / 25.4).toFixed(2);
            return `${inches} in`;
        } else {
            // cm to inches
            const inches = (val / 2.54).toFixed(1);
            return `${inches} in`;
        }
    } else {
        if (isMm) return `${val} mm`;
        return `${val} cm`;
    }
}

function updateSchematicDimensions() {
    const hEl = document.getElementById('svgValHeight');
    const wEl = document.getElementById('svgValWidth');

    if (hEl && window.baseHeightNum) {
        hEl.innerText = formatDim(window.baseHeightNum, activeCategoryVariant === 'watches');
    }
    if (wEl && window.baseWidthNum) {
        wEl.innerText = formatDim(window.baseWidthNum, activeCategoryVariant === 'watches');
    }
}

// Extract base dimension numbers initially
document.addEventListener('DOMContentLoaded', function() {
    const initialH = '<?php echo addslashes($activeDimHeight); ?>';
    const initialW = '<?php echo addslashes($activeDimWidth); ?>';
    
    window.baseHeightNum = parseDimNumber(initialH) || (activeCategoryVariant === 'watches' ? 42 : 70);
    window.baseWidthNum = parseDimNumber(initialW) || (activeCategoryVariant === 'watches' ? 22 : 50);
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

    // 3. Extract dimensions
    const mStr = btn.getAttribute('data-measurement') || '';
    let height = '';
    let width = '';

    if (mStr) {
        const hMatch = mStr.match(/(?:Length|Height|Jacket|بلندی|درێژی|الطول)[:\s]*([0-9.]+\s*(?:cm|mm)?)/i);
        if (hMatch) height = hMatch[1].trim();
        const wMatch = mStr.match(/(?:Width|Chest|Trousers|پانی|الصدر|العرض)[:\s]*([0-9.]+\s*(?:cm|mm)?)/i);
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
        } else if (activeCategoryVariant === 'shoes') {
            const num = parseFloat(sz);
            if (num) {
                height = (25.0 + (num - 39) * 0.6).toFixed(1) + 'cm';
                width = '9.8cm';
            } else {
                height = '27.0cm';
                width = '9.8cm';
            }
        } else if (activeCategoryVariant === 'watches') {
            if (sz.includes('38')) { height = '38mm'; width = '20mm'; }
            else if (sz.includes('40')) { height = '40mm'; width = '20mm'; }
            else if (sz.includes('42')) { height = '42mm'; width = '22mm'; }
            else if (sz.includes('44')) { height = '44mm'; width = '24mm'; }
            else { height = '42mm'; width = '22mm'; }
        } else {
            if (sz === 'XS') { height = '62cm'; width = '42cm'; }
            else if (sz === 'S') { height = '65cm'; width = '45cm'; }
            else if (sz === 'M') { height = '70cm'; width = '50cm'; }
            else if (sz === 'L') { height = '73cm'; width = '54cm'; }
            else if (sz === 'XL') { height = '76cm'; width = '58cm'; }
            else if (sz === 'XXL' || sz === '2XL') { height = '79cm'; width = '62cm'; }
            else if (sz === '3XL') { height = '82cm'; width = '66cm'; }
            else if (sz === '4XL') { height = '85cm'; width = '70cm'; }
            else if (sz === '5XL') { height = '88cm'; width = '74cm'; }
            else { height = '70cm'; width = '50cm'; }
        }
    }

    window.baseHeightNum = parseDimNumber(height);
    window.baseWidthNum = parseDimNumber(width);
    updateSchematicDimensions();
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
