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
                        <p><?php echo t('features_shipping_desc', $lang); ?></p>
                    </div>
                </div>

                <div class="perk-item">
                    <div class="perk-icon">💎</div>
                    <div class="perk-text">
                        <h4><?php echo t('features_quality_title', $lang); ?></h4>
                        <p><?php echo t('features_quality_desc', $lang); ?></p>
                    </div>
                </div>

                <div class="perk-item">
                    <div class="perk-icon">👑</div>
                    <div class="perk-text">
                        <h4><?php echo t('features_support_title', $lang); ?></h4>
                        <p><?php echo t('features_support_desc', $lang); ?></p>
                    </div>
                </div>

                <div class="perk-item">
                    <div class="perk-icon">🛡️</div>
                    <div class="perk-text">
                        <h4><?php echo t('features_payment_title', $lang); ?></h4>
                        <p><?php echo t('features_payment_desc', $lang); ?></p>
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
                <div class="footer-contact-pills">
                    <div class="contact-pill">📍 <?php echo htmlspecialchars($settings['boutique_location_' . $lang] ?? 'Duhok / Erbil / International'); ?></div>
                    <div class="contact-pill">📞 <?php echo htmlspecialchars($settings['contact_phone'] ?? '+964 750 123 4567'); ?></div>
                    <div class="contact-pill">✉️ <?php echo htmlspecialchars($settings['contact_email'] ?? 'concierge@aurastudio.co'); ?></div>
                </div>
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
                    <li><a href="about.php"><?php echo t('nav_about', $lang); ?></a></li>
                    <li><a href="contact.php"><?php echo t('nav_contact', $lang); ?></a></li>
                    <li><a href="admin.php"><?php echo t('nav_admin', $lang); ?></a></li>
                </ul>
            </div>

            <div class="footer-newsletter-col">
                <h4 class="footer-heading"><?php echo t('newsletter_title', $lang); ?></h4>
                <p class="newsletter-sub"><?php echo t('newsletter_desc', $lang); ?></p>
                <form class="newsletter-form" onsubmit="event.preventDefault(); window.AuraStore.showToast('<?php echo $lang === 'ku' ? 'سوپاس بۆ بەشداربوونا تە د یانا ئۆرادا!' : ($lang === 'ar' ? 'شكراً لانضمامك إلى مجتمع أورا!' : 'Thank you for joining the Aura Circle!'); ?>', 'success'); this.reset();">
                    <div class="newsletter-input-group">
                        <input type="email" placeholder="<?php echo t('newsletter_placeholder', $lang); ?>" required class="newsletter-input">
                        <button type="submit" class="newsletter-submit-btn"><?php echo t('newsletter_btn', $lang); ?></button>
                    </div>
                </form>

                <div class="payment-methods-icons">
                    <span class="pay-tag">Cash on Delivery (COD)</span>
                    <span class="pay-tag">FastPay</span>
                    <span class="pay-tag">ZainCash</span>
                    <span class="pay-tag">Visa / Master</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom-bar">
            <div class="container footer-bottom-flex">
                <p>&copy; <?php echo date('Y'); ?> AURA STUDIO. <?php echo t('rights_reserved', $lang); ?></p>
                <div class="footer-legal-links">
                    <a href="about.php">Privacy & Terms</a>
                    <a href="contact.php">VIP Concierge</a>
                    <a href="admin.php">Management</a>
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
    
    <script src="script.js"></script>
</body>
</html>
