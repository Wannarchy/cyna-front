<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/home_repository.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$cats = [];
$listError = '';

try {
    $cats = admin_api()->adminGetCategories();
} catch (RuntimeException $e) {
    $listError = $e->getMessage();
}

$flashError = trim($_GET['error'] ?? '');
$flashSuccess = trim($_GET['success'] ?? '');
$flashWarn = trim($_GET['warn'] ?? '');
$nextSortOrder = admin_category_next_sort_order();

$per_page = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));
$total_cats = count($cats);
$total_pages = max(1, (int) ceil($total_cats / $per_page));
$page = min($page, $total_pages);
$cats_page = array_slice($cats, ($page - 1) * $per_page, $per_page);

$build_categories_url = static function (array $extra = []): string {
    $params = array_filter($extra, static fn ($v): bool => $v !== '' && $v !== 0);

    return 'categories.php'.($params !== [] ? '?'.http_build_query($params) : '');
};
?>

<div class="ph">
  <div class="ph-left">
    <h1>Catégories</h1>
    <p>Gérez les catégories de services SaaS du catalogue</p>
  </div>
</div>

<?php if ($flashSuccess !== ''): ?>
<div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#4ade80;margin-bottom:16px">
  <?= htmlspecialchars($flashSuccess) ?>
</div>
<?php endif; ?>

<?php if ($flashWarn !== ''): ?>
<div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#fbbf24;margin-bottom:16px">
  <?= htmlspecialchars($flashWarn) ?>
</div>
<?php endif; ?>

<?php if ($flashError !== ''): ?>
<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#f87171;margin-bottom:16px">
  <?= htmlspecialchars($flashError) ?>
</div>
<?php endif; ?>

<?php if (! empty($listError)): ?>
<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#f87171;margin-bottom:16px">
  Impossible de charger les catégories : <?= htmlspecialchars($listError) ?>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-12 col-lg-4">
    <div class="card">
      <div class="card-head">Nouvelle catégorie</div>
      <div class="card-body">
        <form method="POST" action="category_save.php" enctype="multipart/form-data" data-cyna-validate="admin-category">
          <div class="mb-3">
            <label class="form-label">Nom de la catégorie *</label>
            <input class="form-control" name="name" required placeholder="Ex : SOC & Surveillance">
          </div>
          <div class="mb-3">
            <label class="form-label">Image Cloudinary</label>
            <input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp">
            <small style="color:var(--c-muted2)">JPG, PNG ou WEBP — max 10 Mo.</small>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Ordre d'affichage *</label>
              <input class="form-control" name="sort_order" type="number" value="<?= (int)$nextSortOrder ?>" min="1" required>
              <small style="color:var(--c-muted2)">Ordre unique.</small>
            </div>
            <div class="col-6">
              <label class="form-label">Statut</label>
              <select class="form-select" name="is_active">
                <option value="1">Actif</option>
                <option value="0">Inactif</option>
              </select>
            </div>
          </div>
          <button class="btn-cyna" style="width:100%">+ Ajouter la catégorie</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-head" style="justify-content:space-between;gap:12px;flex-wrap:wrap">
        <span>Liste des catégories <span class="badge badge-blue"><?= $total_cats ?></span></span>
        <div id="bulk-actions" class="bulk-actions" hidden>
          <span id="bulk-count" class="bulk-count">0 sélectionnée(s)</span>
          <button type="button" class="btn-del js-bulk-delete">Supprimer la sélection</button>
        </div>
      </div>
      <div class="table-scroll">
      <table class="ctable" id="categories-table">
        <thead>
          <tr>
            <th class="col-check">
              <input type="checkbox" id="select-all-categories" class="category-check" title="Tout sélectionner (sans produits)" aria-label="Tout sélectionner">
            </th>
            <th>ID</th>
            <th>Nom</th>
            <th>Image</th>
            <th>Ordre</th>
            <th>Produits</th>
            <th>Statut</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$cats_page): ?>
            <tr><td colspan="8"><div class="empty-state"><div class="icon">▦</div><p>Aucune catégorie créée</p></div></td></tr>
          <?php else: foreach ($cats_page as $c):
            $productCount = (int) ($c['products_count'] ?? 0);
            $canDelete = $productCount === 0;
          ?>
          <tr>
            <td class="col-check">
              <?php if ($canDelete): ?>
                <input type="checkbox" class="category-check js-category-select"
                       value="<?= (int)$c['id'] ?>"
                       data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>"
                       aria-label="Sélectionner <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>">
              <?php else: ?>
                <span title="Catégorie avec produits — non supprimable" style="opacity:.35;font-size:.75rem">—</span>
              <?php endif; ?>
            </td>
            <td class="mono">#<?= (int)$c['id'] ?></td>
            <td style="font-weight:500;color:#fff"><?= htmlspecialchars($c['name']) ?></td>
            <td>
              <?php if (! empty($c['image_path'])): ?>
                <img src="<?= htmlspecialchars(image_display_src($c['image_path'], '../')) ?>"
                     style="width:36px;height:36px;border-radius:8px;object-fit:cover"
                     onerror="this.style.display='none'">
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
            <td><span class="badge badge-gray"><?= (int)$c['sort_order'] ?></span></td>
            <td>
              <?php if ($productCount > 0): ?>
                <span class="badge badge-blue" title="Suppression impossible"><?= $productCount ?> produit<?= $productCount > 1 ? 's' : '' ?></span>
              <?php else: ?>
                <span class="muted">0</span>
              <?php endif; ?>
            </td>
            <td>
              <?= (int)$c['is_active']
                ? '<span class="badge badge-green">Actif</span>'
                : '<span class="badge badge-red">Inactif</span>' ?>
            </td>
            <td class="text-right actions-cell">
              <div class="row-actions">
                <a href="category_edit.php?id=<?= (int)$c['id'] ?>" class="btn-edit">Modifier</a>
                <?php if ($canDelete): ?>
                  <button type="button" class="btn-del js-delete-category"
                          data-id="<?= (int)$c['id'] ?>"
                          data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>">
                    Supprimer
                  </button>
                <?php else: ?>
                  <span class="btn-del" style="opacity:.45;cursor:not-allowed" title="Réassignez les produits avant suppression">Supprimer</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      </div>
      <?php admin_render_pagination($page, $total_pages, $total_cats, 'catégorie(s)', $build_categories_url, true); ?>
    </div>
  </div>
</div>

<div id="delete-modal" class="admin-modal" aria-hidden="true" hidden>
  <div class="admin-modal-backdrop" data-close-modal></div>
  <div class="admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
    <div class="admin-modal-head">
      <h2 id="delete-modal-title">Supprimer la catégorie</h2>
      <button type="button" class="admin-modal-close" data-close-modal aria-label="Fermer">&times;</button>
    </div>
    <div class="admin-modal-body">
      <p id="delete-modal-message"></p>
      <ul id="delete-modal-list" class="delete-modal-list" hidden></ul>
      <p style="font-size:.8rem;color:var(--c-muted2);margin:0">Les catégories contenant des produits ne peuvent pas être supprimées.</p>
    </div>
    <div class="admin-modal-foot">
      <button type="button" class="btn-ghost" data-close-modal>Annuler</button>
      <form method="POST" action="category_delete.php" id="delete-category-form" style="margin:0">
        <div id="delete-form-fields"></div>
        <button type="submit" class="btn-del">Confirmer la suppression</button>
      </form>
    </div>
  </div>
</div>

<style>
  .bulk-actions { display: flex; align-items: center; gap: 12px; }
  .bulk-count { font-size: .78rem; color: var(--c-muted2); text-transform: none; letter-spacing: 0; font-weight: 500; }
  .col-check { width: 42px; text-align: center; vertical-align: middle; }
  .category-check { width: 16px; height: 16px; cursor: pointer; accent-color: var(--c-cyan); }
  .delete-modal-list {
    margin: 12px 0 16px; padding-left: 18px; max-height: 160px; overflow-y: auto;
    font-size: .84rem; color: var(--c-text);
  }
  .delete-modal-list li { margin-bottom: 4px; }
  .admin-modal {
    position: fixed; inset: 0; z-index: 9999;
    display: none; align-items: center; justify-content: center; padding: 16px;
  }
  .admin-modal.is-open { display: flex; }
  .admin-modal[hidden] { display: none !important; }
  .admin-modal.is-open[hidden] { display: flex !important; }
  .admin-modal-backdrop {
    position: absolute; inset: 0;
    background: rgba(0,0,0,.65); backdrop-filter: blur(4px);
  }
  .admin-modal-dialog {
    position: relative; z-index: 1; width: 100%; max-width: 440px;
    background: var(--c-card); border: 1px solid var(--c-border);
    border-radius: 16px; box-shadow: 0 24px 64px rgba(0,0,0,.45);
    overflow: hidden;
  }
  .admin-modal-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid var(--c-border);
  }
  .admin-modal-head h2 { margin: 0; font-size: 1rem; font-weight: 700; color: #fff; }
  .admin-modal-close {
    background: transparent; border: none; color: var(--c-muted2);
    font-size: 1.25rem; line-height: 1; cursor: pointer; padding: 4px 8px; border-radius: 6px;
  }
  .admin-modal-close:hover { color: #fff; background: rgba(255,255,255,.06); }
  .admin-modal-body { padding: 20px; color: var(--c-muted); font-size: .9rem; line-height: 1.6; }
  .admin-modal-foot {
    display: flex; align-items: center; justify-content: flex-end; gap: 10px;
    padding: 16px 20px; border-top: 1px solid var(--c-border);
    background: rgba(255,255,255,.02);
  }
  button.btn-del { font-family: inherit; }
</style>

<script>
(function () {
  var modal = document.getElementById('delete-modal');
  var titleEl = document.getElementById('delete-modal-title');
  var messageEl = document.getElementById('delete-modal-message');
  var listEl = document.getElementById('delete-modal-list');
  var fieldsEl = document.getElementById('delete-form-fields');
  var bulkActions = document.getElementById('bulk-actions');
  var bulkCount = document.getElementById('bulk-count');
  var selectAll = document.getElementById('select-all-categories');

  if (!modal || !titleEl || !messageEl || !listEl || !fieldsEl) return;

  document.body.appendChild(modal);

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
  }

  function getSelectedCategories() {
    return Array.prototype.map.call(
      document.querySelectorAll('.js-category-select:checked'),
      function (cb) { return { id: cb.value, name: cb.getAttribute('data-name') || '' }; }
    );
  }

  function getDeletableCheckboxes() {
    return document.querySelectorAll('.js-category-select');
  }

  function updateBulkBar() {
    var selected = getSelectedCategories();
    var count = selected.length;
    var boxes = getDeletableCheckboxes();
    if (bulkActions) bulkActions.hidden = count === 0;
    if (bulkCount) bulkCount.textContent = count + ' sélectionnée' + (count > 1 ? 's' : '');
    if (selectAll && boxes.length > 0) {
      selectAll.checked = count > 0 && count === boxes.length;
      selectAll.indeterminate = count > 0 && count < boxes.length;
    } else if (selectAll) {
      selectAll.checked = false;
      selectAll.indeterminate = false;
    }
  }

  function setFormFields(items, fieldName) {
    fieldsEl.innerHTML = '';
    items.forEach(function (item) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = fieldName;
      input.value = String(item.id);
      fieldsEl.appendChild(input);
    });
  }

  function openModal() {
    modal.hidden = false;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function openSingleDeleteModal(id, name) {
    titleEl.textContent = 'Supprimer la catégorie';
    messageEl.innerHTML = 'Voulez-vous vraiment supprimer <strong style="color:#fff">' + escapeHtml(name) + '</strong>&nbsp;?';
    listEl.hidden = true;
    listEl.innerHTML = '';
    setFormFields([{ id: id }], 'id');
    openModal();
  }

  function openBulkDeleteModal(items) {
    var count = items.length;
    titleEl.textContent = 'Supprimer les catégories sélectionnées';
    messageEl.textContent = 'Voulez-vous vraiment supprimer ' + count + ' catégorie' + (count > 1 ? 's' : '') + ' ?';
    var visible = items.slice(0, 8);
    var html = visible.map(function (item) { return '<li>' + escapeHtml(item.name) + '</li>'; }).join('');
    if (items.length > 8) html += '<li class="muted">… et ' + (items.length - 8) + ' autre(s)</li>';
    listEl.innerHTML = html;
    listEl.hidden = false;
    setFormFields(items, 'ids[]');
    openModal();
  }

  document.addEventListener('change', function (e) {
    if (e.target.classList.contains('js-category-select')) updateBulkBar();
    if (e.target.id === 'select-all-categories') {
      var checked = e.target.checked;
      getDeletableCheckboxes().forEach(function (cb) { cb.checked = checked; });
      updateBulkBar();
    }
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.js-delete-category')) {
      e.preventDefault();
      var btn = e.target.closest('.js-delete-category');
      openSingleDeleteModal(btn.getAttribute('data-id'), btn.getAttribute('data-name'));
      return;
    }
    if (e.target.closest('.js-bulk-delete')) {
      e.preventDefault();
      var selected = getSelectedCategories();
      if (selected.length) openBulkDeleteModal(selected);
      return;
    }
    if (e.target.closest('[data-close-modal]')) closeModal();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
  });

  updateBulkBar();
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
