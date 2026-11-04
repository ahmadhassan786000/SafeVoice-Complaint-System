<?php
/**
 * Student navigation bar - the ONE header used on every Student page AND on the public Home page when a Student is logged in.
 * Needs: $me (student row), optional $active ('home' | 'complaints' | 'submit' | 'messages').
 * Items: Home, My Complaints, Submit Complaint, Messages. (No Student Dashboard link.)
 */
$unread_notifs = (int)db_value($conn, "SELECT COUNT(*) FROM notifications WHERE recipient_user_id = ? AND is_read = 0", 'i', [$me['id']]);
$recent_notifs = db_all($conn, "SELECT id, title, message, notification_type, related_complaint_id, is_read, created_at FROM notifications WHERE recipient_user_id = ? ORDER BY id DESC LIMIT 5", 'i', [$me['id']]);
$unread_msgs = (int)db_value($conn, "SELECT COUNT(*) FROM complaint_messages m JOIN complaints c ON c.id = m.complaint_id WHERE c.student_user_id = ? AND m.sender_role = 'INVESTIGATOR' AND m.is_read = 0", 'i', [$me['id']]);
$nav = ['home' => ['index.php', 'Home'],
        'complaints' => ['student/my-complaints.php', 'My Complaints'],
        'submit' => ['student/submit-complaint.php', 'Submit Complaint'],
        'messages' => ['student/messages.php', 'Messages']];
$active = $active ?? '';
?>
<nav class="navbar navbar-expand-lg sv-student-nav sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 order-0" href="<?= url('index.php') ?>" aria-label="SafeVoice home">
      <img src="<?= url('assets/images/safevoice-logo.png') ?>" alt="SafeVoice" width="36" height="36"><span class="fw-bold">SafeVoice</span>
    </a>
    <div class="d-flex align-items-center gap-2 ms-auto order-1 order-lg-3">
      <div class="dropdown">
        <button class="btn btn-light position-relative rounded-circle" data-bs-toggle="dropdown" aria-label="Notifications" aria-expanded="false">
          <i class="bi bi-bell"></i>
          <?php if ($unread_notifs > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unread_notifs ?></span><?php endif; ?>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-0 sv-notif-menu">
          <div class="px-3 py-2 fw-semibold border-bottom">Notifications</div>
          <?php if (!$recent_notifs): ?><div class="p-3 text-muted small">No notifications yet.</div><?php endif; ?>
          <?php foreach ($recent_notifs as $n): ?>
            <form method="post" action="<?= url('actions/mark-notification-read.php') ?>" class="m-0">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
              <button class="dropdown-item sv-notif-item <?= $n['is_read'] ? '' : 'unread' ?>" type="submit">
                <div class="fw-semibold small"><?= e($n['title']) ?></div>
                <div class="small text-muted text-wrap"><?= e($n['message']) ?></div>
                <div class="small text-muted"><?= e(fmt_date($n['created_at'])) ?></div>
              </button>
            </form>
          <?php endforeach; ?>
          <a class="dropdown-item text-center small fw-semibold border-top py-2" href="<?= url('student/notifications.php') ?>">View all notifications</a>
        </div>
      </div>
      <div class="dropdown">
        <button class="btn p-0 d-flex align-items-center border-0" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open account menu for <?= e($me['full_name']) ?>">
          <img src="<?= e(avatar_url($me)) ?>" alt="" class="sv-avatar-sm me-2" width="28" height="28"><span class="d-none d-md-inline fw-semibold small sv-student-user-name"><?= e($me['full_name']) ?></span>
          <span class="d-md-none fw-semibold small">Account</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= url('student/profile.php') ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
          <li><a class="dropdown-item" href="<?= url('student/profile-settings.php') ?>"><i class="bi bi-gear me-2"></i>Profile Settings</a></li>
          <li><a class="dropdown-item" href="<?= url('auth/change-password.php') ?>"><i class="bi bi-key me-2"></i>Change Password</a></li>
          <li><a class="dropdown-item" href="<?= url('student/help.php') ?>"><i class="bi bi-life-preserver me-2"></i>Help &amp; Safety</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= url('auth/logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
    <button class="navbar-toggler order-2 d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#studentNav" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse order-3 order-lg-2" id="studentNav">
      <ul class="navbar-nav mx-auto">
        <?php foreach ($nav as $key => $item): ?>
          <li class="nav-item"><a class="nav-link <?= $active === $key ? 'active' : '' ?>" <?= $active === $key ? 'aria-current="page"' : '' ?> href="<?= url($item[0]) ?>"><?= e($item[1]) ?><?php if ($key === 'messages' && $unread_msgs > 0): ?> <span class="badge rounded-pill bg-danger ms-1"><?= $unread_msgs ?></span><?php endif; ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</nav>
