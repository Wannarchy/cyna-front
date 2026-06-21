<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$row = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['content_text'] ?? '');

    try {
        admin_api()->adminUpdateHomepageContent($text);
    } catch (RuntimeException) {
    }

    header('Location: home_text.php');
    exit;
}

require_once __DIR__ . '/header.php';

try {
    $homepage = admin_api()->getHomepage();
    $row = $homepage['content'] ?? null;
} catch (RuntimeException) {
}
?>

<div class="ph">
  <div class="ph-left">
    <h1>Texte Homepage</h1>
    <p>Modifiez le texte affiché sous le carousel</p>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-7">
    <div class="card">
      <div class="card-head">Éditer le contenu</div>
      <div class="card-body">
        <form method="POST" data-cyna-validate="admin-home-text">
          <div style="background:rgba(38,208,206,.05);border:1px solid rgba(38,208,206,.15);border-radius:10px;padding:14px;margin-bottom:14px">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#26d0ce;margin-bottom:8px">Texte d'accueil</div>
            <textarea class="form-control" name="content_text" rows="6" required
              placeholder="Ex : CYNA propose des solutions SaaS de cybersécurité..."><?= htmlspecialchars($row['content_text'] ?? '') ?></textarea>
          </div>
          <button class="btn-cyna">Enregistrer les modifications</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card">
      <div class="card-head">Aperçu</div>
      <div class="card-body">
        <div style="background:linear-gradient(135deg,rgba(26,41,128,.3),rgba(38,208,206,.15));border:1px solid rgba(38,208,206,.15);border-radius:10px;padding:18px;font-size:.88rem;color:rgba(255,255,255,.8);line-height:1.7;min-height:80px">
          <?= ! empty($row['content_text']) ? nl2br(htmlspecialchars($row['content_text'])) : '<span style="opacity:.4;font-style:italic">Aucun texte configuré.</span>' ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
