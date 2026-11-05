<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$q = trim($_GET['q'] ?? ''); $cat = (int)($_GET['category'] ?? 0);
$off = $_GET['officer'] ?? ''; $st = pick($_GET['status'] ?? '', COMPLAINT_STATUSES);
$sql = "SELECT c.id, c.complaint_code, c.status, c.created_at, c.assigned_officer_id, cat.category_name, d.school_name, d.student_name, s.student_id AS student_code, o.full_name AS officer_name
        FROM complaints c JOIN complaint_categories cat ON cat.id=c.category_id JOIN complaint_student_details d ON d.complaint_id=c.id JOIN users s ON s.id=c.student_user_id LEFT JOIN users o ON o.id=c.assigned_officer_id WHERE 1=1";
$types = ''; $params = [];
if ($q !== '')      { $sql .= " AND (c.complaint_code LIKE ? OR d.school_name LIKE ? OR d.student_name LIKE ? OR s.full_name LIKE ? OR s.student_id LIKE ?)"; $types .= 'sssss'; array_push($params, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%"); }
if ($cat)           { $sql .= " AND c.category_id = ?"; $types .= 'i'; $params[] = $cat; }
if ($off === 'none') $sql .= " AND c.assigned_officer_id IS NULL";
elseif ((int)$off > 0) { $sql .= " AND c.assigned_officer_id = ?"; $types .= 'i'; $params[] = (int)$off; }
if ($st !== '')     { $sql .= " AND c.status = ?"; $types .= 's'; $params[] = $st; }
$rows = db_all($conn, $sql . " ORDER BY c.id DESC LIMIT 300", $types, $params);
$cats = category_options($conn, false);
$officers = db_all($conn, "SELECT u.id, u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE r.role_code='INVESTIGATOR' ORDER BY u.full_name");
$page_title = 'Complaints'; $active = 'complaints';
include ROOT_PATH . '/includes/panel-header.php';
?>
<h1 class="sv-page-title">Complaints Management</h1>
<form class="row g-2 mb-3" method="get">
  <div class="col-12 col-md-6 col-lg-3"><input class="form-control" name="q" placeholder="Complaint ID, school, or student name / ID" aria-label="Search complaint ID, school, student name, or student ID" value="<?= e($q) ?>"></div>
  <div class="col-12 col-md-6 col-lg-2"><select class="form-select" name="category"><option value="0">All categories</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-12 col-md-6 col-lg-2"><select class="form-select" name="officer"><option value="">All investigators</option><option value="none" <?= $off === 'none' ? 'selected' : '' ?>>Unassigned</option><?php foreach ($officers as $o): ?><option value="<?= (int)$o['id'] ?>" <?= (string)$off === (string)$o['id'] ? 'selected' : '' ?>><?= e($o['full_name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-12 col-md-6 col-lg-2"><select class="form-select" name="status"><option value="">All statuses</option><?php foreach (COMPLAINT_STATUSES as $s): ?><option <?= $st === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
  <div class="col-12 col-md-6 col-lg-3">
    <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-sv" type="submit">Filter</button>
      <a class="btn btn-outline-sv" href="complaints.php">Reset</a>
    </div>
  </div>
</form>
<div class="sv-card"><div class="table-responsive"><table class="table sv-table mb-0"><thead><tr><th>Complaint ID</th><th>Student</th><th>Category</th><th>School</th><th>Investigator</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">No complaints found.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?><tr><td class="fw-semibold"><?= e($r['complaint_code']) ?></td><td><?= e($r['student_name']) ?><div class="small text-muted"><?= e($r['student_code']) ?></div></td><td><?= e($r['category_name']) ?></td><td><?= e($r['school_name']) ?></td>
<td><?= $r['officer_name'] ? e($r['officer_name']) : status_badge('Unassigned') ?></td><td><?= status_badge($r['status']) ?></td><td><?= e(fmt_date($r['created_at'], false)) ?></td><td><a class="btn btn-sm btn-sv" href="<?= url('admin/complaint-details.php?id=' . (int)$r['id']) ?>">View</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
