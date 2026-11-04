<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$id = (int)($_GET['id'] ?? 0);
$c = staff_get_complaint($conn, $id, $me);
if (!$c) { flash_set('danger', 'Complaint not found.'); redirect('admin/complaints.php'); }
$evidence = db_all($conn, "SELECT id, file_name, file_size, created_at FROM complaint_evidence WHERE complaint_id = ? ORDER BY id", 'i', [$id]);
$inv = db_one($conn, "SELECT * FROM investigations WHERE complaint_id = ?", 'i', [$id]);
$statements = $inv ? db_all($conn, "SELECT st.statement_details, st.created_at, u.full_name FROM statements st JOIN users u ON u.id = st.recorded_by_user_id WHERE st.investigation_id = ? ORDER BY st.id DESC", 'i', [$inv['id']]) : [];
$history = db_all($conn, "SELECT h.old_status, h.new_status, h.public_description, h.internal_notes, h.created_at, u.full_name FROM complaint_status_history h JOIN users u ON u.id = h.changed_by_user_id WHERE h.complaint_id = ? ORDER BY h.id DESC", 'i', [$id]);
$assignments = db_all($conn, "SELECT a.reassignment_reason, a.assigned_at, p.full_name AS prev_name, n.full_name AS new_name, ad.full_name AS admin_name FROM complaint_assignments a LEFT JOIN users p ON p.id=a.previous_officer_id LEFT JOIN users n ON n.id=a.new_officer_id LEFT JOIN users ad ON ad.id=a.assigned_by_admin_id WHERE a.complaint_id = ? ORDER BY a.id DESC", 'i', [$id]);
// Admin sees the REAL sender of every message
$msgs = db_all($conn, "SELECT m.sender_role, m.message, m.is_info_request, m.created_at, u.full_name, u.id AS uid, u.profile_image FROM complaint_messages m JOIN users u ON u.id = m.sender_user_id WHERE m.complaint_id = ? ORDER BY m.id", 'i', [$id]);
$officers = db_all($conn, "SELECT u.id, u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE r.role_code='INVESTIGATOR' AND u.account_status='active' ORDER BY u.full_name");
$student = ['id' => $c['student_uid'], 'profile_image' => $c['student_image'], 'role_code' => ROLE_STUDENT];
$officer = $c['assigned_officer_id'] ? ['id' => $c['assigned_officer_id'], 'profile_image' => $c['officer_image'], 'role_code' => ROLE_OFFICER] : null;
$ret = 'admin/complaint-details.php?id=' . $id;
$page_title = 'Complaint ' . $c['complaint_code']; $active = 'complaints';
include ROOT_PATH . '/includes/panel-header.php';
?>
<a href="<?= url('admin/complaints.php') ?>" class="small"><i class="bi bi-arrow-left"></i> Back to Complaints</a>
<div class="d-flex justify-content-between flex-wrap gap-2 my-2"><div><h1 class="sv-page-title mb-1"><?= e($c['complaint_code']) ?></h1><?= status_badge($c['status']) ?> <?= $c['assigned_officer_id'] ? '' : status_badge('Unassigned') ?></div></div>
<div class="row g-4">
  <div class="col-xl-4">
    <div class="sv-card p-4 mb-4"><h2 class="h6 text-muted">Student</h2><div class="d-flex align-items-center gap-3 mb-2"><img class="sv-avatar-md" src="<?= e(avatar_url($student)) ?>" alt=""><div><div class="fw-semibold"><?= e($c['student_name']) ?></div><div class="small text-muted"><?= e($c['student_code']) ?></div></div></div>
      <dl class="small mb-0"><dt>Email</dt><dd><?= e($c['student_email']) ?></dd><dt>Phone</dt><dd><?= e($c['student_phone'] ?: '-') ?></dd><dt>School / Roll / Class</dt><dd><?= e($c['school_name']) ?> / <?= e($c['school_roll_number']) ?> / <?= e($c['class_name']) ?></dd><dt>Gender</dt><dd class="mb-0"><?= e($c['gender']) ?></dd></dl></div>
    <div class="sv-card p-4 mb-4"><h2 class="h6 text-muted">Assigned Investigator</h2>
      <?php if ($officer): ?><div class="d-flex align-items-center gap-3"><img class="sv-avatar-md" src="<?= e(avatar_url($officer)) ?>" alt=""><div><div class="fw-semibold"><?= e($c['officer_name']) ?></div><div class="small text-muted"><?= e($c['officer_email']) ?></div></div></div><?php else: ?><?= status_badge('Unassigned') ?><?php endif; ?></div>
    <div class="sv-card p-4 mb-4" id="reassign"><h2 class="h6 text-muted">Reassign complaint</h2>
      <form method="post" action="<?= url('actions/reassign-complaint.php') ?>"><?= csrf_field() ?><input type="hidden" name="complaint_id" value="<?= $id ?>">
        <select class="form-select mb-2" name="officer_id" required><option value="">Select investigator…</option><?php foreach ($officers as $o): ?><option value="<?= (int)$o['id'] ?>" <?= (int)$o['id'] === (int)$c['assigned_officer_id'] ? 'disabled' : '' ?>><?= e($o['full_name']) ?></option><?php endforeach; ?></select>
        <textarea class="form-control mb-2" name="reason" rows="2" placeholder="Reason (required)" required minlength="5" maxlength="250"></textarea><button class="btn btn-sv w-100">Reassign</button></form></div>
    <form method="post" action="<?= url('actions/update-status.php') ?>" class="sv-card p-4"><?= csrf_field() ?><input type="hidden" name="complaint_id" value="<?= $id ?>"><input type="hidden" name="return" value="<?= e($ret) ?>"><h2 class="h6 text-muted">Update status</h2>
      <select class="form-select mb-2" name="status"><?php foreach (['Under Review', 'Under Investigation', 'Resolved', 'Closed'] as $s): ?><option <?= $c['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
      <textarea class="form-control mb-2" name="internal_notes" rows="2" placeholder="Internal note (hidden from student)" maxlength="2000"></textarea><button class="btn btn-outline-sv w-100">Update</button></form>
  </div>
  <div class="col-xl-8">
    <div class="sv-card p-4 mb-4"><div class="row g-2 small"><div class="col-md-4"><span class="text-muted">Category</span><div class="fw-semibold"><?= e($c['category_name']) ?></div></div><div class="col-md-4"><span class="text-muted">Reported person</span><div class="fw-semibold"><?= e(trim($c['reported_person_type'] . ' ' . ($c['reported_person_name'] ? '— ' . $c['reported_person_name'] : ''))) ?></div></div><div class="col-md-4"><span class="text-muted">Incident</span><div class="fw-semibold"><?= e(fmt_date($c['incident_date'], false)) ?>, <?= e($c['incident_location']) ?></div></div>
      <div class="col-md-6"><span class="text-muted">Submitted</span><div class="fw-semibold"><?= e(fmt_date($c['created_at'])) ?></div></div><div class="col-md-6"><span class="text-muted">Updated</span><div class="fw-semibold"><?= e(fmt_date($c['updated_at'])) ?></div></div></div><hr>
      <div class="sv-pre"><?= e($c['description']) ?></div><?php if ($c['witness_information']): ?><hr><div class="small text-muted">Witnesses</div><div class="sv-pre"><?= e($c['witness_information']) ?></div><?php endif; ?></div>
    <div class="sv-card mb-4"><div class="card-header-sv">Evidence (<?= count($evidence) ?>)</div><ul class="list-group list-group-flush"><?php foreach ($evidence as $ev): ?><li class="list-group-item d-flex justify-content-between"><span><i class="bi bi-file-earmark me-2"></i><?= e($ev['file_name']) ?></span><span><a target="_blank" class="btn btn-sm btn-outline-sv" href="<?= url('actions/evidence-file.php?id=' . (int)$ev['id']) ?>">Open</a> <a class="btn btn-sm btn-outline-sv" href="<?= url('actions/evidence-file.php?id=' . (int)$ev['id'] . '&dl=1') ?>">Download</a></span></li><?php endforeach; ?><?php if (!$evidence): ?><li class="list-group-item text-muted">None.</li><?php endif; ?></ul></div>
    <div class="sv-card p-4 mb-4" id="investigation"><h2 class="h5">Investigation</h2>
      <?php if (!$inv): ?><p class="text-muted mb-0">No investigation record yet.</p><?php else: ?>
        <p class="small mb-1"><strong>Finding:</strong> <?= e($inv['findings'] ?: 'Not decided') ?></p><div class="small text-muted">Summary</div><div class="sv-pre mb-2"><?= e($inv['investigation_summary'] ?: '-') ?></div>
        <div class="small text-muted">Notes</div><div class="sv-pre mb-2"><?= e($inv['investigation_notes'] ?: '-') ?></div><div class="small text-muted">Final report</div><div class="sv-pre mb-2"><?= e($inv['final_report'] ?: '-') ?></div>
        <div class="small text-muted">Statements (<?= count($statements) ?>)</div><?php foreach ($statements as $s): ?><div class="border rounded p-2 my-1 small"><div class="text-muted"><?= e($s['full_name']) ?> · <?= e(fmt_date($s['created_at'])) ?></div><div class="sv-pre"><?= e($s['statement_details']) ?></div></div><?php endforeach; ?><?php endif; ?></div>
    <div class="sv-card mb-4"><div class="card-header-sv">Messages (real senders)</div><div class="sv-chat m-3"><?php if (!$msgs): ?><div class="text-muted small text-center">No messages.</div><?php endif; ?>
      <?php foreach ($msgs as $m): $mine = $m['sender_role'] === 'INVESTIGATOR'; ?><div class="sv-msg <?= $mine ? 'mine' : '' ?>"><img class="sv-avatar flex-shrink-0" alt="" src="<?= e(avatar_url(['id' => $m['uid'], 'profile_image' => $m['profile_image'], 'role_code' => $mine ? ROLE_OFFICER : ROLE_STUDENT])) ?>"><div><div class="sv-msg-meta"><?= e($m['full_name']) ?> (<?= $mine ? 'Investigator' : 'Student' ?>) · <?= e(fmt_date($m['created_at'])) ?></div><div class="sv-msg-bubble sv-pre"><?= $m['is_info_request'] ? '<span class="badge bg-warning text-dark">Info request</span> ' : '' ?><?= e($m['message']) ?></div></div></div><?php endforeach; ?></div></div>
    <div class="row g-4"><div class="col-lg-6"><div class="sv-card"><div class="card-header-sv">Status history</div><ul class="list-group list-group-flush"><?php foreach ($history as $h): ?><li class="list-group-item small"><div class="d-flex justify-content-between"><strong><?= e($h['public_description']) ?></strong><span class="text-muted"><?= e(fmt_date($h['created_at'])) ?></span></div><div class="text-muted">by <?= e($h['full_name']) ?></div><?php if ($h['internal_notes']): ?><div class="sv-pre">Internal: <?= e($h['internal_notes']) ?></div><?php endif; ?></li><?php endforeach; ?></ul></div></div>
    <div class="col-lg-6"><div class="sv-card"><div class="card-header-sv">Assignment history</div><ul class="list-group list-group-flush"><?php foreach ($assignments as $a): ?><li class="list-group-item small"><div class="d-flex justify-content-between"><strong><?= e($a['prev_name'] ?: 'None') ?> → <?= e($a['new_name'] ?: 'None') ?></strong><span class="text-muted"><?= e(fmt_date($a['assigned_at'])) ?></span></div><div class="text-muted">By <?= e($a['admin_name'] ?: 'Automatic routing') ?><?= $a['reassignment_reason'] ? ' — ' . e($a['reassignment_reason']) : '' ?></div></li><?php endforeach; ?><?php if (!$assignments): ?><li class="list-group-item text-muted small">No assignments yet (category unassigned).</li><?php endif; ?></ul></div></div></div>
  </div>
</div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
