<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_STUDENT);

$c = student_get_complaint($conn, (int)($_GET['id'] ?? 0), $me['id']);   // no officer data is ever selected
if (!$c) { flash_set('danger', 'Complaint not found.'); redirect('student/my-complaints.php'); }
$evidence = db_all($conn, "SELECT id, file_name, file_type, file_size, created_at FROM complaint_evidence WHERE complaint_id = ? ORDER BY id", 'i', [$c['id']]);

$page_title = 'Complaint ' . $c['complaint_code']; $active = 'complaints';
include ROOT_PATH . '/includes/student-header.php';
function row($l, $v) { echo '<div class="col-md-6"><div class="small text-muted">' . e($l) . '</div><div class="fw-semibold">' . e($v === null || $v === '' ? '-' : $v) . '</div></div>'; }
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div><h1 class="sv-page-title mb-1"><?= e($c['complaint_code']) ?></h1><?= status_badge($c['status']) ?></div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-sv" href="<?= url('student/complaint-progress.php?id=' . (int)$c['id']) ?>"><i class="bi bi-clock-history me-1"></i>Progress</a>
    <a class="btn btn-outline-sv" href="<?= url('student/messages.php?c=' . (int)$c['id']) ?>"><i class="bi bi-chat-dots me-1"></i>Messages</a>
    <a class="btn btn-outline-sv" href="<?= url('student/evidence.php?c=' . (int)$c['id']) ?>"><i class="bi bi-paperclip me-1"></i>Evidence</a>
  </div>
</div>
<div class="sv-card p-4 mb-4"><div class="row g-3">
  <?php row('Student Name', $c['student_name']); row('Student ID', $c['student_code']); row('School Name', $c['school_name']); row('School Roll Number', $c['school_roll_number']);
        row('Class', $c['class_name']); row('Gender', $c['gender']); row('Complaint Category', $c['category_name']);
        row('Reported Person', trim($c['reported_person_type'] . ' ' . ($c['reported_person_name'] ? '— ' . $c['reported_person_name'] : '')));
        row('Incident Date', fmt_date($c['incident_date'], false)); row('Incident Location', $c['incident_location']);
        row('Submitted', fmt_date($c['created_at'])); row('Last Updated', fmt_date($c['updated_at'])); ?>
</div></div>
<div class="sv-card p-4 mb-4"><h2 class="h6 text-muted">Description</h2><div class="sv-pre"><?= e($c['description']) ?></div>
  <?php if ($c['witness_information']): ?><h2 class="h6 text-muted mt-3">Witness information</h2><div class="sv-pre"><?= e($c['witness_information']) ?></div><?php endif; ?></div>
<div class="sv-card"><div class="card-header-sv">Evidence (<?= count($evidence) ?>)</div>
  <?php if (!$evidence): ?><div class="p-3 text-muted small">No evidence uploaded.</div><?php endif; ?>
  <ul class="list-group list-group-flush"><?php foreach ($evidence as $ev): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center"><span><i class="bi bi-file-earmark me-2"></i><?= e($ev['file_name']) ?> <span class="text-muted small">(<?= round($ev['file_size'] / 1024) ?> KB)</span></span>
    <a class="btn btn-sm btn-outline-sv" href="<?= url('actions/evidence-file.php?id=' . (int)$ev['id']) ?>" target="_blank">Open</a></li><?php endforeach; ?></ul>
</div>
<?php include ROOT_PATH . '/includes/student-footer.php'; ?>
