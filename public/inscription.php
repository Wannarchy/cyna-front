<?php

require_once '../config/config.php';
cyna_session_start();
require_once '../includes/function.php';
require_once '../includes/csrf.php';
require_once '../includes/lang.php';
require_once '../includes/password_helpers.php';
require_once '../includes/form_validation.php';

startSession();
$erreurs = [];
$succes  = '';
$old = ['prenom' => '', 'nom' => '', 'email' => ''];

if (! empty($_SESSION['flash_erreurs']) && is_array($_SESSION['flash_erreurs'])) {
    $erreurs = $_SESSION['flash_erreurs'];
    unset($_SESSION['flash_erreurs']);
}
if (! empty($_SESSION['flash_succes'])) {
    $succes = (string) $_SESSION['flash_succes'];
    unset($_SESSION['flash_succes']);
}
if (! empty($_SESSION['flash_old']) && is_array($_SESSION['flash_old'])) {
    $old = array_merge($old, $_SESSION['flash_old']);
    unset($_SESSION['flash_old']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $prenom = trim($_POST['prenom'] ?? '');
    $nom    = trim($_POST['nom'] ?? '');
    $email  = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $mdp    = $_POST['mot_de_passe'] ?? '';
    $mdp2   = $_POST['confirmation_mot_de_passe'] ?? '';

    if (empty($prenom))              $erreurs[] = $lang==='en'?'First name required.':($lang==='ar'?'الاسم الأول مطلوب.':($lang==='he'?'שם פרטי נדרש.':'Le prénom est requis.'));
    if (empty($nom))                 $erreurs[] = $lang==='en'?'Last name required.':($lang==='ar'?'اسم العائلة مطلوب.':($lang==='he'?'שם משפחה נדרש.':'Le nom est requis.'));
    if (!$email)                     $erreurs[] = $lang==='en'?'Invalid email.':($lang==='ar'?'بريد إلكتروني غير صالح.':($lang==='he'?'אימייל לא תקין.':'Email invalide.'));
    $erreurs = array_merge($erreurs, password_policy_errors($mdp, $lang));
    if ($mdp !== $mdp2)              $erreurs[] = $lang==='en'?'Passwords do not match.':($lang==='ar'?'كلمتا المرور غير متطابقتان.':($lang==='he'?'הסיסמאות אינן תואמות.':'Les mots de passe ne correspondent pas.'));

    if (empty($erreurs)) {
        try {
            api_client()->register($prenom, $nom, $email, $mdp);
            $succes = $lang==='en'?'Registration successful! Check your inbox to confirm your account (link valid 24 hours).':($lang==='ar'?'تم التسجيل بنجاح! تحقق من بريدك الإلكتروني لتأكيد حسابك (الرابط صالح 24 ساعة).':($lang==='he'?'ההרשמה הצליחה! בדוק את תיבת הדואר שלך לאישור החשבון (קישור תקף 24 שעות).':'Inscription réussie ! Vérifiez votre boîte mail pour confirmer votre compte (lien valable 24 h).'));
        } catch (Throwable $e) {
            $message = $e->getMessage();
            if (str_contains(strtolower($message), 'email')) {
                $erreurs[] = $lang==='en'?'This email address is already in use.':($lang==='ar'?'عنوان البريد الإلكتروني هذا مستخدم بالفعل.':($lang==='he'?'כתובת אימייל זו כבר בשימוש.':'Cette adresse email est déjà utilisée.'));
            } else {
                $erreurs[] = $lang==='en'?'An error occurred.':($lang==='ar'?'حدث خطأ.':($lang==='he'?'אירעה שגיאה.':'Erreur.'));
            }
        }
    }

    if (! empty($erreurs)) {
        $_SESSION['flash_erreurs'] = $erreurs;
        $_SESSION['flash_old'] = [
            'prenom' => $prenom,
            'nom' => $nom,
            'email' => is_string($email) ? $email : (string) ($_POST['email'] ?? ''),
        ];
    } elseif ($succes !== '') {
        $_SESSION['flash_succes'] = $succes;
    }

    header('Location: inscription.php', true, 303);
    exit;
}

$lbl_title   = $lang==='en'?'Create an account':($lang==='ar'?'إنشاء حساب':($lang==='he'?'יצירת חשבון':'Créer un compte'));
$lbl_sub     = $lang==='en'?'Access our SaaS cybersecurity solutions':($lang==='ar'?'الوصول إلى حلول الأمن السيبراني SaaS':($lang==='he'?'גש לפתרונות אבטחת הסייבר SaaS שלנו':'Accédez à nos solutions SaaS de cybersécurité'));
$lbl_fname   = $lang==='en'?'First name *':($lang==='ar'?'الاسم الأول *':($lang==='he'?'שם פרטי *':'Prénom *'));
$lbl_lname   = $lang==='en'?'Last name *':($lang==='ar'?'اسم العائلة *':($lang==='he'?'שם משפחה *':'Nom *'));
$lbl_email   = $lang==='en'?'Email address *':($lang==='ar'?'عنوان البريد الإلكتروني *':($lang==='he'?'כתובת אימייל *':'Adresse email *'));
$lbl_pwd     = $lang==='en'?'Password *':($lang==='ar'?'كلمة المرور *':($lang==='he'?'סיסמה *':'Mot de passe *'));
$lbl_pwd2    = $lang==='en'?'Confirm password *':($lang==='ar'?'تأكيد كلمة المرور *':($lang==='he'?'אישור סיסמה *':'Confirmer le mot de passe *'));
$lbl_pwd_ph  = password_policy_placeholder($lang);
$lbl_pwd2_ph = $lang==='en'?'Repeat password':($lang==='ar'?'أعد كتابة كلمة المرور':($lang==='he'?'חזור על הסיסמה':'Répétez le mot de passe'));
$lbl_cgu     = $lang==='en'?'I accept the <a href="Cgu.php">Terms of Service</a> and <a href="mention_legales.php">privacy policy</a> of CYNA.':($lang==='ar'?'أوافق على <a href="Cgu.php">شروط الاستخدام</a> و<a href="mention_legales.php">سياسة الخصوصية</a> لـ CYNA.':($lang==='he'?'אני מקבל את <a href="Cgu.php">תנאי השימוש</a> ו<a href="mention_legales.php">מדיניות הפרטיות</a> של CYNA.':'J\' accepte les <a href="Cgu.php">Conditions Générales d\'Utilisation</a> et la <a href="mention_legales.php">politique de confidentialité</a> de CYNA.'));
$lbl_submit  = $lang==='en'?'Create my account →':($lang==='ar'?'إنشاء حسابي →':($lang==='he'?'צור את חשבוני →':'Créer mon compte →'));
$lbl_or      = $lang==='en'?'or':($lang==='ar'?'أو':($lang==='he'?'או':'ou'));
$lbl_login   = $lang==='en'?'Already have an account? <a href="connexion.php">Sign in</a>':($lang==='ar'?'لديك حساب بالفعل؟ <a href="connexion.php">تسجيل الدخول</a>':($lang==='he'?'כבר יש לך חשבון? <a href="connexion.php">התחבר</a>':'Déjà un compte ? <a href="connexion.php">Se connecter</a>'));
$lbl_back    = $lang==='en'?'← Back to home':($lang==='ar'?'← العودة للرئيسية':($lang==='he'?'← חזרה לדף הבית':'← Retour à l\'accueil'));
$lbl_connect = $lang==='en'?'→ Sign in':($lang==='ar'?'→ تسجيل الدخول':($lang==='he'?'→ התחבר':'→ Se connecter'));

// Jauges de force du mot de passe traduites
$strength_labels = $lang==='en'?['Too weak','Weak','Medium','Strong','Excellent']:($lang==='ar'?['ضعيف جداً','ضعيف','متوسط','قوي','ممتاز']:($lang==='he'?['חלש מאוד','חלש','בינוני','חזק','מצוין']:['Trop faible','Faible','Moyen','Fort','Excellent']));
$strength_hints  = password_policy_hints($lang);
$strength_empty  = $lang==='en'?'Enter a password':($lang==='ar'?'أدخل كلمة مرور':($lang==='he'?'הזן סיסמה':'Entrez un mot de passe'));
?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — <?= $lbl_title ?></title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/css/public-mobile.css" rel="stylesheet">
  <link href="../assets/css/pages/auth.css" rel="stylesheet">
  <link href="../assets/css/pages/inscription.css" rel="stylesheet">
</head>
<body class="auth-page">

  <div class="bg-deco"></div>

  <nav class="navbar">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <div style="display:flex;align-items:center;gap:12px">
      <?= lang_switcher() ?>
      <a class="navbar-link" href="../index.php"><?= $lbl_back ?></a>
    </div>
  </nav>

  <main>
    <div class="auth-card">
      <div class="auth-header">
        <div class="auth-logo">C</div>
        <div class="auth-title"><?= $lbl_title ?></div>
        <div class="auth-sub"><?= $lbl_sub ?></div>
      </div>

      <div class="form-box">
        <?php if ($succes): ?>
        <div class="alert-ok">
          <?= htmlspecialchars($succes) ?><br>
          <a href="connexion.php" style="color:#4ade80;font-weight:600;margin-top:8px;display:inline-block"><?= $lbl_connect ?></a>
        </div>
        <?php else: ?>
        <?php if (!empty($erreurs)): ?>
        <div class="alert-err">
          <?php foreach ($erreurs as $e): ?>
          <?= htmlspecialchars($e) ?><br>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="inscription.php" data-cyna-validate="register">
          <?= csrf_field() ?>
          <div class="row-2">
            <div class="field">
              <label class="field-label"><?= $lbl_fname ?></label>
              <input class="field-input" type="text" name="prenom" required placeholder="<?= $lang==='en'?'John':($lang==='ar'?'محمد':($lang==='he'?'יוחנן':'Jean')) ?>"
                     value="<?= htmlspecialchars($old['prenom']) ?>">
            </div>
            <div class="field">
              <label class="field-label"><?= $lbl_lname ?></label>
              <input class="field-input" type="text" name="nom" required placeholder="<?= $lang==='en'?'Doe':($lang==='ar'?'أحمد':($lang==='he'?'כהן':'Dupont')) ?>"
                     value="<?= htmlspecialchars($old['nom']) ?>">
            </div>
          </div>
          <div class="field">
            <label class="field-label"><?= $lbl_email ?></label>
            <input class="field-input" type="email" name="email" required placeholder="you@example.com"
                   value="<?= htmlspecialchars($old['email']) ?>">
          </div>
          <div class="field">
            <label class="field-label"><?= $lbl_pwd ?></label>
            <input class="field-input" type="password" name="mot_de_passe" id="mdp"
                   required placeholder="<?= $lbl_pwd_ph ?>"oninput="checkStrength(this.value)">
            <div class="strength-bar"><div class="strength-fill" id="strength-fill" style="width:0%"></div></div>
            <div class="strength-text" id="strength-text"><?= $strength_empty ?></div>
          </div>
          <div class="field">
            <label class="field-label"><?= $lbl_pwd2 ?></label>
            <input class="field-input" type="password" name="confirmation_mot_de_passe" required placeholder="<?= $lbl_pwd2_ph ?>">
          </div>
          <div class="cgu-row">
            <input type="checkbox" name="cgu" required>
            <span><?= $lbl_cgu ?></span>
          </div>
          <button type="submit" class="btn-submit"><?= $lbl_submit ?></button>
        </form>

        <div class="divider"><?= $lbl_or ?></div>
        <div class="login-row"><?= $lbl_login ?></div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <footer>
    <a href="Cgu.php"><?= t('cgu') ?></a>
    <a href="mention_legales.php"><?= t('legal') ?></a>
    <a href="Contact.php"><?= t('contact') ?></a>
    <span><?= t('copyright') ?></span>
  </footer>

  <?php form_validation_include($lang); ?>
<script>
var strengthLabels = <?= json_encode($strength_labels) ?>;
var strengthHints  = <?= json_encode($strength_hints) ?>;
var strengthEmpty  = '<?= $strength_empty ?>';

function checkStrength(val) {
  var score = 0, missing = [];
  if (val.length >= 8)           score++; else missing.push(strengthHints[0]);
  if (/[A-Z]/.test(val))         score++; else missing.push(strengthHints[1]);
  if (/[a-z]/.test(val))         score++; else missing.push(strengthHints[2]);
  if (/[0-9]/.test(val))         score++; else missing.push(strengthHints[3]);
  if (/[^A-Za-z0-9]/.test(val))  score++; else missing.push(strengthHints[4]);
  var colors = ['#ef4444','#f97316','#eab308','#22c55e','#26d0ce'];
  var pct    = ['20%','40%','60%','80%','100%'];
  var idx    = Math.max(0, score - 1);
  document.getElementById('strength-fill').style.width      = val.length ? pct[idx] : '0%';
  document.getElementById('strength-fill').style.background = val.length ? colors[idx] : 'transparent';
  var label = val.length ? strengthLabels[idx] : strengthEmpty;
  if (val.length && missing.length) label += ' — ' + missing.join(', ');
  document.getElementById('strength-text').textContent = label;
}
</script>
</body>
</html>