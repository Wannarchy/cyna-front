<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/lang.php';
$raison = htmlspecialchars($_GET['raison'] ?? ($lang==='en'?'Your payment was declined by your bank.':($lang==='ar'?'تم رفض دفعتك من قِبل بنكك.':($lang==='he'?'התשלום שלך נדחה על ידי הבנק שלך.':'Votre paiement a été refusé par votre banque.'))));
?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — <?= $lang==='en'?'Payment declined':($lang==='ar'?'الدفع مرفوض':($lang==='he'?'תשלום נדחה':'Paiement refusé')) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/css/public-mobile.css" rel="stylesheet">
  <link href="../assets/css/pages/auth.css" rel="stylesheet">
  <link href="../assets/css/pages/paiement-refuse.css" rel="stylesheet">
</head>
<body class="auth-page">

  <div class="bg-deco bg-deco--error"></div>

  <nav class="navbar">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <?= lang_switcher() ?>
  </nav>

  <main>
    <div class="card">
      <div class="error-circle"></div>
      <h1><?= $lang==='en'?'Payment declined':($lang==='ar'?'الدفع مرفوض':($lang==='he'?'תשלום נדחה':'Paiement refusé')) ?></h1>
      <div class="sub"><?= $lang==='en'?'Your payment could not be processed. No amount has been debited.':($lang==='ar'?'تعذّر معالجة دفعتك. لم يُخصم أي مبلغ.':($lang==='he'?'לא ניתן היה לעבד את התשלום שלך. לא חויב כל סכום.':'Votre paiement n\'a pas pu être traité. Aucun montant n\'a été débité.')) ?></div>

      <div class="reason-box">
        <?= $raison ?>
      </div>

      <div class="tips">
        <div class="tips-title"><?= $lang==='en'?'What to do?':($lang==='ar'?'ماذا تفعل؟':($lang==='he'?'מה לעשות?':'Que faire ?')) ?></div>
        <div class="tip">
          <span class="tip-icon"></span>
          <?= $lang==='en'?'Check that the card number, expiry date and CVV are correct.':($lang==='ar'?'تحقق من صحة رقم البطاقة وتاريخ الانتهاء ورمز CVV.':($lang==='he'?'בדוק שמספר הכרטיס, תאריך התפוגה וה-CVV נכונים.':'Vérifiez que le numéro de carte, la date d\'expiration et le CVV sont corrects.')) ?>
        </div>
        <div class="tip">
          <span class="tip-icon"></span>
          <?= $lang==='en'?'Make sure your card has sufficient funds.':($lang==='ar'?'تأكد من وجود رصيد كافٍ في بطاقتك.':($lang==='he'?'ודא שיש בכרטיס שלך מספיק כספים.':'Assurez-vous que votre carte dispose de fonds suffisants.')) ?>
        </div>
        <div class="tip">
          <span class="tip-icon"></span>
          <?= $lang==='en'?'Contact your bank — some banks block online payments by default.':($lang==='ar'?'تواصل مع بنكك — بعض البنوك تحجب المدفوعات الإلكترونية افتراضياً.':($lang==='he'?'צור קשר עם הבנק שלך — חלק מהבנקים חוסמים תשלומים מקוונים כברירת מחדל.':'Contactez votre banque — certaines banques bloquent les paiements en ligne par défaut.')) ?>
        </div>
        <div class="tip">
          <span class="tip-icon"></span>
          <?= $lang==='en'?'Try with a different card.':($lang==='ar'?'جرّب بطاقة أخرى.':($lang==='he'?'נסה עם כרטיס אחר.':'Essayez avec une autre carte bancaire.')) ?>
        </div>
      </div>

      <div class="actions">
        <a href="checkout.php" class="btn-primary">
          <?= $lang==='en'?'Retry payment':($lang==='ar'?'إعادة المحاولة':($lang==='he'?'נסה שוב':'Réessayer le paiement')) ?>
        </a>
        <a href="panier.php" class="btn-secondary">
          ← <?= $lang==='en'?'Back to cart':($lang==='ar'?'العودة للسلة':($lang==='he'?'חזרה לעגלה':'Retour au panier')) ?>
        </a>
      </div>
    </div>
  </main>

  <footer>© 2025 CYNA-IT — Support : contact@cyna-it.fr</footer>

</body>
</html>