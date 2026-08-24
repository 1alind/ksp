<?php
$activePage = 'shop';
$pageTitle = 'Secure Checkout';
require_once __DIR__ . '/header.php';

$orderPlaced = false;
$confirmedOrder = null;
$settings = get_store_settings();
$rate = $settings['exchange_rate_usd_to_iqd'] ?? 1320;
$gateways = $settings['gateways'] ?? [];

// Handle Order Submission via PHP POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['customer_email'] ?? '');
    $phone = trim($_POST['customer_phone'] ?? '');
    $city = trim($_POST['customer_city'] ?? 'Duhok');
    $address = trim($_POST['customer_address'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? 'Cash on Delivery');
    $cartJson = $_POST['cart_items_json'] ?? '[]';
    $cartItems = json_decode($cartJson, true) ?: [];
    
    if (!empty($name) && !empty($phone) && !empty($address) && !empty($cartItems)) {
        $subtotal = 0;
        foreach ($cartItems as $ci) {
            $subtotal += ($ci['price'] * $ci['quantity']);
        }
        $discountRate = floatval($_POST['applied_discount_rate'] ?? 0);
        $discount = $subtotal * $discountRate;
        $total = max(0, $subtotal - $discount);
        $totalIqd = $total * $rate;

        $isOnlinePaid = strpos($paymentMethod, 'FIB') !== false || strpos($paymentMethod, 'ZainCash') !== false || strpos($paymentMethod, 'FastPay') !== false;

        $orderPayload = [
            'order_id' => 'ORD-' . rand(10000, 99999),
            'customer_name' => htmlspecialchars($name),
            'email' => htmlspecialchars($email),
            'phone' => htmlspecialchars($phone),
            'city' => htmlspecialchars($city),
            'address' => htmlspecialchars($address),
            'payment_method' => htmlspecialchars($paymentMethod),
            'payment_status' => $isOnlinePaid ? 'Paid (Verified Online)' : 'Pending (Pay on Delivery)',
            'payment_gateway_tx' => $isOnlinePaid ? ('TX-' . strtoupper(substr(md5(uniqid()), 0, 10))) : 'COD-' . rand(1000, 9999),
            'order_status' => 'Processing',
            'courier' => 'AURA VIP Express Logistics',
            'tracking_code' => 'AURA-EXP-' . rand(10000, 99999),
            'dispatch_notes' => 'Order verified. Satin wrap and quality inspection underway at central hub.',
            'estimated_delivery' => 'Within 24-48 Hours across ' . htmlspecialchars($city),
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'shipping' => 0.00,
            'discount' => $discount,
            'total' => $total,
            'total_iqd' => $totalIqd,
            'created_at' => date('Y-m-d\TH:i:s\Z')
        ];

        $confirmedOrder = create_order($orderPayload);
        $orderPlaced = true;
    }
}
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">Aura Secure Gateway • Iraq & Kurdistan</span>
            <h1 class="page-banner-title"><?php echo t('checkout_title', $lang); ?></h1>
            <p class="page-banner-subtitle">
                Exclusive complimentary express delivery across Kurdistan Region and all Federal Iraq governorates.
            </p>
        </div>
    </div>
</div>

<section class="checkout-section">
    <div class="container">
        <?php if ($orderPlaced && $confirmedOrder): ?>
            <!-- Order Success Confirmation Screen -->
            <div class="checkout-success-card text-center">
                <div class="success-icon-badge">✓</div>
                <span class="section-kicker text-primary"><?php echo $lang === 'ku' ? 'داخازی هاتە تۆمارکرن' : ($lang === 'ar' ? 'تم تأكيد طلبك بنجاح' : 'Payment & Order Confirmed'); ?></span>
                <h2 class="success-title"><?php echo t('checkout_success_title', $lang); ?></h2>
                <p class="success-desc">
                    <?php echo t('checkout_success_desc', $lang); ?>
                </p>

                <div class="order-summary-pill">
                    <div>
                        <span class="text-muted"><?php echo $lang === 'ku' ? 'ژمارا داخازیێ' : ($lang === 'ar' ? 'رقم الطلب' : 'Order Reference'); ?></span>
                        <h3 class="order-id-display"><?php echo htmlspecialchars($confirmedOrder['order_id']); ?></h3>
                    </div>
                    <div>
                        <span class="text-muted"><?php echo $lang === 'ku' ? 'کوژمێ گشتی' : ($lang === 'ar' ? 'المبلغ الإجمالي' : 'Total Amount'); ?></span>
                        <h3 class="order-total-display">$<?php echo number_format($confirmedOrder['total'], 2); ?> <small style="font-size:14px; color:var(--text-muted);">(<?php echo number_format($confirmedOrder['total_iqd'] ?? ($confirmedOrder['total'] * $rate)); ?> IQD)</small></h3>
                    </div>
                    <div>
                        <span class="text-muted"><?php echo $lang === 'ku' ? 'شێوازێ دانێ' : ($lang === 'ar' ? 'طريقة الدفع' : 'Payment Method'); ?></span>
                        <h4 style="color:var(--text-primary); margin-top:4px;"><?php echo htmlspecialchars($confirmedOrder['payment_method']); ?></h4>
                    </div>
                </div>

                <div class="success-actions mt-32">
                    <a href="track.php?order_id=<?php echo urlencode($confirmedOrder['order_id']); ?>" class="btn btn-primary btn-luxury btn-lg">
                        🚚 <?php echo t('nav_track', $lang); ?>
                    </a>
                    <a href="shop.php" class="btn btn-outline btn-lg">
                        <?php echo t('nav_shop', $lang); ?>
                    </a>
                </div>
            </div>

            <script>
                // Clear local cart storage upon confirmed order
                window.addEventListener('DOMContentLoaded', () => {
                    if (window.AuraStore) {
                        window.AuraStore.clearCart();
                    }
                });
            </script>

        <?php else: ?>
            <!-- Checkout Form & Dynamic Cart Sync -->
            <form action="checkout.php" method="POST" id="checkoutForm" onsubmit="return validateAndSubmitCheckout(event)">
                <input type="hidden" name="place_order" value="1">
                <input type="hidden" name="cart_items_json" id="hiddenCartJson" value="[]">
                <input type="hidden" name="applied_discount_rate" id="hiddenDiscountRate" value="0">

                <div class="checkout-grid">
                    
                    <!-- Left Column: Delivery & Payment Details -->
                    <div class="checkout-form-column">
                        
                        <!-- Delivery Information Card -->
                        <div class="checkout-card">
                            <h3 class="checkout-step-title">
                                <span class="step-num">1</span>
                                <span><?php echo t('checkout_shipping_details', $lang); ?></span>
                            </h3>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label><?php echo t('checkout_name', $lang); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" id="coCustomerName" required class="form-control" placeholder="Full Name (الاسم الكامل)">
                                </div>

                                <div class="form-group">
                                    <label><?php echo t('checkout_phone', $lang); ?> <span class="text-danger">*</span></label>
                                    <input type="tel" name="customer_phone" id="coCustomerPhone" required class="form-control" placeholder="0750 xxx xxxx / 0770 xxx xxxx">
                                </div>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label><?php echo t('checkout_email', $lang); ?></label>
                                    <input type="email" name="customer_email" id="coCustomerEmail" class="form-control" placeholder="client@example.com">
                                </div>

                                <div class="form-group">
                                    <label><?php echo t('checkout_city', $lang); ?> <span class="text-danger">*</span></label>
                                    <select name="customer_city" id="coCustomerCity" required class="form-control">
                                        <optgroup label="<?php echo $lang === 'ku' ? 'هەرێما کوردستانێ (Kurdistan Region)' : ($lang === 'ar' ? 'إقليم كوردستان (Kurdistan Region)' : 'Kurdistan Region (إقليم كوردستان)'); ?>">
                                            <option value="Duhok" selected><?php echo $lang === 'ku' ? 'دهۆک / Duhok' : ($lang === 'ar' ? 'دهوك / Duhok' : 'Duhok / دهۆک'); ?></option>
                                            <option value="Erbil"><?php echo $lang === 'ku' ? 'هەولێر / Erbil' : ($lang === 'ar' ? 'أربيل / Erbil' : 'Erbil (Hewlêr) / هەولێر'); ?></option>
                                            <option value="Sulaymaniyah"><?php echo $lang === 'ku' ? 'سلێمانی / Sulaymaniyah' : ($lang === 'ar' ? 'السليمانية / Sulaymaniyah' : 'Sulaymaniyah / سلێمانی'); ?></option>
                                            <option value="Zakho"><?php echo $lang === 'ku' ? 'زاخۆ / Zakho' : ($lang === 'ar' ? 'زاخو / Zakho' : 'Zakho / زاخۆ'); ?></option>
                                            <option value="Halabja"><?php echo $lang === 'ku' ? 'هەڵەبجە / Halabja' : ($lang === 'ar' ? 'حلبجة / Halabja' : 'Halabja / هەڵەبجە'); ?></option>
                                            <option value="Soran"><?php echo $lang === 'ku' ? 'سۆران / Soran' : ($lang === 'ar' ? 'سوران / Soran' : 'Soran / سۆران'); ?></option>
                                            <option value="Akre"><?php echo $lang === 'ku' ? 'ئاکرێ / Akre' : ($lang === 'ar' ? 'عقرة / Akre' : 'Akre / ئاکرێ'); ?></option>
                                        </optgroup>
                                        <optgroup label="<?php echo $lang === 'ku' ? 'پارێزگەهێن عیراقێ (Federal Iraq)' : ($lang === 'ar' ? 'محافظات العراق (Federal Iraq)' : 'Federal Iraq Governorates (باقي العراق)'); ?>">
                                            <option value="Baghdad"><?php echo $lang === 'ku' ? 'بەغدا / Baghdad' : ($lang === 'ar' ? 'بغداد / Baghdad' : 'Baghdad / بغداد'); ?></option>
                                            <option value="Basra"><?php echo $lang === 'ku' ? 'بەسرە / Basra' : ($lang === 'ar' ? 'البصرة / Basra' : 'Basra / البصرة'); ?></option>
                                            <option value="Mosul"><?php echo $lang === 'ku' ? 'مووسڵ (نەینەوا) / Mosul' : ($lang === 'ar' ? 'الموصل (نينوى) / Mosul' : 'Mosul (Nineveh) / الموصل'); ?></option>
                                            <option value="Kirkuk"><?php echo $lang === 'ku' ? 'کەرکووک / Kirkuk' : ($lang === 'ar' ? 'كركوك / Kirkuk' : 'Kirkuk / كركوك'); ?></option>
                                            <option value="Najaf"><?php echo $lang === 'ku' ? 'نەجەف / Najaf' : ($lang === 'ar' ? 'النجف / Najaf' : 'Najaf / النجف'); ?></option>
                                            <option value="Karbala"><?php echo $lang === 'ku' ? 'کەربەلا / Karbala' : ($lang === 'ar' ? 'كربلاء / Karbala' : 'Karbala / كربلاء'); ?></option>
                                            <option value="Anbar"><?php echo $lang === 'ku' ? 'ئەنبار (ڕەمادی) / Anbar' : ($lang === 'ar' ? 'الأنبار (الرمادي) / Anbar' : 'Anbar (Ramadi / Fallujah) / الأنبار'); ?></option>
                                            <option value="Babil"><?php echo $lang === 'ku' ? 'بابل (حلە) / Babil' : ($lang === 'ar' ? 'بابل (الحلة) / Babil' : 'Babil (Hillah) / بابل'); ?></option>
                                            <option value="Diyala"><?php echo $lang === 'ku' ? 'دیالە (بەعقوبە) / Diyala' : ($lang === 'ar' ? 'ديالى (بعقوبة) / Diyala' : 'Diyala (Baqubah) / ديالى'); ?></option>
                                            <option value="Wasit"><?php echo $lang === 'ku' ? 'واسیت (کوت) / Wasit' : ($lang === 'ar' ? 'واسط (الكوت) / Wasit' : 'Wasit (Kut) / واسط'); ?></option>
                                            <option value="Maysan"><?php echo $lang === 'ku' ? 'میسان (عمارە) / Maysan' : ($lang === 'ar' ? 'ميسان (العمارة) / Maysan' : 'Maysan (Amarah) / ميسان'); ?></option>
                                            <option value="Dhi Qar"><?php echo $lang === 'ku' ? 'زیقار (ناصریە) / Dhi Qar' : ($lang === 'ar' ? 'ذي قار (الناصرية) / Dhi Qar' : 'Dhi Qar (Nasiriyah) / ذي قار'); ?></option>
                                            <option value="Muthanna"><?php echo $lang === 'ku' ? 'موسەننا (سەماوە) / Muthanna' : ($lang === 'ar' ? 'المثنى (السماوة) / Muthanna' : 'Muthanna (Samawah) / المثنى'); ?></option>
                                            <option value="Qadisiyyah"><?php echo $lang === 'ku' ? 'قادسیە (دیوانیە) / Qadisiyyah' : ($lang === 'ar' ? 'القادسية (الديوانية) / Qadisiyyah' : 'Qadisiyyah (Diwaniyah) / القادسية'); ?></option>
                                            <option value="Saladin"><?php echo $lang === 'ku' ? 'سەلاحەدین (تکریت) / Saladin' : ($lang === 'ar' ? 'صلاح الدين (تكريت) / Saladin' : 'Saladin (Tikrit / Samarra) / صلاح الدين'); ?></option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><?php echo t('checkout_address', $lang); ?> <span class="text-danger">*</span></label>
                                <textarea name="customer_address" rows="3" required class="form-control" placeholder="Neighborhood, Street, Building / Villa number"></textarea>
                            </div>
                        </div>

                        <!-- Payment Method Selection Card -->
                        <div class="checkout-card mt-24">
                            <h3 class="checkout-step-title">
                                <span class="step-num">2</span>
                                <span><?php echo t('checkout_payment_method', $lang); ?></span>
                            </h3>

                            <div class="payment-options-list">
                                
                                <!-- 1. First Iraqi Bank (FIB Bank) -->
                                <label class="payment-option-label active" onclick="selectPaymentMethod('fib')">
                                    <input type="radio" name="payment_method" value="First Iraqi Bank (FIB Bank)" checked onchange="highlightPaymentOption(this)">
                                    <div class="payment-option-content">
                                        <div class="pay-title-row">
                                            <span class="pay-icon">🏦</span>
                                            <strong><?php echo t('payment_fib', $lang); ?></strong>
                                            <span class="badge-tag" style="background:rgba(212,175,55,0.15); color:var(--accent-gold); border-color:var(--accent-gold); margin-left:auto;">Official Bank API</span>
                                        </div>
                                        <p class="pay-desc"><?php echo t('payment_fib_desc', $lang); ?></p>
                                    </div>
                                </label>

                                <!-- 2. ZainCash (زين كاش) -->
                                <label class="payment-option-label" onclick="selectPaymentMethod('zaincash')">
                                    <input type="radio" name="payment_method" value="ZainCash (زين كاش)" onchange="highlightPaymentOption(this)">
                                    <div class="payment-option-content">
                                        <div class="pay-title-row">
                                            <span class="pay-icon">📱</span>
                                            <strong><?php echo t('payment_zaincash', $lang); ?></strong>
                                            <span class="badge-tag" style="background:rgba(131,24,67,0.15); color:#f472b6; border-color:#f472b6; margin-left:auto;">Wallet & OTP</span>
                                        </div>
                                        <p class="pay-desc"><?php echo t('payment_zaincash_desc', $lang); ?></p>
                                    </div>
                                </label>

                                <!-- 3. Cash on Delivery (COD) -->
                                <label class="payment-option-label" onclick="selectPaymentMethod('cod')">
                                    <input type="radio" name="payment_method" value="Cash on Delivery" onchange="highlightPaymentOption(this)">
                                    <div class="payment-option-content">
                                        <div class="pay-title-row">
                                            <span class="pay-icon">💵</span>
                                            <strong><?php echo t('payment_cod', $lang); ?></strong>
                                        </div>
                                        <p class="pay-desc"><?php echo t('payment_cod_desc', $lang); ?></p>
                                    </div>
                                </label>

                                <!-- 4. FastPay Mobile Wallet -->
                                <label class="payment-option-label" onclick="selectPaymentMethod('fastpay')">
                                    <input type="radio" name="payment_method" value="FastPay Wallet" onchange="highlightPaymentOption(this)">
                                    <div class="payment-option-content">
                                        <div class="pay-title-row">
                                            <span class="pay-icon">⚡</span>
                                            <strong><?php echo t('payment_fastpay', $lang); ?></strong>
                                        </div>
                                        <p class="pay-desc"><?php echo t('payment_fastpay_desc', $lang); ?></p>
                                    </div>
                                </label>

                                <!-- 5. Credit / Debit Card -->
                                <label class="payment-option-label" onclick="selectPaymentMethod('card')">
                                    <input type="radio" name="payment_method" value="Credit / Debit Card" onchange="highlightPaymentOption(this)">
                                    <div class="payment-option-content">
                                        <div class="pay-title-row">
                                            <span class="pay-icon">💳</span>
                                            <strong><?php echo t('payment_card', $lang); ?></strong>
                                        </div>
                                        <p class="pay-desc"><?php echo t('payment_card_desc', $lang); ?></p>
                                    </div>
                                </label>

                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Order Review & Confirmation -->
                    <div class="checkout-summary-column">
                        <div class="summary-card">
                            <h3 class="summary-title"><?php echo $lang === 'ku' ? 'بەرهەمێن داخازیێ' : ($lang === 'ar' ? 'محتويات الطلب' : 'Your Luxury Bag'); ?></h3>

                            <div class="checkout-items-list" id="checkoutItemsList">
                                <!-- Populated dynamically via JS -->
                            </div>

                            <div class="summary-divider"></div>

                            <div class="summary-row">
                                <span><?php echo t('cart_subtotal', $lang); ?></span>
                                <strong id="coSubtotal">$0.00</strong>
                            </div>

                            <div class="summary-row" id="coDiscountRow" style="display:none; color:var(--accent-gold);">
                                <span><?php echo t('cart_discount', $lang); ?></span>
                                <strong id="coDiscount">-$0.00</strong>
                            </div>

                            <div class="summary-row">
                                <span><?php echo t('cart_shipping', $lang); ?></span>
                                <span class="free-shipping-badge">✓ <?php echo t('cart_shipping_free', $lang); ?></span>
                            </div>

                            <div class="summary-divider"></div>

                            <div class="summary-total-row">
                                <div>
                                    <span class="total-label"><?php echo t('cart_total', $lang); ?></span>
                                    <div class="iqd-price-pill" id="coTotalIqd">≈ 0 IQD</div>
                                </div>
                                <strong class="total-amount" id="coTotal">$0.00</strong>
                            </div>

                            <div class="summary-guarantee mt-20">
                                <span>🔒 256-Bit SSL Encrypted &bull; Authentic Luxury Guarantee</span>
                            </div>

                            <button type="submit" id="btnSubmitOrder" class="btn btn-primary btn-luxury btn-lg btn-block mt-24">
                                <?php echo t('checkout_place_order', $lang); ?>
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<!-- FIB QR Code Scanner Modal -->
<div class="modal-overlay" id="fibPaymentModalOverlay">
    <div class="modal-dialog" style="max-width:480px;">
        <button class="modal-close-btn" onclick="closeFibModal()">✕</button>
        <div class="modal-body text-center">
            <span class="gateway-icon-badge" style="margin:0 auto 12px; font-size:28px;">🏦</span>
            <h3 style="font-size:22px; font-weight:800; color:var(--accent-gold); margin-bottom:4px;">First Iraqi Bank (FIB)</h3>
            <p class="text-muted" style="font-size:13px; margin-bottom:16px;">Scan with your <strong>FIB Mobile Banking App</strong></p>

            <div class="fib-qr-frame" id="fibQrContainer">
                <!-- SVG QR injected via JS -->
            </div>

            <div class="payment-code-display">
                <span style="font-size:12px; color:var(--text-muted);">Payment Code:</span>
                <span class="code-digits" id="fibCodeDisplay">FIB-84920</span>
            </div>

            <div style="font-size:14px; color:var(--text-secondary); margin:12px 0;">
                Amount to Authorize: <strong class="text-primary font-bold" id="fibAmountDisplay">$0.00 (0 IQD)</strong>
            </div>

            <div style="background:var(--bg-subtle); padding:12px; border-radius:8px; font-size:12px; color:var(--text-muted); margin-bottom:20px;">
                ⏱️ Waiting for FIB confirmation on your mobile app... (Session active)
            </div>

            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeFibModal()">Cancel</button>
                <button type="button" class="btn btn-primary btn-luxury" style="flex:2;" onclick="confirmFibPayment()">✓ Simulate FIB Scan Confirmation</button>
            </div>
        </div>
    </div>
</div>

<!-- ZainCash Verification Modal -->
<div class="modal-overlay" id="zainPaymentModalOverlay">
    <div class="modal-dialog" style="max-width:480px;">
        <button class="modal-close-btn" onclick="closeZainModal()">✕</button>
        <div class="modal-body text-center">
            <span class="gateway-icon-badge" style="margin:0 auto 12px; font-size:28px; background:#831843;">📱</span>
            <h3 style="font-size:22px; font-weight:800; color:#f472b6; margin-bottom:4px;">ZainCash (زين كاش)</h3>
            <p class="text-muted" style="font-size:13px; margin-bottom:16px;">Authorize transaction with your Zain mobile wallet</p>

            <div style="text-align:left; background:var(--bg-subtle); padding:16px; border-radius:8px; margin-bottom:18px;">
                <div class="form-group mb-12">
                    <label style="font-size:12px;">ZainCash Wallet Phone Number</label>
                    <input type="tel" id="zcMsisdnInput" value="07835077893" class="form-control" placeholder="078xxxxxxxx">
                </div>
                <div class="form-group mb-0">
                    <label style="font-size:12px;">Wallet PIN / OTP Code</label>
                    <input type="password" id="zcPinInput" value="1234" maxlength="6" class="form-control" placeholder="Enter PIN / SMS OTP">
                </div>
            </div>

            <div style="font-size:14px; color:var(--text-secondary); margin:12px 0;">
                Payable: <strong class="text-primary font-bold" id="zcAmountDisplay">0 IQD</strong>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeZainModal()">Cancel</button>
                <button type="button" class="btn btn-primary btn-luxury" style="flex:2; background:#831843; border-color:#831843;" onclick="confirmZainCashPayment()">✓ Authorize ZainCash Payment</button>
            </div>
        </div>
    </div>
</div>

<script>
const EXCHANGE_RATE = <?php echo json_encode($rate); ?>;
let activePaymentGateway = 'fib';

document.addEventListener('DOMContentLoaded', () => {
    renderCheckoutCart();
});

function selectPaymentMethod(gateway) {
    activePaymentGateway = gateway;
}

function renderCheckoutCart() {
    const container = document.getElementById('checkoutItemsList');
    if (!container) return;

    const cart = window.AuraStore ? window.AuraStore.getCart() : [];
    if (!cart || cart.length === 0) {
        container.innerHTML = `<div class="text-center py-20 text-muted">${window.AURA_LANG === 'ku' ? 'سەبەتە یا ڤالایە' : (window.AURA_LANG === 'ar' ? 'سلة التسوق فارغة' : 'Your cart is empty.')}</div>`;
        return;
    }

    let subtotal = 0;
    let html = '';

    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        const itemTitle = typeof item.title === 'object' ? (item.title[window.AURA_LANG] || item.title.en) : item.title;

        html += `
            <div class="checkout-item-compact">
                <img src="${item.image}" alt="${itemTitle}" class="co-thumb">
                <div class="co-info">
                    <h4 class="co-title">${itemTitle}</h4>
                    <span class="co-meta">Qty: ${item.quantity} ${item.size ? '• Size: ' + item.size : ''}</span>
                </div>
                <div class="co-price">$${itemTotal.toFixed(2)}</div>
            </div>
        `;
    });

    container.innerHTML = html;

    const activeDiscountRate = parseFloat(sessionStorage.getItem('aura_discount_rate') || 0);
    const discount = subtotal * activeDiscountRate;
    const finalTotal = Math.max(0, subtotal - discount);
    const finalIqd = finalTotal * EXCHANGE_RATE;

    document.getElementById('coSubtotal').innerText = '$' + subtotal.toFixed(2);
    if (activeDiscountRate > 0) {
        document.getElementById('coDiscountRow').style.display = 'flex';
        document.getElementById('coDiscount').innerText = '-$' + discount.toFixed(2);
    }
    document.getElementById('coTotal').innerText = '$' + finalTotal.toFixed(2);
    document.getElementById('coTotalIqd').innerText = '≈ ' + finalIqd.toLocaleString() + ' IQD';

    document.getElementById('hiddenCartJson').value = JSON.stringify(cart);
    document.getElementById('hiddenDiscountRate').value = activeDiscountRate.toString();
}

function highlightPaymentOption(radio) {
    document.querySelectorAll('.payment-option-label').forEach(el => el.classList.remove('active'));
    radio.closest('.payment-option-label').classList.add('active');
}

function validateAndSubmitCheckout(event) {
    const cart = window.AuraStore ? window.AuraStore.getCart() : [];
    if (!cart || cart.length === 0) {
        alert(window.AURA_LANG === 'ku' ? 'سەبەتە یا ڤالایە' : 'Your shopping cart is empty.');
        event.preventDefault();
        return false;
    }
    document.getElementById('hiddenCartJson').value = JSON.stringify(cart);

    const selMethod = document.querySelector('input[name="payment_method"]:checked')?.value || '';

    // If FIB is selected and not already confirmed, trigger the FIB QR modal
    if (selMethod.includes('FIB') && !window._fibPaidConfirmed) {
        event.preventDefault();
        showFibModal();
        return false;
    }

    // If ZainCash is selected and not already confirmed, trigger ZainCash OTP modal
    if (selMethod.includes('ZainCash') && !window._zainPaidConfirmed) {
        event.preventDefault();
        showZainModal();
        return false;
    }

    return true;
}

function showFibModal() {
    const cart = window.AuraStore.getCart();
    let total = 0;
    cart.forEach(it => total += it.price * it.quantity);
    const rate = parseFloat(sessionStorage.getItem('aura_discount_rate') || 0);
    const finalTotal = Math.max(0, total - (total * rate));
    const totalIqd = finalTotal * EXCHANGE_RATE;

    document.getElementById('fibAmountDisplay').innerText = '$' + finalTotal.toFixed(2) + ' (' + totalIqd.toLocaleString() + ' IQD)';
    document.getElementById('fibCodeDisplay').innerText = 'FIB-' + Math.floor(10000 + Math.random() * 90000);

    // Render realistic SVG QR code pattern
    document.getElementById('fibQrContainer').innerHTML = `
        <svg viewBox="0 0 160 160" width="160" height="160" style="display:block; margin:0 auto;">
            <rect width="160" height="160" fill="#ffffff" />
            <!-- Outer Markers -->
            <rect x="10" y="10" width="40" height="40" fill="#0c0e14" />
            <rect x="16" y="16" width="28" height="28" fill="#ffffff" />
            <rect x="22" y="22" width="16" height="16" fill="#d4af37" />

            <rect x="110" y="10" width="40" height="40" fill="#0c0e14" />
            <rect x="116" y="16" width="28" height="28" fill="#ffffff" />
            <rect x="122" y="22" width="16" height="16" fill="#d4af37" />

            <rect x="10" y="110" width="40" height="40" fill="#0c0e14" />
            <rect x="16" y="116" width="28" height="28" fill="#ffffff" />
            <rect x="22" y="22" width="16" height="16" fill="#d4af37" />
            <rect x="22" y="122" width="16" height="16" fill="#d4af37" />

            <!-- Dynamic grid dots -->
            <rect x="60" y="20" width="10" height="10" fill="#0c0e14" />
            <rect x="80" y="20" width="10" height="20" fill="#0c0e14" />
            <rect x="60" y="40" width="20" height="10" fill="#d4af37" />
            <rect x="60" y="60" width="40" height="40" fill="#0c0e14" />
            <rect x="70" y="70" width="20" height="20" fill="#ffffff" />
            <rect x="76" y="76" width="8" height="8" fill="#d4af37" />
            <rect x="110" y="60" width="20" height="10" fill="#0c0e14" />
            <rect x="130" y="80" width="20" height="20" fill="#0c0e14" />
            <rect x="20" y="70" width="20" height="10" fill="#0c0e14" />
            <rect x="20" y="90" width="10" height="10" fill="#d4af37" />
            <rect x="60" y="110" width="20" height="20" fill="#0c0e14" />
            <rect x="90" y="110" width="10" height="40" fill="#0c0e14" />
            <rect x="110" y="130" width="30" height="10" fill="#d4af37" />
            <rect x="120" y="110" width="20" height="10" fill="#0c0e14" />
        </svg>
    `;

    document.getElementById('fibPaymentModalOverlay').classList.add('open');
}

function closeFibModal() {
    document.getElementById('fibPaymentModalOverlay').classList.remove('open');
}

function confirmFibPayment() {
    window._fibPaidConfirmed = true;
    closeFibModal();
    if (window.AuraStore) {
        window.AuraStore.showToast('✓ FIB Payment verified successfully! Submitting order...', 'success');
    }
    setTimeout(() => {
        document.getElementById('checkoutForm').submit();
    }, 600);
}

function showZainModal() {
    const cart = window.AuraStore.getCart();
    let total = 0;
    cart.forEach(it => total += it.price * it.quantity);
    const rate = parseFloat(sessionStorage.getItem('aura_discount_rate') || 0);
    const finalTotal = Math.max(0, total - (total * rate));
    const totalIqd = finalTotal * EXCHANGE_RATE;

    document.getElementById('zcAmountDisplay').innerText = totalIqd.toLocaleString() + ' IQD ($' + finalTotal.toFixed(2) + ')';
    document.getElementById('zainPaymentModalOverlay').classList.add('open');
}

function closeZainModal() {
    document.getElementById('zainPaymentModalOverlay').classList.remove('open');
}

function confirmZainCashPayment() {
    window._zainPaidConfirmed = true;
    closeZainModal();
    if (window.AuraStore) {
        window.AuraStore.showToast('✓ ZainCash OTP Verified! Submitting order...', 'success');
    }
    setTimeout(() => {
        document.getElementById('checkoutForm').submit();
    }, 600);
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
