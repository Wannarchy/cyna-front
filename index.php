<?php
require_once __DIR__ . '/config/config.php';
cyna_session_start();

require_once __DIR__ . '/includes/home_repository.php';
require_once __DIR__ . '/includes/lang.php';

$est_connecte = isset($_SESSION['utilisateur_id']);
$nb_panier    = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));

$slides     = home_get_slides($connexion);
$homeText   = home_get_text($connexion, $lang);
$categories = home_get_categories($connexion);
$featured   = home_get_featured_products($connexion, 8);

// Labels traduits
$lbl_search_ph  = $lang==='en'?'Search a service (SOC, EDR, XDR...)':($lang==='ar'?'ابحث عن خدمة (SOC، EDR، XDR...)':($lang==='he'?'חפש שירות (SOC, EDR, XDR...)':'Rechercher un service (SOC, EDR, XDR...)'));
$lbl_search_btn = $lang==='en'?'Search':($lang==='ar'?'بحث':($lang==='he'?'חפש':'Chercher'));
$lbl_cart       = $lang==='en'?'Cart':($lang==='ar'?'السلة':($lang==='he'?'עגלה':'Panier'));
$lbl_explore    = $lang==='en'?'Explore':($lang==='ar'?'استكشف':($lang==='he'?'גלה':'Explorer'));
$lbl_categories = $lang==='en'?'Our categories':($lang==='ar'?'فئاتنا':($lang==='he'?'הקטגוריות שלנו':'Nos catégories'));
$lbl_selection  = $lang==='en'?'Selection':($lang==='ar'?'اختيار':($lang==='he'?'בחירה':'Sélection'));
$lbl_no_featured= $lang==='en'?'No featured products at the moment.':($lang==='ar'?'لا توجد منتجات مميزة في الوقت الحالي.':($lang==='he'?'אין מוצרים מומלצים כרגע.':'Aucun produit mis en avant pour le moment.'));
$lbl_per_month  = $lang==='en'?'/ mo':($lang==='ar'?'/ شهر':($lang==='he'?'/ חודש':'/ mois'));
$lbl_available  = $lang==='en'?'● Available':($lang==='ar'?'● متاح':($lang==='he'?'● זמין':'● Disponible'));
$lbl_unavailable= $lang==='en'?'● Unavailable':($lang==='ar'?'● غير متاح':($lang==='he'?'● לא זמין':'● Indisponible'));
$lbl_see        = $lang==='en'?'View →':($lang==='ar'?'عرض →':($lang==='he'?'צפה →':'Voir →'));
$lbl_discover   = $lang==='en'?'Discover →':($lang==='ar'?'اكتشف →':($lang==='he'?'גלה →':'Découvrir →'));
$lbl_footer_sub = $lang==='en'?'SaaS cybersecurity solutions<br>for businesses':($lang==='ar'?'حلول الأمن السيبراني SaaS<br>للشركات':($lang==='he'?'פתרונות אבטחת סייבר SaaS<br>לעסקים':'Solutions SaaS de cybersécurité<br>pour les entreprises'));
$lbl_legal      = $lang==='en'?'Legal':($lang==='ar'?'القانوني':($lang==='he'?'משפטי':'Légal'));
$lbl_support    = $lang==='en'?'Support':($lang==='ar'?'الدعم':($lang==='he'?'תמיכה':'Support'));
$lbl_account    = $lang==='en'?'Account':($lang==='ar'?'الحساب':($lang==='he'?'חשבון':'Compte'));
$lbl_social     = $lang==='en'?'Social':($lang==='ar'?'التواصل الاجتماعي':($lang==='he'?'רשתות חברתיות':'Réseaux'));
?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — <?= t('hero_title') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/css/public-mobile.css" rel="stylesheet">
  <link href="assets/css/pages/index.css" rel="stylesheet">
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg sticky-top cyna-navbar">
    <div class="container">
      <a class="navbar-brand" href="index.php">CYNA</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <form class="search-wrap my-2 my-lg-0" action="public/recherche.php" method="GET">
          <span class="search-icon"></span>
          <input type="search" name="q" placeholder="<?= $lbl_search_ph ?>">
          <button class="search-btn" type="submit"><?= $lbl_search_btn ?></button>
        </form>
        <div class="nav-links">
          <a href="public/catalogue.php"><?= t('nav_catalogue') ?></a>
          <a href="public/panier.php" class="cart-btn">
            <?= $lbl_cart ?>
            <?php if ($nb_panier > 0): ?>
              <span class="cart-count"><?= $nb_panier ?></span>
            <?php endif; ?>
          </a>
          <?php
          $is_admin = ! empty($_SESSION['is_admin']);
          ?>
          <?php if ($is_admin): ?>
            <a href="admin/index.php"
               style="display:inline-flex;align-items:center;gap:5px;background:rgba(139,92,246,.15);border:1px solid rgba(139,92,246,.25);color:#a78bfa;border-radius:20px;padding:5px 12px;font-size:.75rem;font-weight:700;text-decoration:none;transition:all .15s"
               onmouseover="this.style.background='rgba(139,92,246,.25)'"
               onmouseout="this.style.background='rgba(139,92,246,.15)'">
              Admin
            </a>
          <?php endif; ?>
          <?php if (!$est_connecte): ?>
            <a href="public/connexion.php"><?= t('nav_login') ?></a>
            <a href="public/inscription.php" class="btn-cyna"><?= t('nav_register') ?></a>
          <?php else: ?>
            <a href="public/mon-compte.php"><?= t('nav_account') ?></a>
            <a href="public/deconnexion.php"><?= t('nav_logout') ?></a>
          <?php endif; ?>
          <?= lang_switcher() ?>
        </div>
      </div>
    </div>
  </nav>

  <!-- HERO / CAROUSEL -->
  <section class="hero-section">
    <div class="container">
      <?php if (count($slides) > 0): ?>
        <div id="homeCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
          <div class="carousel-indicators">
            <?php foreach ($slides as $i => $s): ?>
              <button type="button"
                      data-bs-target="#homeCarousel"
                      data-bs-slide-to="<?= $i ?>"
                      class="<?= $i === 0 ? 'active' : '' ?>"
                      aria-label="Slide <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
          </div>
          <div class="carousel-inner rounded-4 overflow-hidden">
            <?php foreach ($slides as $i => $s): ?>
              <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                <div class="carousel-slide">
                  <img src="<?= htmlspecialchars(asset_image($s['image_path'] ?? null, $s['image_url'] ?? null)) ?>"
                       alt="<?= htmlspecialchars($s['title']) ?>">
                  <div class="carousel-overlay"></div>
                  <div class="carousel-caption">
                    <h2><?= htmlspecialchars(slide_title($s, $lang)) ?></h2>
                    <?php if (!empty(slide_subtitle($s, $lang))): ?>
                      <p><?= htmlspecialchars(slide_subtitle($s, $lang)) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($s['link_url'])): ?>
                      <a class="btn" href="<?= htmlspecialchars($s['link_url']) ?>"><?= $lbl_discover ?></a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
          </button>
        </div>
      <?php else: ?>
        <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:40px;text-align:center">
          <h2 style="font-weight:800;color:#fff"><?= t('hero_title') ?></h2>
          <p style="color:var(--muted)"><?= t('hero_sub') ?></p>
        </div>
      <?php endif; ?>
      <?php if (!empty($homeText)): ?>
        <div class="home-text"><?= nl2br(htmlspecialchars($homeText)) ?></div>
      <?php endif; ?>
    </div>
  </section>

  <!-- CATÉGORIES -->
  <?php if (count($categories) > 0): ?>
  <section class="section" style="background:#080d1c">
    <div class="container">
      <div class="section-label"><?= $lbl_explore ?></div>
      <div class="section-title"><?= $lbl_categories ?></div>
      <div class="row g-3">
        <?php foreach ($categories as $cat): ?>
          <div class="col-12 col-sm-6 col-lg-4">
            <a class="cat-card" href="public/catalogue.php?category_id=<?= (int)$cat['id'] ?>">
              <img class="cat-card-img"
                   src="<?= htmlspecialchars(asset_image($cat['image_path'] ?? null, $cat['image_url'] ?? null)) ?>"
                   alt="<?= htmlspecialchars($cat['name']) ?>">
              <div class="cat-card-body">
                <span class="cat-card-name"><?= htmlspecialchars($cat['name']) ?></span>
                <span class="cat-card-arrow">→</span>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- TOP PRODUITS -->
  <section class="section">
    <div class="container">
      <div class="section-label"><?= $lbl_selection ?></div>
      <div class="section-title"><?= t('featured_title') ?></div>
      <?php if (count($featured) === 0): ?>
        <div style="background:var(--card);border:1px solid var(--border);border-radius:14px;padding:32px;text-align:center;color:var(--muted)">
          <?= $lbl_no_featured ?>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($featured as $p):
            $avail = product_is_available($p);
            $statusLabel = product_status_label($p, $lang);
          ?>
            <div class="col-12 col-sm-6 col-lg-3">
              <a class="prod-card" href="public/produit.php?id=<?= (int)$p['id'] ?>" style="<?= $avail ? '' : 'opacity:.55' ?>">
                <img class="prod-card-img"
                     src="<?= htmlspecialchars(asset_image($p['image_path'] ?? null)) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>"
                     onerror="this.onerror=null;this.style.display='none'">
                <div class="prod-card-body">
                  <div class="prod-card-name"><?= htmlspecialchars($p['name']) ?></div>
                  <div class="prod-price-badge">
                    <span class="amount"><?= number_format((float)$p['price_monthly'], 2, ',', ' ') ?> €</span>
                    <span class="period"><?= $lbl_per_month ?></span>
                  </div>
                </div>
                <div class="prod-card-footer">
                  <span class="<?= $avail ? 'prod-available' : 'prod-unavailable' ?>"><?= htmlspecialchars($statusLabel) ?></span>
                  <span class="prod-cta"><?= $lbl_see ?></span>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="row g-4">
        <div class="col-12 col-md-4">
          <div class="footer-brand">CYNA</div>
          <div class="footer-sub"><?= $lbl_footer_sub ?></div>
        </div>
        <div class="col-6 col-md-2">
          <div class="footer-title"><?= $lbl_legal ?></div>
          <a class="footer-link" href="public/mention_legales.php"><?= t('legal') ?></a>
          <a class="footer-link" href="public/Cgu.php"><?= t('cgu') ?></a>
        </div>
        <div class="col-6 col-md-2">
          <div class="footer-title"><?= $lbl_support ?></div>
          <a class="footer-link" href="public/Contact.php"><?= t('contact') ?></a>
          <a class="footer-link" href="public/a-propos.php"><?= t('about') ?></a>
        </div>
        <div class="col-6 col-md-2">
          <div class="footer-title"><?= $lbl_account ?></div>
          <?php if ($est_connecte): ?>
            <a class="footer-link" href="public/mon-compte.php"><?= t('nav_account') ?></a>
            <a class="footer-link" href="public/mes-commandes.php"><?= t('my_orders') ?></a>
          <?php else: ?>
            <a class="footer-link" href="public/connexion.php"><?= t('nav_login') ?></a>
            <a class="footer-link" href="public/inscription.php"><?= t('nav_register') ?></a>
          <?php endif; ?>
        </div>
        <div class="col-6 col-md-2">
          <div class="footer-title"><?= $lbl_social ?></div>
          <a class="footer-link" href="#">LinkedIn</a>
          <a class="footer-link" href="#">X (Twitter)</a>
          <a class="footer-link" href="#">Facebook</a>
        </div>
      </div>
      <div class="footer-bottom">© 2025 CYNA-IT — 10 Rue de Penthièvre, 75008 Paris — SIRET : 91371103200015</div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>