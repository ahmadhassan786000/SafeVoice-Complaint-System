<?php
/** Processes login for all roles. Role is read from the database, never from the browser. Unlimited attempts. */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';

csrf_require();

$email    = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    old_set(['email' => $email]);
    flash_set('danger', 'Please enter your email address and password.');
    redirect('auth/login.php');
}

$user = valid_email($email) ? db_one($conn,
    "SELECT u.id, u.password, u.account_status, r.role_code
     FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ?", 's', [$email]) : null;

if (!$user || !password_verify($password, $user['password'])) {
    old_set(['email' => $email]);
    flash_set('danger', 'Invalid email or password.');
    redirect('auth/login.php');
}

if ($user['account_status'] !== 'active') {
    old_set(['email' => $email]);
    flash_set('danger', 'Your account is ' . $user['account_status'] . '. Please contact the administrator.');
    redirect('auth/login.php');
}

// Success
session_regenerate_id(true);                          // prevent session fixation
$_SESSION['user_id']       = (int)$user['id'];
$_SESSION['role']          = $user['role_code'];
$_SESSION['last_activity'] = time();
unset($_SESSION['csrf_token']);                       // fresh CSRF token for the new session

if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    db_exec($conn, "UPDATE users SET password = ? WHERE id = ?", 'si', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
}
db_exec($conn, "UPDATE users SET last_login_at = NOW() WHERE id = ?", 'i', [$user['id']]);
old_clear();

flash_set('success', 'Login successful. Welcome back!');
redirect(dashboard_path_for_role($user['role_code']));
