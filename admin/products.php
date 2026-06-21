<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/home_repository.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$cats = [];
$allProducts = [];
$listError = '';

try {
    $cats = admin_api()->adminGetCategories();
    $allProducts = array_map('admin_product_row', admin_api()->adminGetProducts());
} catch (RuntimeException $e) {
    $listError = $e->getMessage();
}

$flashError = trim($_GET['error'] ?? '');
$flashSuccess = trim($_GET['success'] ?? '');
$flashWarn = trim($_GET['warn'] ?? '');

$per_page = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));
$filter_cat = (int) ($_GET['cat'] ?? 0);
$filter_dispo = $_GET['dispo'] ?? '';
$filter_q = trim($_GET['q'] ?? '');

$products = array_values(array_filter($allProducts, static function (array $product) use ($filter_cat, $filter_dispo, $filter_q): bool {
    if ($filter_cat > 0 && (int) ($product['category_id'] ?? 0) !== $filter_cat) {
        return false;
    }
    if ($filter_dispo === '1' && empty($product['is_available'])) {
        return false;
    }
    if ($filter_dispo === '0' && ! empty($product['is_available'])) {
        return false;
    }
    if ($filter_q !== '' && stripos($product['name'] ?? '', $filter_q) === false) {
        return false;
    }

    return true;
}));

$total_products = count($products);
$total_pages = max(1, (int) ceil($total_products / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;
$products = array_slice($products, $offset, $per_page);

$nb_indispo = count(array_filter($allProducts, static fn (array $product): bool => empty($product['is_available'])));
$nb_total = count($allProducts);
$nb_featured = count(array_filter($allProducts, static fn (array $product): bool => ! empty($product['is_featured'])));

$build_url = function($extra = []) use ($filter_cat, $filter_dispo, $filter_q) {
    $p = array_merge(['cat' => $filter_cat, 'dispo' => $filter_dispo, 'q' => $filter_q], $extra);
    $filtered = array_filter($p, function($v) { return $v !== '' && $v !== 0; });
    return 'products.php?' . http_build_query($filtered);
};
?>

<div class="ph">
  <div class="ph-left">
    <h1>Produits SaaS</h1>
    <p><?= $nb_total ?> service(s) — <?= $nb_indispo ?> indisponible(s)</p>
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

<?php if ($listError !== ''): ?>
<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#f87171;margin-bottom:16px">
  Impossible de charger les produits depuis l'API : <?= htmlspecialchars($listError) ?>
</div>
<?php endif; ?>

<!-- ALERTE STOCK -->
<?php if ($nb_indispo > 0): ?>
<div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:12px">
  <div style="flex:1">
    <div style="font-size:.85rem;font-weight:700;color:#fbbf24"><?= $nb_indispo ?> service(s) indisponible(s)</div>
    <div style="font-size:.75rem;color:rgba(245,158,11,.6);margin-top:2px">Ces services sont masqués pour les clients. Pensez à les réactiver.</div>
  </div>
  <a href="<?= $build_url(['dispo' => '0']) ?>" style="font-size:.75rem;font-weight:600;color:#fbbf24;text-decoration:none;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:5px 12px">Voir les indispos →</a>
</div>
<?php endif; ?>

<!-- KPI -->
<div class="row g-3 mb-4">
  <div class="col-4">
    <div class="stat-card">
      <div class="stat-icon">⬡</div>
      <div class="stat-info"><div class="stat-val"><?= $nb_total ?></div><div class="stat-lbl">Total services</div></div>
    </div>
  </div>
  <div class="col-4">
    <div class="stat-card" style="<?= $nb_indispo > 0 ? 'border-color:rgba(245,158,11,.3)' : '' ?>">
      <div class="stat-icon" style="<?= $nb_indispo > 0 ? 'background:rgba(245,158,11,.15);color:#fbbf24' : '' ?>"></div>
      <div class="stat-info"><div class="stat-val" style="<?= $nb_indispo > 0 ? 'color:#fbbf24' : '' ?>"><?= $nb_indispo ?></div><div class="stat-lbl">Indisponibles</div></div>
    </div>
  </div>
  <div class="col-4">
    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-info"><div class="stat-val"><?= $nb_featured ?></div><div class="stat-lbl">Mis en avant</div></div>
    </div>
  </div>
</div>

<!-- FORMULAIRE AJOUT -->
<div class="card mb-3">
  <div class="card-head">Nouveau produit / service</div>
  <div class="card-body">
    <form method="POST" action="product_save.php" enctype="multipart/form-data" data-cyna-validate="admin-product">
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label">Nom du service *</label>
          <input class="form-control" name="name" required placeholder="Ex : Cyna EDR Pro">
        </div>
        <div class="col-md-3">
          <label class="form-label">Catégorie *</label>
          <select class="form-select" name="category_id" required <?= !$cats ? 'disabled' : '' ?>>
            <option value="" disabled selected>— Choisir —</option>
            <?php foreach ($cats as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!$cats): ?>
            <small style="color:#f87171">Créez d'abord une catégorie.</small>
          <?php endif; ?>
        </div>
        <div class="col-md-2">
          <label class="form-label">Prix mensuel (€)</label>
          <input class="form-control" name="price_monthly" type="number" step="0.01" value="0.00">
        </div>
        <div class="col-md-2">
          <label class="form-label">Prix annuel (€)</label>
          <input class="form-control" name="price_yearly" type="number" step="0.01" value="0.00">
        </div>
        <div class="col-md-1">
          <label class="form-label">Dispo</label>
          <select class="form-select" name="is_available">
            <option value="1">Oui</option>
            <option value="0">Non</option>
          </select>
        </div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-12">
          <label class="form-label">Description du service</label>
          <textarea class="form-control" name="description" rows="4"
                    placeholder="Décrivez les fonctionnalités, avantages et valeur pour les entreprises..."></textarea>
        </div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-12">
          <label class="form-label">Caractéristiques techniques</label>
          <textarea class="form-control" name="technical_specs" rows="5"
                    placeholder="Une caractéristique par ligne&#10;Ex : Surveillance 24/7&#10;Ex : Intégration SOC"></textarea>
          <small style="color:var(--c-muted2)">Une ligne = une puce sur la fiche produit.</small>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Image Cloudinary</label>
          <input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp">
          <small style="color:var(--c-muted2)">JPG, PNG ou WEBP — max 10 Mo</small>
        </div>
        <div class="col-md-2">
          <label class="form-label">Stock</label>
          <input class="form-control" name="stock" type="number" min="0" step="1" value="100">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ordre d'affichage</label>
          <input class="form-control" name="featured_order" type="number" value="0">
        </div>
        <div class="col-md-2">
          <label class="form-label">Mis en avant</label>
          <select class="form-select" name="is_featured">
            <option value="0">Non</option>
            <option value="1">Oui</option>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn-cyna w-100">+ Ajouter le service</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- FILTRES -->
<form method="GET" action="products.php" style="margin-bottom:14px">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="search" name="q" value="<?= htmlspecialchars($filter_q) ?>"
      placeholder="Rechercher un service..."
      style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 13px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:220px">
    <select name="cat" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none">
      <option value="0">Toutes catégories</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $filter_cat===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="dispo" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none">
      <option value="">Tous</option>
      <option value="1" <?= $filter_dispo==='1'?'selected':'' ?>>Disponibles</option>
      <option value="0" <?= $filter_dispo==='0'?'selected':'' ?>>Indisponibles</option>
    </select>
    <button type="submit" class="btn-cyna" style="padding:8px 18px;font-size:.83rem">Filtrer</button>
    <?php if ($filter_q || $filter_cat || $filter_dispo !== ''): ?>
      <a href="products.php" class="btn-ghost" style="padding:7px 14px;font-size:.8rem">Reset</a>
    <?php endif; ?>
  </div>
</form>

<!-- TABLE -->
<div class="card">
  <div class="card-head" style="justify-content:space-between;gap:12px;flex-wrap:wrap">
    <span>Liste des produits</span>
    <div id="bulk-actions" class="bulk-actions" hidden>
      <span id="bulk-count" class="bulk-count">0 sélectionné(s)</span>
      <button type="button" class="btn-del js-bulk-delete">Supprimer la sélection</button>
    </div>
  </div>
  <div class="table-scroll">
  <table class="ctable" id="products-table">
    <thead>
      <tr>
        <th class="col-check">
          <input type="checkbox" id="select-all-products" class="product-check" title="Tout sélectionner sur cette page" aria-label="Tout sélectionner">
        </th>
        <th>Service</th>
        <th>Catégorie</th>
        <th>Prix mensuel</th>
        <th>Prix annuel</th>
        <th>Stock</th>
        <th>Statut</th>
        <th>Mis en avant</th>
        <th class="text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$products): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="icon">⬡</div><p>Aucun produit trouvé</p></div></td></tr>
      <?php else: foreach ($products as $p): ?>
      <tr style="<?= !$p['is_available'] ? 'opacity:.65' : '' ?>">
        <td class="col-check">
          <input type="checkbox" class="product-check js-product-select"
                 value="<?= (int)$p['id'] ?>"
                 data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>"
                 aria-label="Sélectionner <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>">
        </td>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <?php if ($p['image_path']): ?>
              <img src="<?= htmlspecialchars(image_display_src($p['image_path'] ?? null, '../')) ?>" style="width:32px;height:32px;border-radius:6px;object-fit:cover" onerror="this.style.display='none'">
            <?php endif; ?>
            <div>
              <div style="font-weight:600;color:#fff;font-size:.84rem"><?= htmlspecialchars($p['name']) ?></div>
              <?php if (!$p['is_available']): ?>
                <span style="font-size:.65rem;background:rgba(245,158,11,.12);color:#fbbf24;border:1px solid rgba(245,158,11,.2);border-radius:20px;padding:1px 7px">Indisponible</span>
              <?php endif; ?>
            </div>
          </div>
        </td>
        <td class="muted"><?= htmlspecialchars($p['cat_name'] ?? '—') ?></td>
        <td style="font-weight:600;color:#fff"><?= number_format((float)$p['price_monthly'],2,',',' ') ?> €</td>
        <td class="muted"><?= number_format((float)$p['price_yearly'],2,',',' ') ?> €</td>
        <td>
          <?php $stock = (int)($p['stock'] ?? 0); ?>
          <span class="badge <?= $stock > 0 ? 'badge-blue' : 'badge-yellow' ?>"><?= $stock ?></span>
        </td>
        <td>
          <?php if ($p['is_available'] && (int)($p['stock'] ?? 0) > 0): ?>
            <span class="badge badge-green">● Disponible</span>
          <?php elseif (!$p['is_available']): ?>
            <span class="badge badge-yellow">Indisponible</span>
          <?php else: ?>
            <span class="badge badge-yellow">Rupture de stock</span>
          <?php endif; ?>
        </td>
        <td><?= $p['is_featured'] ? '<span class="badge badge-blue">Oui</span>' : '<span class="muted">—</span>' ?></td>
        <td class="text-right actions-cell">
          <div class="row-actions">
            <a href="product_edit.php?id=<?= (int)$p['id'] ?>" class="btn-edit">Modifier</a>
            <button type="button" class="btn-del js-delete-product"
                    data-id="<?= (int)$p['id'] ?>"
                    data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>">
              Supprimer
            </button>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>

  <!-- PAGINATION -->
  <?php if ($total_pages > 1): ?>
  <div style="padding:16px 20px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div style="font-size:.78rem;color:var(--c-muted)">
      Page <?= $page ?> / <?= $total_pages ?> — <?= $total_products ?> produit(s)
    </div>
    <div style="display:flex;gap:4px">
      <?php if ($page > 1): ?>
        <a href="<?= $build_url(['page' => $page-1]) ?>" class="btn-view">← Préc</a>
      <?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
        <a href="<?= $build_url(['page' => $i]) ?>"
           style="padding:5px 12px;border-radius:6px;font-size:.78rem;text-decoration:none;<?= $i===$page ? 'background:var(--grad);color:#fff' : 'background:rgba(255,255,255,.06);color:#8b92a8' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
      <?php if ($page < $total_pages): ?>
        <a href="<?= $build_url(['page' => $page+1]) ?>" class="btn-view">Suiv →</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<div id="delete-modal" class="admin-modal" aria-hidden="true" hidden>
  <div class="admin-modal-backdrop" data-close-modal></div>
  <div class="admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
    <div class="admin-modal-head">
      <h2 id="delete-modal-title">Supprimer le produit</h2>
      <button type="button" class="admin-modal-close" data-close-modal aria-label="Fermer">&times;</button>
    </div>
    <div class="admin-modal-body">
      <p id="delete-modal-message"></p>
      <ul id="delete-modal-list" class="delete-modal-list" hidden></ul>
      <p style="font-size:.8rem;color:var(--c-muted2);margin:0">Cette action est irréversible.</p>
    </div>
    <div class="admin-modal-foot">
      <button type="button" class="btn-ghost" data-close-modal>Annuler</button>
      <form method="POST" action="product_delete.php" id="delete-product-form" style="margin:0">
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
  .product-check {
    width: 16px; height: 16px; cursor: pointer; accent-color: var(--c-cyan);
  }
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
  .admin-modal-head h2 {
    margin: 0; font-size: 1rem; font-weight: 700; color: #fff;
  }
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
  var selectAll = document.getElementById('select-all-products');

  if (!modal || !titleEl || !messageEl || !listEl || !fieldsEl) {
    return;
  }

  document.body.appendChild(modal);

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
  }

  function getSelectedProducts() {
    return Array.prototype.map.call(
      document.querySelectorAll('.js-product-select:checked'),
      function (cb) {
        return { id: cb.value, name: cb.getAttribute('data-name') || '' };
      }
    );
  }

  function updateBulkBar() {
    var selected = getSelectedProducts();
    var count = selected.length;

    if (bulkActions) {
      bulkActions.hidden = count === 0;
    }
    if (bulkCount) {
      bulkCount.textContent = count + ' sélectionné' + (count > 1 ? 's' : '');
    }
    if (selectAll) {
      var boxes = document.querySelectorAll('.js-product-select');
      selectAll.checked = count > 0 && count === boxes.length;
      selectAll.indeterminate = count > 0 && count < boxes.length;
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
    titleEl.textContent = 'Supprimer le produit';
    messageEl.innerHTML = 'Voulez-vous vraiment supprimer <strong style="color:#fff">' + escapeHtml(name) + '</strong>&nbsp;?';
    listEl.hidden = true;
    listEl.innerHTML = '';
    setFormFields([{ id: id }], 'id');
    openModal();
  }

  function openBulkDeleteModal(items) {
    var count = items.length;
    titleEl.textContent = 'Supprimer les produits sélectionnés';
    messageEl.textContent = 'Voulez-vous vraiment supprimer ' + count + ' produit' + (count > 1 ? 's' : '') + ' ?';

    var visible = items.slice(0, 8);
    var html = visible.map(function (item) {
      return '<li>' + escapeHtml(item.name) + '</li>';
    }).join('');

    if (items.length > 8) {
      html += '<li class="muted">… et ' + (items.length - 8) + ' autre(s)</li>';
    }

    listEl.innerHTML = html;
    listEl.hidden = false;
    setFormFields(items, 'ids[]');
    openModal();
  }

  document.addEventListener('change', function (e) {
    if (e.target.classList.contains('js-product-select')) {
      updateBulkBar();
    }
    if (e.target.id === 'select-all-products') {
      var checked = e.target.checked;
      document.querySelectorAll('.js-product-select').forEach(function (cb) {
        cb.checked = checked;
      });
      updateBulkBar();
    }
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.js-delete-product')) {
      e.preventDefault();
      var btn = e.target.closest('.js-delete-product');
      openSingleDeleteModal(btn.getAttribute('data-id'), btn.getAttribute('data-name'));
      return;
    }
    if (e.target.closest('.js-bulk-delete')) {
      e.preventDefault();
      var selected = getSelectedProducts();
      if (selected.length === 0) {
        return;
      }
      openBulkDeleteModal(selected);
      return;
    }
    if (e.target.closest('[data-close-modal]')) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) {
      closeModal();
    }
  });

  updateBulkBar();
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>