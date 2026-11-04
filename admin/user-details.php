<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$id = (int)($_GET['id'] ?? 0);
$t = get_user_by_id($conn, $id);
if (!$t || $t['role_code'] !== ROLE_STUDENT) { flash_set('danger', 'Student not found.'); redirect('admin/users.php'); }
$complaints = db_all($conn, "SELECT c.id, c.complaint_code, c.status, c.created_at, cat.category_name, d.school_name FROM complaints c JOIN complaint_categories cat ON cat.id=c.category_id JOIN complaint_student_details d ON d.complaint_id=c.id WHERE c.student_user_id = ? ORDER BY c.id DESC", 'i', [$id]);
$page_title = 'Student ' . $t['full_name']; $active = 'users';
include ROOT_PATH . '/includes/panel-header.php';
$target = $t; $return = 'admin/user-details.php?id=' . $id; $show_info_form = false;
?>
<a href="<?= url('admin/users.php') ?>" class="small"><i class="bi bi-arrow-left"></i> Back to Users</a>
<h1 class="sv-page-title mt-2"><?= e($t['full_name']) ?> <span class="badge bg-success fs-6">Student</span></h1>
<div class="row g-4 mt-1">
  <div class="col-lg-5"><div class="sv-card p-4"><div class="d-flex align-items-center gap-3 mb-3"><img src="<?= e(avatar_url($t)) ?>" class="sv-avatar-md" alt="Profile picture"><div><h2 class="h5 mb-1">Account</h2><span class="text-muted small">Profile picture is view-only for administrators.</span></div></div><dl class="row mb-3 small"><dt class="col-5">Student ID</dt><dd class="col-7"><?= e($t['student_id']) ?></dd><dt class="col-5">Email</dt><dd class="col-7"><?= e($t['email']) ?></dd><dt class="col-5">Phone</dt><dd class="col-7"><?= e($t['phone'] ?: '-') ?></dd><dt class="col-5">Registered</dt><dd class="col-7"><?= e(fmt_date($t['created_at'])) ?></dd><dt class="col-5">Status</dt><dd class="col-7"><?= e($t['account_status']) ?></dd></dl>
    <form method="post" action="<?= url('actions/update-user-status.php') ?>" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $id ?>"><input type="hidden" name="return" value="<?= e($return) ?>">
      <select name="status" class="form-select"><?php foreach (['active', 'inactive', 'suspended'] as $s): ?><option <?= $t['account_status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select><button class="btn btn-sv">Update</button></form></div></div>
  <div class="col-lg-7"><div class="sv-card"><div class="card-header-sv">Complaints (<?= count($complaints) ?>)</div><div class="table-responsive"><table class="table sv-table mb-0"><tbody>
    <?php if (!$complaints): ?><tr><td class="text-muted p-3">No complaints.</td></tr><?php endif; ?>
    <?php foreach ($complaints as $c): ?><tr><td class="fw-semibold"><?= e($c['complaint_code']) ?></td><td><?= e($c['category_name']) ?></td><td><?= e($c['school_name']) ?></td><td><?= status_badge($c['status']) ?></td><td><a class="btn btn-sm btn-outline-sv" href="<?= url('admin/complaint-details.php?id=' . (int)$c['id']) ?>">Open</a></td></tr><?php endforeach; ?></tbody></table></div></div></div>
</div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
