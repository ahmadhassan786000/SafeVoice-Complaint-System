<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_STUDENT);

$list = db_all($conn, "SELECT id, complaint_code FROM complaints WHERE student_user_id = ? ORDER BY id DESC", 'i', [$me['id']]);
$sel = (int)($_GET['c'] ?? ($list[0]['id'] ?? 0));
$c = $sel ? student_get_complaint($conn, $sel, $me['id']) : null;
$files = $c ? db_all($conn, "SELECT id, file_name, file_type, file_size, created_at FROM complaint_evidence WHERE complaint_id = ? ORDER BY id DESC", 'i', [$sel]) : [];

$page_title = 'Evidence'; $active = 'complaints';
include ROOT_PATH . '/includes/student-header.php';
?>
<h1 class="sv-page-title">Evidence</h1>
<?php if (!$list): ?><div class="sv-card p-4 text-muted">Submit a complaint first to attach evidence.</div><?php else: ?>
<form method="get" class="mb-3 d-flex gap-2 align-items-center"><label class="fw-semibold">Complaint:</label>
  <select name="c" class="form-select w-auto" onchange="this.form.submit()"><?php foreach ($list as $l): ?><option value="<?= (int)$l['id'] ?>" <?= (int)$l['id'] === $sel ? 'selected' : '' ?>><?= e($l['complaint_code']) ?></option><?php endforeach; ?></select></form>
<?php if ($c): ?>
<div class="row g-4"><div class="col-lg-7"><div class="sv-card"><div class="card-header-sv">Uploaded files (<?= count($files) ?>)</div>
  <?php if (!$files): ?><div class="p-3 text-muted small">No evidence yet.</div><?php endif; ?>
  <div class="row g-3 p-3"><?php foreach ($files as $f): $img = strpos($f['file_type'], 'image/') === 0; ?>
    <div class="col-sm-6"><div class="sv-cat-card">
      <?php if ($img): ?><a href="<?= url('actions/evidence-file.php?id=' . (int)$f['id']) ?>" target="_blank"><img class="img-fluid rounded mb-2" style="max-height:140px;object-fit:cover;width:100%" src="<?= url('actions/evidence-file.php?id=' . (int)$f['id']) ?>" alt="Evidence preview"></a><?php else: ?><i class="bi bi-file-earmark-text fs-1 text-success"></i><?php endif; ?>
      <div class="small fw-semibold text-break"><?= e($f['file_name']) ?></div><div class="small text-muted"><?= round($f['file_size'] / 1024) ?> KB · <?= e(fmt_date($f['created_at'], false)) ?></div>
      <a class="btn btn-sm btn-outline-sv mt-2" href="<?= url('actions/evidence-file.php?id=' . (int)$f['id'] . '&dl=1') ?>">Download</a></div></div>
  <?php endforeach; ?></div></div></div>
  <div class="col-lg-5"><div class="sv-card p-4"><h2 class="h5">Upload more evidence</h2>
    <form method="post" action="<?= url('actions/upload-evidence.php') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="complaint_id" value="<?= $sel ?>">
      <input type="file" class="form-control mb-2" name="evidence[]" multiple required accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt">
      <div class="form-text mb-3">JPG, PNG, WEBP, PDF, DOC, DOCX or TXT. <strong>Do not upload sexual or explicit images</strong> (especially of anyone under 18). Describe them in a message instead and see <a href="<?= url('student/help.php') ?>">Emergency Help</a>.</div>
      <button class="btn btn-sv w-100"><i class="bi bi-upload me-1"></i>Upload</button></form></div></div></div>
<?php endif; endif; ?>
<?php include ROOT_PATH . '/includes/student-footer.php'; ?>
