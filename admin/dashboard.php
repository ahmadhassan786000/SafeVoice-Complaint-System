<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$n = function ($sql) use ($conn) { return (int)db_value($conn, $sql); };
$cards = [
 ['bi-people', 'Total Students', $n("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.role_code='STUDENT'")],
 ['bi-person-badge', 'Investigation Officers', $n("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.role_code='INVESTIGATOR'")],
 ['bi-person-check', 'Active Officers', $n("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.role_code='INVESTIGATOR' AND u.account_status='active'")],
 ['bi-folder2-open', 'Total Complaints', $n("SELECT COUNT(*) FROM complaints")],
 ['bi-hourglass-split', 'Pending Complaints', $n("SELECT COUNT(*) FROM complaints WHERE status='Pending'")],
 ['bi-search', 'Under Investigation', $n("SELECT COUNT(*) FROM complaints WHERE status='Under Investigation'")],
 ['bi-check2-circle', 'Resolved Complaints', $n("SELECT COUNT(*) FROM complaints WHERE status='Resolved'")],
 ['bi-question-circle', 'Unassigned Complaints', $n("SELECT COUNT(*) FROM complaints WHERE assigned_officer_id IS NULL")],
];
$recent = db_all($conn, "SELECT c.id, c.complaint_code, c.status, c.created_at, c.description, cat.category_name, d.student_name, o.full_name AS officer_name, c.assigned_officer_id
    FROM complaints c JOIN complaint_categories cat ON cat.id=c.category_id JOIN complaint_student_details d ON d.complaint_id=c.id LEFT JOIN users o ON o.id=c.assigned_officer_id ORDER BY c.id DESC LIMIT 8");

$page_title = 'Dashboard'; $active = 'dashboard';
include ROOT_PATH . '/includes/panel-header.php';
?>
<div class="sv-role-dashboard sv-admin-dashboard">
<header class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3 mb-4">
  <div>
    <div class="sv-dashboard-kicker">Administration</div>
    <h1 class="sv-page-title mb-1">Dashboard</h1>
    <p class="text-muted mb-0">SafeVoice platform overview</p>
  </div>
  <a class="btn btn-outline-sv flex-shrink-0" href="<?= url('admin/reports.php') ?>"><i class="bi bi-bar-chart-line me-1" aria-hidden="true"></i>Reports &amp; Analytics</a>
</header>
<section class="sv-dashboard-metrics mb-4" aria-label="Platform overview">
  <h2 class="sv-dashboard-kicker mb-2">Platform at a glance</h2>
  <div class="row g-3"><?php foreach ($cards as $c): ?>
    <div class="col-6 col-md-4 col-xl-3"><div class="sv-card sv-stat h-100"><div class="sv-stat-icon flex-shrink-0"><i class="bi <?= e($c[0]) ?>" aria-hidden="true"></i></div><div class="min-w-0"><div class="sv-stat-num"><?= (int)$c[2] ?></div><div class="sv-stat-label"><?= e($c[1]) ?></div></div></div></div><?php endforeach; ?></div>
</section>
<section class="sv-card sv-dashboard-workarea mb-4" aria-labelledby="adminRecentComplaints">
<div class="card-header-sv"><div><div class="sv-dashboard-kicker">Case management</div><h2 class="h5 mb-0" id="adminRecentComplaints">Recent complaints</h2></div><a class="btn btn-sm btn-outline-sv" href="<?= url('admin/complaints.php') ?>">View All</a></div>
<div class="table-responsive sv-dashboard-table-wrap" role="region" aria-label="Recent complaints table" tabindex="0"><table class="table sv-table align-middle mb-0"><thead><tr><th scope="col">Complaint ID</th><th scope="col">Student</th><th scope="col">Category</th><th scope="col">Assigned Investigator</th><th scope="col">Subject</th><th scope="col">Status</th><th scope="col">Date</th><th scope="col">Actions</th></tr></thead><tbody>
<?php if (!$recent): ?><tr><td colspan="8" class="text-center text-muted py-4">No complaints yet.</td></tr><?php endif; ?>
<?php foreach ($recent as $r): ?><tr><td class="fw-semibold"><?= e($r['complaint_code']) ?></td><td><?= e($r['student_name']) ?></td><td><?= e($r['category_name']) ?></td>
<td><?= $r['officer_name'] ? e($r['officer_name']) : status_badge('Unassigned') ?></td><td class="small text-muted"><?= e(mb_strimwidth($r['description'], 0, 50, '…')) ?></td><td><?= status_badge($r['status']) ?></td><td class="text-nowrap"><?= e(fmt_date($r['created_at'], false)) ?></td>
<td><div class="d-flex flex-wrap gap-1"><a class="btn btn-sm btn-sv" href="<?= url('admin/complaint-details.php?id=' . (int)$r['id']) ?>">Details</a> <a class="btn btn-sm btn-outline-sv" href="<?= url('admin/complaint-details.php?id=' . (int)$r['id'] . '#investigation') ?>">Investigation</a> <a class="btn btn-sm btn-outline-sv" aria-label="Reassign complaint <?= e($r['complaint_code']) ?>" title="Reassign" href="<?= url('admin/complaint-details.php?id=' . (int)$r['id'] . '#reassign') ?>"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></a></div></td></tr><?php endforeach; ?>
</tbody></table></div>
</section>
</div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
