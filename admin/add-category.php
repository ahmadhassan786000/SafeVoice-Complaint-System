<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_ADMIN);

$page_title = 'Add Category'; $active = 'categories';
include ROOT_PATH . '/includes/panel-header.php';
?>
<a href="<?= url('admin/categories.php') ?>" class="small"><i class="bi bi-arrow-left"></i> Back to Category Management</a>
<h1 class="sv-page-title mt-2">Add Category</h1>
<p class="text-muted">Create a category for student complaints. Assign an investigator separately on Category Assignments.</p>
<form method="post" action="<?= url('actions/save-category.php') ?>" class="sv-card p-4" style="max-width:760px">
  <?= csrf_field() ?><input type="hidden" name="op" value="create">
  <div class="mb-3"><label class="form-label" for="category_name">Category Name *</label><input id="category_name" class="form-control" name="category_name" required maxlength="100" value="<?= e(old('category_name')) ?>"></div>
  <div class="mb-3"><label class="form-label" for="description">Description</label><textarea id="description" class="form-control" name="description" rows="4" maxlength="500"><?= e(old('description')) ?></textarea></div>
  <div class="mb-3"><label class="form-label" for="status">Status</label><select id="status" class="form-select" name="status"><option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
  <div class="d-flex gap-2"><button class="btn btn-sv"><i class="bi bi-plus-circle me-1"></i>Create Category</button><a class="btn btn-outline-sv" href="<?= url('admin/categories.php') ?>">Cancel</a></div>
</form>
<?php old_clear(); include ROOT_PATH . '/includes/panel-footer.php'; ?>
