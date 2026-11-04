<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_STUDENT);
$total = (int)db_value($conn, "SELECT COUNT(*) FROM complaints WHERE student_user_id = ?", 'i', [$me['id']]);
$page_title = 'My Profile'; $active = '';
include ROOT_PATH . '/includes/student-header.php';
?>
<h1 class="sv-page-title">My Profile</h1>
<div class="sv-card p-4" style="max-width:640px"><div class="d-flex align-items-center gap-3 mb-3">
  <img src="<?= e(avatar_url($me)) ?>" class="rounded-circle" style="width:90px;height:90px;object-fit:cover" alt="">
  <div><h2 class="h4 mb-0"><?= e($me['full_name']) ?></h2><span class="badge bg-success">Student</span></div></div>
  <dl class="row mb-0"><dt class="col-sm-4">Student ID</dt><dd class="col-sm-8"><?= e($me['student_id']) ?></dd>
    <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?= e($me['email']) ?></dd>
    <dt class="col-sm-4">Phone</dt><dd class="col-sm-8"><?= e($me['phone'] ?: '-') ?></dd>
    <dt class="col-sm-4">Member since</dt><dd class="col-sm-8"><?= e(fmt_date($me['created_at'], false)) ?></dd>
    <dt class="col-sm-4">Complaints</dt><dd class="col-sm-8"><?= $total ?></dd></dl>
  <a class="btn btn-sv mt-3" href="<?= url('student/profile-settings.php') ?>">Edit Profile &amp; Picture</a></div>
<?php include ROOT_PATH . '/includes/student-footer.php'; ?>
