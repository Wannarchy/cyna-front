<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/function.php';
require_once __DIR__ . '/../includes/lang.php';

$message = '';
$message_type = 'erreur';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('connexion.php');
    exit;
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$resendId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if (! $email && $resendId <= 0) {
    $message = $lang === 'en' ? 'Invalid email address.' : ($lang === 'ar' ? 'عنوان البريد الإلكتروني غير صالح.' : ($lang === 'he' ? 'כתובת אימייל לא תקינה.' : 'Adresse email invalide.'));
} else {
    try {
        if (! empty($_SESSION['api_token'])) {
            api_client()->resendVerification();
        } elseif ($resendId > 0) {
            api_client()->resendVerificationByEmail('', $resendId);
        } else {
            api_client()->resendVerificationByEmail($email);
        }
        $message = $lang === 'en' ? 'If this address is associated with an unconfirmed account, a new email has been sent (valid 24 hours).' : ($lang === 'ar' ? 'إذا كان هذا العنوان مرتبطاً بحساب غير مؤكد، فقد تم إرسال بريد جديد (صالح 24 ساعة).' : ($lang === 'he' ? 'אם כתובת זו משויכת לחשבון לא מאומת, נשלח אימייל חדש (תקף 24 שעות).' : 'Si cette adresse est associée à un compte non confirmé, un nouvel email vient d\'être envoyé (lien valable 24 h).'));
        $message_type = 'succes';
    } catch (RuntimeException $e) {
        $message = $e->getMessage();
    }
}

$lbl_title = $lang === 'en' ? 'Confirmation email' : ($lang === 'ar' ? 'بريد التأكيد' : ($lang === 'he' ? 'אימייל אישור' : 'Email de confirmation'));
$lbl_back = $lang === 'en' ? '← Back to login' : ($lang === 'ar' ? '← العودة لتسجيل الدخول' : ($lang === 'he' ? '← חזרה להתחברות' : '← Retour à la connexion'));
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
  <link href="../assets/css/pages/renvoyer-confirmation.css" rel="stylesheet">
</head>
<body class="auth-status">

  <div class="card card--center">
    <div class="icon"></div>
    <div class="card-title"><?= $lbl_title ?></div>
    <div class="message <?= $message_type ?>">
      <p><?= htmlspecialchars($message) ?></p>
    </div>
    <a href="connexion.php" class="btn-link"><?= $lbl_back ?></a>
  </div>

</body>
</html>
