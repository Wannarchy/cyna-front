<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/home_repository.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: products.php');
    exit;
}

$p = null;
$cats = [];
$success = false;
$error = '';
$warn = '';

$flashSuccess = trim($_GET['success'] ?? '');
$flashError = trim($_GET['error'] ?? '');
$flashWarn = trim($_GET['warn'] ?? '');

try {
    $cats = admin_api()->adminGetCategories();
    foreach (admin_api()->adminGetProducts() as $product) {
        if ((int) ($product['id'] ?? 0) === $id) {
            $p = admin_product_row($product);
            break;
        }
    }
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}

if (! $p) {
    header('Location: products.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $newImagePath = null;
    $imageSelected = admin_request_has_image_file($_FILES['image'] ?? null);

    try {
        $newImagePath = admin_upload_product_image($_FILES['image'] ?? null, 'products');
    } catch (Throwable $e) {
        $error = 'Upload image : '.$e->getMessage();
    }

    if ($imageSelected && ($newImagePath === null || trim((string) $newImagePath) === '') && $error === '') {
        $error = 'Upload image : aucune URL Cloudinary reçue.';
    }

    if ($name === '') {
        $error = 'Le nom du service est obligatoire.';
    } elseif (admin_product_category_id($_POST) <= 0) {
        $error = 'La catégorie est obligatoire.';
    } elseif (admin_product_name_taken($name, $id)) {
        $error = 'Un produit avec ce nom existe déjà.';
    } elseif ($error === '') {
        try {
            $result = admin_api()->adminUpdateProduct(
                $id,
                admin_product_payload($_POST, $newImagePath, $p['image_path'] ?? null)
            );
            $updated = $result['data'] ?? [];
            $apiMessage = trim((string) ($result['message'] ?? ''));

            if ($updated !== []) {
                $p = admin_product_row($updated);
            }
            $success = true;
            if ($apiMessage !== '' && stripos($apiMessage, 'Stripe') !== false && stripos($apiMessage, 'échouée') !== false) {
                $warn = $apiMessage;
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="ph">
  <div class="ph-left">
    <h1>Modifier le produit</h1>
    <p>Mise à jour de « <?= htmlspecialchars($p['name']) ?> »</p>
  </div>
  <a href="products.php" class="btn-ghost">← Retour aux produits</a>
</div>

<?php if ($success || $flashSuccess !== ''): ?>
  <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#4ade80;margin-bottom:20px;display:flex;align-items:center;gap:8px">
    <?= htmlspecialchars($flashSuccess !== '' ? $flashSuccess : 'Produit mis à jour avec succès.') ?>
  </div>
<?php endif; ?>

<?php if ($warn !== '' || $flashWarn !== ''): ?>
  <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#fbbf24;margin-bottom:20px">
    <?= htmlspecialchars($warn !== '' ? $warn : $flashWarn) ?>
  </div>
<?php endif; ?>

<?php if ($error || $flashError !== ''): ?>
  <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#f87171;margin-bottom:20px">
    <?= htmlspecialchars($error !== '' ? $error : $flashError) ?>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-head">Informations du service</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" data-cyna-validate="admin-product">
          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label">Nom du service *</label>
              <input class="form-control" name="name" required value="<?= htmlspecialchars($p['name']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Catégorie *</label>
              <select class="form-select" name="category_id" required <?= !$cats ? 'disabled' : '' ?>>
                <option value="" disabled <?= empty($p['category_id']) ? 'selected' : '' ?>>— Choisir —</option>
                <?php foreach ($cats as $c): ?>
                  <option value="<?= (int)$c['id'] ?>" <?= (int)$p['category_id']===(int)$c['id']?'selected':'' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (!$cats): ?>
                <small style="color:#f87171">Créez d'abord une catégorie.</small>
              <?php endif; ?>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12">
              <label class="form-label">Description du service</label>
              <textarea class="form-control" name="description" rows="5"
                        placeholder="Fonctionnalités, avantages, sécurité..."><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12">
              <label class="form-label">Caractéristiques techniques</label>
              <textarea class="form-control" name="technical_specs" rows="6"
                        placeholder="Une caractéristique par ligne"><?= htmlspecialchars(implode("\n", $p['technical_specs'] ?? [])) ?></textarea>
              <small style="color:var(--c-muted2)">Ex : Protection multi-terminaux, Support 24/7, SLA garanti…</small>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Prix mensuel (€)</label>
              <input class="form-control" name="price_monthly" type="number" step="0.01"
                     value="<?= number_format((float)$p['price_monthly'],2,'.','') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Prix annuel (€)</label>
              <input class="form-control" name="price_yearly" type="number" step="0.01"
                     value="<?= number_format((float)$p['price_yearly'],2,'.','') ?>">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Image Cloudinary</label>
              <input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp">
              <small style="color:var(--c-muted2)">JPG, PNG ou WEBP uniquement — max 10 Mo. Laisse vide pour conserver l'image actuelle.</small>
              <?php if (! empty($p['image_path']) && ! in_array($p['image_path'], ['logo.jpg', 'logo.png'], true)): ?>
                <div style="margin-top:8px;font-size:.72rem;color:var(--c-muted);word-break:break-all"><?= htmlspecialchars($p['image_path']) ?></div>
              <?php endif; ?>
            </div>
            <div class="col-md-3">
              <label class="form-label">Disponibilité</label>
              <select class="form-select" name="is_available">
                <option value="1" <?= (int)$p['is_available']===1?'selected':'' ?>>Disponible</option>
                <option value="0" <?= (int)$p['is_available']===0?'selected':'' ?>>Indisponible</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Top produit</label>
              <select class="form-select" name="is_featured">
                <option value="0" <?= (int)$p['is_featured']===0?'selected':'' ?>>Non</option>
                <option value="1" <?= (int)$p['is_featured']===1?'selected':'' ?>>Oui</option>
              </select>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Stock (quantité disponible)</label>
              <input class="form-control" name="stock" type="number" min="0" step="1"
                     value="<?= (int)($p['stock'] ?? 0) ?>" style="max-width:160px">
              <small style="color:var(--c-muted2)">0 = indisponible à l'achat.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Produit physique (livraison)</label>
              <select class="form-select" name="requires_shipping" style="max-width:220px">
                <option value="0" <?= empty($p['requires_shipping']) ? 'selected' : '' ?>>Non — SaaS / digital</option>
                <option value="1" <?= ! empty($p['requires_shipping']) ? 'selected' : '' ?>>Oui — adresse de livraison requise</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ordre (top produits)</label>
              <input class="form-control" name="featured_order" type="number"
                     value="<?= (int)$p['featured_order'] ?>" style="max-width:120px">
            </div>
          </div>

          <div class="d-flex gap-2">
            <button class="btn-cyna" type="submit">Enregistrer les modifications</button>
            <a href="products.php" class="btn-ghost">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card mb-3">
      <div class="card-head">Aperçu</div>
      <div class="card-body">
        <div style="background:rgba(255,255,255,.03);border:1px solid var(--c-border);border-radius:10px;overflow:hidden">
          <?php if (!empty($p['image_path']) && ! in_array($p['image_path'], ['logo.jpg', 'logo.png'], true)): ?>
            <img src="<?= htmlspecialchars(image_display_src($p['image_path'] ?? null, '../')) ?>"
                 style="width:100%;height:120px;object-fit:cover;display:block"
                 alt="<?= htmlspecialchars($p['name']) ?>">
          <?php else: ?>
            <div style="height:120px;background:linear-gradient(135deg,rgba(26,41,128,.4),rgba(38,208,206,.2));display:flex;align-items:center;justify-content:center;font-size:2.5rem"></div>
          <?php endif; ?>
          <div style="padding:14px">
            <div style="font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--c-cyan);margin-bottom:4px">
              <?= htmlspecialchars($p['cat_name'] ?? 'Sans catégorie') ?>
            </div>
            <div style="font-weight:600;color:#fff;margin-bottom:6px"><?= htmlspecialchars($p['name']) ?></div>
            <div style="font-size:.8rem;color:var(--c-muted2)">
              <?= number_format((float)$p['price_monthly'],2,',',' ') ?> € / mois
            </div>
          </div>
        </div>
        <div style="margin-top:12px;text-align:center">
          <a href="../public/produit.php?id=<?= $id ?>" target="_blank" class="btn-view" style="font-size:.75rem">
            Voir la page publique →
          </a>
        </div>
      </div>
    </div>

    <div class="card" style="border-color:rgba(239,68,68,.2)">
      <div class="card-head" style="color:#f87171">Zone dangereuse</div>
      <div class="card-body">
        <p style="font-size:.8rem;color:var(--c-muted2);margin-bottom:12px">
          La suppression est irréversible.
        </p>
        <form method="POST" action="product_delete.php" onsubmit="return confirm('Supprimer définitivement ce produit ?')">
          <input type="hidden" name="id" value="<?= $id ?>">
          <button type="submit" class="btn-del" style="width:100%;text-align:center;display:block;padding:8px">
            Supprimer ce produit
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
