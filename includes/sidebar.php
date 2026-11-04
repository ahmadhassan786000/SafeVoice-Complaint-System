<?php
/** Admin / Investigator sidebar (no notifications anywhere). Needs $me and $active. */
$role = $me['role_code'];
if ($role === ROLE_ADMIN) {
    $unassigned = (int)db_value($conn, "SELECT COUNT(*) FROM complaints WHERE assigned_officer_id IS NULL AND status NOT IN ('Resolved','Closed')");
    $items = [
        'dashboard'  => ['admin/dashboard.php', 'bi-grid-1x2', 'Dashboard', 0],
        'users'      => ['admin/users.php', 'bi-people', 'Users', 0],
        'officers'   => ['admin/officers.php', 'bi-person-badge', 'Investigation Officers', 0],
        'categories' => ['admin/categories.php', 'bi-tags', 'Category Management', 0],
        'category-assignments' => ['admin/category-assignments.php', 'bi-diagram-3', 'Category Assignments', 0],
        'complaints' => ['admin/complaints.php', 'bi-folder2-open', 'Complaints', $unassigned],
        'settings'   => ['admin/settings.php', 'bi-sliders', 'System Settings', 0],
    ];
    $brand = 'SafeVoice Admin'; $badge = 'Administrator'; $brand_link = 'admin/profile.php';
} else {
    $unread = (int)db_value($conn, "SELECT COUNT(*) FROM complaint_messages m JOIN complaints c ON c.id = m.complaint_id
              WHERE c.assigned_officer_id = ? AND m.sender_role = 'STUDENT' AND m.is_read = 0", 'i', [$me['id']]);
    $items = [
        'dashboard'     => ['officer/dashboard.php', 'bi-grid-1x2', 'Dashboard', 0],
        'complaints'    => ['officer/complaints.php', 'bi-folder2-open', 'Assigned Complaints', 0],
        'investigation' => ['officer/investigation.php', 'bi-search', 'Investigation Management', 0],
        'messages'      => ['officer/messages.php', 'bi-chat-dots', 'Messages', $unread],
    ];
    $brand = 'SafeVoice'; $badge = 'Investigation Officer'; $brand_link = 'officer/dashboard.php';
}
?>
<a href="<?= url($brand_link) ?>" class="sv-side-brand text-decoration-none">
  <img src="<?= e(avatar_url($me)) ?>" alt="" class="sv-side-avatar" onerror="this.src='<?= url('assets/images/safevoice-logo.png') ?>'">
  <div>
    <div class="fw-bold text-white"><?= e($brand) ?></div>
    <span class="badge sv-role-badge"><?= e($badge) ?></span>
  </div>
</a>
<nav class="sv-side-nav flex-grow-1">
  <?php foreach ($items as $key => $it): ?>
    <a href="<?= url($it[0]) ?>" class="sv-nav-link <?= $active === $key ? 'active' : '' ?>">
      <i class="bi <?= e($it[1]) ?>"></i><span class="flex-grow-1"><?= e($it[2]) ?></span>
      <?php if ($it[3] > 0): ?><span class="badge rounded-pill bg-danger"><?= (int)$it[3] ?></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
</nav>
<div class="sv-side-bottom">
  <hr class="border-light opacity-25">
  <a href="<?= url('auth/logout.php') ?>" class="sv-nav-link"><i class="bi bi-box-arrow-left"></i><span>Logout</span></a>
  <a href="<?= url('public/home.php') ?>" class="sv-nav-link" target="_blank"><i class="bi bi-globe2"></i><span>View Website</span></a>
</div>
