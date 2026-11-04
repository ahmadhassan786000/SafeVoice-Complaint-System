<?php
/** Save summary, notes, findings and final report (assigned Officer only). */
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

$summary = trim($_POST['investigation_summary'] ?? ''); $notes = trim($_POST['investigation_notes'] ?? '');
$report  = trim($_POST['final_report'] ?? '');
$finding = pick($_POST['findings'] ?? '', ['Substantiated', 'Not Substantiated', 'Inconclusive', 'Referred'], '');
foreach ([$summary, $notes, $report] as $t) if (mb_strlen($t) > 10000) { flash_set('danger', 'One of the fields is too long.'); redirect($back); }

$old = db_one($conn, "SELECT id, investigation_summary, findings, final_report FROM investigations WHERE complaint_id = ?", 'i', [$cid]);
$f = $finding === '' ? null : $finding;
$s_ = $summary === '' ? null : $summary; $n_ = $notes === '' ? null : $notes; $r_ = $report === '' ? null : $report;

if ($old) {
    db_exec($conn, "UPDATE investigations SET investigation_summary = ?, investigation_notes = ?, findings = ?, final_report = ?, officer_user_id = ? WHERE complaint_id = ?",
            'ssssii', [$s_, $n_, $f, $r_, $me['id'], $cid]);
    $changed = ($old['investigation_summary'] ?? null) !== $s_ || ($old['findings'] ?? null) !== $f || ($old['final_report'] ?? null) !== $r_;
} else {
    db_insert($conn, "INSERT INTO investigations (complaint_id, officer_user_id, investigation_summary, investigation_notes, findings, final_report) VALUES (?,?,?,?,?,?)",
              'iissss', [$cid, $me['id'], $s_, $n_, $f, $r_]);
    $changed = true;
}
if ($changed) add_public_event($conn, $cid, (int)$me['id'], 'Investigation updated');
flash_set('success', 'Investigation saved.');
redirect($back);
