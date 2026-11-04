<?php
/** Processes a voluntary password change. Works for all roles. */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';

$me = require_login();
csrf_require();

$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

$hash = db_value($conn, "SELECT password FROM users WHERE id = ?", 'i', [$me['id']]);

$error = '';
if (!$hash || !password_verify($current, $hash))      $error = 'Your current password is incorrect.';
elseif (($pw = password_error($new)) !== '')          $error = $pw;
elseif ($new !== $confirm)                            $error = 'New passwords do not match.';
elseif (password_verify($new, $hash))                 $error = 'Your new password must be different from the current one.';

if ($error) {
    flash_set('danger', $error);
    redirect('auth/change-password.php');
}

db_exec($conn, "UPDATE users SET password = ? WHERE id = ?",
        'si', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
session_regenerate_id(true);
flash_set('success', 'Your password has been updated.');
redirect(dashboard_path_for_role($me['role_code']));
