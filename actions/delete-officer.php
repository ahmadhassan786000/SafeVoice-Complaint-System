<?php
/** Safe delete of an Investigator. Refuses when open work or historical records depend on the account. */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_ADMIN);
csrf_require();

$id = (int)($_POST['officer_id'] ?? 0);
$t = get_user_by_id($conn, $id);
if (!$t || $t['role_code'] !== ROLE_OFFICER) { flash_set('danger', 'Investigator not found.'); redirect('admin/officers.php'); }

$open = (int)db_value($conn, "SELECT COUNT(*) FROM complaints WHERE assigned_officer_id = ? AND status NOT IN ('Resolved','Closed')", 'i', [$id]);
$cats = (int)db_value($conn, "SELECT COUNT(*) FROM category_assignments WHERE officer_user_id = ?", 'i', [$id]);
if ($open > 0) { flash_set('danger', "Cannot delete: $open open complaint(s) must be reassigned first."); redirect('admin/officer-details.php?id=' . $id); }
if ($cats > 0) { flash_set('danger', "Cannot delete: $cats category assignment(s) must be reassigned or removed first."); redirect('admin/officer-details.php?id=' . $id); }

$history = (int)db_value($conn, "SELECT
   (SELECT COUNT(*) FROM complaints WHERE assigned_officer_id = ?) + (SELECT COUNT(*) FROM complaint_assignments WHERE previous_officer_id = ? OR new_officer_id = ?)
 + (SELECT COUNT(*) FROM investigations WHERE officer_user_id = ?) + (SELECT COUNT(*) FROM statements WHERE recorded_by_user_id = ?)
 + (SELECT COUNT(*) FROM complaint_messages WHERE sender_user_id = ?) + (SELECT COUNT(*) FROM complaint_status_history WHERE changed_by_user_id = ?)
 + (SELECT COUNT(*) FROM complaint_evidence WHERE uploaded_by_user_id = ?)", 'iiiiiiii', array_fill(0, 8, $id));

if ($history > 0) {   // preserve historical records -> deactivate instead
    db_exec($conn, "UPDATE users SET account_status = 'inactive' WHERE id = ?", 'i', [$id]);
    flash_set('warning', 'This investigator has historical records, so the account was deactivated instead of deleted to preserve them.');
    redirect('admin/officers.php');
}
remove_profile_image($conn, $id);
db_exec($conn, "DELETE FROM users WHERE id = ? AND role_id = ?", 'ii', [$id, get_role_id($conn, ROLE_OFFICER)]);
flash_set('success', 'Investigator deleted.');
redirect('admin/officers.php');
