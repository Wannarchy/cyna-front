<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/form_validation.php';

$est_connecte = isset($_SESSION['utilisateur_id']);
$nb_panier    = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
$success      = false;
$errors       = [];

$prefill_email = '';
if ($est_connecte) {
    $prefill_email = $_SESSION['utilisateur_email'] ?? '';
}

// FORMULAIRE DE CONTACT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'contact') {
    $email   = trim($_POST['email']   ?? '');
    $sujet   = trim($_POST['sujet']   ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = $lang==='en'?'Invalid email address.':($lang==='ar'?'عنوان البريد الإلكتروني غير صالح.':($lang==='he'?'כתובת אימייל לא תקינה.':'Adresse email invalide.'));
    if (strlen($sujet) < 3)    $errors[] = $lang==='en'?'Subject too short.':($lang==='ar'?'الموضوع قصير جداً.':($lang==='he'?'הנושא קצר מדי.':'Le sujet est trop court.'));
    if (strlen($message) < 10) $errors[] = $lang==='en'?'Message too short.':($lang==='ar'?'الرسالة قصيرة جداً.':($lang==='he'?'ההודעה קצרה מדי.':'Le message est trop court.'));

    if (empty($errors)) {
        try {
            api_client()->submitContact($email, $sujet, $message);
            $success = true;
        } catch (Throwable $e) {
            $errors[] = $lang === 'en'
                ? 'Unable to send your message. Please try again later.'
                : ($lang === 'ar'
                    ? 'تعذر إرسال رسالتك. يرجى المحاولة لاحقاً.'
                    : ($lang === 'he'
                        ? 'לא ניתן לשלוח את ההודעה. נסה שוב מאוחר יותר.'
                        : 'Impossible d\'envoyer votre message. Veuillez réessayer plus tard.'));
        }
    }
}

// CHATBOT API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'chat') {
    header('Content-Type: application/json');
    $user_msg = trim($_POST['message'] ?? '');
    if (empty($user_msg)) {
        echo json_encode(['response' => $lang==='en'?'Hello! How can I help you?':($lang==='ar'?'مرحباً! كيف يمكنني مساعدتك؟':($lang==='he'?'שלום! איך אוכל לעזור?':'Bonjour ! Comment puis-je vous aider ?'))]);
        exit;
    }

    if ($est_connecte && ! empty($_SESSION['api_token'])) {
        try {
            $chat = api_client()->sendChatMessage($user_msg, $_SESSION['chat_session_id'] ?? null);
            if (! empty($chat['session_id'])) {
                $_SESSION['chat_session_id'] = $chat['session_id'];
            }
            echo json_encode(['response' => $chat['bot_response'] ?? '']);
            exit;
        } catch (Throwable) {
        }
    }

    $knowledge = "
Tu es l'assistant virtuel de CYNA, une entreprise spécialisée dans les solutions de cybersécurité SaaS (SOC, EDR, XDR).
CYNA est situé au 10 Rue de Penthièvre, 75008 Paris. SIRET : 91371103200015. Email : contact@cyna-it.fr.
Horaires : Lun-Ven 9h-18h.
Produits CYNA :
- SOC (Security Operations Center) : surveillance et détection 24/7, à partir de 299€/mois
- EDR (Endpoint Detection & Response) : protection des endpoints, à partir de 149€/mois
- XDR (Extended Detection & Response) : corrélation multi-sources, prix selon configuration
Paiement : Visa, Mastercard, American Express via Stripe. Paiement sécurisé SSL.
Abonnements : mensuel ou annuel (10% de réduction). Résiliation possible à tout moment depuis l'espace compte.
Support 24/7 inclus dans tous les abonnements.
Répondre dans la langue de l'utilisateur, de façon concise et professionnelle (max 2-3 phrases).
Si tu ne sais pas, propose de contacter le support via le formulaire.
Ne jamais inventer de prix ou de fonctionnalités non listées.
";

    $api_url = 'https://api.anthropic.com/v1/messages';
    $payload = json_encode([
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 300,
        'system'     => $knowledge,
        'messages'   => [[' role' => 'user', ' content' => $user_msg]]
    ]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . ($_ENV['ANTHROPIC_API_KEY'] ?? ''),
        'anthropic-version: 2023-06-01'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data    = json_decode($response, true);
    $bot_msg = $data[' content'][0]['text'] ?? null;

    if (!$bot_msg) {
        $msg_lower = strtolower($user_msg);
        if (strpos($msg_lower, 'prix') !== false || strpos($msg_lower, 'tarif') !== false || strpos($msg_lower, 'price') !== false || strpos($msg_lower, 'cost') !== false || strpos($msg_lower, 'سعر') !== false || strpos($msg_lower, 'מחיר') !== false) {
            $bot_msg = $lang==='en'?'Our services start from €149/month for EDR, €299/month for SOC. Annual subscription saves 10%. Check our catalogue for detailed pricing.':($lang==='ar'?'تبدأ خدماتنا من 149€/شهر لـ EDR و299€/شهر لـ SOC. الاشتراك السنوي يوفر 10%.':($lang==='he'?'השירותים שלנו מתחילים מ-149€/חודש ל-EDR, 299€/חודש ל-SOC. מנוי שנתי חוסך 10%.':'Nos services démarrent à partir de 149€/mois pour l\'EDR, 299€/mois pour le SOC. Un abonnement annuel vous fait économiser 10%.'));
        } elseif (strpos($msg_lower, 'abonnement') !== false || strpos($msg_lower, 'subscription') !== false || strpos($msg_lower, 'اشتراك') !== false || strpos($msg_lower, 'מנוי') !== false) {
            $bot_msg = $lang==='en'?'You can manage your subscriptions from your account → "My subscriptions". Cancellation takes effect at the end of the current period, free of charge.':($lang==='ar'?'يمكنك إدارة اشتراكاتك من حسابك → "اشتراكاتي". يسري الإلغاء في نهاية الفترة الحالية دون رسوم.':($lang==='he'?'תוכל לנהל את המנויים שלך מחשבונך → "המנויים שלי". הביטול נכנס לתוקף בסוף התקופה הנוכחית.':'Vous pouvez gérer vos abonnements depuis votre espace compte → "Mes abonnements". La résiliation prend effet à la fin de la période en cours, sans frais.'));
        } elseif (strpos($msg_lower, 'paiement') !== false || strpos($msg_lower, 'payment') !== false || strpos($msg_lower, 'دفع') !== false || strpos($msg_lower, 'תשלום') !== false) {
            $bot_msg = $lang==='en'?'We accept Visa, Mastercard and American Express via Stripe (100% secure SSL payment). You can save your cards in "My payments".':($lang==='ar'?'نقبل Visa وMastercard وAmerican Express عبر Stripe (دفع آمن 100% SSL). يمكنك حفظ بطاقاتك في "طرق الدفع".':($lang==='he'?'אנו מקבלים Visa, Mastercard ו-American Express דרך Stripe (תשלום מאובטח 100% SSL).':'Nous acceptons Visa, Mastercard et American Express via Stripe (paiement 100% sécurisé SSL).'));
        } elseif (strpos($msg_lower, 'bonjour') !== false || strpos($msg_lower, 'hello') !== false || strpos($msg_lower, 'مرحبا') !== false || strpos($msg_lower, 'שלום') !== false) {
            $bot_msg = $lang==='en'?'Hello!  I\'m the CYNA virtual assistant. I can answer your questions about our SOC, EDR, XDR services, subscriptions or payment. How can I help?':($lang==='ar'?'مرحباً!  أنا المساعد الافتراضي لـ CYNA. كيف يمكنني مساعدتك؟':($lang==='he'?'שלום!  אני העוזר הווירטואלי של CYNA. כיצד אוכל לעזור?':'Bonjour !  Je suis l\'assistant virtuel CYNA. Comment puis-je vous aider ?'));
        } else {
            $bot_msg = $lang==='en'?'I\'m not sure I understand your request. For personalized assistance, please use the contact form or write to contact@cyna-it.fr.':($lang==='ar'?'لست متأكداً من فهم طلبك. للحصول على مساعدة شخصية، استخدم نموذج الاتصال أو اكتب إلى contact@cyna-it.fr.':($lang==='he'?'אני לא בטוח שהבנתי את בקשתך. לסיוע אישי, השתמש בטופס יצירת הקשר.':'Je ne suis pas sûr de comprendre votre demande. Pour une assistance personnalisée, n\'hésitez pas à utiliser le formulaire de contact.'));
        }
    }

    echo json_encode(['response' => $bot_msg]);
    exit;
}

// Labels traduits
$lbl_hero_tag = $lang==='en'?'Support':($lang==='ar'?' الدعم':($lang==='he'?' תמיכה':'Support'));
$lbl_hero_h1  = $lang==='en'?'Contact us':($lang==='ar'?'تواصل معنا':($lang==='he'?'צור קשר':'Contactez-nous'));
$lbl_hero_p   = $lang==='en'?'Our team is available Monday to Friday, 9am to 6pm to answer your questions.':($lang==='ar'?'فريقنا متاح من الاثنين إلى الجمعة، من 9 صباحاً حتى 6 مساءً للإجابة على أسئلتك.':($lang==='he'?'הצוות שלנו זמין מיום שני עד שישי, 9:00-18:00 לענות על שאלותיך.':'Notre équipe est disponible du lundi au vendredi, de 9h à 18h pour répondre à vos questions.'));
$lbl_send_msg = $lang==='en'?'Send a message':($lang==='ar'?' إرسال رسالة':($lang==='he'?' שלח הודעה':'Envoyer un message'));
$lbl_email    = $lang==='en'?'Email address *':($lang==='ar'?'عنوان البريد الإلكتروني *':($lang==='he'?'כתובת אימייל *':'Adresse email *'));
$lbl_subject  = $lang==='en'?'Subject *':($lang==='ar'?'الموضوع *':($lang==='he'?'נושא *':'Sujet *'));
$lbl_message  = $lang==='en'?'Message *':($lang==='ar'?'الرسالة *':($lang==='he'?'הודעה *':'Message *'));
$lbl_send_btn = $lang==='en'?'Send message →':($lang==='ar'?'إرسال الرسالة →':($lang==='he'?'שלח הודעה →':'Envoyer le message →'));
$lbl_sent_ok  = $lang==='en'?'Message sent!':($lang==='ar'?'تم إرسال الرسالة!':($lang==='he'?'ההודעה נשלחה!':'Message envoyé !'));
$lbl_sent_sub = $lang==='en'?'Thank you! Our team will reply within 24h.':($lang==='ar'?'شكراً! سيرد فريقنا خلال 24 ساعة.':($lang==='he'?'תודה! הצוות שלנו יגיב תוך 24 שעות.':'Merci ! Notre équipe vous répondra sous 24h.'));
$lbl_another  = $lang==='en'?'Send another message →':($lang==='ar'?'إرسال رسالة أخرى →':($lang==='he'?'שלח הודעה נוספת →':'Envoyer un autre message →'));
$lbl_coords   = $lang==='en'?'Our contact details':($lang==='ar'?' معلومات الاتصال':($lang==='he'?' פרטי יצירת קשר':'Nos coordonnées'));
$lbl_address  = $lang==='en'?'Address':($lang==='ar'?'العنوان':($lang==='he'?'כתובת':'Adresse'));
$lbl_hours    = $lang==='en'?'Business hours':($lang==='ar'?'ساعات العمل':($lang==='he'?'שעות פעילות':'Horaires'));
$lbl_hours_val= $lang==='en'?'Mon–Fri: 9am–6pm<br>Excluding public holidays':($lang==='ar'?'الاثنين–الجمعة: 9ص–6م<br>باستثناء أيام العطل':($lang==='he'?'שני–שישי: 9:00–18:00<br>למעט ימי חג':'Lun–Ven : 9h–18h<br>Hors jours fériés'));
$lbl_faq      = $lang==='en'?'Frequently asked questions':($lang==='ar'?' الأسئلة الشائعة':($lang==='he'?' שאלות נפוצות':'Questions fréquentes'));
$lbl_chat_btn = $lang==='en'?'Chat':($lang==='ar'?' محادثة':($lang==='he'?' צ\'אט':'Contact Me'));
$lbl_chat_name= $lang==='en'?'CYNA Assistant':($lang==='ar'?'مساعد CYNA':($lang==='he'?'עוזר CYNA':'Assistant CYNA'));
$lbl_chat_online= $lang==='en'?'Online — Responds instantly':($lang==='ar'?'متصل — يستجيب فوراً':($lang==='he'?'מחובר — מגיב מיידית':'En ligne — Répond instantanément'));
$lbl_chat_welcome= $lang==='en'?'Hello! I\'m the CYNA virtual assistant. How can I help you?':($lang==='ar'?' مرحباً! أنا المساعد الافتراضي لـ CYNA. كيف يمكنني مساعدتك؟':($lang==='he'?' שלום! אני העוזר הווירטואלי של CYNA. כיצד אוכל לעזור?':'Bonjour ! Je suis l\'assistant virtuel CYNA. Comment puis-je vous aider ?'));
$lbl_placeholder = $lang==='en'?'Write your message...':($lang==='ar'?'اكتب رسالتك...':($lang==='he'?'כתוב את הודעתך...':'Écrivez votre message...'));
$lbl_close    = $lang==='en'?'Close':($lang==='ar'?' إغلاق':($lang==='he'?' סגור':'Fermer'));
$lbl_open_chat= $lang==='en'?'Chat':($lang==='ar'?' محادثة':($lang==='he'?' צ\'אט':'Contact Me'));

$subjects = $lang==='en'
    ? ['Question about subscriptions','Technical issue','Quote request','Billing','Partnership','Other']
    : ($lang==='ar'
        ? ['سؤال حول الاشتراكات','مشكلة تقنية','طلب عرض سعر','الفوترة','شراكة','أخرى']
        : ($lang==='he'
            ? ['שאלה על מנויים','בעיה טכנית','בקשת הצעת מחיר','חיוב','שותפות','אחר']
            : ['Question sur les abonnements','Problème technique','Demande de devis','Facturation','Partenariat','Autre']));

$faqs = $lang==='en' ? [
    ['How do I change my subscription?', 'Log in → "My subscriptions" → click "Change"next to the desired subscription.'],
    ['What payment methods do you accept?', 'We accept Visa, Mastercard and American Express via Stripe (secure SSL payment).'],
    ['How do I cancel my subscription?', 'From "My subscriptions" → "Cancel"button. Cancellation is effective at the end of the current period.'],
    ['Is there a trial period?', 'Some services offer a free trial. Check the corresponding product page for details.'],
    ['How do I reset my password?', 'Login page → "Forgot password" → enter your email → click the link received (valid 24h).'],
] : ($lang==='ar' ? [
    ['كيف أغيّر اشتراكي؟', 'تسجيل الدخول → "اشتراكاتي" → انقر "تغيير" بجانب الاشتراك المطلوب.'],
    ['ما طرق الدفع المقبولة؟', 'نقبل Visa وMastercard وAmerican Express عبر Stripe (دفع آمن SSL).'],
    ['كيف أُلغي اشتراكي؟', 'من "اشتراكاتي" → زر "إلغاء". يسري الإلغاء في نهاية الفترة الحالية.'],
    ['هل هناك فترة تجريبية؟', 'بعض الخدمات تقدم فترة تجريبية مجانية. تحقق من صفحة المنتج.'],
    ['كيف أسترجع كلمة المرور؟', 'صفحة تسجيل الدخول → "نسيت كلمة المرور" → أدخل بريدك الإلكتروني.'],
] : ($lang==='he' ? [
    ['כיצד אשנה את המנוי שלי?', 'התחבר → "המנויים שלי" → לחץ "שנה" לצד המנוי הרצוי.'],
    ['אילו אמצעי תשלום מקובלים?', 'אנו מקבלים Visa, Mastercard ו-American Express דרך Stripe (תשלום מאובטח SSL).'],
    ['כיצד אבטל את המנוי שלי?', 'מ"המנויים שלי" → כפתור "בטל". הביטול נכנס לתוקף בסוף התקופה הנוכחית.'],
    ['האם יש תקופת ניסיון?', 'חלק מהשירותים מציעים ניסיון חינם. בדוק את דף המוצר המתאים.'],
    ['כיצד אשחזר את הסיסמה?', 'דף כניסה → "שכחת סיסמה" → הזן את האימייל שלך → לחץ על הקישור שנשלח.'],
] : [
    ['Comment modifier mon abonnement ?', 'Connectez-vous → "Mes abonnements" → cliquez sur "Changer"à côté de l\'abonnement souhaité.'],
    ['Quels modes de paiement acceptez-vous ?', 'Nous acceptons Visa, Mastercard et American Express via Stripe (paiement sécurisé SSL).'],
    ['Comment résilier mon abonnement ?', 'Depuis "Mes abonnements" → bouton "Résilier". La résiliation est effective à la fin de la période en cours.'],
    ['Y a-t-il une période d\'essai ?', 'Certains services proposent un essai gratuit. Consultez la page produit correspondante pour les détails.'],
    ['Comment récupérer mon mot de passe ?', 'Page de connexion → "Mot de passe oublié" → entrez votre email → cliquez le lien reçu (valide 24h).'],
]));
?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — <?= t('contact') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/css/public-mobile.css" rel="stylesheet">
  <link href="../assets/css/pages/legacy-navbar.css" rel="stylesheet">
  <link href="../assets/css/pages/contact.css" rel="stylesheet">
</head>
<body>
  <nav class="navbar navbar-expand-lg sticky-top legacy-nav navbar--tall">
    <div class="container">
      <a class="navbar-brand" href="../index.php">CYNA</a>
      <div class="d-flex align-items-center gap-2 ms-auto">
        <a href="catalogue.php" class="nav-link-p d-none d-md-block"><?= t('nav_catalogue') ?></a>
        <a href="panier.php" class="cart-btn"><?= $nb_panier > 0 ? " ($nb_panier)" : '' ?></a>
        <?php if ($est_connecte): ?>
        <a href="mon-compte.php" class="nav-link-p"><?= t('nav_account') ?></a>
        <a href="deconnexion.php" class="nav-link-p"><?= t('nav_logout') ?></a>
        <?php else: ?>
        <a href="connexion.php" class="nav-link-p"><?= t('nav_login') ?></a>
        <a href="inscription.php" class="btn-cyna"><?= t('nav_register') ?></a>
        <?php endif; ?>
        <?= lang_switcher() ?>
      </div>
    </div>
  </nav>

  <div class="container">
    <div class="hero">
      <div class="hero-tag"><?= $lbl_hero_tag ?></div>
      <h1><?= $lbl_hero_h1 ?></h1>
      <p><?= $lbl_hero_p ?></p>
    </div>

    <div class="row g-4 mb-5">
      <!-- FORMULAIRE -->
      <div class="col-12 col-lg-7">
        <div class="ccard">
          <h2><?= $lbl_send_msg ?></h2>
          <?php if ($success): ?>
          <div class="success-box">
            <div class="ico"></div>
            <h3><?= $lbl_sent_ok ?></h3>
            <p><?= $lbl_sent_sub ?></p>
            <a href="Contact.php" style="display:inline-block;margin-top:14px;color:var(--cyan);font-size:.84rem"><?= $lbl_another ?></a>
          </div>
          <?php else: ?>
          <?php if ($errors): ?>
          <div class="error-box"><?php foreach($errors as $e): ?> <?= htmlspecialchars($e) ?><br><?php endforeach; ?></div>
          <?php endif; ?>
          <form method="POST" data-cyna-validate="contact">
            <input type="hidden" name="action" value="contact">
            <div class="mb-3">
              <label class="form-label"><?= $lbl_email ?></label>
              <input class="form-control" type="email" name="email" required
                value="<?= htmlspecialchars($prefill_email ?: ($_POST['email'] ?? '')) ?>"
                placeholder="you@example.com">
            </div>
            <div class="mb-3">
              <label class="form-label"><?= $lbl_subject ?></label>
              <select class="form-select" name="sujet" required>
                <option value="">— <?= $lang==='en'?'Choose a subject':($lang==='ar'?'اختر موضوعاً':($lang==='he'?'בחר נושא':'Choisir un sujet')) ?> —</option>
                <?php foreach ($subjects as $s): ?>
                <option value="<?= $s ?>" <?= ($_POST['sujet']??'')===$s?' selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label"><?= $lbl_message ?></label>
              <textarea class="form-control" name="message" required minlength="10" maxlength="5000" placeholder="..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>
            <button class="btn-send" type="submit"><?= $lbl_send_btn ?></button>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- INFOS + FAQ -->
      <div class="col-12 col-lg-5">
        <div class="ccard" style="margin-bottom:16px">
          <h2><?= $lbl_coords ?></h2>
          <div class="info-item">
            <div class="info-icon"></div>
            <div><div class="info-label"><?= $lbl_address ?></div><div class="info-val">10 Rue de Penthièvre<br>75008 Paris, France</div></div>
          </div>
          <div class="info-item">
            <div class="info-icon"></div>
            <div><div class="info-label">Email</div><div class="info-val"><a href="mailto:contact@cyna-it.fr">contact@cyna-it.fr</a></div></div>
          </div>
          <div class="info-item">
            <div class="info-icon"></div>
            <div><div class="info-label"><?= $lbl_hours ?></div><div class="info-val"><?= $lbl_hours_val ?></div></div>
          </div>
          <div class="info-item" style="margin-bottom:0">
            <div class="info-icon"></div>
            <div><div class="info-label">Website</div><div class="info-val"><a href="https://www.cyna-it.fr" target="_blank">www.cyna-it.fr</a></div></div>
          </div>
        </div>
        <div class="ccard">
          <h2><?= $lbl_faq ?></h2>
          <?php foreach ($faqs as [$q, $a]): ?>
          <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)"><?= $q ?> <span class="chevron">▼</span></div>
            <div class="faq-a"><?= $a ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <footer>
    <a href="mention_legales.php"><?= t('legal') ?></a>
    <a href="Cgu.php"><?= t('cgu') ?></a>
    <a href="Contact.php"><?= t('contact') ?></a>
    <a href="a-propos.php"><?= t('about') ?></a>
    <span><?= t('copyright') ?></span>
  </footer>

  <!-- CHATBOT -->
  <button class="chat-fab" onclick="toggleChat()" id="chatFab">
    <span class="dot"></span> <?= $lbl_open_chat ?>
  </button>

  <div class="chat-window" id="chatWindow">
    <div class="chat-header">
      <div class="chat-avatar"></div>
      <div class="chat-header-info">
        <div class="chat-header-name"><?= $lbl_chat_name ?></div>
        <div class="chat-header-status"><?= $lbl_chat_online ?></div>
      </div>
      <button class="chat-close" onclick="toggleChat()"></button>
    </div>
    <div class="chat-messages" id="chatMessages">
      <div class="msg bot">
        <div class="msg-bubble"><?= $lbl_chat_welcome ?></div>
        <div class="msg-time"><?= $lang==='en'?'Now':($lang==='ar'?'الآن':($lang==='he'?'עכשיו':'Maintenant')) ?></div>
      </div>
    </div>
    <div class="chat-suggestions" id="chatSuggestions">
      <?php if ($lang==='en'): ?>
      <button class="sug-btn" onclick="sendSuggestion('What are your prices?')"> Pricing</button>
      <button class="sug-btn" onclick="sendSuggestion('How to change my subscription?')"> Subscription</button>
      <button class="sug-btn" onclick="sendSuggestion('Accepted payment methods?')"> Payment</button>
      <button class="sug-btn" onclick="sendSuggestion('Talk to a human')"> Agent</button>
      <?php elseif ($lang==='ar'): ?>
      <button class="sug-btn" onclick="sendSuggestion('ما هي أسعاركم؟')"> الأسعار</button>
      <button class="sug-btn" onclick="sendSuggestion('كيف أغيّر اشتراكي؟')"> الاشتراك</button>
      <button class="sug-btn" onclick="sendSuggestion('طرق الدفع المقبولة؟')"> الدفع</button>
      <button class="sug-btn" onclick="sendSuggestion('التحدث مع موظف')"> موظف</button>
      <?php elseif ($lang==='he'): ?>
      <button class="sug-btn" onclick="sendSuggestion('מה המחירים שלכם?')"> מחירים</button>
      <button class="sug-btn" onclick="sendSuggestion('כיצד לשנות את המנוי?')"> מנוי</button>
      <button class="sug-btn" onclick="sendSuggestion('אמצעי תשלום מקובלים?')"> תשלום</button>
      <button class="sug-btn" onclick="sendSuggestion('לדבר עם נציג')"> נציג</button>
      <?php else: ?>
      <button class="sug-btn" onclick="sendSuggestion('Quels sont vos tarifs ?')"> Tarifs</button>
      <button class="sug-btn" onclick="sendSuggestion('Comment modifier mon abonnement ?')"> Abonnement</button>
      <button class="sug-btn" onclick="sendSuggestion('Modes de paiement acceptés ?')"> Paiement</button>
      <button class="sug-btn" onclick="sendSuggestion('Parler à un humain')"> Agent</button>
      <?php endif; ?>
    </div>
    <div class="chat-input-wrap">
      <input class="chat-input" id="chatInput" type="text" placeholder="<?= $lbl_placeholder ?>"
        onkeydown="if(event.key==='Enter')sendMessage()">
      <button class="chat-send" id="chatSendBtn" onclick="sendMessage()"></button>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <?php form_validation_include($lang); ?>
<script>
function toggleFaq(el) {
  el.nextElementSibling.classList.toggle('open');
  el.querySelector('.chevron').classList.toggle('open');
}

var chatOpen = false;
var openLabel  = '<?= $lbl_open_chat ?>';
var closeLabel = '<?= $lbl_close ?>';

function toggleChat() {
  chatOpen = !chatOpen;
  document.getElementById('chatWindow').classList.toggle('open', chatOpen);
  document.getElementById('chatFab').innerHTML = chatOpen
    ? '<span class="dot"></span> ' + closeLabel
    : '<span class="dot"></span> ' + openLabel;
  if (chatOpen) document.getElementById('chatInput').focus();
}

function getTime() {
  var d = new Date();
  return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
}

function addMessage(text, type) {
  var msgs = document.getElementById('chatMessages');
  var div  = document.createElement('div');
  div.className = 'msg ' + type;
  div.innerHTML = '<div class="msg-bubble">' + text + '</div><div class="msg-time">' + getTime() + '</div>';
  msgs.appendChild(div);
  msgs.scrollTop = msgs.scrollHeight;
}

function showTyping() {
  var msgs = document.getElementById('chatMessages');
  var div  = document.createElement('div');
  div.className = 'msg bot typing'; div.id = 'typingIndicator';
  div.innerHTML = '<div class="msg-bubble"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></div>';
  msgs.appendChild(div);
  msgs.scrollTop = msgs.scrollHeight;
}

function hideTyping() { var t = document.getElementById('typingIndicator'); if (t) t.remove(); }

function sendMessage() {
  var input = document.getElementById('chatInput');
  var msg   = input.value.trim();
  if (!msg) return;
  input.value = '';
  document.getElementById('chatSuggestions').style.display = 'none';
  addMessage(msg, 'user');
  var btn = document.getElementById('chatSendBtn');
  btn.disabled = true;
  showTyping();
  var fd = new FormData();
  fd.append(' action', 'chat');
  fd.append('message', msg);
  fetch('Contact.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      hideTyping();
      btn.disabled = false;
      addMessage(data.response || '...', 'bot');
    })
    .catch(function() {
      hideTyping();
      btn.disabled = false;
      addMessage('<?= $lang==="en"?"An error occurred. Please try again.":($lang==="ar"?"حدث خطأ. يرجى المحاولة مجدداً.":($lang==="he"?"אירעה שגיאה. נסה שוב.":"Une erreur est survenue. Veuillez réessayer.")) ?>', 'bot');
    });
}

function sendSuggestion(text) {
  document.getElementById('chatInput').value = text;
  sendMessage();
}
</script>
</body>
</html>