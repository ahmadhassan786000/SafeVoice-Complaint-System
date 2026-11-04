<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_OFFICER);
$oid = (int)$me['id'];

$s = db_one($conn, "SELECT COUNT(*) total,
    SUM(c.status = 'Pending' AND i.id IS NULL) new_c, SUM(c.status IN ('Pending','Under Review')) pending,
    SUM(c.status = 'Under Investigation') inv, SUM(c.status = 'Resolved') resolved, SUM(c.status = 'Closed') closed
    FROM complaints c LEFT JOIN investigations i ON i.complaint_id = c.id WHERE c.assigned_officer_id = ?", 'i', [$oid]);
$s['unread'] = (int)db_value($conn, "SELECT COUNT(*) FROM complaint_messages m JOIN complaints c ON c.id = m.complaint_id WHERE c.assigned_officer_id = ? AND m.sender_role = 'STUDENT' AND m.is_read = 0", 'i', [$oid]);
$recent = db_all($conn, "SELECT c.id, c.complaint_code, c.status, c.created_at, cat.category_name, d.school_name, u.full_name AS student_name
    FROM complaints c JOIN complaint_categories cat ON cat.id = c.category_id JOIN complaint_student_details d ON d.complaint_id = c.id JOIN users u ON u.id = c.student_user_id
    WHERE c.assigned_officer_id = ? ORDER BY c.id DESC LIMIT 8", 'i', [$oid]);

$page_title = 'Dashboard'; $active = 'dashboard';
include ROOT_PATH . '/includes/panel-header.php';
$cards = [['bi-folder2','Total Assigned',$s['total']],['bi-bell','New Complaints',$s['new_c']],['bi-hourglass-split','Pending',$s['pending']],['bi-search','Under Investigation',$s['inv']],['bi-check2-circle','Resolved',$s['resolved']],['bi-archive','Closed',$s['closed']],['bi-chat-dots','Unread Messages',$s['unread']]];
?>
<div class="sv-role-dashboard sv-officer-dashboard">
<header class="sv-dashboard-heading mb-4">
  <h1 class="sv-page-title mb-1">Investigator Dashboard</h1>
  <p class="text-muted mb-0">Welcome, <?= e($me['full_name']) ?>. Review your assigned caseload and recent activity.</p>
</header>
<section class="sv-dashboard-metrics mb-4" aria-label="Caseload overview">
  <h2 class="sv-dashboard-kicker mb-2">Caseload at a glance</h2>
  <div class="row g-3"><?php foreach ($cards as $c): ?>
    <div class="col-6 col-md-4 col-xl-3"><div class="sv-card sv-stat h-100"><div class="sv-stat-icon flex-shrink-0"><i class="bi <?= e($c[0]) ?>" aria-hidden="true"></i></div><div class="min-w-0"><div class="sv-stat-num"><?= (int)$c[2] ?></div><div class="sv-stat-label"><?= e($c[1]) ?></div></div></div></div><?php endforeach; ?></div>
</section>
<section class="sv-card sv-dashboard-workarea" aria-labelledby="officerRecentComplaints">
<div class="card-header-sv"><div><div class="sv-dashboard-kicker">Assigned casework</div><h2 class="h5 mb-0" id="officerRecentComplaints">Recent complaints</h2></div><a class="btn btn-sm btn-sv" href="<?= url('officer/complaints.php') ?>">View assigned cases</a></div>
<div class="table-responsive sv-dashboard-table-wrap" role="region" aria-label="Recent assigned complaints table" tabindex="0"><table class="table sv-table align-middle mb-0"><thead><tr><th scope="col">Complaint ID</th><th scope="col">Student</th><th scope="col">Category</th><th scope="col">School</th><th scope="col">Date</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead><tbody>
<?php if (!$recent): ?><tr><td colspan="7" class="text-center text-muted py-4">No complaints are assigned to you yet.</td></tr><?php endif; ?>
<?php foreach ($recent as $r): ?><tr><td class="fw-semibold"><?= e($r['complaint_code']) ?></td><td><?= e($r['student_name']) ?></td><td><?= e($r['category_name']) ?></td><td><?= e($r['school_name']) ?></td><td><?= e(fmt_date($r['created_at'], false)) ?></td><td><?= status_badge($r['status']) ?></td>
<td><div class="d-flex flex-wrap gap-1"><a class="btn btn-sm btn-sv" href="<?= url('officer/complaint-details.php?id=' . (int)$r['id']) ?>">View</a>
<a class="btn btn-sm btn-outline-sv" href="<?= url('officer/investigation.php?id=' . (int)$r['id']) ?>">Investigate</a>
<a class="btn btn-sm btn-outline-sv" aria-label="Message student about <?= e($r['complaint_code']) ?>" title="Messages" href="<?= url('officer/messages.php?c=' . (int)$r['id']) ?>"><i class="bi bi-chat-dots" aria-hidden="true"></i></a></div></td></tr><?php endforeach; ?>
</tbody></table></div>
</section>
</div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
