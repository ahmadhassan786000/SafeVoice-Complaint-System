<?php
/** Admin creates an Investigator account. Role is always INVESTIGATOR. */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_ADMIN);
csrf_require();

$name = trim($_POST['full_name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $phone = trim($_POST['phone'] ?? '');
$pw = $_POST['password'] ?? ''; $pw2 = $_POST['confirm_password'] ?? '';
$images = normalize_files('profile_image');
$image = $images[0] ?? null;

$err = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 100 || !preg_match('/^[\p{L}\s.\'-]+$/u', $name)) $err[] = 'Enter a valid full name.';
if (!valid_email($email)) $err[] = 'Enter a valid email address.';
else {
    $existing = db_one($conn, "SELECT r.role_code, u.account_status FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ?", 's', [$email]);
    if ($existing) {
        if ($existing['role_code'] === ROLE_OFFICER && $existing['account_status'] !== 'active') {
            $err[] = 'An inactive investigator account already uses this email. Find it under Investigation Officers (All statuses) and activate it instead.';
        } else {
            $err[] = 'This email is already used by an existing ' . strtolower($existing['role_code']) . ' account.';
        }
    }
}
if (!phone_required_ok($phone)) $err[] = 'Enter a valid phone number.';
if (($m = password_error($pw)) !== '') $err[] = $m;
if ($pw !== $pw2) $err[] = 'Passwords do not match.';
if ($image) {
    $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    if ($image['error'] !== UPLOAD_ERR_OK) $err[] = 'Profile picture upload failed.';
    elseif ($image['size'] > MAX_PROFILE_IMAGE_SIZE) $err[] = 'Profile picture must be 2 MB or smaller.';
    elseif (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || !is_uploaded_file($image['tmp_name'])) $err[] = 'Choose a valid JPG, PNG or WEBP profile picture.';
    elseif (!in_array(detect_mime($image['tmp_name']), ['image/jpeg', 'image/png', 'image/webp'], true) || @getimagesize($image['tmp_name']) === false) $err[] = 'The uploaded profile picture is not a valid image.';
}
if ($err) { old_set($_POST); foreach ($err as $x) flash_set('danger', $x); redirect('admin/add-officer.php'); }

$role_id = get_role_id($conn, ROLE_OFFICER);
if (!$role_id) {
    error_log('Cannot create investigator: the INVESTIGATOR role is missing from the roles table.');
    old_set($_POST);
    flash_set('danger', 'The Investigator role is missing from the database. Import the updated database/migration.sql in phpMyAdmin, then try again.');
    redirect('admin/add-officer.php');
}

$id = db_insert($conn,
    "INSERT INTO users (role_id, full_name, email, phone, password, account_status) VALUES (?,?,?,?,?,'active')",
    'issss', [$role_id, $name, $email, $phone, password_hash($pw, PASSWORD_DEFAULT)]);
if (!$id) {
    old_set($_POST);
    $existing = db_one($conn, "SELECT r.role_code, u.account_status FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ?", 's', [$email]);
    if ($existing) {
        flash_set('danger', 'This email was just used by another account. Please check the account and try again.');
    } else {
        flash_set('danger', 'The investigator account could not be created because of a database error. Please check the application error log.');
    }
    redirect('admin/add-officer.php');
}

old_clear();
if ($image) {
    $image_error = set_profile_image($conn, $id, $image);
    if ($image_error !== '') flash_set('warning', 'Investigator account created, but the profile picture could not be saved: ' . $image_error);
}
flash_set('success', 'Investigator account created.');
redirect('admin/officers.php');
