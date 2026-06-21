<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$success = '';
$error = '';
$users = [];
$orders = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    $uid = (int) $_POST['user_id'];
    $status = (int) $_POST['new_status'];

    try {
        admin_api()->adminUpdateUser($uid, ['est_actif' => $status === 1]);
        $success = $status ? 'Compte activé.' : 'Compte suspendu.';
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $uid = (int) $_POST['user_id'];

    try {
        admin_api()->adminDeleteUser($uid);
        $success = 'Utilisateur supprimé.';
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

try {
    $orders = array_map('admin_order_row', admin_api()->adminGetOrders());
    $users = array_map(static fn (array $user): array => admin_user_row($user, $orders), admin_api()->adminGetUsers());
} catch (RuntimeException $e) {
    $error = $error ?: $e->getMessage();
}

$filter_q = trim($_GET['q'] ?? '');
$filter_status = $_GET['status'] ?? '';

$users = array_values(array_filter($users, static function (array $user) use ($filter_q, $filter_status): bool {
    if ($filter_q !== '') {
        $haystack = strtolower(($user['email'] ?? '').' '.($user['prenom'] ?? '').' '.($user['nom'] ?? ''));
        if (strpos($haystack, strtolower($filter_q)) === false) {
            return false;
        }
    }
    if ($filter_status === 'confirmed' && empty($user['est_confirme'])) {
        return false;
    }
    if ($filter_status === 'unconfirmed' && ! empty($user['est_confirme'])) {
        return false;
    }
    if ($filter_status === 'active' && empty($user['est_actif'])) {
        return false;
    }
    if ($filter_status === 'suspended' && ! empty($user['est_actif'])) {
        return false;
    }

    return true;
}));

$per_page = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$total_users = count($users);
$total_pages = max(1, (int) ceil($total_users / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;
$users_page = array_slice($users, $offset, $per_page);

$build_users_url = static function (array $extra = []) use ($filter_q, $filter_status): string {
    $params = array_merge(['q' => $filter_q, 'status' => $filter_status], $extra);
    $filtered = array_filter($params, static fn ($v): bool => $v !== '' && $v !== 0);

    return 'users.php'.($filtered !== [] ? '?'.http_build_query($filtered) : '');
};

$confirmed_users = count(array_filter($users, static fn (array $user): bool => ! empty($user['est_confirme'])));
$admin_users = count(array_filter($users, static fn (array $user): bool => ! empty($user['is_admin'])));
?>

<div class="ph">
  <div class="ph-left">
    <h1>Utilisateurs</h1>
    <p><?= $total_users ?> compte(s) affiché(s)</p>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-info"><div class="stat-val"><?= $total_users ?></div><div class="stat-lbl">Total utilisateurs</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-info"><div class="stat-val"><?= $confirmed_users ?></div><div class="stat-lbl">Comptes confirmés</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-info"><div class="stat-val"><?= $total_users - $confirmed_users ?></div><div class="stat-lbl">En attente confirmation</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-info"><div class="stat-val"><?= $admin_users ?></div><div class="stat-lbl">Administrateurs</div></div>
    </div>
  </div>
</div>

<?php if ($success): ?>
<div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#4ade80;margin-bottom:16px"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#f87171;margin-bottom:16px"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="GET" action="users.php" style="margin-bottom:16px">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="search" name="q" value="<?= htmlspecialchars($filter_q) ?>" placeholder="Rechercher par nom, email..." style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 13px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:280px">
    <select name="status" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none">
      <option value="" <?= !$filter_status?'selected':'' ?>>Tous les statuts</option>
      <option value="confirmed" <?= $filter_status==='confirmed'?'selected':'' ?>>Email confirmé</option>
      <option value="unconfirmed" <?= $filter_status==='unconfirmed'?'selected':'' ?>>Non confirmé</option>
      <option value="active" <?= $filter_status==='active'?'selected':'' ?>>Actif</option>
      <option value="suspended" <?= $filter_status==='suspended'?'selected':'' ?>>Suspendu</option>
    </select>
    <button type="submit" class="btn-cyna" style="padding:8px 18px;font-size:.83rem">Filtrer</button>
    <?php if ($filter_q || $filter_status): ?>
      <a href="users.php" class="btn-ghost" style="padding:7px 14px;font-size:.8rem">Réinitialiser</a>
    <?php endif; ?>
    <span style="margin-left:auto;font-size:.78rem;color:var(--c-muted)"><?= $total_users ?> résultat(s)</span>
  </div>
</form>

<div class="card">
  <div class="table-scroll">
  <table class="ctable">
    <thead>
      <tr>
        <th>ID</th>
        <th>Utilisateur</th>
        <th>Email</th>
        <th>Statut</th>
        <th>Commandes</th>
        <th>Total dépensé</th>
        <th class="text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$users_page): ?>
        <tr><td colspan="7"><div class="empty-state"><div class="icon"></div><p>Aucun utilisateur trouvé</p></div></td></tr>
      <?php else: foreach ($users_page as $u): ?>
      <tr>
        <td class="mono">#<?= (int)$u['id'] ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;flex-shrink:0">
              <?= strtoupper(substr($u['prenom']??'U',0,1)) ?>
            </div>
            <div>
              <div style="font-weight:500;color:#fff;font-size:.84rem"><?= htmlspecialchars(trim(($u['prenom']??'').' '.($u['nom']??''))) ?: '—' ?></div>
              <?php if ($u['is_admin']): ?><span class="badge badge-blue">Admin</span><?php endif; ?>
            </div>
          </div>
        </td>
        <td class="muted"><?= htmlspecialchars($u['email']) ?></td>
        <td>
          <?= $u['est_confirme'] ? '<span class="badge badge-green">Confirmé</span>' : '<span class="badge badge-yellow">En attente</span>' ?>
          <?= (int)$u['est_actif'] === 0 ? '<span class="badge badge-red">Suspendu</span>' : '<span class="badge badge-gray">Actif</span>' ?>
        </td>
        <td><?= (int)$u['nb_orders'] > 0 ? '<span style="font-weight:600;color:#fff">'.(int)$u['nb_orders'].'</span>' : '<span class="muted">0</span>' ?></td>
        <td style="font-weight:600;color:<?= $u['total_spent'] > 0 ? '#fff' : 'var(--c-muted)' ?>"><?= number_format((float)$u['total_spent'],2,',',' ') ?> €</td>
        <td class="text-right">
          <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap">
            <?php if ($u['nb_orders'] > 0): ?>
              <a href="orders.php?user_id=<?= (int)$u['id'] ?>" class="btn-view" title="Voir les commandes">Cmd.</a>
            <?php endif; ?>
            <?php if (!$u['is_admin']): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <input type="hidden" name="new_status" value="<?= (int)$u['est_actif'] === 0 ? 1 : 0 ?>">
                <?php if ((int)$u['est_actif'] === 0): ?>
                  <button type="submit" class="btn-view" style="background:rgba(34,197,94,.1);color:#4ade80;border-color:rgba(34,197,94,.2)">Activer</button>
                <?php else: ?>
                  <button type="submit" class="btn-del">Suspendre</button>
                <?php endif; ?>
              </form>
              <?php if ($u['nb_orders'] == 0): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer définitivement cet utilisateur ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button type="submit" class="btn-del">Suppr.</button>
              </form>
              <?php endif; ?>
            <?php else: ?>
              <span class="badge badge-blue">Protégé</span>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
  <?php admin_render_pagination($page, $total_pages, $total_users, 'utilisateur(s)', $build_users_url, true); ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
