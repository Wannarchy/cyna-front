<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/public_layout.php';

if (!isset($_SESSION['utilisateur_id'])) { header('Location: connexion.php'); exit; }
$user_id = (int)$_SESSION['utilisateur_id'];

$user = [
    'prenom' => $_SESSION['utilisateur_prenom'] ?? '',
    'nom' => $_SESSION['utilisateur_nom'] ?? '',
    'email' => $_SESSION['utilisateur_email'] ?? '',
];

// Téléchargement facture PDF
$download_id = isset($_GET['facture']) ? (int)$_GET['facture'] : 0;
if ($download_id > 0) {
    $order = api_client()->getOrder($download_id);
    if ($order && (int) ($order['user_id'] ?? 0) === $user_id) {
        $items = $order['items'] ?? [];
        header('Content-Type: text/html; charset=utf-8');
        $lbl_invoice    = $lang==='en'?'INVOICE':($lang==='ar'?'فاتورة':($lang==='he'?'חשבונית':'FACTURE'));
        $lbl_bill_to    = $lang==='en'?'Bill to':($lang==='ar'?'إلى':($lang==='he'?'לחיוב':'Facturer à'));
        $lbl_details    = $lang==='en'?'Details':($lang==='ar'?'التفاصيل':($lang==='he'?'פרטים':'Détails'));
        $lbl_service    = $lang==='en'?'Service':($lang==='ar'?'الخدمة':($lang==='he'?'שירות':'Service'));
        $lbl_sub_type   = $lang==='en'?'Subscription':($lang==='ar'?'الاشتراك':($lang==='he'?'מנוי':'Abonnement'));
        $lbl_price_h    = $lang==='en'?'Price':($lang==='ar'?'السعر':($lang==='he'?'מחיר':'Prix'));
        $lbl_total_h    = $lang==='en'?'TOTAL':($lang==='ar'?'المجموع':($lang==='he'?'סה"כ':'TOTAL TTC'));
        $lbl_order_n    = $lang==='en'?'Order':($lang==='ar'?'الطلب':($lang==='he'?'הזמנה':'Commande'));
        $lbl_print      = $lang==='en'?'Print / Save PDF':($lang==='ar'?' طباعة / حفظ PDF':($lang==='he'?' הדפס / שמור PDF':'Imprimer / Sauvegarder PDF'));
        $lbl_ready      = $lang==='en'?'Invoice ready to print':($lang==='ar'?'الفاتورة جاهزة للطباعة':($lang==='he'?'החשבונית מוכנה להדפסה':'Facture prête à imprimer'));
        echo '<!doctype html><html lang="'.$lang.'"><head><meta charset="utf-8"><title>'.$lbl_invoice.' #'.(int)$order['id'].'</title><style> body{font-family:Arial,sans-serif;margin:40px auto;color:#222;max-width:800px;}
        .header{display:flex;justify-content:space-between;margin-bottom:40px;}
        .logo{font-size:1.8rem;font-weight:900;color:#1a2980;}
        .inv-info{text-align:right;font-size:.9rem;color:#555;}
        h2{color:#1a2980;border-bottom:2px solid #1a2980;padding-bottom:8px;}
        table{width:100%;border-collapse:collapse;margin-top:16px;}
        th{background:#1a2980;color:#fff;padding:10px;text-align:left;font-size:.85rem;}
        td{padding:10px;border-bottom:1px solid #eee;font-size:.88rem;}
        .total-row td{font-weight:700;background:#f8f9fa;font-size:1rem;}
        .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0;}
        .info-box{background:#f8f9fa;padding:16px;border-radius:8px;}
        .info-box h4{font-size:.8rem;color:#888;text-transform:uppercase;margin-bottom:8px;}
        .footer-note{margin-top:40px;padding-top:16px;border-top:1px solid #eee;font-size:.78rem;color:#888;text-align:center;}
        @media print{.no-print{display:none;}}
        </style></head><body>';
        echo '<div class="no-print" style="background:#1a2980;color:#fff;padding:12px 20px;border-radius:8px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center">';
        echo '<span> '.$lbl_ready.'</span>';
        echo '<button onclick="window.print()" style="background:#26d0ce;color:#222;border:none;border-radius:6px;padding:8px 18px;font-weight:700;cursor:pointer">'.$lbl_print.'</button>';
        echo '</div>';
        echo '<div class="header"><div><div class="logo">CYNA</div><div style="font-size:.8rem;color:#888;margin-top:4px">CYNA-IT — 10 Rue de Penthièvre, 75008 Paris<br>SIRET : 91371103200015</div></div>';
        echo '<div class="inv-info"><strong>'.$lbl_invoice.'</strong><br>N° FAC-'.str_pad($order['id'],6,'0',STR_PAD_LEFT).'<br>'.($lang==='en'?'Date':($lang==='ar'?'التاريخ':($lang==='he'?'תאריך':'Date'))).' : '.date('d/m/Y',strtotime($order['created_at'])).'</div></div>';
        echo '<div class="info-grid">';
        echo '<div class="info-box"><h4>'.$lbl_bill_to.'</h4><strong>'.htmlspecialchars($order['billing_name']??($user['prenom'].' '.$user['nom'])).'</strong><br>'.htmlspecialchars($user['email']).'<br>'.htmlspecialchars($order['billing_address']??'').'</div>';
        echo '<div class="info-box"><h4>'.$lbl_details.'</h4>'.$lbl_order_n.' #'.(int)$order['id'].'<br>'.($lang==='en'?'Date':($lang==='ar'?'التاريخ':($lang==='he'?'תאריך':'Date'))).' : '.date('d/m/Y H:i',strtotime($order['created_at'])).'</div>';
        echo '</div>';
        echo '<h2>'.($lang==='en'?'Order details':($lang==='ar'?'تفاصيل الطلب':($lang==='he'?'פרטי ההזמנה':'Détail de la commande'))).'</h2>';
        echo '<table><thead><tr><th>'.$lbl_service.'</th><th>'.$lbl_sub_type.'</th><th>'.$lbl_price_h.'</th></tr></thead><tbody>';
        foreach ($items as $item) {
            $cycle = ucfirst($item['cycle'] ?? 'mensuel');
            $price = (float) ($item['price'] ?? 0);
            $name = $item['product'][' name'] ?? ($item[' name'] ?? 'Service');
            echo '<tr><td>'.htmlspecialchars($name).'</td><td>'.$cycle.'</td><td>'.number_format($price,2,',','.').' €</td></tr>';
        }
        echo '<tr class="total-row"><td colspan="2" style="text-align:right">'.$lbl_total_h.'</td><td>'.number_format((float)$order['total'],2,',','.').' €</td></tr>';
        echo '</tbody></table>';
        echo '<div class="footer-note">'.($lang==='en'?'Thank you for your trust.':($lang==='ar'?'شكراً لثقتكم.':($lang==='he'?'תודה על אמונך.':'Merci de votre confiance.'))).'contact@cyna-it.fr — www.cyna-it.fr</div>';
        echo '</body></html>';
        exit;
    }
}

$detail_id = isset($_GET['order']) ? (int)$_GET['order'] : 0;

try {
    $apiOrders = api_client()->getOrders();
} catch (Throwable) {
    $apiOrders = [];
}

$all_orders = array_map(function (array $order) use ($user) {
    return [
        'id' => $order['id'],
        'total' => $order['total'],
        'created_at' => $order['created_at'],
        'billing_name' => $order['billing_name'] ?? ($user['prenom'].' '.$user['nom']),
        'billing_address' => $order['billing_address'] ?? '',
        'card_last4' => $order['card_last4'] ?? null,
        'year' => date('Y', strtotime($order['created_at'] ?? 'now')),
        'items' => $order['items'] ?? [],
    ];
}, $apiOrders);

$filter_year = isset($_GET['annee']) ? (int)$_GET['annee'] : 0;
$filter_q    = trim($_GET['q'] ?? '');
$years = array_unique(array_column($all_orders, 'year'));
rsort($years);

$filtered = array_filter($all_orders, function($o) use ($filter_year, $filter_q) {
    if ($filter_year > 0 && (int)$o['year'] !== $filter_year) return false;
    if ($filter_q !== '') {
        $search = strtolower($filter_q);
        if (strpos(strtolower($o['billing_name']??''), $search) === false &&
            strpos((string)$o['id'], $filter_q) === false) return false;
    }
    return true;
});

$by_year = [];
foreach ($filtered as $o) { $by_year[$o['year']][] = $o; }
krsort($by_year);

$order_items_cache = [];
foreach ($filtered as $o) {
    $order_items_cache[$o['id']] = array_map(function ($item) {
        return [
            ' name' => $item['product'][' name'] ?? 'Service',
            'cycle' => $item['cycle'] ?? 'monthly',
            'price' => $item['price'] ?? 0,
        ];
    }, $o['items'] ?? []);
}

// Labels
$lbl_search_ph = $lang==='en'?'Search an order...':($lang==='ar'?' البحث عن طلب...':($lang==='he'?' חפש הזמנה...':'Rechercher une commande...'));
$lbl_filter    = $lang==='en'?'Filter':($lang==='ar'?'تصفية':($lang==='he'?'סנן':'Filtrer'));
$lbl_reset     = $lang==='en'?'Reset':($lang==='ar'?' إعادة':($lang==='he'?' אפס':'Reset'));
$lbl_all       = $lang==='en'?'All':($lang==='ar'?'الكل':($lang==='he'?'הכל':'Toutes'));
$lbl_no_orders = $lang==='en'?'No orders yet.':($lang==='ar'?'لا توجد طلبات حتى الآن.':($lang==='he'?'אין הזמנות עדיין.':'Aucune commande pour l\'instant.'));
$lbl_no_results= $lang==='en'?'No orders match your search.':($lang==='ar'?'لا توجد طلبات تطابق بحثك.':($lang==='he'?'אין הזמנות התואמות לחיפוש שלך.':'Aucune commande ne correspond à votre recherche.'));
$lbl_orders_ct = $lang==='en'?'order(s)':($lang==='ar'?'طلب(ات)':($lang==='he'?'הזמנה(ות)':'commande(s)'));
$lbl_billing   = $lang==='en'?'Billing':($lang==='ar'?'الفوترة':($lang==='he'?'חיוב':'Facturation'));
$lbl_payment   = $lang==='en'?'Payment':($lang==='ar'?'الدفع':($lang==='he'?'תשלום':'Paiement'));
$lbl_service   = $lang==='en'?'Service':($lang==='ar'?'الخدمة':($lang==='he'?'שירות':'Service'));
$lbl_sub_t     = $lang==='en'?'Subscription':($lang==='ar'?'الاشتراك':($lang==='he'?'מנוי':'Abonnement'));
$lbl_price_h   = $lang==='en'?'Price':($lang==='ar'?'السعر':($lang==='he'?'מחיר':'Prix'));
$lbl_total_h   = $lang==='en'?'Total':($lang==='ar'?'المجموع':($lang==='he'?'סה"כ':'Total'));
$lbl_download  = $lang==='en'?'Download invoice':($lang==='ar'?' تنزيل الفاتورة':($lang==='he'?' הורד חשבונית':'Télécharger la facture'));
$lbl_secure_pay= $lang==='en'?'Secure payment':($lang==='ar'?'دفع آمن':($lang==='he'?'תשלום מאובטח':'Paiement sécurisé'));
$lbl_discover  = $lang==='en'?'Discover our services →':($lang==='ar'?'اكتشف خدماتنا →':($lang==='he'?'גלה את השירותים שלנו →':'Découvrir nos services →'));
?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA — <?= t('my_orders') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="../assets/css/public-mobile.css" rel="stylesheet">
  <link href="../assets/css/pages/legacy-navbar.css" rel="stylesheet">
  <link href="../assets/css/pages/compte-espace.css" rel="stylesheet">
  <link href="../assets/css/pages/mes-commandes.css" rel="stylesheet">
</head>
<body>
  <nav class="navbar navbar-expand-lg sticky-top legacy-nav">
    <div class="container-fluid px-3 px-lg-4">
      <a class="navbar-brand" href="../index.php">CYNA</a>
      <div class="d-flex align-items-center gap-2 ms-auto">
        <?= lang_switcher() ?>
        <a class="nav-link" href="panier.php">
          Panier <?= cyna_cart_badge_html() ?>
        </a>
        <a class="nav-link" href="deconnexion.php"><?= t('nav_logout') ?></a>
      </div>
    </div>
  </nav>
  <div class="wrap">
    <aside class="sb">
      <div class="u-card">
        <div class="u-av"><?= strtoupper(substr($user['prenom']??'U',0,1)) ?></div>
        <div class="u-name"><?= htmlspecialchars(($user['prenom']??'').' '.($user['nom']??'')) ?></div>
        <div class="u-email"><?= htmlspecialchars($user['email']??'') ?></div>
      </div>
      <nav class="sb-nav">
        <a href="mon-compte.php?tab=profil"><?= t('profile') ?></a>
        <a href="mon-compte.php?tab=securite"><?= t('security') ?></a>
        <a href="adresses.php"><?= t('my_addresses') ?></a>
        <a href="paiements.php"><?= t('my_payments') ?></a>
        <a href="mes-abonnements.php"><?= t('my_subscriptions') ?></a>
        <a href="mes-commandes.php" class="active"><?= t('my_orders') ?></a>
        <a href="deconnexion.php" style="color:rgba(239,68,68,.6)"><?= t('nav_logout') ?></a>
      </nav>
    </aside>
    <main class="main">
      <form method="GET" action="mes-commandes.php">
        <div class="filters">
          <input type="search" class="form-ctrl" name="q" placeholder="<?= $lbl_search_ph ?>" value="<?= htmlspecialchars($filter_q) ?>" style="flex:1;min-width:200px">
          <button type="submit" style="background:var(--grad);color:#fff;border:none;border-radius:9px;padding:8px 16px;font-size:.83rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif"><?= $lbl_filter ?></button>
          <?php if ($filter_q || $filter_year): ?>
          <a href="mes-commandes.php" style="background:transparent;color:#8b92a8;border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 14px;font-size:.8rem;text-decoration:none"><?= $lbl_reset ?></a>
          <?php endif; ?>
        </div>
        <div class="d-flex gap-2 flex-wrap mb-4">
          <a href="mes-commandes.php<?= $filter_q ? '?q='.urlencode($filter_q) : '' ?>" class="year-pill <?= !$filter_year ? 'active' : '' ?>"><?= $lbl_all ?></a>
          <?php foreach ($years as $y): ?>
          <a href="mes-commandes.php?annee=<?= $y ?><?= $filter_q ? '&q='.urlencode($filter_q) : '' ?>" class="year-pill <?= $filter_year===$y ? 'active' : '' ?>"><?= $y ?></a>
          <?php endforeach; ?>
        </div>
      </form>
      <?php if (empty($filtered)): ?>
      <div class="no-orders">
        <div class="icon"></div>
        <p style="font-size:.88rem"><?= $filter_q || $filter_year ? $lbl_no_results : $lbl_no_orders ?></p>
        <?php if (!$filter_q && !$filter_year): ?>
        <a href="catalogue.php" style="color:var(--cyan);font-size:.85rem;text-decoration:none"><?= $lbl_discover ?></a>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <?php foreach ($by_year as $year => $year_orders): ?>
      <div class="year-group">
        <div class="year-label"><?= $year ?> <span style="font-size:.75rem;font-weight:500;color:#5c6378"><?= count($year_orders) ?> <?= $lbl_orders_ct ?></span></div>
        <?php foreach ($year_orders as $o):
            $items = $order_items_cache[$o['id']] ?? [];
            $services = implode(', ', array_slice(array_map(function($i) { return htmlspecialchars($i[' name']??''); }, $items), 0, 3));
            if (count($items) > 3) $services .= ' +' . (count($items)-3);
        ?>
        <div class="order-row" id="row-<?= (int)$o['id'] ?>" onclick="toggleOrder(<?= (int)$o['id'] ?>)">
          <div class="order-top">
            <span class="order-id">#<?= str_pad($o['id'],6,'0',STR_PAD_LEFT) ?></span>
            <div class="flex-grow-1">
              <div class="order-name"><?= htmlspecialchars($o['billing_name'] ?? $user['prenom'].' '.$user['nom']) ?></div>
              <?php if ($services): ?><div class="order-services"><?= $services ?></div><?php endif; ?>
            </div>
            <span class="order-date"><?= date('d/m/Y', strtotime($o['created_at'])) ?></span>
            <span class="order-total"><?= number_format((float)$o['total'],2,',',' ') ?> €</span>
            <span class="order-chevron">▶</span>
          </div>
          <div class="order-detail" id="detail-<?= (int)$o['id'] ?>">
            <div class="detail-grid">
              <div class="detail-box">
                <div class="detail-box-label"><?= $lbl_billing ?></div>
                <div class="detail-box-val">
                  <?= htmlspecialchars($o['billing_name'] ?? '') ?><br>
                  <?php if (!empty($o['billing_address'])): ?>
                  <?= nl2br(htmlspecialchars($o['billing_address'])) ?>
                  <?php endif; ?>
                </div>
              </div>
              <div class="detail-box">
                <div class="detail-box-label"><?= $lbl_payment ?></div>
                <div class="detail-box-val">
                  <?php $card = $o['card_last4'] ?? null; ?>
                  <?php if ($card): ?>
                  •••• •••• •••• <?= htmlspecialchars($card) ?>
                  <?php else: ?><?= $lbl_secure_pay ?><?php endif; ?><br>
                  <span style="font-size:.75rem;color:#5c6378"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></span>
                </div>
              </div>
            </div>
            <?php if ($items): ?>
            <table class="items-table">
              <thead>
                <tr>
                  <th><?= $lbl_service ?></th>
                  <th><?= $lbl_sub_t ?></th>
                  <th style="text-align:right"><?= $lbl_price_h ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                  <td><?= htmlspecialchars($item[' name'] ?? 'Service') ?></td>
                  <td><span style="font-size:.72rem;padding:2px 8px;border-radius:20px;background:rgba(38,208,206,.1);color:#26d0ce;border:1px solid rgba(38,208,206,.2)"><?= ucfirst($item['cycle'] ?? 'mensuel') ?></span></td>
                  <td style="text-align:right;font-weight:600"><?= number_format((float)($item['price'] ?? 0),2,',',' ') ?> €</td>
                </tr>
                <?php endforeach; ?>
                <tr style="border-top:1px solid rgba(255,255,255,.1)">
                  <td colspan="2" style="text-align:right;font-weight:700;color:#fff;padding-top:12px"><?= $lbl_total_h ?></td>
                  <td style="text-align:right;font-weight:700;color:#fff;padding-top:12px"><?= number_format((float)$o['total'],2,',',' ') ?> €</td>
                </tr>
              </tbody>
            </table>
            <?php endif; ?>
            <div style="display:flex;gap:10px;margin-top:4px" onclick="event.stopPropagation()">
              <a href="mes-commandes.php?facture=<?= (int)$o['id'] ?>" target="_blank" class="btn-facture"><?= $lbl_download ?></a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </main>
  </div>
  <footer>
    <a href="Cgu.php"><?= t('cgu') ?></a>
    <a href="mention_legales.php"><?= t('legal') ?></a>
    <a href="Contact.php"><?= t('contact') ?></a>
    <span><?= t('copyright') ?></span>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  function toggleOrder(id) {
    document.getElementById('row-' + id).classList.toggle('open');
  }
  <?php if ($detail_id > 0): ?>
  document.addEventListener('DOMContentLoaded', function() {
    toggleOrder(<?= (int)$detail_id ?>);
    var el = document.getElementById('row-<?= (int)$detail_id ?>');
    if (el) el.scrollIntoView({behavior:'smooth',block:'center'});
  });
  <?php endif; ?>
  </script>
</body>
</html>