<?php
/**
 * Public header: logo (left) - section links (middle) - ONE "Get Started" button (right).
 * Get Started -> auth/login.php, which itself forwards logged-in users to their own dashboard.
 */
if (!empty($student)) {                       // logged-in Student: Home | My Complaints | Submit Complaint | Messages
    $me = $student; $active = 'home';
    include ROOT_PATH . '/includes/student-nav.php';
    if (!empty($_SESSION['flash'])) { echo '<div class="container pt-3">'; include ROOT_PATH . '/includes/flash-messages.php'; echo '</div>'; }
    return;
}
$links = ['home' => 'Home', 'about' => 'About', 'features' => 'Features', 'safety' => 'Safety', 'contact' => 'Contact'];
?>
<nav class="navbar navbar-expand-lg sv-pub-nav sticky-top" id="pubNav">
  <div class="container">
    <a class="navbar-brand" href="#home">
      <img src="<?= url('assets/images/safevoice-logo.png') ?>" alt="SafeVoice logo" width="40" height="40"><span>SafeVoice</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav" aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="publicNav">
      <ul class="navbar-nav mx-auto mb-3 mb-lg-0">
        <?php foreach ($links as $id => $label): ?>
          <li class="nav-item"><a class="nav-link" href="#<?= e($id) ?>" data-spy="<?= e($id) ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <a class="btn btn-getstarted" href="<?= url('auth/login.php') ?>">Get Started <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
  </div>
</nav>
