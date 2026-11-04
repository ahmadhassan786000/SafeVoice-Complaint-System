<?php
/**
 * Processes student registration. The role is ALWAYS Student - it is never read from the form.
 * Validation runs in priority order and stops at the FIRST problem, so the user only ever sees one error.
 */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';

csrf_require();

if (get_setting($conn, 'allow_registration', '1') !== '1') {
    flash_set('danger', 'Registration is currently closed.');
    redirect('auth/login.php');
}

$full_name = trim($_POST['full_name'] ?? '');
$email     = strtolower(trim($_POST['email'] ?? ''));
$phone     = trim($_POST['phone'] ?? '');
$password  = $_POST['password'] ?? '';
$confirm   = $_POST['confirm_password'] ?? '';

/** Returns [field, message] for the first problem, or null. */
function first_register_error($conn, $full_name, $email, $phone, $password, $confirm) {
    // 1. Full name
    if ($full_name === '')                                        return ['full_name', 'Please enter your full name.'];
    if (mb_strlen($full_name) < 2 || mb_strlen($full_name) > 100) return ['full_name', 'Full name must be between 2 and 100 characters.'];
    if (!preg_match('/^[\p{L}\s.\'-]+$/u', $full_name))           return ['full_name', 'Full name can only contain letters, spaces, dots, hyphens and apostrophes.'];
    // 2. Email
    if ($email === '')                                            return ['email', 'Please enter your email address.'];
    if (!valid_email($email))                                     return ['email', 'Please enter a valid email address.'];
    // 3. Phone (compulsory)
    if ($phone === '')                                            return ['phone', 'Please enter your phone number.'];
    if (!phone_required_ok($phone))                               return ['phone', 'Please enter a valid phone number (at least 7 digits).'];
    // 4. Password (minimum length only)
    if ($password === '')                                         return ['password', 'Please enter a password.'];
    if (($m = password_error($password)) !== '')                  return ['password', $m];
    // 5. Confirm password
    if ($confirm === '')                                          return ['confirm_password', 'Please confirm your password.'];
    if ($password !== $confirm)                                   return ['confirm_password', 'Passwords do not match.'];
    // Duplicate email (checked last so the user fixes simple input problems first)
    if (db_value($conn, "SELECT id FROM users WHERE email = ?", 's', [$email]))
                                                                  return ['email', 'An account with this email address already exists.'];
    return null;
}

$err = first_register_error($conn, $full_name, $email, $phone, $password, $confirm);
if ($err) {
    old_set($_POST);
    $_SESSION['field_error'] = ['field' => $err[0], 'message' => $err[1]];
    redirect('auth/register.php');
}

$role_id = get_role_id($conn, ROLE_STUDENT);     // forced STUDENT role
$hash    = password_hash($password, PASSWORD_DEFAULT);

mysqli_begin_transaction($conn);
$new_id = db_insert($conn,
    "INSERT INTO users (role_id, full_name, email, phone, password, account_status)
     VALUES (?,?,?,?,?, 'active')", 'issss', [$role_id, $full_name, $email, $phone, $hash]);

$ok = false;
if ($new_id) {
    $student_id = make_student_id($new_id);      // stored in the database, never shown in the flash message
    $ok = db_exec($conn, "UPDATE users SET student_id = ? WHERE id = ?", 'si', [$student_id, $new_id]) !== false;
}

if ($ok) {
    mysqli_commit($conn);
    old_clear();
    flash_set('success', 'Your account has been created successfully. Please log in to continue.');
    redirect('auth/login.php');
}

mysqli_rollback($conn);
old_set($_POST);
$_SESSION['field_error'] = ['field' => 'full_name', 'message' => 'We could not create your account right now. Please try again.'];
redirect('auth/register.php');
