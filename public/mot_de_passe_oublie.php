<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/function.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/form_validation.php';
require_once __DIR__ . '/../includes/csrf.php';

$erreurs = [];
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if (! $email) {
        $erreurs[] = $lang === 'en' ? 'Invalid email address.' : ($lang === 'ar' ? 'عنوان البريد الإلكتروني غير صالح.' : ($lang === 'he' ? 'כתובת אימייל לא תקינה.' : 'Adresse email invalide.'));
    } else {
        try {
            api_client()->forgotPassword($email);
            $succes = $lang === 'en' ? 'If this address is associated with an account, a reset email has been sent.' : ($lang === 'ar' ? 'إذا كان هذا العنوان مرتبطاً بحساب، فقد تم إرسال بريد إلكتروني لإعادة التعيين.' : ($lang === 'he' ? 'אם כתובת זו משויכת לחשבון, נשלח אימייל לאיפוס.' : 'Si cette adresse est associée à un compte, un email de réinitialisation a été envoyé.'));
        } catch (RuntimeException $e) {
            $succes = $lang === 'en' ? 'If this address is associated with an account, a reset email has been sent.' : ($lang === 'ar' ? 'إذا كان هذا العنوان مرتبطاً بحساب، فقد تم إرسال بريد إلكتروني لإعادة التعيين.' : ($lang === 'he' ? 'אם כתובת זו משויכת לחשבון, נשלח אימייל לאיפוס.' : 'Si cette adresse est associée à un compte, un email de réinitialisation a été envoyé.'));
        }
    }
}

$lbl_title = $lang === 'en' ? 'Forgot your password?' : ($lang === 'ar' ? 'نسيت كلمة المرور؟' : ($lang === 'he' ? 'שכחת את הסיסמה?' : 'Mot de passe oublié ?'));
$lbl_sub = $lang === 'en' ? 'Enter your email and we\'ll send you a link to reset your password.' : ($lang === 'ar' ? 'أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور.' : ($lang === 'he' ? 'הזן את האימייל שלך ונשלח לך קישור לאיפוס הסיסמה.' : 'Entrez votre email et nous vous enverrons un lien pour réinitialiser votre mot de passe.'));
$lbl_email = $lang === 'en' ? 'Email address' : ($lang === 'ar' ? 'عنوان البريد الإلكتروني' : ($lang === 'he' ? 'כתובת אימייל' : 'Adresse email'));
$lbl_submit = $lang === 'en' ? 'Send reset link →' : ($lang === 'ar' ? 'إرسال رابط إعادة التعيين →' : ($lang === 'he' ? 'שלח קישור לאיפוס →' : 'Envoyer le lien →'));
$lbl_back = $lang === 'en' ? '← Back to login' : ($lang === 'ar' ? '← العودة لتسجيل الدخول' : ($lang === 'he' ? '← חזרה להתחברות' : '← Retour à la connexion'));
$lbl_back_nav = $lbl_back;
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
  <link href="../assets/css/pages/mot-de-passe-oublie.css" rel="stylesheet">
</head>
<body class="auth-page">

  <nav class="navbar">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <div style="display:flex;align-items:center;gap:12px">
      <?= lang_switcher() ?>
      <a class="navbar-link" href="connexion.php"><?= $lbl_back_nav ?></a>
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
          <?= htmlspecialchars($succes) ?><br><br>
          <a href="connexion.php" style="color:#4ade80;font-weight:600"><?= $lbl_back ?></a>
        </div>
        <?php else: ?>
        <?php if ($erreurs): ?>
        <div class="alert-err">
          <?php foreach ($erreurs as $e): ?>
          <?= htmlspecialchars($e) ?><br>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" data-cyna-validate="forgot-password">
          <?= csrf_field() ?>
          <div class="field">
            <label class="field-label"><?= $lbl_email ?></label>
            <input class="field-input" type="email" name="email" required placeholder="you@example.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <button type="submit" class="btn-submit"><?= $lbl_submit ?></button>
        </form>
        <a href="connexion.php" class="back-link"><?= $lbl_back ?></a>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <?php form_validation_include($lang); ?>
</body>
</html>
