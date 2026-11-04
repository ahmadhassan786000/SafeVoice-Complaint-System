<?php
/** Change a complaint's workflow status (assigned Officer or Admin). */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role([ROLE_OFFICER, ROLE_ADMIN]);
csrf_require();

$cid = (int)($_POST['complaint_id'] ?? 0);
$back = safe_return(dashboard_path_for_role($me['role_code']));
if (!can_access_complaint($conn, $cid, $me)) { http_response_code(403); flash_set('danger', 'Access denied.'); redirect(dashboard_path_for_role($me['role_code'])); }

$new = $_POST['status'] ?? '';
$notes = trim($_POST['internal_notes'] ?? '');
if (!in_array($new, COMPLAINT_STATUSES, true) || $new === 'Pending') { flash_set('danger', 'Please choose a valid status.'); redirect($back); }
if (mb_strlen($notes) > 2000) { flash_set('danger', 'Internal notes are too long.'); redirect($back); }

if (set_complaint_status($conn, $cid, $new, (int)$me['id'], $notes === '' ? null : $notes)) flash_set('success', 'Status updated.');
else flash_set('danger', 'Could not update the status.');
redirect($back);
