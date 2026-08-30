<?php
$activePage = 'shop';
$pageTitle = 'Size & Fit Guide';
require_once __DIR__ . '/header.php';

$variant = $_GET['v'] ?? 'tshirt';
if (!in_array($variant, ['tshirt', 'jeans', 'jacket', 'shoes'])) {
    $variant = 'tshirt';
}
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">Maison Aura Atelier</span>
            <h1 class="page-banner-title"><?php echo t('how_to_measure_title', $lang); ?></h1>
            <p class="page-banner-subtitle">
                <?php 
                if ($lang === 'ku') echo 'رێبەرێ قیاس و بەرگدروویێ بۆ هەر جۆرەکێ جلوبەرگان';
                elseif ($lang === 'ar') echo 'دليل المقاسات والقياسات الدقيقة لجميع قطع الأزياء';
                else echo 'Precision sizing matrix & measurement blueprints for every category';
                ?>
            </p>
        </div>
    </div>
</div>

<section class="size-guide-page-section py-60">
    <div class="container">
        <!-- Category Selector Tabs -->
        <div class="size-guide-tabs-nav">
            <a href="size_guide.php?v=tshirt" class="size-tab-btn <?php echo $variant === 'tshirt' ? 'active' : ''; ?>">
                <span class="tab-icon">👕</span>
                <span><?php echo $lang === 'ku' ? 'تیشێرت و بلوز' : ($lang === 'ar' ? 'تيشيرت وبلايز' : 'T-Shirts & Tops'); ?></span>
            </a>
            <a href="size_guide.php?v=jeans" class="size-tab-btn <?php echo $variant === 'jeans' ? 'active' : ''; ?>">
                <span class="tab-icon">👖</span>
                <span><?php echo $lang === 'ku' ? 'پانتۆل و جنیز' : ($lang === 'ar' ? 'بنطلونات وجينز' : 'Jeans & Trousers'); ?></span>
            </a>
            <a href="size_guide.php?v=jacket" class="size-tab-btn <?php echo $variant === 'jacket' ? 'active' : ''; ?>">
                <span class="tab-icon">🧥</span>
                <span><?php echo $lang === 'ku' ? 'جاکێت و کاپۆشین' : ($lang === 'ar' ? 'جواكت وهودي' : 'Jackets & Hoodies'); ?></span>
            </a>
            <a href="size_guide.php?v=shoes" class="size-tab-btn <?php echo $variant === 'shoes' ? 'active' : ''; ?>">
                <span class="tab-icon">👟</span>
                <span><?php echo $lang === 'ku' ? 'پاڤۆک و زەرف' : ($lang === 'ar' ? 'أحذية وسنيكرز' : 'Footwear & Sneakers'); ?></span>
            </a>
        </div>

        <div class="size-guide-content-card" dir="<?php echo $dir; ?>">
            
            <?php if ($variant === 'tshirt'): ?>
                <!-- T-Shirt & Tops Guide -->
                <div class="guide-variant-header">
                    <h2><?php echo $lang === 'ku' ? 'رێبەرێ قیاسا تیشێرت و بلوزان' : ($lang === 'ar' ? 'دليل مقاسات التيشيرت والبلوزات' : 'T-Shirts & Tops Measurement Blueprint'); ?></h2>
                    <p><?php echo $lang === 'ku' ? 'بۆ دەستکەفتنا قیاسێ د رستەستی دا، تیشێرتەکا خۆ ل سەر ڕوویەک تەخت ڕابکێشە و ب ڤی شێوەی پشکنینێ بکە:' : ($lang === 'ar' ? 'لضمان المقاس المثالي، افرد إحدى قطعك المفضلة على سطح مستوٍ وقم بالقياس كالتالي:' : 'For the most accurate fit, lay your favorite t-shirt flat on a smooth surface and measure according to the guide below:'); ?></p>
                </div>

                <div class="guide-grid-layout">
                    <div class="measure-illustration-box">
                        <div class="measure-svg-wrapper">
                            <svg class="measure-svg-graphic" viewBox="0 0 460 280" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="shirtGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#1e2235" />
                                        <stop offset="100%" stop-color="#12141f" />
                                    </linearGradient>
                                    <filter id="shirtGlow" x="-20%" y="-20%" width="140%" height="140%">
                                        <feDropShadow dx="0" dy="6" stdDeviation="10" flood-color="#000000" flood-opacity="0.45" />
                                    </filter>
                                </defs>
                                <path d="M 160 40 C 175 32, 200 28, 230 28 C 260 28, 285 32, 300 40 L 375 75 L 340 130 L 300 110 L 300 255 C 300 263, 292 270, 282 270 L 178 270 C 168 270, 160 263, 160 255 L 160 110 L 120 130 L 85 75 Z" 
                                      fill="url(#shirtGrad)" stroke="#3a405a" stroke-width="2" stroke-linejoin="round" filter="url(#shirtGlow)" />
                                <path d="M 195 40 Q 230 68 265 40" stroke="#dcb348" stroke-width="2" fill="none" />
                                <line x1="160" y1="135" x2="300" y2="135" stroke="#dcb348" stroke-width="3.5" />
                                <circle cx="160" cy="135" r="5" fill="#dcb348" />
                                <circle cx="300" cy="135" r="5" fill="#dcb348" />
                                <g transform="translate(175, 100)">
                                    <rect width="110" height="26" rx="13" fill="#0d1017" stroke="#dcb348" stroke-width="2" />
                                    <text x="55" y="17" fill="#dcb348" font-size="11.5" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">Width: 50cm</text>
                                </g>
                                <line x1="178" y1="46" x2="178" y2="270" stroke="#f43f5e" stroke-width="2.5" stroke-dasharray="6 4" />
                                <circle cx="178" cy="46" r="4" fill="#f43f5e" />
                                <circle cx="178" cy="270" r="4" fill="#f43f5e" />
                                <g transform="translate(38, 140)">
                                    <rect width="115" height="24" rx="12" fill="#0d1017" stroke="#f43f5e" stroke-width="1.5" />
                                    <text x="57" y="16" fill="#f43f5e" font-size="11" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">Height: 70cm</text>
                                </g>
                            </svg>
                        </div>
                    </div>

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

                <div class="modal-matrix-container mt-40">
                    <div class="matrix-heading-wrap">
                        <span class="matrix-sparkle">✦</span>
                        <h4><?php echo $lang === 'ku' ? 'خشتێ گشتگیر یێ قیاسێن تیشێرتان' : ($lang === 'ar' ? 'جدول مقاسات التيشيرتات القياسية' : 'Standard T-Shirt Size Matrix'); ?></h4>
                    </div>
                    <table class="modal-dim-table">
                        <thead>
                            <tr>
                                <th><?php echo $lang === 'ku' ? 'قیاس' : ($lang === 'ar' ? 'المقاس' : 'Size'); ?></th>
                                <th><?php echo $lang === 'ku' ? 'بلندی (Length)' : ($lang === 'ar' ? 'الطول (Length)' : 'Height / Length'); ?></th>
                                <th><?php echo $lang === 'ku' ? 'پانی (Chest)' : ($lang === 'ar' ? 'العرض (Chest)' : 'Width / Chest'); ?></th>
                                <th><?php echo $lang === 'ku' ? 'شان (Shoulder)' : ($lang === 'ar' ? 'الكتف (Shoulder)' : 'Shoulder'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><span class="matrix-sz-pill">XS</span></td><td>62 cm</td><td>42 cm</td><td>40 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">S</span></td><td>65 cm</td><td>45 cm</td><td>42 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">M</span></td><td>70 cm</td><td>50 cm</td><td>45 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">L</span></td><td>73 cm</td><td>54 cm</td><td>48 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">XL</span></td><td>76 cm</td><td>58 cm</td><td>51 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">XXL</span></td><td>79 cm</td><td>62 cm</td><td>54 cm</td></tr>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($variant === 'jeans'): ?>
                <!-- Jeans & Trousers Guide -->
                <div class="guide-variant-header">
                    <h2><?php echo $lang === 'ku' ? 'رێبەرێ قیاسا پانتۆل و جنیزان' : ($lang === 'ar' ? 'دليل مقاسات البنطلونات والجينز' : 'Jeans & Trousers Measurement Blueprint'); ?></h2>
                    <p><?php echo $lang === 'ku' ? 'بۆ قیاسکرنا کەمەر و درێژیا پانتۆلی ب ڕووبەرەک تەخت بکاربینە:' : ($lang === 'ar' ? 'لقياس محيط الخصر والطول الداخلي بدقة، اتبع الإرشادات التالية:' : 'For precise waist and inseam measurements, measure across the waistband and down the inner leg seam:'); ?></p>
                </div>

                <div class="guide-grid-layout">
                    <div class="measure-illustration-box">
                        <div class="measure-svg-wrapper">
                            <svg class="measure-svg-graphic" viewBox="0 0 460 280" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M 180 30 L 280 30 L 295 100 L 250 260 L 215 260 L 195 140 L 175 260 L 140 260 L 165 100 Z" fill="#181c2e" stroke="#3a405a" stroke-width="2"/>
                                <!-- Waist Line -->
                                <line x1="180" y1="45" x2="280" y2="45" stroke="#dcb348" stroke-width="3.5" />
                                <g transform="translate(185, 55)">
                                    <rect width="90" height="24" rx="12" fill="#0d1017" stroke="#dcb348" stroke-width="2" />
                                    <text x="45" y="16" fill="#dcb348" font-size="11" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">Waist: 32"</text>
                                </g>
                                <!-- Inseam Line -->
                                <line x1="215" y1="90" x2="215" y2="250" stroke="#f43f5e" stroke-width="2.5" stroke-dasharray="6 4" />
                                <g transform="translate(245, 150)">
                                    <rect width="95" height="24" rx="12" fill="#0d1017" stroke="#f43f5e" stroke-width="1.5" />
                                    <text x="47" y="16" fill="#f43f5e" font-size="11" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">Inseam: 32"</text>
                                </g>
                            </svg>
                        </div>
                    </div>

                    <div class="measure-steps-list">
                        <div class="measure-step-item">
                            <span class="step-num">1</span>
                            <div class="step-text">
                                <strong>Waist Band (کەمەر)</strong>
                                <span>Button pants and lay flat. Measure straight across top waistband edge.</span>
                            </div>
                        </div>
                        <div class="measure-step-item width-accent">
                            <span class="step-num">2</span>
                            <div class="step-text">
                                <strong>Inseam (درێژیا ناڤخوەیی)</strong>
                                <span>Measure from crotch seam down to the bottom hem of the pant leg.</span>
                            </div>
                        </div>
                        <div class="measure-step-item height-accent">
                            <span class="step-num">3</span>
                            <div class="step-text">
                                <strong>Rise (بلندیا سەر کەمەری)</strong>
                                <span>Measure from crotch seam up to the front waistband top.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-matrix-container mt-40">
                    <div class="matrix-heading-wrap">
                        <span class="matrix-sparkle">✦</span>
                        <h4><?php echo $lang === 'ku' ? 'خشتێ قیاسێن جنیز و پانتۆلان' : ($lang === 'ar' ? 'جدول مقاسات البنطلونات والجينز' : 'Jeans & Trousers Sizing Matrix'); ?></h4>
                    </div>
                    <table class="modal-dim-table">
                        <thead>
                            <tr>
                                <th>Size (US/EU)</th>
                                <th>Waist (کەمەر)</th>
                                <th>Inseam (درێژی)</th>
                                <th>Hips (حەوز)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><span class="matrix-sz-pill">30</span></td><td>76 - 79 cm</td><td>81 cm</td><td>96 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">32</span></td><td>81 - 84 cm</td><td>82 cm</td><td>101 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">34</span></td><td>86 - 89 cm</td><td>83 cm</td><td>106 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">36</span></td><td>91 - 94 cm</td><td>84 cm</td><td>111 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">38</span></td><td>96 - 99 cm</td><td>85 cm</td><td>116 cm</td></tr>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($variant === 'jacket'): ?>
                <!-- Jacket & Hoodie Guide -->
                <div class="guide-variant-header">
                    <h2><?php echo $lang === 'ku' ? 'رێبەرێ قیاسا جاکێت، کاپۆشین و پالتاوان' : ($lang === 'ar' ? 'دليل مقاسات الجواكت والملابس الخارجية' : 'Jackets & Outerwear Measurement Blueprint'); ?></h2>
                    <p><?php echo $lang === 'ku' ? 'بۆ جاکێت و کاپۆشینان، شان و درێژیا قۆڵان ڕۆلەکا سەرەکی دبینن:' : ($lang === 'ar' ? 'للملابس الخارجية والجواكت، يُراعى قياس عرض الكتفين وطول الأكمام بدقة:' : 'For outerwear and coats, shoulder width and sleeve length are essential for layering:'); ?></p>
                </div>

                <div class="guide-grid-layout">
                    <div class="measure-illustration-box">
                        <div class="measure-svg-wrapper">
                            <svg class="measure-svg-graphic" viewBox="0 0 460 280" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M 150 40 C 170 30, 210 25, 230 25 C 250 25, 290 30, 310 40 L 390 85 L 350 150 L 310 120 L 305 260 C 305 268, 295 275, 285 275 L 175 275 C 165 275, 155 268, 155 260 L 150 120 L 110 150 L 70 85 Z" fill="#1b2033" stroke="#3a405a" stroke-width="2"/>
                                <!-- Sleeve Line -->
                                <line x1="70" y1="85" x2="135" y2="125" stroke="#dcb348" stroke-width="3" />
                                <g transform="translate(60, 135)">
                                    <rect width="100" height="24" rx="12" fill="#0d1017" stroke="#dcb348" stroke-width="2" />
                                    <text x="50" y="16" fill="#dcb348" font-size="11" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">Sleeve: 64cm</text>
                                </g>
                                <!-- Chest Line -->
                                <line x1="150" y1="140" x2="310" y2="140" stroke="#f43f5e" stroke-width="3" />
                                <g transform="translate(180, 100)">
                                    <rect width="105" height="24" rx="12" fill="#0d1017" stroke="#f43f5e" stroke-width="1.5" />
                                    <text x="52" y="16" fill="#f43f5e" font-size="11" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">Chest: 58cm</text>
                                </g>
                            </svg>
                        </div>
                    </div>

                    <div class="measure-steps-list">
                        <div class="measure-step-item">
                            <span class="step-num">1</span>
                            <div class="step-text">
                                <strong>Shoulders (شان ب شان)</strong>
                                <span>Measure across back from shoulder seam tip to seam tip.</span>
                            </div>
                        </div>
                        <div class="measure-step-item width-accent">
                            <span class="step-num">2</span>
                            <div class="step-text">
                                <strong>Sleeve Length (درێژیا قۆڵی)</strong>
                                <span>Measure from top shoulder seam edge down to cuff wrist.</span>
                            </div>
                        </div>
                        <div class="measure-step-item height-accent">
                            <span class="step-num">3</span>
                            <div class="step-text">
                                <strong>Jacket Length (بلندیا گشتی)</strong>
                                <span>Measure from collar base down to bottom hem line.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-matrix-container mt-40">
                    <div class="matrix-heading-wrap">
                        <span class="matrix-sparkle">✦</span>
                        <h4><?php echo $lang === 'ku' ? 'خشتێ قیاسێن جاکێتان' : ($lang === 'ar' ? 'جدول مقاسات الجواكت والمعاطف' : 'Jackets & Outerwear Sizing Matrix'); ?></h4>
                    </div>
                    <table class="modal-dim-table">
                        <thead>
                            <tr>
                                <th>Size</th>
                                <th>Chest (سنگ)</th>
                                <th>Shoulder (شان)</th>
                                <th>Sleeve (قۆڵ)</th>
                                <th>Length (بلندی)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><span class="matrix-sz-pill">S</span></td><td>104 cm</td><td>46 cm</td><td>63 cm</td><td>70 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">M</span></td><td>108 cm</td><td>48 cm</td><td>64 cm</td><td>72 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">L</span></td><td>114 cm</td><td>50 cm</td><td>65 cm</td><td>74 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">XL</span></td><td>120 cm</td><td>52 cm</td><td>66 cm</td><td>76 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">XXL</span></td><td>126 cm</td><td>54 cm</td><td>67 cm</td><td>78 cm</td></tr>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($variant === 'shoes'): ?>
                <!-- Footwear Guide -->
                <div class="guide-variant-header">
                    <h2><?php echo $lang === 'ku' ? 'رێبەرێ قیاسا پاڤۆک و زەرفان' : ($lang === 'ar' ? 'دليل مقاسات الأحذية والسنيكرز' : 'Footwear & Sneakers Measurement Blueprint'); ?></h2>
                    <p><?php echo $lang === 'ku' ? 'پێیا خۆ ل سەر کاغەزەکێ دانە و دورتا درێژیا پێیا خۆ ب سانتی مەتر قیاس بکە:' : ($lang === 'ar' ? 'قف على ورقة بيضاء وارسم خطاً من الكعب إلى أطول إصبع، ثم قس المسافة بالسنتيمتر:' : 'Stand on a blank sheet of paper and measure the distance from your heel to your longest toe in centimeters:'); ?></p>
                </div>

                <div class="guide-grid-layout">
                    <div class="measure-illustration-box">
                        <div class="measure-svg-wrapper">
                            <svg class="measure-svg-graphic" viewBox="0 0 460 280" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M 120 160 C 120 120, 160 80, 220 80 C 290 80, 360 110, 370 150 C 380 190, 350 220, 270 220 C 180 220, 120 200, 120 160 Z" fill="#181c2e" stroke="#3a405a" stroke-width="2"/>
                                <!-- Foot Length Line -->
                                <line x1="130" y1="150" x2="365" y2="150" stroke="#dcb348" stroke-width="3.5" />
                                <g transform="translate(200, 105)">
                                    <rect width="115" height="24" rx="12" fill="#0d1017" stroke="#dcb348" stroke-width="2" />
                                    <text x="57" y="16" fill="#dcb348" font-size="11" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif">Foot: 27.5 cm</text>
                                </g>
                            </svg>
                        </div>
                    </div>

                    <div class="measure-steps-list">
                        <div class="measure-step-item">
                            <span class="step-num">1</span>
                            <div class="step-text">
                                <strong>Heel to Toe (پاشنە تا پەنجە)</strong>
                                <span>Place foot on paper, mark back of heel and tip of longest toe.</span>
                            </div>
                        </div>
                        <div class="measure-step-item width-accent">
                            <span class="step-num">2</span>
                            <div class="step-text">
                                <strong>Measure Distance (مەودا)</strong>
                                <span>Measure distance between the two marks with a ruler in cm.</span>
                            </div>
                        </div>
                        <div class="measure-step-item height-accent">
                            <span class="step-num">3</span>
                            <div class="step-text">
                                <strong>Match Size (هەڵبژاردنا قیاسی)</strong>
                                <span>Compare your cm measurement with our international conversion table below.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-matrix-container mt-40">
                    <div class="matrix-heading-wrap">
                        <span class="matrix-sparkle">✦</span>
                        <h4><?php echo $lang === 'ku' ? 'خشتێ قیاسێن نێڤدەولەتی یێن پاڤۆکان' : ($lang === 'ar' ? 'جدول المقاسات الدولي للأحذية' : 'International Footwear Conversion Matrix'); ?></h4>
                    </div>
                    <table class="modal-dim-table">
                        <thead>
                            <tr>
                                <th>EU</th>
                                <th>US</th>
                                <th>UK</th>
                                <th>Foot Length (CM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><span class="matrix-sz-pill">40</span></td><td>7</td><td>6.5</td><td>25.0 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">41</span></td><td>8</td><td>7.5</td><td>26.0 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">42</span></td><td>8.5</td><td>8</td><td>26.5 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">43</span></td><td>9.5</td><td>9</td><td>27.5 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">44</span></td><td>10</td><td>9.5</td><td>28.0 cm</td></tr>
                            <tr><td><span class="matrix-sz-pill">45</span></td><td>11</td><td>10.5</td><td>29.0 cm</td></tr>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
