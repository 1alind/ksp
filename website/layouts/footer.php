    </main>
    <!-- Main Content Ends -->

    <!-- Global Customer Experience Badges -->
    <section class="perks-banner">
        <div class="container">
            <div class="perks-grid">
                <div class="perk-item">
                    <div class="perk-icon">🚀</div>
                    <div class="perk-text">
                        <h4><?php echo t('features_shipping_title', $lang); ?></h4>
                    </div>
                </div>

                <div class="perk-item">
                    <div class="perk-icon">💎</div>
                    <div class="perk-text">
                        <h4><?php echo t('features_quality_title', $lang); ?></h4>
                    </div>
                </div>

                <div class="perk-item">
                    <div class="perk-icon">👑</div>
                    <div class="perk-text">
                        <h4><?php echo t('features_support_title', $lang); ?></h4>
                    </div>
                </div>

                <div class="perk-item">
                    <div class="perk-icon">🛡️</div>
                    <div class="perk-text">
                        <h4><?php echo t('features_payment_title', $lang); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="site-footer">
        <div class="container footer-main-container">
            <div class="footer-brand-col">
                <a href="index.php" class="brand-logo footer-logo">
                    <?php if ($logoType === 'image' && !empty($logoImageUrl)): ?>
                        <img src="<?php echo htmlspecialchars($logoImageUrl); ?>" alt="<?php echo htmlspecialchars($storeName); ?>" class="brand-img-logo" style="max-height:38px; object-fit:contain;">
                    <?php else: ?>
                        <div class="logo-emblem"><?php echo htmlspecialchars($logoEmblem); ?></div>
                        <div class="logo-text-group">
                            <span class="logo-main"><?php echo htmlspecialchars($logoMain); ?></span>
                            <span class="logo-sub"><?php echo htmlspecialchars($logoSub); ?></span>
                        </div>
                    <?php endif; ?>
                </a>
                <p class="footer-intro"><?php echo htmlspecialchars($settings['store_description_' . $lang] ?? t('footer_about', $lang)); ?></p>
            </div>

            <div class="footer-links-col">
                <h4 class="footer-heading"><?php echo t('footer_categories', $lang); ?></h4>
                <ul class="footer-nav">
                    <li><a href="shop.php?cat=clothes"><?php echo t('nav_clothes', $lang); ?></a></li>
                    <li><a href="shop.php?cat=watches"><?php echo t('nav_watches', $lang); ?></a></li>
                    <li><a href="shop.php?cat=perfumes"><?php echo t('nav_perfumes', $lang); ?></a></li>
                    <li><a href="shop.php?cat=accessories"><?php echo t('nav_accessories', $lang); ?></a></li>
                </ul>
            </div>

            <div class="footer-links-col">
                <h4 class="footer-heading"><?php echo t('footer_quick_links', $lang); ?></h4>
                <ul class="footer-nav">
                    <li><a href="shop.php"><?php echo t('nav_shop', $lang); ?></a></li>
                    <li><a href="track.php"><?php echo t('nav_track', $lang); ?></a></li>
                    <li><a href="admin.php"><?php echo t('nav_admin', $lang); ?></a></li>
                </ul>
            </div>

            <div class="footer-payment-col">
                <h4 class="footer-heading"><?php echo $lang === 'ku' ? 'دەرگەهێن پارەدانێ' : ($lang === 'ar' ? 'بوابات الدفع' : 'Payment Gateways'); ?></h4>
                <div class="payment-methods-icons">
                    <!-- FIB Logo Pill -->
                    <div class="pay-logo-badge pay-logo-fib" title="First Iraqi Bank (FIB)">
                        <svg viewBox="0 0 110 32" width="94" height="28" xmlns="http://www.w3.org/2000/svg">
                            <rect width="110" height="32" rx="6" fill="#0A192F"/>
                            <path d="M12 16 L17 10 L22 16 L17 22 Z" fill="#D4AF37"/>
                            <path d="M17 13 L19.5 16 L17 19 L14.5 16 Z" fill="#0A192F"/>
                            <circle cx="17" cy="16" r="1.5" fill="#FFFFFF"/>
                            <text x="28" y="21.5" fill="#FFFFFF" font-family="system-ui, sans-serif" font-weight="900" font-size="14" letter-spacing="0.5">FIB</text>
                            <text x="60" y="14.5" fill="#D4AF37" font-family="system-ui, sans-serif" font-weight="700" font-size="5.8" letter-spacing="0.4">FIRST IRAQI</text>
                            <text x="60" y="22" fill="#94A3B8" font-family="system-ui, sans-serif" font-weight="600" font-size="5.5" letter-spacing="0.4">BANK</text>
                        </svg>
                    </div>
                    <!-- FastPay Logo Pill -->
                    <div class="pay-logo-badge pay-logo-fastpay" title="FastPay Mobile Wallet">
                        <svg viewBox="0 0 110 32" width="94" height="28" xmlns="http://www.w3.org/2000/svg">
                            <rect width="110" height="32" rx="6" fill="#FFC800"/>
                            <g transform="translate(8, 6)">
                                <circle cx="10" cy="10" r="9" fill="#111827"/>
                                <path d="M7 6.5 L11.5 10 L7 13.5" stroke="#FFC800" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                <path d="M10.5 6.5 L15 10 L10.5 13.5" stroke="#FFC800" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            </g>
                            <text x="32" y="21" fill="#111827" font-family="system-ui, sans-serif" font-weight="900" font-size="13.5" letter-spacing="-0.3">FastPay</text>
                        </svg>
                    </div>
                    <!-- ZainCash Logo Pill -->
                    <div class="pay-logo-badge pay-logo-zaincash" title="ZainCash">
                        <svg viewBox="0 0 110 32" width="94" height="28" xmlns="http://www.w3.org/2000/svg">
                            <rect width="110" height="32" rx="6" fill="#1F132B"/>
                            <g transform="translate(8, 6)">
                                <circle cx="10" cy="10" r="8.5" fill="none" stroke="#EC4899" stroke-width="2.5"/>
                                <path d="M6.5 10 C6.5 8 8 6.5 10 6.5 C12 6.5 13.5 8 13.5 10 C13.5 12 12 13.5 10 13.5" stroke="#A855F7" stroke-width="2" stroke-linecap="round" fill="none"/>
                                <circle cx="10" cy="10" r="1.8" fill="#38BDF8"/>
                            </g>
                            <text x="32" y="21" fill="#FFFFFF" font-family="system-ui, sans-serif" font-weight="900" font-size="13" letter-spacing="-0.2">Zain<tspan fill="#EC4899">Cash</tspan></text>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom-bar">
            <div class="container footer-bottom-flex">
                <p>&copy; <?php echo date('Y'); ?> AURA STUDIO. <?php echo t('rights_reserved', $lang); ?></p>
                <div class="footer-legal-links">
                    <a href="shop.php"><?php echo t('nav_shop', $lang); ?></a>
                    <a href="track.php"><?php echo t('nav_track', $lang); ?></a>
                    <a href="admin.php"><?php echo t('nav_admin', $lang); ?></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Pass JSON Data to Client Scripts for Instant Filtering & Cart Operations -->
    <script>
        window.AURA_LANG = "<?php echo $lang; ?>";
        window.AURA_THEME = "<?php echo $theme; ?>";
        window.AURA_TRANSLATIONS = <?php echo json_encode($translations[$lang] ?? $translations['en']); ?>;
        <?php 
        $all_products = get_all_products();
        ?>
        window.AURA_PRODUCTS = <?php echo json_encode($all_products); ?>;
    </script>
    
    <script src="/script.js"></script>
</body>
</html>
