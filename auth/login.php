<?php
/** Login page - standalone layout (no public header/footer). Logged-in users are sent to their dashboard. */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';

if (!empty($_SESSION['user_id'])) redirect(dashboard_path_for_role($_SESSION['role'] ?? ''));

$page_title = 'Login';
include ROOT_PATH . '/includes/auth-header.php';
?>
      <h1 class="sv-auth-title">Welcome back</h1>
      <p class="sv-auth-sub">Log in to continue to your SafeVoice account.</p>

      <form method="post" action="<?= url('actions/login.php') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="email">Email Address</label>
          <div class="input-group sv-input sv-input-no-icon">
            <input type="email" class="form-control" id="email" name="email" required autofocus autocomplete="email" placeholder="you@example.com" value="<?= e(old('email')) ?>">
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <div class="input-group sv-input sv-input-no-icon">
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
            <button class="btn js-toggle-password" type="button" data-target="password" aria-label="Show or hide password"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="btn btn-sv sv-auth-btn w-100">Login <i class="bi bi-arrow-right ms-1"></i></button>
      </form>
      <p class="text-center small mt-4 mb-0">Don't have an account? <a class="fw-semibold" href="<?= url('auth/register.php') ?>">Sign up</a></p>
<?php old_clear(); include ROOT_PATH . '/includes/auth-footer.php'; ?>
