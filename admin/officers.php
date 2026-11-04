<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$q = trim($_GET['q'] ?? ''); $st = pick($_GET['status'] ?? '', ['active', 'inactive', 'suspended']);
$sql = "SELECT u.id, u.full_name, u.email, u.phone, u.profile_image, u.account_status, u.created_at, 'INVESTIGATOR' AS role_code,
   (SELECT GROUP_CONCAT(cat.category_name ORDER BY cat.id SEPARATOR ', ') FROM category_assignments ca JOIN complaint_categories cat ON cat.id=ca.category_id WHERE ca.officer_user_id=u.id) AS cats,
   (SELECT COUNT(*) FROM complaints c WHERE c.assigned_officer_id=u.id AND c.status NOT IN ('Resolved','Closed')) AS open_n,
   (SELECT COUNT(*) FROM complaints c WHERE c.assigned_officer_id=u.id) AS total_n
   FROM users u JOIN roles r ON r.id=u.role_id WHERE r.role_code='INVESTIGATOR'";
$types = ''; $params = [];
if ($q !== '') { $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)"; $types .= 'ss'; $l = "%$q%"; array_push($params, $l, $l); }
if ($st !== '') { $sql .= " AND u.account_status = ?"; $types .= 's'; $params[] = $st; }
$rows = db_all($conn, $sql . " ORDER BY u.id DESC", $types, $params);
$page_title = 'Investigation Officers'; $active = 'officers';
include ROOT_PATH . '/includes/panel-header.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h1 class="sv-page-title mb-0">Investigation Officers Management</h1><p class="text-muted mb-0">Only Investigator accounts. Created by Admin only.</p></div>
<a class="btn btn-sv" href="<?= url('admin/add-officer.php') ?>"><i class="bi bi-plus-circle me-1"></i>Add Investigator</a></div>
<form class="row g-2 mb-3" method="get"><div class="col-md-5"><input class="form-control" name="q" placeholder="Search name or email" value="<?= e($q) ?>"></div>
<div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option><?php foreach (['active', 'inactive', 'suspended'] as $s): ?><option <?= $st === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><button class="btn btn-sv">Filter</button> <a class="btn btn-outline-sv" href="officers.php">Reset</a></div></form>
<div class="sv-card"><div class="table-responsive"><table class="table sv-table mb-0"><thead><tr><th>Investigator</th><th>Email / Phone</th><th>Categories</th><th>Workload</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No investigators found.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?><tr><td><div class="d-flex align-items-center gap-2"><img class="sv-avatar" src="<?= e(avatar_url($r)) ?>" alt=""><?= e($r['full_name']) ?></div></td>
<td class="small"><?= e($r['email']) ?><br><?= e($r['phone'] ?: '-') ?></td><td class="small"><?= e($r['cats'] ?: '—') ?></td><td><?= (int)$r['open_n'] ?> open / <?= (int)$r['total_n'] ?> total</td>
<td><span class="badge <?= $r['account_status'] === 'active' ? 'bg-success' : ($r['account_status'] === 'suspended' ? 'bg-danger' : 'bg-secondary') ?>"><?= e($r['account_status']) ?></span></td><td><?= e(fmt_date($r['created_at'], false)) ?></td>
<td class="text-nowrap"><a class="btn btn-sm btn-sv" href="<?= url('admin/officer-details.php?id=' . (int)$r['id']) ?>">Manage</a>
<form method="post" action="<?= url('actions/update-user-status.php') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="return" value="admin/officers.php"><input type="hidden" name="status" value="<?= $r['account_status'] === 'active' ? 'inactive' : 'active' ?>"><button class="btn btn-sm btn-outline-sv"><?= $r['account_status'] === 'active' ? 'Deactivate' : 'Activate' ?></button></form>
<form method="post" action="<?= url('actions/delete-officer.php') ?>" class="d-inline" data-confirm="Delete this investigator? If they have history they will be deactivated instead."><?= csrf_field() ?><input type="hidden" name="officer_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
