<?php
/** Admin: controlled reassignment of one complaint (reason mandatory, full history kept). */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_ADMIN);
csrf_require();

$cid = (int)($_POST['complaint_id'] ?? 0); $officer = (int)($_POST['officer_id'] ?? 0); $reason = trim($_POST['reason'] ?? '');
$back = 'admin/complaint-details.php?id=' . $cid;
$c = db_one($conn, "SELECT id, complaint_code, assigned_officer_id, status FROM complaints WHERE id = ?", 'i', [$cid]);
if (!$c) { flash_set('danger', 'Complaint not found.'); redirect('admin/complaints.php'); }
if (!db_value($conn, "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND r.role_code = 'INVESTIGATOR' AND u.account_status = 'active'", 'i', [$officer])) { flash_set('danger', 'Choose an active investigator.'); redirect($back); }
if ((int)$c['assigned_officer_id'] === $officer) { flash_set('info', 'That investigator already handles this complaint.'); redirect($back); }
if (mb_strlen($reason) < 5 || mb_strlen($reason) > 250) { flash_set('danger', 'Please give a reason (5-250 characters).'); redirect($back); }

if (assign_complaint($conn, $cid, $officer, (int)$me['id'], $reason)) {
    flash_set('success', 'Complaint reassigned. History has been recorded.');
} else flash_set('danger', 'Could not reassign the complaint.');
redirect($back);
