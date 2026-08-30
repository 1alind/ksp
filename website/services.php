<?php
$title = "Our Services — Aura Studio";
$activePage = "services";
$currentYear = "2026";
include 'header.php';
?>

<!-- Services Header -->
<section class="page-header">
    <div class="container">
        <span class="section-badge">Our Capabilities</span>
        <h1 class="page-title">Digital capabilities that drive competitive advantage.</h1>
        <p class="page-subtitle">We work at the intersection of aesthetic design and robust technology to build unified brand and product systems.</p>
    </div>
</section>

<!-- Detailed Service Sections -->
<section class="services-detail-section">
    <div class="container">
        <!-- Service Block 1: Design -->
        <div class="service-detail-block" id="design">
            <div class="service-detail-content">
                <span class="service-num-tag">01</span>
                <h2>Product Design & UI/UX</h2>
                <p class="lead">We design interfaces that feel natural, intuitive, and remarkably polished.</p>
                <p>Our design process is rooted in thorough user research, collaborative wireframing, and rigorous testing. We create detailed interactive high-fidelity design prototypes, comprehensive style systems, and fully integrated component libraries that elevate your brand's digital presence.</p>
                <ul class="service-points">
                    <li>High-Fidelity UI/UX Design</li>
                    <li>Design Systems & Component Libraries</li>
                    <li>Interactive Prototyping</li>
                    <li>User Research & Usability Testing</li>
                </ul>
            </div>
            <div class="service-detail-visual">
                <div class="visual-card-pattern pattern-design">
                    <div class="visual-element-ui bg-dark-card">
                        <span class="ui-dot-group"><span class="red"></span><span class="yellow"></span><span class="green"></span></span>
                        <div class="ui-mock-text line-title"></div>
                        <div class="ui-mock-text line-body"></div>
                        <div class="ui-mock-grid">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Block 2: Development -->
        <div class="service-detail-block block-reverse" id="dev">
            <div class="service-detail-content">
                <span class="service-num-tag">02</span>
                <h2>Web & Software Development</h2>
                <p class="lead">We write pixel-perfect, highly performant code tailored for security and scalability.</p>
                <p>We build lightweight web applications, modern single-page applications, and server-side platforms that offer rapid page loads and high accessibility ratings. Our stack is customized to your performance goals and business needs.</p>
                <ul class="service-points">
                    <li>Responsive Web App Engineering</li>
                    <li>Server-Side PHP & API Integration</li>
                    <li>Content Management Systems (CMS)</li>
                    <li>W3C Accessibility & SEO Auditing</li>
                </ul>
            </div>
            <div class="service-detail-visual">
                <div class="visual-card-pattern pattern-dev">
                    <div class="visual-element-code">
                        <div class="code-line"><span class="keyword">const</span> studio = <span class="string">'Aura'</span>;</div>
                        <div class="code-line indent-1"><span class="keyword">function</span> <span class="function">craft</span>(idea) {</div>
                        <div class="code-line indent-2"><span class="keyword">return</span> <span class="keyword">new</span> <span class="class">Experience</span>({</div>
                        <div class="code-line indent-3">design: <span class="bool">true</span>,</div>
                        <div class="code-line indent-3">performance: <span class="number">100</span></div>
                        <div class="code-line indent-2">});</div>
                        <div class="code-line indent-1">}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Block 3: Brand Identity -->
        <div class="service-detail-block" id="branding">
            <div class="service-detail-content">
                <span class="service-num-tag">03</span>
                <h2>Brand Strategy & Visual Identity</h2>
                <p class="lead">Shaping distinct identities that command attention and drive loyalty.</p>
                <p>We work with both early-stage start-ups and established leaders to define their voice, positioning, and visual guidelines. We formulate logo designs, typographical frameworks, palette selections, and full guidelines that keep brand experiences unified.</p>
                <ul class="service-points">
                    <li>Brand Voice & Positioning</li>
                    <li>Logo Design & Typographical Systems</li>
                    <li>Palette Guidelines & Brand Assets</li>
                    <li>Brand Guidelines (Brand Book) Production</li>
                </ul>
            </div>
            <div class="service-detail-visual">
                <div class="visual-card-pattern pattern-branding">
                    <div class="brand-swatches">
                        <span class="swatch sw-primary"></span>
                        <span class="swatch sw-secondary"></span>
                        <span class="swatch sw-accent"></span>
                        <span class="swatch sw-dark"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="cta-section">
    <div class="container cta-container">
        <h2 class="cta-title">Let's create something great together.</h2>
        <p class="cta-desc">Reach out to start designing or developing your next high-performance digital product with Aura Studio.</p>
        <a href="shop.php" class="btn btn-primary-light">Explore Luxury Collection</a>
    </div>
</section>

<?php
include 'footer.php';
?>
