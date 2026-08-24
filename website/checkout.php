<?php
$activePage = 'shop';
$pageTitle = 'Secure Checkout';
require_once __DIR__ . '/header.php';

$orderPlaced = false;
$confirmedOrder = null;

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

        $orderPayload = [
            'order_id' => 'ORD-' . rand(10000, 99999),
            'customer_name' => htmlspecialchars($name),
            'email' => htmlspecialchars($email),
            'phone' => htmlspecialchars($phone),
            'city' => htmlspecialchars($city),
            'address' => htmlspecialchars($address),
            'payment_method' => htmlspecialchars($paymentMethod),
            'payment_status' => $paymentMethod === 'Cash on Delivery' ? 'Pending' : 'Paid',
            'order_status' => 'Processing',
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'shipping' => 0.00,
            'discount' => $discount,
            'total' => $total,
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
            <span class="section-kicker">Aura Secure Gateway</span>
            <h1 class="page-banner-title"><?php echo t('checkout_title', $lang); ?></h1>
        </div>
    </div>
</div>

<section class="checkout-section">
    <div class="container">
        
        <?php if ($orderPlaced && $confirmedOrder): ?>
            <!-- Order Success Confirmation Screen -->
            <div class="order-success-card">
                <div class="success-icon-wrap">✓</div>
                <h2 class="success-title"><?php echo t('order_confirmed', $lang); ?></h2>
                <p class="success-sub"><?php echo t('order_tracking_msg', $lang); ?></p>
                
                <div class="order-ref-badge">
                    <span><?php echo t('order_number', $lang); ?>:</span>
                    <strong><?php echo $confirmedOrder['order_id']; ?></strong>
                </div>

                <div class="order-receipt-summary">
                    <div class="receipt-row">
                        <span>Recipient:</span>
                        <strong><?php echo htmlspecialchars($confirmedOrder['customer_name']); ?> (<?php echo htmlspecialchars($confirmedOrder['phone']); ?>)</strong>
                    </div>
                    <div class="receipt-row">
                        <span>Destination:</span>
                        <strong><?php echo htmlspecialchars($confirmedOrder['city']); ?>, <?php echo htmlspecialchars($confirmedOrder['address']); ?></strong>
                    </div>
                    <div class="receipt-row">
                        <span>Payment Method:</span>
                        <strong><?php echo htmlspecialchars($confirmedOrder['payment_method']); ?> (<?php echo htmlspecialchars($confirmedOrder['payment_status']); ?>)</strong>
                    </div>
                    <div class="receipt-row">
                        <span>Total Paid/Due:</span>
                        <strong class="text-primary text-xl">$<?php echo number_format($confirmedOrder['total'], 2); ?></strong>
                    </div>
                </div>

                <div class="success-actions-row">
                    <a href="track.php?order_id=<?php echo urlencode($confirmedOrder['order_id']); ?>" class="btn btn-primary btn-luxury">
                        🔍 <?php echo t('nav_track', $lang); ?>
                    </a>
                    <a href="shop.php" class="btn btn-secondary">
                        ← <?php echo t('cart_continue', $lang); ?>
                    </a>
                </div>
            </div>

            <script>
                // Clear cart from local storage after successful checkout
                window.AuraStore.clearCart();
            </script>

        <?php else: ?>

            <!-- Checkout Form & Order Summary Grid -->
            <form action="checkout.php" method="POST" id="checkoutForm" class="checkout-grid" onsubmit="return validateAndSubmitCheckout(event)">
                
                <!-- Hidden inputs to pass cart JSON and discount -->
                <input type="hidden" name="cart_items_json" id="hiddenCartJson" value="[]">
                <input type="hidden" name="applied_discount_rate" id="hiddenDiscountRate" value="0">
                <input type="hidden" name="place_order" value="1">

                <!-- Left Column: Shipping & Payment Form -->
                <div class="checkout-form-column">
                    
                    <div class="checkout-card">
                        <h3 class="checkout-step-title">
                            <span class="step-num">1</span>
                            <span><?php echo t('checkout_customer_info', $lang); ?></span>
                        </h3>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label><?php echo t('checkout_name', $lang); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" required class="form-control" placeholder="e.g. Alind Duhoki">
                            </div>

                            <div class="form-group">
                                <label><?php echo t('checkout_phone', $lang); ?> <span class="text-danger">*</span></label>
                                <input type="tel" name="customer_phone" required class="form-control" placeholder="0750 123 4567">
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label><?php echo t('checkout_email', $lang); ?></label>
                                <input type="email" name="customer_email" class="form-control" placeholder="client@example.com">
                            </div>

                            <div class="form-group">
                                <label><?php echo t('checkout_city', $lang); ?> <span class="text-danger">*</span></label>
                                <select name="customer_city" required class="form-control">
                                    <option value="Duhok"><?php echo t('checkout_city_duhok', $lang); ?></option>
                                    <option value="Erbil"><?php echo t('checkout_city_erbil', $lang); ?></option>
                                    <option value="Sulaymaniyah"><?php echo t('checkout_city_sulaymaniyah', $lang); ?></option>
                                    <option value="Baghdad"><?php echo t('checkout_city_baghdad', $lang); ?></option>
                                    <option value="Basra"><?php echo t('checkout_city_basra', $lang); ?></option>
                                    <option value="International"><?php echo t('checkout_city_other', $lang); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><?php echo t('checkout_address', $lang); ?> <span class="text-danger">*</span></label>
                            <textarea name="customer_address" rows="3" required class="form-control" placeholder="Neighborhood, Street, Building / Villa number"></textarea>
                        </div>
                    </div>

                    <!-- Payment Method Card -->
                    <div class="checkout-card mt-24">
                        <h3 class="checkout-step-title">
                            <span class="step-num">2</span>
                            <span><?php echo t('checkout_payment_method', $lang); ?></span>
                        </h3>

                        <div class="payment-options-list">
                            <label class="payment-option-label active">
                                <input type="radio" name="payment_method" value="Cash on Delivery" checked onchange="highlightPaymentOption(this)">
                                <div class="payment-option-content">
                                    <div class="pay-title-row">
                                        <span class="pay-icon">💵</span>
                                        <strong><?php echo t('payment_cod', $lang); ?></strong>
                                    </div>
                                    <p class="pay-desc">Pay cash securely upon home delivery inspection.</p>
                                </div>
                            </label>

                            <label class="payment-option-label">
                                <input type="radio" name="payment_method" value="FastPay / ZainCash" onchange="highlightPaymentOption(this)">
                                <div class="payment-option-content">
                                    <div class="pay-title-row">
                                        <span class="pay-icon">📱</span>
                                        <strong><?php echo t('payment_fastpay', $lang); ?></strong>
                                    </div>
                                    <p class="pay-desc">Direct mobile wallet transfer with instant confirmation.</p>
                                </div>
                            </label>

                            <label class="payment-option-label">
                                <input type="radio" name="payment_method" value="Credit / Debit Card" onchange="highlightPaymentOption(this)">
                                <div class="payment-option-content">
                                    <div class="pay-title-row">
                                        <span class="pay-icon">💳</span>
                                        <strong><?php echo t('payment_card', $lang); ?></strong>
                                    </div>
                                    <p class="pay-desc">Visa, MasterCard, or Bank Transfer (Encrypted).</p>
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
                            <span id="coSubtotal" class="font-bold">$0.00</span>
                        </div>

                        <div class="summary-row" id="coDiscountRow" style="display:none;">
                            <span><?php echo t('cart_discount', $lang); ?></span>
                            <span id="coDiscount" class="text-success font-bold">-$0.00</span>
                        </div>

                        <div class="summary-row">
                            <span><?php echo t('cart_shipping', $lang); ?></span>
                            <span class="text-success font-bold"><?php echo t('cart_free_shipping', $lang); ?></span>
                        </div>

                        <div class="summary-divider"></div>

                        <div class="summary-row total-row">
                            <span><?php echo t('cart_total', $lang); ?></span>
                            <span id="coTotal" class="total-price-val">$0.00</span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-luxury w-full btn-lg mt-24">
                            <span><?php echo t('place_order', $lang); ?></span>
                            <span>⚡</span>
                        </button>
                    </div>
                </div>

            </form>

        <?php endif; ?>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    renderCheckoutReview();
});

function renderCheckoutReview() {
    const cart = window.AuraStore.getCart();
    const container = document.getElementById('checkoutItemsList');
    if (!container) return;

    if (!cart || cart.length === 0) {
        container.innerHTML = '<p class="text-muted">Your cart is empty. <a href="shop.php">Browse collection</a></p>';
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

    // Apply discount rate
    const activeDiscountRate = parseFloat(sessionStorage.getItem('aura_discount_rate') || 0);
    const discount = subtotal * activeDiscountRate;
    const finalTotal = Math.max(0, subtotal - discount);

    document.getElementById('coSubtotal').innerText = '$' + subtotal.toFixed(2);
    if (activeDiscountRate > 0) {
        document.getElementById('coDiscountRow').style.display = 'flex';
        document.getElementById('coDiscount').innerText = '-$' + discount.toFixed(2);
    }
    document.getElementById('coTotal').innerText = '$' + finalTotal.toFixed(2);

    // Sync to hidden input
    document.getElementById('hiddenCartJson').value = JSON.stringify(cart);
    document.getElementById('hiddenDiscountRate').value = activeDiscountRate.toString();
}

function highlightPaymentOption(radio) {
    document.querySelectorAll('.payment-option-label').forEach(el => el.classList.remove('active'));
    radio.closest('.payment-option-label').classList.add('active');
}

function validateAndSubmitCheckout(event) {
    const cart = window.AuraStore.getCart();
    if (!cart || cart.length === 0) {
        alert('Your shopping cart is empty.');
        event.preventDefault();
        return false;
    }
    document.getElementById('hiddenCartJson').value = JSON.stringify(cart);
    return true;
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
