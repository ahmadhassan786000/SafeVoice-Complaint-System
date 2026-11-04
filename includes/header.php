<?php
/** Public homepage layout - top part (only used by public/home.php). Set $page_title before including. */
$page_title = $page_title ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> | <?= e(APP_NAME) ?></title>
  <meta name="description" content="SafeVoice is a secure platform for students to report school-related concerns and receive help through a structured investigation process.">
  <link rel="icon" type="image/png" href="<?= url('assets/images/favicon.png') ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
  <link href="<?= url('assets/css/safevoice-ui.css') ?>" rel="stylesheet">
</head>
<body class="sv-public">
<?php include ROOT_PATH . '/includes/navbar.php'; ?>
