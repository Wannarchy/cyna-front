<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/order_helpers.php';
require_once __DIR__ . '/../includes/public_layout.php';

if (! isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}

$user_id  = (int) $_SESSION['utilisateur_id'];
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

$order = null;
if ($order_id > 0) {
    try {
        $fetched = api_client()->getOrder($order_id);
        if ($fetched && (int) ($fetched['user_id'] ?? 0) === $user_id) {
            $order = $fetched;
        }
    } catch (Throwable) {
    }
}

$items = $order['items'] ?? [];
$summary = $order ? order_summary_amounts($order) : null;

$lbl_confirmed   = t('order_confirmed');
$lbl_thanks      = t('order_thanks');
$lbl_view        = t('view_orders');
$lbl_home        = t('back_home');
$lbl_order_n     = $lang==='en'?'Order':($lang==='ar'?'الطلب':($lang==='he'?'הזמנה':'Commande'));
$lbl_service     = $lang==='en'?'SaaS service':($lang==='ar'?'خدمة SaaS':($lang==='he'?'שירות SaaS':'Service SaaS'));
$lbl_cycle       = $lang==='en'?'Duration':($lang==='ar'?'المدة':($lang==='he'?'משך':'Durée'));
$lbl_price       = $lang==='en'?'Price (incl. VAT)':($lang==='ar'?'السعر (شامل الضريبة)':($lang==='he'?'מחיר (כולל מע"מ)':'Prix TTC'));
$lbl_subtotal    = $lang==='en'?'Subtotal (excl. VAT)':($lang==='ar'?'المجموع (بدون ضريبة)':($lang==='he'?'סכום ביניים (ללא מע"מ)':'Sous-total HT'));
$lbl_promo       = $lang==='en'?'Promo discount':($lang==='ar'?'خصم ترويجي':($lang==='he'?'הנחת קופון':'Réduction promo'));
$lbl_tax         = $lang==='en'?'VAT (20%)':($lang==='ar'?'ضريبة (20%)':($lang==='he'?'מע"מ (20%)':'TVA (20%)'));
$lbl_total       = $lang==='en'?'Total (incl. VAT)':($lang==='ar'?'المجموع (شامل الضريبة)':($lang==='he'?'סה"כ (כולל מע"מ)':'Total TTC'));
$lbl_billing     = $lang==='en'?'Billing address':($lang==='ar'?'عنوان الفوترة':($lang==='he'?'כתובת לחיוב':'Adresse de facturation'));
$lbl_shipping    = $lang==='en'?'Delivery address':($lang==='ar'?'عنوان التوصيل':($lang==='he'?'כתובת למשלוח':'Adresse de livraison'));
$lbl_payment     = $lang==='en'?'Payment method':($lang==='ar'?'طريقة الدفع':($lang==='he'?'אמצעי תשלום':'Moyen de paiement'));
$lbl_summary     = $lang==='en'?'Order summary':($lang==='ar'?'ملخص الطلب':($lang==='he'?'סיכום הזמנה':'Récapitulatif de commande'));
$lbl_invoice     = $lang==='en'?'📄 Download invoice':($lang==='ar'?'📄 تنزيل الفاتورة':($lang==='he'?'📄 הורד חשבונית':'📄 Télécharger la facture'));
$lbl_not_found   = $lang==='en'?'Order not found.':($lang==='ar'?'الطلب غير موجود.':($lang==='he'?'הזמנה לא נמצאה.':'Commande introuvable.'));
$lbl_email_note  = $lang==='en'?'A confirmation email has been sent to you.':($lang==='ar'?'تم إرسال بريد تأكيد إليك.':($lang==='he'?'נשלח אליך אימייל אישור.':'Un email de confirmation vous a été envoyé.'));
$lbl_secure_note = $lang==='en'?'Payment processed securely via Stripe.':($lang==='ar'?'تم الدفع بأمان عبر Stripe.':($lang==='he'?'התשלום עובד בצורה מאובטחת דרך Stripe.':'Paiement traité de façon sécurisée via Stripe.'));
?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA — <?= $lbl_confirmed ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="../assets/css/public-mobile.css" rel="stylesheet">
  <link href="../assets/css/pages/legacy-navbar.css" rel="stylesheet">
  <link href="../assets/css/pages/confirmation.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top legacy-nav">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <div class="d-flex align-items-center gap-2 ms-auto">
      <?= lang_switcher() ?>
      <a class="nav-link" href="panier.php">Panier <?= cyna_cart_badge_html() ?></a>
      <a class="nav-link" href="mon-compte.php"><?= t('nav_account') ?></a>
    </div>
  </div>
</nav>

<div class="wrap">
  <div class="confirm-card">
    <div class="confirm-head">
      <div class="check-circle">✅</div>
      <div class="confirm-title"><?= $lbl_confirmed ?></div>
      <div class="confirm-sub"><?= $lbl_thanks ?><br><?= $lbl_secure_note ?></div>
    </div>
    <div class="confirm-body">
      <?php if (! $order || ! $summary): ?>
        <p style="text-align:center;color:#f87171;padding:16px 0"><?= $lbl_not_found ?></p>
        <div class="actions">
          <a href="mes-commandes.php" class="btn-primary-grad"><?= $lbl_view ?></a>
          <a href="../index.php" class="btn-ghost"><?= $lbl_home ?></a>
        </div>
      <?php else: ?>
        <div class="order-meta">
          <span class="order-id"><?= $lbl_order_n ?> #<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></span>
          <span style="font-size:.8rem;color:#5c6378;font-family:'DM Mono',monospace"><?= date('d/m/Y H:i', strtotime($order['created_at'] ?? 'now')) ?></span>
        </div>

        <h2 class="section-title"><?= $lbl_summary ?></h2>
        <?php if ($items): ?>
        <div class="info-block" style="padding:0;overflow:hidden">
          <table class="items-table">
            <thead>
              <tr>
                <th><?= $lbl_service ?></th>
                <th><?= $lbl_cycle ?></th>
                <th style="text-align:right"><?= $lbl_price ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td style="font-weight:600"><?= htmlspecialchars($item['product']['name'] ?? ($item['name'] ?? 'Service')) ?></td>
                <td><span class="badge-cycle"><?= htmlspecialchars(order_cycle_label((string) ($item['cycle'] ?? 'monthly'), $lang)) ?></span></td>
                <td style="text-align:right;font-weight:600"><?= order_money((float) ($item['price'] ?? 0)) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div class="totals">
            <div class="total-line">
              <span><?= $lbl_subtotal ?></span>
              <span><?= order_money($summary['subtotal_ht']) ?></span>
            </div>
            <?php if ($summary['promo_discount'] > 0): ?>
            <div class="total-line promo">
              <span><?= $lbl_promo ?><?= $summary['promo_code'] ? ' ('.htmlspecialchars($summary['promo_code']).')' : '' ?></span>
              <span>- <?= order_money($summary['promo_discount']) ?></span>
            </div>
            <?php endif; ?>
            <div class="total-line">
              <span><?= $lbl_tax ?></span>
              <span><?= order_money($summary['tax_amount']) ?></span>
            </div>
            <div class="total-line grand">
              <span><?= $lbl_total ?></span>
              <span><?= order_money($summary['total']) ?></span>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-md-<?= ! empty($order['shipping_address']) ? '6' : '6' ?>">
            <h2 class="section-title"><?= $lbl_billing ?></h2>
            <div class="info-block">
              <?php if (! empty($order['billing_name']) || ! empty($order['billing_address'])): ?>
              <div class="name"><?= htmlspecialchars($order['billing_name'] ?? '') ?></div>
              <p class="text"><?= nl2br(htmlspecialchars($order['billing_address'] ?? '')) ?></p>
              <?php else: ?>
              <p class="text" style="color:rgba(255,255,255,.4);margin:0"><?= $lang==='en'?'Not provided':($lang==='ar'?'غير متوفر':($lang==='he'?'לא סופק':'Non renseignée')) ?></p>
              <?php endif; ?>
            </div>
          </div>
          <?php if (! empty($order['shipping_address'])): ?>
          <div class="col-md-6">
            <h2 class="section-title"><?= $lbl_shipping ?></h2>
            <div class="info-block">
              <div class="name"><?= htmlspecialchars($order['shipping_name'] ?? '') ?></div>
              <p class="text"><?= nl2br(htmlspecialchars($order['shipping_address'] ?? '')) ?></p>
            </div>
          </div>
          <?php endif; ?>
          <div class="col-md-<?= ! empty($order['shipping_address']) ? '12' : '6' ?>">
            <h2 class="section-title"><?= $lbl_payment ?></h2>
            <div class="info-block">
              <div class="name"><?= htmlspecialchars(order_payment_display($order)) ?></div>
              <p class="text" style="margin:0;font-size:.82rem;color:rgba(255,255,255,.45)">Stripe · SSL</p>
            </div>
          </div>
        </div>

        <a href="mes-commandes.php?facture=<?= (int) $order['id'] ?>" target="_blank" class="btn-invoice"><?= $lbl_invoice ?></a>

        <div class="actions">
          <a href="mes-commandes.php?order=<?= (int) $order['id'] ?>" class="btn-primary-grad"><?= $lbl_view ?></a>
          <a href="../index.php" class="btn-ghost"><?= $lbl_home ?></a>
        </div>
        <div class="email-note"><?= $lbl_email_note ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<footer>
  <a href="mention_legales.php"><?= t('legal') ?></a>
  <a href="Cgu.php"><?= t('cgu') ?></a>
  <a href="Contact.php"><?= t('contact') ?></a>
  <span><?= t('copyright') ?></span>
</footer>
</body>
</html>
