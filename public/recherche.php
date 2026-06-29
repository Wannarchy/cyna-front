<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/search_repository.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/public_layout.php';

$q           = trim($_GET['q']           ?? '');
$cat_id      = (int)($_GET['cat_id']     ?? 0);
$price_min   = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? (float)$_GET['price_min'] : null;
$price_max   = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (float)$_GET['price_max'] : null;
$dispo_only  = isset($_GET['dispo']) && $_GET['dispo'] === '1';
$sort        = $_GET['sort'] ?? 'pertinence';

$categories = search_get_categories();
$allProducts = search_get_products(true);
$filtered = search_filter_products($allProducts, [
    'q' => $q,
    'cat_id' => $cat_id,
    'price_min' => $price_min,
    'price_max' => $price_max,
    'dispo_only' => $dispo_only,
    'sort' => $sort,
]);

$per_page      = 12;
$page          = max(1, (int)($_GET['page'] ?? 1));
$total_results = count($filtered);
$total_pages   = max(1, (int) ceil($total_results / $per_page));
$page          = min($page, $total_pages);
$offset        = ($page - 1) * $per_page;
$products      = array_slice($filtered, $offset, $per_page);

[$global_min, $global_max] = search_price_bounds($allProducts);

$est_connecte = isset($_SESSION['utilisateur_id']);

// Labels
$lbl_search_ph   = $lang==='en'?'Search a service (SOC, EDR, XDR…)':($lang==='ar'?'ابحث عن خدمة (SOC، EDR، XDR…)':($lang==='he'?'חפש שירות (SOC, EDR, XDR…)':'Rechercher un service (SOC, EDR, XDR…)'));
$lbl_search_btn  = $lang==='en'?'Search':($lang==='ar'?'بحث':($lang==='he'?'חפש':'Rechercher'));
$lbl_cart        = $lang==='en'?'Cart':($lang==='ar'?' السلة':($lang==='he'?' עגלה':'Panier'));
$lbl_filters     = $lang==='en'?'Filters':($lang==='ar'?'الفلاتر':($lang==='he'?'מסננים':'Filtres'));
$lbl_filters_btn = $lang==='en'?'Filters':($lang==='ar'?' فلاتر':($lang==='he'?' מסננים':'Filtres'));
$lbl_category    = $lang==='en'?'Category':($lang==='ar'?'الفئة':($lang==='he'?'קטגוריה':'Catégorie'));
$lbl_all_cats    = $lang==='en'?'All categories':($lang==='ar'?'جميع الفئات':($lang==='he'?'כל הקטגוריות':'Toutes les catégories'));
$lbl_price       = $lang==='en'?'Monthly price (€)':($lang==='ar'?'السعر الشهري (€)':($lang==='he'?'מחיר חודשי (€)':'Prix mensuel (€)'));
$lbl_apply       = $lang==='en'?'Apply':($lang==='ar'?'تطبيق':($lang==='he'?'החל':'Appliquer'));
$lbl_dispo_only  = $lang==='en'?'Available services only':($lang==='ar'?'الخدمات المتاحة فقط':($lang==='he'?'שירותים זמינים בלבד':'Services disponibles uniquement'));
$lbl_availability= $lang==='en'?'Availability':($lang==='ar'?'التوفر':($lang==='he'?'זמינות':'Disponibilité'));
$lbl_reset       = $lang==='en'?'Reset filters':($lang==='ar'?'إعادة تعيين الفلاتر':($lang==='he'?'אפס מסננים':'Réinitialiser les filtres'));
$lbl_res_for     = $lang==='en'?'Results for':($lang==='ar'?'نتائج لـ':($lang==='he'?'תוצאות עבור':'Résultats pour'));
$lbl_all_svc     = $lang==='en'?'All services':($lang==='ar'?'جميع الخدمات':($lang==='he'?'כל השירותים':'Tous les services'));
$lbl_results_ct  = $lang==='en'?'result(s) found':($lang==='ar'?'نتيجة(نتائج)':($lang==='he'?'תוצאה(ות) נמצאה':'résultat(s) trouvé(s)'));
$lbl_page        = $lang==='en'?'page':($lang==='ar'?'صفحة':($lang==='he'?'עמוד':'page'));
$lbl_sort_rel    = $lang==='en'?'Relevance':($lang==='ar'?'الصلة':($lang==='he'?'רלוונטיות':'Pertinence'));
$lbl_sort_asc    = $lang==='en'?'Price ascending':($lang==='ar'?'السعر تصاعدي':($lang==='he'?'מחיר עולה':'Prix croissant'));
$lbl_sort_desc   = $lang==='en'?'Price descending':($lang==='ar'?'السعر تنازلي':($lang==='he'?'מחיר יורד':'Prix décroissant'));
$lbl_sort_new    = $lang==='en'?'Newest':($lang==='ar'?'الأحدث':($lang==='he'?'חדש ביותר':'Plus récents'));
$lbl_sort_dispo  = $lang==='en'?'Availability':($lang==='ar'?'التوفر':($lang==='he'?'זמינות':'Disponibilité'));
$lbl_no_results  = $lang==='en'?'No results':($lang==='ar'?'لا توجد نتائج':($lang==='he'?'אין תוצאות':'Aucun résultat'));
$lbl_no_match    = $lang==='en'?'No service matches':($lang==='ar'?'لا توجد خدمة تطابق':($lang==='he'?'אין שירות תואם':'Aucun service ne correspond à'));
$lbl_try_other   = $lang==='en'?'Try another term or remove filters.':($lang==='ar'?'جرب مصطلحاً آخر أو أزل الفلاتر.':($lang==='he'?'נסה מונח אחר או הסר מסננים.':'Essayez un autre terme ou supprimez les filtres.'));
$lbl_no_filter   = $lang==='en'?'No service matches the selected filters.':($lang==='ar'?'لا توجد خدمة تطابق الفلاتر المحددة.':($lang==='he'?'אין שירות התואם לפילטרים שנבחרו.':'Aucun service ne correspond aux filtres sélectionnés.'));
$lbl_see_all     = $lang==='en'?'View all services →':($lang==='ar'?'عرض جميع الخدمات →':($lang==='he'?'צפה בכל השירותים →':'Voir tous les services →'));
$lbl_from        = $lang==='en'?'From':($lang==='ar'?'من':($lang==='he'?'החל מ-':'À partir de'));
$lbl_per_mo      = $lang==='en'?'/ mo':($lang==='ar'?'/ شهر':($lang==='he'?'/ חודש':'/ mois'));
$lbl_per_yr      = $lang==='en'?'/ yr':($lang==='ar'?'/ سنة':($lang==='he'?'/ שנה':'/ an'));
$lbl_avail       = $lang==='en'?'● Available':($lang==='ar'?'● متاح':($lang==='he'?'● זמין':'● Disponible'));
$lbl_unavail     = $lang==='en'?'● Unavailable':($lang==='ar'?'● غير متاح':($lang==='he'?'● לא זמין':'● Indisponible'));
$lbl_see_offer   = $lang==='en'?'View offer →':($lang==='ar'?'عرض العرض →':($lang==='he'?'צפה בהצעה →':'Voir l\'offre →'));
$lbl_see_anyway  = $lang==='en'?'View anyway →':($lang==='ar'?'عرض على أي حال →':($lang==='he'?'צפה בכל זאת →':'Voir quand même →'));
$lbl_prev        = $lang==='en'?'← Previous':($lang==='ar'?'← السابق':($lang==='he'?'← הקודם':'← Précédent'));
$lbl_next        = $lang==='en'?'Next →':($lang==='ar'?'التالي →':($lang==='he'?'הבא →':'Suivant →'));
$lbl_clear       = $lang==='en'?'Clear':($lang==='ar'?' مسح':($lang==='he'?' נקה':'Effacer'));
$lbl_dispo_tag   = $lang==='en'?'Available only':($lang==='ar'?' المتاح فقط':($lang==='he'?' זמין בלבד':'Disponibles seulement'));
$lbl_min_tag     = $lang==='en'?'€ min:':($lang==='ar'?'€ الحد الأدنى:':($lang==='he'?'€ מינ׳:':'€ min :'));
$lbl_max_tag     = $lang==='en'?'€ max:':($lang==='ar'?'€ الحد الأقصى:':($lang==='he'?'€ מקס׳:':'€ max :'));
$lbl_min_ph      = $lang==='en'?'Min':($lang==='ar'?'الأدنى':($lang==='he'?'מינ\'':'Min'));
$lbl_max_ph      = $lang==='en'?'Max':($lang==='ar'?'الأقصى':($lang==='he'?'מקס\'':'Max'));

$searchTitle = ($lang==='en'?'Search':($lang==='ar'?'بحث':($lang==='he'?'חיפוש':'Recherche'))).($q !== '' ? ' : '.$q : '');
cyna_public_head($searchTitle, 'recherche');
cyna_public_nav(true);
?>
<div class="cyna-search-layout">
  <aside class="cyna-filters-panel filters" id="filters-panel">
    <form method="GET" action="recherche.php" id="filter-form">
      <?php if ($q !== ''): ?>
      <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
      <?php endif; ?>
      <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
      <h2><?= $lbl_filters ?></h2>

      <!-- Catégorie -->
      <div class="filter-section">
        <div class="filter-title"><?= $lbl_category ?></div>
        <label class="cat-option <?= $cat_id===0?'active-cat':'' ?>">
          <input type="radio" name="cat_id" value="0" <?= $cat_id===0?' checked':'' ?> onchange="this.form.submit()">
          <?= $lbl_all_cats ?>
        </label>
        <?php foreach ($categories as $cat): ?>
        <label class="cat-option <?= $cat_id===(int)$cat['id']?'active-cat':'' ?>">
          <input type="radio" name="cat_id" value="<?= (int)$cat['id'] ?>"
                 <?= $cat_id===(int)$cat['id']?' checked':'' ?> onchange="this.form.submit()">
          <?= htmlspecialchars($cat['name']) ?>
        </label>
        <?php endforeach; ?>
      </div>

      <!-- Prix -->
      <div class="filter-section">
        <div class="filter-title"><?= $lbl_price ?></div>
        <div class="d-flex gap-2">
          <input class="filter-input" type="number" name="price_min" placeholder="<?= $lbl_min_ph ?>"
                 value="<?= $price_min !== null ? $price_min : '' ?>" min="0" step="1" style="width:50%">
          <input class="filter-input" type="number" name="price_max" placeholder="<?= $lbl_max_ph ?>"
                 value="<?= $price_max !== null ? $price_max : '' ?>" min="0" step="1" style="width:50%">
        </div>
        <button type="submit" style="width:100%;margin-top:8px;background:var(--grad);border:none;color:#fff;border-radius:8px;padding:7px;font-size:.78rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;">
          <?= $lbl_apply ?>
        </button>
      </div>

      <!-- Disponibilité -->
      <div class="filter-section">
        <div class="filter-title"><?= $lbl_availability ?></div>
        <label class="toggle-dispo">
          <span><?= $lbl_dispo_only ?></span>
          <input type="checkbox" name="dispo" value="1" <?= $dispo_only?' checked':'' ?> onchange="this.form.submit()">
        </label>
      </div>

      <a href="recherche.php" class="btn-reset"><?= $lbl_reset ?></a>
    </form>
  </aside>

  <!-- RÉSULTATS -->
  <main class="results">
    <div class="results-header">
      <div>
        <div class="results-title">
          <?php if ($q !== ''): ?>
          <?= $lbl_res_for ?> <span style="color:var(--cyan)">"<?= htmlspecialchars($q) ?>"</span>
          <?php else: ?>
          <?= $lbl_all_svc ?>
          <?php endif; ?>
        </div>
        <div class="results-count"><?= $total_results ?> <?= $lbl_results_ct ?><?= $total_pages > 1 ? ' — '.$lbl_page.' '.$page.'/'.$total_pages : '' ?></div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="cyna-filter-toggle btn btn-sm btn-outline-light" type="button" onclick="document.getElementById('filters-panel').classList.toggle('is-mobile-open')">
          <?= $lbl_filters_btn ?>
        </button>
        <form method="GET" action="recherche.php" id="sort-form">
          <?php if ($q !== ''):   ?><input type="hidden" name="q"         value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
          <?php if ($cat_id):     ?><input type="hidden" name="cat_id"    value="<?= $cat_id ?>"><?php endif; ?>
          <?php if ($price_min):  ?><input type="hidden" name="price_min" value="<?= $price_min ?>"><?php endif; ?>
          <?php if ($price_max):  ?><input type="hidden" name="price_max" value="<?= $price_max ?>"><?php endif; ?>
          <?php if ($dispo_only): ?><input type="hidden" name="dispo"     value="1"><?php endif; ?>
          <select class="sort-select" name="sort" onchange="document.getElementById('sort-form').submit()">
            <option value="pertinence" <?= $sort==='pertinence'?' selected':'' ?>><?= $lbl_sort_rel ?></option>
            <option value="price_asc"  <?= $sort==='price_asc' ?' selected':'' ?>><?= $lbl_sort_asc ?></option>
            <option value="price_desc" <?= $sort==='price_desc'?' selected':'' ?>><?= $lbl_sort_desc ?></option>
            <option value="newest"     <?= $sort==='newest'    ?' selected':'' ?>><?= $lbl_sort_new ?></option>
            <option value="dispo"      <?= $sort==='dispo'     ?' selected':'' ?>><?= $lbl_sort_dispo ?></option>
          </select>
        </form>
      </div>
    </div>

    <!-- Tags filtres actifs -->
    <?php if ($cat_id || $price_min !== null || $price_max !== null || $dispo_only): ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <?php if ($cat_id):
        $catName = array_values(array_filter($categories, function($c) use ($cat_id) { return (int)$c['id'] === $cat_id; }));
        if ($catName): ?>
      <span style="background:rgba(38,208,206,.12);color:var(--cyan);border:1px solid rgba(38,208,206,.2);padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:500">
        <?= htmlspecialchars($catName[0]['name']) ?>
      </span>
      <?php endif; ?>
      <?php endif; ?>
      <?php if ($price_min !== null): ?>
      <span style="background:rgba(79,140,255,.12);color:#93c5fd;border:1px solid rgba(79,140,255,.2);padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:500">
        <?= $lbl_min_tag ?> <?= $price_min ?> €
      </span>
      <?php endif; ?>
      <?php if ($price_max !== null): ?>
      <span style="background:rgba(79,140,255,.12);color:#93c5fd;border:1px solid rgba(79,140,255,.2);padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:500">
        <?= $lbl_max_tag ?> <?= $price_max ?> €
      </span>
      <?php endif; ?>
      <?php if ($dispo_only): ?>
      <span style="background:rgba(34,197,94,.12);color:#4ade80;border:1px solid rgba(34,197,94,.2);padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:500">
        <?= $lbl_dispo_tag ?>
      </span>
      <?php endif; ?>
      <a href="recherche.php<?= $q!==''?'?q='.urlencode($q):'' ?>" style="color:var(--muted);font-size:.73rem;padding:3px 8px;border-radius:20px;text-decoration:none;border:1px solid var(--border2)">
        <?= $lbl_clear ?>
      </a>
    </div>
    <?php endif; ?>

    <!-- Grille produits -->
    <?php if (count($products) === 0): ?>
    <div class="empty">
      <div class="ico"></div>
      <h3><?= $lbl_no_results ?></h3>
      <p>
        <?php if ($q !== ''): ?>
        <?= $lbl_no_match ?> <strong>"<?= htmlspecialchars($q) ?>"</strong>.<br>
        <?= $lbl_try_other ?>
        <?php else: ?>
        <?= $lbl_no_filter ?>
        <?php endif; ?>
      </p>
      <a href="recherche.php" style="color:var(--cyan);font-size:.85rem"><?= $lbl_see_all ?></a>
    </div>
    <?php else: ?>
    <div class="products-grid">
      <?php foreach ($products as $p):
        $avail = product_is_available($p);
        $statusLabel = product_status_label($p, $lang);
        $name  = htmlspecialchars($p['name']);
        if ($q !== '') {
          $name = preg_replace('/('.preg_quote(htmlspecialchars($q),'/').')/i', '<mark>$1</mark>', $name);
        }
      ?>
      <a href="produit.php?id=<?= (int)$p['id'] ?>" class="prod-card <?= $avail?'':'unavail' ?>">
        <?php if (!empty($p['image_path']) && $p['image_path'] !== 'logo.jpg'): ?>
        <img class="prod-img" src="<?= htmlspecialchars(image_display_src($p['image_path'] ?? null, '../')) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
        <?php else: ?>
        <div class="prod-img-placeholder"></div>
        <?php endif; ?>
        <div class="prod-body">
          <?php if ($p['cat_name']): ?>
          <div class="prod-cat"><?= htmlspecialchars($p['cat_name']) ?></div>
          <?php endif; ?>
          <div class="prod-name"><?= $name ?></div>
          <div class="prod-price">
            <?= $lbl_from ?> <b><?= number_format((float)$p['price_monthly'],2,',',' ') ?> € <?= $lbl_per_mo ?></b>
            <?php if ((float)$p['price_yearly'] > 0): ?>
            · <span style="color:var(--cyan)"><?= number_format((float)$p['price_yearly'],0,',',' ') ?> € <?= $lbl_per_yr ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="prod-foot">
          <?= $avail
            ? '<span class="badge-avail">'.$statusLabel.'</span>'
            : '<span class="badge-unavail">'.$statusLabel.'</span>' ?>
          <span class="prod-cta"><?= $avail ? $lbl_see_offer : $lbl_see_anyway ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1):
      $base_params = array_filter([
        'q'         => $q,
        'cat_id'    => $cat_id ?: '',
        'sort'      => $sort !== 'pertinence' ? $sort : '',
        'price_min' => $price_min !== null ? $price_min : '',
        'price_max' => $price_max !== null ? $price_max : '',
        'dispo'     => $dispo_only ? '1' : '',
      ], function($v) { return $v !== ''; });
    ?>
    <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:28px;flex-wrap:wrap">
      <?php if ($page > 1): ?>
      <a href="recherche.php?<?= http_build_query(array_merge($base_params, ['page' => $page-1])) ?>"
         style="padding:8px 16px;border-radius:9px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);text-decoration:none;font-size:.82rem;font-weight:600">
        <?= $lbl_prev ?>
      </a>
      <?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
      <a href="recherche.php?<?= http_build_query(array_merge($base_params, ['page' => $i])) ?>"
         style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:9px;text-decoration:none;font-size:.85rem;font-weight:700;<?= $i===$page ? 'background:linear-gradient(135deg,#1a2980,#26d0ce);color:#fff' : 'background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6)' ?>">
        <?= $i ?>
      </a>
      <?php endfor; ?>
      <?php if ($page < $total_pages): ?>
      <a href="recherche.php?<?= http_build_query(array_merge($base_params, ['page' => $page+1])) ?>"
         style="padding:8px 16px;border-radius:9px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);text-decoration:none;font-size:.82rem;font-weight:600">
        <?= $lbl_next ?>
      </a>
      <?php endif; ?>
      <div style="width:100%;text-align:center;font-size:.72rem;color:rgba(255,255,255,.3);margin-top:6px">
        <?= $lbl_page ?> <?= $page ?> / <?= $total_pages ?> — <?= $total_results ?> <?= $lbl_results_ct ?>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </main>
</div>
<script>
document.addEventListener('click', function(e) {
  var panel = document.getElementById('filters-panel');
  if (panel.classList.contains('is-mobile-open') && !panel.contains(e.target) && !e.target.closest('.cyna-filter-toggle')) {
    panel.classList.remove('is-mobile-open');
  }
});
</script>
<?php cyna_public_footer(); ?>