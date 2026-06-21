<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$filter_q = trim($_GET['q'] ?? '');
$filter_date = trim($_GET['date'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$messages = [];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reply') {
    $messageId = (int) ($_POST['message_id'] ?? 0);
    $reply = trim($_POST['reply'] ?? '');

    if ($messageId <= 0) {
        $error = 'Message introuvable.';
    } elseif (strlen($reply) < 5) {
        $error = 'La réponse doit contenir au moins 5 caractères.';
    } else {
        try {
            $result = admin_api()->adminReplyContactMessage($messageId, $reply);
            $success = $result['message'] ?? 'Réponse envoyée.';
            if (empty($result['data']['mail_sent'])) {
                $success .= ' (email non envoyé — vérifiez la configuration SMTP de l\'API)';
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    $messageId = (int) ($_POST['message_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if ($messageId <= 0) {
        $error = 'Message introuvable.';
    } elseif (! in_array($status, ['pending', 'replied', 'closed'], true)) {
        $error = 'Statut invalide.';
    } else {
        try {
            $result = admin_api()->adminUpdateContactStatus($messageId, $status);
            $success = $result['message'] ?? 'Statut mis à jour.';
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

$query = ['per_page' => 200];
if ($filter_q !== '') {
    $query['q'] = $filter_q;
}
if ($filter_date !== '') {
    $query['date'] = $filter_date;
}
if (in_array($filter_status, ['pending', 'replied', 'closed'], true)) {
    $query['status'] = $filter_status;
}

try {
    $messages = admin_api()->adminGetContactMessages($query);
} catch (RuntimeException $e) {
    if ($error === '') {
        $error = $e->getMessage();
    }
}

function contact_status_label(string $status): string
{
    return match ($status) {
        'replied' => 'Répondu',
        'closed' => 'Clôturé',
        default => 'En attente',
    };
}

function contact_status_badge(string $status): string
{
    return match ($status) {
        'replied' => '<span style="font-size:.72rem;padding:3px 9px;border-radius:20px;background:rgba(34,197,94,.1);color:#86efac;border:1px solid rgba(34,197,94,.2)">Répondu</span>',
        'closed' => '<span style="font-size:.72rem;padding:3px 9px;border-radius:20px;background:rgba(148,163,184,.1);color:#cbd5e1;border:1px solid rgba(148,163,184,.2)">Clôturé</span>',
        default => '<span style="font-size:.72rem;padding:3px 9px;border-radius:20px;background:rgba(251,191,36,.1);color:#fcd34d;border:1px solid rgba(251,191,36,.2)">En attente</span>',
    };
}

$total_messages = count($messages);
$today_messages = count(array_filter($messages, static fn (array $msg): bool => substr((string) ($msg['created_at'] ?? ''), 0, 10) === date('Y-m-d')));
$pending_messages = count(array_filter($messages, static fn (array $msg): bool => ($msg['status'] ?? 'pending') === 'pending'));
$replied_messages = count(array_filter($messages, static fn (array $msg): bool => ($msg['status'] ?? '') === 'replied'));
$closed_messages = count(array_filter($messages, static fn (array $msg): bool => ($msg['status'] ?? '') === 'closed'));

$build_url = static function (array $extra = []) use ($filter_q, $filter_date, $filter_status): string {
    $params = array_merge([
        'q' => $filter_q,
        'date' => $filter_date,
        'status' => $filter_status,
    ], $extra);
    $filtered = array_filter($params, static fn ($v): bool => $v !== '');

    return 'chat_logs.php'.($filtered !== [] ? '?'.http_build_query($filtered) : '');
};
?>

<div class="ph">
  <div class="ph-left">
    <h1>Messages contact</h1>
    <p>Formulaire de contact — consultation, changement de statut et réponse directe</p>
  </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:16px;padding:12px 16px;border-radius:10px;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);color:#86efac;font-size:.85rem">
  <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger" style="margin-bottom:16px;padding:12px 16px;border-radius:10px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#fca5a5;font-size:.85rem">
  <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col">
    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-info"><div class="stat-val"><?= $total_messages ?></div><div class="stat-lbl">Total</div></div>
    </div>
  </div>
  <div class="col">
    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-info"><div class="stat-val"><?= $today_messages ?></div><div class="stat-lbl">Aujourd'hui</div></div>
    </div>
  </div>
  <div class="col">
    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-info"><div class="stat-val"><?= $pending_messages ?></div><div class="stat-lbl">En attente</div></div>
    </div>
  </div>
  <div class="col">
    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-info"><div class="stat-val"><?= $replied_messages ?></div><div class="stat-lbl">Répondus</div></div>
    </div>
  </div>
  <div class="col">
    <div class="stat-card">
      <div class="stat-icon"></div>
      <div class="stat-info"><div class="stat-val"><?= $closed_messages ?></div><div class="stat-lbl">Clôturés</div></div>
    </div>
  </div>
</div>

<form method="GET" action="chat_logs.php" style="margin-bottom:16px">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="search" name="q" value="<?= htmlspecialchars($filter_q) ?>" placeholder="Rechercher email, sujet, message..." style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 13px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:280px">
    <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;colorscheme:dark">
    <select name="status" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none">
      <option value="">Tous les statuts</option>
      <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>En attente</option>
      <option value="replied" <?= $filter_status === 'replied' ? 'selected' : '' ?>>Répondus</option>
      <option value="closed" <?= $filter_status === 'closed' ? 'selected' : '' ?>>Clôturés</option>
    </select>
    <button type="submit" class="btn-cyna" style="padding:8px 18px;font-size:.83rem">Filtrer</button>
    <?php if ($filter_q || $filter_date || $filter_status): ?>
      <a href="chat_logs.php" class="btn-ghost" style="padding:7px 14px;font-size:.8rem">Reset</a>
    <?php endif; ?>
    <span style="margin-left:auto;font-size:.78rem;color:var(--c-muted)"><?= count($messages) ?> résultat(s)</span>
  </div>
</form>

<?php if (!$messages): ?>
<div class="card">
  <div class="empty-state" style="padding:48px">
    <div class="icon" style="font-size:2rem;margin-bottom:12px;opacity:.3"></div>
    <p>Aucun message de contact pour le moment.</p>
  </div>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:12px">
<?php foreach ($messages as $msg): ?>
<?php
  $id = (int) ($msg['id'] ?? 0);
  $email = (string) ($msg['email'] ?? '');
  $sujet = (string) ($msg['sujet'] ?? '');
  $body = (string) ($msg['message'] ?? '');
  $status = (string) ($msg['status'] ?? 'pending');
  $userId = $msg['user_id'] ?? null;
  $createdAt = $msg['created_at'] ?? null;
  $repliedAt = $msg['replied_at'] ?? null;
  $repliedById = $msg['replied_by'] ?? null;
  $replies = is_array($msg['replies'] ?? null) ? $msg['replies'] : [];
  $repliesCount = (int) ($msg['replies_count'] ?? count($replies));
?>
<div class="card" style="overflow:hidden">
  <div style="padding:14px 20px;border-bottom:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;gap:12px;cursor:pointer" onclick="toggleMessage(<?= $id ?>)">
    <div style="display:flex;align-items:center;gap:12px;min-width:0">
      <div style="width:36px;height:36px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;color:#fff;flex-shrink:0">
        <?= $email !== '' ? strtoupper(substr($email, 0, 1)) : '?' ?>
      </div>
      <div style="min-width:0">
        <div style="font-size:.85rem;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          #<?= $id ?> · <?= htmlspecialchars($email) ?>
        </div>
        <div style="font-size:.78rem;color:#cbd5e1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($sujet) ?></div>
        <div style="font-size:.72rem;color:var(--c-muted)">
          <?= $createdAt ? date('d/m/Y à H:i', strtotime($createdAt)) : '—' ?>
          <?php if ($userId): ?> · user #<?= (int) $userId ?><?php endif; ?>
        </div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
      <?= contact_status_badge($status) ?>
      <?php if ($repliesCount > 0): ?>
        <span style="font-size:.72rem;padding:3px 9px;border-radius:20px;background:rgba(79,140,255,.1);color:#93c5fd;border:1px solid rgba(79,140,255,.2)"><?= $repliesCount ?> rép.</span>
      <?php endif; ?>
      <span style="color:var(--c-muted);font-size:.8rem" id="chev-<?= $id ?>">▶</span>
    </div>
  </div>

  <div id="message-<?= $id ?>" style="display:none;padding:16px 20px;background:rgba(0,0,0,.15)">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:14px;font-size:.78rem;color:var(--c-muted)">
      <div><strong style="color:#cbd5e1">ID message</strong><br>#<?= $id ?></div>
      <div><strong style="color:#cbd5e1">User ID</strong><br><?= $userId ? (int) $userId : '—' ?></div>
      <div><strong style="color:#cbd5e1">Date création</strong><br><?= $createdAt ? date('d/m/Y H:i', strtotime($createdAt)) : '—' ?></div>
      <div><strong style="color:#cbd5e1">Statut</strong><br><?= contact_status_label($status) ?></div>
      <div><strong style="color:#cbd5e1">Dernière réponse par (ID)</strong><br><?= $repliedById ? (int) $repliedById : '—' ?></div>
      <div><strong style="color:#cbd5e1">Dernière réponse le</strong><br><?= $repliedAt ? date('d/m/Y H:i', strtotime($repliedAt)) : '—' ?></div>
      <div><strong style="color:#cbd5e1">Nb réponses</strong><br><?= $repliesCount ?></div>
    </div>

    <form method="POST" action="<?= htmlspecialchars($build_url(['open' => $id])) ?>" onclick="event.stopPropagation()" style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <input type="hidden" name="action" value="status">
      <input type="hidden" name="message_id" value="<?= $id ?>">
      <label style="font-size:.72rem;color:var(--c-muted);text-transform:uppercase;letter-spacing:.04em">Changer le statut</label>
      <select name="status" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none">
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>En attente</option>
        <option value="replied" <?= $status === 'replied' ? 'selected' : '' ?>>Répondu</option>
        <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Clôturé</option>
      </select>
      <button type="submit" class="btn-ghost" style="padding:8px 16px;font-size:.83rem">Mettre à jour le statut</button>
    </form>

    <div style="margin-bottom:14px">
      <div style="font-size:.72rem;color:var(--c-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em">Message utilisateur</div>
      <div style="background:#1a2035;border:1px solid rgba(255,255,255,.08);color:#e8eaf2;border-radius:12px;padding:12px 14px;font-size:.84rem;white-space:pre-wrap"><?= htmlspecialchars($body) ?></div>
    </div>

    <?php if ($replies !== []): ?>
    <div style="margin-bottom:14px">
      <div style="font-size:.72rem;color:var(--c-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em">Historique des réponses</div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach ($replies as $reply): ?>
        <?php
          $replyAdminId = $reply['admin_id'] ?? null;
          $replyAt = $reply['created_at'] ?? null;
          $replyBody = (string) ($reply['body'] ?? '');
          $mailSent = ! empty($reply['mail_sent']);
        ?>
        <div style="background:rgba(38,208,206,.06);border:1px solid rgba(38,208,206,.15);border-radius:10px;padding:10px 12px">
          <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:.72rem;color:var(--c-muted);margin-bottom:6px">
            <span>Admin ID : <strong style="color:#cbd5e1"><?= $replyAdminId ? (int) $replyAdminId : '—' ?></strong></span>
            <span><?= $replyAt ? date('d/m/Y H:i', strtotime($replyAt)) : '—' ?><?= $mailSent ? ' · envoyé' : ' · non envoyé' ?></span>
          </div>
          <div style="font-size:.84rem;color:#e8eaf2;white-space:pre-wrap"><?= htmlspecialchars($replyBody) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($build_url(['open' => $id])) ?>" onclick="event.stopPropagation()" style="margin-top:8px" data-cyna-validate="admin-reply">
      <input type="hidden" name="action" value="reply">
      <input type="hidden" name="message_id" value="<?= $id ?>">
      <label style="display:block;font-size:.72rem;color:var(--c-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em">
        Ajouter une nouvelle réponse (chaque envoi est conservé dans l'historique)
      </label>
      <textarea name="reply" rows="5" required minlength="5" maxlength="5000" placeholder="Rédigez une nouvelle réponse à l'utilisateur..." style="width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px 12px;font-size:.84rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;resize:vertical"></textarea>
      <div style="display:flex;justify-content:flex-end;margin-top:10px">
        <button type="submit" class="btn-cyna" style="padding:8px 18px;font-size:.83rem">Envoyer une nouvelle réponse</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function toggleMessage(id) {
  var el = document.getElementById('message-' + id);
  var chev = document.getElementById('chev-' + id);
  if (!el || !chev) return;
  var open = el.style.display !== 'none';
  el.style.display = open ? 'none' : 'block';
  chev.textContent = open ? '▶' : '▼';
}
<?php if (!empty($_GET['open'])): ?>
document.addEventListener('DOMContentLoaded', function () {
  toggleMessage(<?= (int) $_GET['open'] ?>);
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
