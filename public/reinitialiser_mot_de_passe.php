<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/function.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/password_helpers.php';
require_once __DIR__ . '/../includes/form_validation.php';

$erreurs = [];
$succes = '';

$email = trim($_POST['email'] ?? $_GET['email'] ?? '');
$token = trim($_POST['token'] ?? $_GET['token'] ?? '');

if ($email === '' || $token === '') {
    die($lang === 'en' ? 'Invalid link. Please make a new reset request.' : ($lang === 'ar' ? 'رابط غير صالح.' : ($lang === 'he' ? 'קישור לא תקין.' : 'Lien invalide. Veuillez refaire une demande de réinitialisation.')));
}

$token_valide = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau = $_POST['nouveau_mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation_mot_de_passe'] ?? '';

    $erreurs = array_merge($erreurs, password_policy_errors($nouveau, $lang));
    if ($nouveau !== $confirmation) {
        $erreurs[] = $lang === 'en' ? 'Passwords do not match.' : ($lang === 'ar' ? 'كلمتا المرور غير متطابقتان.' : ($lang === 'he' ? 'הסיסמאות אינן תואמות.' : 'Les mots de passe ne correspondent pas.'));
    }

    if (empty($erreurs)) {
        try {
            api_client()->resetPassword($email, $token, $nouveau);
            $succes = $lang === 'en' ? 'Your password has been reset successfully!' : ($lang === 'ar' ? 'تمت إعادة تعيين كلمة المرور بنجاح!' : ($lang === 'he' ? 'הסיסמה שלך אופסה בהצלחה!' : 'Votre mot de passe a été réinitialisé avec succès !'));
            $token_valide = false;
        } catch (RuntimeException $e) {
            $erreurs[] = $e->getMessage();
            $token_valide = false;
        }
    }
}

$lbl_title = $lang === 'en' ? 'New password' : ($lang === 'ar' ? 'كلمة مرور جديدة' : ($lang === 'he' ? 'סיסמה חדשה' : 'Nouveau mot de passe'));
$lbl_new_pwd = $lbl_title;
$lbl_confirm_pwd = $lang === 'en' ? 'Confirm password' : ($lang === 'ar' ? 'تأكيد كلمة المرور' : ($lang === 'he' ? 'אישור סיסמה' : 'Confirmer le mot de passe'));
$lbl_submit = $lang === 'en' ? 'Reset password' : ($lang === 'ar' ? 'إعادة تعيين' : ($lang === 'he' ? 'אפס סיסמה' : 'Réinitialiser'));
$lbl_back = $lang === 'en' ? '← Back to login' : ($lang === 'ar' ? '← العودة لتسجيل الدخول' : ($lang === 'he' ? '← חזרה להתחברות' : '← Retour à la connexion'));
$lbl_login_btn = $lang === 'en' ? 'Sign in' : ($lang === 'ar' ? 'تسجيل الدخول' : ($lang === 'he' ? 'התחבר' : 'Se connecter'));
$lbl_expired_title = $lang === 'en' ? '⚠️ This reset link is invalid or has expired.' : ($lang === 'ar' ? '⚠️ رابط إعادة التعيين غير صالح أو منتهي.' : ($lang === 'he' ? '⚠️ קישור האיפוס לא תקין או שפג תוקפו.' : '⚠️ Ce lien de réinitialisation est invalide ou a expiré.'));
$lbl_new_link = $lang === 'en' ? 'Request a new link' : ($lang === 'ar' ? 'طلب رابط جديد' : ($lang === 'he' ? 'בקש קישור חדש' : 'Demander un nouveau lien'));
?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $lbl_title ?> — CYNA</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/css/public-mobile.css" rel="stylesheet">
  <link href="../assets/css/pages/auth-status.css" rel="stylesheet">
  <link href="../assets/css/pages/reinitialiser-mot-de-passe.css" rel="stylesheet">
</head>
<body class="auth-status">
  <div class="card">
    <div class="card-title"><?= $lbl_title ?></div>

    <?php if ($erreurs): ?>
    <div class="alert-err">
      <?php foreach ($erreurs as $e): ?>
      <p><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($succes): ?>
    <div class="alert-ok">
      <p>✅ <?= htmlspecialchars($succes) ?></p>
      <p style="margin-top:12px"><a href="connexion.php" class="btn-link"><?= $lbl_login_btn ?></a></p>
    </div>
    <?php elseif (! $token_valide): ?>
    <div class="alert-warn">
      <p><?= $lbl_expired_title ?></p>
    </div>
    <div class="text-center">
      <a href="mot_de_passe_oublie.php" class="btn-link"><?= $lbl_new_link ?></a>
    </div>
    <?php else: ?>
    <form method="POST" data-cyna-validate="password-reset">
      <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="field">
        <label><?= $lbl_new_pwd ?></label>
        <input type="password" name="nouveau_mot_de_passe" required placeholder="<?= htmlspecialchars(password_policy_placeholder($lang)) ?>">
      </div>
      <div class="field">
        <label><?= $lbl_confirm_pwd ?></label>
        <input type="password" name="confirmation_mot_de_passe" required>
      </div>
      <button type="submit" class="btn-submit"><?= $lbl_submit ?></button>
    </form>
    <a href="connexion.php" class="back-link"><?= $lbl_back ?></a>
    <?php endif; ?>
  </div>

  <?php form_validation_include($lang); ?>
</body>
</html>
