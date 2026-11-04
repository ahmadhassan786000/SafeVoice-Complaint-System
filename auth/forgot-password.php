<?php
/**
 * Forgot password. XAMPP has no mail server, so self-service reset by email is not possible locally.
 * The safe, honest flow: students/officers contact the administrator, who resets the password
 * (Admin > Users / Investigation Officers, built in Phase 5).
 */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';

$contact_email = db_value($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'contact_email'") ?: 'support@safevoice.local';
$page_title = 'Forgot Password';
$body_class = 'sv-auth-body';
include ROOT_PATH . '/includes/header.php';
?>
<main class="sv-auth-wrap">
  <div class="sv-auth-card text-center">
    <i class="bi bi-key display-5 text-success"></i>
    <h1 class="h4 fw-bold mt-2">Forgot your password?</h1>
    <p class="text-muted">For your protection, passwords are reset by the SafeVoice administrator.
       Please contact <strong><?= e($contact_email) ?></strong> from the email address you registered with.
       You will receive a temporary password and be asked to choose a new one after logging in.</p>
    <a href="<?= url('auth/login.php') ?>" class="btn btn-sv">Back to Login</a>
  </div>
</main>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
