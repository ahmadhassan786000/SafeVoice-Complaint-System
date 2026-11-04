<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$q = trim($_GET['q'] ?? ''); $st = pick($_GET['status'] ?? '', ['active', 'inactive', 'suspended']);
$sql = "SELECT u.id, u.student_id, u.full_name, u.email, u.phone, u.profile_image, u.account_status, u.created_at, 'STUDENT' AS role_code,
        (SELECT COUNT(*) FROM complaints c WHERE c.student_user_id = u.id) AS complaints
        FROM users u JOIN roles r ON r.id = u.role_id WHERE r.role_code = 'STUDENT'";
$types = ''; $params = [];
if ($q !== '') { $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.student_id LIKE ?)"; $types .= 'sss'; $l = "%$q%"; array_push($params, $l, $l, $l); }
if ($st !== '') { $sql .= " AND u.account_status = ?"; $types .= 's'; $params[] = $st; }
$rows = db_all($conn, $sql . " ORDER BY u.id DESC LIMIT 300", $types, $params);
$page_title = 'Users'; $active = 'users';
include ROOT_PATH . '/includes/panel-header.php';
?>
<h1 class="sv-page-title">Users Management</h1><p class="text-muted">Student accounts only. Roles cannot be changed.</p>
<form class="row g-2 mb-3" method="get"><div class="col-md-5"><input class="form-control" name="q" placeholder="Search name, email or Student ID" value="<?= e($q) ?>"></div>
<div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option><?php foreach (['active', 'inactive', 'suspended'] as $s): ?><option <?= $st === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><button class="btn btn-sv">Filter</button> <a class="btn btn-outline-sv" href="users.php">Reset</a></div></form>
<div class="sv-card"><div class="table-responsive"><table class="table sv-table mb-0"><thead><tr><th>Student</th><th>Student ID</th><th>Email / Phone</th><th>Complaints</th><th>Registered</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No students found.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?><tr><td><div class="d-flex align-items-center gap-2"><img class="sv-avatar" src="<?= e(avatar_url($r)) ?>" alt=""><?= e($r['full_name']) ?></div></td><td><?= e($r['student_id']) ?></td>
<td class="small"><?= e($r['email']) ?><br><?= e($r['phone'] ?: '-') ?></td><td><?= (int)$r['complaints'] ?></td><td><?= e(fmt_date($r['created_at'], false)) ?></td>
<td><span class="badge <?= $r['account_status'] === 'active' ? 'bg-success' : ($r['account_status'] === 'suspended' ? 'bg-danger' : 'bg-secondary') ?>"><?= e($r['account_status']) ?></span></td>
<td class="text-nowrap"><form method="post" action="<?= url('actions/update-user-status.php') ?>" class="d-inline user-action-form"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="return" value="admin/users.php">
<select name="action" class="form-select form-select-sm" aria-label="Actions for <?= e($r['full_name']) ?>" data-user-actions>
  <option value="">Choose action…</option>
  <option value="<?= e(url('admin/user-details.php?id=' . (int)$r['id'])) ?>">View details</option>
  <?php if ($r['account_status'] === 'active'): ?><option value="suspended">Suspend</option><option value="inactive">Deactivate</option>
  <?php else: ?><option value="active">Activate</option><?php endif; ?>
</select><input type="hidden" name="status" value=""></form></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<script>
document.querySelectorAll('[data-user-actions]').forEach(function (select) {
  select.addEventListener('change', function () {
    var value = select.value;
    if (!value) return;
    if (value === 'active' || value === 'inactive' || value === 'suspended') {
      var label = value === 'active' ? 'activate' : (value === 'inactive' ? 'deactivate' : 'suspend');
      if (!window.confirm('Are you sure you want to ' + label + ' this account?')) {
        select.value = '';
        return;
      }
      select.form.querySelector('[name="status"]').value = value;
      select.form.submit();
      return;
    }
    window.location.assign(value);
  });
});
</script>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
