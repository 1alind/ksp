<?php
$activePage = 'contact';
$pageTitle = 'VIP Concierge & Contact';
require_once __DIR__ . '/header.php';

$contactSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_inquiry'])) {
    $cName = trim($_POST['name'] ?? '');
    $cEmail = trim($_POST['email'] ?? '');
    $cPhone = trim($_POST['phone'] ?? '');
    $cSubject = trim($_POST['subject'] ?? 'General Inquiry');
    $cOrderId = trim($_POST['order_id'] ?? '');
    $cMessage = trim($_POST['message'] ?? '');

    if (!empty($cName) && !empty($cMessage)) {
        $inqData = read_json_db('inquiries.json');
        $inqList = $inqData['inquiries'] ?? [];
        $isPackageIssue = strpos($cSubject, 'Package') !== false || !empty($cOrderId);
        $newInquiry = [
            'id' => ($isPackageIssue ? 'CLAIM-' : 'INQ-') . rand(1000, 9999),
            'name' => htmlspecialchars($cName),
            'email' => htmlspecialchars($cEmail),
            'phone' => htmlspecialchars($cPhone),
            'order_id' => htmlspecialchars($cOrderId),
            'is_package_claim' => $isPackageIssue,
            'subject' => htmlspecialchars($cSubject) . (!empty($cOrderId) ? ' (' . htmlspecialchars($cOrderId) . ')' : ''),
            'message' => htmlspecialchars($cMessage),
            'status' => $isPackageIssue ? 'Pending Inspection' : 'Open',
            'date' => date('Y-m-d H:i:s')
        ];
        array_unshift($inqList, $newInquiry);
        $inqData['inquiries'] = $inqList;
        write_json_db('inquiries.json', $inqData);
        $contactSuccess = $lang === 'ku' ? 'سوپاس بۆ پەیوەندیا تە! پەیاما تە گەهشتە بەشێ پشتگیری و زەمانەتێ.' : ($lang === 'ar' ? 'شكراً لتواصلك معنا! تم استلام رسالتك وإحالتها للمسؤول المختص فوراً.' : 'Thank you! Your inquiry has been forwarded to our VIP resolution team.');
    }
}
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">✦ <?php echo t('policy_full_online_badge', $lang); ?></span>
            <h1 class="page-banner-title"><?php echo t('nav_contact', $lang); ?></h1>
            <p class="page-banner-subtitle"><?php echo t('policy_issue_only_notice', $lang); ?></p>
        </div>
    </div>
</div>

<section class="contact-section">
    <div class="container">
        
        <!-- Online-Only Policy Notice Banner -->
        <div style="background:var(--bg-subtle); border:1px solid var(--border-color); border-radius:12px; padding:18px 22px; margin-bottom:30px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:26px;">🛡️</span>
                <div>
                    <strong style="display:block; font-size:14.5px; color:var(--text-primary);">
                        <?php echo $lang === 'ku' ? 'خزمەتگوزاریا تەمام دیجیتالی و گەهاندنا پاراستی' : ($lang === 'ar' ? 'خدمة شراء رقمية آلية بالكامل مع ضمان استلام الطرد' : 'Fully Online Order Processing & Dedicated Package Resolution'); ?>
                    </strong>
                    <span style="font-size:12.5px; color:var(--text-secondary);">
                        <?php echo $lang === 'ku' ? 'هەمی داخازی ب شێوازێ ئۆتۆماتیکی دچنە قۆناغا گەهاندنێ. پەیوەندیکرن ب تنێ بۆ چارەسەرکرنا هەر کێشەیەکا پاکێجا گەهشتی یە.' : ($lang === 'ar' ? 'تتم جميع الطلبات والدفعات آلياً دون الحاجة لمكالمات تأكيد. التواصل مخصص حصرياً لمعالجة أية مشكلة في الطرد المستلم.' : 'All orders and payments process automatically without phone confirmation. Direct concierge communication is reserved for delivered package assistance and warranty resolution.'); ?>
                    </span>
                </div>
            </div>
            <a href="track.php" class="btn btn-outline btn-xs" style="color:var(--accent-gold); border-color:var(--accent-gold); font-weight:700;">
                🚚 <?php echo t('nav_track', $lang); ?>
            </a>
        </div>
        
        <?php if (!empty($contactSuccess)): ?>
            <div class="alert alert-success mb-30">✓ <?php echo $contactSuccess; ?></div>
        <?php endif; ?>

        <div class="contact-grid">
            <!-- Contact Info -->
            <div class="contact-info-col">
                <div class="contact-card">
                    <span class="contact-icon">📍</span>
                    <h3><?php echo $lang === 'ku' ? 'لقێن مە یێن سەرەکی' : ($lang === 'ar' ? 'فروعنا الرئيسية' : 'Boutique Locations'); ?></h3>
                    <p><strong>Duhok Flagship:</strong> KRO Commercial Avenue, Duhok</p>
                    <p><strong>Erbil Boutique:</strong> Gulan St, Empire World, Erbil</p>
                    <p><strong>International Hub:</strong> Geneva & Dubai</p>
                </div>

                <div class="contact-card mt-24">
                    <span class="contact-icon">📞</span>
                    <h3><?php echo $lang === 'ku' ? 'پەیوەندی و پشتگیری' : ($lang === 'ar' ? 'الهاتف وخدمة العملاء' : 'Direct Line'); ?></h3>
                    <p>VIP Concierge: <strong>+964 750 123 4567</strong></p>
                    <p>WhatsApp Support: <strong>+964 750 987 6543</strong></p>
                    <p>Email: <strong>concierge@aurastudio.co</strong></p>
                </div>

                <div class="contact-card mt-24">
                    <span class="contact-icon">⏰</span>
                    <h3><?php echo $lang === 'ku' ? 'دەمژمێرێن کارکرنێ' : ($lang === 'ar' ? 'ساعات العمل' : 'Opening Hours'); ?></h3>
                    <p>Saturday – Thursday: 10:00 AM – 11:00 PM</p>
                    <p>Friday: 2:00 PM – 11:00 PM</p>
                    <p>Online Portal: Active 24/7</p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-col">
                <div class="contact-form-card">
                    <h2 class="form-title"><?php echo $lang === 'ku' ? 'نامەیەکێ بۆ مە بهنێرە' : ($lang === 'ar' ? 'أرسل استفسارك الآن' : 'Send a Private Inquiry'); ?></h2>
                    <form action="contact.php" method="POST" class="contact-form">
                        <input type="hidden" name="send_inquiry" value="1">

                        <div class="form-row-2">
                            <div class="form-group">
                                <label><?php echo t('checkout_name', $lang); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="name" required class="form-control" placeholder="Full Name">
                            </div>
                            <div class="form-group">
                                <label><?php echo t('checkout_phone', $lang); ?></label>
                                <input type="tel" name="phone" class="form-control" placeholder="0750 123 4567">
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label><?php echo t('checkout_email', $lang); ?></label>
                                <input type="email" name="email" class="form-control" placeholder="email@example.com">
                            </div>
                            <div class="form-group">
                                <label>Subject / Topic <span class="text-danger">*</span></label>
                                <select name="subject" class="form-control" onchange="const oi=document.getElementById('contactOrderIdGroup'); if(this.value.includes('Package')) oi.style.display='block';">
                                    <option value="🚨 Delivered Package Issue / Discrepancy" style="font-weight:bold; color:#ef4444;">🚨 Delivered Package Issue / Discrepancy (Priority)</option>
                                    <option value="Swiss Watches Warranty & Certification">Swiss Watches Warranty & Certification</option>
                                    <option value="Bespoke Clothes & Sizing">Bespoke Clothes & Sizing</option>
                                    <option value="Artisan Perfumes Consultation">Artisan Perfumes Consultation</option>
                                    <option value="Order & Logistics Inquiry">Order & Logistics Inquiry</option>
                                    <option value="Other VIP Request">Other VIP Request</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" id="contactOrderIdGroup" style="margin-bottom:16px;">
                            <label style="font-size:13px; font-weight:700;">Order ID (if reporting an issue with a delivered package)</label>
                            <input type="text" name="order_id" class="form-control" placeholder="e.g. ORD-84920">
                            <small class="text-muted" style="font-size:11.5px;">Orders are automated end-to-end; support intervenes specifically to replace or remedy any problem with delivered packages.</small>
                        </div>

                        <div class="form-group">
                            <label>Message <span class="text-danger">*</span></label>
                            <textarea name="message" rows="5" required class="form-control" placeholder="How may our stylists assist you today?"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-luxury btn-lg w-full">
                            <span>Send Message to Concierge</span>
                            <span>→</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
