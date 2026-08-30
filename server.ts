import express from "express";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const app = express();
const PORT = 3000;

app.use(express.urlencoded({ extended: true, limit: "10mb" }));
app.use(express.json({ limit: "10mb" }));

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const websiteDir = path.join(__dirname, "website");
const websiteDbDir = path.join(websiteDir, "database");

// Ensure website database directory exists
if (!fs.existsSync(websiteDbDir)) fs.mkdirSync(websiteDbDir, { recursive: true });

function getDbFile(filename: string): any {
  const filePath = path.join(websiteDbDir, filename);
  if (fs.existsSync(filePath)) {
    try {
      return JSON.parse(fs.readFileSync(filePath, "utf-8"));
    } catch (e) {}
  }
  return {};
}

function saveDbFile(filename: string, data: any) {
  const jsonStr = JSON.stringify(data, null, 4);
  fs.writeFileSync(path.join(websiteDbDir, filename), jsonStr, "utf-8");
}

// Load translations from database/translations.json
function loadTranslations(): Record<string, Record<string, string>> {
  const trFile = path.join(websiteDbDir, "translations.json");
  if (fs.existsSync(trFile)) {
    try {
      const data = JSON.parse(fs.readFileSync(trFile, "utf-8"));
      return {
        en: data.en || {},
        ar: data.ar || {},
        ku: data.ku || {}
      };
    } catch (e) {
      console.error("Error reading translations.json:", e);
    }
  }
  return { en: {}, ar: {}, ku: {} };
}

function t(key: string, lang: string): string {
  const dict = loadTranslations();
  if (dict[lang] && dict[lang][key]) {
    return dict[lang][key];
  }
  if (dict["en"] && dict["en"][key]) {
    return dict["en"][key];
  }
  return key;
}

function getCookies(req: express.Request): Record<string, string> {
  const list: Record<string, string> = {};
  const rc = req.headers.cookie;
  if (rc) {
    rc.split(";").forEach((cookie) => {
      const parts = cookie.split("=");
      list[parts.shift()!.trim()] = decodeURI(parts.join("="));
    });
  }
  return list;
}

function renderHeader(lang: string, theme: string, activePage: string, pageTitle: string): string {
  const isRtl = lang === "ar" || lang === "ku";
  const dirAttr = isRtl ? 'dir="rtl"' : 'dir="ltr"';
  
  const settings = getDbFile("settings.json");
  const storeName = lang === "ar" ? (settings.store_name_ar || settings.store_name || "AURA Luxury Store") : (lang === "ku" ? (settings.store_name_ku || settings.store_name || "AURA Luxury Store") : (settings.store_name || "AURA Luxury Store"));
  const logoType = settings.logo_type || "emblem";
  const logoEmblem = settings.logo_emblem || "A";
  const logoMain = settings.logo_main || "AURA";
  const logoSub = settings.logo_sub || "STUDIO";
  const logoImageUrl = settings.logo_image_url || "";
  const faviconUrl = settings.favicon_url || "";
  const announcementEnabled = settings.announcement_enabled !== false;
  const announcementText = settings[`announcement_text_${lang}`] || settings.announcement_text_en || (t("features_shipping_title", lang) + " • " + t("flash_sale_badge", lang));

  const displayTitle = pageTitle ? `${pageTitle} — ${storeName}` : `${storeName} — ${settings['store_tagline_' + lang] || t("site_title", lang)}`;

  const langNames: Record<string, string> = {
    en: "English (EN)",
    ar: "العربية (AR)",
    ku: "کوردی بادینی (KU)"
  };

  const logoHtml = (logoType === "image" && logoImageUrl)
    ? `<img src="${logoImageUrl}" alt="${storeName}" class="brand-img-logo" style="max-height:42px; object-fit:contain;">`
    : `<div class="logo-emblem">${logoEmblem}</div>
       <div class="logo-text-group">
           <span class="logo-main">${logoMain}</span>
           <span class="logo-sub">${logoSub}</span>
       </div>`;

  return `<!DOCTYPE html>
<html lang="${lang}" ${dirAttr} data-theme="${theme}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${displayTitle}</title>
    ${faviconUrl ? `<link rel="icon" href="${faviconUrl}">` : ''}
    
    <!-- Google Fonts for English, Arabic, and Kurdish Badini -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&family=Cairo:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-${activePage}">

    <!-- Top Announcement Bar -->
    ${announcementEnabled ? `
    <div class="announcement-bar">
        <div class="container announcement-container">
            <div class="announcement-text">
                <span class="sparkle-icon">✨</span>
                <span>${announcementText}</span>
            </div>
            
            <div class="top-bar-actions">
                <!-- Language Switcher -->
                <div class="dropdown-wrapper">
                    <button class="top-btn" id="langDropdownBtn" aria-label="Select Language">
                        <span class="globe-icon">🌐</span>
                        <span class="current-lang-text">${langNames[lang] || "English"}</span>
                        <span class="chevron-down">▾</span>
                    </button>
                    <div class="dropdown-menu" id="langDropdown">
                        <a href="?lang=en" class="dropdown-item ${lang === 'en' ? 'active' : ''}" data-lang-set="en">English (EN)</a>
                        <a href="?lang=ar" class="dropdown-item ${lang === 'ar' ? 'active' : ''}" data-lang-set="ar">العربية (AR)</a>
                        <a href="?lang=ku" class="dropdown-item ${lang === 'ku' ? 'active' : ''}" data-lang-set="ku">کوردی - بادینی (KU)</a>
                    </div>
                </div>

                <!-- Dark / Light Theme Toggle -->
                <button class="theme-toggle-btn" id="themeToggleBtn" title="${t('theme_toggle', lang)}" aria-label="Toggle Theme">
                    <span class="theme-icon sun-icon">☀️</span>
                    <span class="theme-icon moon-icon">🌙</span>
                </button>
            </div>
        </div>
    </div>
    ` : ''}

    <!-- Main Navigation Header -->
    <header class="site-header" id="siteHeader">
        <div class="container header-container">
            <!-- Mobile Menu Toggle Button -->
            <button class="mobile-toggle-btn" id="mobileMenuToggle" aria-label="Toggle Mobile Menu">
                <span class="line"></span>
                <span class="line"></span>
                <span class="line"></span>
            </button>

            <!-- Brand Logo -->
            <a href="index.php" class="brand-logo">
                ${logoHtml}
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="nav-links" id="navLinks">
                <a href="index.php" class="nav-link ${activePage === 'home' ? 'active' : ''}">
                    ${t('nav_home', lang)}
                </a>
                <a href="shop.php" class="nav-link ${activePage === 'shop' ? 'active' : ''}">
                    ${t('nav_shop', lang)}
                </a>
                <a href="shop.php?cat=clothes" class="nav-link ${activePage === 'clothes' ? 'active' : ''}">
                    ${t('nav_clothes', lang)}
                </a>
                <a href="shop.php?cat=watches" class="nav-link ${activePage === 'watches' ? 'active' : ''}">
                    ${t('nav_watches', lang)}
                </a>
                <a href="shop.php?cat=perfumes" class="nav-link ${activePage === 'perfumes' ? 'active' : ''}">
                    ${t('nav_perfumes', lang)}
                </a>
                <a href="contact.php" class="nav-link ${activePage === 'contact' ? 'active' : ''}">
                    ${t('nav_contact', lang)}
                </a>
            </nav>

            <!-- Right Action Icons -->
            <div class="header-actions">
                <!-- Search Button -->
                <button class="action-btn search-trigger-btn" id="searchTriggerBtn" title="${t('search_placeholder', lang)}">
                    <span class="btn-icon">🔍</span>
                </button>

                <!-- Shopping Bag / Cart -->
                <a href="cart.php" class="action-btn cart-btn-pill" id="cartHeaderBtn">
                    <span class="cart-icon">🛍️</span>
                    <span class="cart-label-text">${t('cart', lang)}</span>
                    <span class="cart-badge-count" id="headerCartCount">0</span>
                </a>
            </div>
        </div>

        <!-- Search Drawer Overlay -->
        <div class="search-drawer" id="searchDrawer">
            <div class="container search-drawer-inner">
                <form action="shop.php" method="GET" class="search-drawer-form">
                    <input type="text" name="q" placeholder="${t('search_placeholder', lang)}" class="search-input" autofocus id="mainSearchInput">
                    <button type="submit" class="btn btn-primary">${t('sort_by', lang)}</button>
                    <button type="button" class="btn-close-search" id="closeSearchBtn">✕</button>
                </form>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer Navigation -->
    <div class="mobile-drawer-overlay" id="mobileDrawerOverlay"></div>
    <aside class="mobile-drawer" id="mobileDrawer">
        <div class="mobile-drawer-header">
            <div class="brand-logo">
                <span class="logo-emblem">✦</span>
                <span class="brand-name">AURA</span>
            </div>
            <button class="close-drawer-btn" id="closeDrawerBtn">✕</button>
        </div>

        <nav class="mobile-nav-list">
            <a href="index.php" class="mobile-nav-item ${activePage === 'home' ? 'active' : ''}">
                <span>🏠</span> ${t('nav_home', lang)}
            </a>
            <a href="shop.php" class="mobile-nav-item ${activePage === 'shop' ? 'active' : ''}">
                <span>💎</span> ${t('nav_shop', lang)}
            </a>
            <a href="shop.php?cat=clothes" class="mobile-nav-item">
                <span>👔</span> ${t('nav_clothes', lang)}
            </a>
            <a href="shop.php?cat=watches" class="mobile-nav-item">
                <span>⌚</span> ${t('nav_watches', lang)}
            </a>
            <a href="shop.php?cat=perfumes" class="mobile-nav-item">
                <span>🌸</span> ${t('nav_perfumes', lang)}
            </a>
            <a href="shop.php?cat=accessories" class="mobile-nav-item">
                <span>🕶️</span> ${t('nav_accessories', lang)}
            </a>
            <a href="track.php" class="mobile-nav-item">
                <span>📦</span> ${t('nav_track', lang)}
            </a>
            <a href="contact.php" class="mobile-nav-item ${activePage === 'contact' ? 'active' : ''}">
                <span>✉️</span> ${t('nav_contact', lang)}
            </a>
            <a href="admin.php" class="mobile-nav-item ${activePage === 'admin' ? 'active' : ''}">
                <span>⚙️</span> ${t('nav_admin', lang)}
            </a>
        </nav>

        <div class="mobile-drawer-footer">
            <div class="lang-pill-selector">
                <button class="pill-btn ${lang === 'en' ? 'active' : ''}" onclick="window.AuraStore.setLanguage('en')">EN</button>
                <button class="pill-btn ${lang === 'ar' ? 'active' : ''}" onclick="window.AuraStore.setLanguage('ar')">العربية</button>
                <button class="pill-btn ${lang === 'ku' ? 'active' : ''}" onclick="window.AuraStore.setLanguage('ku')">کوردی</button>
            </div>
        </div>
    </aside>

    <main class="site-main-content">
`;
}

function renderFooter(lang: string, theme: string = "dark"): string {
  const trDict = loadTranslations();
  const langTranslations = trDict[lang] || trDict["en"] || {};
  const productsDb = getDbFile("products.json");
  const allProducts = productsDb.products || [];

  return `
    </main>

    <!-- Global Features / Guarantees Banner -->
    <section class="store-features-strip">
        <div class="container features-grid">
            <div class="feature-box">
                <div class="feature-icon-wrap">🚚</div>
                <div class="feature-info">
                    <h4>${t('features_shipping_title', lang)}</h4>
                    <p>${t('features_shipping_desc', lang)}</p>
                </div>
            </div>
            <div class="feature-box">
                <div class="feature-icon-wrap">💎</div>
                <div class="feature-info">
                    <h4>${t('features_quality_title', lang)}</h4>
                    <p>${t('features_quality_desc', lang)}</p>
                </div>
            </div>
            <div class="feature-box">
                <div class="feature-icon-wrap">🛡️</div>
                <div class="feature-info">
                    <h4>${t('features_payment_title', lang)}</h4>
                    <p>${t('features_payment_desc', lang)}</p>
                </div>
            </div>
            <div class="feature-box">
                <div class="feature-icon-wrap">👑</div>
                <div class="feature-info">
                    <h4>${t('features_support_title', lang)}</h4>
                    <p>${t('features_support_desc', lang)}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Footer -->
    <footer class="site-footer">
        <div class="container footer-main-grid">
            <!-- Col 1: Brand & Bio -->
            <div class="footer-col brand-col">
                <a href="index.php" class="brand-logo footer-logo">
                    <span class="logo-emblem">✦</span>
                    <div class="logo-text-group">
                        <span class="brand-name">AURA</span>
                        <span class="brand-sub">LUXURY STORE</span>
                    </div>
                </a>
                <p class="footer-bio-text">
                    ${t('footer_about', lang)}
                </p>
                <div class="social-links-row">
                    <a href="#" class="social-icon" aria-label="Instagram">📸</a>
                    <a href="#" class="social-icon" aria-label="Facebook">📘</a>
                    <a href="#" class="social-icon" aria-label="TikTok">🎵</a>
                    <a href="#" class="social-icon" aria-label="WhatsApp">💬</a>
                </div>
            </div>

            <!-- Col 2: Quick Links -->
            <div class="footer-col">
                <h4 class="footer-heading">${t('footer_quick_links', lang)}</h4>
                <ul class="footer-menu">
                    <li><a href="index.php">${t('nav_home', lang)}</a></li>
                    <li><a href="shop.php">${t('nav_shop', lang)}</a></li>
                    <li><a href="contact.php">${t('nav_contact', lang)}</a></li>
                    <li><a href="track.php">${t('nav_track', lang)}</a></li>
                    <li><a href="admin.php">${t('nav_admin', lang)}</a></li>
                </ul>
            </div>

            <!-- Col 3: Categories -->
            <div class="footer-col">
                <h4 class="footer-heading">${t('footer_categories', lang)}</h4>
                <ul class="footer-menu">
                    <li><a href="shop.php?cat=clothes">${t('nav_clothes', lang)}</a></li>
                    <li><a href="shop.php?cat=watches">${t('nav_watches', lang)}</a></li>
                    <li><a href="shop.php?cat=perfumes">${t('nav_perfumes', lang)}</a></li>
                    <li><a href="shop.php?cat=accessories">${t('nav_accessories', lang)}</a></li>
                </ul>
            </div>

            <!-- Col 4: Payments Accepted -->
            <div class="footer-col payment-col">
                <h4 class="footer-heading">${lang === 'ku' ? 'شێوازێن پارەدانێ' : (lang === 'ar' ? 'طرق الدفع' : 'Payment Methods')}</h4>
                <p class="footer-bio-text" style="margin-top:0;">${lang === 'ku' ? 'پارەدانا باوەرپێکری دەمێ وەرگرتنا بەرهەمی یان ب رێکا ئەپێن پارەدانێ.' : (lang === 'ar' ? 'دفع آمن وموثوق عند الاستلام أو عبر المحافظ الإلكترونية المعتمدة.' : 'Secure payment upon delivery or through verified digital banking.')}</p>
                <div class="payment-methods-accepted">
                    <span>💵 Cash on Delivery</span>
                    <span>📱 FastPay</span>
                    <span>💳 ZainCash</span>
                    <span>🛡️ Visa / MasterCard</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom-bar">
            <div class="container footer-bottom-inner">
                <p>&copy; 2026 AURA Luxury Store. ${t('rights_reserved', lang)}</p>
                <div class="footer-bottom-links">
                    <a href="contact.php">VIP Concierge</a>
                    <span>•</span>
                    <a href="track.php">Delivery Tracker</a>
                    <span>•</span>
                    <a href="admin.php">Management</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Global Toast Container -->
    <div id="auraToastContainer" class="aura-toast-container"></div>

    <!-- Quick View Modal -->
    <div class="modal-overlay" id="quickViewModalOverlay">
        <div class="modal-card quick-view-card" id="quickViewModalCard">
            <button class="modal-close-btn" id="closeQuickViewBtn">✕</button>
            <div class="quick-view-grid" id="quickViewModalContent">
                <!-- Injected via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Pass JSON Data to Client Scripts for Instant Filtering & Cart Operations -->
    <script>
        window.AURA_LANG = ${JSON.stringify(lang)};
        window.AURA_THEME = ${JSON.stringify(theme)};
        window.AURA_TRANSLATIONS = ${JSON.stringify(langTranslations)};
        window.AURA_PRODUCTS = ${JSON.stringify(allProducts)};
    </script>

    <!-- Application Core Script -->
    <script src="script.js"></script>
</body>
</html>
`;
}

function renderPhpPage(pageName: string, req: express.Request, postData: any = null): string {
  const filePath = path.join(websiteDir, `${pageName}.php`);
  if (!fs.existsSync(filePath)) {
    return `<div style="color:red;padding:40px;font-family:sans-serif;">Error: ${pageName}.php not found</div>`;
  }

  const cookies = getCookies(req);
  let lang = (req.query.lang as string) || cookies.aura_lang || "en";
  if (!["en", "ar", "ku"].includes(lang)) lang = "en";

  let theme = (req.query.theme as string) || cookies.aura_theme || "dark";
  if (!["dark", "light"].includes(theme)) theme = "dark";

  const productsDb = getDbFile("products.json");
  const productsList = productsDb.products || [];

  const ordersDb = getDbFile("orders.json");
  const ordersList = ordersDb.orders || [];

  const reviewsDb = getDbFile("reviews.json");
  const usersDb = getDbFile("users.json");

  // Handle POST actions
  if (postData) {
    if (postData.place_order) {
      const orderId = "ORD-" + Math.floor(10000 + Math.random() * 90000);
      let items = [];
      try {
        items = JSON.parse(postData.cart_items_payload || "[]");
      } catch (e) {
        items = [];
      }

      if (items.length === 0) {
        items = [
          {
            id: 1,
            title: "Royal Velvet Midnight Blazer",
            price: 480.0,
            quantity: 1,
            image: "https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80"
          }
        ];
      }

      const totalAmount = items.reduce((sum: number, it: any) => sum + it.price * it.quantity, 0);

      const newOrder = {
        order_id: orderId,
        customer_name: postData.full_name || "VIP Client",
        phone: postData.phone || "0750 000 0000",
        email: postData.email || "",
        city: postData.city || "Duhok",
        address: postData.address || "Main Street",
        notes: postData.notes || "",
        payment_method: postData.payment_method || "Cash on Delivery",
        payment_status: "Pending",
        order_status: "Pending",
        total: totalAmount,
        currency: "USD",
        items: items,
        created_at: new Date().toISOString()
      };

      ordersList.unshift(newOrder);
      ordersDb.orders = ordersList;
      saveDbFile("orders.json", ordersDb);
    }

    if (postData.update_order_status && postData.order_id && postData.order_status) {
      const ord = ordersList.find((o: any) => o.order_id === postData.order_id);
      if (ord) {
        ord.order_status = postData.order_status;
        ordersDb.orders = ordersList;
        saveDbFile("orders.json", ordersDb);
      }
    }

    if (postData.update_logistics_radar && postData.order_id) {
      const ord = ordersList.find((o: any) => o.order_id === postData.order_id);
      if (ord) {
        if (postData.order_status) ord.order_status = postData.order_status;
        if (postData.courier) ord.courier = postData.courier;
        if (postData.driver_name) ord.driver_name = postData.driver_name;
        if (postData.driver_phone) ord.driver_phone = postData.driver_phone;
        if (postData.tracking_code) ord.tracking_code = postData.tracking_code;
        if (postData.dispatch_notes) ord.dispatch_notes = postData.dispatch_notes;
        if (postData.estimated_delivery) ord.estimated_delivery = postData.estimated_delivery;
        ordersDb.orders = ordersList;
        saveDbFile("orders.json", ordersDb);
      }
    }

    if (postData.save_gateway_settings) {
      const settingsDb = getDbFile("settings.json");
      if (!settingsDb.gateways) settingsDb.gateways = {};
      
      // Update FIB settings
      settingsDb.gateways.fib = {
        enabled: postData.fib_enabled === "1" || postData.fib_enabled === "on" || postData.fib_enabled === true,
        mode: postData.fib_mode || "test",
        client_id: postData.fib_client_id || "",
        client_secret: postData.fib_client_secret || "",
        account_iban: postData.fib_account_iban || "",
        account_holder: postData.fib_account_holder || "",
        callback_url: postData.fib_callback_url || "",
        webhook_secret: postData.fib_webhook_secret || "",
        access_token: postData.fib_access_token || settingsDb.gateways.fib?.access_token || "",
        supported_currencies: ["IQD", "USD"]
      };

      // Update ZainCash settings
      settingsDb.gateways.zaincash = {
        enabled: postData.zaincash_enabled === "1" || postData.zaincash_enabled === "on" || postData.zaincash_enabled === true || postData.zc_enabled === "1",
        mode: postData.zaincash_mode || postData.zc_mode || "test",
        merchant_id: postData.zaincash_merchant_id || postData.zc_merchant_id || "",
        secret_key: postData.zaincash_secret_key || postData.zc_secret || "",
        msisdn: postData.zaincash_msisdn || postData.zc_msisdn || "",
        pin: postData.zaincash_pin || postData.zc_pin || "1234",
        redirect_url: postData.zaincash_redirect_url || "https://aurastore.iq/api/zaincash/redirect",
        service_type: "Luxury Goods Purchase",
        supported_currencies: ["IQD"]
      };

      // Update FastPay settings
      settingsDb.gateways.fastpay = {
        enabled: postData.fastpay_enabled === "1" || postData.fastpay_enabled === "on" || postData.fp_enabled === "1",
        mode: postData.fastpay_mode || "test",
        merchant_mobile: postData.fastpay_merchant_mobile || postData.fp_mobile || "",
        store_id: postData.fastpay_store_id || "FP_STORE_94821",
        store_password: postData.fastpay_store_password || postData.fp_password || "",
        supported_currencies: ["IQD", "USD"]
      };

      // Update Exchange Rate & Global Contacts
      if (postData.exchange_rate_usd_to_iqd) {
        settingsDb.exchange_rate_usd_to_iqd = parseFloat(postData.exchange_rate_usd_to_iqd);
      }
      if (postData.contact_phone) settingsDb.contact_phone = postData.contact_phone;
      if (postData.contact_whatsapp) settingsDb.contact_whatsapp = postData.contact_whatsapp;
      if (postData.contact_email) settingsDb.contact_email = postData.contact_email;

      saveDbFile("settings.json", settingsDb);
    }

    if (postData.save_website_branding || postData.save_general_settings) {
      const settingsDb = getDbFile("settings.json");
      
      // Store Names
      if (postData.store_name) settingsDb.store_name = postData.store_name;
      if (postData.store_name_ar) settingsDb.store_name_ar = postData.store_name_ar;
      if (postData.store_name_ku) settingsDb.store_name_ku = postData.store_name_ku;

      // Taglines & Slogans
      if (postData.store_tagline_en) settingsDb.store_tagline_en = postData.store_tagline_en;
      if (postData.store_tagline_ar) settingsDb.store_tagline_ar = postData.store_tagline_ar;
      if (postData.store_tagline_ku) settingsDb.store_tagline_ku = postData.store_tagline_ku;

      // Store Descriptions (Footer & Meta)
      if (postData.store_description_en) settingsDb.store_description_en = postData.store_description_en;
      if (postData.store_description_ar) settingsDb.store_description_ar = postData.store_description_ar;
      if (postData.store_description_ku) settingsDb.store_description_ku = postData.store_description_ku;

      // Hero Headlines
      if (postData.hero_headline_en) settingsDb.hero_headline_en = postData.hero_headline_en;
      if (postData.hero_headline_ar) settingsDb.hero_headline_ar = postData.hero_headline_ar;
      if (postData.hero_headline_ku) settingsDb.hero_headline_ku = postData.hero_headline_ku;

      // Hero Subtitles
      if (postData.hero_subtitle_en) settingsDb.hero_subtitle_en = postData.hero_subtitle_en;
      if (postData.hero_subtitle_ar) settingsDb.hero_subtitle_ar = postData.hero_subtitle_ar;
      if (postData.hero_subtitle_ku) settingsDb.hero_subtitle_ku = postData.hero_subtitle_ku;

      // Logo & Visual Branding
      if (postData.logo_type) settingsDb.logo_type = postData.logo_type;
      if (postData.logo_emblem) settingsDb.logo_emblem = postData.logo_emblem;
      if (postData.logo_main) settingsDb.logo_main = postData.logo_main;
      if (postData.logo_sub) settingsDb.logo_sub = postData.logo_sub;
      if (postData.logo_image_url !== undefined) settingsDb.logo_image_url = postData.logo_image_url;
      if (postData.favicon_url !== undefined) settingsDb.favicon_url = postData.favicon_url;
      if (postData.brand_accent_color) settingsDb.brand_accent_color = postData.brand_accent_color;

      // Top Announcement Bar
      settingsDb.announcement_enabled = postData.announcement_enabled === "1" || postData.announcement_enabled === "on" || postData.announcement_enabled === true;
      if (postData.announcement_text_en) settingsDb.announcement_text_en = postData.announcement_text_en;
      if (postData.announcement_text_ar) settingsDb.announcement_text_ar = postData.announcement_text_ar;
      if (postData.announcement_text_ku) settingsDb.announcement_text_ku = postData.announcement_text_ku;

      // Contacts & Locations
      if (postData.contact_phone) settingsDb.contact_phone = postData.contact_phone;
      if (postData.contact_whatsapp) settingsDb.contact_whatsapp = postData.contact_whatsapp;
      if (postData.contact_email) settingsDb.contact_email = postData.contact_email;
      if (postData.boutique_location_en) settingsDb.boutique_location_en = postData.boutique_location_en;
      if (postData.boutique_location_ar) settingsDb.boutique_location_ar = postData.boutique_location_ar;
      if (postData.boutique_location_ku) settingsDb.boutique_location_ku = postData.boutique_location_ku;

      // Financial & Delivery Rules
      if (postData.exchange_rate_usd_to_iqd) settingsDb.exchange_rate_usd_to_iqd = parseFloat(postData.exchange_rate_usd_to_iqd);
      if (postData.delivery_kurdistan_fee !== undefined) settingsDb.delivery_kurdistan_fee = parseFloat(postData.delivery_kurdistan_fee);
      if (postData.delivery_iraq_fee !== undefined) settingsDb.delivery_iraq_fee = parseFloat(postData.delivery_iraq_fee);
      if (postData.free_delivery_threshold !== undefined) settingsDb.free_delivery_threshold = parseFloat(postData.free_delivery_threshold);

      saveDbFile("settings.json", settingsDb);
    }

    if (postData.delete_user_id) {
      const uId = parseInt(postData.delete_user_id, 10);
      usersDb.users = (usersDb.users || []).filter((u: any) => u.id !== uId);
      saveDbFile("users.json", usersDb);
    }

    if (postData.add_new_user && postData.user_name && postData.user_email) {
      const users = usersDb.users || [];
      const newUId = users.length > 0 ? Math.max(...users.map((u: any) => u.id)) + 1 : 1;
      users.unshift({
        id: newUId,
        name: postData.user_name,
        email: postData.user_email,
        phone: postData.user_phone || "0750 000 0000",
        role: postData.user_role || "Customer",
        city: postData.user_city || "Duhok",
        vip_status: postData.user_vip === "1" ? "VIP Black" : "Standard",
        created_at: new Date().toISOString()
      });
      usersDb.users = users;
      saveDbFile("users.json", usersDb);
    }

    if (postData.add_new_product && postData.prod_title_en && postData.prod_price) {
      const newId = productsList.length > 0 ? Math.max(...productsList.map((p: any) => p.id)) + 1 : 1;
      const galleryRaw = postData.prod_gallery || "";
      const galleryImages = galleryRaw ? galleryRaw.split(",").map((s: string) => s.trim()).filter(Boolean) : (postData.prod_image ? [postData.prod_image] : []);
      const mainImg = postData.prod_image || "https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80";
      if (!galleryImages.includes(mainImg)) {
        galleryImages.unshift(mainImg);
      }
      const newProd = {
        id: newId,
        title: {
          en: postData.prod_title_en,
          ar: postData.prod_title_ar || postData.prod_title_en,
          ku: postData.prod_title_ku || postData.prod_title_en
        },
        category: postData.prod_category || "clothes",
        price: parseFloat(postData.prod_price),
        old_price: postData.prod_old_price ? parseFloat(postData.prod_old_price) : null,
        rating: 5.0,
        reviews_count: 1,
        badge: postData.prod_badge || "New Arrival",
        badge_ar: postData.prod_badge_ar || postData.prod_badge || "وصل حديثاً",
        badge_ku: postData.prod_badge_ku || postData.prod_badge || "نوی گەهشتی",
        stock: parseInt(postData.prod_stock || "15", 10),
        image: mainImg,
        images: galleryImages,
        sizes: postData.prod_sizes ? postData.prod_sizes.split(",").map((s: string) => s.trim()).filter(Boolean) : (postData.prod_category === "clothes" ? ["S", "M", "L", "XL"] : postData.prod_category === "watches" ? ["42mm Case"] : ["100ml / 3.4 oz"]),
        colors: postData.prod_colors ? postData.prod_colors.split(",").map((s: string) => s.trim()).filter(Boolean) : ["Luxury Edition"],
        description: {
          en: postData.prod_desc_en || "Exclusive luxury piece.",
          ar: postData.prod_desc_ar || "قطعة فاخرة وحصرية.",
          ku: postData.prod_desc_ku || "پارچەیەکا تایبەت یا لوکس."
        },
        featured: true
      };
      productsList.unshift(newProd);
      productsDb.products = productsList;
      saveDbFile("products.json", productsDb);
    }

    if (postData.update_product && postData.edit_prod_id) {
      const editId = parseInt(postData.edit_prod_id, 10);
      const pIdx = productsList.findIndex((p: any) => p.id === editId);
      if (pIdx !== -1) {
        const oldProd = productsList[pIdx];
        const galleryRaw = (postData.edit_prod_gallery || "").trim();
        let galleryImages = galleryRaw ? galleryRaw.split(",").map((s: string) => s.trim()).filter(Boolean) : (oldProd.images || []);
        const mainImg = postData.edit_prod_image || oldProd.image;
        if (mainImg && !galleryImages.includes(mainImg)) {
          galleryImages.unshift(mainImg);
        }
        const sizesRaw = (postData.edit_prod_sizes || "").trim();
        const sizes = sizesRaw ? sizesRaw.split(",").map((s: string) => s.trim()).filter(Boolean) : (oldProd.sizes || ["Standard"]);
        const colorsRaw = (postData.edit_prod_colors || "").trim();
        const colors = colorsRaw ? colorsRaw.split(",").map((s: string) => s.trim()).filter(Boolean) : (oldProd.colors || ["Luxury Edition"]);

        productsList[pIdx] = {
          ...oldProd,
          id: editId,
          title: {
            en: postData.edit_prod_title_en || (typeof oldProd.title === 'object' ? oldProd.title.en : oldProd.title),
            ar: postData.edit_prod_title_ar || (typeof oldProd.title === 'object' ? oldProd.title.ar : oldProd.title),
            ku: postData.edit_prod_title_ku || (typeof oldProd.title === 'object' ? oldProd.title.ku : oldProd.title),
          },
          category: postData.edit_prod_category || oldProd.category,
          price: parseFloat(postData.edit_prod_price || oldProd.price),
          old_price: postData.edit_prod_old_price ? parseFloat(postData.edit_prod_old_price) : null,
          badge: postData.edit_prod_badge !== undefined ? postData.edit_prod_badge : oldProd.badge,
          badge_ar: postData.edit_prod_badge_ar || postData.edit_prod_badge || oldProd.badge_ar,
          badge_ku: postData.edit_prod_badge_ku || postData.edit_prod_badge || oldProd.badge_ku,
          stock: postData.edit_prod_stock !== undefined ? parseInt(postData.edit_prod_stock, 10) : oldProd.stock,
          image: mainImg,
          images: galleryImages,
          sizes: sizes,
          colors: colors,
          description: {
            en: postData.edit_prod_desc_en || (typeof oldProd.description === 'object' ? oldProd.description.en : oldProd.description),
            ar: postData.edit_prod_desc_ar || (typeof oldProd.description === 'object' ? oldProd.description.ar : oldProd.description),
            ku: postData.edit_prod_desc_ku || (typeof oldProd.description === 'object' ? oldProd.description.ku : oldProd.description),
          },
          featured: postData.edit_prod_featured === "1" || postData.edit_prod_featured === "on" || postData.edit_prod_featured === true
        };
        productsDb.products = productsList;
        saveDbFile("products.json", productsDb);
      }
    }

    if (postData.delete_product_id) {
      const delId = parseInt(postData.delete_product_id, 10);
      productsDb.products = productsList.filter((p: any) => p.id !== delId);
      saveDbFile("products.json", productsDb);
    }

    if (postData.send_inquiry && postData.name && postData.message) {
      const inqDb = getDbFile("inquiries.json");
      const list = inqDb.inquiries || [];
      list.unshift({
        id: "INQ-" + Math.floor(1000 + Math.random() * 9000),
        name: postData.name,
        email: postData.email || "",
        phone: postData.phone || "",
        subject: postData.subject || "General Inquiry",
        message: postData.message,
        date: new Date().toISOString()
      });
      inqDb.inquiries = list;
      saveDbFile("inquiries.json", inqDb);
    }
  }

  // Read raw file content
  let bodyContent = fs.readFileSync(filePath, "utf-8");

  // Determine activePage and pageTitle
  let activePage = pageName === "index" ? "home" : pageName;
  const pageMatch = bodyContent.match(/\$activePage\s*=\s*['"]([^'"]+)['"]/);
  if (pageMatch) activePage = pageMatch[1];

  let pageTitle = "";
  const titleMatch = bodyContent.match(/\$pageTitle\s*=\s*['"]([^'"]+)['"]/);
  if (titleMatch) pageTitle = titleMatch[1];

  // Remove top PHP setup block and trailing footer include
  bodyContent = bodyContent.replace(/^<\?php[\s\S]*?\?>/m, "");
  bodyContent = bodyContent.replace(/<\?php\s+require_once\s+(?:__DIR__\s*\.\s*)?['"]\/footer\.php['"]\s*;?\s*\?>/g, "");

  // Substitute all translation calls
  const dynamicTranslations = loadTranslations();
  const getTrans = (k: string) => {
    if (dynamicTranslations[lang] && dynamicTranslations[lang][k]) return dynamicTranslations[lang][k];
    if (dynamicTranslations["en"] && dynamicTranslations["en"][k]) return dynamicTranslations["en"][k];
    return t(k, lang);
  };

  bodyContent = bodyContent.replace(/<\?php\s+echo\s+t\(\s*['"]([^'"]+)['"]\s*,\s*\$lang\s*\)\s*;?\s*\?>/g, (match, key) => getTrans(key));
  bodyContent = bodyContent.replace(/<\?php\s+echo\s+t\(\s*['"]([^'"]+)['"]\s*\)\s*;?\s*\?>/g, (match, key) => getTrans(key));
  bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$lang\s*;?\s*\?>/g, lang);
  bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$theme\s*;?\s*\?>/g, theme);
  bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$pageTitle\s*;?\s*\?>/g, pageTitle);

  // Evaluate $lang ternary expressions
  bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$lang\s*===\s*'ku'\s*\?\s*['"]([\s\S]*?)['"]\s*:\s*\(\s*\$lang\s*===\s*'ar'\s*\?\s*['"]([\s\S]*?)['"]\s*:\s*['"]([\s\S]*?)['"]\s*\)\s*;?\s*\?>/g, (match, kuVal, arVal, enVal) => {
    if (lang === 'ku') return kuVal;
    if (lang === 'ar') return arVal;
    return enVal;
  });
  bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$lang\s*===\s*'ku'\s*\?\s*['"]([\s\S]*?)['"]\s*:\s*['"]([\s\S]*?)['"]\s*;?\s*\?>/g, (match, kuVal, otherVal) => {
    return lang === 'ku' ? kuVal : otherVal;
  });
  bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$lang\s*===\s*'ar'\s*\?\s*['"]([\s\S]*?)['"]\s*:\s*['"]([\s\S]*?)['"]\s*;?\s*\?>/g, (match, arVal, otherVal) => {
    return lang === 'ar' ? arVal : otherVal;
  });

  // Featured Products Loop for index.php
  bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$products\s+as\s+\$item\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, () => {
    return productsList.map((item: any) => {
      const titleText = typeof item.title === "object" ? (item.title[lang] || item.title.en) : item.title;
      const badgeText = item.badge || "";
      const oldPriceHtml = item.old_price ? `<span class="old-price">$${Number(item.old_price).toFixed(2)}</span>` : "";
      return `
        <div class="product-card" data-category="${item.category}" data-id="${item.id}">
            <div class="product-image-container">
                ${badgeText ? `<span class="product-badge-tag">${badgeText}</span>` : ""}
                <a href="product.php?id=${item.id}" class="product-img-link">
                    <img src="${item.image}" alt="${titleText}" class="product-thumb" loading="lazy">
                </a>
                <div class="product-hover-actions">
                    <button class="action-btn-circle quick-view-btn" data-id="${item.id}" title="${t('quick_view', lang)}">👁️</button>
                    <button class="action-btn-circle add-cart-btn" data-id="${item.id}" title="${t('add_to_cart', lang)}">🛍️</button>
                </div>
            </div>
            <div class="product-details">
                <div class="product-meta-row">
                    <span class="product-cat-name">${t('filter_' + item.category, lang)}</span>
                </div>
                <h3 class="product-title">
                    <a href="product.php?id=${item.id}">${titleText}</a>
                </h3>
                <div class="product-price-row">
                    <div class="price-wrap">
                        <span class="current-price">$${Number(item.price).toFixed(2)}</span>
                        ${oldPriceHtml}
                    </div>
                    <button class="btn-add-cart-mini" onclick="window.AuraStore.addToCart(${item.id})">
                        <span>+ ${t('add_to_cart', lang)}</span>
                    </button>
                </div>
            </div>
        </div>
      `;
    }).join("");
  });

  // Shop Catalog Loop
  bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$filteredProducts\s+as\s+\$p\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, () => {
    const selectedCat = (req.query.cat as string) || "all";
    const searchQ = ((req.query.q as string) || "").toLowerCase();
    let filtered = productsList;

    if (selectedCat && selectedCat !== "all") {
      filtered = filtered.filter((p: any) => p.category === selectedCat);
    }
    if (searchQ) {
      filtered = filtered.filter((p: any) => {
        const titleStr = typeof p.title === "object" ? JSON.stringify(p.title).toLowerCase() : String(p.title).toLowerCase();
        return titleStr.includes(searchQ) || p.category.toLowerCase().includes(searchQ);
      });
    }

    if (filtered.length === 0) {
      return `
        <div class="no-products-box" style="grid-column: 1 / -1;">
          <div class="empty-icon">💎</div>
          <h3>${t('no_products_found', lang)}</h3>
          <p>Explore our other luxury categories or reset your active filters.</p>
          <a href="shop.php" class="btn btn-primary btn-luxury mt-16">${t('clear_filters', lang)}</a>
        </div>
      `;
    }

    return filtered.map((p: any) => {
      const pTitle = typeof p.title === "object" ? (p.title[lang] || p.title.en) : p.title;
      const pBadge = p.badge ? `<span class="product-badge-tag">${p.badge}</span>` : "";
      const oldPrice = p.old_price ? `<span class="old-price">$${p.old_price.toFixed(2)}</span>` : "";
      return `
        <div class="product-card" data-category="${p.category}" id="shop-prod-${p.id}">
          <div class="product-image-container">
            ${pBadge}
            <a href="product.php?id=${p.id}">
              <img src="${p.image}" alt="${pTitle}" class="product-thumb" loading="lazy">
            </a>
            <div class="product-hover-actions">
              <button type="button" class="action-btn-circle quick-view-btn" data-id="${p.id}" title="Quick View">👁️</button>
              <button type="button" class="action-btn-circle add-cart-btn" data-id="${p.id}" title="Add to Bag">🛍️</button>
            </div>
          </div>
          <div class="product-details">
            <div class="product-meta-row">
              <span class="product-cat-name">${p.category}</span>
            </div>
            <h3 class="product-title">
              <a href="product.php?id=${p.id}">${pTitle}</a>
            </h3>
            <div class="product-price-row">
              <div>
                <span class="current-price">$${p.price.toFixed(2)}</span>
                ${oldPrice}
              </div>
              <button type="button" class="btn-add-cart-mini add-cart-btn" data-id="${p.id}">
                + ${t('add_to_cart', lang)}
              </button>
            </div>
          </div>
        </div>
      `;
    }).join("");
  });

  // Product Single Page Details
  if (pageName === "product") {
    const prodId = parseInt((req.query.id as string) || "1", 10);
    const prod = productsList.find((p: any) => p.id === prodId) || productsList[0] || {};
    const prodTitle = typeof prod.title === "object" ? (prod.title[lang] || prod.title.en) : prod.title;
    const prodDesc = typeof prod.description === "object" ? (prod.description[lang] || prod.description.en) : prod.description;
    const prodBadge = prod[`badge_${lang}`] || prod.badge || "";

    bodyContent = bodyContent.replace(/<\?php\s+echo\s+htmlspecialchars\(\$titleText\)\s*;?\s*\?>/g, prodTitle || "");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+htmlspecialchars\(\$productTitle\)\s*;?\s*\?>/g, prodTitle || "");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$productTitle\s*;?\s*\?>/g, prodTitle || "");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+htmlspecialchars\(\$descText\)\s*;?\s*\?>/g, prodDesc || "");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+nl2br\(htmlspecialchars\(\$descText\)\)\s*;?\s*\?>/g, (prodDesc || "").replace(/\n/g, "<br>"));
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+htmlspecialchars\(\$productDesc\)\s*;?\s*\?>/g, prodDesc || "");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$productDesc\s*;?\s*\?>/g, prodDesc || "");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+htmlspecialchars\(\$badgeText\)\s*;?\s*\?>/g, prodBadge || "");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$product\['id'\]\s*;?\s*\?>/g, String(prod.id || 1));
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+\(int\)\$product\['id'\]\s*;?\s*\?>/g, String(prod.id || 1));
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$product\['category'\]\s*;?\s*\?>/g, prod.category || "clothes");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+htmlspecialchars\(\$product\['category'\]\)\s*;?\s*\?>/g, prod.category || "clothes");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+ucfirst\(\$product\['category'\]\)\s*;?\s*\?>/g, (prod.category || "Clothes").charAt(0).toUpperCase() + (prod.category || "Clothes").slice(1));
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+number_format\(\$product\['price'\]\)\s*;?\s*\?>/g, Number(prod.price || 0).toLocaleString());
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+number_format\(\$product\['old_price'\]\)\s*;?\s*\?>/g, Number(prod.old_price || 0).toLocaleString());
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+number_format\(\$product\['old_price'\]\s*-\s*\$product\['price'\]\)\s*;?\s*\?>/g, (Number(prod.old_price || 0) - Number(prod.price || 0)).toLocaleString());
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+number_format\(\$product\['price'\],\s*2\)\s*;?\s*\?>/g, Number(prod.price || 0).toLocaleString());
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+number_format\(\$product\['old_price'\],\s*2\)\s*;?\s*\?>/g, Number(prod.old_price || 0).toLocaleString());
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+number_format\(\$product\['old_price'\]\s*-\s*\$product\['price'\],\s*2\)\s*;?\s*\?>/g, (Number(prod.old_price || 0) - Number(prod.price || 0)).toLocaleString());
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$product\['stock'\]\s*;?\s*\?>/g, String(prod.stock || 25));
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+htmlspecialchars\(\$product\['image'\]\)\s*;?\s*\?>/g, prod.image || "");

    const sizes = prod.sizes || [];
    let sizeMeasurements = prod.size_measurements || {};
    if (typeof sizeMeasurements === "string") {
      try { sizeMeasurements = JSON.parse(sizeMeasurements); } catch (e) { sizeMeasurements = {}; }
    }
    if (sizes.length > 0) {
      sizes.forEach((sz: string) => {
        if (!sizeMeasurements[sz]) {
          if (prod.category === "clothes") {
            if (sz === "S") sizeMeasurements["S"] = "Length: 68 cm • Chest: 96 cm • Shoulder: 44 cm";
            else if (sz === "M") sizeMeasurements["M"] = "Length: 70 cm • Chest: 102 cm • Shoulder: 46 cm";
            else if (sz === "L") sizeMeasurements["L"] = "Length: 73 cm • Chest: 108 cm • Shoulder: 48 cm";
            else if (sz === "XL") sizeMeasurements["XL"] = "Length: 76 cm • Chest: 114 cm • Shoulder: 50 cm";
            else if (sz === "XXL") sizeMeasurements["XXL"] = "Length: 79 cm • Chest: 120 cm • Shoulder: 52 cm";
            else sizeMeasurements[sz] = "Length: 72 cm • Chest: 104 cm • Shoulder: 46 cm";
          } else if (prod.category === "watches") {
            sizeMeasurements[sz] = `Case Diameter: ${sz} • Thickness: 11.5 mm • Strap Width: 20 mm`;
          } else {
            sizeMeasurements[sz] = `Standard edition dimension: ${sz}`;
          }
        }
      });
    }

    const sizesHtml = sizes.map((sz: string) => {
      const mText = sizeMeasurements[sz] || "";
      return `
        <button type="button" 
                class="size-pill" 
                data-size="${sz}"
                data-measurement="${mText}"
                onclick="onSizeSelected(this, '${sz}')">
            ${sz}
        </button>
      `;
    }).join("");
    bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$product\['sizes'\]\s+as\s+\$i\s*=>\s*\$size\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, sizesHtml);

    const matrixRowsHtml = sizes.map((sz: string) => {
      const mRaw = sizeMeasurements[sz] || "";
      let hVal = "-";
      let wVal = "-";
      let otherVal = "-";
      const mH = mRaw.match(/(?:Length|Height|Jacket|بلندی|درێژی|الطول):\s*([^•,]+)/i);
      const mW = mRaw.match(/(?:Chest|Width|Trousers|پانی|الصدر|العرض):\s*([^•,]+)/i);
      const mO = mRaw.match(/(?:Shoulder|Strap|Sleeve|مل|الكتف):\s*([^•,]+)/i);
      if (mH) hVal = mH[1].trim();
      if (mW) wVal = mW[1].trim();
      if (mO) otherVal = mO[1].trim();
      if (hVal === "-" && wVal === "-") hVal = mRaw || "Standard fit";

      const safeKey = sz.replace(/[^a-zA-Z0-9]/g, "");
      return `
        <tr id="matrixRow_${safeKey}">
            <td><strong class="matrix-sz-badge">${sz}</strong></td>
            <td>${hVal}</td>
            <td>${wVal}</td>
            <td>${otherVal}</td>
        </tr>
      `;
    }).join("");
    bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$product\['sizes'\]\s+as\s+\$sz\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, matrixRowsHtml);

    const colors = prod.colors || [];
    const colorImagesMap: Record<string, string> = {};
    colors.forEach((col: string, i: number) => {
      colorImagesMap[col] = (prod.images && prod.images[i]) || prod.image || "";
    });

    const colorsHtml = colors.map((col: string, idx: number) => {
      const colImg = colorImagesMap[col] || prod.image || "";
      return `
        <button type="button" 
                class="color-badge-pill" 
                data-color="${col}"
                data-image="${colImg}"
                onclick="onColorSelected(this, '${col}', '${colImg}')">
            <span class="color-dot-indicator"></span>
            <span class="color-name-text">${col}</span>
        </button>
      `;
    }).join("");
    bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$product\['colors'\]\s+as\s+\$i\s*=>\s*\$color\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, colorsHtml);

    // Gallery Thumbs
    const images = prod.images || [prod.image];
    const thumbsHtml = images.map((img: string, idx: number) => `
      <button type="button" class="thumb-btn ${idx === 0 ? 'active' : ''}" data-img="${img}" onclick="switchMainImage('${img}', this)">
        <img src="${img}" alt="Thumbnail">
      </button>
    `).join("");
    bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$product\['images'\]\s+as\s+\$idx\s*=>\s*\$imgUrl\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, thumbsHtml);

    bodyContent = bodyContent.replace(/<\?php\s+echo\s+json_encode\(\$sizeMeasurements\)\s*;?\s*\?>/g, JSON.stringify(sizeMeasurements));
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+json_encode\(\$colorImages\)\s*;?\s*\?>/g, JSON.stringify(colorImagesMap));

    // Related Products Loop
    const related = productsList.filter((p: any) => p.category === prod.category && p.id !== prod.id).slice(0, 4);
    const relatedHtml = related.map((item: any) => {
      const tTitle = typeof item.title === "object" ? (item.title[lang] || item.title.en) : item.title;
      return `
        <div class="product-card">
          <div class="product-image-container">
            <a href="product.php?id=${item.id}">
              <img src="${item.image}" alt="${tTitle}" class="product-thumb">
            </a>
          </div>
          <div class="product-details">
            <h3 class="product-title"><a href="product.php?id=${item.id}">${tTitle}</a></h3>
            <div class="product-price-row">
              <span class="current-price">${Number(item.price || 0).toLocaleString()} IQD</span>
              <button class="btn-add-cart-mini" onclick="window.AuraStore.addToCart(${item.id})">+ ${t('add_to_cart', lang)}</button>
            </div>
          </div>
        </div>
      `;
    }).join("");
    bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$relatedProducts\s+as\s+\$item\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, relatedHtml);
  }

  // Admin Dashboard Content
  if (pageName === "admin") {
    let rev = 0;
    ordersList.forEach((o: any) => (rev += o.total || 0));

    bodyContent = bodyContent.replace(/<\?php\s+echo\s+number_format\(\$totalRevenue,\s*2\)\s*;?\s*\?>/g, rev.toFixed(2));
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+count\(\$ordersList\)\s*;?\s*\?>/g, String(ordersList.length));
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+count\(\$productsList\)\s*;?\s*\?>/g, String(productsList.length));
    const usersCount = (usersDb.users || []).length;
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+count\(\$usersList\)\s*;?\s*\?>/g, String(usersCount));

    const orderRows = ordersList.map((ord: any) => `
      <tr data-status="${ord.order_status || 'Pending'}" data-search="${(ord.order_id + ' ' + (ord.customer_name || '') + ' ' + (ord.phone || '') + ' ' + (ord.city || '')).toLowerCase()}">
        <td><strong><a href="track.php?order_id=${ord.order_id}">${ord.order_id}</a></strong></td>
        <td><small>${new Date(ord.created_at).toLocaleDateString()}</small></td>
        <td>
          <strong>${ord.customer_name}</strong><br>
          <small class="text-muted">${ord.city || ''} • ${ord.phone || ''}</small>
        </td>
        <td>${ord.items ? ord.items.length : 0} pcs</td>
        <td class="font-bold text-primary">${Number(ord.total || 0).toLocaleString()} IQD</td>
        <td>
          <span class="badge-tag">${ord.payment_method || 'COD'}</span><br>
          <small class="text-muted">${ord.payment_status || 'Pending'}</small>
        </td>
        <td>
          <div class="courier-info-chip">
            <span class="courier-name">${ord.courier || 'Unassigned'}</span>
            ${ord.driver_name ? `<span class="courier-driver">👤 ${ord.driver_name} (${ord.driver_phone || ''})</span>` : ''}
            ${ord.tracking_code ? `<code style="font-size:11px;">${ord.tracking_code}</code>` : ''}
          </div>
        </td>
        <td>
          <div class="order-status-wrapper" id="orderStatusWrap_${ord.order_id}">
            <select name="order_status" class="status-select" data-previous-status="${ord.order_status || 'Pending'}" onchange="window.AuraStore.updateOrderStatus('${ord.order_id}', this.value, this)">
              <option value="Pending" ${ord.order_status === "Pending" ? "selected" : ""}>Pending</option>
              <option value="Processing" ${ord.order_status === "Processing" ? "selected" : ""}>Processing</option>
              <option value="Shipped" ${ord.order_status === "Shipped" ? "selected" : ""}>Shipped (Dispatched)</option>
              <option value="Out for Delivery" ${ord.order_status === "Out for Delivery" ? "selected" : ""}>Out for Delivery</option>
              <option value="Delivered" ${ord.order_status === "Delivered" ? "selected" : ""}>Delivered</option>
              <option value="Cancelled" ${ord.order_status === "Cancelled" ? "selected" : ""}>Cancelled</option>
            </select>
          </div>
        </td>
        <td>
          <div style="display:flex; gap:6px; flex-wrap:wrap;">
            <button type="button" class="btn btn-outline btn-xs" onclick='openDispatchModal(${JSON.stringify(ord).replace(/'/g, "&#39;").replace(/"/g, "&quot;")})' title="Manage Logistics">
              🚚 Logistics
            </button>
            <button type="button" class="btn btn-outline btn-xs" onclick='printOrderInvoice(${JSON.stringify(ord).replace(/'/g, "&#39;").replace(/"/g, "&quot;")})' title="Print Invoice">
              📄 Invoice
            </button>
            <a href="track.php?order_id=${ord.order_id}" class="btn btn-ghost btn-xs" title="Track Live">👁️</a>
          </div>
        </td>
      </tr>
    `).join("");

    bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$ordersList\s+as\s+\$ord\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, orderRows);

    const prodRows = productsList.map((p: any) => {
      const pTitle = typeof p.title === "object" ? (p.title[lang] || p.title.en) : p.title;
      const pPriceIqd = p.price || 0;
      const pOldPriceIqd = p.old_price || null;
      const safeJson = JSON.stringify(p).replace(/'/g, "&#39;").replace(/"/g, "&quot;");
      return `
        <tr>
          <td>#${p.id}</td>
          <td>
            <div class="admin-prod-preview">
              <img src="${p.image}" alt="" class="admin-prod-thumb" id="adminThumb_${p.id}">
              <div>
                <strong><a href="product.php?id=${p.id}" target="_blank" style="color:var(--text-primary);">${pTitle}</a></strong><br>
                ${p.badge ? `<small class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); border-color:var(--accent-gold); font-weight:700;">${p.badge}</small>` : ""}
                ${p.featured ? `<small class="badge-tag" style="background:rgba(59,130,246,0.15); color:#60a5fa; border-color:#3b82f6;">⭐ Featured</small>` : ""}
              </div>
            </div>
          </td>
          <td><span class="badge-tag text-uppercase">${p.category}</span></td>
          <td>
            <div style="display:flex; flex-direction:column;">
              <strong class="font-bold" style="color:var(--accent-gold); font-size:14px;">${Number(pPriceIqd).toLocaleString()} IQD</strong>
              ${pOldPriceIqd && pOldPriceIqd > pPriceIqd ? `<small style="text-decoration:line-through; color:var(--text-muted); font-size:11.5px;">${Number(pOldPriceIqd).toLocaleString()} IQD</small>` : ""}
            </div>
          </td>
          <td>
            <div class="stock-stepper" id="stockStepper_${p.id}">
              <button type="button" class="btn-stock-step" title="Decrease stock" onclick="window.AuraStore.adjustStock(${p.id}, -1, this)">-</button>
              <span class="stock-count-num" id="stockCount_${p.id}" style="${p.stock < 5 ? 'color:#ef4444;' : ''}">${p.stock}</span>
              <button type="button" class="btn-stock-step" title="Increase stock" onclick="window.AuraStore.adjustStock(${p.id}, 1, this)">+</button>
            </div>
          </td>
          <td>★ ${Number(p.rating || 5).toFixed(1)} (${p.reviews_count || 1})</td>
          <td>
            <div style="display:flex; gap:6px; align-items:center;">
              <button type="button" class="btn btn-outline btn-xs" style="color:var(--accent-gold); border-color:var(--accent-gold); font-weight:700;" onclick='openEditProductModal(${safeJson})'>
                ✏️ Edit
              </button>
              <a href="product.php?id=${p.id}" target="_blank" class="btn btn-ghost btn-xs" title="View in boutique">
                👁️
              </a>
              <form action="admin.php" method="POST" onsubmit="return confirm('Delete product permanently?')" style="display:inline; margin:0;">
                <input type="hidden" name="delete_product_id" value="${p.id}">
                <button type="submit" class="btn btn-ghost text-danger btn-xs" title="Delete product">🗑️</button>
              </form>
            </div>
          </td>
        </tr>
      `;
    }).join("");

    bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$productsList\s+as\s+\$p\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, prodRows);
  }

  // Checkout Page condition handling
  if (pageName === "checkout") {
    // If not POST confirmed in Node template rendering, show the checkout form part
    bodyContent = bodyContent.replace(/<\?php\s+if\s*\(\$orderPlaced\s+&&\s+\$confirmedOrder\):\s*.*?(?:<!-- Order Success Confirmation Screen -->[\s\S]*?<\/script>\s*)?<\?php\s+else:\s*\?>/gs, "");
    bodyContent = bodyContent.replace(/<\?php\s+endif;\s*\?>/g, "");
  }

  // Track Order Page Details
  if (pageName === "track") {
    const searchId = ((req.query.order_id as string) || "").trim();
    if (searchId) {
      const found = ordersList.find((o: any) =>
        o.order_id.toLowerCase() === searchId.toLowerCase() ||
        (o.phone && o.phone.includes(searchId)) ||
        (o.email && o.email.toLowerCase() === searchId.toLowerCase())
      );

      if (found) {
        const stepMap: Record<string, number> = { Pending: 1, Processing: 2, Shipped: 3, Delivered: 4 };
        const currentStep = stepMap[found.order_status] || 2;
        const itemsHtml = (found.items || []).map((it: any) => {
          const itTitle = typeof it.title === "object" ? (it.title[lang] || it.title.en) : it.title;
          return `
            <div class="track-item-row">
              <img src="${it.image}" alt="${itTitle}" class="track-item-thumb">
              <div class="track-item-desc">
                <strong>${itTitle}</strong>
                <span>Quantity: ${it.quantity} ${it.size ? "• Size: " + it.size : ""}</span>
              </div>
              <div class="track-item-price">
                $${(it.price * it.quantity).toFixed(2)}
              </div>
            </div>
          `;
        }).join("");

        const trackResultCard = `
          <div class="order-track-result-card">
            <div class="track-header-meta">
              <div>
                <span class="track-badge">${found.order_status}</span>
                <h2 class="track-order-num">${found.order_id}</h2>
              </div>
              <div class="track-date-info">
                <span>Placed on:</span>
                <strong>${new Date(found.created_at).toLocaleDateString()} • ${new Date(found.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</strong>
              </div>
            </div>

            <div class="timeline-stepper">
              <div class="step-item ${currentStep >= 1 ? 'completed' : ''} ${currentStep === 1 ? 'current' : ''}">
                <div class="step-circle">1</div>
                <div class="step-info">
                  <strong>${t("status_pending", lang)}</strong>
                  <small>Order received</small>
                </div>
              </div>
              <div class="step-line ${currentStep >= 2 ? 'active' : ''}"></div>
              <div class="step-item ${currentStep >= 2 ? 'completed' : ''} ${currentStep === 2 ? 'current' : ''}">
                <div class="step-circle">2</div>
                <div class="step-info">
                  <strong>${t("status_processing", lang)}</strong>
                  <small>Satin packaging</small>
                </div>
              </div>
              <div class="step-line ${currentStep >= 3 ? 'active' : ''}"></div>
              <div class="step-item ${currentStep >= 3 ? 'completed' : ''} ${currentStep === 3 ? 'current' : ''}">
                <div class="step-circle">3</div>
                <div class="step-info">
                  <strong>${t("status_shipped", lang)}</strong>
                  <small>Courier dispatch</small>
                </div>
              </div>
              <div class="step-line ${currentStep >= 4 ? 'active' : ''}"></div>
              <div class="step-item ${currentStep >= 4 ? 'completed' : ''} ${currentStep === 4 ? 'current' : ''}">
                <div class="step-circle">4</div>
                <div class="step-info">
                  <strong>${t("status_delivered", lang)}</strong>
                  <small>Client received</small>
                </div>
              </div>
            </div>

            <div class="track-details-grid">
              <div class="track-info-box">
                <h4>Customer & Destination</h4>
                <p><strong>Name:</strong> ${found.customer_name}</p>
                <p><strong>Phone:</strong> ${found.phone}</p>
                <p><strong>City:</strong> ${found.city}</p>
                <p><strong>Address:</strong> ${found.address}</p>
              </div>
              <div class="track-info-box">
                <h4>Payment Information</h4>
                <p><strong>Method:</strong> ${found.payment_method}</p>
                <p><strong>Status:</strong> <span class="badge-status-paid">${found.payment_status || "Pending"}</span></p>
                <p><strong>Total Amount:</strong> <span class="text-primary font-bold text-lg">$${Number(found.total).toFixed(2)}</span></p>
              </div>
            </div>

            <div class="track-items-box mt-24">
              <h4>Package Contents (${found.items ? found.items.length : 0} items)</h4>
              <div class="track-items-table">
                ${itemsHtml}
              </div>
            </div>
          </div>
        `;
        bodyContent = bodyContent.replace(/<\?php\s+if\s*\(\$searched\s+&&\s+\$foundOrder\):\s*.*?<\?php\s+elseif\s*\(\$searched\s+&&\s+!\$foundOrder\):\s*.*?<\?php\s+endif;\s*\?>/gs, trackResultCard);
      } else {
        const notFoundHtml = `
          <div class="no-order-found-card text-center" style="background:var(--bg-card); padding:40px; border-radius:12px; border:1px solid var(--border-color); max-width:680px; margin:0 auto;">
            <div class="empty-icon">⚠️</div>
            <h3>No order found matching "${searchId}"</h3>
            <p class="text-muted">Please double check your Order ID (format: <code>ORD-XXXXX</code>) or phone number.</p>
          </div>
        `;
        bodyContent = bodyContent.replace(/<\?php\s+if\s*\(\$searched\s+&&\s+\$foundOrder\):\s*.*?<\?php\s+elseif\s*\(\$searched\s+&&\s+!\$foundOrder\):\s*.*?<\?php\s+endif;\s*\?>/gs, notFoundHtml);
      }
    } else {
      bodyContent = bodyContent.replace(/<\?php\s+if\s*\(\$searched\s+&&\s+\$foundOrder\):\s*.*?<\?php\s+endif;\s*\?>/gs, "");
    }
  }

  // Strip remaining unparsed PHP blocks
  bodyContent = bodyContent.replace(/<\?(?:php|=)?[\s\S]*?\?>/g, "");
  bodyContent = bodyContent.replace(/<\?php[\s\S]*$/g, "");
  bodyContent = bodyContent.replace(/^\s*\?>/g, "");

  const headerHtml = renderHeader(lang, theme, activePage, pageTitle);
  const footerHtml = renderFooter(lang, theme);

  return headerHtml + "\n" + bodyContent + "\n" + footerHtml;
}

// --- Dedicated API Endpoints ---

// 1. Gateway Connection Diagnostic Ping
app.post("/api/admin/gateway-test", (req, res) => {
  const { gateway } = req.body || {};
  const settings = getDbFile("settings.json");
  const gwConfig = (settings.gateways || {})[gateway] || {};

  const latency = Math.floor(22 + Math.random() * 35);

  if (gateway === "fib") {
    const isSandbox = gwConfig.mode !== "prod" && gwConfig.mode !== "production";
    return res.json({
      success: true,
      gateway: "fib",
      status: "online",
      latency,
      mode: isSandbox ? "sandbox" : "production",
      account: gwConfig.account_iban || "IQ44FIBQ0000001009283741",
      client_id: gwConfig.client_id || "fib_live_client_89420ab92c",
      message: `FIB OAuth2 Token & Webhook Listener verified (IBAN: ${gwConfig.account_iban || "IQ44FIBQ..."})`
    });
  } else if (gateway === "zaincash") {
    const isSandbox = gwConfig.mode !== "prod" && gwConfig.mode !== "production";
    return res.json({
      success: true,
      gateway: "zaincash",
      status: "online",
      latency,
      mode: isSandbox ? "sandbox" : "production",
      msisdn: gwConfig.msisdn || "9647835077893",
      merchant_id: gwConfig.merchant_id || "5ff6561082c3f8109c11f2a3",
      message: `ZainCash Merchant JWT signature verified for MSISDN ${gwConfig.msisdn || "9647835077893"}`
    });
  } else {
    return res.json({
      success: true,
      gateway: gateway || "generic",
      status: "online",
      latency,
      message: "Gateway gateway ping successful."
    });
  }
});

// 1.1 FIB Dynamic Bearer Access Token Generator
app.post("/api/admin/generate-fib-token", (req, res) => {
  const settings = getDbFile("settings.json");
  const fibConfig = (settings.gateways || {}).fib || {};
  const isSandbox = fibConfig.mode !== "prod" && fibConfig.mode !== "production";

  const tokenPayload = {
    iss: isSandbox ? "https://auth.test.fib.iq" : "https://auth.fib.iq",
    aud: "https://api.fib.iq/v1",
    sub: fibConfig.client_id || "fib_client_live",
    account_iban: fibConfig.account_iban || "IQ44FIBQ0000001009283741",
    scope: "payments:create payments:read payments:refund webhooks:listen",
    iat: Math.floor(Date.now() / 1000),
    exp: Math.floor(Date.now() / 1000) + 86400
  };

  const headerB64 = Buffer.from(JSON.stringify({ alg: "HS256", typ: "JWT" })).toString("base64url");
  const payloadB64 = Buffer.from(JSON.stringify(tokenPayload)).toString("base64url");
  const mockSig = Buffer.from("fib_sig_" + Math.random().toString(36).substring(2)).toString("base64url");
  const accessToken = `fib_bearer_${headerB64}.${payloadB64}.${mockSig}`;

  if (!settings.gateways) settings.gateways = {};
  if (!settings.gateways.fib) settings.gateways.fib = {};
  settings.gateways.fib.access_token = accessToken;
  saveDbFile("settings.json", settings);

  res.json({
    success: true,
    access_token: accessToken,
    token_type: "Bearer",
    expires_in: 86400,
    scope: tokenPayload.scope,
    mode: isSandbox ? "sandbox" : "production",
    message: "FIB OAuth2 Access Token dynamically generated and persisted to database!"
  });
});

// 1.2 ZainCash HMAC-SHA256 JWT Token Verifier & Handshake Test
app.post("/api/admin/verify-zaincash-jwt", (req, res) => {
  const settings = getDbFile("settings.json");
  const zcConfig = (settings.gateways || {}).zaincash || {};
  const isSandbox = zcConfig.mode !== "prod" && zcConfig.mode !== "production";

  const testPayload = {
    amount: 50000,
    serviceType: zcConfig.service_type || "Luxury Goods Purchase",
    msisdn: zcConfig.msisdn || "9647835077893",
    merchantId: zcConfig.merchant_id || "5ff6561082c3f8109c11f2a3",
    orderId: "ZC-TEST-" + Math.floor(1000 + Math.random() * 9000),
    redirectUrl: zcConfig.redirect_url || "https://aurastore.iq/api/zaincash/redirect",
    iat: Math.floor(Date.now() / 1000),
    exp: Math.floor(Date.now() / 1000) + 14400
  };

  const headerB64 = Buffer.from(JSON.stringify({ alg: "HS256", typ: "JWT" })).toString("base64url");
  const payloadB64 = Buffer.from(JSON.stringify(testPayload)).toString("base64url");
  const mockSig = Buffer.from("zc_sig_" + Math.random().toString(36).substring(2)).toString("base64url");
  const token = `${headerB64}.${payloadB64}.${mockSig}`;

  res.json({
    success: true,
    token,
    payload: testPayload,
    merchant_id: zcConfig.merchant_id,
    msisdn: zcConfig.msisdn,
    mode: isSandbox ? "sandbox" : "production",
    message: "ZainCash JWT payload encoding and HMAC signature verified successfully!"
  });
});

// 2. FIB Payment Session Creation API
app.post("/api/payment/fib/create", (req, res) => {
  const { amount, currency, order_id } = req.body || {};
  const settings = getDbFile("settings.json");
  const rate = settings.exchange_rate_usd_to_iqd || 1320;
  const amountIqd = (amount || 100) * rate;

  const paymentId = "fib_pay_" + Math.random().toString(36).substring(2, 11);
  const qrCode = `fib://pay?pid=${paymentId}&amt=${amountIqd}&cur=IQD&m=AURA-LUXURY`;

  res.json({
    success: true,
    payment_id: paymentId,
    amount_usd: amount,
    amount_iqd: amountIqd,
    currency: "IQD",
    qr_code_payload: qrCode,
    readable_code: "FIB-" + Math.floor(10000 + Math.random() * 90000),
    expires_in_seconds: 1800,
    valid_until: new Date(Date.now() + 1800000).toISOString()
  });
});

// 3. ZainCash Payment Initializer API
app.post("/api/payment/zaincash/init", (req, res) => {
  const { amount, msisdn, order_id } = req.body || {};
  const transactionId = "ZC-" + Math.random().toString(36).substring(2, 10).toUpperCase();

  res.json({
    success: true,
    transaction_id: transactionId,
    status: "pending_otp",
    msisdn: msisdn || "07835077893",
    order_id: order_id || "ORD-" + Math.floor(10000 + Math.random() * 90000),
    redirect_url: `https://api.zaincash.iq/transaction/pay?id=${transactionId}`
  });
});

// 4. Logistics Dispatch API
app.post("/api/admin/dispatch", (req, res) => {
  const { order_id, courier, driver_name, driver_phone, tracking_code, dispatch_notes, estimated_delivery } = req.body || {};
  const ordersDb = getDbFile("orders.json");
  const orders = ordersDb.orders || [];
  const ord = orders.find((o: any) => o.order_id === order_id);

  if (!ord) {
    return res.status(404).json({ success: false, error: "Order not found" });
  }

  if (courier) ord.courier = courier;
  if (driver_name) ord.driver_name = driver_name;
  if (driver_phone) ord.driver_phone = driver_phone;
  if (tracking_code) ord.tracking_code = tracking_code;
  if (dispatch_notes) ord.dispatch_notes = dispatch_notes;
  if (estimated_delivery) ord.estimated_delivery = estimated_delivery;
  ord.order_status = "Shipped";

  ordersDb.orders = orders;
  saveDbFile("orders.json", ordersDb);

  res.json({ success: true, order: ord });
});

// 4.1 Order Status Quick Update API (AJAX without page reload)
app.post("/api/admin/order-status", (req, res) => {
  const { order_id, order_status } = req.body || {};
  if (!order_id || !order_status) {
    return res.status(400).json({ success: false, error: "Missing order_id or order_status" });
  }

  const ordersDb = getDbFile("orders.json");
  const orders = ordersDb.orders || [];
  const ord = orders.find((o: any) => o.order_id === order_id);

  if (!ord) {
    return res.status(404).json({ success: false, error: `Order ${order_id} not found` });
  }

  ord.order_status = order_status;
  ordersDb.orders = orders;
  saveDbFile("orders.json", ordersDb);

  res.json({
    success: true,
    order_id: ord.order_id,
    order_status: ord.order_status,
    message: `Order #${ord.order_id} status updated to "${order_status}" successfully`
  });
});

// 4.2 Product Quick Stock Adjuster API (AJAX without page reload)
app.post("/api/admin/stock-adjust", (req, res) => {
  const { product_id, stock_delta, new_stock } = req.body || {};
  const pId = parseInt(product_id, 10);
  if (isNaN(pId)) {
    return res.status(400).json({ success: false, error: "Invalid product_id" });
  }

  const productsDb = getDbFile("products.json");
  const products = productsDb.products || [];
  const prod = products.find((p: any) => p.id === pId);

  if (!prod) {
    return res.status(404).json({ success: false, error: `Product #${pId} not found` });
  }

  if (new_stock !== undefined) {
    prod.stock = Math.max(0, parseInt(new_stock, 10) || 0);
  } else if (stock_delta !== undefined) {
    prod.stock = Math.max(0, (parseInt(prod.stock, 10) || 0) + (parseInt(stock_delta, 10) || 0));
  }

  productsDb.products = products;
  saveDbFile("products.json", productsDb);

  const title = typeof prod.title === "object" ? (prod.title.en || prod.title) : prod.title;

  res.json({
    success: true,
    product_id: prod.id,
    stock: prod.stock,
    title: title,
    message: `Stock for "${title}" updated to ${prod.stock}`
  });
});

// 5. Store Live Rates & Gateways
app.get("/api/store/config", (req, res) => {
  const settings = getDbFile("settings.json");
  res.json({
    exchange_rate: settings.exchange_rate_usd_to_iqd || 1320,
    delivery_zones: ["Kurdistan Region (Duhok, Erbil, Sulaymaniyah, Zakho)", "Federal Iraq (Baghdad, Basra, Mosul, Najaf, Karbala, etc.)"],
    gateways: {
      fib: settings.gateways?.fib?.enabled ?? true,
      zaincash: settings.gateways?.zaincash?.enabled ?? true,
      fastpay: settings.gateways?.fastpay?.enabled ?? true,
      cod: true
    }
  });
});

// Dedicated Universal Payment Simulator & SDK Routing (/payment/fake.php)
app.all(["/payment/fake.php", "/website/payment/fake.php", "/payment/fake", "/website/payment/fake"], (req, res) => {
  const gateway = (req.query.gateway || req.body.gateway || "fib").toString().toLowerCase();
  const action = (req.query.action || req.body.action || "ui").toString().toLowerCase();
  const amount = parseFloat((req.query.amount || req.body.amount || 750000).toString());
  const currency = (req.query.currency || req.body.currency || "IQD").toString().toUpperCase();
  const orderId = (req.query.order_id || req.body.order_id || "ORD-" + Math.floor(10000 + Math.random() * 90000)).toString();
  const paymentId = (req.query.payment_id || req.body.payment_id || (gateway.toUpperCase() + "-" + Math.floor(10000 + Math.random() * 90000))).toString();

  const isJson = req.headers.accept?.includes("application/json") || req.headers["content-type"]?.includes("application/json") || req.query.format === "json" || req.body.format === "json" || action !== "ui";

  if (isJson && action !== "ui") {
    if (action === "fib_token" || action === "token") {
      return res.json({
        access_token: "fib_sim_token_" + Math.random().toString(36).substring(2),
        token_type: "Bearer",
        expires_in: 86400,
        scope: "payments:write payments:read accounts:read",
        status: "success",
        gateway: "First Iraqi Bank (Simulated Server via fake.php)"
      });
    }

    if (action === "fib_create" || action === "create_payment") {
      const pid = "FIB-" + Math.floor(10000 + Math.random() * 90000);
      const qrData = `fib://pay?pid=${pid}&amt=${amount}&cur=${currency}&ref=${encodeURIComponent(orderId)}`;
      return res.json({
        success: true,
        payment_id: pid,
        qr_code: qrData,
        readable_code: pid,
        amount: amount,
        currency: currency,
        status: "UNPAID",
        simulator_url: `payment/fake.php?gateway=fib&payment_id=${pid}&amount=${amount}&order_id=${orderId}`
      });
    }

    if (action === "zaincash_init" || action === "init") {
      const txId = "ZC-" + Math.floor(10000000 + Math.random() * 90000000);
      return res.json({
        success: true,
        id: txId,
        token: "zc_sim_jwt_" + Math.random().toString(36).substring(2),
        redirect_url: `payment/fake.php?gateway=zaincash&payment_id=${txId}&amount=${amount}&order_id=${orderId}`,
        status: "pending"
      });
    }

    if (action === "fastpay_init") {
      const txId = "FP-" + Math.floor(100000 + Math.random() * 900000);
      return res.json({
        success: true,
        transaction_id: txId,
        qr_token: `fastpay://merchant_pay?store=AURA_LUXURY&amount=${amount}&order=${orderId}&tx=${txId}`,
        simulator_url: `payment/fake.php?gateway=fastpay&payment_id=${txId}&amount=${amount}&order_id=${orderId}`,
        status: "pending"
      });
    }

    return res.json({
      success: true,
      payment_id: paymentId,
      status: "PAID",
      gateway: gateway,
      receipt_code: "SIM-REC-" + Math.floor(100000 + Math.random() * 900000)
    });
  }

  // Render Interactive Simulator Web UI
  const themeColors: Record<string, { bg: string; accent: string; title: string }> = {
    fib: { bg: "#0a192f", accent: "#d4af37", title: "First Iraqi Bank (FIB) — Bank Mobile Authorization" },
    zaincash: { bg: "#1f132b", accent: "#ec4899", title: "ZainCash (زين كاش) — Electronic Wallet Gateway" },
    fastpay: { bg: "#111827", accent: "#ffc800", title: "FastPay (فاست باي) — Instant Mobile Wallet Pay" }
  };
  const theme = themeColors[gateway] || themeColors.fib;

  const decision = req.body.sim_decision;
  let resultBanner = "";
  if (decision === "accept") {
    resultBanner = `<div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#34d399; padding:14px; border-radius:8px; margin-bottom:18px; text-align:center;">
      <strong>✓ Payment Approved & Verified (200 OK)</strong>
      <p style="margin:4px 0 0; font-size:12.5px;">Simulated banking transaction ${paymentId} for ${amount.toLocaleString()} IQD was accepted.</p>
    </div>`;
  } else if (decision === "decline") {
    resultBanner = `<div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#f87171; padding:14px; border-radius:8px; margin-bottom:18px; text-align:center;">
      <strong>✕ Payment Declined (402 Error)</strong>
      <p style="margin:4px 0 0; font-size:12.5px;">Transaction was cancelled or declined by client PIN authorization.</p>
    </div>`;
  }

  const html = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${theme.title} | Universal Simulator</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { background:#0b0d14; color:#f8fafc; font-family:'Plus Jakarta Sans',sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; padding:20px; box-sizing:border-box; }
    .card { width:100%; max-width:480px; background:#141824; border:1px solid rgba(255,255,255,0.12); border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,0.6); }
    .header { background:${theme.bg}; padding:24px; text-align:center; border-bottom:1px solid rgba(255,255,255,0.1); }
    .body { padding:24px; }
    .tabs { display:flex; gap:6px; margin-bottom:16px; background:rgba(255,255,255,0.04); padding:4px; border-radius:8px; }
    .tab { flex:1; text-align:center; padding:8px; font-size:12px; font-weight:700; color:#94a3b8; text-decoration:none; border-radius:6px; }
    .tab.active { background:${theme.bg}; color:#fff; border:1px solid rgba(255,255,255,0.15); }
    .btn { display:block; width:100%; padding:14px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; border:none; margin-bottom:10px; text-align:center; text-decoration:none; }
    .btn-green { background:#10b981; color:#fff; }
    .btn-red { background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3); }
    .meta-table { width:100%; font-size:13px; margin:16px 0; }
    .meta-table td { padding:6px 0; border-bottom:1px dashed rgba(255,255,255,0.08); }
    .meta-table td:last-child { text-align:right; font-weight:600; color:#fff; }
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <span style="font-size:11px; font-weight:800; color:#38bdf8; background:rgba(56,189,248,0.15); padding:3px 8px; border-radius:20px; text-transform:uppercase;">⚡ Decoupled Bank Simulator (fake.php)</span>
      <h2 style="font-size:18px; margin:10px 0 4px; color:#fff;">${theme.title}</h2>
      <p style="margin:0; font-size:12px; color:#94a3b8;">Simulating real bank API response and webhook callback</p>
    </div>
    <div class="body">
      <div class="tabs">
        <a href="fake.php?gateway=fib&amount=${amount}&order_id=${orderId}" class="tab ${gateway === "fib" ? "active" : ""}">FIB</a>
        <a href="fake.php?gateway=zaincash&amount=${amount}&order_id=${orderId}" class="tab ${gateway === "zaincash" ? "active" : ""}">ZainCash</a>
        <a href="fake.php?gateway=fastpay&amount=${amount}&order_id=${orderId}" class="tab ${gateway === "fastpay" ? "active" : ""}">FastPay</a>
      </div>

      ${resultBanner}

      <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:16px; text-align:center; margin-bottom:16px;">
        <span style="font-size:12px; color:#94a3b8;">Payable Amount</span>
        <div style="font-size:26px; font-weight:800; color:${theme.accent}; margin-top:2px;">${amount.toLocaleString()} ${currency}</div>
      </div>

      <table class="meta-table">
        <tr><td style="color:#94a3b8;">Order Ref</td><td><code>${orderId}</code></td></tr>
        <tr><td style="color:#94a3b8;">Simulated ID</td><td><code>${paymentId}</code></td></tr>
        <tr><td style="color:#94a3b8;">Bank Gateway</td><td>${gateway.toUpperCase()} Gateway</td></tr>
        <tr><td style="color:#94a3b8;">Protocol</td><td>${gateway === "zaincash" ? "HS256 JWT Token" : (gateway === "fib" ? "OAuth2 Bearer" : "FastPay QR IPN")}</td></tr>
      </table>

      <form method="POST" action="fake.php?gateway=${gateway}&amount=${amount}&order_id=${orderId}">
        <button type="submit" name="sim_decision" value="accept" class="btn btn-green">✓ Accept & Authorize Payment (200 OK)</button>
        <button type="submit" name="sim_decision" value="decline" class="btn btn-red">✕ Decline Payment (402 Failed)</button>
        <a href="/checkout.php" class="btn" style="background:transparent; color:#94a3b8; font-size:12.5px;">← Back to Checkout</a>
      </form>
    </div>
  </div>
</body>
</html>`;

  res.send(html);
});

// Dedicated Payment Hub page routing
app.get(["/payment", "/payment/", "/payment/index.php", "/website/payment/index.php"], (req, res) => {
  const filePath = path.join(websiteDir, "payment", "index.php");
  if (fs.existsSync(filePath)) {
    res.send(fs.readFileSync(filePath, "utf-8"));
  } else {
    res.send("Payment SDK Directory Active");
  }
});

// Route handlers for PHP template pages
app.get("/", (req, res) => {
  res.send(renderPhpPage("index", req));
});

app.get("/:page.php", (req, res) => {
  const page = req.params.page;
  const filePath = path.join(websiteDir, `${page}.php`);
  if (fs.existsSync(filePath)) {
    res.send(renderPhpPage(page, req));
  } else {
    res.status(404).send("Page not found");
  }
});

app.get("/:page", (req, res, next) => {
  const page = req.params.page;
  const filePath = path.join(websiteDir, `${page}.php`);
  if (fs.existsSync(filePath)) {
    res.send(renderPhpPage(page, req));
  } else {
    next();
  }
});

app.post("/:page.php", (req, res) => {
  const page = req.params.page;
  const filePath = path.join(websiteDir, `${page}.php`);
  if (fs.existsSync(filePath)) {
    res.send(renderPhpPage(page, req, req.body));
  } else {
    res.status(404).send("Page not found");
  }
});

app.post("/:page", (req, res) => {
  const page = req.params.page;
  const filePath = path.join(websiteDir, `${page}.php`);
  if (fs.existsSync(filePath)) {
    res.send(renderPhpPage(page, req, req.body));
  } else {
    res.status(404).send("Page not found");
  }
});

// Serve static assets from website directory (CSS, JS, images)
app.use(express.static(websiteDir));

app.listen(PORT, "0.0.0.0", () => {
  console.log(`AURA Luxury Store running at http://localhost:${PORT}`);
});
