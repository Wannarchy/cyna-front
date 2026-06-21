<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$success = '';
$error = '';
$codes = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = $_POST['type'] === 'fixed' ? 'fixed' : 'percent';
    $value = (float) $_POST['value'];
    $min_amount = (float) ($_POST['min_amount'] ?? 0);
    $max_uses = $_POST['max_uses'] !== '' ? (int) $_POST['max_uses'] : null;
    $expires_at = $_POST['expires_at'] !== '' ? $_POST['expires_at'] : null;

    if ($code === '') {
        $error = 'Le code est requis.';
    } elseif ($value <= 0) {
        $error = 'La valeur doit être > 0.';
    } else {
        try {
            admin_api()->adminCreatePromoCode([
                'code' => $code,
                'type' => $type,
                'value' => $value,
                'min_amount' => $min_amount,
                'max_uses' => $max_uses,
                'expires_at' => $expires_at,
                'is_active' => true,
            ]);
            $success = "Code promo '$code' créé avec succès !";
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $id = (int) $_POST['id'];
    try {
        foreach (admin_api()->adminGetPromoCodes() as $promo) {
            if ((int) ($promo['id'] ?? 0) === $id) {
                admin_api()->adminUpdatePromoCode($id, [
                    'is_active' => empty($promo['is_active']),
                ]);
                break;
            }
        }
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int) $_POST['id'];
    try {
        admin_api()->adminDeletePromoCode($id);
        $success = 'Code supprimé.';
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

try {
    $codes = admin_api()->adminGetPromoCodes();
} catch (RuntimeException $e) {
    $error = $error ?: $e->getMessage();
}

$nb_actifs = count(array_filter($codes, static fn (array $code): bool => ! empty($code['is_active'])));
$nb_utilises = array_sum(array_map(static fn (array $code): int => (int) ($code['uses_count'] ?? 0), $codes));

$per_page = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));
$total_codes = count($codes);
$total_pages = max(1, (int) ceil($total_codes / $per_page));
$page = min($page, $total_pages);
$codes_page = array_slice($codes, ($page - 1) * $per_page, $per_page);

$build_promo_url = static function (array $extra = []): string {
    $params = array_filter($extra, static fn ($v): bool => $v !== '' && $v !== 0);

    return 'promo_codes.php'.($params !== [] ? '?'.http_build_query($params) : '');
};
?>

<div class="ph">
  <div class="ph-left">
    <h1>Codes promotionnels</h1>
    <p><?= count($codes) ?> code(s) — <?= $nb_actifs ?> actif(s) — <?= $nb_utilises ?> utilisation(s)</p>
  </div>
</div>

<?php if ($success): ?><div class="alert-ok" style="margin-bottom:16px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px 16px;color:#4ade80"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-err" style="margin-bottom:16px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;color:#f87171"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card mb-3">
  <div class="card-head">Créer un code promo</div>
  <div class="card-body">
    <form method="POST" data-cyna-validate="admin-promo">
      <input type="hidden" name="action" value="create">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label">Code *</label>
          <input class="form-control" name="code" required placeholder="SUMMER25" style="text-transform:uppercase">
        </div>
        <div class="col-md-2">
          <label class="form-label">Type</label>
          <select class="form-select" name="type">
            <option value="percent">% Pourcentage</option>
            <option value="fixed">€ Montant fixe</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Valeur *</label>
          <input class="form-control" name="value" type="number" step="0.01" min="0.01" required placeholder="10">
        </div>
        <div class="col-md-2">
          <label class="form-label">Montant min (€)</label>
          <input class="form-control" name="min_amount" type="number" step="0.01" value="0">
        </div>
        <div class="col-md-2">
          <label class="form-label">Utilisations max</label>
          <input class="form-control" name="max_uses" type="number" min="1" placeholder="Illimité">
        </div>
        <div class="col-md-2">
          <label class="form-label">Expire le</label>
          <input class="form-control" name="expires_at" type="date">
        </div>
      </div>
      <div style="margin-top:14px">
        <button type="submit" class="btn-cyna">+ Créer le code</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-scroll">
  <table class="ctable">
    <thead>
      <tr>
        <th>Code</th>
        <th>Remise</th>
        <th>Min panier</th>
        <th>Utilisations</th>
        <th>Expire le</th>
        <th>Statut</th>
        <th class="text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$codes_page): ?>
        <tr><td colspan="7"><div class="empty-state"><div class="icon"></div><p>Aucun code promo</p></div></td></tr>
      <?php else: foreach ($codes_page as $c):
        $expiresAt = $c['expires_at'] ?? null;
        $expiresDate = $expiresAt ? substr((string) $expiresAt, 0, 10) : null;
        $expired = $expiresDate && $expiresDate < date('Y-m-d');
        $maxed = ! empty($c['max_uses']) && (int) ($c['uses_count'] ?? 0) >= (int) $c['max_uses'];
      ?>
      <tr>
        <td><code style="background:rgba(38,208,206,.1);color:#26d0ce;border:1px solid rgba(38,208,206,.2);border-radius:6px;padding:3px 10px;font-size:.85rem;font-weight:700"><?= htmlspecialchars($c['code']) ?></code></td>
        <td style="font-weight:700;color:#fff">
          <?= ($c['type'] ?? '') === 'percent' ? '-'.number_format((float)$c['value'],0).'%' : '-'.number_format((float)$c['value'],2,',',' ').' €' ?>
        </td>
        <td class="muted"><?= ((float)($c['min_amount'] ?? 0)) > 0 ? number_format((float)$c['min_amount'],2,',',' ').' €' : '—' ?></td>
        <td class="muted"><?= (int)($c['uses_count'] ?? 0) ?><?= ! empty($c['max_uses']) ? ' / '.(int)$c['max_uses'] : ' / ∞' ?></td>
        <td class="muted">
          <?php if ($expiresDate): ?>
            <span style="<?= $expired ? 'color:#f87171' : '' ?>"><?= date('d/m/Y', strtotime($expiresDate)) ?><?= $expired ? ' (expiré)' : '' ?></span>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td>
          <?= (! empty($c['is_active']) && ! $expired && ! $maxed) ? '<span class="badge badge-green">● Actif</span>' : '<span class="badge badge-red">● Inactif</span>' ?>
        </td>
        <td class="text-right">
          <div style="display:flex;gap:6px;justify-content:flex-end">
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button type="submit" class="btn-view"><?= ! empty($c['is_active']) ? 'Désactiver' : '▶ Activer' ?></button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce code ?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button type="submit" class="btn-del">Suppr.</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
  <?php admin_render_pagination($page, $total_pages, $total_codes, 'code(s)', $build_promo_url, true); ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
