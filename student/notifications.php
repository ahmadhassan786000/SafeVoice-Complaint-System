<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_STUDENT);
$all = db_all($conn, "SELECT id, title, message, notification_type, related_complaint_id, is_read, created_at FROM notifications WHERE recipient_user_id = ? ORDER BY id DESC LIMIT 100", 'i', [$me['id']]);
$page_title = 'Notifications'; $active = '';
include ROOT_PATH . '/includes/student-header.php';
?>
<div class="sv-page-head">
  <div><h1 class="sv-page-title">Notifications</h1><p class="sv-page-sub">Updates about your complaints and messages from the SafeVoice Investigation Team.</p></div>
  <?php if ($all): ?><form method="post" action="<?= url('actions/mark-notification-read.php') ?>"><?= csrf_field() ?><input type="hidden" name="all" value="1"><button class="btn btn-outline-sv btn-sm"><i class="bi bi-check2-all me-1" aria-hidden="true"></i>Mark all as read</button></form><?php endif; ?>
</div>
<div class="sv-card sv-notif-list">
  <?php if (!$all): ?><div class="sv-empty-state"><i class="bi bi-bell" aria-hidden="true"></i><strong>You have no notifications</strong><span>We will let you know when something changes on your complaints.</span></div><?php endif; ?>
  <?php foreach ($all as $n): ?>
    <div class="sv-notif-row <?= $n['is_read'] ? '' : 'unread' ?>">
      <div><div class="t"><?= $n['is_read'] ? '' : '<span class="badge bg-success me-1">New</span>' ?><?= e($n['title']) ?></div><div class="m"><?= e($n['message']) ?></div><div class="d"><?= e(fmt_date($n['created_at'])) ?></div></div>
      <div class="d-flex gap-1 flex-shrink-0">
        <form method="post" action="<?= url('actions/mark-notification-read.php') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$n['id'] ?>"><button class="btn btn-sm btn-sv">Open</button></form>
        <?php if (!$n['is_read']): ?><form method="post" action="<?= url('actions/mark-notification-read.php') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$n['id'] ?>"><input type="hidden" name="stay" value="1"><button class="btn btn-sm btn-outline-sv">Mark read</button></form><?php endif; ?>
      </div></div>
  <?php endforeach; ?>
</div>
<?php include ROOT_PATH . '/includes/student-footer.php'; ?>
