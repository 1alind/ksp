<?php
$activePage = 'contact';
$pageTitle = 'Concierge & Client Services';
require_once __DIR__ . '/header.php';

$actionMsg = '';
$actionType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_inquiry'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Customer Inquiry');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($message)) {
        $inquiryData = [
            'id' => 'INQ-' . rand(1000, 9999),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
            'status' => 'New',
            'date' => date('Y-m-d H:i:s')
        ];
        if (function_exists('save_inquiry')) {
            save_inquiry($inquiryData);
        }
        $actionMsg = $lang === 'ku' ? 'پەیاما تە ب سەرکەفتیانە هاتە هنارتن! تیما مە د نێزیکترین دەم دا دێ پەیوەندیێ ب تە کەت.' : ($lang === 'ar' ? 'تم إرسال استفسارك بنجاح! سيتواصل معك فريق خدمة العملاء قريباً.' : 'Your concierge inquiry has been received. Our client advisors will reach out promptly.');
    }
}
?>

<section class="contact-section py-60">
    <div class="container">
        <?php if (!empty($actionMsg)): ?>
            <div class="alert alert-success mb-24">✓ <?php echo $actionMsg; ?></div>
        <?php endif; ?>

        <div class="contact-grid">
            <!-- Left Column: Concierge Info -->
            <div class="contact-info-card" dir="<?php echo $dir; ?>">
                <span class="section-kicker">AURA ATELIER</span>
                <h2><?php echo $lang === 'ku' ? 'د خزمەتا هەوە داینە' : ($lang === 'ar' ? 'في خدمتكم دائماً' : 'At Your Complete Service'); ?></h2>
                <p class="text-muted">
                    <?php echo $lang === 'ku' ? 'بۆ هەر پسیارەکێ دەربارەی قیاسان، گەهاندنێ، یان داخازیێن تایبەت، دگەل مە د پەیوەندیێ دا بە.' : ($lang === 'ar' ? 'لأي استفسار بخصوص المقاسات، الشحن السريع، أو الطلبات الخاصة، تواصل مع فريقنا مباشرة.' : 'For personalized sizing consultations, bespoke timepiece sourcing, or delivery tracking assistance, reach our advisors.'); ?>
                </p>

                <div class="contact-methods-list mt-32">
                    <div class="contact-item">
                        <span class="contact-icon">📍</span>
                        <div>
                            <strong><?php echo $lang === 'ku' ? 'ناڤونیشان' : ($lang === 'ar' ? 'الموقع' : 'Boutique Location'); ?></strong>
                            <p>Maltas Avenue, Luxury District, Duhok, Kurdistan Region, Iraq</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">📞</span>
                        <div>
                            <strong><?php echo $lang === 'ku' ? 'تەلەفۆن' : ($lang === 'ar' ? 'الهاتف' : 'Direct Telephone'); ?></strong>
                            <p><a href="tel:+9647500000000">+964 (0) 750 000 0000</a></p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">💬</span>
                        <div>
                            <strong>WhatsApp Support</strong>
                            <p><a href="https://wa.me/9647500000000" target="_blank" rel="noopener">+964 750 000 0000 (Instant Chat)</a></p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">✉️</span>
                        <div>
                            <strong><?php echo $lang === 'ku' ? 'ئیمەیل' : ($lang === 'ar' ? 'البريد الإلكتروني' : 'Direct Email'); ?></strong>
                            <p><a href="mailto:concierge@aurastore.iq">concierge@aurastore.iq</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Contact Form -->
            <div class="contact-form-card" dir="<?php echo $dir; ?>">
                <h3><?php echo $lang === 'ku' ? 'پەیامەکێ بهنێرە' : ($lang === 'ar' ? 'أرسل رسالة خاصة' : 'Send an Inquiry'); ?></h3>
                <form action="contact.php" method="POST" class="contact-form mt-20">
                    <div class="form-group mb-16">
                        <label class="form-label"><?php echo $lang === 'ku' ? 'ناڤێ تەمام' : ($lang === 'ar' ? 'الاسم الكامل' : 'Full Name'); ?> *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Ahmed Ali">
                    </div>
                    <div class="form-row mb-16" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label"><?php echo $lang === 'ku' ? 'ژمارا مۆبایلێ' : ($lang === 'ar' ? 'رقم الهاتف' : 'Phone Number'); ?></label>
                            <input type="tel" name="phone" class="form-control" placeholder="0750 000 0000">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php echo $lang === 'ku' ? 'ئیمەیل' : ($lang === 'ar' ? 'البريد الإلكتروني' : 'Email Address'); ?></label>
                            <input type="email" name="email" class="form-control" placeholder="name@domain.com">
                        </div>
                    </div>
                    <div class="form-group mb-16">
                        <label class="form-label"><?php echo $lang === 'ku' ? 'بابەت' : ($lang === 'ar' ? 'الموضوع' : 'Subject'); ?></label>
                        <input type="text" name="subject" class="form-control" placeholder="Product Inquiry / Sizing / Custom Order">
                    </div>
                    <div class="form-group mb-20">
                        <label class="form-label"><?php echo $lang === 'ku' ? 'دەقێ پەیامێ' : ($lang === 'ar' ? 'نص الرسالة' : 'Your Message'); ?> *</label>
                        <textarea name="message" rows="5" class="form-control" required placeholder="How can our atelier assist you today?"></textarea>
                    </div>
                    <button type="submit" name="send_inquiry" class="btn btn-primary btn-luxury w-full" style="padding:14px;">
                        <?php echo $lang === 'ku' ? 'هنارتنا پەیامێ' : ($lang === 'ar' ? 'إرسال الرسالة' : 'Submit Inquiry'); ?> ✉️
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
