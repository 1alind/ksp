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
$checkoutError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['customer_email'] ?? '');
    $phone = trim($_POST['customer_phone'] ?? '');
    $phoneConfirm = trim($_POST['customer_phone_confirm'] ?? '');
    $governorate = trim($_POST['customer_governorate'] ?? 'Duhok');
    $district = trim($_POST['customer_city'] ?? '');
    $fullCityLocation = !empty($district) ? ($governorate . ' (' . $district . ')') : $governorate;
    $address = trim($_POST['customer_address'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? 'Cash on Delivery');
    $cartJson = $_POST['cart_items_json'] ?? '[]';
    $cartItems = json_decode($cartJson, true) ?: [];

    // Digits-only comparison for phone number validation
    $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
    $cleanPhoneConfirm = preg_replace('/[^\d+]/', '', $phoneConfirm);

    if (empty($phone) || empty($phoneConfirm) || $cleanPhone !== $cleanPhoneConfirm) {
        $checkoutError = t('checkout_phone_mismatch', $lang);
    } elseif (empty($district)) {
        $checkoutError = $lang === 'ku' ? 'تکایە باژێر یان قەزایێ د ناڤ پارێزگەهێ دا هەلبژێرە.' : ($lang === 'ar' ? 'يرجى اختيار المدينة أو القضاء داخل المحافظة.' : 'Please select the city/district inside the selected governorate.');
    } elseif (!empty($name) && !empty($phone) && !empty($address) && !empty($cartItems)) {
        $subtotal = 0;
        foreach ($cartItems as $ci) {
            $subtotal += ($ci['price'] * $ci['quantity']);
        }
        $discountRate = floatval($_POST['applied_discount_rate'] ?? 0);
        $discount = round($subtotal * $discountRate);
        $shippingFee = ($governorate === 'Duhok') ? 4000 : 5000;
        $total = max(0, $subtotal - $discount + $shippingFee);
        $totalIqd = $total;

        $isOnlinePaid = strpos($paymentMethod, 'FIB') !== false || strpos($paymentMethod, 'ZainCash') !== false || strpos($paymentMethod, 'FastPay') !== false;

        $orderPayload = [
            'order_id' => 'ORD-' . rand(10000, 99999),
            'customer_name' => htmlspecialchars($name),
            'email' => htmlspecialchars($email),
            'phone' => htmlspecialchars($phone),
            'governorate' => htmlspecialchars($governorate),
            'district' => htmlspecialchars($district),
            'city' => htmlspecialchars($fullCityLocation),
            'address' => htmlspecialchars($address),
            'payment_method' => htmlspecialchars($paymentMethod),
            'payment_status' => $isOnlinePaid ? 'Paid (Verified Online)' : 'Pending (Pay on Delivery)',
            'payment_gateway_tx' => $isOnlinePaid ? ('TX-' . strtoupper(substr(md5(uniqid()), 0, 10))) : 'COD-' . rand(1000, 9999),
            'order_status' => 'Waiting',
            'courier' => '',
            'tracking_code' => '',
            'dispatch_notes' => 'Order placed. Waiting for fulfillment & package preparation.',
            'estimated_delivery' => 'Estimated Arrival: Within 24 – 72 Hours',
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'shipping' => $shippingFee,
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
                        <h3 class="order-total-display"><?php echo number_format($confirmedOrder['total']); ?> IQD</h3>
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

                            <?php if (!empty($checkoutError)): ?>
                                <div class="alert alert-danger mb-16" style="background:rgba(239,68,68,0.12); border:1px solid #ef4444; border-radius:8px; padding:12px 16px; color:#ef4444; font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px;">
                                    <span>⚠️</span>
                                    <span><?php echo htmlspecialchars($checkoutError); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="form-group mb-16">
                                <label><?php echo t('checkout_name', $lang); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" id="coCustomerName" required class="form-control" placeholder="Full Name (الاسم الكامل)">
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label><?php echo t('checkout_governorate', $lang); ?> <span class="text-danger">*</span></label>
                                    <select name="customer_governorate" id="coCustomerGovernorate" required class="form-control" onchange="onGovernorateSelectChange(this.value)">
                                        <optgroup label="<?php echo $lang === 'ku' ? 'هەرێما کوردستانێ (Kurdistan Region)' : ($lang === 'ar' ? 'إقليم كوردستان (Kurdistan Region)' : 'Kurdistan Region (إقليم كوردستان)'); ?>">
                                            <option value="Duhok" selected><?php echo $lang === 'ku' ? 'دهۆک / Duhok' : ($lang === 'ar' ? 'دهوك / Duhok' : 'Duhok / دهۆک'); ?></option>
                                            <option value="Erbil"><?php echo $lang === 'ku' ? 'هەولێر / Erbil' : ($lang === 'ar' ? 'أربيل / Erbil' : 'Erbil (Hewlêr) / هەولێر'); ?></option>
                                            <option value="Sulaymaniyah"><?php echo $lang === 'ku' ? 'سلێمانی / Sulaymaniyah' : ($lang === 'ar' ? 'السليمانية / Sulaymaniyah' : 'Sulaymaniyah / سلێمانی'); ?></option>
                                            <option value="Halabja"><?php echo $lang === 'ku' ? 'هەڵەبجە / Halabja' : ($lang === 'ar' ? 'حلبجة / Halabja' : 'Halabja / هەڵەبجە'); ?></option>
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

                                <div class="form-group">
                                    <label><?php echo t('checkout_district', $lang); ?> <span class="text-danger">*</span></label>
                                    <select name="customer_city" id="coCustomerCity" required class="form-control">
                                        <option value="Duhok City Center" selected><?php echo $lang === 'ku' ? 'ناڤەندا باژێرێ دهۆکێ / Duhok Center' : ($lang === 'ar' ? 'مركز مدينة دهوك / Duhok Center' : 'Duhok City Center / ناڤەندا دهۆکێ'); ?></option>
                                        <option value="Zakho"><?php echo $lang === 'ku' ? 'زاخۆ / Zakho' : ($lang === 'ar' ? 'زاخو / Zakho' : 'Zakho / زاخۆ'); ?></option>
                                        <option value="Semel"><?php echo $lang === 'ku' ? 'سێمێل / Semel' : ($lang === 'ar' ? 'سميل / Semel' : 'Semel / سێمێل'); ?></option>
                                        <option value="Amedi"><?php echo $lang === 'ku' ? 'ئامێدیێ / Amedi' : ($lang === 'ar' ? 'العمادية / Amedi' : 'Amedi (Amadiya) / ئامێدیێ'); ?></option>
                                        <option value="Akre"><?php echo $lang === 'ku' ? 'ئاکرێ / Akre' : ($lang === 'ar' ? 'عقرة / Akre' : 'Akre (Aqrah) / ئاکرێ'); ?></option>
                                        <option value="Shekhan"><?php echo $lang === 'ku' ? 'شێخان / Shekhan' : ($lang === 'ar' ? 'الشيخان (عين سفني) / Shekhan' : 'Shekhan (Ain Sifni) / شێخان'); ?></option>
                                        <option value="Bardarash"><?php echo $lang === 'ku' ? 'بەردەڕەش / Bardarash' : ($lang === 'ar' ? 'بردرش / Bardarash' : 'Bardarash / بەردەڕەش'); ?></option>
                                        <option value="Deraluk & Shiladze"><?php echo $lang === 'ku' ? 'دێرەلووک و شێلادزێ / Deraluk & Shiladze' : ($lang === 'ar' ? 'ديرلوك وشيلادزي / Deraluk' : 'Deraluk & Shiladze'); ?></option>
                                        <option value="Batifa"><?php echo $lang === 'ku' ? 'باتێفا / Batifa' : ($lang === 'ar' ? 'باتيفا / Batifa' : 'Batifa / باتێفا'); ?></option>
                                        <option value="Kani Masi"><?php echo $lang === 'ku' ? 'کانی ماسی / Kani Masi' : ($lang === 'ar' ? 'كاني ماسي / Kani Masi' : 'Kani Masi / کانی ماسی'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label><?php echo t('checkout_phone', $lang); ?> <span class="text-danger">*</span></label>
                                    <input type="tel" name="customer_phone" id="coCustomerPhone" required class="form-control" placeholder="0750 xxx xxxx / 0770 xxx xxxx" autocomplete="tel">
                                </div>

                                <div class="form-group">
                                    <label><?php echo t('checkout_phone_confirm', $lang); ?> <span class="text-danger">*</span></label>
                                    <input type="tel" name="customer_phone_confirm" id="coCustomerPhoneConfirm" required class="form-control" placeholder="Re-enter phone number (تأكيد الرقم)" autocomplete="tel">
                                    <div id="phoneMatchNotice" class="phone-match-notice" style="display:none; font-size:12px; margin-top:6px; font-weight:600;"></div>
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
                                            <div class="pay-logo-inline">
                                                <svg viewBox="0 0 110 32" width="90" height="26" xmlns="http://www.w3.org/2000/svg">
                                                    <rect width="110" height="32" rx="6" fill="#0A192F"/>
                                                    <path d="M12 16 L17 10 L22 16 L17 22 Z" fill="#D4AF37"/>
                                                    <path d="M17 13 L19.5 16 L17 19 L14.5 16 Z" fill="#0A192F"/>
                                                    <circle cx="17" cy="16" r="1.5" fill="#FFFFFF"/>
                                                    <text x="28" y="21.5" fill="#FFFFFF" font-family="system-ui, sans-serif" font-weight="900" font-size="14" letter-spacing="0.5">FIB</text>
                                                    <text x="60" y="14.5" fill="#D4AF37" font-family="system-ui, sans-serif" font-weight="700" font-size="5.8" letter-spacing="0.4">FIRST IRAQI</text>
                                                    <text x="60" y="22" fill="#94A3B8" font-family="system-ui, sans-serif" font-weight="600" font-size="5.5" letter-spacing="0.4">BANK</text>
                                                </svg>
                                            </div>
                                            <strong><?php echo t('payment_fib', $lang); ?></strong>
                                            <span class="badge-tag" style="background:rgba(212,175,55,0.15); color:var(--accent-gold); border-color:var(--accent-gold); margin-left:auto;">Official Bank API</span>
                                        </div>
                                        <p class="pay-desc"><?php echo t('payment_fib_desc', $lang); ?></p>
                                    </div>
                                </label>

                                <!-- 2. FastPay Mobile Wallet -->
                                <label class="payment-option-label" onclick="selectPaymentMethod('fastpay')">
                                    <input type="radio" name="payment_method" value="FastPay Wallet" onchange="highlightPaymentOption(this)">
                                    <div class="payment-option-content">
                                        <div class="pay-title-row">
                                            <div class="pay-logo-inline">
                                                <svg viewBox="0 0 110 32" width="90" height="26" xmlns="http://www.w3.org/2000/svg">
                                                    <rect width="110" height="32" rx="6" fill="#FFC800"/>
                                                    <g transform="translate(8, 6)">
                                                        <circle cx="10" cy="10" r="9" fill="#111827"/>
                                                        <path d="M7 6.5 L11.5 10 L7 13.5" stroke="#FFC800" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                        <path d="M10.5 6.5 L15 10 L10.5 13.5" stroke="#FFC800" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                    </g>
                                                    <text x="32" y="21" fill="#111827" font-family="system-ui, sans-serif" font-weight="900" font-size="13.5" letter-spacing="-0.3">FastPay</text>
                                                </svg>
                                            </div>
                                            <strong><?php echo t('payment_fastpay', $lang); ?></strong>
                                            <span class="badge-tag" style="background:rgba(255,200,0,0.15); color:#eab308; border-color:#eab308; margin-left:auto;">Instant Wallet</span>
                                        </div>
                                        <p class="pay-desc"><?php echo t('payment_fastpay_desc', $lang); ?></p>
                                    </div>
                                </label>

                                <!-- 3. ZainCash (زين كاش) -->
                                <label class="payment-option-label" onclick="selectPaymentMethod('zaincash')">
                                    <input type="radio" name="payment_method" value="ZainCash (زين كاش)" onchange="highlightPaymentOption(this)">
                                    <div class="payment-option-content">
                                        <div class="pay-title-row">
                                            <div class="pay-logo-inline">
                                                <svg viewBox="0 0 110 32" width="90" height="26" xmlns="http://www.w3.org/2000/svg">
                                                    <rect width="110" height="32" rx="6" fill="#1F132B"/>
                                                    <g transform="translate(8, 6)">
                                                        <circle cx="10" cy="10" r="8.5" fill="none" stroke="#EC4899" stroke-width="2.5"/>
                                                        <path d="M6.5 10 C6.5 8 8 6.5 10 6.5 C12 6.5 13.5 8 13.5 10 C13.5 12 12 13.5 10 13.5" stroke="#A855F7" stroke-width="2" stroke-linecap="round" fill="none"/>
                                                        <circle cx="10" cy="10" r="1.8" fill="#38BDF8"/>
                                                    </g>
                                                    <text x="32" y="21" fill="#FFFFFF" font-family="system-ui, sans-serif" font-weight="900" font-size="13" letter-spacing="-0.2">Zain<tspan fill="#EC4899">Cash</tspan></text>
                                                </svg>
                                            </div>
                                            <strong><?php echo t('payment_zaincash', $lang); ?></strong>
                                            <span class="badge-tag" style="background:rgba(131,24,67,0.15); color:#f472b6; border-color:#f472b6; margin-left:auto;">Wallet & OTP</span>
                                        </div>
                                        <p class="pay-desc"><?php echo t('payment_zaincash_desc', $lang); ?></p>
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
                                <strong id="coSubtotal">0 IQD</strong>
                            </div>

                            <div class="summary-row" id="coDiscountRow" style="display:none; color:var(--accent-gold);">
                                <span><?php echo t('cart_discount', $lang); ?></span>
                                <strong id="coDiscount">-0 IQD</strong>
                            </div>

                            <div class="summary-row">
                                <span><?php echo t('cart_shipping', $lang); ?></span>
                                <strong id="coShipping" style="color:var(--accent-gold);">4,000 IQD</strong>
                            </div>

                            <div class="delivery-rate-subnote" id="coShippingNote" style="font-size:12px; color:var(--text-muted); margin-top:-4px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                                <span>📍 <span id="coShippingGovText"><?php echo $lang === 'ku' ? 'دهۆک (٤,٠٠٠ دینار)' : ($lang === 'ar' ? 'دهوك (4,000 د.ع)' : 'Duhok (4,000 IQD)'); ?></span></span>
                                <span class="badge-tag" id="coShippingBadge" style="font-size:10.5px; padding:2px 8px; background:rgba(34,197,94,0.12); color:#22c55e; border-color:#22c55e;">
                                    <?php echo $lang === 'ku' ? 'نرخێ دهۆکێ: ٤,٠٠٠' : ($lang === 'ar' ? 'سعر دهوك: 4,000' : 'Duhok: 4,000 IQD'); ?>
                                </span>
                            </div>

                            <div class="summary-divider"></div>

                            <div class="summary-total-row">
                                <div>
                                    <span class="total-label"><?php echo t('cart_total', $lang); ?></span>
                                </div>
                                <strong class="total-amount" id="coTotal">0 IQD</strong>
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
            <div class="modal-logo-header" style="margin-bottom:14px;">
                <svg viewBox="0 0 110 32" width="120" height="36" xmlns="http://www.w3.org/2000/svg">
                    <rect width="110" height="32" rx="6" fill="#0A192F"/>
                    <path d="M12 16 L17 10 L22 16 L17 22 Z" fill="#D4AF37"/>
                    <path d="M17 13 L19.5 16 L17 19 L14.5 16 Z" fill="#0A192F"/>
                    <circle cx="17" cy="16" r="1.5" fill="#FFFFFF"/>
                    <text x="28" y="21.5" fill="#FFFFFF" font-family="system-ui, sans-serif" font-weight="900" font-size="14" letter-spacing="0.5">FIB</text>
                    <text x="60" y="14.5" fill="#D4AF37" font-family="system-ui, sans-serif" font-weight="700" font-size="5.8" letter-spacing="0.4">FIRST IRAQI</text>
                    <text x="60" y="22" fill="#94A3B8" font-family="system-ui, sans-serif" font-weight="600" font-size="5.5" letter-spacing="0.4">BANK</text>
                </svg>
            </div>
            <h3 style="font-size:20px; font-weight:800; color:var(--accent-gold); margin-bottom:4px;">First Iraqi Bank (FIB)</h3>
            <p class="text-muted" style="font-size:13px; margin-bottom:16px;">Scan with your <strong>FIB Mobile Banking App</strong></p>

            <div class="fib-qr-frame" id="fibQrContainer">
                <!-- SVG QR injected via JS -->
            </div>

            <div class="payment-code-display">
                <span style="font-size:12px; color:var(--text-muted);">Payment Code:</span>
                <span class="code-digits" id="fibCodeDisplay">FIB-84920</span>
            </div>

            <div style="font-size:14px; color:var(--text-secondary); margin:12px 0;">
                Amount to Authorize: <strong class="text-primary font-bold" id="fibAmountDisplay">0 IQD</strong>
            </div>

            <div style="background:var(--bg-subtle); padding:12px; border-radius:8px; font-size:12px; color:var(--text-muted); margin-bottom:20px;">
                ⏱️ Waiting for FIB confirmation on your mobile app... (Session active)
                <div style="margin-top:6px;">
                    <a href="payment/fake.php?gateway=fib" target="_blank" style="color:var(--accent-gold); text-decoration:underline;">⚡ Open Simulated FIB Banking Server (fake.php)</a>
                </div>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeFibModal()">Cancel</button>
                <button type="button" class="btn btn-primary btn-luxury" style="flex:2;" onclick="confirmFibPayment()">✓ Simulate FIB Scan Confirmation</button>
            </div>
        </div>
    </div>
</div>

<!-- FastPay Mobile Wallet Modal -->
<div class="modal-overlay" id="fastpayPaymentModalOverlay">
    <div class="modal-dialog" style="max-width:480px;">
        <button class="modal-close-btn" onclick="closeFastPayModal()">✕</button>
        <div class="modal-body text-center">
            <div class="modal-logo-header" style="margin-bottom:14px;">
                <svg viewBox="0 0 110 32" width="120" height="36" xmlns="http://www.w3.org/2000/svg">
                    <rect width="110" height="32" rx="6" fill="#FFC800"/>
                    <g transform="translate(8, 6)">
                        <circle cx="10" cy="10" r="9" fill="#111827"/>
                        <path d="M7 6.5 L11.5 10 L7 13.5" stroke="#FFC800" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        <path d="M10.5 6.5 L15 10 L10.5 13.5" stroke="#FFC800" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    </g>
                    <text x="32" y="21" fill="#111827" font-family="system-ui, sans-serif" font-weight="900" font-size="13.5" letter-spacing="-0.3">FastPay</text>
                </svg>
            </div>
            <h3 style="font-size:20px; font-weight:800; color:#eab308; margin-bottom:4px;">FastPay Mobile Wallet</h3>
            <p class="text-muted" style="font-size:13px; margin-bottom:16px;">Scan FastPay QR code or authorize via wallet account</p>

            <div class="fib-qr-frame" id="fastpayQrContainer">
                <!-- FastPay QR injected via JS -->
            </div>

            <div style="text-align:left; background:var(--bg-subtle); padding:14px; border-radius:8px; margin:16px 0;">
                <div class="form-group mb-10">
                    <label style="font-size:12px;">FastPay Mobile Number</label>
                    <input type="tel" id="fpMobileInput" value="07501234567" class="form-control" placeholder="0750xxxxxxx">
                </div>
                <div class="form-group mb-0">
                    <label style="font-size:12px;">FastPay PIN / 6-digit OTP</label>
                    <input type="password" id="fpPinInput" value="882244" maxlength="6" class="form-control" placeholder="Enter FastPay PIN / OTP">
                </div>
            </div>

            <div style="font-size:14px; color:var(--text-secondary); margin:12px 0;">
                Payable: <strong class="text-primary font-bold" id="fpAmountDisplay">0 IQD</strong>
                <div style="margin-top:4px;">
                    <a href="payment/fake.php?gateway=fastpay" target="_blank" style="font-size:12px; color:#eab308; text-decoration:underline;">⚡ Open FastPay Simulator (fake.php)</a>
                </div>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeFastPayModal()">Cancel</button>
                <button type="button" class="btn btn-primary btn-luxury" style="flex:2; background:#FFC800; color:#111827; border-color:#FFC800;" onclick="confirmFastPayPayment()">✓ Authorize FastPay Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- ZainCash Verification Modal -->
<div class="modal-overlay" id="zainPaymentModalOverlay">
    <div class="modal-dialog" style="max-width:480px;">
        <button class="modal-close-btn" onclick="closeZainModal()">✕</button>
        <div class="modal-body text-center">
            <div class="modal-logo-header" style="margin-bottom:14px;">
                <svg viewBox="0 0 110 32" width="120" height="36" xmlns="http://www.w3.org/2000/svg">
                    <rect width="110" height="32" rx="6" fill="#1F132B"/>
                    <g transform="translate(8, 6)">
                        <circle cx="10" cy="10" r="8.5" fill="none" stroke="#EC4899" stroke-width="2.5"/>
                        <path d="M6.5 10 C6.5 8 8 6.5 10 6.5 C12 6.5 13.5 8 13.5 10 C13.5 12 12 13.5 10 13.5" stroke="#A855F7" stroke-width="2" stroke-linecap="round" fill="none"/>
                        <circle cx="10" cy="10" r="1.8" fill="#38BDF8"/>
                    </g>
                    <text x="32" y="21" fill="#FFFFFF" font-family="system-ui, sans-serif" font-weight="900" font-size="13" letter-spacing="-0.2">Zain<tspan fill="#EC4899">Cash</tspan></text>
                </svg>
            </div>
            <h3 style="font-size:20px; font-weight:800; color:#f472b6; margin-bottom:4px;">ZainCash (زين كاش)</h3>
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
                <div style="margin-top:4px;">
                    <a href="payment/fake.php?gateway=zaincash" target="_blank" style="font-size:12px; color:#f472b6; text-decoration:underline;">⚡ Open ZainCash Simulator (fake.php)</a>
                </div>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeZainModal()">Cancel</button>
                <button type="button" class="btn btn-primary btn-luxury" style="flex:2; background:#831843; border-color:#831843;" onclick="confirmZainCashPayment()">✓ Authorize ZainCash Payment</button>
            </div>
        </div>
    </div>
</div>

<script>
const EXCHANGE_RATE = <?php echo json_encode($rate ?? 1320); ?> || 1320;
let activePaymentGateway = 'fib';

document.addEventListener('DOMContentLoaded', () => {
    renderCheckoutCart();
});

function selectPaymentMethod(gateway) {
    activePaymentGateway = gateway;
}

function getDeliveryFee(gov) {
    return (gov === 'Duhok') ? 4000 : 5000;
}

function calculateCheckoutTotals() {
    const cart = window.AuraStore ? window.AuraStore.getCart() : [];
    let subtotal = 0;
    cart.forEach(item => {
        subtotal += (item.price * item.quantity);
    });
    const activeDiscountRate = parseFloat(sessionStorage.getItem('aura_discount_rate') || 0);
    const discount = Math.round(subtotal * activeDiscountRate);
    const gov = document.getElementById('coCustomerGovernorate')?.value || 'Duhok';
    const shippingFee = getDeliveryFee(gov);
    const finalTotal = Math.max(0, Math.round(subtotal - discount + shippingFee));

    return { subtotal, discount, activeDiscountRate, gov, shippingFee, finalTotal };
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
                <div class="co-price">${Math.round(itemTotal).toLocaleString()} IQD</div>
            </div>
        `;
    });

    container.innerHTML = html;

    const totals = calculateCheckoutTotals();

    document.getElementById('coSubtotal').innerText = Math.round(totals.subtotal).toLocaleString() + ' IQD';
    if (totals.activeDiscountRate > 0) {
        document.getElementById('coDiscountRow').style.display = 'flex';
        document.getElementById('coDiscount').innerText = '-' + totals.discount.toLocaleString() + ' IQD';
    } else {
        document.getElementById('coDiscountRow').style.display = 'none';
    }

    const coShipping = document.getElementById('coShipping');
    if (coShipping) {
        coShipping.innerText = totals.shippingFee.toLocaleString() + ' IQD';
    }

    const coShippingGovText = document.getElementById('coShippingGovText');
    const coShippingBadge = document.getElementById('coShippingBadge');
    if (coShippingGovText && coShippingBadge) {
        if (totals.gov === 'Duhok') {
            coShippingGovText.innerText = window.AURA_LANG === 'ku' ? 'دهۆک (٤,٠٠٠ دینار)' : (window.AURA_LANG === 'ar' ? 'دهوك (4,000 د.ع)' : 'Duhok (4,000 IQD)');
            coShippingBadge.innerText = window.AURA_LANG === 'ku' ? 'نرخێ دهۆکێ: ٤,٠٠٠' : (window.AURA_LANG === 'ar' ? 'سعر دهوك: 4,000' : 'Duhok: 4,000 IQD');
            coShippingBadge.style.color = '#22c55e';
            coShippingBadge.style.borderColor = '#22c55e';
            coShippingBadge.style.background = 'rgba(34, 197, 94, 0.12)';
        } else {
            const govName = totals.gov;
            coShippingGovText.innerText = window.AURA_LANG === 'ku' ? `${govName} (٥,٠٠٠ دینار)` : (window.AURA_LANG === 'ar' ? `${govName} (5,000 د.ع)` : `${govName} (5,000 IQD)`);
            coShippingBadge.innerText = window.AURA_LANG === 'ku' ? 'پارێزگەهێن دی: ٥,٠٠٠' : (window.AURA_LANG === 'ar' ? 'باقي المحافظات: 5,000' : 'Express: 5,000 IQD');
            coShippingBadge.style.color = 'var(--accent-gold)';
            coShippingBadge.style.borderColor = 'var(--accent-gold)';
            coShippingBadge.style.background = 'rgba(212, 175, 55, 0.12)';
        }
    }

    document.getElementById('coTotal').innerText = totals.finalTotal.toLocaleString() + ' IQD';

    document.getElementById('hiddenCartJson').value = JSON.stringify(cart);
    document.getElementById('hiddenDiscountRate').value = totals.activeDiscountRate.toString();
}

// IRAQ & KURDISTAN REGION GOVERNORATE TO CITY/DISTRICT MAPPING
const IRAQ_LOCATIONS = {
    'Duhok': [
        { en: 'Duhok City Center', ar: 'مركز مدينة دهوك', ku: 'ناڤەندا باژێرێ دهۆکێ' },
        { en: 'Zakho', ar: 'زاخو', ku: 'زاخۆ' },
        { en: 'Semel', ar: 'سميل', ku: 'سێمێل' },
        { en: 'Amedi (Amadiya)', ar: 'العمادية', ku: 'ئامێدیێ' },
        { en: 'Akre (Aqrah)', ar: 'عقرة', ku: 'ئاکرێ' },
        { en: 'Shekhan (Ain Sifni)', ar: 'الشيخان (عين سفني)', ku: 'شێخان' },
        { en: 'Bardarash', ar: 'بردرش', ku: 'بەردەڕەش' },
        { en: 'Deraluk & Shiladze', ar: 'ديرلوك وشيلادزي', ku: 'دێرەلووک و شێلادزێ' },
        { en: 'Batifa', ar: 'باتيفا', ku: 'باتێفا' },
        { en: 'Kani Masi', ar: 'كاني ماسي', ku: 'کانی ماسی' }
    ],
    'Erbil': [
        { en: 'Erbil City Center (100m / Bakhtiyari / Dream City)', ar: 'مركز أربيل (دريم سيتي / بختياري)', ku: 'ناڤەندا هەولێرێ (بەختیاری / دریم ستی)' },
        { en: 'Ankawa', ar: 'عنكاوا', ku: 'عەنکاوە' },
        { en: 'Soran', ar: 'سوران', ku: 'سۆران' },
        { en: 'Shaqlawa', ar: 'شقلاوة', ku: 'شەقڵاوە' },
        { en: 'Rawanduz', ar: 'رواندوز', ku: 'ڕەواندز' },
        { en: 'Choman', ar: 'جومان', ku: 'چۆمان' },
        { en: 'Koya', ar: 'كويه', ku: 'کۆیە' },
        { en: 'Khabat', ar: 'خبات', ku: 'خەبات' },
        { en: 'Mergasor', ar: 'ميركه سور', ku: 'مێرگەسۆر' },
        { en: 'Baharka', ar: 'بحركة', ku: 'بەحرکە' },
        { en: 'Kasnazan', ar: 'كسنزان', ku: 'کەسنەزان' },
        { en: 'Harir', ar: 'حرير', ku: 'هەریر' }
    ],
    'Sulaymaniyah': [
        { en: 'Sulaymaniyah City Center (Salim St / Sarchinar)', ar: 'مركز السليمانية (شارع سالم / سرجنار)', ku: 'ناڤەندا سلێمانیێ (شەقاما سالم / سەرچنار)' },
        { en: 'Bakrajo', ar: 'بكرجو', ku: 'باکراجۆ' },
        { en: 'Bazian', ar: 'بازيان', ku: 'بازیان' },
        { en: 'Chamchamal', ar: 'جمجمال', ku: 'چەمچەماڵ' },
        { en: 'Dokan', ar: 'دوكان', ku: 'دۆکان' },
        { en: 'Ranya', ar: 'رانية', ku: 'ڕانیە' },
        { en: 'Qaladiza', ar: 'قلعة دزة', ku: 'قەڵادزێ' },
        { en: 'Kalar (Garmian)', ar: 'كلار (كرميان)', ku: 'کەلار (گەرمیان)' },
        { en: 'Kifri', ar: 'كفري', ku: 'کفری' },
        { en: 'Penjwen', ar: 'بنجوين', ku: 'پێنجوێن' },
        { en: 'Said Sadiq', ar: 'سيد صادق', ku: 'سەید سادق' },
        { en: 'Darbandikhan', ar: 'دربندخان', ku: 'دەربەندیخان' },
        { en: 'Qaradagh', ar: 'قره داغ', ku: 'قەرەداغ' }
    ],
    'Halabja': [
        { en: 'Halabja City Center', ar: 'مركز مدينة حلبجة', ku: 'ناڤەندا باژێرێ هەڵەبجە' },
        { en: 'Sharazoor', ar: 'شهرزور', ku: 'شارەزوور' },
        { en: 'Khurmal', ar: 'خورمال', ku: 'خورماڵ' },
        { en: 'Byara', ar: 'بيارة', ku: 'بیارە' },
        { en: 'Sirwan', ar: 'سيروان', ku: 'سیروان' },
        { en: 'Tawella', ar: 'طويلة', ku: 'تەوێڵە' }
    ],
    'Baghdad': [
        { en: 'Karkh (Mansour / Yarmouk / Dawoodi)', ar: 'الكرخ (المنصور واليرموك والداودي)', ku: 'کەرخ (مەنسوور و یەرمووک)' },
        { en: 'Rusafa (Karada / Jadriya / Masbah)', ar: 'الرصافة (الكرادة والجادرية والمسبح)', ku: 'ڕەسافە (کەرادە و جادریە)' },
        { en: 'Zayouna & Palestine Street', ar: 'زيونة وشارع فلسطين', ku: 'زەیونە و شەقاما فەلەستین' },
        { en: 'Adhamiya & Maghrib', ar: 'الأعظمية والمغرب', ku: 'ئەعزەمیە و مەغریب' },
        { en: 'Kadhimiya & Hurriya', ar: 'الكاظمية والحرية', ku: 'کازمیە و حوریە' },
        { en: 'Ghazaliya & University District', ar: 'الغزالية وحي الجامعة', ku: 'غەزالیە و حەی جامعە' },
        { en: 'Doura & Saydiyah', ar: 'الدورة والسيدية', ku: 'دورە و سەیدیە' },
        { en: 'Bayaa & Amil', ar: 'البياع والعامل', ku: 'بەییاع و عامیل' },
        { en: 'New Baghdad & Mashtal', ar: 'بغداد الجديدة والمشتل', ku: 'بەغدایا نوی و مەشتەل' },
        { en: 'Sadr City & Sha\'ab', ar: 'مدينة الصدر والشعب', ku: 'باژێرێ سەدر و شەعب' },
        { en: 'Mahmoudiyah', ar: 'المحمودية', ku: 'مەحمودیە' },
        { en: 'Abu Ghraib', ar: 'أبو غريب', ku: 'ئەبوو غرێب' },
        { en: 'Taji', ar: 'التاجي', ku: 'تاجی' }
    ],
    'Basra': [
        { en: 'Basra Center (Ashar / Bradheya / Manawi Pasha)', ar: 'مركز البصرة (العشار / البراضعية / مناوي باشا)', ku: 'ناڤەندا بەسرە (عەشار / برازعیە)' },
        { en: 'Zubair', ar: 'الزبير', ku: 'زوبێر' },
        { en: 'Abu Al-Khaseeb', ar: 'أبو الخصيب', ku: 'ئەبولخەسیب' },
        { en: 'Qurna', ar: 'القرنة', ku: 'قورنە' },
        { en: 'Shatt Al-Arab (Tannumah)', ar: 'شط العرب (التنومة)', ku: 'شەتولعەرەب (تەنۆمە)' },
        { en: 'Fao', ar: 'الفاو', ku: 'فاو' },
        { en: 'Umm Qasr', ar: 'أم قصر', ku: 'ئوم قەسر' },
        { en: 'Al-Midaina', ar: 'المدينة', ku: 'مەدینە' },
        { en: 'Hartha', ar: 'الهارتة', ku: 'هارتە' }
    ],
    'Mosul': [
        { en: 'Mosul Left Coast (East Mosul - Zuhur / Masarif)', ar: 'الموصل - الساحل الأيسر (الزهور، المصارف)', ku: 'مووسڵ - لایێ چەپێ (زەهوور، مەسارف)' },
        { en: 'Mosul Right Coast (West Mosul)', ar: 'الموصل - الساحل الأيمن', ku: 'مووسڵ - لایێ راستێ' },
        { en: 'Tel Keppe', ar: 'تلكيف', ku: 'تلکێف' },
        { en: 'Hamdaniya / Qaraqosh (Bakhdida)', ar: 'الحمدانية (بغديدا / قره قوش)', ku: 'حەمدانیە (بەغدیدا)' },
        { en: 'Sinjar (Shingal)', ar: 'سنجار (شنكال)', ku: 'شنگال' },
        { en: 'Tal Afar', ar: 'تلعفر', ku: 'تەلەعفەر' },
        { en: 'Bartella', ar: 'برطلة', ku: 'بەرتلە' },
        { en: 'Bashiqa', ar: 'بعشيقة', ku: 'بەعشیقە' },
        { en: 'Shekhan', ar: 'الشيخان', ku: 'شێخان' },
        { en: 'Makhmur', ar: 'مخمور', ku: 'مەخموور' },
        { en: 'Al-Ba\'aj', ar: 'البعاج', ku: 'بەعاج' }
    ],
    'Kirkuk': [
        { en: 'Kirkuk Center (Rahimawa / Shorija / Baghdad Rd)', ar: 'مركز كركوك (رحيماوا / الشورجة / طريق بغداد)', ku: 'ناڤەندا کەرکووک (ڕەحیماوا / شۆڕیجە)' },
        { en: 'Dubiz', ar: 'الدبس', ku: 'دوبز' },
        { en: 'Daquq', ar: 'داقوق', ku: 'داقووق' },
        { en: 'Hawija', ar: 'الحويجة', ku: 'حەویجە' },
        { en: 'Tazakhurmatu', ar: 'تازة خورماتو', ku: 'تازەخورماتوو' },
        { en: 'Laylan', ar: 'ليلان', ku: 'لەیلان' }
    ],
    'Najaf': [
        { en: 'Najaf Center (Al-Adala / Al-Ghadir / Al-Askari)', ar: 'مركز النجف (العدالة والغدير والإسكان)', ku: 'ناڤەندا باژێرێ نەجەفێ' },
        { en: 'Kufa', ar: 'الكوفة', ku: 'کووفە' },
        { en: 'Manathera', ar: 'المناذرة', ku: 'مەنازیرە' },
        { en: 'Mishkhab', ar: 'المشخاب', ku: 'مشخاب' },
        { en: 'Abbasiya', ar: 'العباسية', ku: 'عەباسیە' },
        { en: 'Haidariya', ar: 'الحيدرية', ku: 'حەیدەریە' }
    ],
    'Karbala': [
        { en: 'Karbala Center (Al-Baladiyah / Al-Iskan)', ar: 'مركز كربلاء (البلدية والإسكان والحر)', ku: 'ناڤەندا باژێرێ کەربەلا' },
        { en: 'Hindiya (Tuwaireej)', ar: 'الهندية (طويريج)', ku: 'هیندیە (طویریج)' },
        { en: 'Ain Al-Tamur', ar: 'عين التمر', ku: 'عەین تەمر' },
        { en: 'Husseiniya', ar: 'الحسينية', ku: 'حوسێنیە' },
        { en: 'Hurr', ar: 'الحر', ku: 'حور' }
    ],
    'Anbar': [
        { en: 'Ramadi', ar: 'الرمادي', ku: 'ڕەمادی' },
        { en: 'Fallujah', ar: 'الفلوجة', ku: 'فەلووجە' },
        { en: 'Hit', ar: 'هيت', ku: 'هیت' },
        { en: 'Haditha', ar: 'حديثة', ku: 'حەدیسە' },
        { en: 'Al-Qaim', ar: 'القائم', ku: 'قائیم' },
        { en: 'Rutba', ar: 'الرطبة', ku: 'ڕوتبە' },
        { en: 'Garma', ar: 'الكرمة', ku: 'کەرمە' },
        { en: 'Anah & Rawa', ar: 'عنه وراوه', ku: 'عانە و ڕاوە' },
        { en: 'Saqlawiyah', ar: 'الصقلاوية', ku: 'سەقلاویە' }
    ],
    'Babil': [
        { en: 'Hillah (City Center)', ar: 'مركز الحلة', ku: 'ناڤەندا باژێرێ حلە' },
        { en: 'Musayyib', ar: 'المسيب', ku: 'موسەیب' },
        { en: 'Iskandariya', ar: 'الإسكندرية', ku: 'ئەسکەندەریە' },
        { en: 'Mahawil', ar: 'المحاويل', ku: 'مەحاویل' },
        { en: 'Hashimiya', ar: 'الهاشمية', ku: 'هاشمیە' },
        { en: 'Qasim', ar: 'القاسم', ku: 'قاسم' },
        { en: 'Saddat Al-Hindiyah', ar: 'سدة الهندية', ku: 'سەدەیا هیندی' }
    ],
    'Diyala': [
        { en: 'Baqubah (City Center)', ar: 'مركز بعقوبة', ku: 'ناڤەندا بەعقوبە' },
        { en: 'Khanaqin', ar: 'خانقين', ku: 'خانەقین' },
        { en: 'Muqdadiya', ar: 'المقدادية', ku: 'مەقدادیە' },
        { en: 'Khalis', ar: 'الخالص', ku: 'خالس' },
        { en: 'Balad Ruz', ar: 'بلدروز', ku: 'بەلەدرۆز' },
        { en: 'Jalawla', ar: 'جلولاء', ku: 'جەلەولا' },
        { en: 'Mandali', ar: 'مندلي', ku: 'مەندەلی' }
    ],
    'Wasit': [
        { en: 'Kut (City Center)', ar: 'مركز الكوت', ku: 'ناڤەندا کووتێ' },
        { en: 'Numaniyah', ar: 'النعمانية', ku: 'نوعمانیە' },
        { en: 'Suwaira', ar: 'الصويرة', ku: 'سوێرە' },
        { en: 'Hai', ar: 'الحي', ku: 'حەی' },
        { en: 'Aziziyah', ar: 'العزيزية', ku: 'عەزیزیە' },
        { en: 'Badra & Jassan', ar: 'بدرة وجصان', ku: 'بەدرە و جەسان' }
    ],
    'Maysan': [
        { en: 'Amarah (City Center)', ar: 'مركز العمارة', ku: 'ناڤەندا عەمارە' },
        { en: 'Ali Al-Gharbi', ar: 'علي الغربي', ku: 'عەلی غەربی' },
        { en: 'Majar Al-Kabir', ar: 'المجر الكبير', ku: 'مەجەر کەبیر' },
        { en: 'Qal\'at Saleh', ar: 'قلعة صالح', ku: 'قەڵای ساڵح' },
        { en: 'Kahla', ar: 'الكحلاء', ku: 'کەحلا' },
        { en: 'Maimouna', ar: 'الميمونة', ku: 'مەیموونا' }
    ],
    'DhiQar': [
        { en: 'Nasiriyah (City Center)', ar: 'مركز الناصرية', ku: 'ناڤەندا ناصریە' },
        { en: 'Shatrah', ar: 'الشطرة', ku: 'شەترە' },
        { en: 'Rifa\'i', ar: 'الرفاعي', ku: 'ڕیفاعی' },
        { en: 'Suq Al-Shuyukh', ar: 'سوق الشيوخ', ku: 'سۆقولشویووخ' },
        { en: 'Chibayish (Marshlands)', ar: 'الجبايش (الأهوار)', ku: 'چبایش (زەلکاو)' },
        { en: 'Qal\'at Sukkar', ar: 'قلعة سكر', ku: 'قەڵای سوکەر' }
    ],
    'Muthanna': [
        { en: 'Samawah (City Center)', ar: 'مركز السماوة', ku: 'ناڤەندا سەماوە' },
        { en: 'Rumaitha', ar: 'الرميثة', ku: 'ڕومەیسە' },
        { en: 'Khidhir', ar: 'الخضر', ku: 'خزر' },
        { en: 'Salman', ar: 'السلمان', ku: 'سەلمان' },
        { en: 'Warkaa', ar: 'الوركاء', ku: 'وەرکا' }
    ],
    'Qadisiyyah': [
        { en: 'Diwaniyah (City Center)', ar: 'مركز الديوانية', ku: 'ناڤەندا دیوانیە' },
        { en: 'Shamiya', ar: 'الشامية', ku: 'شامیە' },
        { en: 'Afak', ar: 'عفك', ku: 'عەفەک' },
        { en: 'Hamzah', ar: 'الحمزة', ku: 'حەمزە' },
        { en: 'Ghammas', ar: 'غماس', ku: 'غەماس' }
    ],
    'Saladin': [
        { en: 'Tikrit', ar: 'تكريت', ku: 'تکریت' },
        { en: 'Samarra', ar: 'سامراء', ku: 'سامەڕا' },
        { en: 'Balad', ar: 'بلد', ku: 'بەلەد' },
        { en: 'Dujail', ar: 'الدجيل', ku: 'دوجەیل' },
        { en: 'Baiji', ar: 'بيجي', ku: 'بێجی' },
        { en: 'Tooz Khurmatoo', ar: 'طوزخورماتو', ku: 'تووزخورماتوو' },
        { en: 'Shirqat', ar: 'الشرقاط', ku: 'شەرگات' },
        { en: 'Ishaqi', ar: 'الإسحاقي', ku: 'ئیسحاقی' }
    ]
};

function onGovernorateSelectChange(govKey) {
    const citySelect = document.getElementById('coCustomerCity');
    if (!citySelect) return;

    const cities = IRAQ_LOCATIONS[govKey] || [];
    const currentLang = window.AURA_LANG || 'en';

    let optionsHtml = '';
    cities.forEach((c, idx) => {
        let label = '';
        if (currentLang === 'ku') {
            label = c.ku + (c.en !== c.ku ? ' / ' + c.en : '');
        } else if (currentLang === 'ar') {
            label = c.ar + (c.en !== c.ar ? ' / ' + c.en : '');
        } else {
            label = c.en + (c.ku ? ' / ' + c.ku : (c.ar ? ' / ' + c.ar : ''));
        }
        const isSelected = idx === 0 ? 'selected' : '';
        optionsHtml += `<option value="${c.en}" ${isSelected}>${label}</option>`;
    });

    citySelect.innerHTML = optionsHtml;
    citySelect.style.transition = 'border-color 0.3s ease';
    citySelect.style.borderColor = 'var(--accent-gold, #d4af37)';
    setTimeout(() => { citySelect.style.borderColor = ''; }, 600);

    // Update totals and delivery fee dynamically
    renderCheckoutCart();
}

function highlightPaymentOption(radio) {
    document.querySelectorAll('.payment-option-label').forEach(el => el.classList.remove('active'));
    radio.closest('.payment-option-label').classList.add('active');
}

function checkPhoneMatch() {
    const p1 = document.getElementById('coCustomerPhone');
    const p2 = document.getElementById('coCustomerPhoneConfirm');
    const notice = document.getElementById('phoneMatchNotice');
    if (!p1 || !p2 || !notice) return true;

    const v1 = p1.value.trim();
    const v2 = p2.value.trim();
    const c1 = v1.replace(/[^\d+]/g, '');
    const c2 = v2.replace(/[^\d+]/g, '');

    if (!v2) {
        notice.style.display = 'none';
        p2.style.borderColor = '';
        return false;
    }

    if (c1.length > 0 && c2.length > 0 && c1 === c2) {
        notice.style.display = 'block';
        notice.style.color = '#22c55e';
        notice.innerHTML = '✓ ' + (window.AURA_LANG === 'ku' ? 'ژمارا تەلەفۆنێ یا دروستە و وەک ئێکە' : (window.AURA_LANG === 'ar' ? 'تم التحقق من تطابق رقم الهاتف' : 'Phone numbers verified'));
        p2.style.borderColor = '#22c55e';
        return true;
    } else {
        notice.style.display = 'block';
        notice.style.color = '#ef4444';
        notice.innerHTML = '⚠️ ' + (window.AURA_LANG === 'ku' ? 'ژمارێن تەلەفۆنێ وەک ئێک نینن، تکایە پشتڕاست بکەڤە.' : (window.AURA_LANG === 'ar' ? 'رقما الهاتف غير متطابقين، يرجى التأكد من كتابة نفس الرقم.' : 'Phone numbers do not match. Please verify both numbers.'));
        p2.style.borderColor = '#ef4444';
        return false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const p1 = document.getElementById('coCustomerPhone');
    const p2 = document.getElementById('coCustomerPhoneConfirm');
    if (p1 && p2) {
        p1.addEventListener('input', () => { if (p2.value) checkPhoneMatch(); });
        p2.addEventListener('input', () => checkPhoneMatch());
        p2.addEventListener('blur', () => checkPhoneMatch());
    }
});

function validateAndSubmitCheckout(event) {
    const cart = window.AuraStore ? window.AuraStore.getCart() : [];
    if (!cart || cart.length === 0) {
        alert(window.AURA_LANG === 'ku' ? 'سەبەتە یا ڤالایە' : 'Your shopping cart is empty.');
        event.preventDefault();
        return false;
    }

    // Phone Number 2-Times Verification Validation
    const p1 = document.getElementById('coCustomerPhone');
    const p2 = document.getElementById('coCustomerPhoneConfirm');
    const v1 = p1 ? p1.value.trim() : '';
    const v2 = p2 ? p2.value.trim() : '';
    const c1 = v1.replace(/[^\d+]/g, '');
    const c2 = v2.replace(/[^\d+]/g, '');

    if (!v1 || !v2 || c1 !== c2) {
        event.preventDefault();
        const errorMsg = window.AURA_LANG === 'ku' ? 'ژمارێن تەلەفۆنێ وەک ئێک نینن، تکایە هەردوو ژماران پشتڕاست بکەڤە.' : 
                         (window.AURA_LANG === 'ar' ? 'رقما الهاتف غير متطابقين، يرجى التأكد من كتابة نفس الرقم في الحقلين.' : 
                         'Phone numbers do not match. Please re-enter phone number twice to verify.');
        if (window.AuraStore) {
            window.AuraStore.showToast(errorMsg, 'error');
        } else {
            alert(errorMsg);
        }
        if (p2) {
            p2.focus();
            checkPhoneMatch();
        }
        return false;
    }

    const gov = document.getElementById('coCustomerGovernorate')?.value;
    const city = document.getElementById('coCustomerCity')?.value;
    if (!gov || !city) {
        event.preventDefault();
        const errorMsg = window.AURA_LANG === 'ku' ? 'تکایە پارێزگەهـ و باژێر/قەزایێ دیار بکە.' : 
                         (window.AURA_LANG === 'ar' ? 'يرجى اختيار المحافظة والمدينة/القضاء.' : 
                         'Please select both your Governorate and City/District.');
        if (window.AuraStore) {
            window.AuraStore.showToast(errorMsg, 'error');
        } else {
            alert(errorMsg);
        }
        return false;
    }

    document.getElementById('hiddenCartJson').value = JSON.stringify(cart);

    const selMethod = document.querySelector('input[name="payment_method"]:checked')?.value || '';

    // 1. If FIB is selected and not already confirmed, trigger FIB modal
    if (selMethod.includes('FIB') && !window._fibPaidConfirmed) {
        event.preventDefault();
        showFibModal();
        return false;
    }

    // 2. If FastPay is selected and not already confirmed, trigger FastPay modal
    if (selMethod.includes('FastPay') && !window._fastpayPaidConfirmed) {
        event.preventDefault();
        showFastPayModal();
        return false;
    }

    // 3. If ZainCash is selected and not already confirmed, trigger ZainCash OTP modal
    if (selMethod.includes('ZainCash') && !window._zainPaidConfirmed) {
        event.preventDefault();
        showZainModal();
        return false;
    }

    return true;
}

function showFibModal() {
    const totals = calculateCheckoutTotals();
    const finalTotal = totals.finalTotal;

    document.getElementById('fibAmountDisplay').innerText = finalTotal.toLocaleString() + ' IQD';
    document.getElementById('fibCodeDisplay').innerText = 'FIB-' + Math.floor(10000 + Math.random() * 90000);

    // Render realistic SVG QR code pattern
    document.getElementById('fibQrContainer').innerHTML = `
        <svg viewBox="0 0 160 160" width="160" height="160" style="display:block; margin:0 auto;">
            <rect width="160" height="160" fill="#ffffff" rx="8" />
            <!-- Outer Markers -->
            <rect x="10" y="10" width="40" height="40" fill="#0c0e14" rx="4" />
            <rect x="16" y="16" width="28" height="28" fill="#ffffff" />
            <rect x="22" y="22" width="16" height="16" fill="#d4af37" />

            <rect x="110" y="10" width="40" height="40" fill="#0c0e14" rx="4" />
            <rect x="116" y="16" width="28" height="28" fill="#ffffff" />
            <rect x="122" y="22" width="16" height="16" fill="#d4af37" />

            <rect x="10" y="110" width="40" height="40" fill="#0c0e14" rx="4" />
            <rect x="16" y="116" width="28" height="28" fill="#ffffff" />
            <rect x="22" y="122" width="16" height="16" fill="#d4af37" />

            <!-- Dynamic grid dots -->
            <rect x="60" y="20" width="10" height="10" fill="#0c0e14" />
            <rect x="80" y="20" width="10" height="20" fill="#0c0e14" />
            <rect x="60" y="40" width="20" height="10" fill="#d4af37" />
            <rect x="60" y="60" width="40" height="40" fill="#0c0e14" rx="2" />
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

function showFastPayModal() {
    const totals = calculateCheckoutTotals();
    const finalTotal = totals.finalTotal;

    document.getElementById('fpAmountDisplay').innerText = finalTotal.toLocaleString() + ' IQD';

    // Render FastPay QR code
    document.getElementById('fastpayQrContainer').innerHTML = `
        <svg viewBox="0 0 160 160" width="160" height="160" style="display:block; margin:0 auto;">
            <rect width="160" height="160" fill="#ffffff" rx="8" />
            <!-- Markers -->
            <rect x="10" y="10" width="40" height="40" fill="#111827" rx="4" />
            <rect x="16" y="16" width="28" height="28" fill="#ffffff" />
            <rect x="22" y="22" width="16" height="16" fill="#FFC800" />

            <rect x="110" y="10" width="40" height="40" fill="#111827" rx="4" />
            <rect x="116" y="16" width="28" height="28" fill="#ffffff" />
            <rect x="122" y="22" width="16" height="16" fill="#FFC800" />

            <rect x="10" y="110" width="40" height="40" fill="#111827" rx="4" />
            <rect x="16" y="116" width="28" height="28" fill="#ffffff" />
            <rect x="22" y="122" width="16" height="16" fill="#FFC800" />

            <!-- Pattern -->
            <rect x="60" y="15" width="15" height="15" fill="#111827" />
            <rect x="85" y="15" width="15" height="25" fill="#111827" />
            <rect x="60" y="45" width="25" height="15" fill="#FFC800" />
            <circle cx="80" cy="80" r="16" fill="#FFC800" />
            <path d="M76 74 L81 80 L76 86" stroke="#111827" stroke-width="2.5" stroke-linecap="round" fill="none"/>
            <path d="M81 74 L86 80 L81 86" stroke="#111827" stroke-width="2.5" stroke-linecap="round" fill="none"/>
            <rect x="115" y="60" width="25" height="15" fill="#111827" />
            <rect x="20" y="65" width="20" height="15" fill="#111827" />
            <rect x="20" y="90" width="15" height="10" fill="#FFC800" />
            <rect x="60" y="110" width="25" height="20" fill="#111827" />
            <rect x="95" y="110" width="15" height="35" fill="#111827" />
            <rect x="120" y="120" width="25" height="15" fill="#FFC800" />
        </svg>
    `;

    document.getElementById('fastpayPaymentModalOverlay').classList.add('open');
}

function closeFastPayModal() {
    document.getElementById('fastpayPaymentModalOverlay').classList.remove('open');
}

function confirmFastPayPayment() {
    window._fastpayPaidConfirmed = true;
    closeFastPayModal();
    if (window.AuraStore) {
        window.AuraStore.showToast('✓ FastPay Payment authorized successfully! Submitting order...', 'success');
    }
    setTimeout(() => {
        document.getElementById('checkoutForm').submit();
    }, 600);
}

function showZainModal() {
    const totals = calculateCheckoutTotals();
    const finalTotal = totals.finalTotal;

    document.getElementById('zcAmountDisplay').innerText = finalTotal.toLocaleString() + ' IQD';
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
