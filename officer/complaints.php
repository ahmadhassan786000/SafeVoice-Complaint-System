<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_OFFICER);

$q = trim($_GET['q'] ?? ''); $cat = (int)($_GET['category'] ?? 0); $st = pick($_GET['status'] ?? '', COMPLAINT_STATUSES);
$sql = "SELECT c.id, c.complaint_code, c.status, c.created_at, cat.category_name, d.school_name, d.student_name, u.id AS sid, u.profile_image
        FROM complaints c JOIN complaint_categories cat ON cat.id = c.category_id JOIN complaint_student_details d ON d.complaint_id = c.id JOIN users u ON u.id = c.student_user_id
        WHERE c.assigned_officer_id = ?";
$types = 'i'; $params = [$me['id']];
if ($q !== '') { $sql .= " AND c.complaint_code LIKE ?"; $types .= 's'; $params[] = "%$q%"; }
if ($cat) { $sql .= " AND c.category_id = ?"; $types .= 'i'; $params[] = $cat; }
if ($st !== '') { $sql .= " AND c.status = ?"; $types .= 's'; $params[] = $st; }
$rows = db_all($conn, $sql . " ORDER BY c.id DESC LIMIT 300", $types, $params);
$cats = category_options($conn, false);

$page_title = 'Assigned Complaints'; $active = 'complaints';
include ROOT_PATH . '/includes/panel-header.php';
?>
<h1 class="sv-page-title">Assigned Complaints</h1>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-4"><input class="form-control" name="q" placeholder="Search by Complaint ID" value="<?= e($q) ?>"></div>
  <div class="col-md-3"><select class="form-select" name="category"><option value="0">All categories</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option><?php foreach (COMPLAINT_STATUSES as $s): ?><option <?= $st === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><button class="btn btn-sv">Filter</button> <a class="btn btn-outline-sv" href="complaints.php">Reset</a></div>
</form>
<div class="sv-card"><div class="table-responsive"><table class="table sv-table mb-0"><thead><tr><th>Complaint ID</th><th>Student</th><th>Category</th><th>School</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No complaints found.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?><tr><td class="fw-semibold"><?= e($r['complaint_code']) ?></td>
<td><div class="d-flex align-items-center gap-2"><img class="sv-avatar" src="<?= e(avatar_url(['id' => $r['sid'], 'profile_image' => $r['profile_image'], 'role_code' => ROLE_STUDENT])) ?>" alt=""><?= e($r['student_name']) ?></div></td>
<td><?= e($r['category_name']) ?></td><td><?= e($r['school_name']) ?></td><td><?= e(fmt_date($r['created_at'], false)) ?></td><td><?= status_badge($r['status']) ?></td>
<td class="text-nowrap"><a class="btn btn-sm btn-sv" href="<?= url('officer/complaint-details.php?id=' . (int)$r['id']) ?>">Details</a>
<a class="btn btn-sm btn-outline-sv" href="<?= url('officer/investigation.php?id=' . (int)$r['id']) ?>">Investigation</a>
<a class="btn btn-sm btn-outline-sv" href="<?= url('officer/messages.php?c=' . (int)$r['id']) ?>"><i class="bi bi-chat-dots"></i></a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
