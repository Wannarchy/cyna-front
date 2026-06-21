<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/home_repository.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: categories.php');
    exit;
}

$c = null;
$success = false;
$error = '';

$flashSuccess = trim($_GET['success'] ?? '');
$flashError = trim($_GET['error'] ?? '');

try {
    foreach (admin_api()->adminGetCategories() as $cat) {
        if ((int) ($cat['id'] ?? 0) === $id) {
            $c = $cat;
            break;
        }
    }
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}

if (! $c) {
    header('Location: categories.php?error='.urlencode('Catégorie introuvable.'));
    exit;
}

$productCount = (int) ($c['products_count'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $newImagePath = null;
    $imageSelected = admin_request_has_image_file($_FILES['image'] ?? null);

    try {
        $newImagePath = admin_upload_product_image($_FILES['image'] ?? null, 'categories');
    } catch (Throwable $e) {
        $error = 'Upload image : '.$e->getMessage();
    }

    if ($imageSelected && ($newImagePath === null || trim((string) $newImagePath) === '') && $error === '') {
        $error = 'Upload image : aucune URL Cloudinary reçue.';
    }

    if ($name === '') {
        $error = 'Le nom de la catégorie est obligatoire.';
    } elseif ($sortOrder < 1) {
        $error = 'L\'ordre d\'affichage doit être au minimum 1.';
    } elseif (admin_category_sort_order_taken($sortOrder, $id)) {
        $error = 'Cet ordre d\'affichage est déjà utilisé par une autre catégorie.';
    } elseif ($error === '') {
        try {
            admin_api()->adminUpdateCategory(
                $id,
                admin_category_payload($_POST, $newImagePath, $c['image_path'] ?? null)
            );
            foreach (admin_api()->adminGetCategories() as $cat) {
                if ((int) ($cat['id'] ?? 0) === $id) {
                    $c = $cat;
                    break;
                }
            }
            $success = true;
            $productCount = (int) ($c['products_count'] ?? 0);
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="ph">
  <div class="ph-left">
    <h1>Modifier la catégorie</h1>
    <p>Mise à jour de « <?= htmlspecialchars($c['name']) ?> »</p>
  </div>
  <a href="categories.php" class="btn-ghost">← Retour aux catégories</a>
</div>

<?php if ($success || $flashSuccess !== ''): ?>
<div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#4ade80;margin-bottom:20px">
  <?= htmlspecialchars($flashSuccess !== '' ? $flashSuccess : 'Catégorie mise à jour avec succès.') ?>
</div>
<?php endif; ?>

<?php if ($flashError !== ''): ?>
<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#f87171;margin-bottom:20px">
  <?= htmlspecialchars($flashError) ?>
</div>
<?php endif; ?>

<?php if ($error !== ''): ?>
<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#f87171;margin-bottom:20px">
  <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-12 col-lg-5">
    <div class="card">
      <div class="card-head">Informations</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" data-cyna-validate="admin-category">
          <input type="hidden" name="id" value="<?= $id ?>">
          <div class="mb-3">
            <label class="form-label">Nom de la catégorie *</label>
            <input class="form-control" name="name" required
              value="<?= htmlspecialchars($_POST['name'] ?? $c['name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Image Cloudinary</label>
            <input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp">
            <small style="color:var(--c-muted2)">JPG, PNG ou WEBP — max 10 Mo. Laissez vide pour conserver l'image actuelle.</small>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Ordre d'affichage *</label>
              <input class="form-control" name="sort_order" type="number" min="1" required
                value="<?= (int) ($_POST['sort_order'] ?? $c['sort_order'] ?? 1) ?>">
            </div>
            <div class="col-6">
              <label class="form-label">Statut</label>
              <?php $isActive = (int) ($_POST['is_active'] ?? $c['is_active'] ?? 1); ?>
              <select class="form-select" name="is_active">
                <option value="1" <?= $isActive === 1 ? 'selected' : '' ?>>Actif</option>
                <option value="0" <?= $isActive === 0 ? 'selected' : '' ?>>Inactif</option>
              </select>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button class="btn-cyna" type="submit">Enregistrer</button>
            <a href="categories.php" class="btn-ghost" style="padding:10px 16px;text-decoration:none">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card">
      <div class="card-head">Aperçu</div>
      <div class="card-body" style="text-align:center">
        <?php if (! empty($c['image_path'])): ?>
          <img src="<?= htmlspecialchars(image_display_src($c['image_path'], '../')) ?>"
               alt="" style="max-width:100%;max-height:180px;border-radius:12px;object-fit:cover;border:1px solid var(--c-border)"
               onerror="this.style.display='none'">
          <div style="margin-top:10px;font-size:.72rem;color:var(--c-muted);word-break:break-all;text-align:left"><?= htmlspecialchars($c['image_path']) ?></div>
        <?php else: ?>
          <div style="padding:32px;color:var(--c-muted2);font-size:.85rem">Aucune image</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card" style="margin-top:16px">
      <div class="card-head">Résumé</div>
      <div class="card-body" style="font-size:.84rem;color:var(--c-muted)">
        <p style="margin:0 0 8px"><strong style="color:#fff">ID</strong> #<?= $id ?></p>
        <p style="margin:0 0 8px"><strong style="color:#fff">Produits rattachés</strong> <?= $productCount ?></p>
        <?php if ($productCount > 0): ?>
          <p style="margin:12px 0 0;font-size:.78rem;color:var(--c-muted2)">Cette catégorie ne peut pas être supprimée tant qu'elle contient des produits.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
