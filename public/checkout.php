<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
$est_connecte = isset($_SESSION['utilisateur_id']);

require_once __DIR__ . '/../includes/cart_repository.php';
require_once __DIR__ . '/../includes/address_helpers.php';
require_once __DIR__ . '/../includes/form_validation.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}

$cart  = $_SESSION['cart'] ?? [];
$items = cart_get_products($connexion, $cart);
$total = cart_total($items);

if (count($items) === 0) {
    header('Location: panier.php');
    exit;
}

$user = [
    'prenom' => $_SESSION['utilisateur_prenom'] ?? '',
    'nom' => $_SESSION['utilisateur_nom'] ?? '',
    'email' => $_SESSION['utilisateur_email'] ?? '',
    'is_admin' => $_SESSION['is_admin'] ?? 0,
];
$_SESSION['is_admin'] = (int) ($user['is_admin'] ?? 0);

$default_addr = null;
$default_ship = null;
$saved_cards = [];
$addresses = [];
$billing_addresses = [];
$shipping_addresses = [];

try {
    $addresses = api_client()->getAddresses();
    $billing_addresses = address_filter_for_billing($addresses);
    $shipping_addresses = address_filter_for_shipping($addresses);
    foreach ($billing_addresses as $address) {
        if (! empty($address['is_default'])) {
            $default_addr = $address;
            break;
        }
    }
    if (! $default_addr && ! empty($billing_addresses[0])) {
        $default_addr = $billing_addresses[0];
    }
    $default_ship = address_default_shipping($shipping_addresses);
} catch (Throwable) {
    $billing_addresses = [];
    $shipping_addresses = [];
}

try {
    $saved_cards = api_client()->getPaymentMethods();
} catch (Throwable) {
}

try {
    $billingConfig = api_client()->getBillingConfig();
    $stripePublishableKey = $billingConfig['stripe_key'] ?? '';
} catch (Throwable) {
    require_once __DIR__ . '/../config/stripe_config.php';
    $stripePublishableKey = STRIPE_PUBLISHABLE_KEY;
}

$nb_panier = cart_session_count();
$tva       = $total * 0.20;
$needs_shipping = cart_requires_shipping($items);

$selected_address_id = (int) ($_POST['address_id'] ?? ($default_addr['id'] ?? 0));
$selected_shipping_id = (int) ($_POST['shipping_address_id'] ?? ($default_ship['id'] ?? 0));
$shipping_same_as_billing = ! $needs_shipping || ! empty($_POST['shipping_same_as_billing']);

$addr_form = address_from_input([]);
$ship_form = address_from_input([], 'shipping_');

if (! empty($_POST['adresse1']) || ! empty($_POST['prenom'])) {
    $addr_form = address_from_input($_POST);
} elseif ($selected_address_id > 0) {
    foreach ($billing_addresses as $address) {
        if ((int) ($address['id'] ?? 0) === $selected_address_id) {
            $addr_form = address_from_input($address);
            break;
        }
    }
} elseif ($default_addr) {
    $addr_form = address_from_input($default_addr);
} else {
    $addr_form['prenom'] = $user['prenom'];
    $addr_form['nom'] = $user['nom'];
}

if ($needs_shipping && ! $shipping_same_as_billing) {
    if (! empty($_POST['shipping_adresse1']) || ! empty($_POST['shipping_prenom'])) {
        $ship_form = address_from_input($_POST, 'shipping_');
    } elseif ($selected_shipping_id > 0) {
        foreach ($shipping_addresses as $address) {
            if ((int) ($address['id'] ?? 0) === $selected_shipping_id) {
                $ship_form = address_from_input($address);
                break;
            }
        }
    } elseif ($default_ship) {
        $ship_form = address_from_input($default_ship);
    }
} elseif ($needs_shipping) {
    $ship_form = $addr_form;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Finaliser la commande</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="../assets/css/public-mobile.css" rel="stylesheet">
  <link href="../assets/css/pages/legacy-navbar.css" rel="stylesheet">
  <link href="../assets/css/pages/checkout.css" rel="stylesheet">
  <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
  <!-- NAVBAR -->
  <nav class="navbar sticky-top legacy-nav navbar--tall">
    <div class="container">
      <a class="navbar-brand" href="../index.php">CYNA</a>
      <div class="d-flex align-items-center gap-2 ms-auto">
        <a href="panier.php" class="nav-link-plain">← Panier</a>
        <?php if ($est_connecte): ?>
        <a href="mon-compte.php" class="nav-link-plain">Mon compte</a>
        <a href="deconnexion.php" class="nav-link-plain">Déconnexion</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <div class="page-wrap">
    <!-- ÉTAPES -->
    <div class="steps">
      <div class="step done">
        <div class="step-num"></div>
        <div class="step-label">Panier</div>
      </div>
      <div class="step-sep"></div>
      <div class="step active">
        <div class="step-num">2</div>
        <div class="step-label">Facturation</div>
      </div>
      <div class="step-sep"></div>
      <div class="step inactive">
        <div class="step-num">3</div>
        <div class="step-label">Confirmation</div>
      </div>
    </div>

    <div class="page-title">Finaliser la commande</div>
    <div class="page-sub"><?= $nb_panier ?> service(s) · Total : <?= number_format($total, 2, ',', ' ') ?> €</div>

    <?php
  // Afficher les erreurs Stripe/paiement
  if (!empty($_SESSION['checkout_errors'])) {
      echo '<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:12px;padding:16px 20px;margin-bottom:20px">';
      echo '<div style="font-size:.9rem;font-weight:700;color:#f87171;margin-bottom:6px"> Paiement refusé</div>';
      foreach ($_SESSION['checkout_errors'] as $err) {
          echo '<div style="font-size:.84rem;color:rgba(239,68,68,.8)">' . htmlspecialchars($err) . '</div>';
      }
      echo '</div>';
      unset($_SESSION['checkout_errors']);
  }
    ?>

    <form action="checkout_submit.php" method="POST" data-cyna-validate="checkout" data-cyna-manual="1">
      <div class="row g-4">
        <!-- GAUCHE : formulaire -->
        <div class="col-12 col-lg-7">
          <!-- Adresse de facturation -->
          <div class="form-section">
            <div class="form-section-head">
              <div class="form-section-icon"></div>
              <div>
                <div class="form-section-title">Adresse de facturation</div>
                <div class="form-section-sub">Ces informations apparaîtront sur votre facture</div>
              </div>
            </div>
            <div class="form-section-body">
              <?php if ($billing_addresses): ?>
              <div class="row g-3 mb-3">
                <div class="col-12">
                  <label class="field-label">Adresse enregistrée</label>
                  <select name="address_id" class="field-input">
                    <?php foreach ($billing_addresses as $address): ?>
                    <option value="<?= (int) ($address['id'] ?? 0) ?>"
                      <?= $selected_address_id === (int) ($address['id'] ?? 0) ? 'selected' : '' ?>>
                      <?= htmlspecialchars(($address['label'] ?? 'Adresse').' — '.address_billing_name($address).', '.$address['ville']) ?>
                      <?= ! empty($address['is_default']) ? ' (par défaut)' : '' ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                  <div style="margin-top:8px">
                    <a href="adresses.php" class="addr-change">Gérer mes adresses →</a>
                  </div>
                </div>
              </div>
              <?php endif; ?>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="field-label">Prénom *</label>
                  <input type="text" name="prenom" class="field-input" required
                    value="<?= htmlspecialchars($addr_form['prenom']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="field-label">Nom *</label>
                  <input type="text" name="nom" class="field-input" required
                    value="<?= htmlspecialchars($addr_form['nom']) ?>">
                </div>
                <div class="col-12">
                  <label class="field-label">Adresse ligne 1 *</label>
                  <input type="text" name="adresse1" class="field-input" required
                    placeholder="Numéro et rue"
                    value="<?= htmlspecialchars($addr_form['adresse1']) ?>">
                </div>
                <div class="col-12">
                  <label class="field-label">Adresse ligne 2</label>
                  <input type="text" name="adresse2" class="field-input"
                    placeholder="Complément, bâtiment, étage…"
                    value="<?= htmlspecialchars($addr_form['adresse2']) ?>">
                </div>
                <div class="col-md-4">
                  <label class="field-label">Code postal *</label>
                  <input type="text" name="code_postal" class="field-input" required
                    value="<?= htmlspecialchars($addr_form['code_postal']) ?>">
                </div>
                <div class="col-md-5">
                  <label class="field-label">Ville *</label>
                  <input type="text" name="ville" class="field-input" required
                    value="<?= htmlspecialchars($addr_form['ville']) ?>">
                </div>
                <div class="col-md-3">
                  <label class="field-label">Région</label>
                  <input type="text" name="region" class="field-input"
                    value="<?= htmlspecialchars($addr_form['region']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="field-label">Pays *</label>
                  <select name="pays" class="field-input" required>
                    <?php foreach (address_country_options() as $p): ?>
                    <option <?= $addr_form['pays'] === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="field-label">Téléphone</label>
                  <input type="tel" name="telephone" class="field-input"
                    value="<?= htmlspecialchars($addr_form['telephone']) ?>">
                </div>
              </div>
            </div>
          </div>

          <?php if ($needs_shipping): ?>
          <div class="form-section" id="shipping-section">
            <div class="form-section-head">
              <div class="form-section-icon"></div>
              <div>
                <div class="form-section-title">Adresse de livraison</div>
                <div class="form-section-sub">Requis pour les produits physiques de votre panier</div>
              </div>
            </div>
            <div class="form-section-body">
              <label style="display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:.85rem;color:rgba(255,255,255,.7);cursor:pointer">
                <input type="checkbox" name="shipping_same_as_billing" id="shipping_same_as_billing" value="1"
                  <?= $shipping_same_as_billing ? 'checked' : '' ?> onchange="toggleShippingFields()">
                Identique à l'adresse de facturation
              </label>
              <div id="shipping-fields" style="<?= $shipping_same_as_billing ? 'display:none' : '' ?>">
                <?php if ($shipping_addresses): ?>
                <div class="row g-3 mb-3">
                  <div class="col-12">
                    <label class="field-label">Adresse de livraison enregistrée</label>
                    <select name="shipping_address_id" class="field-input" id="shipping_address_id">
                      <?php foreach ($shipping_addresses as $address): ?>
                      <option value="<?= (int) ($address['id'] ?? 0) ?>"
                        <?= $selected_shipping_id === (int) ($address['id'] ?? 0) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($address['label'] ?? 'Adresse').' — '.address_billing_name($address).', '.$address['ville']) ?>
                        <?= ! empty($address['is_default_shipping']) ? ' (livraison par défaut)' : '' ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <?php endif; ?>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="field-label">Prénom *</label>
                    <input type="text" name="shipping_prenom" class="field-input shipping-field"
                      value="<?= htmlspecialchars($ship_form['prenom']) ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="field-label">Nom *</label>
                    <input type="text" name="shipping_nom" class="field-input shipping-field"
                      value="<?= htmlspecialchars($ship_form['nom']) ?>">
                  </div>
                  <div class="col-12">
                    <label class="field-label">Adresse ligne 1 *</label>
                    <input type="text" name="shipping_adresse1" class="field-input shipping-field"
                      value="<?= htmlspecialchars($ship_form['adresse1']) ?>">
                  </div>
                  <div class="col-12">
                    <label class="field-label">Adresse ligne 2</label>
                    <input type="text" name="shipping_adresse2" class="field-input shipping-field"
                      value="<?= htmlspecialchars($ship_form['adresse2']) ?>">
                  </div>
                  <div class="col-md-4">
                    <label class="field-label">Code postal *</label>
                    <input type="text" name="shipping_code_postal" class="field-input shipping-field"
                      value="<?= htmlspecialchars($ship_form['code_postal']) ?>">
                  </div>
                  <div class="col-md-5">
                    <label class="field-label">Ville *</label>
                    <input type="text" name="shipping_ville" class="field-input shipping-field"
                      value="<?= htmlspecialchars($ship_form['ville']) ?>">
                  </div>
                  <div class="col-md-3">
                    <label class="field-label">Région</label>
                    <input type="text" name="shipping_region" class="field-input shipping-field"
                      value="<?= htmlspecialchars($ship_form['region']) ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="field-label">Pays *</label>
                    <select name="shipping_pays" class="field-input shipping-field">
                      <?php foreach (address_country_options() as $p): ?>
                      <option <?= $ship_form['pays'] === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="field-label">Téléphone</label>
                    <input type="tel" name="shipping_telephone" class="field-input shipping-field"
                      value="<?= htmlspecialchars($ship_form['telephone']) ?>">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Informations de paiement -->
          <div class="form-section">
            <div class="form-section-head">
              <div class="form-section-icon"></div>
              <div>
                <div class="form-section-title">Informations de paiement</div>
                <div class="form-section-sub">Connexion sécurisée SSL — tokenisation Stripe</div>
              </div>
            </div>
            <div class="form-section-body">
              <?php if ($saved_cards): ?>
              <!-- CARTES ENREGISTRÉES -->
              <div style="margin-bottom:18px">
                <label class="field-label" style="margin-bottom:10px">Choisir une carte enregistrée</label>
                <div style="display:flex;flex-direction:column;gap:8px" id="saved-cards-list">
                  <?php foreach ($saved_cards as $card):
                  $is_exp = ($card['exp_year'] < (int)date('Y')) ||
                            ($card['exp_year'] == (int)date('Y') && $card['exp_month'] < (int)date('m'));
                  ?>
                  <label class="saved-card-label <?= $is_exp ? 'expired' : '' ?>"
                    style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:11px;padding:12px 16px;cursor:<?= $is_exp ? 'not-allowed' : 'pointer' ?>;transition:border-color .15s">
                    <input type="radio" name="saved_card_id" value="<?= htmlspecialchars($card['id']) ?>"
                      <?= $card['is_default'] && !$is_exp ? 'checked' : '' ?> <?= $is_exp ? 'disabled' : '' ?> onchange="useSavedCard(true)"
                      style="accent-color:#26d0ce;width:16px;height:16px;flex-shrink:0">
                    <div style="flex:1;min-width:0">
                      <div style="font-size:.87rem;font-weight:600;color:<?= $is_exp ? '#5c6378' : '#fff' ?>">
                        <?= htmlspecialchars($card['card_brand']) ?> •••• <?= htmlspecialchars($card['card_last4']) ?>
                        <?php if ($card['is_default']): ?>
                        <span style="font-size:.65rem;background:rgba(38,208,206,.12);color:#26d0ce;border:1px solid rgba(38,208,206,.2);border-radius:20px;padding:1px 7px;margin-left:6px">Par défaut</span>
                        <?php endif; ?>
                        <?php if ($is_exp): ?>
                        <span style="font-size:.65rem;background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);border-radius:20px;padding:1px 7px;margin-left:6px">Expirée</span>
                        <?php endif; ?>
                      </div>
                      <div style="font-size:.73rem;color:#5c6378;margin-top:2px">
                        Expire <?= str_pad((string) ($card['exp_month'] ?? ''),2,'0',STR_PAD_LEFT) ?>/<?= htmlspecialchars((string) ($card['exp_year'] ?? '')) ?>
                      </div>
                    </div>
                    <div style="font-size:1.3rem"><?= $card['card_brand']==='Mastercard'?'':'' ?></div>
                  </label>
                  <?php endforeach; ?>
                  <!-- Option nouvelle carte -->
                  <label style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:11px;padding:12px 16px;cursor:pointer;transition:border-color .15s">
                    <input type="radio" name="saved_card_id" value="new"
                      onchange="useSavedCard(false)"
                      style="accent-color:#26d0ce;width:16px;height:16px;flex-shrink:0">
                    <div style="font-size:.87rem;font-weight:600;color:#e8eaf2">+ Utiliser une nouvelle carte</div>
                  </label>
                </div>
              </div>
              <!-- NOUVELLE CARTE (masquée si carte enregistrée sélectionnée) -->
              <div id="new-card-section" style="display:none">
              <?php else: ?>
              <div id="new-card-section">
              <?php endif; ?>
              <div class="row g-3">
                <div class="col-12">
                  <label class="field-label">Nom sur la carte *</label>
                  <input type="text" name="card_holder" id="card_holder" class="field-input"
                    placeholder="NOM PRÉNOM" style="text-transform:uppercase"
                    value="<?= htmlspecialchars(strtoupper($user['prenom'].' '.$user['nom'])) ?>">
                </div>
                <div class="col-12">
                  <label class="field-label">Numéro de carte *</label>
                  <div id="stripe-card-number" class="field-input" style="padding:11px 14px;min-height:44px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;transition:border-color .15s"></div>
                </div>
                <div class="col-6">
                  <label class="field-label">Date d'expiration *</label>
                  <div id="stripe-card-expiry" class="field-input" style="padding:11px 14px;min-height:44px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;transition:border-color .15s"></div>
                </div>
                <div class="col-6">
                  <label class="field-label">CVV *</label>
                  <div id="stripe-card-cvc" class="field-input" style="padding:11px 14px;min-height:44px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;transition:border-color .15s"></div>
                </div>
              </div>
              <input type="hidden" name="payment_method" id="payment_method">
              <input type="hidden" name="stripe_token" id="stripe_token">
              <input type="hidden" name="card_last4" id="card_last4">
              <div id="stripe-error" style="display:none;margin-top:12px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:10px 14px;font-size:.82rem;color:#f87171"></div>
              </div><!-- /new-card-section -->
              <div class="notice" style="margin-top:14px">
                Vos données bancaires sont tokenisées par Stripe et ne transitent jamais par nos serveurs.
              </div>
            </div>
          </div>
        </div>

        <!-- DROITE : récap -->
        <div class="col-12 col-lg-5">
          <div class="recap">
            <div class="recap-title">Récapitulatif de commande</div>
            <?php foreach ($items as $it): ?>
            <div class="recap-item">
              <div>
                <div class="recap-item-name"><?= htmlspecialchars($it['name']) ?> × <?= (int) ($it['qty'] ?? 1) ?></div>
                <div class="recap-item-cycle">
                  <?= $it['cycle'] === 'yearly' ? 'Abonnement annuel' : 'Abonnement mensuel' ?>
                </div>
              </div>
              <div class="recap-item-price"><?= number_format($it['line_total'] ?? $it['unit_price'],2,',',' ') ?> €</div>
            </div>
            <?php endforeach; ?>
            <hr class="recap-divider">

            <!-- CODE PROMO -->
            <div style="margin-bottom:14px;padding:12px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:10px">
              <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.35);margin-bottom:8px">Code promo</div>
              <div style="display:flex;gap:8px">
                <input type="text" id="promo-input" placeholder="SUMMER25"
                  style="flex:1;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:7px 11px;font-size:.8rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;text-transform:uppercase">
                <button type="button" onclick="applyPromo()"
                  style="background:rgba(38,208,206,.1);border:1px solid rgba(38,208,206,.2);color:#26d0ce;border-radius:8px;padding:7px 12px;font-size:.75rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif">
                  Appliquer
                </button>
              </div>
              <div id="promo-msg" style="font-size:.73rem;margin-top:6px;display:none"></div>
            </div>
            <input type="hidden" name="promo_code" id="promo-code-hidden">
            <div class="recap-row">
              <span>Sous-total HT</span>
              <span><?= number_format($total / 1.2, 2, ',', ' ') ?> €</span>
            </div>
            <div class="recap-row" id="promo-row" style="display:none">
              <span id="promo-label" style="color:#4ade80"> Réduction</span>
              <span id="promo-amount" style="color:#4ade80">—</span>
            </div>
            <div class="recap-row">
              <span>TVA (20%)</span>
              <span><?= number_format($total - ($total / 1.2), 2, ',', ' ') ?> €</span>
            </div>
            <hr class="recap-divider">
            <div class="recap-total">
              <span class="recap-total-label">Total TTC</span>
              <span class="recap-total-amount"><?= number_format($total, 2, ',', ' ') ?> €</span>
            </div>
            <button type="submit" class="btn-submit">
              Confirmer et payer <?= number_format($total, 2, ',', ' ') ?> €
            </button>
            <div class="secure-row">
              <span class="secure-item"> SSL sécurisé</span>
              <span class="secure-item"> Données chiffrées</span>
              <span class="secure-item">↩ Résiliable</span>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>

  <footer>
    <a href="Cgu.php">CGU</a>
    <a href="mention_legales.php">Mentions légales</a>
    <a href="Contact.php">Contact</a>
    <span style="display:block;margin-top:8px">© 2025 CYNA-IT</span>
  </footer>

  <?php form_validation_include('fr'); ?>
<script>
// ── Code promo ────────────────────────────────────────────────
function applyPromo() {
  var code  = document.getElementById('promo-input').value.trim();
  var total = <?= $total ?>;
  var msg   = document.getElementById('promo-msg');

  if (!code) return;

  var fd = new FormData();
  fd.append('code',  code);
  fd.append('total', total);

  fetch('check_promo.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      msg.style.display = 'block';
      if (data.valid) {
        msg.style.color = '#4ade80';
        msg.textContent = data.message;
        document.getElementById('promo-code-hidden').value = code;
        document.getElementById('promo-row').style.display = 'flex';
        document.getElementById('promo-label').textContent = ' ' + code;
        document.getElementById('promo-amount').textContent = '-' + data.discount.toFixed(2).replace('.',',') + ' €';
        // Mettre à jour le total affiché
        var newTotal = data.new_total.toFixed(2).replace('.',',');
        document.querySelector('.recap-total-amount').textContent = newTotal + ' €';
        document.querySelector('.btn-submit').textContent = 'Confirmer et payer ' + newTotal + ' €';
      } else {
        msg.style.color = '#f87171';
        msg.textContent = ' ' + data.message;
        document.getElementById('promo-row').style.display = 'none';
      }
    });
}

// Permettre Enter dans le champ promo
document.getElementById('promo-input').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') { e.preventDefault(); applyPromo(); }
});
var hasSavedCards = <?= $saved_cards ? 'true' : 'false' ?>;

function useSavedCard(isSaved) {
  var section = document.getElementById('new-card-section');
  if (section) section.style.display = isSaved ? 'none' : 'block';
}

// Init : si carte par défaut dispo → masquer le form nouvelle carte
if (hasSavedCards) {
  var defaultChecked = document.querySelector('input[name="saved_card_id"]:checked');
  if (defaultChecked && defaultChecked.value !== 'new') {
    useSavedCard(true);
  } else {
    useSavedCard(false);
  }
}

// ── Stripe Elements ───────────────────────────────────────────
var stripe   = Stripe('<?= htmlspecialchars($stripePublishableKey) ?>');
var elements = stripe.elements();

var style = {
  base: {
    color: '#e8eaf2',
    fontFamily: '"DM Sans", sans-serif',
    fontSize: '15px',
    '::placeholder': { color: '#3a3f52' },
    iconColor: '#26d0ce',
  },
  invalid: { color: '#f87171', iconColor: '#f87171' },
  complete: { color: '#4ade80', iconColor: '#4ade80' }
};

var cardNumber = elements.create('cardNumber', { style: style, placeholder: '0000 0000 0000 0000' });
var cardExpiry = elements.create('cardExpiry', { style: style });
var cardCvc    = elements.create('cardCvc',    { style: style });

cardNumber.mount('#stripe-card-number');
cardExpiry.mount('#stripe-card-expiry');
cardCvc.mount('#stripe-card-cvc');

// Focus/blur
[
  {el: cardNumber, id: 'stripe-card-number'},
  {el: cardExpiry, id: 'stripe-card-expiry'},
  {el: cardCvc,    id: 'stripe-card-cvc'},
].forEach(function(item) {
  item.el.on('focus', function() {
    document.getElementById(item.id).style.borderColor = 'rgba(38,208,206,.5)';
    document.getElementById(item.id).style.boxShadow   = '0 0 0 3px rgba(38,208,206,.08)';
  });
  item.el.on('blur', function() {
    document.getElementById(item.id).style.borderColor = 'rgba(255,255,255,.1)';
    document.getElementById(item.id).style.boxShadow   = 'none';
  });
  item.el.on('change', function(e) {
    var div = document.getElementById('stripe-error');
    if (e.error) { div.style.display = 'block'; div.textContent = ' ' + e.error.message; }
    else { div.style.display = 'none'; }
  });
});

// ── Submit ────────────────────────────────────────────────────
var form = document.querySelector(' form');
form.addEventListener('submit', function(e) {
  e.preventDefault();

  var btn = form.querySelector('.btn-submit');
  if (window.CynaValidate && !window.CynaValidate.validate(form, 'checkout')) {
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Traitement en cours...';

  // Carte enregistrée sélectionnée ?
  var savedRadio = document.querySelector('input[name="saved_card_id"]:checked');
  if (savedRadio && savedRadio.value !== 'new') {
    document.getElementById('payment_method').value = savedRadio.value;
    form.submit();
    return;
  }

  var name = document.getElementById('card_holder') ? document.getElementById('card_holder').value : '';
  stripe.createPaymentMethod({
    type: 'card',
    card: cardNumber,
    billing_details: { name: name }
  }).then(function(result) {
    if (result.error) {
      var div = document.getElementById('stripe-error');
      div.style.display = 'block';
      div.textContent = ' ' + result.error.message;
      btn.disabled = false;
      btn.textContent = 'Confirmer et payer <?= number_format($total, 2, ',', ' ') ?> €';
    } else {
      document.getElementById('payment_method').value = result.paymentMethod.id;
      document.getElementById('card_last4').value = result.paymentMethod.card ? result.paymentMethod.card.last4 : '';
      form.submit();
    }
  });
});

// ── Adresses enregistrées ─────────────────────────────────────
(function () {
  var billingAddresses = <?= json_encode($billing_addresses ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  var shippingAddresses = <?= json_encode($shipping_addresses ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

  function fillFields(addr, prefix) {
    ['prenom', 'nom', 'adresse1', 'adresse2', 'code_postal', 'ville', 'region', 'telephone'].forEach(function (field) {
      var el = document.querySelector('[name="' + prefix + field + '"]');
      if (el) el.value = addr[field] || '';
    });
    var paysEl = document.querySelector('[name="' + prefix + 'pays"]');
    if (paysEl) paysEl.value = addr.pays || 'France';
  }

  var billingSelect = document.querySelector('select[name="address_id"]');
  if (billingSelect) {
    billingSelect.addEventListener('change', function () {
      var id = parseInt(this.value, 10);
      var addr = billingAddresses.find(function (a) { return parseInt(a.id, 10) === id; });
      if (addr) fillFields(addr, '');
    });
  }

  var shippingSelect = document.getElementById('shipping_address_id');
  if (shippingSelect) {
    shippingSelect.addEventListener('change', function () {
      var id = parseInt(this.value, 10);
      var addr = shippingAddresses.find(function (a) { return parseInt(a.id, 10) === id; });
      if (addr) fillFields(addr, 'shipping_');
    });
  }
})();

function toggleShippingFields() {
  var checked = document.getElementById('shipping_same_as_billing');
  var block = document.getElementById('shipping-fields');
  if (! block || ! checked) return;
  block.style.display = checked.checked ? 'none' : '';
  document.querySelectorAll('.shipping-field').forEach(function (el) {
    el.required = ! checked.checked;
  });
}

toggleShippingFields();
</script>
</body>
</html>