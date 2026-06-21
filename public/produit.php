<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();

$est_connecte = isset($_SESSION['utilisateur_id']);

require_once __DIR__ . '/../includes/home_repository.php';
require_once __DIR__ . '/../includes/product_repository.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/public_layout.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); die("ID produit invalide."); }

$product = product_get_by_id($connexion, $id);
if (!$product) { http_response_code(404); die("Produit introuvable."); }

$isAvail     = product_is_available($product);
$statusLabel = product_status_label($product, $lang);
$gallerySlides = product_gallery_slides($product);
$desc        = product_desc_fallback($product['name'], $product['description'] ?? '');
$specs       = product_specs_fallback($product['technical_specs'] ?? []);
$similars    = product_get_similar($connexion, $product['category_id'] ? (int)$product['category_id'] : null, $id, 6);

if (isset($_SESSION['utilisateur_id'])) {
    $_SESSION['is_admin'] = (int) ($_SESSION['is_admin'] ?? 0);
}

$nb_panier = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));

// Labels traduits
$lbl_home         = $lang==='en'?'Home':($lang==='ar'?'الرئيسية':($lang==='he'?'דף הבית':'Accueil'));
$lbl_catalogue    = $lang==='en'?'Catalogue':($lang==='ar'?'الكتالوج':($lang==='he'?'קטלוג':'Catalogue'));
$lbl_avail        = $lang==='en'?'● Available immediately':($lang==='ar'?'● متاح فوراً':($lang==='he'?'● זמין מיד':'● Disponible immédiatement'));
$lbl_unavail      = $lang==='en'?'● Temporarily unavailable':($lang==='ar'?'● غير متاح مؤقتاً':($lang==='he'?'● זמנית לא זמין':'● Momentanément indisponible'));
$lbl_specs_head   = $lang==='en'?'Technical specifications':($lang==='ar'?'المواصفات التقنية':($lang==='he'?'מפרט טכני':'Caractéristiques techniques'));
$lbl_about_head   = $lang==='en'?'About this service':($lang==='ar'?'حول هذه الخدمة':($lang==='he'?'אודות שירות זה':'À propos de ce service'));
$lbl_about_text   = $lang==='en'?'This CYNA service is designed for companies looking to strengthen their cybersecurity posture with a modern SaaS solution. You benefit from rapid deployment, centralized visibility and continuous monitoring.':($lang==='ar'?'تم تصميم هذه الخدمة من CYNA للشركات التي تسعى إلى تعزيز وضعها الأمني السيبراني بحل SaaS حديث. استفد من النشر السريع والرؤية المركزية والمراقبة المستمرة.':($lang==='he'?'שירות CYNA זה מיועד לחברות המבקשות לחזק את עמדת אבטחת הסייבר שלהן עם פתרון SaaS מודרני. אתה נהנה מפריסה מהירה, נראות מרכזית וניטור רציף.':'Ce service CYNA est conçu pour les entreprises souhaitant renforcer leur posture de cybersécurité avec une solution SaaS moderne. Vous bénéficiez d\'une mise en place rapide, d\'une visibilité centralisée et d\'une supervision continue.'));
$lbl_monthly      = $lang==='en'?'Monthly':($lang==='ar'?'شهري':($lang==='he'?'חודשי':'Mensuel'));
$lbl_annual       = $lang==='en'?'Annual':($lang==='ar'?'سنوي':($lang==='he'?'שנתי':'Annuel'));
$lbl_per_month    = $lang==='en'?'per month':($lang==='ar'?'في الشهر':($lang==='he'?'לחודש':'par mois'));
$lbl_per_year     = $lang==='en'?'per year':($lang==='ar'?'في السنة':($lang==='he'?'לשנה':'par an'));
$lbl_subscribe    = $lang==='en'?'SUBSCRIBE NOW':($lang==='ar'?' اشترك الآن':($lang==='he'?' הירשם עכשיו':'S\'ABONNER MAINTENANT'));
$lbl_unavail_btn  = $lang==='en'?'SERVICE UNAVAILABLE':($lang==='ar'?' الخدمة غير متاحة':($lang==='he'?' שירות לא זמין':'SERVICE INDISPONIBLE'));
$lbl_cta_avail    = $lang==='en'?'You will choose the cycle (monthly / annual) in your cart.':($lang==='ar'?'ستختار دورة الفوترة (شهرية / سنوية) في سلة التسوق.':($lang==='he'?'תבחר את המחזור (חודשי / שנתי) בעגלת הקניות שלך.':'Vous choisirez le cycle (mensuel / annuel) dans votre panier.'));
$lbl_cta_unavail  = $lang==='en'?'This service is currently under maintenance or temporarily suspended.':($lang==='ar'?'هذه الخدمة تخضع حالياً للصيانة أو معلقة مؤقتاً.':($lang==='he'?'שירות זה נמצא כרגע בתחזוקה או מושהה זמנית.':'Ce service est actuellement en maintenance ou temporairement suspendu.'));
$lbl_security_h   = $lang==='en'?'Payment & security':($lang==='ar'?' الدفع والأمان':($lang==='he'?' תשלום ואבטחה':'Paiement & sécurité'));
$lbl_sec1         = $lang==='en'?'Secure payment (Stripe / PayPal)':($lang==='ar'?' دفع آمن (Stripe / PayPal)':($lang==='he'?' תשלום מאובטח (Stripe / PayPal)':'Paiement sécurisé (Stripe / PayPal)'));
$lbl_sec2         = $lang==='en'?'Data encrypted in transit and at rest':($lang==='ar'?' البيانات مشفرة أثناء النقل والتخزين':($lang==='he'?' נתונים מוצפנים בזמן העברה ואחסון':'Données chiffrées en transit et au repos'));
$lbl_sec3         = $lang==='en'?'Subscription cancellable anytime':($lang==='ar'?' الاشتراك قابل للإلغاء في أي وقت':($lang==='he'?' מנוי ניתן לביטול בכל עת':'Abonnement résiliable à tout moment'));
$lbl_sec4         = $lang==='en'?' 24/7 support included':($lang==='ar'?' دعم 24/7 مضمّن':($lang==='he'?' תמיכה 24/7 כלולה':'Support 24/7 inclus'));
$lbl_similars     = $lang==='en'?'Similar services':($lang==='ar'?'خدمات مشابهة':($lang==='he'?'שירותים דומים':'Services similaires'));
$lbl_see_cat      = $lang==='en'?'View category →':($lang==='ar'?'عرض الفئة →':($lang==='he'?'צפה בקטגוריה →':'Voir la catégorie →'));
$lbl_per_mo       = $lang==='en'?'/ mo':($lang==='ar'?'/ شهر':($lang==='he'?'/ חודש':'/ mois'));
$lbl_avail_badge  = $lang==='en'?'● Available':($lang==='ar'?'● متاح':($lang==='he'?'● זמין':'● Disponible'));
$lbl_unavail_badge= $lang==='en'?'● Unavailable':($lang==='ar'?'● غير متاح':($lang==='he'?'● לא זמין':'● Indisponible'));
$lbl_see          = $lang==='en'?'View →':($lang==='ar'?'عرض →':($lang==='he'?'צפה →':'Voir →'));
$lbl_search_ph    = $lang==='en'?'Search a service (SOC, EDR, XDR...)':($lang==='ar'?'ابحث عن خدمة (SOC, EDR, XDR...)':($lang==='he'?'חפש שירות (SOC, EDR, XDR...)':'Rechercher un service (SOC, EDR, XDR...)'));
$lbl_search_btn   = $lang==='en'?'Search':($lang==='ar'?'بحث':($lang==='he'?'חפש':'Chercher'));
$lbl_category     = $lang==='en'?'Category':($lang==='ar'?'الفئة':($lang==='he'?'קטגוריה':'Catégorie'));
?>
<?php
cyna_public_head($product['name'], 'produit');
cyna_public_nav(true);
?>
<div class="cyna-page">
  <!-- BREADCRUMB -->
  <div class="breadcrumb-nav">
    <a href="../index.php"><?= $lbl_home ?></a><span>›</span>
    <a href="catalogue.php"><?= $lbl_catalogue ?></a>
    <?php if (!empty($product['category_id'])): ?>
    <span>›</span>
    <a href="catalogue.php?category_id=<?= (int)$product['category_id'] ?>"><?= htmlspecialchars($product['category_name'] ?? $lbl_category) ?></a>
    <?php endif; ?>
    <span>›</span>
    <strong style="color:#fff"><?= htmlspecialchars($product['name']) ?></strong>
  </div>

  <!-- CARROUSEL + EN-TÊTE -->
  <?php if ($gallerySlides): ?>
  <div id="prodGallery" class="carousel slide prod-carousel" data-bs-ride="carousel">
    <?php if (count($gallerySlides) > 1): ?>
    <div class="carousel-indicators">
      <?php foreach ($gallerySlides as $i => $slide): ?>
      <button type="button" data-bs-target="#prodGallery" data-bs-slide-to="<?= $i ?>"
              <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?>
              aria-label="Slide <?= $i + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="carousel-inner">
      <?php foreach ($gallerySlides as $i => $slide): ?>
      <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
        <img src="<?= htmlspecialchars(image_display_src($slide['image_path'], '../')) ?>"
             alt="<?= htmlspecialchars($slide['caption'] !== '' ? $slide['caption'] : $product['name']) ?>">
        <?php if ($slide['caption'] !== ''): ?>
        <div class="carousel-caption d-none d-md-block">
          <p class="mb-0" style="font-size:.9rem;color:rgba(255,255,255,.85)"><?= htmlspecialchars($slide['caption']) ?></p>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (count($gallerySlides) > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#prodGallery" data-bs-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#prodGallery" data-bs-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Next</span>
    </button>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="prod-badges">
    <?php if (!empty($product['category_name'])): ?>
    <span class="badge-cat"><?= htmlspecialchars($product['category_name']) ?></span>
    <?php endif; ?>
    <?php if ($isAvail): ?>
    <span class="badge-avail"><?= htmlspecialchars($statusLabel) ?></span>
    <?php else: ?>
    <span class="badge-unavail"><?= htmlspecialchars($statusLabel) ?></span>
    <?php endif; ?>
  </div>
  <h1 class="prod-title"><?= htmlspecialchars($product['name']) ?></h1>
  <p class="prod-lead"><?= nl2br(htmlspecialchars($desc)) ?></p>

  <div class="row g-4">
    <!-- GAUCHE -->
    <div class="col-12 col-lg-8">
      <div class="info-card">
        <div class="info-card-head"><?= $lbl_about_head ?></div>
        <div class="info-card-body">
          <p class="about-text mb-0"><?= nl2br(htmlspecialchars($desc)) ?></p>
        </div>
      </div>
      <div class="info-card">
        <div class="info-card-head"><?= $lbl_specs_head ?></div>
        <div class="info-card-body">
          <ul class="specs-list">
            <?php foreach ($specs as $s): ?>
            <li><?= htmlspecialchars($s) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- DROITE : prix + CTA -->
    <div class="col-12 col-lg-4">
      <div class="price-card">
        <div class="price-row">
          <div class="price-block">
            <div class="price-label"><?= $lbl_monthly ?></div>
            <div class="price-amount"><?= number_format((float)$product['price_monthly'],2,',',' ') ?> €</div>
            <div class="price-period"><?= $lbl_per_month ?></div>
          </div>
          <div class="price-block price-annual">
            <div class="price-label"><?= $lbl_annual ?></div>
            <div class="price-amount"><?= number_format((float)$product['price_yearly'],2,',',' ') ?> €</div>
            <div class="price-period"><?= $lbl_per_year ?></div>
          </div>
        </div>
        <hr class="price-divider">
        <form action="panier_add.php" method="POST">
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
          <button type="submit" class="btn-subscribe" <?= $isAvail ? '' : 'disabled' ?>>
            <?= $isAvail ? $lbl_subscribe : $lbl_unavail_btn ?>
          </button>
        </form>
        <div class="cta-note">
          <?= $isAvail ? $lbl_cta_avail : $lbl_cta_unavail ?>
        </div>
        <?php if ($product['stock'] !== null): ?>
        <?php $stock = (int) $product['stock']; ?>
        <div class="stock-note" style="margin-top:8px;font-size:.82rem;font-weight:600;color:<?= $stock > 0 ? '#26d0ce' : '#fbbf24' ?>">
          <?php if ($stock > 0): ?>
          <?= $lang==='en' ? '● In stock' : ($lang==='ar' ? '● متوفر' : ($lang==='he' ? '● במלאי' : '● En stock')) ?>
          <?php else: ?>
          <?= $lang==='en' ? '● Out of stock' : ($lang==='ar' ? '● نفد المخزون' : ($lang==='he' ? '● אזל מהמלאי' : '● Rupture de stock')) ?>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="security-card">
        <h3><?= $lbl_security_h ?></h3>
        <div class="security-item"><?= $lbl_sec1 ?></div>
        <div class="security-item"><?= $lbl_sec2 ?></div>
        <div class="security-item"><?= $lbl_sec3 ?></div>
        <div class="security-item"><?= $lbl_sec4 ?></div>
      </div>
    </div>
  </div>

  <!-- SERVICES SIMILAIRES -->
  <?php if (count($similars) > 0): ?>
  <div style="margin-top:48px">
    <div class="section-header">
      <div class="section-title"><?= $lbl_similars ?></div>
      <?php if (!empty($product['category_id'])): ?>
      <a href="catalogue.php?category_id=<?= (int)$product['category_id'] ?>" class="btn-cat"><?= $lbl_see_cat ?></a>
      <?php endif; ?>
    </div>
    <div class="similar-grid">
      <?php foreach ($similars as $sp):
        $spAvail = product_is_available($sp);
      ?>
      <a class="prod-card" href="produit.php?id=<?= (int)$sp['id'] ?>" style="<?= $spAvail ? '' : 'opacity:.55' ?>">
        <img class="prod-card-img"
             src="<?= htmlspecialchars(image_display_src($sp['image_path'] ?? null, '../')) ?>"
             alt="<?= htmlspecialchars($sp['name']) ?>"
             onerror="this.onerror=null;this.style.display='none'">
        <div class="prod-card-body">
          <div class="prod-card-name"><?= htmlspecialchars($sp['name']) ?></div>
          <div class="prod-card-price"><?= number_format((float)$sp['price_monthly'],2,',',' ') ?> € <?= $lbl_per_mo ?></div>
        </div>
        <div class="prod-card-footer">
          <?php if ($spAvail): ?>
          <span class="b-avail"><?= $lbl_avail_badge ?></span>
          <?php else: ?>
          <span class="b-unavail"><?= $lbl_unavail_badge ?></span>
          <?php endif; ?>
          <span style="font-size:.75rem;font-weight:700;color:var(--cyan)"><?= $lbl_see ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php cyna_public_footer(); ?>