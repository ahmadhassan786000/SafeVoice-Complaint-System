<?php /** Admin/Investigator layout (left sidebar). Needs $me, $active, $page_title. */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title ?? 'Dashboard') ?> | SafeVoice</title>
  <link rel="icon" type="image/png" href="<?= url('assets/images/favicon.png') ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
  <link href="<?= url('assets/css/safevoice-ui.css') ?>" rel="stylesheet">
</head>
<body class="sv-panel">
<aside class="sv-sidebar" id="svSidebar"><?php include ROOT_PATH . '/includes/sidebar.php'; ?></aside>
<div class="sv-backdrop" id="svBackdrop"></div>
<div class="sv-main">
  <header class="sv-topbar d-lg-none">
    <button class="btn btn-sm btn-outline-sv" id="svSidebarToggle" aria-label="Open navigation menu" aria-controls="svSidebar" aria-expanded="false">
      <i class="bi bi-list fs-5" aria-hidden="true"></i>
    </button>
    <img src="<?= url('assets/images/safevoice-logo.png') ?>" width="30" height="30" alt="">
    <strong>SafeVoice</strong>
  </header>
  <main class="sv-content">
    <?php include ROOT_PATH . '/includes/flash-messages.php'; ?>
