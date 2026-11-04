<?php
/** Remove a profile picture. Self, or Admin for any Student/Investigator. */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
$me = require_login();
csrf_require();

$back = safe_return(dashboard_path_for_role($me['role_code']));
$target_id = (int)($_POST['target_user_id'] ?? $me['id']);
$target = get_user_by_id($conn, $target_id);
$allowed = $target && (
    $target_id === (int)$me['id']
    || ($me['role_code'] === ROLE_ADMIN && $target['role_code'] === ROLE_OFFICER)
);
if (!$allowed) { http_response_code(403); flash_set('danger', 'You are not allowed to do that.'); redirect($back); }

remove_profile_image($conn, $target_id);
flash_set('success', 'Profile picture removed.');
redirect($back);
