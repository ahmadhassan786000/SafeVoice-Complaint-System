<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
require_once ROOT_PATH . '/includes/public-content.php';
$me = require_role(ROLE_STUDENT);

$uid = (int)$me['id'];
$stats = db_one($conn, "SELECT COUNT(*) total, SUM(status='Pending') pending, SUM(status='Under Investigation') inv, SUM(status='Resolved') resolved FROM complaints WHERE student_user_id = ?", 'i', [$uid]);
$recent = db_all($conn, "SELECT c.id, c.complaint_code, c.status, c.created_at, cat.category_name FROM complaints c JOIN complaint_categories cat ON cat.id = c.category_id WHERE c.student_user_id = ? ORDER BY c.id DESC LIMIT 5", 'i', [$uid]);
$messages = db_all($conn, "SELECT m.complaint_id, m.message, m.is_read, m.created_at, c.complaint_code FROM complaint_messages m JOIN complaints c ON c.id = m.complaint_id WHERE c.student_user_id = ? AND m.sender_role = 'INVESTIGATOR' ORDER BY m.id DESC LIMIT 3", 'i', [$uid]);
$progress_map = ['Pending' => 20, 'Under Review' => 40, 'Under Investigation' => 65, 'Resolved' => 90, 'Closed' => 100];

$hour = (int)date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$unread_msgs = (int)db_value($conn, "SELECT COUNT(*) FROM complaint_messages m JOIN complaints c ON c.id = m.complaint_id WHERE c.student_user_id = ? AND m.sender_role = 'INVESTIGATOR' AND m.is_read = 0", 'i', [$uid]);
$contact_email = trim((string)get_setting($conn, 'contact_email'));
$contact_phone = trim((string)get_setting($conn, 'contact_phone'));
$emergency_number = trim((string)get_setting($conn, 'emergency_number'));
$db_cats = db_all($conn, "SELECT category_name, description FROM complaint_categories WHERE status = 'active' ORDER BY category_name");

$page_title = 'Dashboard'; $active = 'dashboard';
include ROOT_PATH . '/includes/student-header.php';
?>
<div class="sv-dash-hero mb-4 sv-fade-up">
  <div class="row align-items-center g-4">
    <div class="col-lg-7">
      <div class="sv-dash-welcome">
        <img src="<?= e(avatar_url($me)) ?>" alt="" class="sv-dash-avatar d-none d-sm-block">
        <div>
          <div class="sv-dash-eyebrow"><i class="bi bi-shield-check" aria-hidden="true"></i><?= e($greet) ?>, <?= e($me['full_name']) ?></div>
          <h1 class="hello mb-2">Your voice matters.<br><span>Your safety comes first.</span></h1>
          <p class="sv-dash-intro mb-0">You are not alone. Your concerns are taken seriously and handled with care.</p>
        </div>
      </div>
      <div class="sv-dash-actions">
        <a href="<?= url('student/submit-complaint.php') ?>" class="btn btn-light"><i class="bi bi-plus-circle me-1"></i>Submit a concern</a>
        <a href="<?= url('student/my-complaints.php') ?>" class="btn sv-dash-secondary">View my complaints <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
      </div>
      <div class="sv-dash-assurances" aria-label="SafeVoice commitments">
        <span><i class="bi bi-lock-fill" aria-hidden="true"></i>Private reporting</span>
        <span><i class="bi bi-chat-heart-fill" aria-hidden="true"></i>A team that listens</span>
      </div>
    </div>
    <div class="col-lg-5">
      <aside class="sv-dash-story" aria-label="How SafeVoice supports you">
        <div class="sv-dash-story-head">
          <span class="sv-dash-story-mark"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i></span>
          <span><strong>SafeVoice support</strong><small>Private by design</small></span>
          <i class="bi bi-three-dots ms-auto" aria-hidden="true"></i>
        </div>
        <div class="sv-dash-story-body">
          <span class="sv-dash-story-kicker">When you're ready</span>
          <h2>Your words matter.</h2>
          <p>Share what’s going on. A trained team will listen and help you take the next step.</p>
          <div class="sv-dash-story-rule"></div>
          <div class="sv-dash-story-step">
            <span class="sv-dash-step-icon"><i class="bi bi-send-check-fill" aria-hidden="true"></i></span>
            <span><strong>Your concern is received</strong><small>Privately, through your account</small></span>
            <i class="bi bi-check-circle-fill sv-dash-step-check" aria-hidden="true"></i>
          </div>
          <div class="sv-dash-story-step">
            <span class="sv-dash-step-icon"><i class="bi bi-person-heart" aria-hidden="true"></i></span>
            <span><strong>A specialist reviews it</strong><small>With care and attention</small></span>
          </div>
          <div class="sv-dash-story-step">
            <span class="sv-dash-step-icon"><i class="bi bi-chat-square-text-fill" aria-hidden="true"></i></span>
            <span><strong>You stay informed</strong><small>Follow updates in your account</small></span>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>

<div class="sv-dash-section-head">
  <div><span class="sv-dashboard-kicker">Your activity</span><h2 class="sv-section-title">A clear view of your reports</h2></div>
  <a class="sv-dash-section-link" href="<?= url('student/my-complaints.php') ?>">See all complaints <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
</div>
<div class="row g-3 mb-4">
  <?php foreach ([['t1', 'bi-folder2-open', 'Total Complaints', $stats['total'], '.05s'], ['t2', 'bi-hourglass-split', 'Pending Complaints', $stats['pending'], '.12s'],
                  ['t3', 'bi-search', 'Under Investigation', $stats['inv'], '.19s'], ['t4', 'bi-check2-circle', 'Resolved Complaints', $stats['resolved'], '.26s']] as $s): ?>
    <div class="col-6 col-lg-3 sv-fade-up" style="--d:<?= $s[4] ?>"><div class="sv-stat-card <?= $s[0] ?>"><div class="ico"><i class="bi <?= $s[1] ?>"></i></div>
      <div><div class="num"><?= (int)$s[3] ?></div><div class="lbl"><?= e($s[2]) ?></div></div></div></div>
  <?php endforeach; ?>
</div>

<div class="sv-dash-section-head sv-dash-actions-head">
  <div><span class="sv-dashboard-kicker">Next steps</span><h2 class="sv-section-title">Quick actions</h2></div>
</div>
<div class="row g-3 mb-4">
  <?php foreach ([['bi-send', 'Submit Complaint', 'student/submit-complaint.php'], ['bi-folder2-open', 'My Complaints', 'student/my-complaints.php'],
                  ['bi-chat-dots', $unread_msgs ? "Messages ($unread_msgs new)" : 'Messages', 'student/messages.php'], ['bi-life-preserver', 'Help & Safety', 'student/help.php']] as $q): ?>
    <div class="col-6 col-lg-3 sv-fade-up" style="--d:.3s"><a class="sv-quick" href="<?= url($q[2]) ?>"><i class="bi <?= $q[0] ?>"></i><span><?= e($q[1]) ?></span></a></div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="sv-card mb-4 sv-fade-up" style="--d:.35s">
      <div class="card-header-sv"><span><i class="bi bi-clock-history me-2 text-success"></i>Recent Complaints</span> <a class="btn btn-sm btn-outline-sv" href="<?= url('student/my-complaints.php') ?>">View all</a></div>
      <div class="table-responsive"><table class="table sv-table mb-0">
        <thead><tr><th>Complaint ID</th><th>Category</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>
        <?php if (!$recent): ?><tr><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>You have not submitted any complaints yet.<br><a class="btn btn-sv btn-sm mt-3" href="<?= url('student/submit-complaint.php') ?>">Submit your first complaint</a></td></tr><?php endif; ?>
        <?php foreach ($recent as $r): ?><tr>
          <td class="fw-semibold"><?= e($r['complaint_code']) ?></td><td><?= e($r['category_name']) ?></td><td><?= e(fmt_date($r['created_at'], false)) ?></td>
          <td><?= status_badge($r['status']) ?></td><td class="text-end"><a class="btn btn-sm btn-sv" href="<?= url('student/complaint-details.php?id=' . (int)$r['id']) ?>">View</a></td></tr><?php endforeach; ?>
      </tbody></table></div>
    </div>
    <?php if ($recent): $latest = $recent[0]; $pc = $progress_map[$latest['status']] ?? 20; $order = array_keys($progress_map); $idx = array_search($latest['status'], $order); ?>
    <div class="sv-card p-4 sv-fade-up" style="--d:.4s">
      <div class="d-flex justify-content-between align-items-center mb-2"><strong><i class="bi bi-signpost-split me-2 text-success"></i>Complaint Progress — <?= e($latest['complaint_code']) ?></strong><?= status_badge($latest['status']) ?></div>
      <div class="progress" style="height:12px;border-radius:50rem"><div class="progress-bar bg-success progress-bar-striped" style="width:<?= $pc ?>%"></div></div>
      <div class="sv-progress-steps"><?php foreach ($order as $i => $st): ?><span class="<?= $i <= $idx ? 'on' : '' ?>"><?= e($st) ?></span><?php endforeach; ?></div>
      <a class="small fw-semibold d-inline-block mt-3" href="<?= url('student/complaint-progress.php?id=' . (int)$latest['id']) ?>">View full timeline <i class="bi bi-arrow-right"></i></a>
    </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
    <div class="sv-card mb-4 sv-fade-up" style="--d:.38s"><div class="card-header-sv"><span><i class="bi bi-chat-heart me-2 text-success"></i>Recent Messages</span></div>
      <?php if (!$messages): ?><div class="p-3 text-muted small">No messages yet.</div><?php endif; ?>
      <?php foreach ($messages as $m): ?>
        <a class="sv-convo-item" href="<?= url('student/messages.php?c=' . (int)$m['complaint_id']) ?>">
          <div class="d-flex gap-2"><img src="<?= url('assets/images/safevoice-logo.png') ?>" class="sv-avatar" alt="">
          <div><div class="small fw-semibold">SafeVoice Investigation Team <?= $m['is_read'] ? '' : '<span class="badge bg-success">New</span>' ?></div>
          <div class="small text-muted"><?= e(mb_strimwidth($m['message'], 0, 70, '…')) ?></div><div class="small text-muted"><?= e($m['complaint_code']) ?> · <?= e(fmt_date($m['created_at'])) ?></div></div></div></a>
      <?php endforeach; ?>
    </div>
    <div class="sv-card sv-fade-up" style="--d:.44s"><div class="card-header-sv"><span><i class="bi bi-bell me-2 text-success"></i>Recent Notifications</span> <a class="small" href="<?= url('student/notifications.php') ?>">All</a></div>
      <?php if (!$recent_notifs): ?><div class="p-3 text-muted small">No notifications yet.</div><?php endif; ?>
      <?php foreach ($recent_notifs as $n): ?><div class="sv-convo-item"><div class="small fw-semibold"><?= $n['is_read'] ? '' : '<span class="badge bg-success me-1">New</span>' ?><?= e($n['title']) ?></div><div class="small text-muted"><?= e($n['message']) ?></div></div><?php endforeach; ?>
    </div>
    <section class="sv-card sv-resource-card mt-4 sv-fade-up" style="--d:.48s" aria-labelledby="studentResourcesTitle">
      <div class="card-header-sv"><span id="studentResourcesTitle"><i class="bi bi-shield-heart me-2 text-success"></i>Support &amp; resources</span></div>
      <div class="p-3">
        <p class="small text-muted mb-3">Learn how reports are handled and find guidance if you need support.</p>
        <nav class="sv-resource-links" aria-label="Student support resources">
          <a href="<?= url('public/how-it-works.php') ?>"><i class="bi bi-arrow-left-right" aria-hidden="true"></i><span>How SafeVoice works</span><i class="bi bi-arrow-up-right ms-auto" aria-hidden="true"></i></a>
          <a href="<?= url('student/help.php') ?>"><i class="bi bi-life-preserver" aria-hidden="true"></i><span>Help &amp; safety guidance</span><i class="bi bi-arrow-up-right ms-auto" aria-hidden="true"></i></a>
          <a href="<?= url('public/emergency-help.php') ?>"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span>Emergency help</span><i class="bi bi-arrow-up-right ms-auto" aria-hidden="true"></i></a>
        </nav>
        <?php if ($emergency_number !== ''): ?>
          <div class="sv-resource-alert mt-3"><strong>If you are in immediate danger:</strong> call <?= e($emergency_number) ?> or go to a safe place and tell a trusted adult. SafeVoice is not monitored in real time.</div>
        <?php endif; ?>
        <?php if ($contact_email !== '' || $contact_phone !== ''): ?>
          <div class="sv-resource-contact mt-3">
            <div class="small fw-semibold mb-1">SafeVoice contact</div>
            <?php if ($contact_email !== ''): ?><a class="small d-block" href="mailto:<?= e($contact_email) ?>"><i class="bi bi-envelope me-1" aria-hidden="true"></i><?= e($contact_email) ?></a><?php endif; ?>
            <?php if ($contact_phone !== ''): ?><a class="small d-block" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $contact_phone)) ?>"><i class="bi bi-telephone me-1" aria-hidden="true"></i><?= e($contact_phone) ?></a><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</div>
<section class="mt-5" aria-labelledby="aboutTitle">
  <div class="mb-3"><div class="sv-dashboard-kicker">About SafeVoice</div><h2 class="sv-section-title" id="aboutTitle">Support and guidance, all in one place</h2>
    <p class="sv-section-lead">The same information shown on the public website, so you never have to leave your dashboard to find it.</p></div>
  <div class="row g-4">
    <div class="col-lg-6"><div class="sv-card h-100">
      <div class="card-header-sv"><span><i class="bi bi-signpost-2 me-2 text-success"></i>How SafeVoice works</span></div>
      <div class="px-3 py-2">
        <?php foreach ($steps as $i => $st): ?>
          <div class="sv-info-step"><div class="n"><i class="bi <?= e($st[0]) ?>" aria-hidden="true"></i></div><div><strong><?= ($i + 1) ?>. <?= e($st[1]) ?></strong><span><?= e($st[2]) ?></span></div></div>
        <?php endforeach; ?>
      </div>
    </div></div>
    <div class="col-lg-6"><div class="sv-card h-100">
      <div class="card-header-sv"><span><i class="bi bi-tags me-2 text-success"></i>What you can report</span><a class="btn btn-sm btn-outline-sv" href="<?= url('student/submit-complaint.php') ?>">Submit complaint</a></div>
      <div class="p-3"><div class="row g-2">
        <?php if ($db_cats): foreach ($db_cats as $dc): ?>
          <div class="col-sm-6"><div class="sv-cat-tile"><strong><?= e($dc['category_name']) ?></strong><?php if (trim((string)$dc['description']) !== ''): ?><span><?= e(mb_strimwidth($dc['description'], 0, 90, '…')) ?></span><?php endif; ?></div></div>
        <?php endforeach; else: foreach ($cats as $dc): ?>
          <div class="col-sm-6"><div class="sv-cat-tile"><strong><?= e($dc[1]) ?></strong><span><?= e($dc[2]) ?></span></div></div>
        <?php endforeach; endif; ?>
      </div></div>
    </div></div>
    <div class="col-12"><div class="sv-card">
      <div class="card-header-sv"><span><i class="bi bi-shield-check me-2 text-success"></i>Why students trust SafeVoice</span></div>
      <div class="p-3"><div class="row g-3">
        <?php foreach ($why as $w): ?>
          <div class="col-md-6 col-lg-4"><div class="sv-tip"><i class="bi <?= e($w[0]) ?>" aria-hidden="true"></i><div><strong><?= e($w[1]) ?></strong><span><?= e($w[2]) ?></span></div></div></div>
        <?php endforeach; ?>
      </div></div>
    </div></div>
    <div class="col-lg-6"><div class="sv-card h-100">
      <div class="card-header-sv"><span><i class="bi bi-heart-pulse me-2 text-success"></i>Staying safe</span><a class="small fw-semibold" href="<?= url('student/help.php') ?>">Help &amp; Safety</a></div>
      <div class="p-3 d-grid gap-2">
        <?php foreach ($tips as $tp): ?>
          <div class="sv-tip"><i class="bi <?= e($tp[0]) ?>" aria-hidden="true"></i><div><strong><?= e($tp[1]) ?></strong><span><?= e($tp[2]) ?></span></div></div>
        <?php endforeach; ?>
      </div>
    </div></div>
    <div class="col-lg-6"><div class="sv-card h-100">
      <div class="card-header-sv"><span><i class="bi bi-question-circle me-2 text-success"></i>Frequently asked questions</span></div>
      <div class="p-3"><div class="accordion sv-info-faq" id="dashFaq">
        <?php foreach ($faqs as $i => $f): ?>
          <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button <?= $i ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#dashFaq<?= $i ?>" aria-expanded="<?= $i ? 'false' : 'true' ?>" aria-controls="dashFaq<?= $i ?>"><?= e($f[0]) ?></button></h3>
            <div id="dashFaq<?= $i ?>" class="accordion-collapse collapse <?= $i ? '' : 'show' ?>" data-bs-parent="#dashFaq"><div class="accordion-body small text-muted"><?= e($f[1]) ?></div></div></div>
        <?php endforeach; ?>
      </div></div>
    </div></div>
  </div>
</section>
<?php include ROOT_PATH . '/includes/student-footer.php'; ?>
