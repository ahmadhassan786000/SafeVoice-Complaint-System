<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_STUDENT);

$q = trim($_GET['q'] ?? ''); $st = pick($_GET['status'] ?? '', COMPLAINT_STATUSES);
$sql = "SELECT c.id, c.complaint_code, c.status, c.created_at, cat.category_name, d.school_name
        FROM complaints c JOIN complaint_categories cat ON cat.id = c.category_id JOIN complaint_student_details d ON d.complaint_id = c.id
        WHERE c.student_user_id = ?";
$types = 'i'; $params = [$me['id']];
if ($q !== '')  { $sql .= " AND (c.complaint_code LIKE ? OR d.school_name LIKE ? OR cat.category_name LIKE ?)"; $types .= 'sss'; $l = "%$q%"; array_push($params, $l, $l, $l); }
if ($st !== '') { $sql .= " AND c.status = ?"; $types .= 's'; $params[] = $st; }
$rows = db_all($conn, $sql . " ORDER BY c.id DESC LIMIT 200", $types, $params);

$page_title = 'My Complaints'; $active = 'complaints';
include ROOT_PATH . '/includes/student-header.php';
?>
<div class="sv-page-head">
  <div><h1 class="sv-page-title">My Complaints</h1><p class="sv-page-sub">Track the status of every complaint you have submitted.</p></div>
  <a class="btn btn-sv" href="<?= url('student/submit-complaint.php') ?>"><i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Submit Complaint</a>
</div>
<form class="sv-card sv-filter-bar" method="get" role="search">
  <div class="flex-grow-1" style="flex-basis:260px"><label class="visually-hidden" for="q">Search complaints</label><input class="form-control" id="q" name="q" placeholder="Search by Complaint ID, school or category" value="<?= e($q) ?>"></div>
  <div style="flex-basis:200px"><label class="visually-hidden" for="statusFilter">Status</label><select class="form-select" id="statusFilter" name="status"><option value="">All statuses</option><?php foreach (COMPLAINT_STATUSES as $s): ?><option <?= $st === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
  <div class="d-flex gap-2"><button class="btn btn-sv">Filter</button> <a class="btn btn-outline-sv" href="my-complaints.php">Reset</a></div>
</form>
<div class="sv-card"><div class="table-responsive"><table class="table sv-table mb-0">
  <thead><tr><th>Complaint ID</th><th>Category</th><th>School</th><th>Submitted</th><th>Status</th><th></th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="6"><div class="sv-empty-state"><i class="bi bi-inbox" aria-hidden="true"></i><strong>No complaints found</strong><span>Try a different search, or submit a new complaint.</span></div></td></tr><?php endif; ?>
  <?php foreach ($rows as $r): ?><tr>
    <td class="fw-semibold"><?= e($r['complaint_code']) ?></td><td><?= e($r['category_name']) ?></td><td><?= e($r['school_name']) ?></td>
    <td><?= e(fmt_date($r['created_at'], false)) ?></td><td><?= status_badge($r['status']) ?></td>
    <td><a class="btn btn-sm btn-sv" href="<?= url('student/complaint-details.php?id=' . (int)$r['id']) ?>">View Details</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php include ROOT_PATH . '/includes/student-footer.php'; ?>
