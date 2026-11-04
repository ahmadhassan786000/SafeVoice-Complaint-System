<?php
/**
 * Category assignment management (Admin only).
 *  mode=set          category_id + officer_id (+reason)  -> assign / change
 *  mode=remove       category_id                        -> remove assignment
 *  mode=officer_set  officer_id + categories[]          -> sync one officer's categories
 * UNIQUE(category_id) guarantees ONE officer per category; we also validate server-side.
 */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_ADMIN);
csrf_require();

$back = safe_return('admin/category-assignments.php');
$mode = $_POST['mode'] ?? '';
$aid = (int)$me['id'];

function valid_officer($conn, $id) {
    return (bool)db_value($conn, "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND r.role_code = 'INVESTIGATOR' AND u.account_status = 'active'", 'i', [$id]);
}
function set_assignment($conn, $cat, $officer, $aid, $reason) {
    $prev = db_value($conn, "SELECT officer_user_id FROM category_assignments WHERE category_id = ?", 'i', [$cat]);
    if ($prev !== null && (int)$prev === $officer) return 'same';
    $ok = db_exec($conn, "INSERT INTO category_assignments (category_id, officer_user_id, assigned_by_admin_id) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE officer_user_id = VALUES(officer_user_id), assigned_by_admin_id = VALUES(assigned_by_admin_id)", 'iii', [$cat, $officer, $aid]);
    if ($ok === false) return 'error';
    $name = db_value($conn, "SELECT category_name FROM complaint_categories WHERE id = ?", 'i', [$cat]);
    $stale = (int)db_value($conn, "SELECT COUNT(*) FROM complaints WHERE category_id = ? AND status NOT IN ('Resolved','Closed') AND (assigned_officer_id IS NULL OR assigned_officer_id <> ?)", 'ii', [$cat, $officer]);
    return $stale;
}

if ($mode === 'set') {
    $cat = (int)($_POST['category_id'] ?? 0); $officer = (int)($_POST['officer_id'] ?? 0); $reason = trim($_POST['reason'] ?? '');
    if (!db_value($conn, "SELECT id FROM complaint_categories WHERE id = ?", 'i', [$cat]) || !valid_officer($conn, $officer)) { flash_set('danger', 'Choose a valid category and an active investigator.'); redirect($back); }
    $r = set_assignment($conn, $cat, $officer, $aid, mb_substr($reason, 0, 200));
    if ($r === 'same') flash_set('info', 'That investigator is already assigned to this category.');
    elseif ($r === 'error') flash_set('danger', 'Could not save the assignment.');
    else { flash_set('success', 'Assignment saved. New complaints in this category go to the new investigator.');
           if ($r > 0) flash_set('warning', "$r existing open complaint(s) in this category are not with this investigator. They are NOT moved automatically — reassign them from Complaints."); }
} elseif ($mode === 'remove') {
    $cat = (int)($_POST['category_id'] ?? 0);
    $name = db_value($conn, "SELECT category_name FROM complaint_categories WHERE id = ?", 'i', [$cat]);
    db_exec($conn, "DELETE FROM category_assignments WHERE category_id = ?", 'i', [$cat]);
    flash_set('success', 'Assignment removed. New complaints in this category will stay Unassigned.');
} elseif ($mode === 'officer_set') {
    $officer = (int)($_POST['officer_id'] ?? 0);
    if (!valid_officer($conn, $officer)) { flash_set('danger', 'The investigator must be active to receive categories.'); redirect($back); }
    $wanted = array_map('intval', (array)($_POST['categories'] ?? []));
    $mine = array_map('intval', array_column(db_all($conn, "SELECT category_id FROM category_assignments WHERE officer_user_id = ?", 'i', [$officer]), 'category_id'));
    $blocked = 0; $added = 0; $removed = 0;
    foreach ($wanted as $cat) {
        if (in_array($cat, $mine, true)) continue;
        $holder = db_value($conn, "SELECT officer_user_id FROM category_assignments WHERE category_id = ?", 'i', [$cat]);
        if ($holder !== null && (int)$holder !== $officer) { $blocked++; continue; }   // never steal silently
        if (db_value($conn, "SELECT id FROM complaint_categories WHERE id = ?", 'i', [$cat])) { set_assignment($conn, $cat, $officer, $aid, ''); $added++; }
    }
    foreach ($mine as $cat) if (!in_array($cat, $wanted, true)) {
        db_exec($conn, "DELETE FROM category_assignments WHERE category_id = ? AND officer_user_id = ?", 'ii', [$cat, $officer]);
 $removed++;
    }
    flash_set('success', "Assignments updated ($added added, $removed removed).");
    if ($blocked) flash_set('warning', "$blocked category(ies) already belong to another investigator and were skipped. Use Category Assignments to change them deliberately.");
} else { flash_set('danger', 'Invalid request.'); }
redirect($back);
