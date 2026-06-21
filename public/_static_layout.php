<?php
/**
 * Layout partagé pour les pages statiques (mentions légales, CGU, à propos).
 *
 * @var string $page_title
 * @var list<array{0: string, 1: string}> $sections
 */
if (! isset($sections, $page_title)) {
    http_response_code(500);
    exit('Static layout misconfigured.');
}

require_once __DIR__ . '/../includes/public_layout.php';

cyna_public_head($page_title, 'static');
cyna_public_nav(false);
?>
<div class="cyna-static">
  <h1 class="cyna-page-title"><?= htmlspecialchars($page_title) ?></h1>
  <div class="cyna-static-intro"><?= htmlspecialchars(t('copyright')) ?> — CYNA-IT</div>
  <?php foreach ($sections as $section): ?>
  <div class="cyna-static-section">
    <h2><?= htmlspecialchars($section[0]) ?></h2>
    <p><?= $section[1] ?></p>
  </div>
  <?php endforeach; ?>
</div>
<?php cyna_public_footer(); ?>
