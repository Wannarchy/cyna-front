<?php

/**
 * @return array{root: string, public: string, assets: string, in_public: bool}
 */
function cyna_layout_paths(): array
{
    static $paths = null;

    if ($paths !== null) {
        return $paths;
    }

    $inPublic = str_contains(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? ''), '/public/');
    $paths = [
        'in_public' => $inPublic,
        'root' => $inPublic ? '../' : '',
        'public' => $inPublic ? '' : 'public/',
        'assets' => ($inPublic ? '../' : '').'assets/',
    ];

    return $paths;
}

function cyna_page_css_href(string $page): string
{
    $page = preg_replace('/[^a-z0-9_-]/i', '', $page);
    $p = cyna_layout_paths();

    return $p['assets'].'css/pages/'.$page.'.css';
}

function cyna_public_head(string $title, string $pageCss = '', array $extraCss = []): void
{
    $p = cyna_layout_paths();
    $pageFiles = $pageCss !== '' ? [$pageCss] : [];
    $stylesheets = array_merge($extraCss, $pageFiles);
    ?>
<!doctype html>
<html <?= rtl_attrs() ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CYNA — <?= htmlspecialchars($title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= htmlspecialchars($p['assets']) ?>css/public-mobile.css" rel="stylesheet">
<?php foreach ($stylesheets as $sheet): ?>
<?php if ($sheet !== ''): ?>
<link href="<?= htmlspecialchars(cyna_page_css_href($sheet)) ?>" rel="stylesheet">
<?php endif; ?>
<?php endforeach; ?>
</head>
<body>
    <?php
}

function cyna_public_nav(bool $withSearch = true): void
{
    $p = cyna_layout_paths();
    $est_connecte = isset($_SESSION['utilisateur_id']);
    $nb_panier = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
    $is_admin = ! empty($_SESSION['is_admin']);
    ?>
<nav class="navbar navbar-expand-lg sticky-top cyna-navbar">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= htmlspecialchars($p['root']) ?>index.php">CYNA</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#cynaNav" aria-controls="cynaNav" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse cyna-nav-panel" id="cynaNav">
      <?php if ($withSearch): ?>
      <form class="cyna-search-wrap" action="<?= htmlspecialchars($p['public']) ?>recherche.php" method="GET">
        <span class="search-icon">⌕</span>
        <input type="search" name="q" placeholder="<?= htmlspecialchars(t('nav_catalogue') === 'Catalogue' ? 'Rechercher un service...' : 'Search...') ?>" aria-label="Recherche">
        <button class="search-btn" type="submit">OK</button>
      </form>
      <?php endif; ?>
      <div class="cyna-nav-links ms-lg-auto">
        <a href="<?= htmlspecialchars($p['public']) ?>catalogue.php"><?= htmlspecialchars(t('nav_catalogue')) ?></a>
        <a href="<?= htmlspecialchars($p['public']) ?>recherche.php">Recherche</a>
        <a href="<?= htmlspecialchars($p['public']) ?>panier.php" class="cyna-nav-cart">
          Panier
          <?php if ($nb_panier > 0): ?><span class="badge bg-dark ms-1"><?= (int) $nb_panier ?></span><?php endif; ?>
        </a>
        <?php if ($is_admin): ?>
        <a href="<?= htmlspecialchars($p['root']) ?>admin/index.php">Admin</a>
        <?php endif; ?>
        <?php if ($est_connecte): ?>
        <a href="<?= htmlspecialchars($p['public']) ?>mon-compte.php"><?= htmlspecialchars(t('nav_account')) ?></a>
        <a href="<?= htmlspecialchars($p['public']) ?>deconnexion.php"><?= htmlspecialchars(t('nav_logout')) ?></a>
        <?php else: ?>
        <a href="<?= htmlspecialchars($p['public']) ?>connexion.php"><?= htmlspecialchars(t('nav_login')) ?></a>
        <a href="<?= htmlspecialchars($p['public']) ?>inscription.php" class="cyna-nav-cta"><?= htmlspecialchars(t('nav_register')) ?></a>
        <?php endif; ?>
        <div class="px-2 py-1"><?= lang_switcher() ?></div>
      </div>
    </div>
  </div>
</nav>
    <?php
}

function cyna_public_footer(): void
{
    $p = cyna_layout_paths();
    ?>
<footer class="cyna-footer">
  <div class="cyna-footer-links">
    <a href="<?= htmlspecialchars($p['public']) ?>mention_legales.php"><?= htmlspecialchars(t('legal')) ?></a>
    <a href="<?= htmlspecialchars($p['public']) ?>Cgu.php"><?= htmlspecialchars(t('cgu')) ?></a>
    <a href="<?= htmlspecialchars($p['public']) ?>Contact.php"><?= htmlspecialchars(t('contact')) ?></a>
    <a href="<?= htmlspecialchars($p['public']) ?>a-propos.php"><?= htmlspecialchars(t('about')) ?></a>
  </div>
  <span><?= htmlspecialchars(t('copyright')) ?></span>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    <?php
}
