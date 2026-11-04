<?php
/** Renders and clears flash messages. Each alert auto-fades after 3 seconds (see assets/js/main.js). */
foreach (flash_get() as $f): ?>
  <div class="alert alert-<?= e($f['type']) ?> alert-dismissible sv-flash" role="alert">
    <?= e($f['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endforeach; ?>
