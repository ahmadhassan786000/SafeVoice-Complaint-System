<?php
/** Student layout (horizontal top header). Needs $me, $active, $page_title. The navigation lives in includes/student-nav.php. */
$active = $active ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title ?? 'Student') ?> | SafeVoice</title>
  <link rel="icon" type="image/png" href="<?= url('assets/images/favicon.png') ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
  <link href="<?= url('assets/css/safevoice-ui.css') ?>" rel="stylesheet">
</head>
<body class="sv-student">
<?php include ROOT_PATH . '/includes/student-nav.php'; ?>
<main class="container py-4 flex-grow-1">
<?php include ROOT_PATH . '/includes/flash-messages.php'; ?>
