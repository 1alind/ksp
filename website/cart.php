<?php
$activePage = 'cart';
$pageTitle = 'Shopping Bag';
require_once __DIR__ . '/header.php';
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">Aura Concierge</span>
            <h1 class="page-banner-title"><?php echo t('cart', $lang); ?></h1>
        </div>
    </div>
</div>

<section class="cart-section">
    <div class="container">
        
        <!-- Cart Contents Container (populated dynamically via JS & preserved in localStorage) -->
        <div id="cartAppContainer" class="cart-layout-grid">
            <!-- Left Column: Items Table -->
            <div class="cart-items-column">
                <div class="cart-table-wrap">
                    <table class="cart-table" id="cartTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cartTableBody">
                            <!-- Populated via script.js -->
                        </tbody>
                    </table>
                </div>

                <div class="cart-table-footer">
                    <a href="shop.php" class="btn btn-outline">← <?php echo t('cart_continue', $lang); ?></a>
                    <button class="btn btn-ghost text-danger" onclick="window.AuraStore.clearCart()"><?php echo $lang === 'ku' ? 'ڤالاکرنا سەبەتێ' : ($lang === 'ar' ? 'تفريغ السلة' : 'Clear Cart'); ?></button>
                </div>
            </div>

            <!-- Right Column: Order Summary & Coupon -->
            <div class="cart-summary-column">
                <div class="summary-card">
                    <h3 class="summary-title"><?php echo $lang === 'ku' ? 'پوختەیا داخازیێ' : ($lang === 'ar' ? 'ملخص الطلب' : 'Order Summary'); ?></h3>
                    
                    <div class="summary-row">
                        <span><?php echo t('cart_subtotal', $lang); ?></span>
                        <span id="summarySubtotal" class="font-bold">$0.00</span>
                    </div>

                    <div class="summary-row" id="discountRow" style="display: none;">
                        <span><?php echo t('cart_discount', $lang); ?> <small id="discountBadge" class="text-success"></small></span>
                        <span id="summaryDiscount" class="text-success font-bold">-$0.00</span>
                    </div>

                    <div class="summary-row">
                        <span><?php echo t('cart_shipping', $lang); ?></span>
                        <span class="text-success font-bold"><?php echo t('cart_free_shipping', $lang); ?></span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row total-row">
                        <span><?php echo t('cart_total', $lang); ?></span>
                        <span id="summaryTotal" class="total-price-val">$0.00</span>
                    </div>

                    <!-- Coupon Code Input -->
                    <div class="coupon-box">
                        <label class="coupon-label"><?php echo t('cart_coupon', $lang); ?> (Try: <code>AURA10</code> or <code>LUXURY20</code>)</label>
                        <div class="coupon-input-group">
                            <input type="text" id="couponInput" placeholder="AURA10" class="form-control text-uppercase">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="applyCoupon()"><?php echo t('cart_apply', $lang); ?></button>
                        </div>
                        <div id="couponFeedback" class="coupon-msg"></div>
                    </div>

                    <a href="checkout.php" class="btn btn-primary btn-luxury w-full btn-lg mt-20">
                        <span><?php echo t('cart_checkout', $lang); ?></span>
                        <span>→</span>
                    </a>

                    <div class="security-assurance mt-20 text-center">
                        <small class="text-muted">🔒 256-Bit Encrypted & Guaranteed Safe Checkout</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty Cart Display -->
        <div id="emptyCartView" class="empty-cart-state" style="display: none;">
            <div class="empty-cart-icon">🛍️</div>
            <h2><?php echo t('cart_empty', $lang); ?></h2>
            <p><?php echo $lang === 'ku' ? 'تە چ بەرهەم زێدە نەکراینە، سەرەدانا کۆگەهێ بکە بۆ دیتنا فەخامەتترین بەرهەمان.' : ($lang === 'ar' ? 'لم تقم بإضافة أي منتجات بعد. استكشف مجموعتنا الفاخرة واختر ما يناسبك.' : 'Discover our exquisite collection of apparel, Swiss timepieces, and artisan fragrances.'); ?></p>
            <a href="shop.php" class="btn btn-primary btn-luxury btn-lg mt-24">
                <span><?php echo t('hero_shop_now', $lang); ?></span>
                <span>→</span>
            </a>
        </div>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    renderCartPage();
});

function renderCartPage() {
    const cart = window.AuraStore.getCart();
    const container = document.getElementById('cartAppContainer');
    const emptyView = document.getElementById('emptyCartView');
    const tbody = document.getElementById('cartTableBody');

    if (!cart || cart.length === 0) {
        if (container) container.style.display = 'none';
        if (emptyView) emptyView.style.display = 'block';
        return;
    }

    if (container) container.style.display = 'grid';
    if (emptyView) emptyView.style.display = 'none';

    let subtotal = 0;
    let html = '';

    cart.forEach((item, index) => {
        const itemSubtotal = item.price * item.quantity;
        subtotal += itemSubtotal;
        const itemTitle = typeof item.title === 'object' ? (item.title[window.AURA_LANG] || item.title.en) : item.title;

        html += `
            <tr class="cart-item-row">
                <td class="product-cell">
                    <div class="cart-product-flex">
                        <img src="${item.image}" alt="${itemTitle}" class="cart-product-thumb">
                        <div class="cart-product-info">
                            <a href="product.php?id=${item.id}" class="cart-item-title">${itemTitle}</a>
                            ${item.size ? `<span class="cart-item-spec">Size: ${item.size}</span>` : ''}
                            ${item.color ? `<span class="cart-item-spec">Color: ${item.color}</span>` : ''}
                        </div>
                    </div>
                </td>
                <td class="price-cell">$${item.price.toFixed(2)}</td>
                <td class="qty-cell">
                    <div class="quantity-picker qty-mini">
                        <button type="button" class="qty-btn" onclick="window.AuraStore.updateQuantity(${item.id}, ${item.quantity - 1}, '${item.size || ''}', '${item.color || ''}'); renderCartPage();">−</button>
                        <input type="number" value="${item.quantity}" readonly class="qty-input">
                        <button type="button" class="qty-btn" onclick="window.AuraStore.updateQuantity(${item.id}, ${item.quantity + 1}, '${item.size || ''}', '${item.color || ''}'); renderCartPage();">+</button>
                    </div>
                </td>
                <td class="subtotal-cell font-bold">$${itemSubtotal.toFixed(2)}</td>
                <td class="remove-cell">
                    <button class="btn-remove-item" onclick="window.AuraStore.removeFromCart(${item.id}, '${item.size || ''}', '${item.color || ''}'); renderCartPage();" title="Remove">✕</button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    // Check discount from sessionStorage
    const activeDiscountRate = parseFloat(sessionStorage.getItem('aura_discount_rate') || 0);
    const discountAmount = subtotal * activeDiscountRate;
    const finalTotal = Math.max(0, subtotal - discountAmount);

    document.getElementById('summarySubtotal').innerText = '$' + subtotal.toFixed(2);
    
    const discountRow = document.getElementById('discountRow');
    if (activeDiscountRate > 0) {
        discountRow.style.display = 'flex';
        document.getElementById('discountBadge').innerText = `(${activeDiscountRate * 100}%)`;
        document.getElementById('summaryDiscount').innerText = '-$' + discountAmount.toFixed(2);
    } else {
        discountRow.style.display = 'none';
    }

    document.getElementById('summaryTotal').innerText = '$' + finalTotal.toFixed(2);
}

function applyCoupon() {
    const code = document.getElementById('couponInput').value.trim().toUpperCase();
    const feedback = document.getElementById('couponFeedback');
    
    if (code === 'AURA10') {
        sessionStorage.setItem('aura_discount_rate', '0.10');
        feedback.className = 'coupon-msg text-success';
        feedback.innerText = '✓ 10% Aura VIP Discount applied!';
        renderCartPage();
    } else if (code === 'LUXURY20') {
        sessionStorage.setItem('aura_discount_rate', '0.20');
        feedback.className = 'coupon-msg text-success';
        feedback.innerText = '✓ 20% Luxury Connoisseur Discount applied!';
        renderCartPage();
    } else {
        feedback.className = 'coupon-msg text-danger';
        feedback.innerText = '✕ Invalid promo code. Try AURA10';
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
