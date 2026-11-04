<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
$page_title = 'How It Works';
include ROOT_PATH . '/includes/header.php';
$steps = [
 ['Create an account','Register with your name and email. You receive a unique Student ID.'],
 ['Submit a complaint','Choose a category, enter your school details, describe the incident and optionally upload evidence.'],
 ['Automatic routing','The complaint is routed to the officer responsible for that category. You never choose or see the officer.'],
 ['Investigation','The officer reviews the case, may ask you for more information and records findings.'],
 ['Track progress','Follow a timeline of real events and read messages from the SafeVoice Investigation Team.'],
 ['Resolution','You are notified when your complaint is resolved or closed.'],
];
?>
<main class="container py-5" style="max-width:900px">
  <h1 class="sv-page-title">How SafeVoice works</h1>
  <p class="text-muted mb-4">From report to resolution in six steps.</p>
  <?php foreach ($steps as $i => $s): ?>
    <div class="sv-card p-3 mb-3 d-flex gap-3"><div class="sv-step-num flex-shrink-0"><?= $i + 1 ?></div>
      <div><h2 class="h5 mb-1"><?= e($s[0]) ?></h2><p class="mb-0 text-muted"><?= e($s[1]) ?></p></div></div>
  <?php endforeach; ?>
  <div class="text-center mt-4"><a class="btn btn-sv btn-lg" href="<?= url('auth/register.php') ?>">Get started</a></div>
</main>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
