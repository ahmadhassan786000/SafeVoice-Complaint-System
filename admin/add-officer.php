<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_ADMIN);

$page_title = 'Add Investigator'; $active = 'officers';
include ROOT_PATH . '/includes/panel-header.php';
?>
<a href="<?= url('admin/officers.php') ?>" class="small"><i class="bi bi-arrow-left"></i> Back to Investigation Officers</a>
<h1 class="sv-page-title mt-2">Add Investigator</h1>
<p class="text-muted">Create an investigator account. The Investigation Officer role is assigned automatically.</p>
<form method="post" action="<?= url('actions/create-officer.php') ?>" enctype="multipart/form-data" class="sv-card p-4" style="max-width:900px" novalidate>
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label" for="full_name">Full Name *</label><input id="full_name" class="form-control" name="full_name" required maxlength="100" autocomplete="name" value="<?= e(old('full_name')) ?>"></div>
    <div class="col-md-6"><label class="form-label" for="email">Email Address *</label><input id="email" type="email" class="form-control" name="email" required maxlength="150" autocomplete="email" value="<?= e(old('email')) ?>"></div>
    <div class="col-md-6"><label class="form-label" for="phone">Phone Number *</label><input id="phone" type="tel" class="form-control" name="phone" required maxlength="20" autocomplete="tel" value="<?= e(old('phone')) ?>"></div>
    <div class="col-md-6"><label class="form-label" for="profile_image">Profile Picture</label><input id="profile_image" type="file" class="form-control" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><div class="form-text">Optional. JPG, PNG or WEBP, up to 2 MB.</div></div>
    <div class="col-md-6"><label class="form-label" for="password">Password *</label><input id="password" type="password" class="form-control" name="password" required autocomplete="new-password"></div>
    <div class="col-md-6"><label class="form-label" for="confirm_password">Confirm Password *</label><input id="confirm_password" type="password" class="form-control" name="confirm_password" required autocomplete="new-password"></div>
  </div>
  <div class="d-flex gap-2 mt-4"><button class="btn btn-sv"><i class="bi bi-person-plus me-1"></i>Create Investigator</button><a class="btn btn-outline-sv" href="<?= url('admin/officers.php') ?>">Cancel</a></div>
</form>
<?php old_clear(); include ROOT_PATH . '/includes/panel-footer.php'; ?>
