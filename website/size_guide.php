<?php
$activePage = 'shop';
$pageTitle = 'Size & Fit Guide';
require_once __DIR__ . '/header.php';

$backUrl = $_GET['from'] ?? ($_SERVER['HTTP_REFERER'] ?? 'shop.php');
if (str_starts_with($backUrl, 'http')) {
    $parsed = parse_url($backUrl);
    $backUrl = ($parsed['path'] ?? 'shop.php') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
}
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
        <div class="size-guide-content-card" dir="<?php echo $dir; ?>">
            
            <div class="guide-variant-header">
                <h2><?php echo $lang === 'ku' ? 'رێبەرێ قیاسا جلوبەرگان' : ($lang === 'ar' ? 'دليل القياس والخطوات' : 'How to Measure'); ?></h2>
                <p><?php echo $lang === 'ku' ? 'بۆ دەستکەفتنا قیاسێ د رستەستی دا، تیشێرتەکا خۆ ل سەر ڕوویەک تەخت ڕابکێشە و ب ڤی شێوەی پشکنینێ بکە:' : ($lang === 'ar' ? 'لضمان المقاس المثالي، افرد إحدى قطعك المفضلة على سطح مستوٍ وقم بالقياس كالتالي:' : 'For the most accurate fit, lay your favorite garment flat on a smooth surface and follow the simple steps below:'); ?></p>
            </div>

            <div class="guide-grid-layout">
                <div class="measure-illustration-box">
                    <div class="measure-svg-wrapper">
                        <svg class="measure-svg-graphic" viewBox="0 0 460 280" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="shirtGradClean" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#1e2235" />
                                    <stop offset="100%" stop-color="#12141f" />
                                </linearGradient>
                                <filter id="shirtGlowClean" x="-20%" y="-20%" width="140%" height="140%">
                                    <feDropShadow dx="0" dy="6" stdDeviation="10" flood-color="#000000" flood-opacity="0.45" />
                                </filter>
                            </defs>
                            <path d="M 160 40 C 175 32, 200 28, 230 28 C 260 28, 285 32, 300 40 L 375 75 L 340 130 L 300 110 L 300 255 C 300 263, 292 270, 282 270 L 178 270 C 168 270, 160 263, 160 255 L 160 110 L 120 130 L 85 75 Z" 
                                  fill="url(#shirtGradClean)" stroke="#3a405a" stroke-width="2" stroke-linejoin="round" filter="url(#shirtGlowClean)" />
                            <path d="M 195 40 Q 230 68 265 40" stroke="#dcb348" stroke-width="2" fill="none" />
                            <path d="M 160 110 L 190 55" stroke="#2a2e42" stroke-width="1.5" stroke-dasharray="4 4" />
                            <path d="M 300 110 L 270 55" stroke="#2a2e42" stroke-width="1.5" stroke-dasharray="4 4" />
                            
                            <!-- Width Line & Badge -->
                            <line x1="160" y1="135" x2="300" y2="135" stroke="#dcb348" stroke-width="3.5" />
                            <circle cx="160" cy="135" r="5" fill="#dcb348" />
                            <circle cx="300" cy="135" r="5" fill="#dcb348" />
                            <polygon points="168,130 160,135 168,140" fill="#dcb348" />
                            <polygon points="292,130 300,135 292,140" fill="#dcb348" />
                            <g transform="translate(175, 100)">
                                <rect width="110" height="26" rx="13" fill="#0d1017" stroke="#dcb348" stroke-width="2" />
                                <text x="55" y="17" fill="#dcb348" font-size="11.5" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif"><?php echo $lang === 'ku' ? 'پانی (Width)' : ($lang === 'ar' ? 'العرض (Width)' : 'Width'); ?></text>
                            </g>

                            <!-- Height Line & Badge -->
                            <line x1="178" y1="46" x2="178" y2="270" stroke="#f43f5e" stroke-width="2.5" stroke-dasharray="6 4" />
                            <circle cx="178" cy="46" r="4" fill="#f43f5e" />
                            <circle cx="178" cy="270" r="4" fill="#f43f5e" />
                            <polygon points="173,54 178,46 183,54" fill="#f43f5e" />
                            <polygon points="173,262 178,270 183,262" fill="#f43f5e" />
                            <g transform="translate(38, 140)">
                                <rect width="115" height="24" rx="12" fill="#0d1017" stroke="#f43f5e" stroke-width="1.5" />
                                <text x="57" y="16" fill="#f43f5e" font-size="11" font-weight="700" text-anchor="middle" font-family="system-ui, sans-serif"><?php echo $lang === 'ku' ? 'بلندی (Height)' : ($lang === 'ar' ? 'الارتفاع (Height)' : 'Height'); ?></text>
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

            <div class="text-center mt-40">
                <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-primary" style="padding: 14px 32px; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: #fff; background: #d4af37; font-weight: 600;">
                    ← <?php echo $lang === 'ku' ? 'ڤەگەر بۆ بەرهەمی' : ($lang === 'ar' ? 'العودة للمنتج' : 'Back to Product'); ?>
                </a>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
