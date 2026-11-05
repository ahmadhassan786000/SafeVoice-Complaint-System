<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_STUDENT);
$cats = category_options($conn);
$max_mb = (int)get_setting($conn, 'max_evidence_mb', 10);
$emergency = get_setting($conn, 'emergency_number', '15');

// Validation errors from actions/submit-complaint.php are shown as one clear, persistent summary (not a 3-second flash).
// Other flash types (info/warning) are put back so the normal alerts still work.
$form_errors = []; $keep = [];
foreach (flash_get() as $f) { if ($f['type'] === 'danger') $form_errors[] = $f['message']; else $keep[] = $f; }
foreach ($keep as $f) flash_set($f['type'], $f['message']);
// Map each server message to the field it is about, so that field is highlighted.
$field_hints = ['student name' => 'student_name', 'school name' => 'school_name', 'roll number' => 'school_roll_number', 'class is' => 'class_name', 'gender' => 'gender',
                'category' => 'category_id', 'who is being reported' => 'reported_person_type', 'reported person name' => 'reported_person_name',
                'incident date' => 'incident_date', 'incident location' => 'incident_location', 'describe the incident' => 'description', 'description or witness' => 'description'];
$bad = [];
foreach ($form_errors as $m) foreach ($field_hints as $needle => $id) if (stripos($m, $needle) !== false) $bad[$id] = true;
$inv = function ($id) use ($bad) { return isset($bad[$id]) ? ' is-invalid' : ''; };

$page_title = 'Submit Complaint'; $active = 'submit';
$page_scripts = '<script src="' . url('assets/js/submit-complaint.js') . '"></script>';
include ROOT_PATH . '/includes/student-header.php';
?>
<div class="sv-sc-hero">
  <span class="sv-sc-hero-icon" aria-hidden="true"><i class="bi bi-megaphone"></i></span>
  <div>
    <h1 class="sv-page-title">Submit a Complaint</h1>
    <p class="sv-page-sub">Tell us what happened. A SafeVoice investigator will review your report, and you can follow its progress and message the team from your account.</p>
  </div>
</div>

<?php if ($form_errors): ?>
  <div class="alert alert-danger sv-sc-errors" role="alert" id="scErrors" tabindex="-1">
    <div class="d-flex gap-2"><i class="bi bi-exclamation-octagon-fill fs-5" aria-hidden="true"></i>
      <div><strong>Your complaint was not submitted yet.</strong> Please fix the following and try again. Your answers have been kept.
        <ul class="mb-0 mt-2"><?php foreach ($form_errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div></div>
  </div>
<?php endif; ?>

<div class="row g-4 align-items-start">
  <div class="col-lg-8">
    <form method="post" action="<?= url('actions/submit-complaint.php') ?>" enctype="multipart/form-data" novalidate id="complaintForm" data-max-mb="<?= $max_mb ?>">
      <?= csrf_field() ?>

      <section class="sv-fsec" aria-labelledby="secA">
        <header class="sv-fsec-head"><span class="sv-fsec-num">1</span><div><h2 id="secA">Student information</h2><p>Enter the student name for this complaint. Your Student ID comes from your account.</p></div></header>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label" for="student_name">Full Name <span class="sv-req" aria-hidden="true">*</span></label><input class="form-control<?= $inv('student_name') ?>" id="student_name" name="student_name" maxlength="100" required autocomplete="name" value="<?= e(old('student_name', $me['full_name'])) ?>"></div>
          <div class="col-md-6"><label class="form-label" for="sc_sid">Student ID</label><input class="form-control" id="sc_sid" value="<?= e($me['student_id']) ?>" disabled></div>
          <div class="col-md-6"><label class="form-label" for="school_name">School Name <span class="sv-req" aria-hidden="true">*</span></label><input class="form-control<?= $inv('school_name') ?>" id="school_name" name="school_name" maxlength="150" required value="<?= e(old('school_name')) ?>" autocomplete="organization"></div>
          <div class="col-md-6"><label class="form-label" for="school_roll_number">School Roll Number <span class="sv-req" aria-hidden="true">*</span></label><input class="form-control<?= $inv('school_roll_number') ?>" id="school_roll_number" name="school_roll_number" maxlength="50" required value="<?= e(old('school_roll_number')) ?>"></div>
          <div class="col-md-6"><label class="form-label" for="class_name">Class <span class="sv-req" aria-hidden="true">*</span></label>
            <input class="form-control<?= $inv('class_name') ?>" id="class_name" name="class_name" list="classList" maxlength="50" placeholder="e.g. Grade 9 - A" required value="<?= e(old('class_name')) ?>">
            <datalist id="classList"><?php for ($i = 1; $i <= 12; $i++): ?><option value="Grade <?= $i ?>"><?php endfor; ?></datalist></div>
          <div class="col-md-6"><label class="form-label" for="gender">Gender <span class="sv-req" aria-hidden="true">*</span></label><select class="form-select<?= $inv('gender') ?>" id="gender" name="gender" required>
            <option value="">Select…</option><?php foreach (['Male', 'Female', 'Prefer not to say'] as $g): ?><option <?= old('gender') === $g ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?></select></div>
        </div>
      </section>

      <section class="sv-fsec" aria-labelledby="secB">
        <header class="sv-fsec-head"><span class="sv-fsec-num">2</span><div><h2 id="secB">Incident details</h2><p>What kind of concern is it, who is involved, and where and when did it happen?</p></div></header>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label" for="category_id">Complaint Category <span class="sv-req" aria-hidden="true">*</span></label><select class="form-select<?= $inv('category_id') ?>" id="category_id" name="category_id" required>
            <option value="">Select…</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)old('category_id') === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6"><label class="form-label" for="reported_person_type">Reported Person Type <span class="sv-req" aria-hidden="true">*</span></label><select class="form-select<?= $inv('reported_person_type') ?>" id="reported_person_type" name="reported_person_type" required>
            <option value="">Select…</option><?php foreach (REPORTED_TYPES as $t): ?><option <?= old('reported_person_type') === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6"><label class="form-label" for="reported_person_name">Reported Person Name <span class="sv-opt">(optional)</span></label><input class="form-control<?= $inv('reported_person_name') ?>" id="reported_person_name" name="reported_person_name" maxlength="100" value="<?= e(old('reported_person_name')) ?>"><div class="form-text">Leave blank if you do not know the name.</div></div>
          <div class="col-md-3 col-6"><label class="form-label" for="incident_date">Incident Date <span class="sv-req" aria-hidden="true">*</span></label><input type="date" class="form-control<?= $inv('incident_date') ?>" id="incident_date" name="incident_date" max="<?= date('Y-m-d') ?>" required value="<?= e(old('incident_date')) ?>"></div>
          <div class="col-md-3 col-6"><label class="form-label" for="incident_location">Location <span class="sv-req" aria-hidden="true">*</span></label><input class="form-control<?= $inv('incident_location') ?>" id="incident_location" name="incident_location" maxlength="200" required placeholder="e.g. Playground" value="<?= e(old('incident_location')) ?>"></div>
        </div>
      </section>

      <section class="sv-fsec" aria-labelledby="secC">
        <header class="sv-fsec-head"><span class="sv-fsec-num">3</span><div><h2 id="secC">What happened</h2><p>Describe the incident in your own words. Include what was said or done, how often, and how it affected you.</p></div></header>
        <div class="row g-3">
          <div class="col-12"><label class="form-label" for="description">Detailed Incident Description <span class="sv-req" aria-hidden="true">*</span></label>
            <textarea class="form-control<?= $inv('description') ?>" id="description" name="description" rows="6" minlength="20" maxlength="5000" required aria-describedby="descHelp"><?= e(old('description')) ?></textarea>
            <div class="sv-counter-row" id="descHelp"><span>Minimum 20 characters.</span><span id="descCount" aria-live="off">0 / 5000</span></div></div>
          <div class="col-12"><label class="form-label" for="witness_information">Witness Information <span class="sv-opt">(optional)</span></label>
            <textarea class="form-control" id="witness_information" name="witness_information" rows="2" maxlength="2000" placeholder="Names or descriptions of anyone who saw what happened"><?= e(old('witness_information')) ?></textarea></div>
        </div>
      </section>

      <section class="sv-fsec" aria-labelledby="secD">
        <header class="sv-fsec-head"><span class="sv-fsec-num">4</span><div><h2 id="secD">Supporting evidence <span class="sv-opt">(optional)</span></h2><p>Add screenshots, photos or documents that support your report.</p></div></header>
        <div class="sv-dropzone" id="dropzone">
          <input type="file" class="sv-dz-input" id="evidence" name="evidence[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt" aria-describedby="evHelp">
          <div class="sv-dz-body">
            <span class="sv-dz-icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></span>
            <div class="sv-dz-title"><strong>Click to choose files</strong> or drag and drop them here</div>
            <div class="sv-dz-sub" id="evHelp">JPG, PNG, WEBP, PDF, Word (DOC/DOCX) or TXT · up to <?= $max_mb ?> MB each · you can select several files</div>
          </div>
        </div>
        <ul class="sv-file-list list-unstyled" id="fileList" aria-live="polite"></ul>
        <div class="alert alert-warning py-2 small d-none" id="fileWarn" role="alert"></div>
        <div class="sv-safe-note"><i class="bi bi-shield-exclamation" aria-hidden="true"></i><div><strong>Never upload sexual or explicit images.</strong> Describe them in words instead. If you are in danger, see <a href="<?= url('student/help.php') ?>">Help &amp; Safety</a>.</div></div>
      </section>

      <div class="sv-submit-bar">
        <a href="<?= url('index.php') ?>" class="btn btn-outline-sv">Cancel</a>
        <button class="btn btn-sv btn-lg" id="submitBtn" type="submit"><i class="bi bi-send me-2" aria-hidden="true"></i><span>Submit Complaint</span></button>
      </div>
    </form>
  </div>

  <aside class="col-lg-4" aria-label="Guidance">
    <div class="sv-side-stack">
      <div class="sv-aside-card">
        <h2><i class="bi bi-lightbulb" aria-hidden="true"></i> Tips for a clear report</h2>
        <ul><li>Say <strong>what</strong> happened, <strong>when</strong> and <strong>where</strong>.</li><li>Mention who was there and anything that was said.</li><li>Keep screenshots, messages or photos as evidence.</li><li>Stick to the facts as you remember them.</li></ul>
      </div>
      <div class="sv-aside-card green">
        <h2><i class="bi bi-lock" aria-hidden="true"></i> Your privacy</h2>
        <p>Your complaint is visible only to authorized SafeVoice staff. The person you report never sees your information, and replies come from the <em>SafeVoice Investigation Team</em>.</p>
      </div>
      <div class="sv-aside-card danger">
        <h2><i class="bi bi-telephone-fill" aria-hidden="true"></i> In immediate danger?</h2>
        <p class="mb-0">Call <strong><?= e($emergency) ?></strong> or tell a trusted adult right now. Do not wait for a reply here.</p>
      </div>
    </div>
  </aside>
</div>
<?php old_clear(); include ROOT_PATH . '/includes/student-footer.php'; ?>
