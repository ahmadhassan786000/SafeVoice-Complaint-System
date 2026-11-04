<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_STUDENT);
$page_title = 'Help & Safety'; $active = 'help';
include ROOT_PATH . '/includes/student-header.php';
$faqs = [
 ['Who can see my complaint?', 'Only the authorized SafeVoice investigator and administrators. The person you report never sees your information.'],
 ['Will I know who is investigating?', 'No. For everyone\'s protection all replies come from the "SafeVoice Investigation Team".'],
 ['Can I submit more than one complaint?', 'Yes. Each complaint has its own ID, messages, evidence and timeline.'],
 ['What evidence can I upload?', 'Images, PDF, Word and text files. Never upload sexual or explicit images — describe them in words instead.'],
 ['How long does it take?', 'Times vary. You will get a notification whenever the status changes or the team needs more information.'],
];
?>
<h1 class="sv-page-title">Help &amp; Safety</h1>
<div class="alert alert-danger"><i class="bi bi-telephone-fill me-1"></i><strong>Emergency?</strong> If you are in immediate danger call <strong><?= e(get_setting($conn, 'emergency_number', '15')) ?></strong> or tell a trusted adult right now.</div>
<div class="row g-4 mb-4">
  <div class="col-md-6"><div class="sv-card p-4 h-100"><h2 class="h5"><i class="bi bi-emoji-angry text-success me-2"></i>About bullying</h2><p class="mb-0 text-muted">Bullying is repeated unkind behaviour — hitting, name-calling, exclusion, spreading rumours or online harassment. It is never your fault. Keep screenshots or notes, stay near people you trust, and report it.</p></div></div>
  <div class="col-md-6"><div class="sv-card p-4 h-100"><h2 class="h5"><i class="bi bi-exclamation-octagon text-success me-2"></i>About harassment</h2><p class="mb-0 text-muted">Harassment is unwanted behaviour that makes you feel unsafe, humiliated or uncomfortable — including sexual comments or contact. You have the right to say no and to report it.</p></div></div>
  <div class="col-12"><div class="sv-card p-4"><h2 class="h5"><i class="bi bi-shield-check text-success me-2"></i>Safety guidance</h2>
    <ul class="mb-0 text-muted"><li>Tell a trusted adult — a parent, guardian, teacher or counselor.</li><li>Write down what happened, when and where, and who saw it.</li><li>Do not retaliate; stay with friends in safe places.</li><li>Keep your SafeVoice password private and log out on shared devices.</li></ul></div></div>
</div>
<h2 class="h4">Frequently asked questions</h2>
<div class="accordion mb-4" id="faq"><?php foreach ($faqs as $i => $f): ?>
  <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button <?= $i ? 'collapsed' : '' ?>" data-bs-toggle="collapse" data-bs-target="#f<?= $i ?>"><?= e($f[0]) ?></button></h3>
  <div id="f<?= $i ?>" class="accordion-collapse collapse <?= $i ? '' : 'show' ?>" data-bs-parent="#faq"><div class="accordion-body"><?= e($f[1]) ?></div></div></div><?php endforeach; ?></div>
<?php include ROOT_PATH . '/includes/student-footer.php'; ?>
