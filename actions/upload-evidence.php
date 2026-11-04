<?php
/** Upload additional evidence to a complaint (student: own; officer: assigned; admin: any). */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
$me = require_login();
csrf_require();

$cid = (int)($_POST['complaint_id'] ?? 0);
$back = $me['role_code'] === ROLE_STUDENT ? 'student/evidence.php?c=' . $cid : dashboard_path_for_role($me['role_code']);
if (!can_access_complaint($conn, $cid, $me)) { http_response_code(403); flash_set('danger', 'Access denied.'); redirect(dashboard_path_for_role($me['role_code'])); }

$files = normalize_files('evidence');
if (!$files) { flash_set('danger', 'Please choose at least one file.'); redirect($back); }
$saved = 0;
foreach ($files as $f) {
    $e = save_evidence($conn, $cid, (int)$me['id'], $f);
    if ($e === '') $saved++; else flash_set('danger', $e);
}
if ($saved) {
    flash_set('success', "$saved file(s) uploaded.");
}
redirect($back);
