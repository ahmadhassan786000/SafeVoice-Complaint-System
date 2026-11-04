<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$page_title = 'System Settings'; $active = 'settings';
include ROOT_PATH . '/includes/panel-header.php';
?>
<h1 class="sv-page-title">System Settings</h1>
<form method="post" action="<?= url('actions/save-settings.php') ?>" class="sv-card p-4" style="max-width:720px"><?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Site name</label><input class="form-control" name="site_name" maxlength="60" required value="<?= e(get_setting($conn, 'site_name', 'SafeVoice')) ?>"></div>
  <div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label">Contact email</label><input type="email" class="form-control" name="contact_email" required value="<?= e(get_setting($conn, 'contact_email')) ?>"></div>
  <div class="col-md-6"><label class="form-label">Contact phone</label><input class="form-control" name="contact_phone" required value="<?= e(get_setting($conn, 'contact_phone')) ?>"></div></div>
  <div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label">Emergency number shown to students</label><input class="form-control" name="emergency_number" required value="<?= e(get_setting($conn, 'emergency_number', '15')) ?>"></div>
  <div class="col-md-6"><label class="form-label">Max evidence file size (MB, 1-10)</label><input type="number" min="1" max="10" class="form-control" name="max_evidence_mb" required value="<?= (int)get_setting($conn, 'max_evidence_mb', 10) ?>"></div></div>
  <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="allow_registration" id="reg" value="1" <?= get_setting($conn, 'allow_registration', '1') === '1' ? 'checked' : '' ?>><label class="form-check-label" for="reg">Allow new student registrations</label></div>
  <button class="btn btn-sv">Save Settings</button></form>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
