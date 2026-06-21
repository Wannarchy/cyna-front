<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$filter_q = trim($_GET['q'] ?? '');
$filter_date = trim($_GET['date'] ?? '');
$filter_action = trim($_GET['action'] ?? '');
$filter_target_type = trim($_GET['target_type'] ?? '');
$filter_actor_type = trim($_GET['actor_type'] ?? '');
$filter_admin_id = (int) ($_GET['admin_id'] ?? 0);
$filter_user_id = (int) ($_GET['user_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$success = trim($_GET['success'] ?? '');
$error = trim($_GET['error'] ?? '');
$logs = [];
$meta = [
    'current_page' => 1,
    'last_page' => 1,
    'per_page' => 50,
    'total' => 0,
];
$admins = [];
$usersById = [];

$query = [
    'page' => $page,
    'per_page' => 50,
];
if ($filter_q !== '') {
    $query['q'] = $filter_q;
}
if ($filter_date !== '') {
    $query['date'] = $filter_date;
}
if ($filter_action !== '') {
    $query['action'] = $filter_action;
}
if ($filter_target_type !== '') {
    $query['target_type'] = $filter_target_type;
}
if ($filter_actor_type !== '') {
    $query['actor_type'] = $filter_actor_type;
}
if ($filter_admin_id > 0) {
    $query['admin_id'] = $filter_admin_id;
}
if ($filter_user_id > 0) {
    $query['user_id'] = $filter_user_id;
}

try {
    $response = admin_api()->adminGetLogs($query);
    $rawLogs = $response['data'] ?? [];
    $logs = array_map('admin_log_row', is_array($rawLogs) ? $rawLogs : []);
    $meta = [
        'current_page' => (int) ($response['current_page'] ?? 1),
        'last_page' => (int) ($response['last_page'] ?? 1),
        'per_page' => (int) ($response['per_page'] ?? 50),
        'total' => (int) ($response['total'] ?? count($logs)),
    ];
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}

try {
    $allUsers = admin_api()->adminGetUsers();
    foreach ($allUsers as $user) {
        $uid = (int) ($user['id'] ?? 0);
        if ($uid <= 0) {
            continue;
        }
        $usersById[$uid] = [
            'is_admin' => ! empty($user['is_admin']),
            'bloquer' => ! empty($user['bloquer']),
        ];
    }
    $admins = array_values(array_filter(
        $allUsers,
        static fn (array $user): bool => ! empty($user['is_admin'])
    ));
} catch (RuntimeException) {
}

$build_url = static function (array $extra = []) use ($filter_q, $filter_date, $filter_action, $filter_target_type, $filter_actor_type, $filter_admin_id, $filter_user_id): string {
    $params = array_merge([
        'q' => $filter_q,
        'date' => $filter_date,
        'action' => $filter_action,
        'target_type' => $filter_target_type,
        'actor_type' => $filter_actor_type,
        'admin_id' => $filter_admin_id,
        'user_id' => $filter_user_id,
    ], $extra);
    $filtered = array_filter($params, static fn ($value): bool => $value !== '' && $value !== 0);

    return 'audit_logs.php?'.http_build_query($filtered);
};

function audit_action_label(string $action): string
{
    return match ($action) {
        'user.create' => 'Création utilisateur',
        'user.update' => 'Modification utilisateur',
        'user.delete' => 'Suppression utilisateur',
        'user.block_toggle' => 'Blocage utilisateur',
        'product.create' => 'Création produit',
        'product.update' => 'Modification produit',
        'product.delete' => 'Suppression produit',
        'category.create' => 'Création catégorie',
        'category.update' => 'Modification catégorie',
        'category.delete' => 'Suppression catégorie',
        'order.status_update' => 'Changement statut commande',
        'promo_code.create' => 'Création code promo',
        'promo_code.update' => 'Modification code promo',
        'promo_code.delete' => 'Suppression code promo',
        'homepage_slide.update' => 'Mise à jour slides',
        'homepage_slide.delete' => 'Suppression slide',
        'homepage_content.update' => 'Mise à jour texte accueil',
        'contact_message.reply' => 'Réponse message contact',
        'contact_message.status_update' => 'Statut message contact',
        'upload.image' => 'Upload image',
        'page.view' => 'Visite page (parcours achat)',
        'order.create' => 'Création commande',
        'order.update' => 'Modification commande',
        'billing.checkout' => 'Paiement checkout',
        'billing.checkout_success' => 'Paiement confirmé',
        'auth.logout' => 'Déconnexion',
        'profile.update' => 'Modification profil',
        'profile.delete' => 'Suppression profil',
        'account.self_deleted' => 'Suppression compte (utilisateur)',
        default => $action,
    };
}

function audit_target_label(?string $type, ?int $id): string
{
    if ($type === null || $type === '') {
        return '—';
    }

    $label = $type.($id ? ' #'.$id : '');

    return $label;
}

function audit_details_preview(mixed $details): string
{
    if ($details === '' || $details === null || $details === []) {
        return '—';
    }

    if (is_array($details) && isset($details['method'])) {
        return (string) $details['method'];
    }

    if (is_string($details)) {
        $trimmed = trim($details);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded) && isset($decoded['method'])) {
                return (string) $decoded['method'];
            }
        }

        return $details;
    }

    return '—';
}
?>

<div class="ph">
  <div class="ph-left">
    <h1>Logs</h1>
    <p>Historique des actions (sécurité, commandes, administration). Données minimisées conformément au RGPD — <?= (int) $meta['total'] ?> entrée(s)</p>
  </div>
</div>

<?php if ($success !== ''): ?>
<div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#4ade80;margin-bottom:16px">
  <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<?php if ($error !== ''): ?>
<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#f87171;margin-bottom:16px">
  <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="GET" action="audit_logs.php" style="margin-bottom:16px">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="search" name="q" value="<?= htmlspecialchars($filter_q) ?>" placeholder="Rechercher action, IP, acteur, détails..."
      style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 13px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;min-width:240px">
    <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>"
      style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;color-scheme:dark">
    <select name="actor_type" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none">
      <option value="">Tous les acteurs</option>
      <option value="admin" <?= $filter_actor_type === 'admin' ? 'selected' : '' ?>>Admin</option>
      <option value="user" <?= $filter_actor_type === 'user' ? 'selected' : '' ?>>Utilisateur</option>
      <option value="guest" <?= $filter_actor_type === 'guest' ? 'selected' : '' ?>>Invité</option>
    </select>
    <select name="action" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none">
      <option value="">Toutes les actions</option>
      <?php
      $actionOptions = [
          'page.view', 'order.create', 'billing.checkout', 'billing.checkout_success',
          'auth.logout', 'profile.update', 'account.self_deleted',
          'user.update', 'user.delete', 'user.block_toggle',
          'product.create', 'product.update', 'product.delete',
          'category.create', 'category.update', 'category.delete',
          'order.status_update',
          'promo_code.create', 'promo_code.update', 'promo_code.delete',
          'homepage_slide.update', 'homepage_slide.delete', 'homepage_content.update',
          'contact_message.reply', 'contact_message.status_update',
          'upload.image',
      ];
      foreach ($actionOptions as $actionOption):
      ?>
      <option value="<?= htmlspecialchars($actionOption) ?>" <?= $filter_action === $actionOption ? 'selected' : '' ?>>
        <?= htmlspecialchars(audit_action_label($actionOption)) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <select name="target_type" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none">
      <option value="">Toutes les cibles</option>
      <?php foreach (['User', 'Product', 'Category', 'Order', 'PromoCode', 'ContactMessage', 'HomepageSlide', 'HomepageContent', 'Upload', 'produit.php', 'checkout.php', 'checkout_submit.php', 'confirmation.php', 'panier.php'] as $typeOption): ?>
      <option value="<?= htmlspecialchars($typeOption) ?>" <?= $filter_target_type === $typeOption ? 'selected' : '' ?>><?= htmlspecialchars($typeOption) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="number" name="user_id" value="<?= $filter_user_id > 0 ? $filter_user_id : '' ?>" placeholder="ID utilisateur" min="1"
      style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:120px">
    <select name="admin_id" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none">
      <option value="0">Tous les admins</option>
      <?php foreach ($admins as $admin): ?>
      <option value="<?= (int) ($admin['id'] ?? 0) ?>" <?= $filter_admin_id === (int) ($admin['id'] ?? 0) ? 'selected' : '' ?>>
        <?= htmlspecialchars(trim(($admin['prenom'] ?? '').' '.($admin['nom'] ?? '')).' — '.($admin['email'] ?? '')) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-cyna" style="padding:8px 18px;font-size:.83rem">Filtrer</button>
    <?php if ($filter_q || $filter_date || $filter_action || $filter_target_type || $filter_actor_type || $filter_admin_id || $filter_user_id): ?>
      <a href="audit_logs.php" class="btn-ghost" style="padding:7px 14px;font-size:.8rem">Reset</a>
    <?php endif; ?>
    <span style="margin-left:auto;font-size:.78rem;color:var(--c-muted)"><?= count($logs) ?> sur cette page</span>
  </div>
</form>

<div class="card">
  <div class="table-scroll">
  <table class="ctable">
    <thead>
      <tr>
        <th>Date</th>
        <th>Acteur</th>
        <th>Action</th>
        <th>Cible</th>
        <th>IP</th>
        <th>Détails</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (! $logs): ?>
      <tr>
        <td colspan="7">
          <div class="empty-state">
            <div class="icon"></div>
            <p>Aucune entrée d'audit pour ces filtres.</p>
          </div>
        </td>
      </tr>
      <?php else: foreach ($logs as $log):
        $blockUserId = audit_log_blockable_user_id($log);
        $blockUser = ($blockUserId && isset($usersById[$blockUserId])) ? $usersById[$blockUserId] : null;
        $canBlock = $blockUserId && $blockUser && empty($blockUser['is_admin']);
        $isBlocked = $canBlock && ! empty($blockUser['bloquer']);
      ?>
      <tr>
        <td class="mono" style="white-space:nowrap">
          <?= $log['created_at'] ? date('d/m/Y H:i', strtotime((string) $log['created_at'])) : '—' ?>
        </td>
        <td>
          <?php if ($log['actor_type'] === 'admin'): ?>
          <span class="badge badge-blue" style="margin-bottom:4px;display:inline-block">Admin</span>
          <?php elseif ($log['actor_type'] === 'user'): ?>
          <span class="badge badge-green" style="margin-bottom:4px;display:inline-block">Utilisateur</span>
          <?php elseif ($log['actor_type'] === 'guest'): ?>
          <span class="badge badge-gray" style="margin-bottom:4px;display:inline-block">Invité</span>
          <?php endif; ?>
          <div style="font-weight:500;color:#fff;font-size:.84rem"><?= htmlspecialchars($log['actor_name'] ?: '—') ?></div>
          <div class="muted" style="font-size:.75rem"><?= htmlspecialchars($log['actor_email'] ?: ('#'.($log['user_id'] ?? ''))) ?></div>
        </td>
        <td>
          <span class="badge badge-blue" title="<?= htmlspecialchars($log['action']) ?>">
            <?= htmlspecialchars(audit_action_label($log['action'])) ?>
          </span>
        </td>
        <td class="mono"><?= htmlspecialchars(audit_target_label($log['target_type'], $log['target_id'])) ?></td>
        <td class="mono muted"><?= htmlspecialchars($log['ip'] ?: '—') ?></td>
        <td style="max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars(audit_details_preview($log['details'])) ?>">
          <span class="muted" style="font-size:.75rem;font-family:'DM Mono',monospace"><?= htmlspecialchars(audit_details_preview($log['details'])) ?></span>
        </td>
        <td>
          <?php if ($canBlock): ?>
          <form method="POST" action="user_block_toggle.php" style="margin:0" onsubmit="return confirm('<?= $isBlocked ? 'Débloquer' : 'Bloquer' ?> cet utilisateur ?');">
            <input type="hidden" name="user_id" value="<?= $blockUserId ?>">
            <input type="hidden" name="bloquer" value="<?= $isBlocked ? '0' : '1' ?>">
            <input type="hidden" name="return_url" value="<?= htmlspecialchars($build_url()) ?>">
            <?php if ($isBlocked): ?>
              <button type="submit" class="btn-ghost" style="padding:5px 10px;font-size:.72rem;color:#4ade80">Débloquer</button>
            <?php else: ?>
              <button type="submit" class="btn-del" style="padding:5px 10px;font-size:.72rem">Bloquer</button>
            <?php endif; ?>
          </form>
          <?php if ($isBlocked): ?>
            <span class="badge badge-red" style="margin-top:4px;display:inline-block">Bloqué</span>
          <?php endif; ?>
          <?php else: ?>
            <span class="muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
  <?php
  admin_render_pagination(
      (int) $meta['current_page'],
      (int) $meta['last_page'],
      (int) $meta['total'],
      'entrée(s)',
      static fn (array $extra): string => $build_url($extra),
      true
  );
  ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
