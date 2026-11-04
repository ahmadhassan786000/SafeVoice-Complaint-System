<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$q = trim($_GET['q'] ?? ''); $act = trim($_GET['action'] ?? '');
$sql = "SELECT a.id, a.action, a.description, a.affected_record_id, a.ip_address, a.created_at, u.full_name, r.role_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id LEFT JOIN roles r ON r.id=u.role_id WHERE 1=1";
$types = ''; $params = [];
if ($q !== '')   { $sql .= " AND (a.description LIKE ? OR u.full_name LIKE ?)"; $types .= 'ss'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($act !== '') { $sql .= " AND a.action = ?"; $types .= 's'; $params[] = $act; }
$rows = db_all($conn, $sql . " ORDER BY a.id DESC LIMIT 300", $types, $params);
$actions = array_column(db_all($conn, "SELECT DISTINCT action FROM audit_logs ORDER BY action"), 'action');
$page_title = 'Audit Logs'; $active = 'audit';
include ROOT_PATH . '/includes/panel-header.php';
?>
<h1 class="sv-page-title">Audit Logs</h1>
<form class="row g-2 mb-3" method="get"><div class="col-md-5"><input class="form-control" name="q" placeholder="Search description or user" value="<?= e($q) ?>"></div>
<div class="col-md-4"><select class="form-select" name="action"><option value="">All actions</option><?php foreach ($actions as $a): ?><option <?= $act === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select></div><div class="col-md-3"><button class="btn btn-sv">Filter</button> <a class="btn btn-outline-sv" href="audit-logs.php">Reset</a></div></form>
<div class="sv-card"><div class="table-responsive"><table class="table sv-table mb-0"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Description</th><th>Record</th><th>IP</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">No log entries.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?><tr><td class="text-nowrap small"><?= e(fmt_date($r['created_at'])) ?></td><td><?= e($r['full_name'] ?: 'System') ?><div class="small text-muted"><?= e($r['role_name']) ?></div></td><td><span class="badge bg-light text-dark border"><?= e($r['action']) ?></span></td><td class="small"><?= e($r['description']) ?></td><td><?= e($r['affected_record_id']) ?></td><td class="small"><?= e($r['ip_address']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
