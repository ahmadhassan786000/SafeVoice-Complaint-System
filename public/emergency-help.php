<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
$page_title = 'Emergency Help';
include ROOT_PATH . '/includes/header.php';
?>
<main class="container py-5" style="max-width:880px">
  <h1 class="sv-page-title">Emergency help</h1>
  <div class="alert alert-danger"><strong>If you are in immediate danger, call <?= e(get_setting($conn, 'emergency_number', '15')) ?> now</strong> or go to a safe place and tell a trusted adult. SafeVoice is not monitored in real time.</div>
  <h2 class="h4 mt-4">What to do</h2>
  <ul><li>Move to a safe place near trusted people.</li><li>Tell a parent, guardian, teacher or counselor you trust.</li><li>Keep any messages, photos or notes that show what happened.</li><li>Then submit a complaint on SafeVoice so it can be investigated.</li></ul>
  <h2 class="h4 mt-4">Sexual imagery</h2>
  <p>Do <strong>not</strong> upload or forward sexual or explicit images of anyone — especially anyone under 18. SafeVoice does not accept them as evidence. Describe what happened in words, report it to a trusted adult or the police, and our team will guide you through a safe reporting process.</p>
  <h2 class="h4 mt-4">Contact</h2>
  <p><?= e(get_setting($conn, 'contact_email')) ?> &middot; <?= e(get_setting($conn, 'contact_phone')) ?></p>
</main>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
