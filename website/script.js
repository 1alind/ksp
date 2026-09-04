/**
 * Aura Luxury E-Commerce Store — Main Interactive Engine
 * Handles: Cart management, Dark/Light theme toggle, Multi-language switcher,
 * Quick View modal, Toast notifications, Real-time Countdown timer, and Filters.
 */

(function () {
    'use strict';

    const CART_STORAGE_KEY = 'aura_cart_items';
    const THEME_STORAGE_KEY = 'aura_theme';
    const LANG_STORAGE_KEY = 'aura_lang';

    // Global Store Object
    window.AuraStore = {
        // 1. Cart Management
        getCart: function () {
            try {
                return JSON.parse(localStorage.getItem(CART_STORAGE_KEY)) || [];
            } catch (e) {
                return [];
            }
        },

        saveCart: function (cart) {
            localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
            this.updateCartBadge();
        },

        addToCart: function (productId, quantity = 1, size = '', color = '') {
            const products = window.AURA_PRODUCTS || [];
            const product = products.find(p => p.id === productId);
            if (!product) return;

            const stock = typeof product.stock === 'number' ? product.stock : parseInt(product.stock || '0', 10);
            if (stock <= 0) {
                const oosMsg = window.AURA_LANG === 'ku'
                    ? '⚠️ ببورە، ئەڤ بەرهەمە نوکە د مەخزەندا نەمایە!'
                    : (window.AURA_LANG === 'ar' ? '⚠️ عذراً، هذا المنتج غير متوفر في المخزن حالياً!' : '⚠️ Sorry, this piece is currently out of stock!');
                this.showToast(oosMsg, 'error');
                return;
            }

            let cart = this.getCart();
            const existingIndex = cart.findIndex(item => item.id === productId && item.size === size && item.color === color);

            if (existingIndex > -1) {
                const newTotal = cart[existingIndex].quantity + quantity;
                if (newTotal > stock) {
                    const limitMsg = window.AURA_LANG === 'ku'
                        ? `⚠️ بتنێ ${stock} دانە د مەخزەندا بەردەستن!`
                        : (window.AURA_LANG === 'ar' ? `⚠️ متوفر فقط ${stock} قطع في المخزن!` : `⚠️ Only ${stock} items available in stock!`);
                    this.showToast(limitMsg, 'error');
                    return;
                }
                cart[existingIndex].quantity = newTotal;
            } else {
                if (quantity > stock) {
                    const limitMsg = window.AURA_LANG === 'ku'
                        ? `⚠️ بتنێ ${stock} دانە د مەخزەندا بەردەستن!`
                        : (window.AURA_LANG === 'ar' ? `⚠️ متوفر فقط ${stock} قطع في المخزن!` : `⚠️ Only ${stock} items available in stock!`);
                    this.showToast(limitMsg, 'error');
                    return;
                }
                cart.push({
                    id: product.id,
                    title: product.title,
                    price: product.price,
                    image: product.image,
                    category: product.category,
                    quantity: quantity,
                    size: size || '',
                    color: color || ''
                });
            }

            this.saveCart(cart);

            const addedMsg = window.AURA_LANG === 'ku'
                ? 'بەرهەم بۆ سەبەتێ هاتە زێدەکرن!'
                : (window.AURA_LANG === 'ar' ? 'تمت إضافة المنتج إلى السلة!' : 'Item added to your luxury bag!');
            
            this.showToast(addedMsg, 'success');
        },

        removeFromCart: function (productId, size = '', color = '') {
            let cart = this.getCart();
            cart = cart.filter(item => !(item.id === productId && item.size === size && item.color === color));
            this.saveCart(cart);
            this.showToast('Item removed from cart', 'info');
        },

        updateQuantity: function (productId, newQty, size = '', color = '') {
            let cart = this.getCart();
            const item = cart.find(item => item.id === productId && item.size === size && item.color === color);
            if (item) {
                if (newQty <= 0) {
                    this.removeFromCart(productId, size, color);
                } else {
                    const products = window.AURA_PRODUCTS || [];
                    const product = products.find(p => p.id === productId);
                    const stock = product ? (typeof product.stock === 'number' ? product.stock : parseInt(product.stock || '999', 10)) : 999;
                    
                    if (newQty > stock) {
                        item.quantity = stock;
                        const limitMsg = window.AURA_LANG === 'ku'
                            ? `⚠️ بتنێ ${stock} دانە د مەخزەندا بەردەستن!`
                            : (window.AURA_LANG === 'ar' ? `⚠️ متوفر فقط ${stock} قطع في المخزن!` : `⚠️ Only ${stock} items available in stock!`);
                        this.showToast(limitMsg, 'error');
                    } else {
                        item.quantity = newQty;
                    }
                    this.saveCart(cart);
                }
            }
        },

        clearCart: function () {
            localStorage.removeItem(CART_STORAGE_KEY);
            this.updateCartBadge();
        },

        adjustStock: function (productId, delta) {
            const badgeEl = document.getElementById('stockBadge_' + productId);
            const statusEl = document.getElementById('stockStatusText_' + productId);
            
            fetch('/admin/products.php?action=adjust_stock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${encodeURIComponent(productId)}&stock_delta=${encodeURIComponent(delta)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    const newStock = data.stock;
                    if (badgeEl) {
                        badgeEl.innerText = newStock;
                        badgeEl.style.color = newStock <= 0 ? '#ef4444' : (newStock <= 3 ? '#f59e0b' : 'var(--text-primary)');
                    }
                    if (statusEl) {
                        if (newStock <= 0) {
                            statusEl.innerText = 'Out of Stock';
                            statusEl.style.background = 'rgba(239,68,68,0.12)';
                            statusEl.style.color = '#ef4444';
                            statusEl.style.borderColor = 'rgba(239,68,68,0.3)';
                        } else if (newStock <= 3) {
                            statusEl.innerText = `Low Stock (${newStock})`;
                            statusEl.style.background = 'rgba(245,158,11,0.12)';
                            statusEl.style.color = '#f59e0b';
                            statusEl.style.borderColor = 'rgba(245,158,11,0.3)';
                        } else {
                            statusEl.innerText = 'In Stock';
                            statusEl.style.background = 'rgba(16,185,129,0.12)';
                            statusEl.style.color = '#10b981';
                            statusEl.style.borderColor = 'rgba(16,185,129,0.3)';
                        }
                    }
                    
                    // Also update window.AURA_PRODUCTS in memory if present
                    if (window.AURA_PRODUCTS) {
                        const p = window.AURA_PRODUCTS.find(x => x.id === productId);
                        if (p) p.stock = newStock;
                    }

                    this.showToast(`Stock updated to ${newStock}`, 'success');
                }
            })
            .catch(err => {
                console.error('Stock adjustment error:', err);
            });
        },

        updateCartBadge: function () {
            const cart = this.getCart();
            const totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
            const badge = document.getElementById('cartCount');
            if (badge) {
                badge.innerText = totalCount;
                badge.style.display = totalCount > 0 ? 'flex' : 'none';
            }
        },

        // 2. Toast Notifications
        showToast: function (message, type = 'info') {
            let container = document.getElementById('toastContainer') || document.getElementById('auraToastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'auraToastContainer';
                container.className = 'aura-toast-container';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            const icon = type === 'success' ? '✓' : (type === 'error' ? '⚠️' : '✨');
            toast.innerHTML = `<span style="font-size:16px;">${icon}</span> <div style="font-weight:600;">${message}</div>`;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                setTimeout(() => toast.remove(), 300);
            }, 3200);
        },

        // 3. Quick View Modal
        openQuickView: function (productId) {
            const products = window.AURA_PRODUCTS || [];
            const product = products.find(p => p.id === productId);
            if (!product) return;

            const modal = document.getElementById('quickViewModal');
            const content = document.getElementById('quickViewContent');
            if (!modal || !content) return;

            const lang = window.AURA_LANG || 'en';
            const title = typeof product.title === 'object' ? (product.title[lang] || product.title.en) : product.title;
            const desc = typeof product.description === 'object' ? (product.description[lang] || product.description.en) : product.description;
            const stock = typeof product.stock === 'number' ? product.stock : parseInt(product.stock || '0', 10);
            const isOutOfStock = stock <= 0;

            const inStockLabel = lang === 'ku' ? 'بەردەستە د مەخزەندا' : (lang === 'ar' ? 'متوفر في المخزن' : 'In Stock');
            const outOfStockLabel = lang === 'ku' ? 'نەمایە د مەخزەندا' : (lang === 'ar' ? 'غير متوفر في المخزن' : 'Out of Stock');
            const addToBagLabel = lang === 'ku' ? 'زێدەکرن بۆ سەبەتێ' : (lang === 'ar' ? 'إضافة إلى السلة' : 'Add to Bag');
            const viewDetailsLabel = lang === 'ku' ? 'دیتنا هویرکاریێن زێدەتر ←' : (lang === 'ar' ? 'عرض التفاصيل الكاملة ←' : 'View Full Details & Options →');

            content.innerHTML = `
                <div class="product-view-grid" style="margin-top:0; gap:30px;">
                    <div class="gallery-main-wrap" style="height:340px; position:relative;">
                        ${isOutOfStock ? `<span class="product-badge-tag out-of-stock-badge" style="z-index:10;">✕ ${outOfStockLabel}</span>` : ''}
                        <img src="${product.image}" alt="${title}" class="gallery-main-img" style="${isOutOfStock ? 'opacity:0.85;' : ''}">
                    </div>
                    <div class="product-buy-info">
                        <div class="product-meta-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span class="product-cat-pill">${product.category}</span>
                            ${isOutOfStock 
                                ? `<span class="stock-status out-of-stock">✕ ${outOfStockLabel}</span>` 
                                : `<span class="stock-status in-stock">● ${inStockLabel}</span>`}
                        </div>
                        <h2 class="single-product-title" style="font-size:24px;">${title}</h2>
                        <div class="single-rating-row">
                            <span class="stars">★★★★★</span>
                            <span class="rating-num">${product.rating}</span>
                        </div>
                        <div class="single-price-box" style="padding:10px 14px; margin-bottom:16px;">
                            <span class="current-price-lg" style="font-size:26px;">${Math.round(product.price).toLocaleString()} IQD</span>
                            ${product.old_price ? `<span class="old-price-lg">${Math.round(product.old_price).toLocaleString()} IQD</span>` : ''}
                        </div>
                        <p class="product-short-desc" style="font-size:14px; margin-bottom:16px;">${desc}</p>
                        <div class="purchase-action-row" style="margin:14px 0;">
                            ${isOutOfStock 
                                ? `<button class="btn btn-secondary w-full" disabled style="opacity:0.6; cursor:not-allowed; padding:12px 20px;">
                                    🚫 ${outOfStockLabel}
                                   </button>`
                                : `<button class="btn btn-primary btn-luxury w-full" onclick="window.AuraStore.addToCart(${product.id}); document.getElementById('quickViewModal').classList.remove('open');">
                                    🛍️ ${addToBagLabel}
                                   </button>`}
                        </div>
                        <a href="product.php?id=${product.id}" class="text-primary font-bold text-center" style="font-size:13.5px; display:block; margin-top:8px;">
                            ${viewDetailsLabel}
                        </a>
                    </div>
                </div>
            `;

            modal.classList.add('open');
        }
    };

    // Global Language Dropdown Toggle
    window.toggleLanguageDropdown = function (event) {
        if (event) {
            if (event.preventDefault) event.preventDefault();
            if (event.stopPropagation) event.stopPropagation();
        }
        const dropdown = document.getElementById('langDropdown');
        if (!dropdown) return;
        dropdown.classList.toggle('show');
    };

    // Global Site Language Switcher
    window.changeSiteLanguage = function (lang, event) {
        if (event && event.preventDefault) {
            event.preventDefault();
        }
        if (!['en', 'ar', 'ku'].includes(lang)) return;
        try {
            localStorage.setItem(LANG_STORAGE_KEY, lang);
        } catch (e) {}

        // Root path cookie accessible across all pages and subfolders
        document.cookie = `aura_lang=${lang};path=/;max-age=31536000;SameSite=Lax`;
        if (window.location.protocol === 'https:') {
            document.cookie = `aura_lang=${lang};path=/;max-age=31536000;SameSite=None;Secure`;
        }

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        } catch (e) {
            window.location.href = `?lang=${lang}`;
        }
    };

    // DOM Ready Initialization
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Theme from localStorage
        const savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }

        // Initialize Cart Counter
        window.AuraStore.updateCartBadge();

        // 1. Theme Toggle Event
        const themeBtn = document.getElementById('themeToggleBtn');
        if (themeBtn) {
            themeBtn.addEventListener('click', function () {
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', nextTheme);
                localStorage.setItem(THEME_STORAGE_KEY, nextTheme);
                document.cookie = `aura_theme=${nextTheme};path=/;max-age=31536000;SameSite=Lax`;
            });
        }

        // 2. Global Outside Click Listener to Close Language Dropdown
        document.addEventListener('click', function (e) {
            const langDropdown = document.getElementById('langDropdown');
            const langBtn = document.getElementById('langDropdownBtn');
            if (langDropdown && langDropdown.classList.contains('show')) {
                if (langBtn && (e.target === langBtn || langBtn.contains(e.target))) {
                    return;
                }
                if (!langDropdown.contains(e.target)) {
                    langDropdown.classList.remove('show');
                }
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const langDropdown = document.getElementById('langDropdown');
                if (langDropdown) {
                    langDropdown.classList.remove('show');
                }
            }
        });

        // 3. Mobile Menu Toggle
        const mobToggle = document.getElementById('mobileMenuToggle');
        const mobDrawer = document.getElementById('mobileDrawer');
        if (mobToggle && mobDrawer) {
            mobToggle.addEventListener('click', function () {
                mobDrawer.classList.toggle('open');
            });
        }

        // 4. Quick View Event Listeners
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.quick-view-btn');
            if (btn) {
                const id = parseInt(btn.getAttribute('data-id'));
                window.AuraStore.openQuickView(id);
            }
        });

        const qvClose = document.getElementById('quickViewClose');
        const qvModal = document.getElementById('quickViewModal');
        if (qvClose && qvModal) {
            qvClose.addEventListener('click', () => qvModal.classList.remove('open'));
            qvModal.addEventListener('click', (e) => {
                if (e.target === qvModal) qvModal.classList.remove('open');
            });
        }

        // 5. Add to cart direct buttons
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.add-cart-btn');
            if (btn) {
                const id = parseInt(btn.getAttribute('data-id'));
                window.AuraStore.addToCart(id);
            }
        });

        // 6. Home Filter Tabs
        const filterTabs = document.querySelectorAll('#homeFilterTabs .cat-tab-btn');
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const filter = this.getAttribute('data-filter');
                const cards = document.querySelectorAll('#featuredProductsGrid .product-card');

                cards.forEach(card => {
                    const cat = card.getAttribute('data-category');
                    const isNew = card.getAttribute('data-is-new') === 'true';
                    if (filter === 'all' || (filter === 'new' && isNew) || cat === filter) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // 7. Live Flash Sale Countdown Timer
        initCountdown();
    });

    function initCountdown() {
        const daysEl = document.getElementById('countDays');
        const hoursEl = document.getElementById('countHours');
        const minsEl = document.getElementById('countMins');
        const secsEl = document.getElementById('countSecs');

        if (!daysEl || !hoursEl || !minsEl || !secsEl) return;

        // 3 days, 14 hours countdown target
        let totalSeconds = (3 * 24 * 3600) + (14 * 3600) + (28 * 60) + 45;

        setInterval(() => {
            if (totalSeconds <= 0) {
                totalSeconds = (3 * 24 * 3600) + (14 * 3600);
            }
            totalSeconds--;

            const d = Math.floor(totalSeconds / (24 * 3600));
            const h = Math.floor((totalSeconds % (24 * 3600)) / 3600);
            const m = Math.floor((totalSeconds % 3600) / 60);
            const s = Math.floor(totalSeconds % 60);

            daysEl.innerText = String(d).padStart(2, '0');
            hoursEl.innerText = String(h).padStart(2, '0');
            minsEl.innerText = String(m).padStart(2, '0');
            secsEl.innerText = String(s).padStart(2, '0');
        }, 1000);
    }

})();
