<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/public_layout.php';

if (!isset($_SESSION['utilisateur_id'])) { header('Location: connexion.php'); exit; }

$success = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = trim($_POST['card_id'] ?? '');
    if ($id !== '') {
        try {
            api_client()->deletePaymentMethod($id);
            $success = $lang==='en'?'Card deleted.':($lang==='ar'?'تم حذف البطاقة.':($lang==='he'?'הכרטיס נמחק.':'Carte supprimée.'));
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_default') {
    $id = trim($_POST['card_id'] ?? '');
    if ($id !== '') {
        try {
            api_client()->setDefaultPaymentMethod($id);
            $success = $lang==='en'?'Default card updated.':($lang==='ar'?'تم تحديث البطاقة الافتراضية.':($lang==='he'?'כרטיס ברירת המחדל עודכן.':'Carte par défaut mise à jour.'));
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $paymentMethodId = trim($_POST['payment_method'] ?? '');
    if ($paymentMethodId === '') {
        $errors[] = $lang==='en'?'Invalid card details.':($lang==='ar'?'تفاصيل البطاقة غير صالحة.':($lang==='he'?'פרטי כרטיס לא תקינים.':'Informations de carte invalides.'));
    } else {
        try {
            api_client()->addPaymentMethod($paymentMethodId);
            $success = $lang==='en'?'Card added successfully!':($lang==='ar'?'تمت إضافة البطاقة بنجاح!':($lang==='he'?'הכרטיס נוסף בהצלחה!':'Carte ajoutée avec succès !'));
            header('Location: paiements.php?added=1');
            exit;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

try {
    $cards = api_client()->getPaymentMethods();
} catch (Throwable) {
    $cards = [];
}

if (isset($_GET['added'])) {
    $success = $lang==='en'?'Card added successfully!':($lang==='ar'?'تمت إضافة البطاقة بنجاح!':($lang==='he'?'הכרטיס נוסף בהצלחה!':'Carte ajoutée avec succès !'));
}

$show_form = isset($_GET['new']) || !empty($errors);
$cur_year  = (int)date('Y');
$cur_month = (int)date('m');

try {
    $user = api_client()->getProfile();
} catch (Throwable) {
    $user = [
        'prenom' => $_SESSION['utilisateur_prenom'] ?? '',
        'nom' => $_SESSION['utilisateur_nom'] ?? '',
        'email' => $_SESSION['utilisateur_email'] ?? '',
    ];
}

try {
    $billingConfig = api_client()->getBillingConfig();
    $stripePublishableKey = $billingConfig['stripe_key'] ?? '';
} catch (Throwable) {
    require_once __DIR__ . '/../config/stripe_config.php';
    $stripePublishableKey = STRIPE_PUBLISHABLE_KEY;
}

function card_icon($brand) {
    $brand = ucfirst(strtolower((string) $brand));
    if ($brand === 'Mastercard') return '';
    if ($brand === 'Amex')       return '';
    if ($brand === 'Discover')   return '';
    return '';
}
function card_color($brand) {
    $brand = ucfirst(strtolower((string) $brand));
    if ($brand === 'Mastercard') return 'linear-gradient(135deg,#1a1a2e,#e63946)';
    if ($brand === 'Amex')       return 'linear-gradient(135deg,#003580,#0097b2)';
    if ($brand === 'Discover')   return 'linear-gradient(135deg,#e07b39,#c1440e)';
    return 'linear-gradient(135deg,#1a2980,#26d0ce)';
}
function card_brand_label($brand) {
    $brand = strtolower((string) $brand);
    return match ($brand) {
        'mastercard' => 'Mastercard',
        'amex' => 'Amex',
        'discover' => 'Discover',
        'visa' => 'Visa',
        default => ucfirst($brand),
    };
}

$lbl_add_card    = $lang==='en'?'Add a card':($lang==='ar'?'إضافة بطاقة':($lang==='he'?'הוסף כרטיס':'Ajouter une carte'));
$lbl_my_cards    = $lang==='en'?'My cards':($lang==='ar'?'بطاقاتي':($lang==='he'?'הכרטיסים שלי':'Mes cartes'));
$lbl_cards_count = $lang==='en'?'card(s)':($lang==='ar'?'بطاقة':($lang==='he'?'כרטיס(ים)':'carte(s)'));
$lbl_holder      = $lang==='en'?'Cardholder name *':($lang==='ar'?'اسم حامل البطاقة *':($lang==='he'?'שם בעל הכרטיס *':'Nom du titulaire *'));
$lbl_card_num    = $lang==='en'?'Card number *':($lang==='ar'?'رقم البطاقة *':($lang==='he'?'מספר הכרטיס *':'Numéro de carte *'));
$lbl_month       = $lang==='en'?'Month *':($lang==='ar'?'الشهر *':($lang==='he'?'חודש *':'Mois *'));
$lbl_year        = $lang==='en'?'Year *':($lang==='ar'?'السنة *':($lang==='he'?'שנה *':'Année *'));
$lbl_cvv         = $lang==='en'?'CVV *':($lang==='ar'?'CVV *':($lang==='he'?'CVV *':'CVV *'));
$lbl_add_btn     = $lang==='en'?'Add card':($lang==='ar'?'إضافة البطاقة':($lang==='he'?'הוסף כרטיס':'Ajouter la carte'));
$lbl_cancel      = t('cancel');
$lbl_add_plus    = '+ '.$lbl_add_card;
$lbl_no_cards    = $lang==='en'?'No saved cards.':($lang==='ar'?'لا توجد بطاقات محفوظة.':($lang==='he'?'אין כרטיסים שמורים.':'Aucune carte enregistrée.'));
$lbl_security_note = $lang==='en'?'Your card information is secured. Only the last 4 digits are stored. The full number is never kept on our servers (PCI-DSS compliant).':($lang==='ar'?'معلومات بطاقتك آمنة. يُخزّن فقط آخر 4 أرقام. لا يُحتفظ بالرقم الكامل على خوادمنا (متوافق مع PCI-DSS).':($lang==='he'?'פרטי הכרטיס שלך מאובטחים. רק 4 הספרות האחרונות נשמרות. המספר המלא לא נשמר בשרתינו (תואם PCI-DSS).':'Vos informations de carte sont sécurisées. Seuls les 4 derniers chiffres sont stockés. Le numéro complet n\'est jamais conservé sur nos serveurs (conforme PCI-DSS).'));
$lbl_expire      = $lang==='en'?'Expires':($lang==='ar'?'تنتهي':($lang==='he'?'פג':'Expire'));
$lbl_default_badge = $lang==='en'?'Default':($lang==='ar'?'افتراضي':($lang==='he'?'ברירת מחדל':'Par défaut'));
$lbl_expired_badge = $lang==='en'?'Expired':($lang==='ar'?' منتهية':($lang==='he'?' פג תוקף':'Expirée'));
$lbl_set_default = $lang==='en'?'Set as default':($lang==='ar'?'تعيين كافتراضي':($lang==='he'?'הגדר כברירת מחדל':'Définir par défaut'));
$lbl_delete      = $lang==='en'?'Delete':($lang==='ar'?' حذف':($lang==='he'?' מחק':'Supprimer'));
$lbl_confirm_del = $lang==='en'?'Delete this card?':($lang==='ar'?'حذف هذه البطاقة؟':($lang==='he'?'למחוק כרטיס זה?':'Supprimer cette carte ?'));
?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA — <?= t('my_payments') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <script src="https://js.stripe.com/v3/"></script>
  <link href="../assets/css/public-mobile.css" rel="stylesheet">
  <link href="../assets/css/pages/legacy-navbar.css" rel="stylesheet">
  <link href="../assets/css/pages/compte-espace.css" rel="stylesheet">
  <link href="../assets/css/pages/paiements.css" rel="stylesheet">
</head>
<body>
  <nav class="navbar navbar-expand-lg sticky-top legacy-nav">
    <div class="container-fluid px-3 px-lg-4">
      <a class="navbar-brand" href="../index.php">CYNA</a>
      <div class="d-flex align-items-center gap-2 ms-auto">
        <?= lang_switcher() ?>
        <a class="nav-link-p" href="panier.php">
          Panier <?= cyna_cart_badge_html() ?>
        </a>
        <a class="nav-link-p" href="deconnexion.php"><?= t('nav_logout') ?></a>
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
        <a href="paiements.php" class="active"><?= t('my_payments') ?></a>
        <a href="mes-abonnements.php"><?= t('my_subscriptions') ?></a>
        <a href="mes-commandes.php"><?= t('my_orders') ?></a>
        <a href="deconnexion.php" style="color:rgba(239,68,68,.6)"><?= t('nav_logout') ?></a>
      </nav>
    </aside>
    <main class="main">
      <?php if ($success): ?>
      <div class="a-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($errors): ?>
      <div class="a-error"><?php foreach($errors as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?></div>
      <?php endif; ?>
      <!-- FORM AJOUT -->
      <?php if ($show_form): ?>
      <div class="ccard">
        <div class="ccard-head"><?= $lbl_add_card ?></div>
        <div class="ccard-body">
          <div class="security-note mb-4">
            <span></span>
            <div><?= $lbl_security_note ?></div>
          </div>
          <form method="POST" action="paiements.php" id="add-card-form">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="payment_method" id="payment_method">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label"><?= $lbl_card_num ?></label>
                <div id="stripe-card-number" class="form-control" style="padding:11px 14px;min-height:44px"></div>
              </div>
              <div class="col-6">
                <label class="form-label"><?= $lbl_month ?> / <?= $lbl_year ?></label>
                <div id="stripe-card-expiry" class="form-control" style="padding:11px 14px;min-height:44px"></div>
              </div>
              <div class="col-6">
                <label class="form-label"><?= $lbl_cvv ?></label>
                <div id="stripe-card-cvc" class="form-control" style="padding:11px 14px;min-height:44px"></div>
              </div>
            </div>
            <div id="stripe-error" style="display:none;margin-top:12px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:10px 14px;font-size:.82rem;color:#f87171"></div>
            <div class="d-flex gap-3 mt-4">
              <button class="btn-save" type="submit" id="add-card-btn"><?= $lbl_add_btn ?></button>
              <a href="paiements.php" class="btn-cancel"><?= $lbl_cancel ?></a>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
      <!-- LISTE -->
      <div class="ccard">
        <div class="ccard-head">
          <?= $lbl_my_cards ?>
          <span style="font-weight:400;color:#5c6378"><?= count($cards) ?> <?= $lbl_cards_count ?></span>
          <?php if (!$show_form): ?>
          <a href="paiements.php?new=1" class="btn-add"><?= $lbl_add_plus ?></a>
          <?php endif; ?>
        </div>
        <div class="ccard-body">
          <?php if (!$cards): ?>
          <div style="text-align:center;padding:40px 0;color:#5c6378">
            <div style="font-size:2.5rem;margin-bottom:12px;opacity:.3"></div>
            <p style="font-size:.88rem"><?= $lbl_no_cards ?></p>
            <a href="paiements.php?new=1" class="btn-add" style="margin-top:12px"><?= $lbl_add_plus ?></a>
          </div>
          <?php else: ?>
          <div class="cards-grid">
            <?php foreach ($cards as $card):
                $brandLabel = card_brand_label($card['card_brand'] ?? 'visa');
                $is_expired = ((int) ($card['exp_year'] ?? 0) < $cur_year) || (((int) ($card['exp_year'] ?? 0) == $cur_year) && ((int) ($card['exp_month'] ?? 0) < $cur_month));
                $holderName = trim(($user['prenom'] ?? '').' '.($user['nom'] ?? ''));
            ?>
            <div>
              <div class="cc-visual" style="background:<?= card_color($brandLabel) ?>">
                <div class="cc-top">
                  <div class="cc-brand"><?= htmlspecialchars($brandLabel) ?> <?= card_icon($brandLabel) ?></div>
                  <div class="cc-chip">
                    <svg width="18" height="14"viewBox="0 0 18 14"><rect x="0" y="0"width="18" height="14"rx="2"fill="none"/><line x1="6" y1="0"x2="6" y2="14"stroke="rgba(0,0,0,.3)"stroke-width="1"/><line x1="12" y1="0"x2="12" y2="14"stroke="rgba(0,0,0,.3)"stroke-width="1"/><line x1="0" y1="5"x2="18" y2="5"stroke="rgba(0,0,0,.3)"stroke-width="1"/><line x1="0" y1="9"x2="18" y2="9"stroke="rgba(0,0,0,.3)"stroke-width="1"/></svg>
                  </div>
                </div>
                <div class="cc-number">•••• •••• •••• <?= htmlspecialchars($card['card_last4'] ?? '') ?></div>
                <div class="cc-bottom">
                  <div>
                    <div class="cc-holder"><?= htmlspecialchars($holderName) ?></div>
                    <?php if (! empty($card['is_default'])): ?><span class="default-badge"><?= $lbl_default_badge ?></span><?php endif; ?>
                    <?php if ($is_expired): ?><span class="expired-badge"><?= $lbl_expired_badge ?></span><?php endif; ?>
                  </div>
                  <div style="text-align:right">
                    <div class="cc-exp-label"><?= $lbl_expire ?></div>
                    <div class="cc-exp"><?= str_pad((string) ($card['exp_month'] ?? 0), 2, '0', STR_PAD_LEFT) ?>/<?= (int) ($card['exp_year'] ?? 0) ?></div>
                  </div>
                </div>
              </div>
              <div class="cc-actions">
                <?php if (empty($card['is_default'])): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="set_default">
                  <input type="hidden" name="card_id" value="<?= htmlspecialchars($card['id'] ?? '') ?>">
                  <button type="submit" class="btn-sm-def"><?= $lbl_set_default ?></button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('<?= $lbl_confirm_del ?>')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="card_id" value="<?= htmlspecialchars($card['id'] ?? '') ?>">
                  <button type="submit" class="btn-sm-del"><?= $lbl_delete ?></button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
  <footer>
    <a href="Cgu.php"><?= t('cgu') ?></a>
    <a href="mention_legales.php"><?= t('legal') ?></a>
    <a href="Contact.php"><?= t('contact') ?></a>
    <span><?= t('copyright') ?></span>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <?php if ($show_form && ! empty($stripePublishableKey)): ?>
  <script>
  var stripe = Stripe('<?= htmlspecialchars($stripePublishableKey) ?>');
  var elements = stripe.elements({ appearance: { theme: 'night', variables: { colorPrimary: '#26d0ce' } } });
  var cardNumber = elements.create('cardNumber', { style: { base: { color: '#e8eaf2', fontFamily: 'DM Sans, sans-serif' } } });
  var cardExpiry = elements.create('cardExpiry', { style: { base: { color: '#e8eaf2', fontFamily: 'DM Sans, sans-serif' } } });
  var cardCvc = elements.create('cardCvc', { style: { base: { color: '#e8eaf2', fontFamily: 'DM Sans, sans-serif' } } });
  cardNumber.mount('#stripe-card-number');
  cardExpiry.mount('#stripe-card-expiry');
  cardCvc.mount('#stripe-card-cvc');

  document.getElementById('add-card-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('add-card-btn');
    var errDiv = document.getElementById('stripe-error');
    btn.disabled = true;
    errDiv.style.display = 'none';
    stripe.createPaymentMethod({
      type: 'card',
      card: cardNumber,
      billing_details: {
        name: '<?= htmlspecialchars(trim(($user['prenom'] ?? '').' '.($user['nom'] ?? ''))) ?>',
        email: '<?= htmlspecialchars($user['email'] ?? '') ?>'
      }
    }).then(function(result) {
      if (result.error) {
        errDiv.textContent = result.error.message;
        errDiv.style.display = 'block';
        btn.disabled = false;
        return;
      }
      document.getElementById('payment_method').value = result.paymentMethod.id;
      e.target.submit();
    });
  });
  </script>
  <?php endif; ?>
</body>
</html>