<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$slides = [];
$edit = null;

try {
    $homepage = admin_api()->getHomepage();
    $slides = $homepage['slides'] ?? [];
    if (isset($_GET['edit'])) {
        foreach ($slides as $slide) {
            if ((int) ($slide['id'] ?? 0) === (int) $_GET['edit']) {
                $edit = $slide;
                break;
            }
        }
    }
} catch (RuntimeException) {
}
?>

<div class="ph">
  <div class="ph-left">
    <h1>Slides du Carousel</h1>
    <p>Gérez les slides affichées sur la page d'accueil — avec traductions EN / AR / HE</p>
  </div>
</div>

<div class="row g-3">

  <!-- FORMULAIRE AJOUT / ÉDITION -->
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-head">
        <?= $edit ? 'Modifier la slide #'.(int)$edit['id'] : 'Nouvelle slide' ?>
        <?php if ($edit): ?>
          <a href="slides.php" style="font-size:.75rem;color:#8b92a8;text-decoration:none;margin-left:auto">Annuler</a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <form method="POST" action="slide_save.php" data-cyna-validate="admin-slide">
          <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
          <?php endif; ?>

          <!-- FR (obligatoire) -->
          <div style="background:rgba(38,208,206,.05);border:1px solid rgba(38,208,206,.15);border-radius:10px;padding:14px;margin-bottom:14px">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#26d0ce;margin-bottom:10px">Français (obligatoire)</div>
            <div class="mb-2">
              <label class="form-label">Titre *</label>
              <input class="form-control" name="title" required placeholder="Ex : Sécurisez votre infrastructure"
                     value="<?= htmlspecialchars($edit['title'] ?? '') ?>">
            </div>
            <div class="mb-0">
              <label class="form-label">Sous-titre</label>
              <input class="form-control" name="subtitle" placeholder="SOC, EDR, XDR — déploiement en 24h"
                     value="<?= htmlspecialchars($edit['subtitle'] ?? '') ?>">
            </div>
          </div>

          <!-- EN -->
          <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:14px;margin-bottom:14px">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#8b92a8;margin-bottom:10px">English <span style="font-weight:400;opacity:.6">(optionnel — fallback FR si vide)</span></div>
            <div class="mb-2">
              <label class="form-label">Title</label>
              <input class="form-control" name="title_en" placeholder="Ex: Secure your infrastructure"
                     value="<?= htmlspecialchars($edit['title_en'] ?? '') ?>">
            </div>
            <div class="mb-0">
              <label class="form-label">Subtitle</label>
              <input class="form-control" name="subtitle_en" placeholder="SOC, EDR, XDR — 24h deployment"
                     value="<?= htmlspecialchars($edit['subtitle_en'] ?? '') ?>">
            </div>
          </div>

          <!-- AR -->
          <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:14px;margin-bottom:14px">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#8b92a8;margin-bottom:10px">العربية <span style="font-weight:400;opacity:.6">(optionnel — fallback FR si vide)</span></div>
            <div class="mb-2">
              <label class="form-label">العنوان</label>
              <input class="form-control" name="title_ar" placeholder="مثال: تأمين بنيتك التحتية" dir="rtl"
                     value="<?= htmlspecialchars($edit['title_ar'] ?? '') ?>">
            </div>
            <div class="mb-0">
              <label class="form-label">العنوان الفرعي</label>
              <input class="form-control" name="subtitle_ar" placeholder="SOC، EDR، XDR — نشر في 24 ساعة" dir="rtl"
                     value="<?= htmlspecialchars($edit['subtitle_ar'] ?? '') ?>">
            </div>
          </div>

          <!-- HE -->
          <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:14px;margin-bottom:14px">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#8b92a8;margin-bottom:10px">עברית <span style="font-weight:400;opacity:.6">(optionnel — fallback FR si vide)</span></div>
            <div class="mb-2">
              <label class="form-label">כותרת</label>
              <input class="form-control" name="title_he" placeholder="לדוג׳: אבטח את התשתית שלך" dir="rtl"
                     value="<?= htmlspecialchars($edit['title_he'] ?? '') ?>">
            </div>
            <div class="mb-0">
              <label class="form-label">כותרת משנה</label>
              <input class="form-control" name="subtitle_he" placeholder="SOC, EDR, XDR — פריסה תוך 24 שעות" dir="rtl"
                     value="<?= htmlspecialchars($edit['subtitle_he'] ?? '') ?>">
            </div>
          </div>

          <!-- Paramètres communs -->
          <div class="mb-3">
            <label class="form-label">Lien (URL)</label>
            <input class="form-control" name="link_url" placeholder="public/catalogue.php"
                   value="<?= htmlspecialchars($edit['link_url'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Image (chemin)</label>
            <input class="form-control" name="image_path" placeholder="assets/images/slide1.jpg"
                   value="<?= htmlspecialchars($edit['image_path'] ?? '') ?>">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Ordre</label>
              <input class="form-control" name="sort_order" type="number" value="<?= (int)($edit['sort_order'] ?? 1) ?>" min="1">
            </div>
            <div class="col-6">
              <label class="form-label">Statut</label>
              <select class="form-select" name="is_active">
                <option value="1" <?= ($edit['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>Actif</option>
                <option value="0" <?= ($edit['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>Inactif</option>
              </select>
            </div>
          </div>

          <button class="btn-cyna" style="width:100%">
            <?= $edit ? 'Enregistrer les modifications' : '+ Ajouter la slide' ?>
          </button>
        </form>
      </div>
    </div>

    <div style="background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.15);border-radius:10px;padding:12px 16px;margin-top:12px;font-size:.78rem;color:rgba(245,158,11,.8)">
      <strong>Fallback automatique :</strong> si une traduction est vide, le titre FR s'affiche à la place. Tu n'es pas obligé de tout remplir.
    </div>
  </div>

  <!-- LISTE DES SLIDES -->
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-head">
        Slides configurées
        <span class="badge badge-blue"><?= count($slides) ?></span>
      </div>
      <table class="ctable">
        <thead>
          <tr><th>ID</th><th>Titre FR</th><th>EN</th><th>AR</th><th>HE</th><th>Statut</th><th class="text-right">Actions</th></tr>
        </thead>
        <tbody>
          <?php if (!$slides): ?>
            <tr><td colspan="7"><div class="empty-state"><div class="icon">▣</div><p>Aucune slide configurée</p></div></td></tr>
          <?php else: foreach ($slides as $s): ?>
          <tr style="<?= $edit && (int)$edit['id'] === (int)$s['id'] ? 'background:rgba(38,208,206,.06)' : '' ?>">
            <td class="mono">#<?= (int)$s['id'] ?></td>
            <td style="font-weight:500;color:#fff;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($s['title']) ?>">
              <?= htmlspecialchars($s['title']) ?>
            </td>
            <td><?= !empty($s['title_en']) ? '<span style="color:#4ade80;font-size:.8rem">✓</span>' : '<span style="color:#5c6378;font-size:.8rem">—</span>' ?></td>
            <td><?= !empty($s['title_ar']) ? '<span style="color:#4ade80;font-size:.8rem">✓</span>' : '<span style="color:#5c6378;font-size:.8rem">—</span>' ?></td>
            <td><?= !empty($s['title_he']) ? '<span style="color:#4ade80;font-size:.8rem">✓</span>' : '<span style="color:#5c6378;font-size:.8rem">—</span>' ?></td>
            <td>
              <?= (int)$s['is_active']
                ? '<span class="badge badge-green">Actif</span>'
                : '<span class="badge badge-red">Inactif</span>' ?>
            </td>
            <td class="text-right" style="display:flex;gap:6px;justify-content:flex-end">
              <a href="slides.php?edit=<?= (int)$s['id'] ?>"
                 style="font-size:.73rem;padding:4px 10px;border-radius:7px;background:rgba(38,208,206,.1);color:#26d0ce;border:1px solid rgba(38,208,206,.2);text-decoration:none;white-space:nowrap">
                Modifier
              </a>
              <a class="btn-del" href="slide_delete.php?id=<?= (int)$s['id'] ?>"
                 onclick="return confirm('Supprimer cette slide ?')">Suppr.</a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>