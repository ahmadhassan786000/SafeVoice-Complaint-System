<?php
/** Admin: create / update / toggle a complaint category. Categories are never hard-deleted. */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_ADMIN);
csrf_require();

$op = $_POST['op'] ?? ''; $id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['category_name'] ?? ''); $desc = trim($_POST['description'] ?? '');
$status_input = $_POST['status'] ?? 'active';
$status = pick($status_input, ['active', 'inactive']);
$back = $op === 'create' ? 'admin/add-category.php' : 'admin/categories.php';

if ($op === 'toggle') {
    $c = db_one($conn, "SELECT category_name, status FROM complaint_categories WHERE id = ?", 'i', [$id]);
    if ($c) {
        $new = $c['status'] === 'active' ? 'inactive' : 'active';
        db_exec($conn, "UPDATE complaint_categories SET status = ? WHERE id = ?", 'si', [$new, $id]);
        flash_set('success', 'Category ' . $c['category_name'] . ' is now ' . $new . '.');
    }
    redirect($back);
}
if ($name === '' || mb_strlen($name) > 100 || mb_strlen($desc) > 500) {
    old_set($_POST);
    flash_set('danger', 'Category name is required (max 100) and description max 500 characters.');
    redirect($back);
}
if ($op === 'create' && $status === '') {
    old_set($_POST);
    flash_set('danger', 'Choose either Active or Inactive for the category status.');
    redirect($back);
}
if (db_value($conn, "SELECT id FROM complaint_categories WHERE category_name = ? AND id <> ?", 'si', [$name, $id])) {
    old_set($_POST);
    flash_set('danger', 'A category with that name already exists.');
    redirect($back);
}

if ($op === 'create') {
    $nid = db_insert($conn, "INSERT INTO complaint_categories (category_name, description, status) VALUES (?,?,?)", 'sss', [$name, $desc === '' ? null : $desc, $status]);
    if (!$nid) {
        old_set($_POST);
        flash_set('danger', 'Could not create the category. Check that the name is unique and try again.');
        redirect($back);
    }
    old_clear();
    flash_set('success', 'Category created.');
    redirect('admin/categories.php');
} elseif ($op === 'update' && $id) {
    if (db_exec($conn, "UPDATE complaint_categories SET category_name = ?, description = ? WHERE id = ?", 'ssi', [$name, $desc === '' ? null : $desc, $id]) === false) {
        flash_set('danger', 'Could not update the category.');
        redirect($back);
    }
    flash_set('success', 'Category updated.');
} else {
    flash_set('danger', 'Invalid category request.');
}
redirect($back);
