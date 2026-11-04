<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$id = (int)($_GET['id'] ?? 0);
$t = get_user_by_id($conn, $id);
if (!$t || $t['role_code'] !== ROLE_OFFICER) { flash_set('danger', 'Investigator not found.'); redirect('admin/officers.php'); }
$stats = db_one($conn, "SELECT COUNT(*) total, SUM(status NOT IN ('Resolved','Closed')) open_n, SUM(status='Resolved') resolved, SUM(status='Closed') closed FROM complaints WHERE assigned_officer_id = ?", 'i', [$id]);
$cases = db_all($conn, "SELECT c.id, c.complaint_code, c.status, cat.category_name, s.full_name AS student_name FROM complaints c JOIN complaint_categories cat ON cat.id=c.category_id JOIN users s ON s.id=c.student_user_id WHERE c.assigned_officer_id = ? ORDER BY c.id DESC LIMIT 30", 'i', [$id]);
$page_title = 'Investigator ' . $t['full_name']; $active = 'officers';
include ROOT_PATH . '/includes/panel-header.php';
$target = $t; $return = 'admin/officer-details.php?id=' . $id; $show_info_form = false;
?>
<section class="sv-officer-detail" aria-labelledby="officer-title">
  <header class="sv-officer-heading">
    <a href="<?= url('admin/officers.php') ?>" class="sv-officer-back"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to Investigation Officers</a>
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mt-3">
      <div>
        <h1 class="sv-page-title mt-1 mb-2" id="officer-title"><?= e($t['full_name']) ?></h1>
        <div class="d-flex flex-wrap gap-2">
          <span class="badge rounded-pill bg-success-subtle text-success-emphasis"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Investigator</span>
          <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis"><?= e($t['account_status']) ?></span>
        </div>
      </div>
      <p class="sv-officer-context mb-1"><i class="bi bi-person-badge me-2" aria-hidden="true"></i>Manage account details, access and assigned work</p>
    </div>
  </header>

  <section class="row g-3 mt-2 mb-4" aria-label="Assigned complaint summary">
    <?php foreach ([['Total assigned', $stats['total'], 'bi-inbox'], ['Open', $stats['open_n'], 'bi-hourglass-split'], ['Resolved', $stats['resolved'], 'bi-check2-circle'], ['Closed', $stats['closed'], 'bi-archive']] as $s): ?>
      <div class="col-6 col-xl-3">
        <div class="sv-card sv-stat sv-officer-stat">
          <span class="sv-officer-stat-icon" aria-hidden="true"><i class="bi <?= e($s[2]) ?>"></i></span>
          <div>
            <div class="sv-stat-num"><?= (int)$s[1] ?></div>
            <div class="sv-stat-label"><?= e($s[0]) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="sv-card sv-officer-card mb-4" aria-labelledby="officer-details-title">
    <div class="sv-officer-card-heading">
      <div>
        <h2 class="h5 mb-1" id="officer-details-title">Profile and personal details</h2>
        <p class="text-muted small mb-0">Update the investigator’s contact information and profile picture.</p>
      </div>
      <i class="bi bi-person-lines-fill sv-officer-heading-icon" aria-hidden="true"></i>
    </div>
    <div class="p-4">
      <div class="row g-4 align-items-start">
        <div class="col-lg-4">
          <div class="sv-officer-profile-panel">
            <div class="sv-officer-profile">
              <img src="<?= e(avatar_url($t)) ?>" class="sv-avatar-md" alt="Profile picture of <?= e($t['full_name']) ?>">
              <div class="min-w-0">
                <div class="fw-semibold"><?= e($t['full_name']) ?></div>
                <div class="small text-muted">Profile picture</div>
              </div>
            </div>
            <form method="post" action="<?= url('actions/upload-profile-picture.php') ?>" enctype="multipart/form-data" class="sv-officer-upload mt-3">
              <?= csrf_field() ?><input type="hidden" name="target_user_id" value="<?= $id ?>"><input type="hidden" name="return" value="<?= e($return) ?>">
              <label class="visually-hidden" for="officer-profile-image">Choose a profile picture</label>
              <input id="officer-profile-image" type="file" class="form-control form-control-sm" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
              <button class="btn btn-sm btn-outline-sv"><i class="bi bi-upload me-1" aria-hidden="true"></i><?= $t['profile_image'] ? 'Replace picture' : 'Upload picture' ?></button>
            </form>
            <div class="form-text mt-2">JPG, PNG or WEBP. Maximum 2 MB.</div>
            <?php if ($t['profile_image']): ?>
              <form method="post" action="<?= url('actions/remove-profile-picture.php') ?>" class="mt-2" data-confirm="Remove this investigator's profile picture?">
                <?= csrf_field() ?><input type="hidden" name="target_user_id" value="<?= $id ?>"><input type="hidden" name="return" value="<?= e($return) ?>">
                <button class="btn btn-sm btn-link text-danger p-0 text-decoration-none"><i class="bi bi-trash me-1" aria-hidden="true"></i>Remove picture</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-8">
          <form method="post" action="<?= url('actions/update-officer.php') ?>" novalidate>
            <?= csrf_field() ?><input type="hidden" name="officer_id" value="<?= $id ?>"><input type="hidden" name="op" value="info">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="officer-full-name">Full name</label>
                <input id="officer-full-name" class="form-control" name="full_name" required value="<?= e($t['full_name']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="officer-email">Email</label>
                <input id="officer-email" type="email" class="form-control" name="email" required value="<?= e($t['email']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="officer-phone">Phone</label>
                <input id="officer-phone" class="form-control" name="phone" value="<?= e($t['phone'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="officer-username">Username</label>
                <input id="officer-username" class="form-control" name="username" value="<?= e($t['username'] ?? '') ?>">
              </div>
            </div>
            <div class="d-flex justify-content-end mt-4">
              <button class="btn btn-sv"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Save details</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>

  <section class="sv-card sv-officer-card mb-4" aria-labelledby="account-access-title">
    <div class="sv-officer-card-heading">
      <div>
        <h2 class="h5 mb-1" id="account-access-title">Account access</h2>
        <p class="text-muted small mb-0">Control account status, deletion and password access.</p>
      </div>
      <i class="bi bi-shield-lock sv-officer-heading-icon" aria-hidden="true"></i>
    </div>
    <div class="p-4">
      <div class="row g-4">
        <div class="col-lg-6">
          <section aria-labelledby="account-status-title">
            <h3 class="h6 fw-semibold mb-3" id="account-status-title">Account status</h3>
            <form method="post" action="<?= url('actions/update-user-status.php') ?>" class="sv-officer-status-form">
              <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $id ?>"><input type="hidden" name="return" value="<?= e($return) ?>">
              <div class="flex-grow-1">
                <label class="form-label" for="officer-status">Account status</label>
                <select id="officer-status" name="status" class="form-select"><?php foreach (['active', 'inactive', 'suspended'] as $s): ?><option <?= $t['account_status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
              </div>
              <button class="btn btn-sv align-self-end"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Update</button>
            </form>

            <div class="sv-officer-danger-zone mt-4">
              <div class="fw-semibold text-danger mb-1"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Delete investigator</div>
              <p class="small text-muted mb-3">Deletion may deactivate accounts with historical records or be blocked while assignments or open work remain.</p>
              <form method="post" action="<?= url('actions/delete-officer.php') ?>" data-confirm="Delete this investigator? If they have history they will be deactivated instead.">
                <?= csrf_field() ?><input type="hidden" name="officer_id" value="<?= $id ?>">
                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1" aria-hidden="true"></i>Delete (when safe)</button>
              </form>
            </div>
          </section>
        </div>
        <div class="col-lg-6 sv-officer-access-column">
          <section aria-labelledby="reset-password-title">
            <h3 class="h6 fw-semibold mb-3" id="reset-password-title">Reset password</h3>
            <p class="small text-muted mb-3">Set a new password for this account.</p>
            <form method="post" action="<?= url('actions/update-officer.php') ?>" autocomplete="off">
              <?= csrf_field() ?><input type="hidden" name="officer_id" value="<?= $id ?>"><input type="hidden" name="op" value="reset_password">
              <div class="mb-3">
                <label class="form-label" for="officer-new-password">New password</label>
                <input id="officer-new-password" type="password" class="form-control" name="new_password" required autocomplete="new-password">
              </div>
              <div class="mb-3">
                <label class="form-label" for="officer-confirm-password">Confirm password</label>
                <input id="officer-confirm-password" type="password" class="form-control" name="confirm_password" required autocomplete="new-password">
              </div>
              <button class="btn btn-outline-sv"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Reset password</button>
            </form>
          </section>
        </div>
      </div>
    </div>
  </section>

  <section class="sv-card sv-officer-card sv-officer-cases mt-4" aria-labelledby="assigned-cases-title">
    <div class="sv-officer-card-heading">
      <div>
        <h2 class="h5 mb-1" id="assigned-cases-title">Assigned complaints</h2>
        <p class="text-muted small mb-0">Most recently assigned cases for this investigator.</p>
      </div>
      <span class="sv-officer-case-count"><?= count($cases) ?> shown</span>
    </div>
    <div class="table-responsive">
      <table class="table sv-table mb-0">
        <thead>
          <tr><th scope="col">Complaint code</th><th scope="col">Student</th><th scope="col">Status</th><th scope="col" class="text-end">Action</th></tr>
        </thead>
        <tbody>
          <?php if (!$cases): ?>
            <tr><td colspan="4" class="text-center text-muted py-5"><i class="bi bi-inbox fs-4 d-block mb-2" aria-hidden="true"></i>No complaints are currently assigned.</td></tr>
          <?php endif; ?>
          <?php foreach ($cases as $c): ?>
            <tr>
              <td class="fw-semibold text-nowrap"><?= e($c['complaint_code']) ?></td>
              <td><?= e($c['student_name']) ?></td>
              <td><?= status_badge($c['status']) ?></td>
              <td class="text-end"><a class="btn btn-sm btn-outline-sv" href="<?= url('admin/complaint-details.php?id=' . (int)$c['id']) ?>">Open<i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
