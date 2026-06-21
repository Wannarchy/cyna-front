<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/catalog_repository.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/public_layout.php';

$cat_id = (int) ($_GET['cat'] ?? $_GET['category_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 9;

$categories = categories_get_all($connexion);

$catalog = catalog_get_products_page($cat_id, $page, $per_page);
$products = $catalog['items'];
$meta = $catalog['meta'];
$total_results = (int) ($meta['total'] ?? count($products));
$total_pages = max(1, (int) ($meta['last_page'] ?? 1));
$page = (int) ($meta['current_page'] ?? $page);
$page = min(max(1, $page), $total_pages);

if ($cat_id > 0) {
    $current_cat = cat_get_by_id($connexion, $cat_id);
} else {
    $current_cat = null;
}

$lbl_per_month = t('per_month');
$lbl_see = t('see_offer');
$lbl_all = t('all_categories');
$lbl_count = t('services_count');
$lbl_none = t('no_product');
$lbl_from = $lang === 'en' ? 'From' : ($lang === 'ar' ? 'من' : ($lang === 'he' ? 'החל מ-' : 'À partir de'));
$lbl_prev = $lang === 'en' ? '← Previous' : ($lang === 'ar' ? '← السابق' : ($lang === 'he' ? '← הקודם' : '← Précédent'));
$lbl_next = $lang === 'en' ? 'Next →' : ($lang === 'ar' ? 'التالي →' : ($lang === 'he' ? 'הבא →' : 'Suivant →'));
$lbl_page = $lang === 'en' ? 'page' : ($lang === 'ar' ? 'صفحة' : ($lang === 'he' ? 'עמוד' : 'page'));

$build_catalog_url = static function (array $extra = []) use ($cat_id): string {
    $params = array_filter(array_merge(['cat' => $cat_id > 0 ? $cat_id : null], $extra), static fn ($v): bool => $v !== null && $v !== '' && $v !== 0);
    if (isset($params['cat']) && (int) $params['cat'] === 0) {
        unset($params['cat']);
    }

    return 'catalogue.php'.($params !== [] ? '?'.http_build_query($params) : '');
};

cyna_public_head(t('catalogue_title'), 'catalogue');
cyna_public_nav(true);
?>
<div class="cyna-page">
  <div class="cyna-page-title"><?= $current_cat ? htmlspecialchars($current_cat['name']) : htmlspecialchars(t('catalogue_title')) ?></div>
  <div class="page-sub"><?= $total_results ?> <?= htmlspecialchars($lbl_count) ?><?= $total_pages > 1 ? ' — '.$lbl_page.' '.$page.'/'.$total_pages : '' ?></div>

  <div class="cat-filters mt-3 mb-3">
    <a href="catalogue.php" class="cat-pill <?= $cat_id === 0 ? 'active' : '' ?>"><?= htmlspecialchars($lbl_all) ?></a>
    <?php foreach ($categories as $cat): ?>
    <a href="catalogue.php?cat=<?= (int) $cat['id'] ?>" class="cat-pill <?= $cat_id === (int) $cat['id'] ? 'active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (count($products) === 0): ?>
  <div class="empty">
    <p><?= htmlspecialchars($lbl_none) ?></p>
    <a href="catalogue.php" style="color:var(--cyan);text-decoration:none"><?= htmlspecialchars($lbl_all) ?> →</a>
  </div>
  <?php else: ?>
  <div class="products-grid">
    <?php foreach ($products as $p):
        $avail = product_is_available($p);
        $statusLabel = product_status_label($p, $lang);
        $catName = is_array($p['category'] ?? null) ? ($p['category']['name'] ?? null) : null;
    ?>
    <a href="produit.php?id=<?= (int) $p['id'] ?>" class="prod-card <?= $avail ? '' : 'unavail' ?>">
      <?php if (! empty($p['image_path']) && $p['image_path'] !== 'logo.jpg'): ?>
      <img class="prod-img" src="<?= htmlspecialchars(image_display_src($p['image_path'] ?? null, '../')) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
      <?php else: ?>
      <div class="prod-img-placeholder"></div>
      <?php endif; ?>
      <div class="prod-body">
        <?php if ($catName): ?><div class="prod-cat"><?= htmlspecialchars($catName) ?></div><?php endif; ?>
        <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="prod-price"><?= htmlspecialchars($lbl_from) ?> <b><?= number_format((float) $p['price_monthly'], 2, ',', ' ') ?> € <?= htmlspecialchars($lbl_per_month) ?></b></div>
      </div>
      <div class="prod-foot">
        <span class="<?= $avail ? 'badge-avail' : 'badge-unavail' ?>"><?= htmlspecialchars($statusLabel) ?></span>
        <span class="prod-cta"><?= htmlspecialchars($lbl_see) ?> →</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if ($total_pages > 1): ?>
  <nav class="catalog-pagination" aria-label="Pagination catalogue">
    <?php if ($page > 1): ?>
    <a href="<?= htmlspecialchars($build_catalog_url(['page' => $page - 1])) ?>" class="page-nav"><?= htmlspecialchars($lbl_prev) ?></a>
    <?php endif; ?>
    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
    <a href="<?= htmlspecialchars($build_catalog_url(['page' => $i])) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?>
    <a href="<?= htmlspecialchars($build_catalog_url(['page' => $page + 1])) ?>" class="page-nav"><?= htmlspecialchars($lbl_next) ?></a>
    <?php endif; ?>
    <div class="catalog-pagination-meta"><?= $lbl_page ?> <?= $page ?> / <?= $total_pages ?></div>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php cyna_public_footer(); ?>
