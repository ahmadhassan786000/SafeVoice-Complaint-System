<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$page_title = 'Profile Settings'; $active = '';
include ROOT_PATH . '/includes/panel-header.php';
$target = $me; $return = 'admin/profile.php'; $show_info_form = true;
?>
<h1 class="sv-page-title">Admin Profile Settings</h1>
<?php include ROOT_PATH . '/includes/profile-forms.php'; ?>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
