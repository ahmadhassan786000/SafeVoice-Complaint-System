<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$rows = db_all($conn, "SELECT cat.id, cat.category_name, cat.description, cat.status,
    (SELECT COUNT(*) FROM complaints c WHERE c.category_id = cat.id) AS used
    FROM complaint_categories cat ORDER BY cat.id");
$page_title = 'Category Management'; $active = 'categories';
include ROOT_PATH . '/includes/panel-header.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h1 class="sv-page-title mb-0">Category Management</h1><p class="text-muted mb-0">Manage complaint categories and their availability.</p></div><a class="btn btn-sv" href="<?= url('admin/add-category.php') ?>"><i class="bi bi-plus-circle me-1"></i>Add Category</a></div>
<div class="sv-card"><div class="table-responsive"><table class="table sv-table mb-0"><thead><tr><th>Category</th><th>Description</th><th>Complaints</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-4">No categories have been created yet.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?><tr><td class="fw-semibold"><?= e($r['category_name']) ?></td><td class="small text-muted"><?= e($r['description']) ?></td><td><?= (int)$r['used'] ?></td>
<td><span class="badge <?= $r['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= e($r['status']) ?></span></td>
<td class="text-nowrap"><button class="btn btn-sm btn-outline-sv" data-edit-category data-id="<?= (int)$r['id'] ?>" data-name="<?= e($r['category_name']) ?>" data-desc="<?= e($r['description']) ?>" data-bs-toggle="modal" data-bs-target="#editCat">Edit</button>
<form method="post" action="<?= url('actions/save-category.php') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="op" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-sv"><?= $r['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<div class="modal fade" id="editCat" tabindex="-1"><div class="modal-dialog"><form method="post" action="<?= url('actions/save-category.php') ?>" class="modal-content"><?= csrf_field() ?><input type="hidden" name="op" value="update"><input type="hidden" name="id" id="ec_id">
  <div class="modal-header"><h5 class="modal-title">Edit category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><label class="form-label">Name</label><input class="form-control mb-2" name="category_name" id="ec_name" required maxlength="100"><label class="form-label">Description</label><textarea class="form-control" name="description" id="ec_desc" rows="3" maxlength="500"></textarea></div>
  <div class="modal-footer"><button class="btn btn-sv">Save</button></div></form></div></div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
