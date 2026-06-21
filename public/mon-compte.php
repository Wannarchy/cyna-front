<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/password_helpers.php';
require_once __DIR__ . '/../includes/form_validation.php';
require_once __DIR__ . '/../includes/public_layout.php';

if (!isset($_SESSION['utilisateur_id'])) { header('Location: connexion.php'); exit; }

try {
    $user = api_client()->getProfile();
} catch (Throwable) {
    session_destroy();
    header('Location: connexion.php');
    exit;
}

if (! $user) {
    session_destroy();
    header('Location: connexion.php');
    exit;
}

$nb_panier = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
$tab       = $_GET['tab'] ?? 'profil';
$success   = '';
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $tab    = 'profil';
    $prenom = trim($_POST['prenom'] ?? '');
    $nom    = trim($_POST['nom']    ?? '');
    $email  = trim($_POST['email']  ?? '');
    if (empty($prenom)) $errors[] = $lang==='en'?'First name required.':($lang==='ar'?'الاسم الأول مطلوب.':($lang==='he'?'שם פרטי נדרש.':'Le prénom est requis.'));
    if (empty($nom))    $errors[] = $lang==='en'?'Last name required.':($lang==='ar'?'اسم العائلة مطلوب.':($lang==='he'?'שם משפחה נדרש.':'Le nom est requis.'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = $lang==='en'?'Invalid email.':($lang==='ar'?'بريد إلكتروني غير صالح.':($lang==='he'?'אימייל לא תקין.':'Email invalide.'));
    if (empty($errors)) {
        try {
            $user = api_client()->updateProfile([
                'prenom' => $prenom,
                'nom' => $nom,
                'email' => $email,
            ]);
            $_SESSION['utilisateur_prenom'] = $user['prenom'] ?? $prenom;
            $_SESSION['utilisateur_nom'] = $user['nom'] ?? $nom;
            $_SESSION['utilisateur_email'] = $user['email'] ?? $email;
            $success = $lang==='en'?'Information updated!':($lang==='ar'?'تم تحديث المعلومات!':($lang==='he'?'המידע עודכן!':'Informations mises à jour !'));
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_account') {
    $tab = 'profil';
    $deletePassword = $_POST['delete_password'] ?? '';
    $deleteConfirmation = trim($_POST['delete_confirmation'] ?? '');
    $deleteAck = ! empty($_POST['delete_ack']);

    if (! $deleteAck) {
        $errors[] = $lang === 'en' ? 'Please confirm that you understand this action is irreversible.' : ($lang === 'ar' ? 'يرجى تأكيد أنك تدرك أن هذا الإجراء لا رجعة فيه.' : ($lang === 'he' ? 'אנא אשר שהבנת שהפעולה בלתי הפיכה.' : 'Veuillez confirmer que vous comprenez que cette action est irréversible.'));
    }
    if ($deleteConfirmation !== 'SUPPRIMER') {
        $errors[] = $lang === 'en' ? 'Type SUPPRIMER to confirm.' : ($lang === 'ar' ? 'اكتب SUPPRIMER للتأكيد.' : ($lang === 'he' ? 'הקלד SUPPRIMER לאישור.' : 'Saisissez SUPPRIMER pour confirmer.'));
    }
    if ($deletePassword === '') {
        $errors[] = $lang === 'en' ? 'Current password required.' : ($lang === 'ar' ? 'كلمة المرور الحالية مطلوبة.' : ($lang === 'he' ? 'סיסמה נוכחית נדרשת.' : 'Le mot de passe actuel est requis.'));
    }

    if (empty($errors)) {
        try {
            api_client()->deleteAccount($deletePassword, 'SUPPRIMER');
            api_client()->logout();
            cyna_session_destroy();
            header('Location: ../index.php?account_deleted=1');
            exit;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $tab       = 'securite';
    $ancien    = $_POST['ancien_mdp']    ?? '';
    $nouveau   = $_POST['nouveau_mdp']   ?? '';
    $confirmer = $_POST['confirmer_mdp'] ?? '';
    if ($nouveau !== $confirmer)           $errors[] = $lang==='en'?'Passwords do not match.':($lang==='ar'?'كلمتا المرور غير متطابقتان.':($lang==='he'?'הסיסמאות אינן תואמות.':'Les mots de passe ne correspondent pas.'));
    $errors = array_merge($errors, password_policy_errors($nouveau, $lang));
    if (empty($errors)) {
        try {
            api_client()->updateProfile([
                'current_password' => $ancien,
                'password' => $nouveau,
                'password_confirmation' => $confirmer,
            ]);
            $success = $lang==='en'?'Password changed successfully!':($lang==='ar'?'تم تغيير كلمة المرور بنجاح!':($lang==='he'?'הסיסמה שונתה בהצלחה!':'Mot de passe modifié avec succès !'));
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

try {
    $orders = api_client()->getOrders();
} catch (Throwable) {
    $orders = [];
}
$nb_orders   = count($orders);
$total_spent = array_sum(array_map(static fn (array $order): float => (float) ($order['total'] ?? 0), $orders));

// Labels
$lbl_profil    = $lang==='en'?'My profile':($lang==='ar'?'ملفي الشخصي':($lang==='he'?'הפרופיל שלי':'Mon profil'));
$lbl_security  = $lang==='en'?'Security':($lang==='ar'?'الأمان':($lang==='he'?'אבטחה':'Sécurité'));
$lbl_verified  = $lang==='en'?'Verified account':($lang==='ar'?' حساب موثق':($lang==='he'?' חשבון מאומת':'Compte vérifié'));
$lbl_orders_ct = $lang==='en'?'Orders':($lang==='ar'?'الطلبات':($lang==='he'?'הזמנות':'Commandes'));
$lbl_spent     = $lang==='en'?'Total spent':($lang==='ar'?'إجمالي الإنفاق':($lang==='he'?'סה"כ הוצאות':'Total dépensé'));
$lbl_email_ver = $lang==='en'?'Email verified':($lang==='ar'?'البريد الإلكتروني موثق':($lang==='he'?'אימייל מאומת':'Email vérifié'));
$lbl_perso     = $lang==='en'?'Personal information':($lang==='ar'?'المعلومات الشخصية':($lang==='he'?'מידע אישי':'Informations personnelles'));
$lbl_fname     = $lang==='en'?'First name':($lang==='ar'?'الاسم الأول':($lang==='he'?'שם פרטי':'Prénom'));
$lbl_lname     = $lang==='en'?'Last name':($lang==='ar'?'اسم العائلة':($lang==='he'?'שם משפחה':'Nom'));
$lbl_email_lbl = $lang==='en'?'Email address':($lang==='ar'?'عنوان البريد الإلكتروني':($lang==='he'?'כתובת אימייל':'Adresse email'));
$lbl_email_note= $lang==='en'?'Changing the email requires re-confirmation.':($lang==='ar'?'تغيير البريد الإلكتروني يتطلب إعادة التأكيد.':($lang==='he'?'שינוי האימייל מחייב אישור מחדש.':'Modifier l\'email nécessite une re-confirmation.'));
$lbl_save      = t('save');
$lbl_change_pwd= $lang==='en'?'Change password':($lang==='ar'?'تغيير كلمة المرور':($lang==='he'?'שינוי סיסמה':'Changer le mot de passe'));
$lbl_curr_pwd  = $lang==='en'?'Current password':($lang==='ar'?'كلمة المرور الحالية':($lang==='he'?'סיסמה נוכחית':'Mot de passe actuel'));
$lbl_new_pwd   = $lang==='en'?'New password':($lang==='ar'?'كلمة المرور الجديدة':($lang==='he'?'סיסמה חדשה':'Nouveau mot de passe'));
$lbl_confirm_pwd = $lang==='en'?'Confirm':($lang==='ar'?'تأكيد':($lang==='he'?'אישור':'Confirmer'));
$lbl_submit_pwd= $lang==='en'?'Change password':($lang==='ar'?'تغيير كلمة المرور':($lang==='he'?'שנה סיסמה':'Modifier le mot de passe'));
$lbl_danger    = $lang==='en'?'Danger zone':($lang==='ar'?'منطقة الخطر':($lang==='he'?'אזור מסוכן':'Zone sensible'));
$lbl_danger_sub= $lang==='en'?'Permanently delete your account and personal data. Billing records required by law may be retained in anonymized form.':($lang==='ar'?'حذف حسابك وبياناتك الشخصية نهائياً. قد يتم الاحتفاظ بسجلات الفوترة المطلوبة قانوناً بشكل مجهول.':($lang==='he'?'מחיקה סופית של החשבון והנתונים האישיים. רשומות חיוב נשמרות לפי חוק בצורה אנונימית.':'Suppression définitive de votre compte et de vos données personnelles. Les données de facturation imposées par la loi peuvent être conservées de façon anonymisée.'));
$lbl_delete_account = $lang==='en'?'Delete my account':($lang==='ar'?'حذف حسابي':($lang==='he'?'מחק את החשבון שלי':'Supprimer mon compte'));
$lbl_delete_modal_title = $lang==='en'?'Confirm account deletion':($lang==='ar'?'تأكيد حذف الحساب':($lang==='he'?'אישור מחיקת חשבון':'Confirmer la suppression du compte'));
$lbl_delete_modal_body = $lang==='en'?'This action is irreversible. Your profile, addresses, payment methods and sessions will be deleted. Orders may be kept for legal obligations without direct identification.':($lang==='ar'?'هذا الإجراء لا رجعة فيه. سيتم حذف ملفك وعناوينك وطرق الدفع والجلسات. قد تُحفظ الطلبات للالتزامات القانونية دون تعريف مباشر.':($lang==='he'?'פעולה זו בלתי הפיכה. הפרופיל, כתובות, אמצעי תשלום וההפעלות יימחקו. הזמנות עשויות להישמר לפי חובה חוקית ללא זיהוי ישיר.':'Cette action est irréversible. Votre profil, adresses, moyens de paiement et sessions seront supprimés. Les commandes peuvent être conservées pour obligations légales sans identification directe.'));
$lbl_delete_pwd = $lang==='en'?'Current password':($lang==='ar'?'كلمة المرور الحالية':($lang==='he'?'סיסמה נוכחית':'Mot de passe actuel'));
$lbl_delete_type = $lang==='en'?'Type SUPPRIMER to confirm':($lang==='ar'?'اكتب SUPPRIMER للتأكيد':($lang==='he'?'הקלד SUPPRIMER לאישור':'Saisissez SUPPRIMER pour confirmer'));
$lbl_delete_ack = $lang==='en'?'I understand this action is permanent and cannot be undone.':($lang==='ar'?'أفهم أن هذا الإجراء نهائي ولا يمكن التراجع عنه.':($lang==='he'?'אני מבין שהפעולה סופית ולא ניתנת לביטול.':'Je comprends que cette action est définitive et irréversible.'));
$lbl_delete_submit = $lang==='en'?'Delete permanently':($lang==='ar'?'حذف نهائي':($lang==='he'?'מחק לצמיתות':'Supprimer définitivement'));
$lbl_delete_cancel = $lang==='en'?'Cancel':($lang==='ar'?'إلغاء':($lang==='he'?'ביטול':'Annuler'));
$lbl_contact_support = $lang==='en'?'Contact support →':($lang==='ar'?'تواصل مع الدعم →':($lang==='he'?'פנה לתמיכה →':'Contacter le support →'));
$lbl_pwd_strengths = $lang==='en'?['Very weak','Weak','Fair','Good','Excellent']:($lang==='ar'?['ضعيف جداً','ضعيف','مقبول','جيد','ممتاز']:($lang==='he'?['חלש מאוד','חלש','סביר','טוב','מצוין']:['Très faible','Faible','Correct','Bon','Excellent']));
$lbl_pwd_hints_missing = password_policy_hints($lang);
$lbl_missing   = $lang==='en'?'missing:':($lang==='ar'?'ناقص:':($lang==='he'?'חסר:':'manque :'));
$lbl_orders_history = $lang==='en'?'Order history':($lang==='ar'?'سجل الطلبات':($lang==='he'?'היסטוריית הזמנות':'Historique des commandes'));
$lbl_no_orders = $lang==='en'?'No orders yet.':($lang==='ar'?'لا توجد طلبات حتى الآن.':($lang==='he'?'אין הזמנות עדיין.':'Aucune commande pour l\'instant.'));
$lbl_discover  = $lang==='en'?'Discover our services →':($lang==='ar'?'اكتشف خدماتنا →':($lang==='he'?'גלה את השירותים שלנו →':'Découvrir nos services →'));
$lbl_order_num = $lang==='en'?'N°':($lang==='ar'?'رقم':($lang==='he'?'מס\'':'N°'));
$lbl_services  = $lang==='en'?'Services':($lang==='ar'?'الخدمات':($lang==='he'?'שירותים':'Services'));
$lbl_amount    = $lang==='en'?'Amount':($lang==='ar'?'المبلغ':($lang==='he'?'סכום':'Montant'));
$lbl_date      = $lang==='en'?'Date':($lang==='ar'?'التاريخ':($lang==='he'?'תאריך':'Date'));
$lbl_detail    = $lang==='en'?'Detail →':($lang==='ar'?'التفاصيل →':($lang==='he'?'פרטים →':'Détail →'));
?>
<?php
cyna_public_head(t('my_account'), 'mon-compte', ['compte-espace']);
cyna_public_nav(false);
?>
<div class="cyna-account-wrap">
  <aside class="cyna-account-sidebar">
    <div class="u-card">
      <div class="u-av"><?= strtoupper(mb_substr($user['prenom'],0,1)) ?></div>
      <div class="u-name"><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></div>
      <div class="u-email"><?= htmlspecialchars($user['email']) ?></div>
      <div class="u-badge"><?= $lbl_verified ?></div>
    </div>
    <nav class="sb-nav">
      <a href="?tab=profil"   class="<?= $tab==='profil'   ?'active':'' ?>"><?= $lbl_profil ?></a>
      <a href="?tab=securite" class="<?= $tab==='securite' ?'active':'' ?>"><?= $lbl_security ?></a>
      <a href="adresses.php"><?= t('my_addresses') ?></a>
      <a href="paiements.php"><?= t('my_payments') ?></a>
      <a href="mes-abonnements.php"><?= t('my_subscriptions') ?></a>
      <a href="mes-commandes.php">
        <?= t('my_orders') ?>
        <?php if ($nb_orders>0): ?>
        <span style="margin-left:auto;font-size:.65rem;font-weight:600;background:rgba(38,208,206,.15);color:var(--cyan);padding:1px 7px;border-radius:20px"><?= $nb_orders ?></span>
        <?php endif; ?>
      </a>
      <a href="deconnexion.php" style="color:rgba(239,68,68,.6)"><?= t('nav_logout') ?></a>
    </nav>
  </aside>
  <main class="cyna-account-content main">
    <?php if ($success): ?>
    <div class="a-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div class="a-error"><?php foreach($errors as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?></div>
    <?php endif; ?>
    <div class="stats">
      <div class="stt">
        <div class="stt-v"><?= $nb_orders ?></div>
        <div class="stt-l"><?= $lbl_orders_ct ?></div>
      </div>
      <div class="stt">
        <div class="stt-v"><?= number_format($total_spent,0,',',' ') ?> €</div>
        <div class="stt-l"><?= $lbl_spent ?></div>
      </div>
      <div class="stt">
        <div class="stt-v"><?= ! empty($user['est_confirme']) ? '' : '' ?></div>
        <div class="stt-l"><?= $lbl_email_ver ?></div>
      </div>
    </div>
    <?php if ($tab==='profil'): ?>
    <div class="ccard">
      <div class="ccard-head"><?= $lbl_perso ?></div>
      <div class="ccard-body">
        <form method="POST" data-cyna-validate="profile">
          <input type="hidden" name="action" value="update_profile">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label"><?= $lbl_fname ?></label>
              <input class="form-control" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><?= $lbl_lname ?></label>
              <input class="form-control" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label"><?= $lbl_email_lbl ?></label>
            <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            <div style="font-size:.7rem;color:#5c6378;margin-top:4px"><?= $lbl_email_note ?></div>
          </div>
          <button class="btn-save" type="submit"><?= $lbl_save ?></button>
        </form>
      </div>
    </div>
    <div class="ccard" style="margin-top:20px">
      <div class="ccard-head" style="color:#f87171"><?= $lbl_danger ?></div>
      <div class="ccard-body">
        <p style="font-size:.82rem;color:#8b92a8;margin:0 0 14px;line-height:1.6"><?= $lbl_danger_sub ?></p>
        <button type="button" class="btn-danger-outline" data-bs-toggle="modal" data-bs-target="#deleteAccountModal"><?= $lbl_delete_account ?></button>
      </div>
    </div>
    <?php elseif ($tab==='securite'): ?>
    <div class="ccard">
      <div class="ccard-head"><?= $lbl_change_pwd ?></div>
      <div class="ccard-body">
        <form method="POST" data-cyna-validate="password-change">
          <input type="hidden" name="action" value="change_password">
          <div class="mb-3">
            <label class="form-label"><?= $lbl_curr_pwd ?></label>
            <input class="form-control" type="password" name="ancien_mdp" required placeholder="••••••••">
          </div>
          <div class="mb-3">
            <label class="form-label"><?= $lbl_new_pwd ?></label>
            <input class="form-control" type="password" name="nouveau_mdp" id="new-pwd" required placeholder="<?= htmlspecialchars(password_policy_placeholder($lang)) ?>"oninput="checkPwd(this.value)">
            <div class="pwd-bar-wrap"><div class="pwd-bar" id="pwd-bar"></div></div>
            <div id="pwd-hint" style="font-size:.7rem;color:#5c6378;margin-top:4px"></div>
          </div>
          <div class="mb-4">
            <label class="form-label"><?= $lbl_confirm_pwd ?></label>
            <input class="form-control" type="password" name="confirmer_mdp" required placeholder="••••••••">
          </div>
          <button class="btn-save" type="submit"><?= $lbl_submit_pwd ?></button>
        </form>
      </div>
    </div>
    <?php elseif ($tab==='commandes'): ?>
    <div class="ccard">
      <div class="ccard-head"><?= $lbl_orders_history ?> <span style="font-size:.75rem;font-weight:500;color:#8b92a8"><?= $nb_orders ?></span></div>
      <?php if (!$orders): ?>
      <div style="text-align:center;padding:48px 24px;color:#5c6378">
        <div style="font-size:2.5rem;margin-bottom:12px;opacity:.3"></div>
        <p style="font-size:.88rem"><?= $lbl_no_orders ?></p>
        <a href="catalogue.php" style="color:var(--cyan);font-size:.85rem;text-decoration:none"><?= $lbl_discover ?></a>
      </div>
      <?php else: ?>
      <div class="cyna-table-scroll">
        <table class="otable">
          <thead>
            <tr>
              <th><?= $lbl_order_num ?></th>
              <th><?= $lbl_services ?></th>
              <th><?= $lbl_amount ?></th>
              <th><?= $lbl_date ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o):
                $items = $o['items'] ?? [];
            ?>
            <tr>
              <td><span style="font-size:.7rem;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(79,140,255,.12);color:#93c5fd;border:1px solid rgba(79,140,255,.2)">#<?= (int)$o['id'] ?></span></td>
              <td>
                <div style="font-size:.84rem;color:#e8eaf2;font-weight:500"><?= htmlspecialchars($o['billing_name']??'—') ?></div>
                <?php if ($items): ?>
                <div style="font-size:.72rem;color:#5c6378;margin-top:2px"><?= implode(', ', array_map(static function ($i) { return htmlspecialchars($i['product']['name'] ?? ''); }, array_slice($items, 0, 2))) ?><?= count($items)>2?' +'.(count($items)-2):'' ?></div>
                <?php endif; ?>
              </td>
              <td style="font-weight:600;color:#fff"><?= number_format((float)$o['total'],2,',',' ') ?> €</td>
              <td style="font-size:.78rem;color:#5c6378;font-family:'DM Mono',monospace"><?= date('d/m/Y',strtotime($o['created_at'])) ?></td>
              <td style="text-align:right"><a href="confirmation.php?order_id=<?= (int)$o['id'] ?>" style="font-size:.73rem;font-weight:600;padding:4px 10px;border-radius:7px;background:rgba(79,140,255,.1);color:#93c5fd;border:1px solid rgba(79,140,255,.2);text-decoration:none"><?= $lbl_detail ?></a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" data-cyna-validate="delete-account">
        <input type="hidden" name="action" value="delete_account">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteAccountModalLabel"><?= htmlspecialchars($lbl_delete_modal_title) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars($lbl_delete_cancel) ?>"></button>
        </div>
        <div class="modal-body">
          <p style="font-size:.84rem;color:#8b92a8;line-height:1.6;margin-bottom:16px"><?= htmlspecialchars($lbl_delete_modal_body) ?></p>
          <div class="mb-3">
            <label class="form-label"><?= htmlspecialchars($lbl_delete_pwd) ?></label>
            <input class="form-control" type="password" name="delete_password" required autocomplete="current-password" placeholder="••••••••">
          </div>
          <div class="mb-3">
            <label class="form-label"><?= htmlspecialchars($lbl_delete_type) ?></label>
            <input class="form-control" type="text" name="delete_confirmation" required autocomplete="off" placeholder="SUPPRIMER">
          </div>
          <label style="display:flex;align-items:flex-start;gap:10px;font-size:.8rem;color:#8b92a8;cursor:pointer">
            <input type="checkbox" name="delete_ack" value="1" required style="margin-top:3px">
            <span><?= htmlspecialchars($lbl_delete_ack) ?></span>
          </label>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-danger-outline" data-bs-dismiss="modal"><?= htmlspecialchars($lbl_delete_cancel) ?></button>
          <button type="submit" class="btn-danger-solid"><?= htmlspecialchars($lbl_delete_submit) ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var pwdStrengths = <?= json_encode($lbl_pwd_strengths) ?>;
var pwdHints     = <?= json_encode($lbl_pwd_hints_missing) ?>;
var pwdMissing   = '<?= $lbl_missing ?>';

function checkPwd(v) {
  var bar=document.getElementById('pwd-bar'), hint=document.getElementById('pwd-hint'), s=0, tips=[];
  if(v.length>=8) s++; else tips.push(pwdHints[0]);
  if(/[A-Z]/.test(v)) s++; else tips.push(pwdHints[1]);
  if(/[a-z]/.test(v)) s++; else tips.push(pwdHints[2]);
  if(/[0-9]/.test(v)) s++; else tips.push(pwdHints[3]);
  if(/[^A-Za-z0-9]/.test(v)) s++; else tips.push(pwdHints[4]);
  var c=['#ef4444','#f59e0b','#eab308','#22c55e','#26d0ce'];
  bar.style.width=(s*20)+'%';
  bar.style.background=c[s-1]||'#ef4444';
  hint.textContent=s>0?pwdStrengths[s-1]+(tips.length?' — '+pwdMissing+' '+tips.join(', '):''):'';
}
</script>
<?php
form_validation_include($lang);
cyna_public_footer();