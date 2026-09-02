<?php
$activePage = 'about';
$pageTitle = 'Our Heritage & Craftsmanship';
require_once __DIR__ . '/header.php';
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">Maison Aura</span>
            <h1 class="page-banner-title"><?php echo t('nav_about', $lang); ?></h1>
            <p class="page-banner-subtitle"><?php echo t('site_tagline', $lang); ?></p>
        </div>
    </div>
</div>

<section class="about-story-section">
    <div class="container">
        <div class="about-grid">
            <div class="about-text-col">
                <span class="section-kicker"><?php echo $lang === 'ku' ? 'ژ سالا ٢٠١٨ وەرە' : ($lang === 'ar' ? 'منذ عام 2018' : 'Established 2018'); ?></span>
                <h2 class="section-main-heading">
                    <?php echo $lang === 'ku' 
                    ? 'هونەرێ فەخامەتێ، دەمژمێرێن نایاب، و بێهنێن شاهانە' 
                    : ($lang === 'ar' 
                    ? 'إتقان التفاصيل، أرقى الساعات، وأندر النفحات العطرية' 
                    : 'The Art of Distinction, Haute Horlogerie & Rare Fragrances'); ?>
                </h2>

                <p class="about-p">
                    <?php echo $lang === 'ku'
                    ? 'ئۆرا (AURA) هاتە دامەزراندن ب ئارمانجا پێشکێشکرنا بەرهەمێن هەرە نایاب و لوکس د جیهانا جلوبەرگێن کلاسیک، دەمژمێرێن هویر یێن سویسری، و عەترێن دەستکرد یێن بێ هاوتا. مە باوەری ب وێ چەندێ یا هەی کو هەر پارچەیەک پێدڤیە هەلگرا فەخامەت و کوالێتیا بێ کێماسی بیت.'
                    : ($lang === 'ar'
                    ? 'تأسست دار أورا (AURA) لتكون الوجهة الاستثنائية لعشاق الذوق الرفيع. ننتقي بعناية بالغة أفضل تصاميم الأزياء الفاخرة، الساعات السويسرية المتقنة، والعطور الزيتية والفرنسية النادرة لتمنحك إطلالة فريدة تعبر عن شخصيتك.'
                    : 'AURA was born out of a relentless passion for peerless craftsmanship. From hand-stitched velvet blazers to intricate automatic skeleton chronometers and sustainably harvested Cambodian oud, every piece in our collection represents the pinnacle of luxury.'); ?>
                </p>

                <div class="about-highlights-grid">
                    <div class="highlight-box">
                        <strong>100%</strong>
                        <span>Original Certified Authenticity</span>
                    </div>
                    <div class="highlight-box">
                        <strong>14+</strong>
                        <span>International Artisan Ateliers</span>
                    </div>
                    <div class="highlight-box">
                        <strong>25,000+</strong>
                        <span>Clients Across Kurdistan & Global</span>
                    </div>
                </div>
            </div>

            <div class="about-visual-col">
                <div class="about-img-frame">
                    <img src="https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=1000&q=80" alt="Aura Haute Craftsmanship" class="about-main-img">
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
