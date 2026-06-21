<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/function.php';
require_once __DIR__ . '/../includes/lang.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$email = isset($_GET['email']) ? urldecode((string) $_GET['email']) : '';
$token = (string) ($_GET['token'] ?? '');

if ($token === '' || ($id <= 0 && $email === '')) {
    die('Paramètres invalides.');
}

$message = '';
$message_type = 'erreur';
$redirect = false;
$expired = false;
$resend_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resend') {
    try {
        $resendId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $resendEmail = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
        if ($resendId > 0) {
            api_client()->resendVerificationByEmail('', $resendId);
        } elseif ($resendEmail) {
            api_client()->resendVerificationByEmail($resendEmail);
        }
        $resend_ok = true;
        $message = $lang === 'en'
            ? 'If your account is not yet confirmed, a new email has been sent. The link is valid for 24 hours.'
            : ($lang === 'ar'
                ? 'إذا لم يتم تأكيد حسابك بعد، فقد أُرسل بريد جديد. الرابط صالح لمدة 24 ساعة.'
                : ($lang === 'he'
                    ? 'אם החשבון שלך עדיין לא אושר, נשלח אימייל חדש. הקישור תקף ל-24 שעות.'
                    : 'Si votre compte n\'est pas encore confirmé, un nouvel email vient d\'être envoyé. Le lien est valable 24 heures.'));
        $message_type = 'succes';
    } catch (RuntimeException $e) {
        $message = $e->getMessage();
    }
} else {
    $payload = ['token' => $token];
    if ($id > 0) {
        $payload['id'] = $id;
    } else {
        $payload['email'] = $email;
    }

    $result = api_client()->verifyRegistrationEmail($payload);

    if ($result['ok']) {
        $message = $lang === 'en'
            ? 'Your account has been confirmed! You can now log in.'
            : ($lang === 'ar'
                ? 'تم تأكيد حسابك بنجاح!'
                : ($lang === 'he'
                    ? 'חשבונך אושר בהצלחה!'
                    : 'Votre compte a été confirmé avec succès ! Vous pouvez maintenant vous connecter.'));
        $message_type = 'succes';
        $redirect = true;
    } else {
        $message = $result['message'];
        $expired = $result['expired'];
    }
}

$lbl_title = $lang === 'en' ? 'Account confirmation' : ($lang === 'ar' ? 'تأكيد الحساب' : ($lang === 'he' ? 'אישור חשבון' : 'Confirmation de compte'));
$lbl_confirmed = $lang === 'en' ? 'Account confirmed!' : ($lang === 'ar' ? 'تم تأكيد الحساب!' : ($lang === 'he' ? 'חשבון אושר!' : 'Compte confirmé !'));
$lbl_login = $lang === 'en' ? 'Log in' : ($lang === 'ar' ? 'تسجيل الدخول' : ($lang === 'he' ? 'התחבר' : 'Se connecter'));
$lbl_redirect = $lang === 'en' ? 'Automatic redirect in 4 seconds…' : ($lang === 'ar' ? 'إعادة توجيه تلقائية خلال 4 ثوانٍ…' : ($lang === 'he' ? 'הפניה אוטומטית תוך 4 שניות…' : 'Redirection automatique dans 4 secondes…'));
$lbl_expired = $lang === 'en' ? 'Link expired' : ($lang === 'ar' ? 'انتهت صلاحية الرابط' : ($lang === 'he' ? 'פג תוקף הקישור' : 'Lien expiré'));
$lbl_resend = $lang === 'en' ? '↺ Send a new confirmation email' : ($lang === 'ar' ? '↺ إرسال بريد تأكيد جديد' : ($lang === 'he' ? '↺ שלח אימייל אישור חדש' : '↺ Renvoyer un email de confirmation'));
?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $lbl_title ?> — CYNA</title>
  <?php if ($redirect): ?>
  <meta http-equiv="refresh" content="4;url=connexion.php">
  <?php endif; ?>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/css/public-mobile.css" rel="stylesheet">
  <link href="../assets/css/pages/auth-status.css" rel="stylesheet">
  <link href="../assets/css/pages/confirmer-email.css" rel="stylesheet">
</head>
<body class="auth-status">

  <div class="card card--center">
    <div class="icon"></div>
    <h2><?= $message_type === 'succes' ? $lbl_confirmed : ($expired ? $lbl_expired : $lbl_title) ?></h2>

    <div class="message <?= $message_type === 'succes' ? 'succes' : ($expired ? 'warn' : 'erreur') ?>">
      <p><?= htmlspecialchars($message) ?></p>
    </div>

    <?php if ($redirect): ?>
    <a href="connexion.php" class="btn"><?= $lbl_login ?></a>
    <p class="redirect-info"><?= $lbl_redirect ?></p>
    <?php elseif ($expired && ! $resend_ok): ?>
    <form method="POST">
      <input type="hidden" name="action" value="resend">
      <?php if ($id > 0): ?>
      <input type="hidden" name="id" value="<?= $id ?>">
      <?php endif; ?>
      <?php if ($email !== ''): ?>
      <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
      <?php endif; ?>
      <button type="submit" class="btn btn-resend"><?= $lbl_resend ?></button>
    </form>
    <p class="redirect-info" style="margin-top:18px">
      <a href="connexion.php" style="color:rgba(255,255,255,.45);text-decoration:none"><?= $lbl_login ?></a>
    </p>
    <?php elseif ($resend_ok): ?>
    <a href="connexion.php" class="btn"><?= $lbl_login ?></a>
    <?php else: ?>
    <a href="connexion.php" class="btn"><?= $lbl_login ?></a>
    <?php endif; ?>
  </div>

</body>
</html>
