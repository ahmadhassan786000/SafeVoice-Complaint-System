<?php
/** Admin: save system settings. */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_ADMIN);
csrf_require();

$site = trim($_POST['site_name'] ?? ''); $email = trim($_POST['contact_email'] ?? ''); $phone = trim($_POST['contact_phone'] ?? '');
$emerg = trim($_POST['emergency_number'] ?? ''); $reg = !empty($_POST['allow_registration']) ? '1' : '0'; $mb = (int)($_POST['max_evidence_mb'] ?? 10);

$err = [];
if ($site === '' || mb_strlen($site) > 60) $err[] = 'Site name is required (max 60 characters).';
if (!valid_email($email)) $err[] = 'Enter a valid contact email.';
if (!valid_phone($phone)) $err[] = 'Enter a valid contact phone.';
if (!preg_match('/^[0-9+\-\s]{2,20}$/', $emerg)) $err[] = 'Enter a valid emergency number.';
if ($mb < 1 || $mb > 10) $err[] = 'Maximum evidence size must be between 1 and 10 MB.';
if ($err) { foreach ($err as $x) flash_set('danger', $x); redirect('admin/settings.php'); }

foreach (['site_name' => $site, 'contact_email' => $email, 'contact_phone' => $phone, 'emergency_number' => $emerg, 'allow_registration' => $reg, 'max_evidence_mb' => (string)$mb] as $k => $v) {
    db_exec($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", 'ss', [$k, $v]);
}
flash_set('success', 'Settings saved.');
redirect('admin/settings.php');
