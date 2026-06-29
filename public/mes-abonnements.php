<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/home_repository.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/public_layout.php';

if (!isset($_SESSION['utilisateur_id'])) { header('Location: connexion.php'); exit; }
$user_id = (int)$_SESSION['utilisateur_id'];

$user = [
    'prenom' => $_SESSION['utilisateur_prenom'] ?? '',
    'nom' => $_SESSION['utilisateur_nom'] ?? '',
    'email' => $_SESSION['utilisateur_email'] ?? '',
];

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $sub_id = (int) $_POST['sub_id'];
    try {
        api_client()->cancelSubscription($sub_id);
        $success = $lang==='en'?'Subscription cancelled. It remains active until the end of the current period.':($lang==='ar'?'تم إلغاء الاشتراك. يبقى نشطاً حتى نهاية الفترة الحالية.':($lang==='he'?'המנוי בוטל. הוא נשאר פעיל עד סוף התקופה הנוכחית.':'Abonnement résilié. Il reste actif jusqu\'à la fin de la période en cours.'));
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upgrade') {
    $sub_id = (int) ($_POST['sub_id'] ?? 0);
    $new_cycle = trim($_POST['new_cycle'] ?? '');
    if ($sub_id <= 0 || ! in_array($new_cycle, ['monthly', 'yearly'], true)) {
        $error = $lang==='en'?'Invalid cycle selection.':($lang==='ar'?'اختيار دورة غير صالح.':($lang==='he'?'בחירת מחזור לא תקינה.':'Cycle de facturation invalide.'));
    } else {
        try {
            api_client()->changeSubscriptionCycle($sub_id, $new_cycle);
            $success = $lang==='en'?'Billing cycle updated.':($lang==='ar'?'تم تحديث دورة الفوترة.':($lang==='he'?'מחזור החיוב עודכן.':'Cycle de facturation mis à jour.'));
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

try {
    $apiSubs = api_client()->getSubscriptions();
} catch (Throwable) {
    $apiSubs = [];
}

$subscriptions = array_map(function (array $sub) {
    $product = $sub['product'] ?? [];

    return [
        'id' => $sub['id'],
        'product_id' => $sub['product_id'],
        'cycle' => $sub['cycle'],
        'price' => $sub['price'],
        'status' => $sub['status'],
        'start_date' => $sub['start_date'],
        'next_billing' => $sub['next_billing'],
        'cancelled_at' => $sub['cancelled_at'] ?? null,
        'product_name' => $product['name'] ?? 'Service',
        'image_path' => $product['image_path'] ?? 'logo.jpg',
        'price_monthly' => $product['price_monthly'] ?? 0,
        'price_yearly' => $product['price_yearly'] ?? 0,
        'category_name' => $product['category']['name'] ?? '',
    ];
}, $apiSubs);

$active_count    = count(array_filter($subscriptions, function($s) { return $s['status'] === 'active'; }));
$cancelled_count = count(array_filter($subscriptions, function($s) { return $s['status'] === 'cancelled'; }));
$total_monthly   = array_sum(array_map(function($s) { return $s['status'] === 'active' ? (float)$s['price'] : 0; }, $subscriptions));

// Labels traduits
$lbl_active_subs  = $lang==='en'?'Active subscriptions':($lang==='ar'?' الاشتراكات النشطة':($lang==='he'?' מנויים פעילים':'Abonnements actifs'));
$lbl_cancel_subs  = $lang==='en'?'Cancelled subscriptions':($lang==='ar'?' الاشتراكات الملغاة':($lang==='he'?' מנויים שבוטלו':'Abonnements résiliés'));
$lbl_actifs_stat  = $lang==='en'?'Active':($lang==='ar'?'نشط':($lang==='he'?'פעיל':'Actifs'));
$lbl_resil_stat   = $lang==='en'?'Cancelled':($lang==='ar'?'ملغى':($lang==='he'?'בוטל':'Résiliés'));
$lbl_period       = $lang==='en'?'/ period':($lang==='ar'?'/ فترة':($lang==='he'?'/ תקופה':'/ période'));
$lbl_price        = $lang==='en'?'Price':($lang==='ar'?'السعر':($lang==='he'?'מחיר':'Prix'));
$lbl_cycle_lbl    = $lang==='en'?'Cycle':($lang==='ar'?'الدورة':($lang==='he'?'מחזור':'Cycle'));
$lbl_next_bill    = $lang==='en'?'Next renewal':($lang==='ar'?'التجديد القادم':($lang==='he'?'חידוש הבא':'Prochain renouvellement'));
$lbl_monthly_lbl  = $lang==='en'?'Monthly':($lang==='ar'?'شهري':($lang==='he'?'חודשי':'Mensuel'));
$lbl_annual_lbl   = $lang==='en'?'Annual':($lang==='ar'?'سنوي':($lang==='he'?'שנתי':'Annuel'));
$lbl_annual_save  = $lang==='en'?'Annual (save 10%)':($lang==='ar'?'سنوي (وفّر 10%)':($lang==='he'?'שנתי (חסוך 10%)':'Annuel (économisez 10%)'));
$lbl_change       = $lang==='en'?'Change':($lang==='ar'?'تغيير':($lang==='he'?'שנה':'Changer'));
$lbl_cancel_btn   = $lang==='en'?'Cancel':($lang==='ar'?' إلغاء':($lang==='he'?' בטל':'Résilier'));
$lbl_resubscribe  = $lang==='en'?'Re-subscribe':($lang==='ar'?' إعادة الاشتراك':($lang==='he'?' הירשם מחדש':'Se réabonner'));
$lbl_cancelled_on = $lang==='en'?'Cancelled on':($lang==='ar'?'تم الإلغاء في':($lang==='he'?'בוטל ב-':'Résilié le'));
$lbl_no_sub       = $lang==='en'?'No subscriptions yet.':($lang==='ar'?'لا توجد اشتراكات حتى الآن.':($lang==='he'?'אין מנויים עדיין.':'Aucun abonnement pour le moment.'));
$lbl_discover     = $lang==='en'?'Discover our services →':($lang==='ar'?'اكتشف خدماتنا →':($lang==='he'?'גלה את השירותים שלנו →':'Découvrir nos services →'));
$lbl_modal_title  = $lang==='en'?'Cancel subscription':($lang==='ar'?' إلغاء الاشتراك':($lang==='he'?' ביטול מנוי':'Résilier l\'abonnement'));
$lbl_modal_sub    = $lang==='en'?'Are you sure you want to cancel':($lang==='ar'?'هل أنت متأكد من رغبتك في إلغاء':($lang==='he'?'האם אתה בטוח שברצונך לבטל':'Voulez-vous vraiment résilier'));
$lbl_modal_info   = $lang==='en'?'The subscription remains active until the end of the current period. No refund will be issued.':($lang==='ar'?'يبقى الاشتراك نشطاً حتى نهاية الفترة الحالية. لن يتم استرداد أي مبلغ.':($lang==='he'?'המנוי נשאר פעיל עד סוף התקופה הנוכחית. לא יינתן החזר כספי.':'L\'abonnement reste actif jusqu\'à la fin de la période en cours. Aucun remboursement ne sera effectué.'));
$lbl_modal_cancel = $lang==='en'?'Cancel':($lang==='ar'?'إلغاء':($lang==='he'?'ביטול':'Annuler'));
$lbl_modal_confirm= $lang==='en'?'Confirm cancellation':($lang==='ar'?'تأكيد الإلغاء':($lang==='he'?'אשר ביטול':'Confirmer la résiliation'));
$lbl_active_badge = $lang==='en'?'Active':($lang==='ar'?'نشط':($lang==='he'?'פעיל':'Actif'));
$lbl_cancel_badge = $lang==='en'?'Cancelled':($lang==='ar'?'ملغى':($lang==='he'?'בוטל':'Résilié'));
$lbl_pending_cancel = $lang==='en'?'Cancellation scheduled':($lang==='ar'?'إلغاء مجدول':($lang==='he'?'ביטול מתוזמן':'Résiliation programmée'));
$lbl_active_until = $lang==='en'?'Active until':($lang==='ar'?'نشط حتى':($lang==='he'?'פעיל עד':'Actif jusqu\'au'));
?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — <?= t('my_subscriptions') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/css/public-mobile.css" rel="stylesheet">
  <link href="../assets/css/pages/legacy-navbar.css" rel="stylesheet">
  <link href="../assets/css/pages/compte-espace.css" rel="stylesheet">
  <link href="../assets/css/pages/mes-abonnements.css" rel="stylesheet">
</head>
<body>
  <nav class="navbar sticky-top legacy-nav">
    <div class="container-fluid px-3 px-lg-4">
      <a class="navbar-brand" href="../index.php">CYNA</a>
      <div class="d-flex align-items-center gap-2 ms-auto">
        <?= lang_switcher() ?>
        <a href="panier.php" class="nav-link-p">Panier <?= cyna_cart_badge_html() ?></a>
        <a href="deconnexion.php" class="nav-link-p"><?= t('nav_logout') ?></a>
      </div>
    </div>
  </nav>
  <div class="wrap">
    <aside class="sb">
      <div class="u-card">
        <div class="u-av"><?= strtoupper(substr($user['prenom']??'U',0,1)) ?></div>
        <div class="u-name"><?= htmlspecialchars(($user['prenom']??'').' '.($user['nom']??'')) ?></div>
        <div class="u-email"><?= htmlspecialchars($user['email']??'') ?></div>
      </div>
      <nav class="sb-nav">
        <a href="mon-compte.php?tab=profil"><?= t('profile') ?></a>
        <a href="mon-compte.php?tab=securite"><?= t('security') ?></a>
        <a href="adresses.php"><?= t('my_addresses') ?></a>
        <a href="paiements.php"><?= t('my_payments') ?></a>
        <a href="mes-abonnements.php" class="active">
          <?= t('my_subscriptions') ?>
          <span style="margin-left:auto;font-size:.65rem;background:rgba(74,222,128,.15);color:#4ade80;border-radius:20px;padding:1px 7px"><?= $active_count ?></span>
        </a>
        <a href="mes-commandes.php"><?= t('my_orders') ?></a>
        <a href="deconnexion.php" style="color:rgba(239,68,68,.6)"><?= t('nav_logout') ?></a>
      </nav>
    </aside>
    <main class="main">
      <?php if ($success): ?>
      <div class="alert-ok"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert-err"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <div class="stats-row">
        <div class="stat-box"><div class="stat-val"><?= $active_count ?></div><div class="stat-lbl"><?= $lbl_actifs_stat ?></div></div>
        <div class="stat-box"><div class="stat-val"><?= $cancelled_count ?></div><div class="stat-lbl"><?= $lbl_resil_stat ?></div></div>
        <div class="stat-box"><div class="stat-val"><?= number_format($total_monthly, 0, ',', ' ') ?> €</div><div class="stat-lbl"><?= $lbl_period ?></div></div>
      </div>
      <?php if (empty($subscriptions)): ?>
      <div class="empty">
        <div style="font-size:2.5rem;margin-bottom:12px;opacity:.3"></div>
        <p style="font-size:.88rem"><?= $lbl_no_sub ?></p>
        <a href="catalogue.php" style="color:var(--cyan);font-size:.85rem;text-decoration:none"><?= $lbl_discover ?></a>
      </div>
      <?php else: ?>
      <!-- ACTIFS -->
      <?php $actifs = array_filter($subscriptions, function($s) { return $s['status'] === 'active'; }); ?>
      <?php if ($actifs): ?>
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:10px"><?= $lbl_active_subs ?></div>
      <?php foreach ($actifs as $sub): ?>
      <div class="ccard">
        <div class="ccard-body">
          <div class="sub-top">
            <?php
              $simg  = $sub['image_path'] ?? '';
              $sname2 = strtoupper(substr($sub['product_name'], 0, 2));
              if ($simg): ?>
            <img class="sub-img" src="../<?= htmlspecialchars($simg) ?>" alt="<?= htmlspecialchars($sub['product_name']) ?>" onerror="this.onerror=null;this.style.display='none'">
            <?php else: ?>
            <div class="sub-img" style="display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;color:#fff;background:linear-gradient(135deg,#1a2980,#26d0ce)"><?= $sname2 ?></div>
            <?php endif; ?>
            <div class="sub-info">
              <div class="sub-name"><?= htmlspecialchars($sub['product_name']) ?></div>
              <div class="sub-cat"><?= htmlspecialchars($sub['category_name'] ?? '') ?></div>
            </div>
            <span class="badge-active">● <?= ! empty($sub['cancelled_at']) ? $lbl_pending_cancel : $lbl_active_badge ?></span>
          </div>
          <?php if (! empty($sub['cancelled_at'])): ?>
          <div style="font-size:.8rem;color:#fbbf24;margin-bottom:14px">
            <?= $lbl_active_until ?> <?= $sub['next_billing'] ? date('d/m/Y', strtotime($sub['next_billing'])) : '—' ?>
          </div>
          <?php endif; ?>
          <div class="sub-details">
            <div>
              <div class="sub-detail-label"><?= $lbl_price ?></div>
              <div class="sub-detail-val">
                <span class="sub-price"><?= number_format((float)$sub['price'],2,',',' ') ?> €</span>
                <span class="sub-period">/<?= $sub['cycle']==='yearly' ? ($lang==='en'?'yr':($lang==='ar'?'سنة':($lang==='he'?'שנה':'an'))) : ($lang==='en'?'mo':($lang==='ar'?'شهر':($lang==='he'?'חודש':'mois'))) ?></span>
              </div>
            </div>
            <div>
              <div class="sub-detail-label"><?= $lbl_cycle_lbl ?></div>
              <div class="sub-detail-val"><?= $sub['cycle']==='yearly' ? $lbl_annual_lbl : $lbl_monthly_lbl ?></div>
            </div>
            <div>
              <div class="sub-detail-label"><?= $lbl_next_bill ?></div>
              <div class="sub-detail-val"><?= $sub['next_billing'] ? date('d/m/Y', strtotime($sub['next_billing'])) : '—' ?></div>
            </div>
          </div>
          <?php if (empty($sub['cancelled_at'])): ?>
          <div class="sub-actions">
            <form method="POST" class="cycle-form">
              <input type="hidden" name="action" value="upgrade">
              <input type="hidden" name="sub_id" value="<?= (int)$sub['id'] ?>">
              <select name="new_cycle" class="cycle-select">
                <option value="monthly" <?= $sub['cycle']==='monthly'?'selected':'' ?>><?= $lbl_monthly_lbl ?></option>
                <option value="yearly"  <?= $sub['cycle']==='yearly'?'selected':'' ?>><?= $lbl_annual_save ?></option>
              </select>
              <button type="submit" class="btn-upgrade"><?= $lbl_change ?></button>
            </form>
            <button class="btn-cancel" onclick="openCancel(<?= (int)$sub['id'] ?>, '<?= htmlspecialchars(addslashes($sub['product_name'])) ?>')">
              <?= $lbl_cancel_btn ?>
            </button>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
      <!-- RÉSILIÉS -->
      <?php $resilies = array_filter($subscriptions, function($s) { return $s['status'] === 'cancelled'; }); ?>
      <?php if ($resilies): ?>
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin:20px 0 10px"><?= $lbl_cancel_subs ?></div>
      <?php foreach ($resilies as $sub): ?>
      <div class="ccard cancelled">
        <div class="ccard-body">
          <div class="sub-top">
            <?php
              $simg3  = $sub['image_path'] ?? '';
              $sname3 = strtoupper(substr($sub['product_name'], 0, 2));
              if ($simg3): ?>
            <img class="sub-img" src="../<?= htmlspecialchars($simg3) ?>" alt="<?= htmlspecialchars($sub['product_name']) ?>" onerror="this.onerror=null;this.style.display='none'">
            <?php else: ?>
            <div class="sub-img" style="display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;color:#fff;background:linear-gradient(135deg,#1a2980,#26d0ce)"><?= $sname3 ?></div>
            <?php endif; ?>
            <div class="sub-info">
              <div class="sub-name"><?= htmlspecialchars($sub['product_name']) ?></div>
              <div class="sub-cat"><?= htmlspecialchars($sub['category_name'] ?? '') ?></div>
            </div>
            <span class="badge-cancelled">● <?= $lbl_cancel_badge ?></span>
          </div>
          <div style="font-size:.8rem;color:var(--muted);margin-bottom:14px">
            <?= $lbl_cancelled_on ?> <?= $sub['cancelled_at'] ? date('d/m/Y', strtotime($sub['cancelled_at'])) : '—' ?>
          </div>
          <div class="sub-actions">
            <a href="produit.php?id=<?= (int)$sub['product_id'] ?>" class="btn-renew"><?= $lbl_resubscribe ?></a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
      <?php endif; ?>
    </main>
  </div>
  <!-- MODAL -->
  <div class="modal-bg" id="cancelModal">
    <div class="modal-box">
      <div class="modal-title"><?= $lbl_modal_title ?></div>
      <div class="modal-sub">
        <?= $lbl_modal_sub ?> <strong id="modal-product-name"></strong> ?<br>
        <?= $lbl_modal_info ?>
      </div>
      <form method="POST" id="cancelForm">
        <input type="hidden" name="action" value="cancel">
        <input type="hidden" name="sub_id" id="modal-sub-id">
        <div class="modal-actions">
          <button type="button" class="btn-modal-cancel" onclick="closeCancel()"><?= $lbl_modal_cancel ?></button>
          <button type="submit" class="btn-modal-confirm"><?= $lbl_modal_confirm ?></button>
        </div>
      </form>
    </div>
  </div>
  <footer>
    <a href="Cgu.php"><?= t('cgu') ?></a>
    <a href="mention_legales.php"><?= t('legal') ?></a>
    <a href="Contact.php"><?= t('contact') ?></a>
    <span><?= t('copyright') ?></span>
  </footer>
  <script>
  function openCancel(subId, productName) {
    document.getElementById('modal-sub-id').value = subId;
    document.getElementById('modal-product-name').textContent = productName;
    document.getElementById('cancelModal').classList.add('open');
  }
  function closeCancel() { document.getElementById('cancelModal').classList.remove('open'); }
  document.getElementById('cancelModal').addEventListener('click', function(e) { if (e.target === this) closeCancel(); });
  </script>
</body>
</html>