<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_OFFICER);

$c = staff_get_complaint($conn, (int)($_GET['id'] ?? 0), $me);   // restricted to this officer's assignments
if (!$c) { http_response_code(403); flash_set('danger', 'Complaint not found or not assigned to you.'); redirect('officer/complaints.php'); }
$evidence = db_all($conn, "SELECT id, file_name, file_type, file_size, created_at FROM complaint_evidence WHERE complaint_id = ? ORDER BY id", 'i', [$c['id']]);
$history = db_all($conn, "SELECT new_status, public_description, internal_notes, created_at FROM complaint_status_history WHERE complaint_id = ? ORDER BY id DESC", 'i', [$c['id']]);
$student = ['id' => $c['student_uid'], 'profile_image' => $c['student_image'], 'role_code' => ROLE_STUDENT];

$page_title = 'Complaint ' . $c['complaint_code']; $active = 'complaints';
include ROOT_PATH . '/includes/panel-header.php';
function sv_officer_complaint_meta($label, $value) {
    echo '<div class="sv-ocd-meta-item"><div class="sv-ocd-meta-label">' . e($label) . '</div><div class="sv-ocd-meta-value">' . e($value === null || $value === '' ? '-' : $value) . '</div></div>';
}
?>
<div class="sv-officer-complaint-detail">
  <header class="sv-ocd-header">
    <div class="sv-ocd-heading">
      <div class="sv-ocd-eyebrow"><i class="bi bi-folder2-open" aria-hidden="true"></i> Complaint</div>
      <div class="sv-ocd-title-line">
        <h1 class="sv-page-title"><?= e($c['complaint_code']) ?></h1>
        <span class="sv-ocd-status"><?= status_badge($c['status']) ?></span>
      </div>
    </div>
    <nav class="sv-ocd-actions" aria-label="Complaint actions">
      <a class="btn btn-sv" href="<?= url('officer/investigation.php?id=' . (int)$c['id']) ?>"><i class="bi bi-search me-1" aria-hidden="true"></i>Open Investigation</a>
      <a class="btn btn-outline-sv" href="<?= url('officer/messages.php?c=' . (int)$c['id']) ?>"><i class="bi bi-chat-dots me-1" aria-hidden="true"></i>Messages</a>
    </nav>
  </header>

  <section class="sv-card sv-ocd-metadata" aria-label="Complaint details">
    <div class="sv-ocd-meta-grid">
      <?php
      sv_officer_complaint_meta('Category', $c['category_name']);
      sv_officer_complaint_meta('Reported Person', trim($c['reported_person_type'] . ' ' . ($c['reported_person_name'] ? '— ' . $c['reported_person_name'] : '')));
      sv_officer_complaint_meta('Incident Date', fmt_date($c['incident_date'], false));
      sv_officer_complaint_meta('Incident Location', $c['incident_location']);
      sv_officer_complaint_meta('Submitted', fmt_date($c['created_at']));
      sv_officer_complaint_meta('Last Updated', fmt_date($c['updated_at']));
      ?>
    </div>
  </section>

  <div class="row g-4 sv-ocd-content">
    <aside class="col-xl-4">
      <section class="sv-card sv-ocd-student" aria-labelledby="sv-ocd-student-heading">
        <div class="sv-ocd-student-top">
          <img src="<?= e(avatar_url($student)) ?>" class="sv-ocd-avatar" alt="Student">
          <div class="sv-ocd-student-name">
            <h2 id="sv-ocd-student-heading"><?= e($c['student_name']) ?></h2>
            <span class="sv-ocd-student-role"><i class="bi bi-person-fill" aria-hidden="true"></i> Student</span>
          </div>
        </div>
        <dl class="sv-ocd-student-fields">
          <div><dt>Student ID</dt><dd><?= e($c['student_code']) ?></dd></div>
          <div><dt>Email</dt><dd class="sv-ocd-break"><?= e($c['student_email']) ?></dd></div>
          <div><dt>Phone</dt><dd><?= e($c['student_phone'] ?: '-') ?></dd></div>
          <div><dt>School</dt><dd><?= e($c['school_name']) ?></dd></div>
          <div><dt>Roll Number</dt><dd><?= e($c['school_roll_number']) ?></dd></div>
          <div><dt>Class</dt><dd><?= e($c['class_name']) ?></dd></div>
          <div><dt>Gender</dt><dd><?= e($c['gender']) ?></dd></div>
        </dl>
      </section>
    </aside>

    <div class="col-xl-8 sv-ocd-activity">
      <section class="sv-card sv-ocd-description" aria-labelledby="sv-ocd-description-heading">
        <div class="sv-ocd-section-heading">
          <span class="sv-ocd-heading-icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>
          <div><h2 id="sv-ocd-description-heading">Complaint description</h2><p>Reported incident and related information</p></div>
        </div>
        <div class="sv-ocd-description-body">
          <h3>Description</h3>
          <div class="sv-pre"><?= e($c['description']) ?></div>
          <?php if ($c['witness_information']): ?>
            <h3 class="sv-ocd-witness-heading">Witness information</h3>
            <div class="sv-pre"><?= e($c['witness_information']) ?></div>
          <?php endif; ?>
        </div>
      </section>

      <section class="sv-card sv-ocd-evidence" aria-labelledby="sv-ocd-evidence-heading">
        <div class="card-header-sv">
          <h2 id="sv-ocd-evidence-heading">Supporting Evidence <span class="sv-ocd-count"><?= count($evidence) ?></span></h2>
        </div>
        <?php if (!$evidence): ?>
          <div class="sv-ocd-empty"><i class="bi bi-file-earmark-x" aria-hidden="true"></i><span>No evidence uploaded.</span></div>
        <?php else: ?>
          <ul class="list-group list-group-flush sv-ocd-evidence-list">
            <?php foreach ($evidence as $ev): ?>
              <li class="list-group-item sv-ocd-evidence-item">
                <div class="sv-ocd-file-info">
                  <span class="sv-ocd-file-icon"><i class="bi bi-file-earmark" aria-hidden="true"></i></span>
                  <span class="sv-ocd-file-text">
                    <span class="sv-ocd-file-name"><?= e($ev['file_name']) ?></span>
                    <span class="sv-ocd-file-size"><?= round($ev['file_size'] / 1024) ?> KB</span>
                  </span>
                </div>
                <div class="sv-ocd-file-actions">
                  <a class="btn btn-sm btn-outline-sv" target="_blank" rel="noopener noreferrer" aria-label="Open <?= e($ev['file_name']) ?>" href="<?= url('actions/evidence-file.php?id=' . (int)$ev['id']) ?>">Open</a>
                  <a class="btn btn-sm btn-outline-sv" aria-label="Download <?= e($ev['file_name']) ?>" href="<?= url('actions/evidence-file.php?id=' . (int)$ev['id'] . '&dl=1') ?>">Download</a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>

      <section class="sv-card sv-ocd-history" aria-labelledby="sv-ocd-history-heading">
        <div class="card-header-sv">
          <h2 id="sv-ocd-history-heading">Status history</h2>
          <span class="sv-ocd-history-caption">Most recent first</span>
        </div>
        <?php if (!$history): ?>
          <div class="sv-ocd-empty"><i class="bi bi-clock-history" aria-hidden="true"></i><span>No status history available.</span></div>
        <?php else: ?>
          <ol class="sv-ocd-timeline">
            <?php foreach ($history as $h): ?>
              <li class="sv-ocd-timeline-item">
                <span class="sv-ocd-timeline-marker" aria-hidden="true"></span>
                <div class="sv-ocd-timeline-content">
                  <div class="sv-ocd-history-event">
                    <strong><?= e($h['public_description']) ?></strong>
                    <time><?= e(fmt_date($h['created_at'])) ?></time>
                  </div>
                  <?php if ($h['internal_notes']): ?><div class="sv-ocd-internal sv-pre"><i class="bi bi-lock-fill" aria-hidden="true"></i><span>Internal: <?= e($h['internal_notes']) ?></span></div><?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      </section>
    </div>
  </div>
</div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
