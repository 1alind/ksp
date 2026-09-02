<?php
$pageTitle = 'Customer Concierge & Support Inquiries | AURA Luxury Admin';
$adminActive = 'inquiries';
$ordersDb = json_decode(file_get_contents(__DIR__ . '/../database/orders.json'), true);
$ordersList = $ordersDb['orders'] ?? [];
$productsDb = json_decode(file_get_contents(__DIR__ . '/../database/products.json'), true);
$productsList = $productsDb['products'] ?? [];
$usersDb = json_decode(file_get_contents(__DIR__ . '/../database/users.json'), true);
$usersList = $usersDb['users'] ?? [];
$inquiriesDb = json_decode(file_get_contents(__DIR__ . '/../database/inquiries.json'), true);
$inquiriesList = $inquiriesDb['inquiries'] ?? [];

$activePage = 'admin';
require_once __DIR__ . '/../header.php';
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">✦ Executive Command Suite</span>
            <h1 class="page-banner-title">Concierge & Customer Inquiries</h1>
            <p class="page-banner-subtitle">
                Incoming bespoke tailoring requests, sizing consultations, and customer care questions.
            </p>
        </div>
    </div>
</div>

<section class="admin-section" style="padding: 40px 0 80px;">
    <div class="container">

        <!-- Unified Admin Navigation Bar -->
        <?php require_once __DIR__ . '/nav.php'; ?>

        <!-- Metric Sub-Cards -->
        <div class="admin-metrics-grid" style="margin-bottom:24px;">
            <div class="admin-metric-card">
                <span class="m-icon">💬</span>
                <div class="m-info">
                    <span class="m-label">Active Concierge Inquiries</span>
                    <strong class="m-value"><?php echo count($inquiriesList); ?> Messages</strong>
                    <span class="iqd-price-pill">Client Assistance Needed</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">⚡</span>
                <div class="m-info">
                    <span class="m-label">Direct WhatsApp Reach</span>
                    <strong class="m-value" style="color:#22c55e;">1-Click Reply</strong>
                    <span class="iqd-price-pill">Instant Messenger Channel</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">⏱️</span>
                <div class="m-info">
                    <span class="m-label">Target Response Time</span>
                    <strong class="m-value">&lt; 15 Minutes</strong>
                    <span class="iqd-price-pill">Luxury Standard</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">👥</span>
                <div class="m-info">
                    <span class="m-label">Client Registry</span>
                    <strong class="m-value"><?php echo count($usersList); ?> Customers</strong>
                    <span class="iqd-price-pill"><a href="/admin/users.php" style="color:inherit;">View Directory →</a></span>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:start;">
            
            <!-- Inquiries List -->
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="margin:0; font-size:18px; font-weight:800;">💬 Concierge Messages & Requests</h3>
                    <input type="text" id="inqSearchInput" onkeyup="filterInquiries()" placeholder="Filter inquiries..." class="form-control" style="max-width:220px; padding:8px 12px; font-size:13px;">
                </div>

                <div class="inquiries-container" id="inquiriesContainer" style="display:flex; flex-direction:column; gap:16px;">
                    <?php if (empty($inquiriesList)): ?>
                        <div style="text-align:center; padding:48px 20px; background:var(--bg-card); border-radius:var(--radius-md); border:1px dashed var(--border-color);">
                            <span style="font-size:36px; display:block; margin-bottom:10px;">💬</span>
                            <h4 style="margin:0 0 6px; font-size:16px;">No Pending Inquiries</h4>
                            <p class="text-muted" style="margin:0; font-size:13px;">Customer concierge questions submitted from the contact form will appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($inquiriesList as $inq): 
                            $waNum = preg_replace('/[^0-9]/', '', $inq['phone'] ?? '');
                            if (strpos($waNum, '07') === 0) $waNum = '964' . substr($waNum, 1);
                            $waText = rawurlencode("Hello " . $inq['name'] . ", greetings from AURA Luxury Store concierge. Regarding your inquiry: " . ($inq['subject'] ?? ''));
                        ?>
                            <div class="inquiry-card" data-search="<?php echo htmlspecialchars(strtolower(($inq['name'] ?? '') . ' ' . ($inq['subject'] ?? '') . ' ' . ($inq['email'] ?? '') . ' ' . ($inq['phone'] ?? ''))); ?>" style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; gap:16px;">
                                <div>
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                                        <div>
                                            <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:700; margin-bottom:6px; display:inline-block;">Inquiry #<?php echo htmlspecialchars($inq['id']); ?></span>
                                            <h4 style="margin:4px 0 2px; font-size:16px; font-weight:700; color:var(--text-primary);"><?php echo htmlspecialchars($inq['name']); ?></h4>
                                            <div style="font-size:12.5px; color:var(--text-muted);">
                                                ✉️ <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" style="color:var(--text-secondary);"><?php echo htmlspecialchars($inq['email'] ?? 'N/A'); ?></a> &nbsp;•&nbsp; 
                                                📞 <?php echo htmlspecialchars($inq['phone'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                        <span style="font-size:11.5px; color:var(--text-muted);"><?php echo !empty($inq['created_at']) ? date('M d, Y', strtotime($inq['created_at'])) : 'Recent'; ?></span>
                                    </div>
                                    <div style="background:var(--bg-subtle); border-radius:8px; padding:12px; margin-top:8px; border:1px solid var(--border-subtle);">
                                        <strong style="display:block; font-size:13px; color:var(--text-primary); margin-bottom:4px;">📌 <?php echo htmlspecialchars($inq['subject'] ?? 'General Inquiry'); ?></strong>
                                        <p style="margin:0; font-size:13.5px; color:var(--text-secondary); line-height:1.5; white-space:pre-wrap;"><?php echo htmlspecialchars($inq['message'] ?? ''); ?></p>
                                    </div>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; pt-12; border-top:1px solid var(--border-subtle); flex-wrap:wrap; gap:8px;">
                                    <div style="display:flex; gap:8px;">
                                        <?php if (!empty($waNum)): ?>
                                            <a href="https://wa.me/<?php echo $waNum; ?>?text=<?php echo $waText; ?>" target="_blank" class="btn btn-outline btn-xs" style="color:#22c55e; border-color:#22c55e;">
                                                💬 WhatsApp Reply
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($inq['email'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>?subject=<?php echo rawurlencode('Re: ' . ($inq['subject'] ?? 'AURA Luxury Inquiry')); ?>" class="btn btn-outline btn-xs">
                                                ✉️ Email
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <form action="/admin/inquiries.php" method="POST" onsubmit="return confirm('Mark inquiry as resolved?')" style="margin:0;">
                                        <input type="hidden" name="delete_inquiry_id" value="<?php echo htmlspecialchars($inq['id']); ?>">
                                        <button type="submit" class="btn btn-ghost text-danger btn-xs">✓ Mark Resolved</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Send Test Concierge Inquiry -->
            <div class="admin-form-card">
                <h3 class="admin-card-title" style="font-size:16px; margin-bottom:6px;">+ Log Direct Client Request</h3>
                <p class="text-muted" style="font-size:12.5px; margin-bottom:16px;">Record a phone call, WhatsApp conversation, or in-person consultation request.</p>
                
                <form action="/admin/inquiries.php" method="POST">
                    <input type="hidden" name="send_inquiry" value="1">
                    
                    <div class="form-group mb-14">
                        <label>Client Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" required class="form-control" placeholder="e.g. Zana Sleman">
                    </div>

                    <div class="form-group mb-14">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" placeholder="zana@example.com">
                    </div>

                    <div class="form-group mb-14">
                        <label>Phone Number (Iraq)</label>
                        <input type="text" name="phone" class="form-control" placeholder="0750 111 2233">
                    </div>

                    <div class="form-group mb-14">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. Custom Chronograph Watch Order">
                    </div>

                    <div class="form-group mb-14">
                        <label>Inquiry Details / Message <span class="text-danger">*</span></label>
                        <textarea name="message" required rows="3" class="form-control" placeholder="Client requested custom engraving on watch back..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-luxury w-100" style="padding:10px;">
                        Log Inquiry
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
function filterInquiries() {
    const q = (document.getElementById('inqSearchInput').value || '').toLowerCase();
    const cards = document.querySelectorAll('.inquiry-card');
    cards.forEach(card => {
        const s = card.getAttribute('data-search') || '';
        card.style.display = (!q || s.includes(q)) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>
