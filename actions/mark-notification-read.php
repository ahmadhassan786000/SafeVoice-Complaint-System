<?php
/** Student-only: mark one notification (and open its target) or all notifications as read. */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_STUDENT);
csrf_require();

if (!empty($_POST['all'])) {
    db_exec($conn, "UPDATE notifications SET is_read = 1 WHERE recipient_user_id = ?", 'i', [$me['id']]);
    flash_set('success', 'All notifications marked as read.');
    redirect('student/notifications.php');
}
$id = (int)($_POST['id'] ?? 0);
$n = db_one($conn, "SELECT id, notification_type, related_complaint_id FROM notifications WHERE id = ? AND recipient_user_id = ?", 'ii', [$id, $me['id']]);
if (!$n) redirect('student/notifications.php');
db_exec($conn, "UPDATE notifications SET is_read = 1 WHERE id = ?", 'i', [$id]);
redirect(!empty($_POST['stay']) ? 'student/notifications.php' : notification_target($n));
