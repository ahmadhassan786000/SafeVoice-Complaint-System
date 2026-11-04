<?php
/** Record a statement in an investigation (assigned Officer only). */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_OFFICER);
csrf_require();

$cid = (int)($_POST['complaint_id'] ?? 0);
$back = 'officer/investigation.php?id=' . $cid;
if (!can_access_complaint($conn, $cid, $me)) { http_response_code(403); flash_set('danger', 'Access denied.'); redirect('officer/dashboard.php'); }

$text = trim($_POST['statement_details'] ?? '');
if ($text === '' || mb_strlen($text) > 5000) { flash_set('danger', 'Please enter the statement (up to 5000 characters).'); redirect($back); }

$inv = db_value($conn, "SELECT id FROM investigations WHERE complaint_id = ?", 'i', [$cid]);
if (!$inv) $inv = db_insert($conn, "INSERT INTO investigations (complaint_id, officer_user_id) VALUES (?,?)", 'ii', [$cid, $me['id']]);
db_exec($conn, "INSERT INTO statements (investigation_id, statement_details, recorded_by_user_id) VALUES (?,?,?)", 'isi', [$inv, $text, $me['id']]);
flash_set('success', 'Statement recorded.');
redirect($back);
