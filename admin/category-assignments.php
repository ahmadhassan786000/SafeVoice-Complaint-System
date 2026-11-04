<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$rows = db_all($conn, "SELECT cat.id, cat.category_name, cat.status, ca.assigned_at, ca.officer_user_id, o.full_name AS officer, o.profile_image, o.account_status
    FROM complaint_categories cat LEFT JOIN category_assignments ca ON ca.category_id = cat.id LEFT JOIN users o ON o.id = ca.officer_user_id ORDER BY cat.id");
$officers = db_all($conn, "SELECT u.id, u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE r.role_code='INVESTIGATOR' AND u.account_status='active' ORDER BY u.full_name");
$page_title = 'Category Assignments'; $active = 'category-assignments';
include ROOT_PATH . '/includes/panel-header.php';
?>
<div class="sv-category-page">
  <a href="<?= url('admin/categories.php') ?>" class="sv-category-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Category Management</a>
  <div class="sv-category-heading">
    <div>
      <h1 class="sv-page-title">Category Assignments</h1>
      <p class="sv-category-intro">Choose the active investigator who will receive new complaints in each category.</p>
    </div>
    <div class="sv-category-guidance"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Changing an assignment routes new complaints automatically. Existing open complaints must be reassigned deliberately.</span></div>
  </div>

  <?php if (!$officers): ?>
    <div class="alert alert-warning sv-category-warning" role="alert">
      <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
      <span>There are no active investigators. <a href="<?= url('admin/add-officer.php') ?>">Create one</a> first.</span>
    </div>
  <?php endif; ?>

  <section class="sv-card sv-category-card" aria-label="Complaint category assignments">
    <div class="table-responsive sv-category-table-wrap">
      <table class="table sv-table sv-category-table mb-0">
        <colgroup><col class="sv-category-col-name"><col class="sv-category-col-status"><col class="sv-category-col-current"><col class="sv-category-col-controls"></colgroup>
        <thead><tr><th scope="col">Category</th><th scope="col">Category status</th><th scope="col">Current investigator</th><th scope="col">Assign / change</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="4" class="sv-category-empty"><i class="bi bi-inboxes" aria-hidden="true"></i><span>No complaint categories have been created yet.</span></td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="sv-category-name-cell" data-label="Category">
                <span class="sv-category-name"><?= e($r['category_name']) ?></span>
              </td>
              <td class="sv-category-status-cell" data-label="Category status">
                <span class="badge <?= $r['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= e(ucfirst($r['status'])) ?></span>
              </td>
              <td data-label="Current investigator">
                <?php if ($r['officer_user_id']): ?>
                  <div class="sv-category-assignee">
                    <img class="sv-avatar" src="<?= e(avatar_url(['id' => $r['officer_user_id'], 'profile_image' => $r['profile_image'], 'role_code' => ROLE_OFFICER])) ?>" alt="">
                    <div class="sv-category-assignee-details">
                      <div class="sv-category-assignee-name"><?= e($r['officer']) ?><?php if ($r['account_status'] !== 'active'): ?> <span class="badge bg-danger"><?= e($r['account_status']) ?></span><?php endif; ?></div>
                      <div class="sv-category-assigned-date"><i class="bi bi-calendar3" aria-hidden="true"></i> Assigned <?= e($r['assigned_at'] ? fmt_date($r['assigned_at'], false) : '—') ?></div>
                    </div>
                  </div>
                <?php else: ?>
                  <?= status_badge('Unassigned') ?>
                <?php endif; ?>
              </td>
              <td class="sv-category-controls-cell" data-label="Assign / change">
                <div class="sv-category-controls">
                  <form method="post" action="<?= url('actions/assign-category.php') ?>" class="sv-category-set-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="mode" value="set">
                    <input type="hidden" name="category_id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="return" value="admin/category-assignments.php">
                    <label class="visually-hidden" for="category-investigator-<?= (int)$r['id'] ?>">Investigator for <?= e($r['category_name']) ?></label>
                    <select id="category-investigator-<?= (int)$r['id'] ?>" name="officer_id" class="form-select form-select-sm sv-category-select" required>
                      <option value="">Select investigator…</option>
                      <?php foreach ($officers as $o): ?>
                        <option value="<?= (int)$o['id'] ?>" <?= (int)$o['id'] === (int)$r['officer_user_id'] ? 'selected' : '' ?>><?= e($o['full_name']) ?></option>
                      <?php endforeach; ?>
                      <?php if ($r['officer_user_id']): ?>
                        <option value="__remove">Unassign category</option>
                      <?php endif; ?>
                    </select>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<script>
document.querySelectorAll('.sv-category-select').forEach(function (select) {
  var previousValue = select.value;
  select.addEventListener('change', function () {
    var mode = select.form.querySelector('input[name="mode"]');
    if (select.value === '__remove') {
      if (!window.confirm('Remove this assignment? New complaints will stay Unassigned.')) {
        select.value = previousValue;
        return;
      }
      mode.value = 'remove';
      select.disabled = true;
      select.form.requestSubmit();
      return;
    }
    if (select.value) {
      mode.value = 'set';
      previousValue = select.value;
      select.form.requestSubmit();
    } else {
      previousValue = select.value;
    }
  });
});
</script>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
