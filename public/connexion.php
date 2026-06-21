<?php
require_once '../config/config.php';
require_once '../includes/function.php';
require_once '../includes/csrf.php';
require_once '../includes/lang.php';
require_once '../includes/form_validation.php';

startSession();

if (!empty($_SESSION['utilisateur_id'])) {
    redirectTo('../index.php');
}

$erreurs            = [];
$email_non_confirme = false;
$email_pour_renvoi  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email        = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (!$email)              $erreurs[] = $lang==='en'?'Invalid email address.':($lang==='ar'?'عنوان البريد الإلكتروني غير صالح.':($lang==='he'?'כתובת אימייל לא תקינה.':'Adresse email invalide.'));
    if (empty($mot_de_passe)) $erreurs[] = $lang==='en'?'Password required.':($lang==='ar'?'كلمة المرور مطلوبة.':($lang==='he'?'סיסמה נדרשת.':'Mot de passe requis.'));

    if (empty($erreurs)) {
        try {
            $response = api_client()->login($email, $mot_de_passe);

            if (! empty($response['data']['requires_otp'])) {
                $erreurs[] = $lang==='en'
                    ? 'Administrator accounts must sign in via the admin area.'
                    : ($lang==='ar'
                        ? 'يجب على حسابات المسؤولين تسجيل الدخول عبر لوحة الإدارة.'
                        : ($lang==='he'
                            ? 'חשבונות מנהלים חייבים להתחבר דרך אזור הניהול.'
                            : 'Les comptes administrateur doivent se connecter via l\'espace admin.'));
            } else {
                $user = $response['data']['user'] ?? [];

                if (empty($user['est_confirme'])) {
                    $email_non_confirme = true;
                    $email_pour_renvoi = $user['email'] ?? $email;
                } else {
                    session_regenerate_id(true);
                    redirectTo('../index.php');
                    exit();
                }
            }
        } catch (Throwable $e) {
            $erreurs[] = $lang==='en'?'Incorrect email or password.':($lang==='ar'?'البريد الإلكتروني أو كلمة المرور غير صحيحة.':($lang==='he'?'אימייל או סיסמה שגויים.':'Email ou mot de passe incorrect.'));
        }
    }
}

$lbl_title    = $lang==='en'?'Welcome back!':($lang==='ar'?'مرحباً بعودتك!':($lang==='he'?'ברוך שובך!':'Bon retour !'));
$lbl_sub      = $lang==='en'?'Sign in to your CYNA account':($lang==='ar'?'تسجيل الدخول إلى حسابك في CYNA':($lang==='he'?'התחבר לחשבון CYNA שלך':'Connectez-vous à votre espace CYNA'));
$lbl_email    = $lang==='en'?'Email address':($lang==='ar'?'عنوان البريد الإلكتروني':($lang==='he'?'כתובת אימייל':'Adresse email'));
$lbl_password = $lang==='en'?'Password':($lang==='ar'?'كلمة المرور':($lang==='he'?'סיסמה':'Mot de passe'));
$lbl_remember = $lang==='en'?'Remember me':($lang==='ar'?'تذكرني':($lang==='he'?'זכור אותי':'Se souvenir de moi'));
$lbl_forgot   = $lang==='en'?'Forgot password?':($lang==='ar'?'نسيت كلمة المرور؟':($lang==='he'?'שכחת סיסמה?':'Mot de passe oublié ?'));
$lbl_submit   = $lang==='en'?'Sign in →':($lang==='ar'?'تسجيل الدخول →':($lang==='he'?'התחבר →':'Se connecter →'));
$lbl_or       = $lang==='en'?'or':($lang==='ar'?'أو':($lang==='he'?'או':'ou'));
$lbl_register = $lang==='en'?'No account yet? <a href="inscription.php">Sign up for free</a>':($lang==='ar'?'ليس لديك حساب؟ <a href="inscription.php">سجّل مجاناً</a>':($lang==='he'?'אין לך חשבון? <a href="inscription.php">הרשם בחינם</a>':'Pas encore de compte ? <a href="inscription.php">S\'inscrire gratuitement</a>'));
$lbl_unconf   = $lang==='en'?'Your account is not confirmed yet. Check your inbox (link valid 24 hours).':($lang==='ar'?'لم يتم تأكيد حسابك بعد. تحقق من بريدك (الرابط صالح 24 ساعة).':($lang==='he'?'החשבון שלך לא אושר עדיין. בדוק את תיבת הדואר (קישור תקף 24 שעות).':'Votre compte n\'est pas encore confirmé. Vérifiez votre boîte mail (lien valable 24 h).'));
$lbl_resend   = $lang==='en'?'↺ Resend email':($lang==='ar'?'↺ إعادة الإرسال':($lang==='he'?'↺ שלח שוב':'↺ Renvoyer l\'email'));
$lbl_back     = $lang==='en'?'← Back to home':($lang==='ar'?'← العودة للرئيسية':($lang==='he'?'← חזרה לדף הבית':'← Retour à l\'accueil'));
?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — <?= t('nav_login') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/css/public-mobile.css" rel="stylesheet">
  <link href="../assets/css/pages/auth.css" rel="stylesheet">
  <link href="../assets/css/pages/connexion.css" rel="stylesheet">
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
        <?php if (!empty($erreurs)): ?>
        <div class="alert-err">
          <?php foreach ($erreurs as $e): ?>
          <?= htmlspecialchars($e) ?><br>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($email_non_confirme): ?>
        <div class="alert-warn">
          <?= $lbl_unconf ?>
          <br>
          <form method="POST" action="renvoyer_confirmation.php" style="display:inline">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email_pour_renvoi) ?>">
            <button type="submit" class="btn-resend"><?= $lbl_resend ?></button>
          </form>
        </div>
        <?php endif; ?>

        <form method="POST" action="connexion.php" data-cyna-validate="login">
          <?= csrf_field() ?>
          <div class="field">
            <label class="field-label"><?= $lbl_email ?></label>
            <input class="field-input" type="email" name="email" required
                   placeholder="you@example.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="field">
            <label class="field-label"><?= $lbl_password ?></label>
            <input class="field-input" type="password" name="mot_de_passe" required placeholder="••••••••">
          </div>
          <div class="row-opt">
            <label class="check-label">
              <input type="checkbox" name="remember"> <?= $lbl_remember ?>
            </label>
            <a href="mot_de_passe_oublie.php" class="forgot-link"><?= $lbl_forgot ?></a>
          </div>
          <button type="submit" class="btn-submit"><?= $lbl_submit ?></button>
        </form>

        <div class="divider"><?= $lbl_or ?></div>
        <div class="register-row"><?= $lbl_register ?></div>
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
</body>
</html>