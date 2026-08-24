<?php
// Session and Language Initialization
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? $_COOKIE['aura_lang'] ?? 'en';
if (!in_array($lang, ['en', 'ar', 'ku'])) {
    $lang = 'en';
}
$_SESSION['lang'] = $lang;
$dir = in_array($lang, ['ar', 'ku']) ? 'rtl' : 'ltr';

$theme = $_GET['theme'] ?? $_SESSION['theme'] ?? $_COOKIE['aura_theme'] ?? 'dark';
if (!in_array($theme, ['dark', 'light'])) {
    $theme = 'dark';
}
$_SESSION['theme'] = $theme;

require_once __DIR__ . '/translations.php';
require_once __DIR__ . '/database/db.php';

$settings = get_store_settings();
$storeName = $settings['store_name'] ?? 'AURA Luxury Store';
if ($lang === 'ar' && !empty($settings['store_name_ar'])) {
    $storeName = $settings['store_name_ar'];
} elseif ($lang === 'ku' && !empty($settings['store_name_ku'])) {
    $storeName = $settings['store_name_ku'];
}

$logoType = $settings['logo_type'] ?? 'emblem';
$logoEmblem = $settings['logo_emblem'] ?? 'A';
$logoMain = $settings['logo_main'] ?? 'AURA';
$logoSub = $settings['logo_sub'] ?? 'STUDIO';
$logoImageUrl = $settings['logo_image_url'] ?? '';
$faviconUrl = $settings['favicon_url'] ?? '';

$announcementEnabled = $settings['announcement_enabled'] ?? true;
$announcementText = $settings['announcement_text_' . $lang] ?? $settings['announcement_text_en'] ?? (t('features_shipping_title', $lang) . ' • ' . t('flash_sale_badge', $lang));

$activePage = $activePage ?? 'home';
$pageTitle = isset($pageTitle) ? $pageTitle . ' — ' . $storeName : $storeName . ' — ' . ($settings['store_tagline_' . $lang] ?? 'Haute Couture & Swiss Horology');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <?php if (!empty($faviconUrl)): ?>
        <link rel="icon" href="<?php echo htmlspecialchars($faviconUrl); ?>">
    <?php endif; ?>
    
    <!-- Google Fonts for English, Arabic, and Kurdish Badini -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&family=Cairo:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-<?php echo $activePage; ?>">
    
    <!-- Top Announcement Bar -->
    <?php if ($announcementEnabled): ?>
    <div class="announcement-bar">
        <div class="container announcement-container">
            <div class="announcement-text">
                <span class="sparkle-icon">✨</span>
                <span><?php echo htmlspecialchars($announcementText); ?></span>
            </div>
            
            <div class="top-bar-actions">
                <!-- Language Switcher -->
                <div class="dropdown-wrapper">
                    <button class="top-btn" id="langDropdownBtn" aria-label="Select Language">
                        <span class="globe-icon">🌐</span>
                        <span class="current-lang-text">
                            <?php 
                            if ($lang === 'ar') echo 'العربية';
                            elseif ($lang === 'ku') echo 'کوردی (بادینی)';
                            else echo 'English';
                            ?>
                        </span>
                        <span class="chevron-down">▾</span>
                    </button>
                    <div class="dropdown-menu" id="langDropdown">
                        <a href="?lang=en" class="dropdown-item <?php echo $lang === 'en' ? 'active' : ''; ?>" data-lang-set="en">English (EN)</a>
                        <a href="?lang=ar" class="dropdown-item <?php echo $lang === 'ar' ? 'active' : ''; ?>" data-lang-set="ar">العربية (AR)</a>
                        <a href="?lang=ku" class="dropdown-item <?php echo $lang === 'ku' ? 'active' : ''; ?>" data-lang-set="ku">کوردی - بادینی (KU)</a>
                    </div>
                </div>

                <!-- Dark / Light Theme Toggle -->
                <button class="theme-toggle-btn" id="themeToggleBtn" title="<?php echo t('theme_toggle', $lang); ?>" aria-label="Toggle Theme">
                    <span class="theme-icon sun-icon">☀️</span>
                    <span class="theme-icon moon-icon">🌙</span>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                <?php if ($logoType === 'image' && !empty($logoImageUrl)): ?>
                    <img src="<?php echo htmlspecialchars($logoImageUrl); ?>" alt="<?php echo htmlspecialchars($storeName); ?>" class="brand-img-logo" style="max-height:42px; object-fit:contain;">
                <?php else: ?>
                    <div class="logo-emblem"><?php echo htmlspecialchars($logoEmblem); ?></div>
                    <div class="logo-text-group">
                        <span class="logo-main"><?php echo htmlspecialchars($logoMain); ?></span>
                        <span class="logo-sub"><?php echo htmlspecialchars($logoSub); ?></span>
                    </div>
                <?php endif; ?>
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="desktop-nav" id="desktopNav">
                <a href="index.php" class="nav-item <?php echo $activePage === 'home' ? 'active' : ''; ?>"><?php echo t('nav_home', $lang); ?></a>
                <a href="shop.php" class="nav-item <?php echo $activePage === 'shop' ? 'active' : ''; ?>"><?php echo t('nav_shop', $lang); ?></a>
                <a href="shop.php?cat=clothes" class="nav-item <?php echo ($activePage === 'shop' && ($_GET['cat'] ?? '') === 'clothes') ? 'active' : ''; ?>"><?php echo t('nav_clothes', $lang); ?></a>
                <a href="shop.php?cat=watches" class="nav-item <?php echo ($activePage === 'shop' && ($_GET['cat'] ?? '') === 'watches') ? 'active' : ''; ?>"><?php echo t('nav_watches', $lang); ?></a>
                <a href="shop.php?cat=perfumes" class="nav-item <?php echo ($activePage === 'shop' && ($_GET['cat'] ?? '') === 'perfumes') ? 'active' : ''; ?>"><?php echo t('nav_perfumes', $lang); ?></a>
                <a href="shop.php?cat=accessories" class="nav-item <?php echo ($activePage === 'shop' && ($_GET['cat'] ?? '') === 'accessories') ? 'active' : ''; ?>"><?php echo t('nav_accessories', $lang); ?></a>
                <a href="track.php" class="nav-item <?php echo $activePage === 'track' ? 'active' : ''; ?>"><?php echo t('nav_track', $lang); ?></a>
                <a href="admin.php" class="nav-item admin-badge-link <?php echo $activePage === 'admin' ? 'active' : ''; ?>"><?php echo t('nav_admin', $lang); ?></a>
            </nav>

            <!-- Header Action Utilities -->
            <div class="header-actions">
                <!-- Search Trigger / Bar -->
                <div class="search-box-wrapper">
                    <form action="shop.php" method="GET" class="search-form" id="headerSearchForm">
                        <input type="text" name="q" class="search-input" placeholder="<?php echo t('search_placeholder', $lang); ?>" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" autocomplete="off">
                        <button type="submit" class="search-submit-btn" aria-label="Search">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </button>
                    </form>
                </div>

                <!-- Cart Button with Counter -->
                <a href="cart.php" class="cart-trigger-btn" id="cartTrigger" title="<?php echo t('cart', $lang); ?>">
                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                    <span class="cart-counter" id="cartCount">0</span>
                </a>
            </div>
        </div>

        <!-- Mobile Drawer Menu -->
        <div class="mobile-drawer" id="mobileDrawer">
            <div class="mobile-drawer-inner">
                <div class="mobile-search-area">
                    <form action="shop.php" method="GET" class="search-form">
                        <input type="text" name="q" class="search-input" placeholder="<?php echo t('search_placeholder', $lang); ?>">
                        <button type="submit" class="search-submit-btn">🔍</button>
                    </form>
                </div>
                
                <nav class="mobile-nav-links">
                    <a href="index.php" class="mob-link <?php echo $activePage === 'home' ? 'active' : ''; ?>"><?php echo t('nav_home', $lang); ?></a>
                    <a href="shop.php" class="mob-link <?php echo $activePage === 'shop' ? 'active' : ''; ?>"><?php echo t('nav_shop', $lang); ?></a>
                    <a href="shop.php?cat=clothes" class="mob-link"><?php echo t('nav_clothes', $lang); ?></a>
                    <a href="shop.php?cat=watches" class="mob-link"><?php echo t('nav_watches', $lang); ?></a>
                    <a href="shop.php?cat=perfumes" class="mob-link"><?php echo t('nav_perfumes', $lang); ?></a>
                    <a href="shop.php?cat=accessories" class="mob-link"><?php echo t('nav_accessories', $lang); ?></a>
                    <a href="track.php" class="mob-link"><?php echo t('nav_track', $lang); ?></a>
                    <a href="admin.php" class="mob-link admin-highlight"><?php echo t('nav_admin', $lang); ?></a>
                    <a href="about.php" class="mob-link"><?php echo t('nav_about', $lang); ?></a>
                    <a href="contact.php" class="mob-link"><?php echo t('nav_contact', $lang); ?></a>
                </nav>

                <div class="mobile-drawer-footer">
                    <div class="lang-selector-pills">
                        <a href="?lang=en" class="pill-btn <?php echo $lang === 'en' ? 'active' : ''; ?>" data-lang-set="en">English</a>
                        <a href="?lang=ar" class="pill-btn <?php echo $lang === 'ar' ? 'active' : ''; ?>" data-lang-set="ar">العربية</a>
                        <a href="?lang=ku" class="pill-btn <?php echo $lang === 'ku' ? 'active' : ''; ?>" data-lang-set="ku">کوردی (بادینی)</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Global Floating Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Quick View Product Modal -->
    <div id="quickViewModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-dialog">
            <button class="modal-close-btn" id="quickViewClose">&times;</button>
            <div class="modal-body" id="quickViewContent">
                <!-- Populated dynamically via JS -->
            </div>
        </div>
    </div>

    <!-- Main Content Area Begins -->
    <main class="main-wrapper">
