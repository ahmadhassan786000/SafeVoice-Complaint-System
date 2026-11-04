<?php
/**
 * Reusable profile blocks.
 * Needs: $target (user row with id, profile_image, role_code, full_name, phone, email), $return (local path to return to),
 *        $show_info_form (bool). Used by student/profile-settings, officer/profile, admin/profile.
 */
?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="sv-card p-4 text-center">
      <img src="<?= e(avatar_url($target)) ?>" class="rounded-circle mb-3" style="width:140px;height:140px;object-fit:cover;border:4px solid var(--sv-green-light)" alt="Profile picture">
      <form method="post" action="<?= url('actions/upload-profile-picture.php') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="target_user_id" value="<?= (int)$target['id'] ?>"><input type="hidden" name="return" value="<?= e($return) ?>">
        <input type="file" class="form-control form-control-sm mb-2" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
        <div class="form-text mb-2">JPG, PNG or WEBP. Maximum 2 MB.</div>
        <button class="btn btn-sv btn-sm w-100"><i class="bi bi-upload me-1"></i><?= $target['profile_image'] ? 'Change Picture' : 'Upload Picture' ?></button>
      </form>
      <?php if ($target['profile_image']): ?>
        <form method="post" action="<?= url('actions/remove-profile-picture.php') ?>" class="mt-2" data-confirm="Remove this profile picture?">
          <?= csrf_field() ?><input type="hidden" name="target_user_id" value="<?= (int)$target['id'] ?>"><input type="hidden" name="return" value="<?= e($return) ?>">
          <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash me-1"></i>Remove Picture</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <?php if (!empty($show_info_form)): ?>
  <div class="col-lg-7">
    <div class="sv-card p-4">
      <h2 class="h5 mb-3">Personal information</h2>
      <form method="post" action="<?= url('actions/update-profile.php') ?>" novalidate>
        <?= csrf_field() ?><input type="hidden" name="return" value="<?= e($return) ?>">
        <div class="mb-3"><label class="form-label">Full Name</label><input class="form-control" name="full_name" maxlength="100" required value="<?= e($target['full_name']) ?>"></div>
        <div class="mb-3"><label class="form-label">Email Address</label><input class="form-control" value="<?= e($target['email']) ?>" disabled><div class="form-text">Email can only be changed by the administrator.</div></div>
        <div class="mb-3"><label class="form-label">Phone Number</label><input class="form-control" name="phone" maxlength="20" value="<?= e($target['phone'] ?? '') ?>"></div>
        <button class="btn btn-sv">Save Changes</button>
        <a class="btn btn-outline-sv" href="<?= url('auth/change-password.php') ?>"><i class="bi bi-key me-1"></i>Change Password</a>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>
