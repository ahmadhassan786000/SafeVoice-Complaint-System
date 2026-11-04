<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_STUDENT);

$c = student_get_complaint($conn, (int)($_GET['id'] ?? 0), $me['id']);
if (!$c) { flash_set('danger', 'Complaint not found.'); redirect('student/my-complaints.php'); }
// Only public_description + timestamp: no internal notes, no user ids, no officer data.
$events = db_all($conn, "SELECT public_description, created_at FROM complaint_status_history WHERE complaint_id = ? ORDER BY id", 'i', [$c['id']]);

$page_title = 'Progress'; $active = 'complaints';
include ROOT_PATH . '/includes/student-header.php';
?>
<a href="<?= url('student/complaint-details.php?id=' . (int)$c['id']) ?>" class="small"><i class="bi bi-arrow-left"></i> Back to complaint</a>
<h1 class="sv-page-title mt-2">Progress — <?= e($c['complaint_code']) ?></h1>
<p><?= status_badge($c['status']) ?></p>
<div class="sv-card p-4"><ul class="sv-timeline">
  <?php foreach ($events as $ev): ?><li><div class="fw-semibold"><?= e($ev['public_description']) ?></div><div class="small text-muted"><?= e(fmt_date($ev['created_at'])) ?></div></li><?php endforeach; ?>
</ul></div>
<?php include ROOT_PATH . '/includes/student-footer.php'; ?>
