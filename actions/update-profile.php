<?php
/** Update own name/phone (all roles). */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
$me = require_login();
csrf_require();

$back = safe_return(dashboard_path_for_role($me['role_code']));
$name = trim($_POST['full_name'] ?? ''); $phone = trim($_POST['phone'] ?? '');
if (mb_strlen($name) < 2 || mb_strlen($name) > 100 || !preg_match('/^[\p{L}\s.\'-]+$/u', $name)) { flash_set('danger', 'Please enter a valid full name.'); redirect($back); }
if ($me['role_code'] === ROLE_STUDENT && !phone_required_ok($phone)) { flash_set('danger', 'A valid phone number is required.'); redirect($back); }
if (!valid_phone($phone)) { flash_set('danger', 'Please enter a valid phone number.'); redirect($back); }

db_exec($conn, "UPDATE users SET full_name = ?, phone = ? WHERE id = ?", 'ssi', [$name, $phone === '' ? null : $phone, $me['id']]);
flash_set('success', 'Profile updated.');
redirect($back);
