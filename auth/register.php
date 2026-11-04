<?php
/** Student signup page - standalone layout. Always creates a STUDENT (there is no role field). */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';

if (!empty($_SESSION['user_id'])) redirect(dashboard_path_for_role($_SESSION['role'] ?? ''));
if (get_setting($conn, 'allow_registration', '1') !== '1') {
    flash_set('warning', 'Registration is currently closed.');
    redirect('auth/login.php');
}

// One server-side error at most, shown under the relevant field.
$fe = $_SESSION['field_error'] ?? null;
unset($_SESSION['field_error']);
$err_field = $fe['field'] ?? '';
function field_err($name, $err_field, $fe) {
    echo '<div class="sv-field-error' . ($err_field === $name ? ' show' : '') . '" data-error-for="' . e($name) . '" role="alert">' . ($err_field === $name ? e($fe['message']) : '') . '</div>';
}

$page_title = 'Create Account';
$page_scripts = '<script src="' . url('assets/js/auth.js') . '"></script>' . ($err_field ? '<script>document.addEventListener("DOMContentLoaded",function(){var f=document.getElementById(' . json_encode($err_field) . ');if(f){f.focus();}});</script>' : '');
include ROOT_PATH . '/includes/auth-header.php';
?>
      <h1 class="sv-auth-title">Create your account</h1>
      <p class="sv-auth-sub">Join SafeVoice to report concerns.</p>

      <form method="post" action="<?= url('actions/register.php') ?>" id="registerForm" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="full_name">Full Name</label>
          <div class="input-group sv-input sv-input-no-icon <?= $err_field === 'full_name' ? 'is-error' : '' ?>">
            <input type="text" class="form-control" id="full_name" name="full_name" maxlength="100" autocomplete="name" placeholder="Your full name" value="<?= e(old('full_name')) ?>">
          </div>
          <?php field_err('full_name', $err_field, $fe); ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="email">Email Address</label>
          <div class="input-group sv-input sv-input-no-icon <?= $err_field === 'email' ? 'is-error' : '' ?>">
            <input type="email" class="form-control" id="email" name="email" maxlength="150" autocomplete="email" placeholder="you@example.com" value="<?= e(old('email')) ?>">
          </div>
          <?php field_err('email', $err_field, $fe); ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="phone">Phone Number</label>
          <div class="input-group sv-input sv-input-no-icon <?= $err_field === 'phone' ? 'is-error' : '' ?>">
            <input type="tel" class="form-control" id="phone" name="phone" maxlength="20" autocomplete="tel" placeholder="e.g. 0300 1234567" value="<?= e(old('phone')) ?>">
          </div>
          <?php field_err('phone', $err_field, $fe); ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <div class="input-group sv-input sv-input-no-icon <?= $err_field === 'password' ? 'is-error' : '' ?>">
            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" placeholder="At least 8 characters">
            <button class="btn js-toggle-password" type="button" data-target="password" aria-label="Show or hide password"><i class="bi bi-eye"></i></button>
          </div>
          <?php field_err('password', $err_field, $fe); ?>
        </div>
        <div class="mb-4">
          <label class="form-label" for="confirm_password">Confirm Password</label>
          <div class="input-group sv-input sv-input-no-icon <?= $err_field === 'confirm_password' ? 'is-error' : '' ?>">
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" placeholder="Re-enter your password">
            <button class="btn js-toggle-password" type="button" data-target="confirm_password" aria-label="Show or hide password"><i class="bi bi-eye"></i></button>
          </div>
          <?php field_err('confirm_password', $err_field, $fe); ?>
        </div>
        <button type="submit" class="btn btn-sv sv-auth-btn w-100">Create Account <i class="bi bi-arrow-right ms-1"></i></button>
      </form>
      <p class="text-center small mt-4 mb-0">Already have an account? <a class="fw-semibold" href="<?= url('auth/login.php') ?>">Log in</a></p>
<?php old_clear(); include ROOT_PATH . '/includes/auth-footer.php'; ?>
