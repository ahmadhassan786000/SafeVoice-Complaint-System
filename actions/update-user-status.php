<?php
/** Admin activates / deactivates / suspends a Student or Investigator. Roles are never changed. */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_ADMIN);
csrf_require();

$id = (int)($_POST['user_id'] ?? 0);
$status = $_POST['status'] ?? '';
$back = safe_return('admin/users.php');
$t = get_user_by_id($conn, $id);

if (!$t || $t['role_code'] === ROLE_ADMIN || $id === (int)$me['id']) { flash_set('danger', 'That account cannot be changed here.'); redirect($back); }
if (!in_array($status, ['active', 'inactive', 'suspended'], true)) { flash_set('danger', 'Invalid status.'); redirect($back); }

db_exec($conn, "UPDATE users SET account_status = ? WHERE id = ?", 'si', [$status, $id]);
flash_set('success', $t['full_name'] . ' is now ' . $status . '.');
if ($t['role_code'] === ROLE_OFFICER && $status !== 'active') {
    $open = (int)db_value($conn, "SELECT COUNT(*) FROM complaints WHERE assigned_officer_id = ? AND status NOT IN ('Resolved','Closed')", 'i', [$id]);
    if ($open) flash_set('warning', "$open open complaint(s) are still assigned to this officer. Please reassign them. New complaints for their categories will stay Unassigned until reassigned.");
}
redirect($back);
