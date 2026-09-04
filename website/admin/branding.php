<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/db.php';

$flashMsg = null;
$settingsDb = get_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_website_branding'])) {
    $settingsDb['store_name'] = trim($_POST['store_name'] ?? 'AURA Luxury Store');
    $settingsDb['store_name_ar'] = trim($_POST['store_name_ar'] ?? 'متجر أورا الفاخر');
    $settingsDb['store_name_ku'] = trim($_POST['store_name_ku'] ?? 'فروشگەها ئۆرا یا شاهانە');

    $settingsDb['store_tagline_en'] = trim($_POST['store_tagline_en'] ?? '');
    $settingsDb['store_tagline_ar'] = trim($_POST['store_tagline_ar'] ?? '');
    $settingsDb['store_tagline_ku'] = trim($_POST['store_tagline_ku'] ?? '');

    $settingsDb['logo_type'] = trim($_POST['logo_type'] ?? 'emblem');
    $settingsDb['logo_emblem'] = trim($_POST['logo_emblem'] ?? 'A');
    $settingsDb['brand_accent_color'] = trim($_POST['brand_accent_color'] ?? '#d4af37');
    $settingsDb['logo_main'] = trim($_POST['logo_main'] ?? 'AURA');
    $settingsDb['logo_sub'] = trim($_POST['logo_sub'] ?? 'STUDIO');
    $settingsDb['logo_image_url'] = trim($_POST['logo_image_url'] ?? '');
    $settingsDb['favicon_url'] = trim($_POST['favicon_url'] ?? '');

    $settingsDb['announcement_enabled'] = !empty($_POST['announcement_enabled']);
    $settingsDb['announcement_text_en'] = trim($_POST['announcement_text_en'] ?? '');
    $settingsDb['announcement_text_ar'] = trim($_POST['announcement_text_ar'] ?? '');
    $settingsDb['announcement_text_ku'] = trim($_POST['announcement_text_ku'] ?? '');

    $settingsDb['delivery_kurdistan_fee'] = intval($_POST['delivery_kurdistan_fee'] ?? 5000);
    $settingsDb['delivery_iraq_fee'] = intval($_POST['delivery_iraq_fee'] ?? 8000);
    $settingsDb['free_delivery_threshold'] = intval($_POST['free_delivery_threshold'] ?? 250000);

    $settingsDb['contact_phone'] = trim($_POST['contact_phone'] ?? '');
    $settingsDb['contact_whatsapp'] = trim($_POST['contact_whatsapp'] ?? '');
    $settingsDb['contact_email'] = trim($_POST['contact_email'] ?? '');

    $settingsDb['boutique_location_en'] = trim($_POST['boutique_location_en'] ?? '');
    $settingsDb['boutique_location_ar'] = trim($_POST['boutique_location_ar'] ?? '');
    $settingsDb['boutique_location_ku'] = trim($_POST['boutique_location_ku'] ?? '');

    save_settings($settingsDb);
    $flashMsg = "✓ Store branding, trilingual identity, and delivery rules updated successfully!";
}

$pageTitle = 'Brand Customizer & Global Settings | AURA Luxury Admin';
$adminActive = 'branding';
$ordersList = get_all_orders();
$productsList = get_all_products();
$usersList = get_all_users();
$inquiriesList = get_all_inquiries();

$s = $settingsDb;

$activePage = 'admin';
require_once __DIR__ . '/../header.php';
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">✦ <?php echo adm_t('admin_nav_branding', 'Brand & Settings'); ?></span>
            <h1 class="page-banner-title"><?php echo adm_t('admin_branding_title', 'Brand Customizer & Settings'); ?></h1>
            <p class="page-banner-subtitle">
                <?php echo adm_t('admin_branding_subtitle', 'Fine-tune trilingual store identity, monogram emblem, announcement ribbons, delivery pricing, and atelier contacts.'); ?>
            </p>
        </div>
    </div>
</div>

<section class="admin-section" style="padding: 40px 0 80px;">
    <div class="container">

        <!-- Unified Admin Navigation Bar -->
        <?php require_once __DIR__ . '/nav.php'; ?>

        <?php if ($flashMsg): ?>
            <div style="background:rgba(34,197,94,0.12); border:1px solid #22c55e; color:#22c55e; border-radius:8px; padding:14px 20px; margin-bottom:24px; font-weight:700; display:flex; align-items:center; justify-content:space-between;">
                <span><?php echo $flashMsg; ?></span>
                <button type="button" onclick="this.parentElement.style.display='none'" style="background:none; border:none; color:#22c55e; cursor:pointer; font-size:16px;">✕</button>
            </div>
        <?php endif; ?>

        <form action="/admin/branding.php" method="POST">
            <input type="hidden" name="save_website_branding" value="1">

            <!-- Section 1: Store Name & Trilingual Branding -->
            <div class="admin-form-card mb-24">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <div>
                        <h3 class="admin-card-title" style="margin:0; font-size:17px;">🏛️ <?php echo adm_t('admin_brand_trilingual_title', 'Trilingual Store Name & Slogans'); ?></h3>
                        <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_brand_trilingual_desc', 'These appear across the header, footer, page titles, and tax invoices.'); ?></p>
                    </div>
                </div>

                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_store_name_en', 'Store Name (English)'); ?></label>
                        <input type="text" name="store_name" value="<?php echo htmlspecialchars($s['store_name'] ?? 'AURA Luxury Store'); ?>" class="form-control" placeholder="AURA Luxury Store">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_store_name_ar', 'Store Name (Arabic - العربية)'); ?></label>
                        <input type="text" name="store_name_ar" value="<?php echo htmlspecialchars($s['store_name_ar'] ?? 'متجر أورا الفاخر'); ?>" class="form-control" placeholder="متجر أورا الفاخر">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_store_name_ku', 'Store Name (Kurdish - کوردی)'); ?></label>
                        <input type="text" name="store_name_ku" value="<?php echo htmlspecialchars($s['store_name_ku'] ?? 'فروشگەها ئۆرا یا شاهانە'); ?>" class="form-control" placeholder="فروشگەها ئۆرا یا شاهانە">
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_tagline_en', 'Tagline (English)'); ?></label>
                        <input type="text" name="store_tagline_en" value="<?php echo htmlspecialchars($s['store_tagline_en'] ?? 'Bespoke Luxury & Haute Elegance in Iraq'); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_tagline_ar', 'Tagline (Arabic - العربية)'); ?></label>
                        <input type="text" name="store_tagline_ar" value="<?php echo htmlspecialchars($s['store_tagline_ar'] ?? 'الأناقة الملكية والقطع الحصرية في العراق'); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_tagline_ku', 'Tagline (Kurdish - کوردی)'); ?></label>
                        <input type="text" name="store_tagline_ku" value="<?php echo htmlspecialchars($s['store_tagline_ku'] ?? 'جوانی و شیکپۆشیا شاهانە ل عیراق و کوردستانێ'); ?>" class="form-control">
                    </div>
                </div>
            </div>

            <!-- Section 2: Logo & Visual Monogram -->
            <div class="admin-form-card mb-24">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <div>
                        <h3 class="admin-card-title" style="margin:0; font-size:17px;">✨ <?php echo adm_t('admin_brand_logo_title', 'Logo Emblem & Visual Identity'); ?></h3>
                        <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_brand_logo_desc', 'Choose between a gold monogram heraldic seal or custom image logo.'); ?></p>
                    </div>
                </div>

                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_logo_type', 'Logo Type'); ?></label>
                        <select name="logo_type" class="form-control">
                            <option value="emblem" <?php echo ($s['logo_type'] ?? 'emblem') === 'emblem' ? 'selected' : ''; ?>><?php echo adm_t('admin_brand_logo_type_emblem', 'Luxury Monogram Emblem + Text'); ?></option>
                            <option value="image" <?php echo ($s['logo_type'] ?? '') === 'image' ? 'selected' : ''; ?>><?php echo adm_t('admin_brand_logo_type_image', 'Custom Image URL Only'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_emblem_letter', 'Emblem Initial Letter'); ?></label>
                        <input type="text" name="logo_emblem" value="<?php echo htmlspecialchars($s['logo_emblem'] ?? 'A'); ?>" maxlength="2" class="form-control" style="font-weight:800; text-align:center;">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_accent_color', 'Accent Brand Color'); ?></label>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="color" name="brand_accent_color" value="<?php echo htmlspecialchars($s['brand_accent_color'] ?? '#d4af37'); ?>" style="height:40px; width:50px; padding:2px; border-radius:6px; cursor:pointer;">
                            <input type="text" value="<?php echo htmlspecialchars($s['brand_accent_color'] ?? '#d4af37'); ?>" class="form-control" readonly style="font-family:monospace;">
                        </div>
                    </div>
                </div>

                <div class="form-row-2 mb-16">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_main_word', 'Main Brand Word (e.g. AURA)'); ?></label>
                        <input type="text" name="logo_main" value="<?php echo htmlspecialchars($s['logo_main'] ?? 'AURA'); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_sub_word', 'Sub Brand Word (e.g. STUDIO or LUXURY)'); ?></label>
                        <input type="text" name="logo_sub" value="<?php echo htmlspecialchars($s['logo_sub'] ?? 'STUDIO'); ?>" class="form-control">
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_logo_url', 'Custom Logo Image URL (Optional)'); ?></label>
                        <input type="url" name="logo_image_url" value="<?php echo htmlspecialchars($s['logo_image_url'] ?? ''); ?>" class="form-control" placeholder="https://domain.com/logo.png">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_favicon_url', 'Favicon URL (Browser Tab Icon)'); ?></label>
                        <input type="url" name="favicon_url" value="<?php echo htmlspecialchars($s['favicon_url'] ?? ''); ?>" class="form-control" placeholder="https://domain.com/favicon.png">
                    </div>
                </div>
            </div>

            <!-- Section 3: Announcement Bar & Delivery Logistics -->
            <div class="admin-form-card mb-24">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <div>
                        <h3 class="admin-card-title" style="margin:0; font-size:17px;">📢 <?php echo adm_t('admin_brand_announcement_title', 'Top Announcement Ribbon & Delivery Fees'); ?></h3>
                        <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_brand_announcement_desc', 'Control the luxury ticker banner and shipping costs across Iraqi provinces.'); ?></p>
                    </div>
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="announcement_enabled" value="1" <?php echo ($s['announcement_enabled'] ?? true) ? 'checked' : ''; ?> style="width:18px; height:18px; accent-color:var(--accent-gold);">
                        <span style="font-weight:700; font-size:13px;"><?php echo adm_t('admin_brand_enable_announcement', 'Enable Announcement Bar'); ?></span>
                    </label>
                </div>

                <div class="form-row-3 mb-20">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_announcement_en', 'Announcement Text (English)'); ?></label>
                        <input type="text" name="announcement_text_en" value="<?php echo htmlspecialchars($s['announcement_text_en'] ?? '✨ Complimentary White-Glove Delivery on orders over 250,000 IQD across Iraq'); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_announcement_ar', 'Announcement Text (Arabic - العربية)'); ?></label>
                        <input type="text" name="announcement_text_ar" value="<?php echo htmlspecialchars($s['announcement_text_ar'] ?? '✨ توصيل فاخر مجاني للطلبات فوق 250,000 دينار عراقي لكافة المحافظات'); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_announcement_ku', 'Announcement Text (Kurdish - کوردی)'); ?></label>
                        <input type="text" name="announcement_text_ku" value="<?php echo htmlspecialchars($s['announcement_text_ku'] ?? '✨ گەهاندنا بێبەرامبەر یا شاهانە بۆ داخازیێن ژ 250,000 دیناران بژووری ل سەرتاسەری عیراقێ'); ?>" class="form-control">
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_delivery_krd', 'Delivery Fee - Kurdistan Region (IQD)'); ?></label>
                        <input type="number" name="delivery_kurdistan_fee" value="<?php echo htmlspecialchars($s['delivery_kurdistan_fee'] ?? 5000); ?>" class="form-control" placeholder="5000">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_delivery_iq', 'Delivery Fee - Federal Iraq (Baghdad/Basra) (IQD)'); ?></label>
                        <input type="number" name="delivery_iraq_fee" value="<?php echo htmlspecialchars($s['delivery_iraq_fee'] ?? 8000); ?>" class="form-control" placeholder="8000">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_free_delivery_spend', 'Free Delivery Minimum Spend (IQD)'); ?></label>
                        <input type="number" name="free_delivery_threshold" value="<?php echo htmlspecialchars($s['free_delivery_threshold'] ?? 250000); ?>" class="form-control" placeholder="250000">
                    </div>
                </div>
            </div>

            <!-- Section 4: Contact & Boutique Atelier Details -->
            <div class="admin-form-card mb-24">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <div>
                        <h3 class="admin-card-title" style="margin:0; font-size:17px;">📍 <?php echo adm_t('admin_brand_contact_title', 'Boutique Atelier & Client Support Contacts'); ?></h3>
                        <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_brand_contact_desc', 'Contact phone, WhatsApp concierge link, and boutique physical showroom address.'); ?></p>
                    </div>
                </div>

                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_phone', 'Customer Service Phone'); ?></label>
                        <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($s['contact_phone'] ?? '+964 750 000 0000'); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_whatsapp', 'Official WhatsApp Number'); ?></label>
                        <input type="text" name="contact_whatsapp" value="<?php echo htmlspecialchars($s['contact_whatsapp'] ?? '9647500000000'); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_email', 'Support / Concierge Email'); ?></label>
                        <input type="email" name="contact_email" value="<?php echo htmlspecialchars($s['contact_email'] ?? 'concierge@aurastore.iq'); ?>" class="form-control">
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_location_en', 'Boutique Location (English)'); ?></label>
                        <input type="text" name="boutique_location_en" value="<?php echo htmlspecialchars($s['boutique_location_en'] ?? 'Dream City Boulevard, Erbil • Gulan Street'); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_location_ar', 'Boutique Location (Arabic - العربية)'); ?></label>
                        <input type="text" name="boutique_location_ar" value="<?php echo htmlspecialchars($s['boutique_location_ar'] ?? 'شارع دريم سيتي، أربيل • شارع كولان'); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_brand_location_ku', 'Boutique Location (Kurdish - کوردی)'); ?></label>
                        <input type="text" name="boutique_location_ku" value="<?php echo htmlspecialchars($s['boutique_location_ku'] ?? 'بۆلیڤاردا دریم سیتی، هەولێر • جادا گولان'); ?>" class="form-control">
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="submit" class="btn btn-primary btn-luxury" style="padding:12px 32px; font-size:15px;">
                    💾 <?php echo adm_t('admin_brand_save_btn', 'Save Brand & Store Settings'); ?>
                </button>
            </div>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../footer.php'; ?>
