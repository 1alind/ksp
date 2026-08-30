-- ==============================================================================
-- AURA Luxury Store — Complete Seed Data SQL Script
-- Comprehensive Example Data for All Product Categories & Order Types
-- Compatible with: MySQL 5.6+, MySQL 5.7+, MySQL 8.0+, MariaDB & InfinityFree Hosting
-- Charset: utf8mb4 (Full Support for English, Kurdish, and Arabic)
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. SEED ALL PRODUCT TYPES & CATEGORIES (Clothes, Watches, Perfumes, Accessories)
-- Uses INSERT INTO ... ON DUPLICATE KEY UPDATE for safe re-runs
-- ------------------------------------------------------------------------------

INSERT INTO `products` 
(`id`, `title_en`, `title_ar`, `title_ku`, `category`, `price`, `old_price`, `rating`, `reviews_count`, `badge_en`, `badge_ar`, `badge_ku`, `stock`, `image`, `images`, `colors`, `sizes`, `size_measurements`, `description_en`, `description_ar`, `description_ku`, `featured`)
VALUES
-- --- CLOTHES CATEGORY ---
(
  1,
  'Royal Midnight Velvet Blazer',
  'بليزر مخملي ملكي ميدنايت',
  'ساکێ مەخمەلی یێ شاهانە میدنایت',
  'clothes',
  245000.00,
  315000.00,
  4.90,
  38,
  'Best Seller',
  'الأكثر مبيعاً',
  'پڕفرۆشترین',
  14,
  'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80","https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=800&q=80","https://images.unsplash.com/photo-1617127365659-c47fa864d8bc?auto=format&fit=crop&w=800&q=80"]',
  '["Midnight Blue","Obsidian Black","Burgundy"]',
  '["S","M","L","XL"]',
  '{"S":"Length: 68 cm • Chest: 96 cm • Shoulder: 44 cm • Sleeve: 62 cm","M":"Length: 70 cm • Chest: 102 cm • Shoulder: 46 cm • Sleeve: 63 cm","L":"Length: 73 cm • Chest: 108 cm • Shoulder: 48 cm • Sleeve: 65 cm","XL":"Length: 76 cm • Chest: 114 cm • Shoulder: 50 cm • Sleeve: 66 cm"}',
  'Impeccably tailored luxury velvet blazer designed with silk lapels and custom metal buttons. Perfect for evening galas, high-profile events, and formal dinners.',
  'بليزر مخملي فاخر بتفصيل متقن مع ياقة حريرية وأزرار معدنية مخصصة. مثالي للحفلات المسائية والمناسبات الرسمية.',
  'ساکێ مخمەلی یێ گەلەک جوان و شاهانە ب نەخشیێن ئاوریشمی و دوگماێن زێڕین. گەلەک گونجایە بۆ ئاهەنگ و هەلکەفتێن فەرمی.',
  1
),
(
  5,
  'Silk Jacquard Formal Evening Suit',
  'بدلة سهرة رسمية من حرير الجاكار',
  'قاتێ فەرمی یێ شەڤێ ژ ئاوریشمێ ژاکار',
  'clothes',
  380000.00,
  490000.00,
  5.00,
  27,
  'New Arrival',
  'وصل حديثاً',
  'نوی گەهشتی',
  6,
  'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=800&q=80","https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80"]',
  '["Deep Emerald Jacquard","Onyx Black"]',
  '["48 (M)","50 (L)","52 (XL)"]',
  '{"48 (M)":"Jacket: 72cm Chest: 100cm • Trousers: Waist 82cm Length 102cm","50 (L)":"Jacket: 74cm Chest: 104cm • Trousers: Waist 86cm Length 104cm","52 (XL)":"Jacket: 76cm Chest: 108cm • Trousers: Waist 90cm Length 106cm"}',
  'Distinctive jacquard woven silk-wool blend suit featuring satin peak lapels and slim-fit trousers.',
  'بدلة سهرة راقية مصنوعة من مزيج الحرير والصوف بنقشة الجاكار الفاخرة مع ياقة ستان وقصة عصرية متناسقة.',
  'قاتەکێ گەلەک نازک ژ تێکەلێ ئاوریشم و پەیفا کوالێتی بەرز دگەل بەنترۆنێ فیت یێ مودێرن.',
  0
),
(
  9,
  'Cashmere & Silk Tailored Trench Coat',
  'معطف كشمير وحرير كلاسيكي فاخر',
  'پالتۆیا شاهانە یا کەشمیر و ئاوریشم',
  'clothes',
  460000.00,
  580000.00,
  4.90,
  21,
  'Winter Collection',
  'مجموعة الشتاء',
  'کۆمەڵەیا زڤستانێ',
  9,
  'https://images.unsplash.com/photo-1544923246-77307dd654cb?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1544923246-77307dd654cb?auto=format&fit=crop&w=800&q=80"]',
  '["Camel Tan","Charcoal Gray"]',
  '["M (48)","L (50)","XL (52)"]',
  '{"M (48)":"Length: 110 cm • Chest: 106 cm • Sleeve: 64 cm","L (50)":"Length: 112 cm • Chest: 112 cm • Sleeve: 66 cm","XL (52)":"Length: 115 cm • Chest: 118 cm • Sleeve: 67 cm"}',
  'Ultra-soft Mongolian cashmere with silk lining, water-repellent finish, and signature horn buttons.',
  'كشمير منغولي فائق النعومة مبطن بالحرير الخالص ومقاوم للماء مع أزرار طبيعية فاخرة.',
  'کەشمیرا مەنگۆلی یا گەلەک نەرم ب بەتانا ئاوریشمی و دژی ئاڤێ دگەل دوگماێن سروشتی.',
  1
),
(
  13,
  'Bespoke Italian Silk Knit Polo',
  'قميص بولو حريري إيطالي مخصص',
  'پۆلۆیا ئاوریشمی یا ئیتالی یا تایبەت',
  'clothes',
  165000.00,
  210000.00,
  4.85,
  19,
  'Summer Luxury',
  'أناقة الصيف',
  'هاڤینا لوکس',
  18,
  'https://images.unsplash.com/photo-1617127365659-c47fa864d8bc?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1617127365659-c47fa864d8bc?auto=format&fit=crop&w=800&q=80"]',
  '["Ivory White","Navy Marine","Sand Gold"]',
  '["S","M","L","XL"]',
  '{"S":"Chest: 94cm • Length: 68cm","M":"Chest: 100cm • Length: 70cm","L":"Chest: 106cm • Length: 72cm","XL":"Chest: 112cm • Length: 74cm"}',
  'Finely knitted Mulberry silk and Egyptian cotton yarn polo with mother-of-pearl buttons.',
  'بولو حريري ناعم منسوج من الحرير التوتي والقطن المصري مع أزرار صدفية أصلية.',
  'پۆلۆیا ئاوریشمی یا نرم ب دوگماێن صەدەفی یێن رەسەن و قوماشێ ئیتالی.',
  0
),

-- --- WATCHES CATEGORY ---
(
  2,
  'Onyx Skeleton Automatic Watch',
  'ساعة أوتوماتيكية أونيكس هيكلية',
  'دەمژمێرا ئۆتۆماتیک یا سکێلێتۆن ئۆنیکس',
  'watches',
  550000.00,
  725000.00,
  5.00,
  64,
  'Luxury',
  'فاخر',
  'لوکس و نازک',
  8,
  'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80","https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=800&q=80","https://images.unsplash.com/photo-1542496658-e33a6d0d50f6?auto=format&fit=crop&w=800&q=80"]',
  '["Black Onyx & Gold","Silver Steel","Rose Gold"]',
  '["41mm Case"]',
  '{"41mm Case":"Case Diameter: 41 mm • Thickness: 11.5 mm • Strap Width: 20 mm • Lug-to-Lug: 47 mm"}',
  'Self-winding mechanical automatic movement with an open-heart skeleton dial, sapphire crystal glass, and Italian genuine leather strap. Water-resistant up to 50M.',
  'حركة ميكانيكية أوتوماتيكية مع ميناء هيكلي مكشوف وزجاج من الكريستال الياقوتي المقاوم للخدش وسوار جلد إيطالي أصلي.',
  'دەمژمێرەکا ميكانيكی یا ئۆتۆماتیك ب دیزاینێ سکێلێتۆن و شوشەیا یاقووتی یا دژی کڕاندنێ و قایشا چەرمی یا ئیتالی یا رەسەن.',
  1
),
(
  6,
  'Celestial Diamond Chronograph',
  'ساعة كرونوغراف سيليستيال الماسية',
  'دەمژمێرا کرۆنۆگراف یا ئەلماسی یا سەلێستیال',
  'watches',
  890000.00,
  1150000.00,
  5.00,
  51,
  'Exclusive',
  'حصري',
  'ب تایبەت',
  4,
  'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=800&q=80","https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80"]',
  '["18K Rose Gold & Diamond Dial","Platinum Silver"]',
  '["42mm Case"]',
  '{"42mm Case":"Diameter: 42 mm • Sapphire Glass • Triple Subdial Chronograph • 100M Water Resistance"}',
  'Precision Swiss chronograph with certified diamond hour markers and sunburst guilloché dial.',
  'ساعة كرونوغراف سويسرية فائقة الدقة مرصعة بماسات معتمدة وميناء بتشطيبات فنية مذهلة.',
  'دەمژمێرەکا سویسری یا دقیق مرسەع کری ب ئەلماسان و سەعەتێن زێڕین یێن شاهانە.',
  1
),
(
  10,
  'Tourbillon Heritage Diver 300M',
  'ساعة توربيون هيريتيج غواص 300 متر',
  'دەمژمێرا تۆرپیۆن یا هێریتێج یا غەواسی 300م',
  'watches',
  1250000.00,
  1600000.00,
  5.00,
  33,
  'Masterpiece',
  'تحفة فنية',
  'شاهکار',
  3,
  'https://images.unsplash.com/photo-1542496658-e33a6d0d50f6?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1542496658-e33a6d0d50f6?auto=format&fit=crop&w=800&q=80"]',
  '["Ceramic Blue & Titanium","Stealth Matte Carbon"]',
  '["43mm Titanium Case"]',
  '{"43mm Titanium Case":"Diameter: 43 mm • Helium Escape Valve • Ceramic Bezel • 300M Depth Rating"}',
  'Titanium grade 5 case, visible flying tourbillon at 6 o’clock, automatic helium valve, 72h power reserve.',
  'هيكل من التيتانيوم عالي الصلابة مع توربيون طائر ومقاومة للماء حتى عمق 300 متر واحتياطي طاقة 72 ساعة.',
  'دیزاینێ تیتانیۆم یێ بهێز دگەل سیستەمێ تۆرپیۆن و بەڕگرییا ئاڤێ هەتا 300 مەتران.',
  0
),

-- --- PERFUMES CATEGORY ---
(
  3,
  'Royal Amber & Smoked Oud Eau de Parfum',
  'عطر العود المدخن والعنبر الملكي',
  'عەترێ عوودێ دووکەلی و عەنبەرێ شاهانە',
  'perfumes',
  190000.00,
  250000.00,
  4.80,
  92,
  'Signature',
  'مميز',
  'تایبەت',
  25,
  'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80","https://images.unsplash.com/photo-1547887537-6158d64c35b3?auto=format&fit=crop&w=800&q=80","https://images.unsplash.com/photo-1523293182086-7651a899d37f?auto=format&fit=crop&w=800&q=80"]',
  '["Gold Edition Bottle","Smoked Obsidian Flacon","Dark Amber Flacon"]',
  '["100ml / 3.4 oz","50ml / 1.7 oz"]',
  '{"100ml / 3.4 oz":"Height: 14.5 cm • Width: 6.2 cm • Depth: 4.5 cm • Net: 100 ml (3.4 fl oz)","50ml / 1.7 oz":"Height: 11.2 cm • Width: 5.0 cm • Depth: 3.8 cm • Net: 50 ml (1.7 fl oz)"}',
  'An intoxicating, long-lasting fragrance blending aged Cambodian oud, warm golden amber, Madagascar vanilla, and Damascus rose. Lasts over 24 hours.',
  'عطر ساحر وثابت يمزج بين العود الكمبودي الفاخر والعنبر الذهبي الدافئ والفانيليا وزهور دمشق. يدوم لأكثر من 24 ساعة.',
  'عەترەکێ گەلەک خوش و بێهنا وی گەلەک دمینیت ژ عوودێ کەمبۆدی، عەنبەرێ زێڕین و گولێن دیمەشقێ. پتر ژ 24 دەمژمێران دمینیت.',
  1
),
(
  7,
  'Nocturne Noir Rose Extrait de Parfum',
  'عطر نوكتورن نوار روز المركز',
  'عەترێ نوکتۆرن نوار رۆز یێ خەست',
  'perfumes',
  230000.00,
  295000.00,
  4.90,
  83,
  'Limited',
  'إصدار محدود',
  'چاپی یا کێم',
  18,
  'https://images.unsplash.com/photo-1547887537-6158d64c35b3?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1547887537-6158d64c35b3?auto=format&fit=crop&w=800&q=80","https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80"]',
  '["Black Crystal Flacon"]',
  '["100ml / 3.4 oz"]',
  '{"100ml / 3.4 oz":"Pure Extrait concentration 30% oils • Lasts 36+ hours on skin and fabrics"}',
  'Dark and mysterious blend of Turkish Black Rose, saffron, leather, patchouli, and white musk.',
  'توليفة ساحرة من الورد التركي الأسود، الزعفران، الجلد، والباتشولي والمسك الأبيض.',
  'تێکەلەکێ سەرسۆڕهێنەر ژ گولێن رەش یێن تورکی، زەعفەران، چەرم و مسکا سپی.',
  0
),
(
  11,
  'Imperial Sandalwood & White Musk',
  'عطر الصندل الإمبراطوري والمسك الأبيض',
  'عەترێ سەندەلا ئیمپراتۆری و مسکا سپی',
  'perfumes',
  175000.00,
  225000.00,
  4.80,
  67,
  'Top Rated',
  'الأعلى تقييماً',
  'بلندترین هەلسەنگاندن',
  20,
  'https://images.unsplash.com/photo-1523293182086-7651a899d37f?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1523293182086-7651a899d37f?auto=format&fit=crop&w=800&q=80"]',
  '["Crystal & Gold Decanter"]',
  '["100ml / 3.4 oz"]',
  '{"100ml / 3.4 oz":"Sandalwood Mysore 100% natural extract • Bergamot & Pure Musk"}',
  'Creamy Mysore sandalwood paired with clean white musk, Italian bergamot, and cedarwood.',
  'خشب الصندل الميسوري الكريمي مع المسك الأبيض النقي والبرغموت الإيطالي وخشب الأرز.',
  'داری سەندەل یێ تایبەت دگەل مسکا پاقژ و بەرگامۆتا ئیتالی و داری سەروو.',
  0
),

-- --- ACCESSORIES CATEGORY ---
(
  4,
  'Italian Leather Minimalist Weekender Bag',
  'حقيبة عطلة نهاية الأسبوع من الجلد الإيطالي',
  'جانتا چەرمێ ئیتالی یا گەشت و دەوامێ',
  'accessories',
  275000.00,
  370000.00,
  4.90,
  45,
  'Handcrafted',
  'صناعة يدوية',
  'دەستکرد',
  11,
  'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80","https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=800&q=80","https://images.unsplash.com/photo-1590874103328-eac38a683ce7?auto=format&fit=crop&w=800&q=80"]',
  '["Vintage Tan","Deep Espresso","Matte Black"]',
  '["Standard 45L"]',
  '{"Standard 45L":"Length: 52 cm • Height: 30 cm • Width: 26 cm • Handle Drop: 18 cm"}',
  'Crafted from full-grain vegetable-tanned Italian leather with solid brass hardware, YKK zippers, and padded laptop compartment.',
  'مصنوعة من جلد إيطالي أصلي كامل الحبيبات مع إكسسوارات من النحاس الصلب وسحابات متينة وحجرة مبطنة للكمبيوتر المحمول.',
  'ژ چەرمێ ئیتالی یێ رەسەن و پاقژ هاتیە چێکرن دگەل قفلێن برۆنز و جهێ لەپتۆپی یێ تایبەت.',
  1
),
(
  8,
  'Artisan Braided Leather Belt with Gold Buckle',
  'حزام جلد يدوي الصنع بإبزيم ذهبي',
  'قایشا چەرمی یا دەستچێکری ب قفلا زێڕین',
  'accessories',
  95000.00,
  130000.00,
  4.70,
  39,
  'Handmade',
  'يدوي',
  'دەستچێکری',
  30,
  'https://images.unsplash.com/photo-1624222247344-550fb60583dc?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1624222247344-550fb60583dc?auto=format&fit=crop&w=800&q=80"]',
  '["Cognac Brown","Matte Black"]',
  '["90 cm (32-34)","100 cm (36-38)","110 cm (40-42)"]',
  '{"90 cm (32-34)":"Width: 3.5 cm • Total Length: 105 cm","100 cm (36-38)":"Width: 3.5 cm • Total Length: 115 cm","110 cm (40-42)":"Width: 3.5 cm • Total Length: 125 cm"}',
  'Full-grain Spanish bridle leather, hand-braided and fitted with a 24K gold-plated solid brass buckle.',
  'جلد إسباني طبيعي مجدول يدوياً بدقة عالية مع إبزيم من النحاس الصلب مطلي بالذهب عيار 24.',
  'قایشا چەرمێ ئیسپانی یا رەسەن یا دەستچێکری دگەل قفلا مسێ هاتیە رووپۆشکرن ب زێڕێ 24 عەیار.',
  0
),
(
  12,
  'Hand-Polished Aviator Titanium Sunglasses',
  'نظارة أفياتور تيتانيوم مصقولة يدوياً',
  'چاڤیلکا ئەڤیاتۆر یا تیتانیۆم یا دەستپۆلیشکری',
  'accessories',
  145000.00,
  195000.00,
  4.80,
  58,
  'Trending',
  'رائج',
  'مۆدێلێ رۆژێ',
  15,
  'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=800&q=80"]',
  '["Gold Frame / Gradient Brown Lens","Matte Gunmetal / Polarized Gray"]',
  '["58mm Standard"]',
  '{"58mm Standard":"Lens Width: 58 mm • Bridge: 14 mm • Temple Length: 140 mm • 100% UV400 Polarized"}',
  'Japanese titanium featherlight frame with Zeiss polarized lenses and 100% UV400 protection.',
  'إطار تيتانيوم ياباني خفيف الوزن للغاية مع عدسات مستقطبة من زايس وحماية كاملة من الأشعة فوق البنفسجية.',
  'چاڤیلکەکا ژ تیتانیۆمێ ژاپۆنی یێ گەلەک سڤک دگەل عەدەسێن پۆلارایز یێن پلە ئێک.',
  0
)
ON DUPLICATE KEY UPDATE
`title_en` = VALUES(`title_en`),
`title_ar` = VALUES(`title_ar`),
`title_ku` = VALUES(`title_ku`),
`category` = VALUES(`category`),
`price` = VALUES(`price`),
`old_price` = VALUES(`old_price`),
`stock` = VALUES(`stock`),
`image` = VALUES(`image`),
`images` = VALUES(`images`),
`colors` = VALUES(`colors`),
`sizes` = VALUES(`sizes`),
`size_measurements` = VALUES(`size_measurements`),
`description_en` = VALUES(`description_en`),
`description_ar` = VALUES(`description_ar`),
`description_ku` = VALUES(`description_ku`),
`featured` = VALUES(`featured`);

-- ------------------------------------------------------------------------------
-- 2. SEED ALL TYPES OF ORDERS
-- Covers:
--  - Payment Methods: FIB, ZainCash, FastPay, Cash on Delivery (COD)
--  - Payment Statuses: Paid (FIB Verified), Paid (ZainCash), Paid (FastPay), Pending, Refunded
--  - Order Lifecycle Statuses: Received, Confirmed, Processing, Shipped, Out for Delivery, Delivered, Cancelled
--  - Cities: Duhok, Erbil, Sulaymaniyah, Baghdad, Basra, Zakho, Kirkuk
-- ------------------------------------------------------------------------------

INSERT INTO `orders` 
(`order_id`, `customer_name`, `customer_phone`, `customer_email`, `governorate`, `district`, `customer_address`, `subtotal`, `shipping_fee`, `discount_amount`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `courier`, `driver_name`, `driver_phone`, `tracking_code`, `dispatch_notes`, `estimated_delivery`, `items_json`)
VALUES
-- 1. FIB Payment | Out for Delivery | Luxury Watch + Blazer | Duhok
(
  'ORD-98421',
  'Shivan Berwari',
  '+964 750 442 8811',
  'shivan.berwari@gmail.com',
  'Duhok',
  'KRO District',
  'Villa 14, Near Dream City Gate',
  795000.00,
  0.00,
  0.00,
  795000.00,
  'First Iraqi Bank (FIB)',
  'Paid (FIB Verified)',
  'Out for Delivery',
  'Lezzoo Logistics Kurdistan',
  'Karwan Zaxo',
  '+964 750 331 9922',
  'LZ-DHK-99218',
  'Fragile luxury package. Customer requested delivery between 4:00 PM - 7:00 PM.',
  'Today by 6:00 PM',
  '[{"product_id":2,"title":"Onyx Skeleton Automatic Watch","price":550000,"quantity":1,"color":"Black Onyx & Gold","size":"41mm Case","image":"https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80"},{"product_id":1,"title":"Royal Midnight Velvet Blazer","price":245000,"quantity":1,"color":"Midnight Blue","size":"L","image":"https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80"}]'
),

-- 2. ZainCash Payment | Shipped in Transit | Perfume + Leather Bag | Baghdad
(
  'ORD-98422',
  'Dr. Hiba Al-Nuaimi',
  '+964 780 119 2200',
  'dr.hiba.nuaimi@yahoo.com',
  'Baghdad',
  'Al-Mansour',
  'Street 14, Near Baghdad Tower, Building 8',
  465000.00,
  0.00,
  0.00,
  465000.00,
  'ZainCash (زين كاش)',
  'Paid (ZainCash Verified)',
  'Shipped',
  'Sandooq Express Iraq',
  'Ammar Al-Janabi',
  '+964 780 554 1122',
  'SND-BGD-40192',
  'Express airway cargo to Baghdad International Airport Hub, dispatched to local courier.',
  'Tomorrow by 2:00 PM',
  '[{"product_id":3,"title":"Royal Amber & Smoked Oud Eau de Parfum","price":190000,"quantity":1,"color":"Gold Edition Bottle","size":"100ml / 3.4 oz","image":"https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80"},{"product_id":4,"title":"Italian Leather Minimalist Weekender Bag","price":275000,"quantity":1,"color":"Vintage Tan","size":"Standard 45L","image":"https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80"}]'
),

-- 3. FastPay Payment | Processing / Packaging | Swiss Watch | Sulaymaniyah
(
  'ORD-98423',
  'Hawre Qadir',
  '+964 770 882 3344',
  'hawre.qadir@outlook.com',
  'Sulaymaniyah',
  'Sarchinar',
  'Bakrajo Road, Gardenia Homes Apt 4B',
  550000.00,
  0.00,
  0.00,
  550000.00,
  'FastPay (فاست باي)',
  'Paid (FastPay Verified)',
  'Processing',
  'Fast Iraq Express Cargo',
  'Dyar Rostam',
  '+964 770 123 7890',
  'FX-SUL-88310',
  'Under quality inspection and luxury gift boxing in our Erbil logistics fulfillment center.',
  'In 2 Days',
  '[{"product_id":2,"title":"Onyx Skeleton Automatic Watch","price":550000,"quantity":1,"color":"Rose Gold","size":"41mm Case","image":"https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80"}]'
),

-- 4. Cash on Delivery (COD) | Confirmed | Multiple Perfumes | Erbil
(
  'ORD-98424',
  'Lana Peshraw',
  '+964 750 991 2233',
  'lana.peshraw@gmail.com',
  'Erbil',
  'Empire World',
  'Diamond Tower 2, Floor 14, Apt 1402',
  420000.00,
  0.00,
  0.00,
  420000.00,
  'Cash on Delivery (دفع عند الاستلام)',
  'Pending',
  'Confirmed',
  'Aura VIP White-Glove Courier',
  'Rebin Barzani',
  '+964 750 771 6655',
  'AUR-ERB-00192',
  'VIP customer white glove delivery with pre-notification via WhatsApp.',
  'Tomorrow by 11:00 AM',
  '[{"product_id":3,"title":"Royal Amber & Smoked Oud Eau de Parfum","price":190000,"quantity":1,"color":"Gold Edition Bottle","size":"100ml / 3.4 oz","image":"https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80"},{"product_id":7,"title":"Nocturne Noir Rose Extrait de Parfum","price":230000,"quantity":1,"color":"Black Crystal Flacon","size":"100ml / 3.4 oz","image":"https://images.unsplash.com/photo-1547887537-6158d64c35b3?auto=format&fit=crop&w=800&q=80"}]'
),

-- 5. ZainCash Payment | Cancelled & Refunded | Basra
(
  'ORD-98425',
  'Mustafa Al-Hilli',
  '+964 781 330 9988',
  'mustafa.hilli@gmail.com',
  'Basra',
  'Al-Jazaer',
  'Al-Ashar Corniche, Palm Residence 5',
  245000.00,
  0.00,
  0.00,
  245000.00,
  'ZainCash (زين كاش)',
  'Refunded',
  'Cancelled',
  'Fast Iraq Express Cargo',
  'N/A',
  'N/A',
  'CAN-BSR-40291',
  'Order cancelled upon customer request for size adjustment. Full payment refunded to ZainCash wallet.',
  'Cancelled & Refunded',
  '[{"product_id":1,"title":"Royal Midnight Velvet Blazer","price":245000,"quantity":1,"color":"Midnight Blue","size":"XL","image":"https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80"}]'
),

-- 6. FIB Payment | Delivered Successfully | Masterpiece Watch + Suit | Erbil Gulan
(
  'ORD-98426',
  'Zryan Farhad',
  '+964 750 118 7766',
  'zryan.farhad@gmail.com',
  'Erbil',
  'Gulan',
  'Gulan Park View Residence, Block C',
  1270000.00,
  0.00,
  0.00,
  1270000.00,
  'First Iraqi Bank (FIB)',
  'Paid (FIB Verified)',
  'Delivered',
  'Aura VIP White-Glove Courier',
  'Alan Merani',
  '+964 750 448 3311',
  'AUR-ERB-00188',
  'Hand delivered directly to customer. Signed receipt confirmed.',
  'Delivered on Aug 28, 2026',
  '[{"product_id":6,"title":"Celestial Diamond Chronograph","price":890000,"quantity":1,"color":"18K Rose Gold & Diamond Dial","size":"42mm Case","image":"https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=800&q=80"},{"product_id":5,"title":"Silk Jacquard Formal Evening Suit","price":380000,"quantity":1,"color":"Deep Emerald Jacquard","size":"50 (L)","image":"https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=800&q=80"}]'
),

-- 7. Cash on Delivery (COD) | Received (Brand New Order) | Sunglasses + Belt | Zakho
(
  'ORD-98427',
  'Nechirvan Zakholi',
  '+964 750 662 4433',
  'nechir.zakho@hotmail.com',
  'Zakho',
  'Pira Dalal Area',
  'Main Street, Near Old Bridge Plaza',
  240000.00,
  5000.00,
  0.00,
  245000.00,
  'Cash on Delivery (دفع عند الاستلام)',
  'Pending',
  'Received',
  'Lezzoo Logistics Kurdistan',
  'Pending Dispatch',
  'N/A',
  'LZ-ZKH-55019',
  'Brand new order placed via mobile checkout. Awaiting warehouse packaging queue.',
  'Within 2-3 Days',
  '[{"product_id":12,"title":"Hand-Polished Aviator Titanium Sunglasses","price":145000,"quantity":1,"color":"Gold Frame / Gradient Brown Lens","size":"58mm Standard","image":"https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=800&q=80"},{"product_id":8,"title":"Artisan Braided Leather Belt with Gold Buckle","price":95000,"quantity":1,"color":"Cognac Brown","size":"100 cm (36-38)","image":"https://images.unsplash.com/photo-1624222247344-550fb60583dc?auto=format&fit=crop&w=800&q=80"}]'
)
ON DUPLICATE KEY UPDATE
`customer_name` = VALUES(`customer_name`),
`customer_phone` = VALUES(`customer_phone`),
`customer_email` = VALUES(`customer_email`),
`governorate` = VALUES(`governorate`),
`district` = VALUES(`district`),
`customer_address` = VALUES(`customer_address`),
`subtotal` = VALUES(`subtotal`),
`shipping_fee` = VALUES(`shipping_fee`),
`discount_amount` = VALUES(`discount_amount`),
`total_amount` = VALUES(`total_amount`),
`payment_method` = VALUES(`payment_method`),
`payment_status` = VALUES(`payment_status`),
`order_status` = VALUES(`order_status`),
`courier` = VALUES(`courier`),
`driver_name` = VALUES(`driver_name`),
`driver_phone` = VALUES(`driver_phone`),
`tracking_code` = VALUES(`tracking_code`),
`dispatch_notes` = VALUES(`dispatch_notes`),
`estimated_delivery` = VALUES(`estimated_delivery`),
`items_json` = VALUES(`items_json`);

-- ------------------------------------------------------------------------------
-- 3. SEED USERS (Admin & Customers)
-- ------------------------------------------------------------------------------
INSERT INTO `users` (`user_code`, `name`, `email`, `password_hash`, `phone`, `city`, `address`, `role`)
VALUES
('USR-1001', 'Alind Duhoki', 'admin@aurastore.com', 'admin123', '+964 750 123 4567', 'Duhok', 'KRO Street, Duhok, Kurdistan Region', 'admin'),
('USR-1002', 'Soran Ahmed', 'soran@example.com', 'customer123', '+964 750 987 6543', 'Erbil', 'Empire World, Gulan St, Erbil', 'customer'),
('USR-1003', 'Hiba Al-Nuaimi', 'dr.hiba.nuaimi@yahoo.com', 'customer123', '+964 780 119 2200', 'Baghdad', 'Al-Mansour, Street 14, Building 8', 'customer')
ON DUPLICATE KEY UPDATE
`name` = VALUES(`name`),
`password_hash` = VALUES(`password_hash`),
`phone` = VALUES(`phone`),
`city` = VALUES(`city`),
`address` = VALUES(`address`),
`role` = VALUES(`role`);

-- ------------------------------------------------------------------------------
-- 4. SEED INQUIRIES & CLAIMS
-- ------------------------------------------------------------------------------
INSERT INTO `inquiries` (`inquiry_code`, `name`, `email`, `phone`, `subject`, `message`, `status`)
VALUES
('INQ-901', 'Barzan Mustafa', 'barzan.m@gmail.com', '+964 750 221 8899', 'Custom Swiss Watch Sizing Inquiry', 'Hello AURA team, I am interested in the Onyx Skeleton Automatic Watch. Can you adjust the stainless steel link bracelet before shipping to Duhok?', 'New'),
('INQ-902', 'Zaid Al-Bayati', 'zaid.bayati@baghdad.iq', '+964 780 445 1200', 'ZainCash Payment & Express Delivery to Mansour, Baghdad', 'Peace be upon you. Can I pay via ZainCash wallet and receive same-day or 24hr express delivery in Mansour, Baghdad?', 'Replied'),
('INQ-903', 'Shivan Berwari', 'shivan.berwari@gmail.com', '+964 750 442 8811', 'Delivery Time Request for ORD-98421', 'Please ensure the courier calls me 30 minutes before arriving at Dream City gate.', 'In Progress')
ON DUPLICATE KEY UPDATE
`name` = VALUES(`name`),
`email` = VALUES(`email`),
`phone` = VALUES(`phone`),
`subject` = VALUES(`subject`),
`message` = VALUES(`message`),
`status` = VALUES(`status`);

-- ------------------------------------------------------------------------------
-- 5. SEED REVIEWS
-- ------------------------------------------------------------------------------
INSERT INTO `reviews` (`id`, `product_id`, `user_name`, `rating`, `comment`, `date`) VALUES
(1, 2, 'Kawa Duhoki', 5, 'گەلەک دەمژمێرەکا جوانە و کوالێتیا وێ یا بێ وێنەیە! زوو گەهشت دەستێ من ل دهۆکێ.', '2026-08-15'),
(2, 3, 'Tariq Mansoor', 5, 'عطر العود والعنبر فواح جداً وثابت على الملابس أكثر من يومين. تجربة ممتازة.', '2026-08-12'),
(3, 1, 'Alexander Hayes', 5, 'The velvet texture and tailoring are absolute bespoke quality. Wore it to an award gala and received endless compliments.', '2026-08-10'),
(4, 4, 'Soran Ahmed', 5, 'جلد طبيعي فاخر جداً والمساحة ممتازة للسفر والعمل. شكراً لخدمة العملاء السريعة.', '2026-08-20')
ON DUPLICATE KEY UPDATE
`user_name` = VALUES(`user_name`),
`rating` = VALUES(`rating`),
`comment` = VALUES(`comment`),
`date` = VALUES(`date`);

SET FOREIGN_KEY_CHECKS = 1;
