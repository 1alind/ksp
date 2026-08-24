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
const rootDbDir = path.join(__dirname, "database");
const websiteDbDir = path.join(websiteDir, "database");

// Ensure database directories exist
if (!fs.existsSync(rootDbDir)) fs.mkdirSync(rootDbDir, { recursive: true });
if (!fs.existsSync(websiteDbDir)) fs.mkdirSync(websiteDbDir, { recursive: true });

function getDbFile(filename: string): any {
  const filePath1 = path.join(websiteDbDir, filename);
  const filePath2 = path.join(rootDbDir, filename);
  if (fs.existsSync(filePath1)) {
    try {
      return JSON.parse(fs.readFileSync(filePath1, "utf-8"));
    } catch (e) {}
  }
  if (fs.existsSync(filePath2)) {
    try {
      return JSON.parse(fs.readFileSync(filePath2, "utf-8"));
    } catch (e) {}
  }
  return {};
}

function saveDbFile(filename: string, data: any) {
  const jsonStr = JSON.stringify(data, null, 4);
  fs.writeFileSync(path.join(websiteDbDir, filename), jsonStr, "utf-8");
  fs.writeFileSync(path.join(rootDbDir, filename), jsonStr, "utf-8");
}

// Load translations from translations.php
function loadTranslations(): Record<string, Record<string, string>> {
  const trFile = path.join(websiteDir, "translations.php");
  const dict: Record<string, Record<string, string>> = { en: {}, ar: {}, ku: {} };
  if (fs.existsSync(trFile)) {
    const raw = fs.readFileSync(trFile, "utf-8");
    const enBlock = raw.match(/'en'\s*=>\s*\[(.*?)\]\s*,\s*'ar'/s);
    const arBlock = raw.match(/'ar'\s*=>\s*\[(.*?)\]\s*,\s*'ku'/s);
    const kuBlock = raw.match(/'ku'\s*=>\s*\[(.*?)\]\s*\];/s);

    const parseBlock = (blockStr: string, targetLang: string) => {
      const lineRegex = /'([a-zA-Z0-9_]+)'\s*=>\s*'([^']*)'/g;
      let lm;
      while ((lm = lineRegex.exec(blockStr)) !== null) {
        dict[targetLang][lm[1]] = lm[2];
      }
    };

    if (enBlock) parseBlock(enBlock[1], "en");
    if (arBlock) parseBlock(arBlock[1], "ar");
    if (kuBlock) parseBlock(kuBlock[1], "ku");
  }
  return dict;
}

const translations = loadTranslations();

function t(key: string, lang: string): string {
  if (translations[lang] && translations[lang][key]) {
    return translations[lang][key];
  }
  if (translations["en"] && translations["en"][key]) {
    return translations["en"][key];
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
  const displayTitle = pageTitle ? `${pageTitle} — ${t("site_title", lang)}` : t("site_title", lang);

  const langNames: Record<string, string> = {
    en: "English (EN)",
    ar: "العربية (AR)",
    ku: "کوردی بادینی (KU)"
  };

  return `<!DOCTYPE html>
<html lang="${lang}" ${dirAttr} data-theme="${theme}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${displayTitle}</title>
    
    <!-- Google Fonts for English, Arabic, and Kurdish Badini -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&family=Cairo:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-${activePage}">

    <!-- Top Announcement Bar -->
    <div class="announcement-bar">
        <div class="container announcement-container">
            <div class="announcement-text">
                <span class="sparkle-icon">✨</span>
                <span>${t("features_shipping_title", lang)} &bull; <strong>${t("flash_sale_badge", lang)}</strong></span>
            </div>
            
            <div class="top-bar-actions">
                <!-- Language Switcher -->
                <div class="dropdown-wrapper">
                    <button class="top-btn" id="langDropdownBtn" aria-label="Select Language">
                        <span class="globe-icon">🌐</span>
                        <span class="current-lang-text">${langNames[lang] || "English"}</span>
                        <span class="chevron-down">▾</span>
                    </button>
                    <div class="dropdown-menu" id="langDropdownMenu">
                        <button class="dropdown-item ${lang === 'en' ? 'active' : ''}" onclick="window.AuraStore.setLanguage('en')">
                            <span class="flag-icon">🇬🇧</span> English (EN)
                        </button>
                        <button class="dropdown-item ${lang === 'ar' ? 'active' : ''}" onclick="window.AuraStore.setLanguage('ar')">
                            <span class="flag-icon">🇮🇶</span> العربية (AR)
                        </button>
                        <button class="dropdown-item ${lang === 'ku' ? 'active' : ''}" onclick="window.AuraStore.setLanguage('ku')">
                            <span class="flag-icon">☀️</span> کوردی بادینی (Badini)
                        </button>
                    </div>
                </div>

                <!-- Theme Switcher (Dark / Light) -->
                <button class="top-btn theme-toggle-btn" id="themeToggleBtn" onclick="window.AuraStore.toggleTheme()" title="${t('theme_toggle', lang)}">
                    <span class="theme-icon-dark ${theme === 'dark' ? 'visible' : 'hidden'}">🌙 <span class="theme-text">${t('theme_dark', lang)}</span></span>
                    <span class="theme-icon-light ${theme === 'light' ? 'visible' : 'hidden'}">☀️ <span class="theme-text">${t('theme_light', lang)}</span></span>
                </button>

                <!-- Order Tracking Quick Link -->
                <a href="track.php" class="top-link ${activePage === 'track' ? 'active' : ''}">
                    <span>🔍</span> ${t('nav_track', lang)}
                </a>

                <!-- Admin Dashboard Link -->
                <a href="admin.php" class="top-link ${activePage === 'admin' ? 'active' : ''}">
                    <span>⚙️</span> ${t('nav_admin', lang)}
                </a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="main-header" id="mainHeader">
        <div class="container nav-container">
            <!-- Mobile Menu Toggle Button -->
            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle navigation">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <!-- Brand Logo -->
            <a href="index.php" class="brand-logo">
                <span class="logo-emblem">✦</span>
                <div class="logo-text-group">
                    <span class="brand-name">AURA</span>
                    <span class="brand-sub">LUXURY STORE</span>
                </div>
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
                <a href="about.php" class="nav-link ${activePage === 'about' ? 'active' : ''}">
                    ${t('nav_about', lang)}
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
            <a href="about.php" class="mobile-nav-item ${activePage === 'about' ? 'active' : ''}">
                <span>📖</span> ${t('nav_about', lang)}
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

function renderFooter(lang: string): string {
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
                    <li><a href="about.php">${t('nav_about', lang)}</a></li>
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

            <!-- Col 4: Newsletter -->
            <div class="footer-col newsletter-col">
                <h4 class="footer-heading">${t('newsletter_title', lang)}</h4>
                <p class="newsletter-sub">${t('newsletter_desc', lang)}</p>
                <form class="newsletter-form" onsubmit="window.AuraStore.handleNewsletter(event)">
                    <input type="email" placeholder="${t('newsletter_placeholder', lang)}" required class="newsletter-input" id="newsletterEmailInput">
                    <button type="submit" class="btn btn-primary">${t('newsletter_btn', lang)}</button>
                </form>
                <div class="payment-methods-accepted">
                    <span>💵 Cash on Delivery</span>
                    <span>📱 FastPay</span>
                    <span>💳 Visa / MasterCard</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom-bar">
            <div class="container footer-bottom-inner">
                <p>&copy; 2026 AURA Luxury Store. ${t('rights_reserved', lang)}</p>
                <div class="footer-bottom-links">
                    <a href="about.php">Heritage</a>
                    <span>•</span>
                    <a href="contact.php">VIP Concierge</a>
                    <span>•</span>
                    <a href="track.php">Delivery Tracker</a>
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

    if (postData.add_new_product && postData.prod_title_en && postData.prod_price) {
      const newId = productsList.length > 0 ? Math.max(...productsList.map((p: any) => p.id)) + 1 : 1;
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
        stock: parseInt(postData.prod_stock || "15", 10),
        image: postData.prod_image || "https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80",
        images: [postData.prod_image || "https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80"],
        sizes: postData.prod_category === "clothes" ? ["S", "M", "L", "XL"] : postData.prod_category === "watches" ? ["42mm Case"] : ["100ml / 3.4 oz"],
        colors: ["Luxury Edition"],
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

  // Remove includes and opening/closing php setup blocks from the file
  bodyContent = bodyContent.replace(/<\?php[\s\S]*?require_once\s+(?:__DIR__\s*\.\s*)?['"]\/header\.php['"]\s*;[\s\S]*?\?>/g, "");
  bodyContent = bodyContent.replace(/<\?php[\s\S]*?require_once\s+(?:__DIR__\s*\.\s*)?['"]\/footer\.php['"]\s*;[\s\S]*?\?>/g, "");

  // Substitute all translation calls
  bodyContent = bodyContent.replace(/<\?php\s+echo\s+t\(\s*['"]([^'"]+)['"]\s*,\s*\$lang\s*\)\s*;?\s*\?>/g, (match, key) => t(key, lang));
  bodyContent = bodyContent.replace(/<\?php\s+echo\s+t\(\s*['"]([^'"]+)['"]\s*\)\s*;?\s*\?>/g, (match, key) => t(key, lang));
  bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$lang\s*;?\s*\?>/g, lang);
  bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$theme\s*;?\s*\?>/g, theme);
  bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$pageTitle\s*;?\s*\?>/g, pageTitle);

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
                    <div class="product-rating">
                        <span class="star-icon">★</span>
                        <span class="rating-val">${Number(item.rating).toFixed(1)}</span>
                    </div>
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
              <div class="product-rating">
                <span>★</span>
                <span>${Number(p.rating).toFixed(1)}</span>
              </div>
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

    bodyContent = bodyContent.replace(/<\?php\s+echo\s+htmlspecialchars\(\$productTitle\)\s*;?\s*\?>/g, prodTitle || "");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$productTitle\s*;?\s*\?>/g, prodTitle || "");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+htmlspecialchars\(\$productDesc\)\s*;?\s*\?>/g, prodDesc || "");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$productDesc\s*;?\s*\?>/g, prodDesc || "");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$product\['id'\]\s*;?\s*\?>/g, String(prod.id || 1));
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+htmlspecialchars\(\$product\['category'\]\)\s*;?\s*\?>/g, prod.category || "clothes");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+number_format\(\$product\['price'\],\s*2\)\s*;?\s*\?>/g, prod.price ? prod.price.toFixed(2) : "0.00");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+number_format\(\$product\['old_price'\],\s*2\)\s*;?\s*\?>/g, prod.old_price ? prod.old_price.toFixed(2) : "");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+number_format\(\$product\['rating'\],\s*1\)\s*;?\s*\?>/g, prod.rating ? prod.rating.toFixed(1) : "5.0");
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+\$product\['reviews_count'\]\s*;?\s*\?>/g, String(prod.reviews_count || 1));
    bodyContent = bodyContent.replace(/<\?php\s+echo\s+htmlspecialchars\(\$product\['image'\]\)\s*;?\s*\?>/g, prod.image || "");

    const sizesHtml = (prod.sizes || ["Standard"]).map((sz: string, idx: number) => `
      <button type="button" class="size-pill ${idx === 0 ? 'active' : ''}" onclick="selectSize(this, '${sz}')">${sz}</button>
    `).join("");
    bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$product\['sizes'\]\s+as\s+\$idx\s*=>\s*\$sz\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, sizesHtml);

    const colorsHtml = (prod.colors || ["Classic"]).map((col: string, idx: number) => `
      <button type="button" class="color-badge-pill ${idx === 0 ? 'active' : ''}" onclick="selectColor(this, '${col}')">${col}</button>
    `).join("");
    bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$product\['colors'\]\s+as\s+\$idx\s*=>\s*\$col\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, colorsHtml);
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
      <tr>
        <td><strong><a href="track.php?order_id=${ord.order_id}">${ord.order_id}</a></strong></td>
        <td><small>${new Date(ord.created_at).toLocaleDateString()}</small></td>
        <td>
          <strong>${ord.customer_name}</strong><br>
          <small class="text-muted">${ord.city} • ${ord.phone}</small>
        </td>
        <td>${ord.items ? ord.items.length : 0} pcs</td>
        <td class="font-bold text-primary">$${Number(ord.total).toFixed(2)}</td>
        <td><span class="badge-tag">${ord.payment_method}</span></td>
        <td>
          <form action="admin.php" method="POST" class="inline-status-form">
            <input type="hidden" name="order_id" value="${ord.order_id}">
            <input type="hidden" name="update_order_status" value="1">
            <select name="order_status" class="status-select" onchange="this.form.submit()">
              <option value="Pending" ${ord.order_status === "Pending" ? "selected" : ""}>Pending</option>
              <option value="Processing" ${ord.order_status === "Processing" ? "selected" : ""}>Processing</option>
              <option value="Shipped" ${ord.order_status === "Shipped" ? "selected" : ""}>Shipped</option>
              <option value="Delivered" ${ord.order_status === "Delivered" ? "selected" : ""}>Delivered</option>
            </select>
          </form>
        </td>
        <td>
          <a href="track.php?order_id=${ord.order_id}" class="btn btn-outline btn-xs">View Track</a>
        </td>
      </tr>
    `).join("");

    bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$ordersList\s+as\s+\$ord\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, orderRows);

    const prodRows = productsList.map((p: any) => {
      const pTitle = typeof p.title === "object" ? (p.title[lang] || p.title.en) : p.title;
      return `
        <tr>
          <td>#${p.id}</td>
          <td>
            <div class="admin-prod-preview">
              <img src="${p.image}" alt="" class="admin-prod-thumb">
              <div>
                <strong>${pTitle}</strong><br>
                <small class="badge-tag">${p.badge || ""}</small>
              </div>
            </div>
          </td>
          <td><span class="badge-tag text-uppercase">${p.category}</span></td>
          <td class="font-bold">$${p.price.toFixed(2)}</td>
          <td>${p.stock} left</td>
          <td>★ ${p.rating.toFixed(1)}</td>
          <td>
            <form action="admin.php" method="POST" onsubmit="return confirm('Delete product permanently?')">
              <input type="hidden" name="delete_product_id" value="${p.id}">
              <button type="submit" class="btn btn-ghost text-danger btn-xs">Delete</button>
            </form>
          </td>
        </tr>
      `;
    }).join("");

    bodyContent = bodyContent.replace(/<\?php\s+foreach\s*\(\$productsList\s+as\s+\$p\):\s*.*?<\?php\s+endforeach;\s*\?>/gs, prodRows);
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
  const footerHtml = renderFooter(lang);

  return headerHtml + "\n" + bodyContent + "\n" + footerHtml;
}

// Serve static assets from website directory
app.use(express.static(websiteDir));

// Route handlers
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

app.listen(PORT, "0.0.0.0", () => {
  console.log(`AURA Luxury Store running at http://localhost:${PORT}`);
});
