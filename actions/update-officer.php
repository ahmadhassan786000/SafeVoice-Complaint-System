<?php
/** Admin edits an Investigator (op=info) or resets their password (op=reset_password). */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_ADMIN);
csrf_require();

$id = (int)($_POST['officer_id'] ?? 0);
$back = 'admin/officer-details.php?id=' . $id;
$t = get_user_by_id($conn, $id);
if (!$t || $t['role_code'] !== ROLE_OFFICER) { flash_set('danger', 'Investigator not found.'); redirect('admin/officers.php'); }

if (($_POST['op'] ?? '') === 'reset_password') {
    $pw = $_POST['new_password'] ?? ''; $pw2 = $_POST['confirm_password'] ?? '';
    if (($m = password_error($pw)) !== '') { flash_set('danger', $m); redirect($back); }
    if ($pw !== $pw2) { flash_set('danger', 'Passwords do not match.'); redirect($back); }
    db_exec($conn, "UPDATE users SET password = ? WHERE id = ?", 'si', [password_hash($pw, PASSWORD_DEFAULT), $id]);
    flash_set('success', 'Password reset successfully.');
    redirect($back);
}

$name = trim($_POST['full_name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $phone = trim($_POST['phone'] ?? ''); $username = trim($_POST['username'] ?? '');
$err = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 100 || !preg_match('/^[\p{L}\s.\'-]+$/u', $name)) $err[] = 'Enter a valid full name.';
if (!valid_email($email)) $err[] = 'Enter a valid email address.';
elseif (db_value($conn, "SELECT id FROM users WHERE email = ? AND id <> ?", 'si', [$email, $id])) $err[] = 'That email address is already in use.';
if (!valid_phone($phone)) $err[] = 'Enter a valid phone number.';
if ($username !== '') {
    if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) $err[] = 'Invalid username format.';
    elseif (db_value($conn, "SELECT id FROM users WHERE username = ? AND id <> ?", 'si', [$username, $id])) $err[] = 'That username is already in use.';
}
if ($err) { foreach ($err as $x) flash_set('danger', $x); redirect($back); }

db_exec($conn, "UPDATE users SET full_name = ?, email = ?, phone = ?, username = ? WHERE id = ?", 'ssssi',
        [$name, $email, $phone === '' ? null : $phone, $username === '' ? null : $username, $id]);
flash_set('success', 'Investigator updated.');
redirect($back);
