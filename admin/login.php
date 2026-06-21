<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();

require_once __DIR__ . '/../includes/admin_helpers.php';

if (! empty($_SESSION['api_token']) && ! empty($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$step = ! empty($_SESSION['admin_otp_challenge']) ? 'otp' : 'credentials';
$otpEmail = (string) ($_SESSION['admin_otp_email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'cancel_otp') {
        unset($_SESSION['admin_otp_challenge'], $_SESSION['admin_otp_email']);
        header('Location: login.php');
        exit;
    }

    if ($action === 'verify_otp') {
        $code = preg_replace('/\D+/', '', (string) ($_POST['otp_code'] ?? '')) ?? '';
        $challenge = (string) ($_SESSION['admin_otp_challenge'] ?? '');

        if ($challenge === '' || strlen($code) !== 8) {
            $error = 'Veuillez saisir le code à 8 chiffres reçu par e-mail.';
            $step = 'otp';
        } else {
            try {
                $response = admin_api()->verifyAdminLoginOtp($challenge, $code);

                if (empty($_SESSION['is_admin'])) {
                    admin_api()->logout();
                    $error = 'Identifiants invalides ou compte non-admin.';
                    unset($_SESSION['admin_otp_challenge'], $_SESSION['admin_otp_email']);
                    $step = 'credentials';
                } else {
                    unset($_SESSION['admin_otp_challenge'], $_SESSION['admin_otp_email']);
                    header('Location: index.php');
                    exit;
                }
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
                $step = 'otp';
            }
        }
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Veuillez remplir tous les champs.';
        } else {
            try {
                $response = admin_api()->login($email, $password);

                if (! empty($response['data']['requires_otp'])) {
                    $_SESSION['admin_otp_challenge'] = $response['data']['challenge_token'] ?? '';
                    $_SESSION['admin_otp_email'] = $email;
                    $otpEmail = $email;
                    $step = 'otp';
                } elseif (empty($_SESSION['is_admin'])) {
                    admin_api()->logout();
                    $error = 'Identifiants invalides ou compte non-admin.';
                } else {
                    header('Location: index.php');
                    exit;
                }
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA Admin — Connexion</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{--blue:#1a2980;--cyan:#26d0ce;--grad:linear-gradient(135deg,#1a2980,#26d0ce);}
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'DM Sans',sans-serif;background:#07090f;color:#e8eaf2;min-height:100vh;display:flex;align-items:center;justify-content:center;}
    .wrap{width:100%;max-width:420px;padding:16px;}
    .brand{text-align:center;margin-bottom:32px;}
    .logo{width:52px;height:52px;border-radius:14px;background:var(--grad);margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:800;color:#fff;}
    .brand h1{font-size:1.5rem;font-weight:700;color:#fff;}
    .brand p{font-size:.82rem;color:#5c6378;margin-top:4px;}
    .card{background:#0e1117;border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:32px;}
    .error-box{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:10px;padding:12px 16px;font-size:.82rem;color:#f87171;margin-bottom:20px;}
    .info-box{background:rgba(38,208,206,.08);border:1px solid rgba(38,208,206,.2);border-radius:10px;padding:12px 16px;font-size:.82rem;color:#7dd3fc;margin-bottom:20px;line-height:1.5;}
    .field{margin-bottom:18px;}
    .field label{display:block;font-size:.73rem;font-weight:600;color:#8b92a8;margin-bottom:6px;text-transform:uppercase;}
    .field input{width:100%;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:11px 14px;font-size:.9rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;}
    .field input:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(38,208,206,.12);}
    .field input.otp-input{font-size:1.4rem;letter-spacing:.35em;text-align:center;font-weight:700;}
    .btn{width:100%;padding:12px;background:var(--grad);color:#fff;border:none;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}
    .btn-secondary{margin-top:12px;background:transparent;border:1px solid rgba(255,255,255,.12);color:#8b92a8;}
  </style>
</head>
<body>
<div class="wrap">
  <div class="brand">
    <div class="logo">C</div>
    <h1>CYNA Admin</h1>
    <p><?= $step === 'otp' ? 'Vérification en deux étapes' : 'Accès réservé aux administrateurs' ?></p>
  </div>
  <div class="card">
    <?php if ($error): ?>
      <div class="error-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($step === 'otp'): ?>
      <div class="info-box">
        Un code à 8 chiffres a été envoyé à <strong><?= htmlspecialchars($otpEmail) ?></strong>.
        Il est valable 15 minutes.
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="verify_otp">
        <div class="field">
          <label>Code de vérification</label>
          <input class="otp-input" type="text" name="otp_code" required maxlength="8" pattern="\d{8}" inputmode="numeric" autocomplete="one-time-code" placeholder="12345678">
        </div>
        <button class="btn" type="submit">Valider le code →</button>
      </form>
      <form method="POST">
        <input type="hidden" name="action" value="cancel_otp">
        <button class="btn btn-secondary" type="submit">← Retour à la connexion</button>
      </form>
    <?php else: ?>
      <form method="POST" data-cyna-validate="admin-login">
        <div class="field">
          <label>Adresse e-mail</label>
          <input type="email" name="email" required placeholder="admin@cyna.com" autocomplete="email">
        </div>
        <div class="field">
          <label>Mot de passe</label>
          <input type="password" name="password" required placeholder="••••••••" autocomplete="current-password">
        </div>
        <button class="btn">Se connecter →</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php
require_once __DIR__ . '/../includes/form_validation.php';
form_validation_include('fr');
?>
</body>
</html>
