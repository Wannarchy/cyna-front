<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/address_helpers.php';
require_once __DIR__ . '/../includes/form_validation.php';

if (!isset($_SESSION['utilisateur_id'])) { header('Location: connexion.php'); exit; }

$success = '';
$errors  = [];
$edit    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)$_POST['address_id'];
    try {
        api_client()->deleteAddress($id);
        $success = $lang==='en' ? 'Address deleted.' : ($lang==='ar' ? 'تم حذف العنوان.' : ($lang==='he' ? 'הכתובת נמחקה.' : 'Adresse supprimée.'));
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_default') {
    $id = (int)$_POST['address_id'];
    try {
        api_client()->updateAddress($id, ['is_default' => true]);
        $success = $lang==='en' ? 'Default address updated.' : ($lang==='ar' ? 'تم تحديث العنوان الافتراضي.' : ($lang==='he' ? 'כתובת ברירת המחדל עודכנה.' : 'Adresse par défaut mise à jour.'));
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_default_shipping') {
    $id = (int)$_POST['address_id'];
    try {
        api_client()->updateAddress($id, ['is_default_shipping' => true]);
        $success = $lang==='en' ? 'Default delivery address updated.' : ($lang==='ar' ? 'تم تحديث عنوان التوصيل الافتراضي.' : ($lang==='he' ? 'כתובת המשלוח בברירת המחדל עודכנה.' : 'Adresse de livraison par défaut mise à jour.'));
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['create', 'update'])) {
    $action   = $_POST['action'];
    $aid      = (int)($_POST['address_id'] ?? 0);
    $prenom   = trim($_POST['prenom']      ?? '');
    $nom      = trim($_POST['nom']         ?? '');
    $adresse1 = trim($_POST['adresse1']    ?? '');
    $adresse2 = trim($_POST['adresse2']    ?? '');
    $ville    = trim($_POST['ville']       ?? '');
    $region   = trim($_POST['region']      ?? '');
    $code     = trim($_POST['code_postal'] ?? '');
    $pays     = trim($_POST['pays']        ?? 'France');
    $tel      = trim($_POST['telephone']   ?? '');
    $label    = trim($_POST['label']       ?? 'Adresse');
    $usage    = trim($_POST['usage_type'] ?? 'both');
    $isDefBill = ! empty($_POST['is_default']);
    $isDefShip = ! empty($_POST['is_default_shipping']);

    if (! in_array($usage, ['billing', 'shipping', 'both'], true)) {
        $usage = 'both';
    }

    if (empty($prenom))   $errors[] = $lang==='en' ? 'First name required.' : ($lang==='ar' ? 'الاسم الأول مطلوب.' : ($lang==='he' ? 'שם פרטי נדרש.' : 'Le prénom est requis.'));
    if (empty($nom))      $errors[] = $lang==='en' ? 'Last name required.'  : ($lang==='ar' ? 'اسم العائلة مطلوب.' : ($lang==='he' ? 'שם משפחה נדרש.' : 'Le nom est requis.'));
    if (empty($adresse1)) $errors[] = $lang==='en' ? 'Address required.'    : ($lang==='ar' ? 'العنوان مطلوب.' : ($lang==='he' ? 'כתובת נדרשת.' : "L'adresse est requise."));
    if (empty($ville))    $errors[] = $lang==='en' ? 'City required.'       : ($lang==='ar' ? 'المدينة مطلوبة.' : ($lang==='he' ? 'עיר נדרשת.' : 'La ville est requise.'));
    if (empty($code))     $errors[] = $lang==='en' ? 'Postal code required.': ($lang==='ar' ? 'الرمز البريدي مطلوب.' : ($lang==='he' ? 'מיקוד נדרש.' : 'Le code postal est requis.'));
    if (empty($pays))     $errors[] = $lang==='en' ? 'Country required.'    : ($lang==='ar' ? 'الدولة مطلوبة.' : ($lang==='he' ? 'מדינה נדרשת.' : 'Le pays est requis.'));

    if (empty($errors)) {
        $payload = address_from_input([
            'label' => $label,
            'usage_type' => $usage,
            'prenom' => $prenom,
            'nom' => $nom,
            'adresse1' => $adresse1,
            'adresse2' => $adresse2,
            'ville' => $ville,
            'region' => $region,
            'code_postal' => $code,
            'pays' => $pays,
            'telephone' => $tel,
        ]);
        $payload['is_default'] = $isDefBill;
        $payload['is_default_shipping'] = $isDefShip;
        try {
            if ($action === 'create') {
                api_client()->createAddress($payload);
                $success = $lang==='en' ? 'Address added!' : ($lang==='ar' ? 'تمت إضافة العنوان!' : ($lang==='he' ? 'הכתובת נוספה!' : 'Adresse ajoutée avec succès !'));
            } else {
                api_client()->updateAddress($aid, $payload);
                $success = $lang==='en' ? 'Address updated!' : ($lang==='ar' ? 'تم تحديث العنوان!' : ($lang==='he' ? 'הכתובת עודכנה!' : 'Adresse mise à jour !'));
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

try {
    $addresses = api_client()->getAddresses();
} catch (Throwable) {
    $addresses = [];
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($addresses as $address) {
        if ((int) ($address['id'] ?? 0) === $editId) {
            $edit = $address;
            break;
        }
    }
}

$nb_panier = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
$usageOptions = address_usage_type_options($lang);
$show_form = isset($_GET['new']) || $edit || !empty($errors);

try {
    $user = api_client()->getProfile();
} catch (Throwable) {
    $user = [
        'prenom' => $_SESSION['utilisateur_prenom'] ?? '',
        'nom' => $_SESSION['utilisateur_nom'] ?? '',
        'email' => $_SESSION['utilisateur_email'] ?? '',
    ];
}
?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA — <?= t('my_addresses') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="../assets/css/public-mobile.css" rel="stylesheet">
  <link href="../assets/css/pages/legacy-navbar.css" rel="stylesheet">
  <link href="../assets/css/pages/compte-espace.css" rel="stylesheet">
  <link href="../assets/css/pages/adresses.css" rel="stylesheet">
</head>
<body>
  <nav class="navbar navbar-expand-lg sticky-top legacy-nav">
    <div class="container-fluid px-3 px-lg-4">
      <a class="navbar-brand" href="../index.php">CYNA</a>
      <div class="d-flex align-items-center gap-2 ms-auto">
        <a class="nav-link" href="panier.php"><?= $nb_panier > 0 ? "($nb_panier)" : '' ?></a>
        <a class="nav-link" href="deconnexion.php"><?= t('nav_logout') ?></a>
        <?= lang_switcher() ?>
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
        <a href="adresses.php" class="active"><?= t('my_addresses') ?></a>
        <a href="paiements.php"><?= t('my_payments') ?></a>
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
      <?php if ($show_form): ?>
      <div class="ccard">
        <div class="ccard-head">
          <?= $edit ? ($lang==='en'?'Edit address':($lang==='ar'?'تعديل العنوان':($lang==='he'?'ערוך כתובת':'Modifier une adresse'))) : ($lang==='en'?'New address':($lang==='ar'?'عنوان جديد':($lang==='he'?'כתובת חדשה':'Nouvelle adresse'))) ?>
        </div>
        <div class="ccard-body">
          <form method="POST" action="adresses.php" data-cyna-validate="address">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
            <?php if ($edit): ?><input type="hidden" name="address_id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label"><?= $lang==='en'?'Label':($lang==='ar'?'التسمية':($lang==='he'?'תווית':'Libellé')) ?></label>
                <input class="form-control" name="label" value="<?= htmlspecialchars($_POST['label'] ?? $edit['label'] ?? 'Adresse') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label"><?= $lang==='en'?'Usage':($lang==='ar'?'الاستخدام':($lang==='he'?'שימוש':'Utilisation')) ?> *</label>
                <?php $selectedUsage = $_POST['usage_type'] ?? $edit['usage_type'] ?? 'both'; ?>
                <select class="form-select" name="usage_type" required>
                  <?php foreach ($usageOptions as $value => $labelOpt): ?>
                  <option value="<?= htmlspecialchars($value) ?>" <?= $selectedUsage === $value ? 'selected' : '' ?>><?= htmlspecialchars($labelOpt) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label"><?= $lang==='en'?'First name':($lang==='ar'?'الاسم الأول':($lang==='he'?'שם פרטי':'Prénom')) ?> *</label>
                <input class="form-control" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? $edit['prenom'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label"><?= $lang==='en'?'Last name':($lang==='ar'?'اسم العائلة':($lang==='he'?'שם משפחה':'Nom')) ?> *</label>
                <input class="form-control" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? $edit['nom'] ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label"><?= $lang==='en'?'Address line 1':($lang==='ar'?'العنوان سطر 1':($lang==='he'?'כתובת שורה 1':'Adresse ligne 1')) ?> *</label>
                <input class="form-control" name="adresse1" required value="<?= htmlspecialchars($_POST['adresse1'] ?? $edit['adresse1'] ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label"><?= $lang==='en'?'Address line 2':($lang==='ar'?'العنوان سطر 2':($lang==='he'?'כתובת שורה 2':'Adresse ligne 2')) ?></label>
                <input class="form-control" name="adresse2" value="<?= htmlspecialchars($_POST['adresse2'] ?? $edit['adresse2'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label"><?= $lang==='en'?'Postal code':($lang==='ar'?'الرمز البريدي':($lang==='he'?'מיקוד':'Code postal')) ?> *</label>
                <input class="form-control" name="code_postal" required value="<?= htmlspecialchars($_POST['code_postal'] ?? $edit['code_postal'] ?? '') ?>">
              </div>
              <div class="col-md-5">
                <label class="form-label"><?= $lang==='en'?'City':($lang==='ar'?'المدينة':($lang==='he'?'עיר':'Ville')) ?> *</label>
                <input class="form-control" name="ville" required value="<?= htmlspecialchars($_POST['ville'] ?? $edit['ville'] ?? '') ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label"><?= $lang==='en'?'Region':($lang==='ar'?'المنطقة':($lang==='he'?'אזור':'Région')) ?></label>
                <input class="form-control" name="region" value="<?= htmlspecialchars($_POST['region'] ?? $edit['region'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label"><?= $lang==='en'?'Country':($lang==='ar'?'الدولة':($lang==='he'?'מדינה':'Pays')) ?> *</label>
                <select class="form-select" name="pays">
                  <?php $selectedPays = $_POST['pays'] ?? $edit['pays'] ?? 'France'; ?>
                  <?php foreach (address_country_options() as $p): ?>
                  <option <?= $selectedPays === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label"><?= $lang==='en'?'Phone':($lang==='ar'?'الهاتف':($lang==='he'?'טלפון':'Téléphone')) ?></label>
                <input class="form-control" name="telephone" type="tel" value="<?= htmlspecialchars($_POST['telephone'] ?? $edit['telephone'] ?? '') ?>">
              </div>
              <div class="col-12">
                <div class="d-flex flex-wrap gap-4" style="font-size:.84rem;color:rgba(255,255,255,.65)">
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="is_default" value="1" <?= (isset($_POST['action']) ? ! empty($_POST['is_default']) : ! empty($edit['is_default'])) ? 'checked' : '' ?>>
                    <?= $lang==='en'?'Default billing address':($lang==='ar'?'عنوان الفوترة الافتراضي':($lang==='he'?'כתובת חיוב ברירת מחדל':'Adresse de facturation par défaut')) ?>
                  </label>
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="is_default_shipping" value="1" <?= (isset($_POST['action']) ? ! empty($_POST['is_default_shipping']) : ! empty($edit['is_default_shipping'])) ? 'checked' : '' ?>>
                    <?= $lang==='en'?'Default delivery address':($lang==='ar'?'عنوان التوصيل الافتراضي':($lang==='he'?'כתובת משלוח ברירת מחדל':'Adresse de livraison par défaut')) ?>
                  </label>
                </div>
              </div>
            </div>
            <div class="d-flex gap-3 mt-4">
              <button class="btn-save" type="submit"><?= $edit ? t('save') : ($lang==='en'?'Add address':($lang==='ar'?'إضافة عنوان':($lang==='he'?'הוסף כתובת':'Ajouter'))) ?></button>
              <a href="adresses.php" class="btn-cancel"><?= t('cancel') ?></a>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
      <div class="ccard">
        <div class="ccard-head">
          <?= t('my_addresses') ?>
          <span style="font-weight:400;color:#5c6378"><?= count($addresses) ?></span>
          <?php if (!$show_form): ?>
          <a href="adresses.php?new=1" class="btn-add">+ <?= $lang==='en'?'Add':($lang==='ar'?'إضافة':($lang==='he'?'הוסף':'Ajouter')) ?></a>
          <?php endif; ?>
        </div>
        <div class="ccard-body">
          <?php if (!$addresses): ?>
          <div style="text-align:center;padding:40px 0;color:#5c6378">
            <div style="font-size:2.5rem;margin-bottom:12px;opacity:.3"></div>
            <p><?= $lang==='en'?'No address saved.':($lang==='ar'?'لا توجد عناوين.':($lang==='he'?'אין כתובות שמורות.':'Aucune adresse enregistrée.')) ?></p>
            <a href="adresses.php?new=1" class="btn-add">+ <?= $lang==='en'?'Add address':($lang==='ar'?'إضافة عنوان':($lang==='he'?'הוסף כתובת':'Ajouter une adresse')) ?></a>
          </div>
          <?php else: ?>
          <div class="addr-grid">
            <?php foreach ($addresses as $addr): ?>
            <div class="addr-item <?= ! empty($addr['is_default']) ? 'is-default' : '' ?>">
              <div class="addr-label">
                <?= htmlspecialchars($addr['label']) ?>
                <span style="font-size:.62rem;color:#8b92a8;margin-left:6px"><?= htmlspecialchars(address_usage_label($addr['usage_type'] ?? 'both', $lang)) ?></span>
                <?php if (! empty($addr['is_default'])): ?><span class="addr-default-badge"><?= $lang==='en'?'Billing':($lang==='ar'?'فوترة':($lang==='he'?'חיוב':'Facturation')) ?></span><?php endif; ?>
                <?php if (! empty($addr['is_default_shipping'])): ?><span class="addr-default-badge"><?= $lang==='en'?'Delivery':($lang==='ar'?'توصيل':($lang==='he'?'משלוח':'Livraison')) ?></span><?php endif; ?>
              </div>
              <div class="addr-name"><?= htmlspecialchars($addr['prenom'].' '.$addr['nom']) ?></div>
              <div class="addr-line">
                <?= htmlspecialchars($addr['adresse1']) ?><br>
                <?php if ($addr['adresse2']): ?><?= htmlspecialchars($addr['adresse2']) ?><br><?php endif; ?>
                <?= htmlspecialchars($addr['code_postal'].' '.$addr['ville']) ?><br>
                <?php if ($addr['region']): ?><?= htmlspecialchars($addr['region']) ?>, <?php endif; ?>
                <?= htmlspecialchars($addr['pays']) ?>
                <?php if ($addr['telephone']): ?><br><?= htmlspecialchars($addr['telephone']) ?><?php endif; ?>
              </div>
              <div class="addr-actions">
                <a href="adresses.php?edit=<?= (int)$addr['id'] ?>" class="btn-sm-edit"><?= t('edit') ?></a>
                <?php if (empty($addr['is_default']) && in_array($addr['usage_type'] ?? 'both', ['billing', 'both'], true)): ?>
                <form method="POST" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="set_default">
                  <input type="hidden" name="address_id" value="<?= (int)$addr['id'] ?>">
                  <button type="submit" class="btn-sm-def"><?= $lang==='en'?'Billing default':($lang==='ar'?'فوترة افتراضية':($lang==='he'?'חיוב ברירת מחדל':'Défaut facturation')) ?></button>
                </form>
                <?php endif; ?>
                <?php if (empty($addr['is_default_shipping']) && in_array($addr['usage_type'] ?? 'both', ['shipping', 'both'], true)): ?>
                <form method="POST" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="set_default_shipping">
                  <input type="hidden" name="address_id" value="<?= (int)$addr['id'] ?>">
                  <button type="submit" class="btn-sm-def"><?= $lang==='en'?'Delivery default':($lang==='ar'?'توصيل افتراضي':($lang==='he'?'משלוח ברירת מחדל':'Défaut livraison')) ?></button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('<?= $lang==='en'?'Delete?':($lang==='ar'?'حذف؟':($lang==='he'?'למחוק?':'Supprimer ?')) ?>')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="address_id" value="<?= (int)$addr['id'] ?>">
                  <button type="submit" class="btn-sm-del"><?= t('delete') ?></button>
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
    <a href="a-propos.php"><?= t('about') ?></a>
    <span><?= t('copyright') ?></span>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <?php form_validation_include($lang); ?>
</body>
</html>