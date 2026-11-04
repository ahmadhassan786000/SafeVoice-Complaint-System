<?php
/**
 * SafeVoice - the ONE public homepage. Every public section lives here (no separate public pages).
 * Visitors and logged-in Students see this page at / (only the header differs); Admin/Investigator are forwarded to their dashboards by index.php.
 */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';

// Logged-in Students see this SAME page with the student navigation. The session is re-validated (suspended/idle accounts are logged out).
$student = null;
if (!empty($_SESSION['user_id'])) {
    require_once ROOT_PATH . '/middleware/auth.php';
    $viewer = require_login();
    if ($viewer['role_code'] === ROLE_STUDENT) $student = $viewer;
}
// Get Started / Report a Concern: visitors -> login; logged-in Students -> Submit Complaint
$go = $student ? url('student/submit-complaint.php') : url('auth/login.php');
$contact_email = trim(get_setting($conn, 'contact_email'));
$contact_phone = trim(get_setting($conn, 'contact_phone'));
$emergency = get_setting($conn, 'emergency_number', '15');

require_once ROOT_PATH . '/includes/public-content.php';

$page_title = 'Your Voice Matters';
include ROOT_PATH . '/includes/header.php';
?>

<!-- 1. HERO -->
<section class="sv-pub-hero" id="home">
  <div class="sv-hero-grain" aria-hidden="true"></div>
  <div class="container position-relative">
    <div class="row align-items-center g-5">
      <div class="col-lg-7 sv-hero-anim">
        <span class="sv-eyebrow sv-hero-eyebrow"><i class="bi bi-shield-check me-1"></i>A safer school starts with being heard</span>
        <h1>Your voice deserves to be heard. <span>Your safety comes first.</span></h1>
        <p class="lead my-4">SafeVoice gives students a secure, supportive way to report school-related concerns and get help from a real team through a clear complaint and investigation process.</p>
        <div class="d-flex flex-wrap gap-3 sv-hero-actions">
          <a href="<?= $go ?>" class="btn btn-light btn-hero text-success">Report a Concern <i class="bi bi-arrow-right ms-1"></i></a>
          <a href="#about" class="btn btn-outline-light btn-hero">Learn More</a>
        </div>
        <div class="sv-hero-assurances" aria-label="SafeVoice commitments">
          <span><i class="bi bi-lock-fill" aria-hidden="true"></i>Private reporting</span>
          <span><i class="bi bi-chat-heart-fill" aria-hidden="true"></i>A team that listens</span>
        </div>
      </div>
      <div class="col-lg-5">
        <figure class="sv-hero-visual" aria-label="Illustration of the private reporting and support process">
          <div class="sv-hero-orbit sv-hero-orbit-one" aria-hidden="true"></div>
          <div class="sv-hero-orbit sv-hero-orbit-two" aria-hidden="true"></div>
          <div class="sv-report-card">
            <div class="sv-report-card-head">
              <span class="sv-report-mark"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i></span>
              <span><strong>SafeVoice support</strong><small>Private by design</small></span>
              <i class="bi bi-three-dots" aria-hidden="true"></i>
            </div>
            <div class="sv-report-card-body">
              <span class="sv-report-kicker">When you're ready</span>
              <h2>Your words matter.</h2>
              <p>Share what’s going on. A trained team will listen and help you take the next step.</p>
              <div class="sv-report-divider"></div>
              <div class="sv-report-step">
                <span class="sv-report-step-icon"><i class="bi bi-send-check-fill" aria-hidden="true"></i></span>
                <span><strong>Your concern is received</strong><small>Privately, through your account</small></span>
                <i class="bi bi-check-circle-fill sv-report-check" aria-hidden="true"></i>
              </div>
              <div class="sv-report-step">
                <span class="sv-report-step-icon"><i class="bi bi-person-heart" aria-hidden="true"></i></span>
                <span><strong>A specialist reviews it</strong><small>With care and attention</small></span>
              </div>
              <div class="sv-report-step">
                <span class="sv-report-step-icon"><i class="bi bi-chat-square-text-fill" aria-hidden="true"></i></span>
                <span><strong>You stay informed</strong><small>Follow updates in your account</small></span>
              </div>
            </div>
          </div>
          <figcaption class="sv-hero-note"><i class="bi bi-heart-fill" aria-hidden="true"></i> You’re not alone in this.</figcaption>
        </figure>
      </div>
    </div>
  </div>
</section>
<div class="sv-hero-transition" aria-hidden="true"></div>

<!-- 2. ABOUT -->
<section class="sv-sec" id="about">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 reveal">
        <span class="sv-eyebrow">About SafeVoice</span>
        <h2 class="sv-sec-title mb-3">A safe place for every student’s voice</h2>
        <p class="text-muted">SafeVoice is a school complaint and student protection system. It exists because many students stay silent about bullying, harassment or unfair treatment — often because they fear nobody will listen, or that others will find out.</p>
        <p class="text-muted">With SafeVoice, a student reports a concern from their own account, the complaint is routed to a specialist Investigation Officer, and the student follows every step: from submission to review, investigation and resolution.</p>
        <ul class="list-unstyled sv-check mt-3">
          <li><i class="bi bi-check-circle-fill"></i><span>Safe reporting from any device</span></li>
          <li><i class="bi bi-check-circle-fill"></i><span>Your identity is never shared with the person you report</span></li>
          <li><i class="bi bi-check-circle-fill"></i><span>Clear updates and private communication throughout the investigation</span></li>
        </ul>
      </div>
      <div class="col-lg-6 reveal" style="--d:.15s">
        <div class="sv-split-visual">
          <h3 class="h4 fw-bold mb-4">How the investigation helps</h3>
          <div class="d-flex gap-3 mb-3"><i class="bi bi-1-circle-fill fs-3"></i><div><strong>Your complaint is received</strong><div class="small opacity-75">It is recorded with its own unique ID.</div></div></div>
          <div class="d-flex gap-3 mb-3"><i class="bi bi-2-circle-fill fs-3"></i><div><strong>A specialist reviews it</strong><div class="small opacity-75">Officers are assigned by complaint category.</div></div></div>
          <div class="d-flex gap-3"><i class="bi bi-3-circle-fill fs-3"></i><div><strong>You are kept informed</strong><div class="small opacity-75">Status updates and messages come from the SafeVoice Investigation Team.</div></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 3. COMPLAINT CATEGORIES -->
<section class="sv-sec" id="categories">
  <div class="container">
    <div class="text-center mb-5 reveal"><span class="sv-eyebrow">Complaint Categories</span><h2 class="sv-sec-title">What can you report?</h2><p class="sv-sec-lead">Choose the category that fits your situation. Each one is handled by a dedicated officer.</p></div>
    <div class="row g-4">
      <?php foreach ($cats as $i => $c): ?>
        <div class="col-sm-6 col-lg-3 reveal" style="--d:<?= ($i % 4) * .08 ?>s"><div class="sv-hover-card"><div class="sv-icon-box"><i class="bi <?= e($c[0]) ?>"></i></div><h3 class="h6 fw-bold"><?= e($c[1]) ?></h3><p class="small text-muted mb-0"><?= e($c[2]) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 4. WHY CHOOSE / FEATURES -->
<section class="sv-sec alt" id="features">
  <div class="container">
    <div class="text-center mb-5 reveal"><span class="sv-eyebrow">Features</span><h2 class="sv-sec-title">Why choose SafeVoice?</h2><p class="sv-sec-lead">Built to protect students and keep every investigation organized.</p></div>
    <div class="row g-4">
      <?php foreach ($why as $i => $w): ?>
        <div class="col-md-6 col-lg-4 reveal" style="--d:<?= ($i % 3) * .1 ?>s"><div class="sv-hover-card"><div class="sv-icon-box"><i class="bi <?= e($w[0]) ?>"></i></div><h3 class="h5 fw-bold"><?= e($w[1]) ?></h3><p class="text-muted mb-0"><?= e($w[2]) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 5. STUDENT SAFETY -->
<section class="sv-sec" id="safety">
  <div class="container">
    <div class="sv-safety-band reveal">
      <div class="row g-4 align-items-center mb-4"><div class="col-lg-8"><span class="sv-eyebrow" style="background:rgba(255,255,255,.14);color:#c9f3da">Student Safety</span><h2 class="fw-bold">You deserve to feel safe at school</h2>
        <p class="opacity-75 mb-0">Speaking up is brave, and it is the first step to getting help. Here is what you can do.</p></div>
        <div class="col-lg-4"><div class="sv-safety-item text-center"><i class="bi bi-telephone-fill"></i><div class="fw-bold mt-1">In immediate danger?</div><div class="small opacity-75">Call <strong><?= e($emergency) ?></strong> or tell a trusted adult right now. SafeVoice is not monitored in real time.</div></div></div></div>
      <div class="row g-3">
        <?php foreach ([['bi-megaphone', 'Speak up', 'Telling someone is not “snitching”. It protects you and others.'], ['bi-exclamation-triangle', 'Report unsafe behaviour', 'Bullying, threats, abuse and harassment are never acceptable.'],
                        ['bi-people', 'Seek help', 'Talk to a parent, guardian, teacher or counselor you trust.'], ['bi-shield-lock', 'Protect yourself', 'Keep messages or notes as evidence, stay near safe people, never retaliate.']] as $x): ?>
          <div class="col-md-6 col-lg-3"><div class="sv-safety-item"><i class="bi <?= e($x[0]) ?>"></i><h3 class="h6 fw-bold mt-2"><?= e($x[1]) ?></h3><p class="small opacity-75 mb-0"><?= e($x[2]) ?></p></div></div>
        <?php endforeach; ?>
      </div>
      <p class="small opacity-75 mt-4 mb-0"><i class="bi bi-info-circle me-1"></i>Please never upload sexual or explicit images of anyone, especially anyone under 18. Describe what happened in words and speak to a trusted adult or the police.</p>
    </div>
  </div>
</section>

<!-- 6. FAQ -->
<section class="sv-sec alt" id="faq">
  <div class="container" style="max-width:820px">
    <div class="text-center mb-5 reveal"><span class="sv-eyebrow">FAQs</span><h2 class="sv-sec-title">Frequently asked questions</h2></div>
    <div class="accordion sv-faq reveal" id="faqList">
      <?php foreach ($faqs as $i => $f): ?>
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button <?= $i ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>" aria-expanded="<?= $i ? 'false' : 'true' ?>"><?= e($f[0]) ?></button></h3>
          <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i ? '' : 'show' ?>" data-bs-parent="#faqList"><div class="accordion-body text-muted"><?= e($f[1]) ?></div></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 7. CONTACT -->
<section class="sv-sec" id="contact">
  <div class="container">
    <div class="text-center mb-5 reveal"><span class="sv-eyebrow">Contact</span><h2 class="sv-sec-title">We are here to help</h2><p class="sv-sec-lead">Questions about SafeVoice? Reach out using the details below.</p></div>
    <div class="row g-4 justify-content-center">
      <?php if ($contact_email !== ''): ?><div class="col-md-6 col-lg-4 reveal"><div class="sv-contact-card"><div class="sv-icon-box mx-auto"><i class="bi bi-envelope"></i></div><h3 class="h6 fw-bold">Email</h3><a href="mailto:<?= e($contact_email) ?>" class="text-muted text-decoration-none"><?= e($contact_email) ?></a></div></div><?php endif; ?>
      <?php if ($contact_phone !== ''): ?><div class="col-md-6 col-lg-4 reveal" style="--d:.1s"><div class="sv-contact-card"><div class="sv-icon-box mx-auto"><i class="bi bi-telephone"></i></div><h3 class="h6 fw-bold">Phone</h3><span class="text-muted"><?= e($contact_phone) ?></span></div></div><?php endif; ?>
      <div class="col-md-6 col-lg-4 reveal" style="--d:.2s"><div class="sv-contact-card"><div class="sv-icon-box mx-auto"><i class="bi bi-person-circle"></i></div><h3 class="h6 fw-bold">Report a concern</h3><a href="<?= $go ?>" class="text-success fw-semibold text-decoration-none">Log in or create an account</a></div></div>
    </div>
  </div>
</section>

<!-- 8. CALL TO ACTION -->
<section class="sv-cta text-center">
  <div class="container position-relative reveal">
    <h2 class="fw-bold display-6 mb-3">Your Voice Can Make a Difference.</h2>
    <p class="lead opacity-75 mb-4">Take the first step today. Your concern will be heard and handled with care.</p>
    <a href="<?= $go ?>" class="btn btn-getstarted btn-lg px-5">Get Started <i class="bi bi-arrow-right ms-1"></i></a>
  </div>
</section>

<!-- Privacy & Terms (linked from the footer) -->
<section class="sv-sec alt" id="legal">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-6" id="privacy"><div class="sv-hover-card sv-legal"><h3 class="h5 fw-bold text-success"><i class="bi bi-shield-lock me-2"></i>Privacy Policy</h3>
        <p>SafeVoice stores the information you provide — account details, school details and complaint content — only to process your complaint.</p>
        <p>Your identity is visible to the authorized investigator and to system administrators, never to the person you report. Your investigator’s identity is not shown to you; replies come from the SafeVoice Investigation Team.</p>
        <p class="mb-0">Passwords are stored as secure hashes and evidence files are kept in private storage that only authorized users can open.</p></div></div>
      <div class="col-lg-6" id="terms"><div class="sv-hover-card sv-legal"><h3 class="h5 fw-bold text-success"><i class="bi bi-file-earmark-text me-2"></i>Terms and Conditions</h3>
        <p>By using SafeVoice you agree to submit only truthful complaints and to keep your login details private.</p>
        <p>False, abusive or malicious reports, and uploading sexual or explicit images, are not allowed and may lead to account suspension.</p>
        <p class="mb-0">SafeVoice is not an emergency service. In immediate danger, call <?= e($emergency) ?> or contact a trusted adult.</p></div></div>
    </div>
  </div>
</section>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
