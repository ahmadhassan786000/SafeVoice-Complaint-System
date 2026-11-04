</main>
<footer class="sv-footer sv-student-footer mt-auto">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-lg-4">
        <a class="d-inline-flex align-items-center gap-2 text-white text-decoration-none mb-3" href="<?= url('index.php') ?>">
          <img src="<?= url('assets/images/safevoice-logo.png') ?>" alt="" width="40" height="40"><strong class="fs-5">SafeVoice</strong>
        </a>
        <p class="small text-white-50 mb-0">A safe, confidential way for students to report concerns, follow progress, and find support.</p>
      </div>
      <div class="col-6 col-lg-2">
        <h2 class="h6 text-white fw-bold">Quick Links</h2>
        <ul class="list-unstyled small d-grid gap-2 mb-0">
          <li><a href="<?= url('index.php') ?>">Home</a></li>
          <li><a href="<?= url('student/my-complaints.php') ?>">My Complaints</a></li>
          <li><a href="<?= url('student/submit-complaint.php') ?>">Submit a Complaint</a></li>
          <li><a href="<?= url('student/messages.php') ?>">Messages</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h2 class="h6 text-white fw-bold">Student Resources</h2>
        <ul class="list-unstyled small d-grid gap-2 mb-0">
          <li><a href="<?= url('student/help.php') ?>">Help &amp; Safety</a></li>
          <li><a href="<?= url('public/how-it-works.php') ?>">How it works</a></li>
          <li><a href="<?= url('public/emergency-help.php') ?>">Emergency help</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <h2 class="h6 text-white fw-bold">Help &amp; Support</h2>
        <div class="small d-grid gap-2 text-white-50">
          <div><i class="bi bi-envelope me-2"></i><a href="mailto:<?= e(get_setting($conn, 'contact_email')) ?>"><?= e(get_setting($conn, 'contact_email')) ?></a></div>
          <div><i class="bi bi-telephone me-2"></i><span><?= e(get_setting($conn, 'contact_phone')) ?></span></div>
        </div>
        <div class="small mt-3 d-flex gap-3"><a href="<?= url('public/home.php#privacy') ?>">Privacy Policy</a><a href="<?= url('public/home.php#terms') ?>">Terms &amp; Conditions</a></div>
      </div>
    </div>
    <hr class="border-light opacity-25 my-4">
    <div class="small text-white-50 d-flex flex-wrap justify-content-between gap-2">
      <span>&copy; <?= date('Y') ?> SafeVoice. All rights reserved.</span><span>Your voice matters. Your privacy matters.</span>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
<?= $page_scripts ?? '' ?>
</body></html>
