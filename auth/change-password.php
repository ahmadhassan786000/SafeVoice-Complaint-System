<?php
/** Change password page (voluntary change, standalone auth layout). */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';

$me = require_login();

$page_title = 'Change Password';
include ROOT_PATH . '/includes/auth-header.php';
?>
      <h1 class="sv-auth-title">Change your password</h1>
      <p class="sv-auth-sub">Choose a new password for your account.</p>

      <form method="post" action="<?= url('actions/change-password.php') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="current_password">Current Password</label>
          <div class="input-group sv-input"><span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password"></div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="new_password">New Password</label>
          <div class="input-group sv-input"><span class="input-group-text"><i class="bi bi-key"></i></span>
            <input type="password" class="form-control" id="new_password" name="new_password" required autocomplete="new-password" placeholder="At least 8 characters">
            <button class="btn js-toggle-password" type="button" data-target="new_password" aria-label="Show or hide password"><i class="bi bi-eye"></i></button></div>
        </div>
        <div class="mb-4">
          <label class="form-label" for="confirm_password">Confirm New Password</label>
          <div class="input-group sv-input"><span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password"></div>
        </div>
        <button type="submit" class="btn btn-sv sv-auth-btn w-100">Update Password</button>
        <a class="btn btn-link w-100 mt-2" href="<?= url(dashboard_path_for_role($me['role_code'])) ?>">Cancel</a>
      </form>
<?php include ROOT_PATH . '/includes/auth-footer.php'; ?>
