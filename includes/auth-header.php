<?php
/**
 * Standalone layout for Login / Signup / Change Password.
 * No public header, no footer, no dashboard navigation. Set $page_title before including.
 * The logo sits INSIDE the card and links to index.php (visitors -> homepage, logged-in users -> their dashboard).
 */
$page_title = $page_title ?? 'Welcome';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> | SafeVoice</title>
  <link rel="icon" type="image/png" href="<?= url('assets/images/favicon.png') ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="sv-auth-page">
<div class="sv-auth-bg" aria-hidden="true"><span class="s1"></span><span class="s2"></span><span class="s3"></span><span class="s4"></span></div>
<main class="sv-auth-screen">
  <div class="sv-auth-box">
    <div class="sv-auth-card2">
      <a href="<?= url('index.php') ?>" class="sv-auth-logo" title="SafeVoice home" aria-label="SafeVoice home">
        <img src="<?= url('assets/images/safevoice-logo.png') ?>" alt="SafeVoice" width="88" height="88">
        <!-- <span>SafeVoice</span> -->
      </a>
      <?php include ROOT_PATH . '/includes/flash-messages.php'; ?>
