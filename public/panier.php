<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
$est_connecte = isset($_SESSION['utilisateur_id']);

require_once __DIR__ . '/../includes/home_repository.php';
require_once __DIR__ . '/../includes/cart_repository.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/public_layout.php';

$cart = $_SESSION['cart'] ?? [];

if (isset($_GET['remove'])) {
    $rid = (int) $_GET['remove'];
    unset($_SESSION['cart'][$rid]);
    header('Location: panier.php');
    exit;
}

if (isset($_GET['update'])) {
    $pid = (int) $_GET['update'];
    $qty = (int) ($_GET['qty'] ?? 1);
    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'] = cart_set_quantity($_SESSION['cart'], $pid, $qty);
    }
    header('Location: panier.php');
    exit;
}

$items     = cart_get_products($connexion, $cart);

foreach ($items as $item) {
    if (isset($_SESSION['cart'][$item['id']])) {
        $_SESSION['cart'][$item['id']]['qty'] = $item['qty'];
    }
}

$total     = cart_total($items);
$nb_panier = cart_session_count();

if (isset($_SESSION['utilisateur_id'])) {
    $_SESSION['is_admin'] = (int) ($_SESSION['is_admin'] ?? 0);
}

// Labels
$lbl_title      = $lang==='en'?'Your cart':($lang==='ar'?' سلة التسوق':($lang==='he'?' העגלה שלך':'Votre panier'));
$lbl_home       = $lang==='en'?'Home':($lang==='ar'?'الرئيسية':($lang==='he'?'דף הבית':'Accueil'));
$lbl_cart       = $lang==='en'?'Cart':($lang==='ar'?'السلة':($lang==='he'?'עגלה':'Panier'));
$lbl_items_sel  = $lang==='en'?'service(s) selected':($lang==='ar'?'خدمة(خدمات) مختارة':($lang==='he'?'שירות(ים) נבחר(ים)':'service(s) sélectionné(s)'));
$lbl_empty_title= $lang==='en'?'Your cart is empty':($lang==='ar'?'سلتك فارغة':($lang==='he'?'העגלה שלך ריקה':'Votre panier est vide'));
$lbl_empty_sub  = $lang==='en'?'Explore our SaaS cybersecurity solutions and add services.':($lang==='ar'?'استكشف حلول الأمن السيبراني SaaS وأضف الخدمات.':($lang==='he'?'גלה את פתרונות אבטחת הסייבר SaaS שלנו והוסף שירותים.':'Explorez nos solutions de cybersécurité SaaS et ajoutez des services.'));
$lbl_see_cat    = $lang==='en'?'View catalogue':($lang==='ar'?'عرض الكتالوج':($lang==='he'?'צפה בקטלוג':'Voir le catalogue'));
$lbl_monthly    = $lang==='en'?'Monthly':($lang==='ar'?'شهري':($lang==='he'?'חודשי':'Mensuel'));
$lbl_yearly     = $lang==='en'?'Annual (-10%)':($lang==='ar'?'سنوي (-10%)':($lang==='he'?'שנתי (-10%)':'Annuel (-10%)'));
$lbl_per_month  = $lang==='en'?'/ mo':($lang==='ar'?'/ شهر':($lang==='he'?'/ חודש':'/ mois'));
$lbl_per_year   = $lang==='en'?'/ yr':($lang==='ar'?'/ سنة':($lang==='he'?'/ שנה':'/ an'));
$lbl_qty        = $lang==='en'?'Qty':($lang==='ar'?'الكمية':($lang==='he'?'כמות':'Qté'));
$lbl_unavail    = $lang==='en'?'Temporarily unavailable':($lang==='ar'?' غير متاح مؤقتاً':($lang==='he'?' זמנית לא זמין':'Temporairement indisponible'));
$lbl_continue   = $lang==='en'?'← Continue shopping':($lang==='ar'?'← مواصلة التسوق':($lang==='he'?'← המשך קניות':'← Continuer les achats'));
$lbl_recap      = $lang==='en'?'Summary':($lang==='ar'?'الملخص':($lang==='he'?'סיכום':'Récapitulatif'));
$lbl_total      = $lang==='en'?'Estimated total':($lang==='ar'?'الإجمالي المقدر':($lang==='he'?'סה"כ משוער':'Total estimé'));
$lbl_tax_note   = $lang==='en'?'Taxes not included. Prices may vary depending on the subscription.':($lang==='ar'?'الضرائب غير مشمولة. الأسعار قابلة للتغيير حسب الاشتراك.':($lang==='he'?'מסים אינם כלולים. המחירים עשויים להשתנות בהתאם למנוי.':'Taxes non incluses. Prix susceptibles de varier selon l\'abonnement.'));
$lbl_unavail_warn= $lang==='en'?'One or more services are unavailable. Remove them to continue.':($lang==='ar'?' خدمة واحدة أو أكثر غير متاحة. أزلها للمتابعة.':($lang==='he'?' שירות אחד או יותר אינו זמין. הסר אותם כדי להמשיך.':'Un ou plusieurs services sont indisponibles. Retirez-les pour continuer.'));
$lbl_checkout   = $lang==='en'?'Proceed to payment':($lang==='ar'?' المتابعة للدفع':($lang==='he'?' המשך לתשלום':'Passer au paiement'));
$lbl_login_pay  = $lang==='en'?'Login to pay':($lang==='ar'?' تسجيل الدخول للدفع':($lang==='he'?' התחבר לתשלום':'Connexion pour payer'));
$lbl_sec_pay    = $lang==='en'?'Secure payment':($lang==='ar'?'دفع آمن':($lang==='he'?'תשלום מאובטח':'Paiement sécurisé'));
$lbl_encrypted  = $lang==='en'?'Encrypted data':($lang==='ar'?'بيانات مشفرة':($lang==='he'?'נתונים מוצפנים':'Données chiffrées'));
$lbl_cancellable= $lang==='en'?'Cancellable':($lang==='ar'?'قابل للإلغاء':($lang==='he'?'ניתן לביטול':'Résiliable'));
$lbl_login_prompt_bold = $lang==='en'?'Sign in':($lang==='ar'?'تسجيل الدخول':($lang==='he'?'התחבר':'Connectez-vous'));
$lbl_login_prompt_text = $lang==='en'?'to save your cart and complete your order.':($lang==='ar'?'لحفظ سلتك وإتمام طلبك.':($lang==='he'?'כדי לשמור את העגלה ולהשלים את ההזמנה.':'pour sauvegarder votre panier et finaliser votre commande.'));
$lbl_login_btn  = $lang==='en'?'Login':($lang==='ar'?'دخول':($lang==='he'?'כניסה':'Connexion'));
$lbl_register_btn = t('nav_register');
$lbl_catalogue  = t('nav_catalogue');
$lbl_services   = $lang==='en'?'Services':($lang==='ar'?'الخدمات':($lang==='he'?'שירותים':'Services'));
$lbl_cart_empty_sub = $lang==='en'?'Your cart is empty':($lang==='ar'?'سلتك فارغة':($lang==='he'?'העגלה שלך ריקה':'Votre panier est vide'));
?>
<?php
cyna_public_head($lbl_cart, 'panier');
cyna_public_nav(false);
?>
<div class="cyna-page" style="max-width:900px">
  <!-- BREADCRUMB -->
  <div style="font-size:.75rem;color:var(--muted);margin-bottom:20px">
    <a href="../index.php" style="color:var(--muted);text-decoration:none"><?= $lbl_home ?></a>
    <span style="margin:0 8px;opacity:.4">›</span>
    <span style="color:#fff"><?= $lbl_cart ?></span>
  </div>
  <div class="page-title"><?= $lbl_title ?></div>
  <div class="page-sub">
    <?= $nb_panier > 0 ? $nb_panier.' '.$lbl_items_sel : $lbl_cart_empty_sub ?>
  </div>

  <?php if (count($items) === 0): ?>
  <div class="empty-cart">
    <div class="empty-icon"></div>
    <div class="empty-title"><?= $lbl_empty_title ?></div>
    <div class="empty-sub"><?= $lbl_empty_sub ?></div>
    <a href="catalogue.php" class="btn-cyna"><?= $lbl_see_cat ?></a>
  </div>
  <?php else: ?>
  <div class="row g-4">
    <!-- GAUCHE : items -->
    <div class="col-lg-8">
      <?php if (!$est_connecte): ?>
      <div class="login-prompt">
        <div class="login-prompt-text">
          <strong><?= $lbl_login_prompt_bold ?></strong> <?= $lbl_login_prompt_text ?>
        </div>
        <div class="d-flex gap-2">
          <a href="connexion.php" class="btn-outline-cyna" style="font-size:.78rem;padding:5px 14px"><?= $lbl_login_btn ?></a>
          <a href="inscription.php" class="btn-cyna" style="font-size:.78rem;padding:5px 14px"><?= $lbl_register_btn ?></a>
        </div>
      </div>
      <?php endif; ?>

      <div class="cart-list">
        <?php foreach ($items as $it): ?>
        <?php $qty = (int) ($it['qty'] ?? 1); ?>
        <div class="cart-item <?= $it['is_available'] ? '' : 'unavailable' ?>">
          <?php
            $img_url = image_display_src($it['image_path'] ?? '', '../');
            $initials = strtoupper(substr($it['name'], 0, 2));
          ?>
          <?php if ($img_url): ?>
          <img class="item-img" src="<?= htmlspecialchars($img_url) ?>" alt="<?= htmlspecialchars($it['name']) ?>"
               onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="item-img" style="display:none;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;color:#fff;background:linear-gradient(135deg,#1a2980,#26d0ce)"><?= $initials ?></div>
          <?php else: ?>
          <div class="item-img" style="display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;color:#fff;background:linear-gradient(135deg,#1a2980,#26d0ce)"><?= $initials ?></div>
          <?php endif; ?>

          <div class="item-info">
            <div class="item-name"><?= htmlspecialchars($it['name']) ?></div>
            <?php if (!$it['is_available']): ?>
            <span class="item-unavail"><?= $lbl_unavail ?></span>
            <?php else: ?>
            <?php
              $maxQty = (int) ($it['max_qty'] ?? 99);
              $canDec = $qty > 1;
              $canInc = $qty < $maxQty;
            ?>
            <div style="margin-top:6px;display:flex;flex-wrap:wrap;align-items:center;gap:10px">
              <select class="cycle-select"
                      data-id="<?= (int) $it['id'] ?>"
                      data-qty="<?= $qty ?>"
                      data-monthly="<?= $it['price_monthly'] ?>"
                      data-yearly="<?= $it['price_yearly'] ?>"
                      data-per-month="<?= $lbl_per_month ?>"
                      data-per-year="<?= $lbl_per_year ?>">
                <option value="monthly" <?= $it['cycle']==='monthly'?' selected':'' ?>><?= $lbl_monthly ?></option>
                <option value="yearly"  <?= $it['cycle']==='yearly'?' selected':'' ?>><?= $lbl_yearly ?></option>
              </select>
              <div class="qty-control" aria-label="<?= $lbl_qty ?>">
                <a href="<?= $canDec ? 'panier.php?update='.(int)$it['id'].'&qty='.($qty - 1) : '#' ?>"
                   class="qty-btn <?= $canDec ? '' : 'disabled' ?>">−</a>
                <span class="qty-value"><?= $qty ?></span>
                <a href="<?= $canInc ? 'panier.php?update='.(int)$it['id'].'&qty='.($qty + 1) : '#' ?>"
                   class="qty-btn <?= $canInc ? '' : 'disabled' ?>">+</a>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <div class="item-price" id="price-<?= (int) $it['id'] ?>">
            <?= number_format($it['line_total'], 2, ',', ' ') ?> €
            <div class="item-price-sub"><?= $qty ?> × <?= number_format($it['unit_price'], 2, ',', ' ') ?> €<?= $it['cycle']==='yearly' ? $lbl_per_year : $lbl_per_month ?></div>
          </div>
          <a href="panier.php?remove=<?= (int)$it['id'] ?>" class="btn-remove"></a>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <a href="catalogue.php" class="btn-outline-cyna"><?= $lbl_continue ?></a>
      </div>
    </div>

    <!-- DROITE : résumé -->
    <div class="col-lg-4">
      <div class="summary">
        <div class="summary-title"><?= $lbl_recap ?></div>
        <?php foreach ($items as $it): ?>
        <div class="summary-row">
          <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:60%"><?= htmlspecialchars($it['name']) ?> × <?= (int) ($it['qty'] ?? 1) ?></span>
          <span style="color:#e8eaf2;font-weight:600"><?= number_format($it['line_total'],2,',',' ') ?> €</span>
        </div>
        <?php endforeach; ?>
        <div class="summary-row total">
          <span><?= $lbl_total ?></span>
          <span class="summary-total-amount" id="total"><?= number_format($total,2,',',' ') ?> €</span>
        </div>
        <div class="summary-note"><?= $lbl_tax_note ?></div>
        <div class="actions">
          <?php $has_unavailable = count(array_filter($items, function($i) { return !$i['is_available']; })) > 0; ?>
          <?php if ($has_unavailable): ?>
          <div style="background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.2);border-radius:10px;padding:10px 14px;font-size:.78rem;color:#f87171">
            <?= $lbl_unavail_warn ?>
          </div>
          <?php else: ?>
          <a href="<?= $est_connecte ? 'checkout.php' : 'connexion.php?redirect=checkout.php' ?>"
             class="btn-cyna" style="justify-content:center;padding:13px">
            <?= $est_connecte ? $lbl_checkout : $lbl_login_pay ?>
          </a>
          <?php endif; ?>
        </div>
        <div class="secure-badges">
          <span class="secure-badge"><?= $lbl_sec_pay ?></span>
          <span class="secure-badge"><?= $lbl_encrypted ?></span>
          <span class="secure-badge">↩ <?= $lbl_cancellable ?></span>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<script>
document.querySelectorAll('.cycle-select').forEach(function(select) {
  select.addEventListener('change', function() {
    var id        = this.dataset.id;
    var qty       = parseInt(this.dataset.qty || '1', 10);
    var monthly   = parseFloat(this.dataset.monthly);
    var yearly    = parseFloat(this.dataset.yearly);
    var perMonth  = this.dataset.perMonth;
    var perYear   = this.dataset.perYear;
    var unitPrice = this.value === 'yearly' ? yearly : monthly;
    var lineTotal = unitPrice * qty;
    var period    = this.value === 'yearly' ? perYear : perMonth;

    var cell = document.getElementById('price-' + id);
    cell.innerHTML = lineTotal.toFixed(2).replace('.', ',') + ' €<div class="item-price-sub">' + qty + '× ' + unitPrice.toFixed(2).replace('.', ',') + ' €' + period + '</div>';

    var total = 0;
    document.querySelectorAll('[id^="price-"]').forEach(function(p) {
      var txt = p.firstChild ? p.firstChild.textContent : p.textContent;
      total += parseFloat(txt.replace(',', '.').replace('€','').trim()) || 0;
    });
    document.getElementById('total').textContent = total.toFixed(2).replace('.', ',') + ' €';
  });
});
</script>
<?php cyna_public_footer(); ?>