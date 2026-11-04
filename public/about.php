<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
$page_title = 'About';
include ROOT_PATH . '/includes/header.php';
?>
<main class="container py-5" style="max-width:880px">
  <h1 class="sv-page-title">About SafeVoice</h1>
  <p class="text-muted">A web-based school complaint and student protection system.</p>
  <p>SafeVoice gives students a safe, structured way to report bullying, harassment, abuse, discrimination, misconduct and other safety concerns. Complaints are reviewed by dedicated Investigation Officers, and students can follow progress and communicate with the <strong>SafeVoice Investigation Team</strong> at every step.</p>
  <h2 class="h4 mt-4">Our objectives</h2>
  <ul><li>Protect students and maintain their privacy.</li><li>Provide a secure complaint submission system.</li><li>Enable controlled, accountable investigations.</li><li>Keep investigation records and show transparent progress.</li></ul>
  <h2 class="h4 mt-4" id="privacy">Privacy Policy</h2>
  <p>SafeVoice stores the information you provide (account details, school details and complaint content) only to process your complaint. Your identity is visible to the authorized investigator and to system administrators — not to the person you report. Your investigator's identity is never shown to you; all replies come from the SafeVoice Investigation Team. Passwords are stored as secure hashes and evidence files are kept in private storage. Contact <?= e(get_setting($conn, 'contact_email')) ?> with any privacy question.</p>
</main>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
