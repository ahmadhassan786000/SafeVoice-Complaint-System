<?php /** Public homepage footer (large, 4 columns + bottom bar). All internal links scroll to homepage sections. */
$contact_email = get_setting($conn, 'contact_email'); $contact_phone = get_setting($conn, 'contact_phone');
?>
<footer class="sv-pub-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <div class="brand mb-3"><img src="<?= url('assets/images/safevoice-logo.png') ?>" alt="" width="44" height="44"><span>SafeVoice</span></div>
        <p class="mb-3">A secure, supportive platform where students can report school-related concerns and follow their complaint through a structured investigation process.</p>
        <a class="btn btn-getstarted btn-sm" href="<?= url('auth/login.php') ?>">Get Started <i class="bi bi-arrow-right ms-1"></i></a>
      </div>
      <div class="col-lg-2 col-md-6 col-6">
        <h3>Quick Links</h3>
        <ul><li><a href="#home">Home</a></li><li><a href="#about">About</a></li><li><a href="#features">Features</a></li><li><a href="#safety">Safety</a></li></ul>
      </div>
      <div class="col-lg-3 col-md-6 col-6">
        <h3>Complaint Categories</h3>
        <ul><li><a href="#categories">Bullying</a></li><li><a href="#categories">Harassment</a></li><li><a href="#categories">Physical Abuse</a></li><li><a href="#categories">Discrimination</a></li><li><a href="#categories">Threats</a></li><li><a href="#categories">Other Concerns</a></li></ul>
      </div>
      <div class="col-lg-3 col-md-6">
        <h3>Support</h3>
        <ul><li><a href="#faq">FAQs</a></li><li><a href="#contact">Contact</a></li><li><a href="#privacy">Privacy Policy</a></li><li><a href="#terms">Terms and Conditions</a></li></ul>
      </div>
    </div>
    <div class="sv-footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <div>&copy; <?= date('Y') ?> SafeVoice. All rights reserved.</div>
      <div><i class="bi bi-shield-check me-1 text-success"></i>Speak up. Stay safe. Be heard.</div>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
<script src="<?= url('assets/js/home.js') ?>"></script>
</body>
</html>
