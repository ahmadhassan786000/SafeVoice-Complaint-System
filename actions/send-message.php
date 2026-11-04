<?php
/** Send a message in a complaint conversation (Student or assigned Officer). */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role([ROLE_STUDENT, ROLE_OFFICER]);
csrf_require();

$cid = (int)($_POST['complaint_id'] ?? 0);
$msg = trim($_POST['message'] ?? '');
$is_student = $me['role_code'] === ROLE_STUDENT;
$back = ($is_student ? 'student/messages.php' : 'officer/messages.php') . '?c=' . $cid;

if (!can_access_complaint($conn, $cid, $me)) { http_response_code(403); flash_set('danger', 'Access denied.'); redirect(dashboard_path_for_role($me['role_code'])); }
if ($msg === '' || mb_strlen($msg) > 3000) { flash_set('danger', 'Please write a message (up to 3000 characters).'); redirect($back); }

$info = (!$is_student && !empty($_POST['is_info_request'])) ? 1 : 0;
$role = $is_student ? 'STUDENT' : 'INVESTIGATOR';
db_exec($conn, "INSERT INTO complaint_messages (complaint_id, sender_user_id, sender_role, message, is_info_request) VALUES (?,?,?,?,?)",
        'iissi', [$cid, $me['id'], $role, $msg, $info]);

if (!$is_student) {
    $c = db_one($conn, "SELECT student_user_id, complaint_code FROM complaints WHERE id = ?", 'i', [$cid]);
    if ($info) {
        notify_student($conn, (int)$c['student_user_id'], 'Additional information requested',
            'The SafeVoice Investigation Team needs more information about ' . $c['complaint_code'] . '.', 'info_request', $cid);
        add_public_event($conn, $cid, (int)$me['id'], 'Additional information requested');
    } else {
        notify_student($conn, (int)$c['student_user_id'], 'New message',
            'You have a new message from the SafeVoice Investigation Team about ' . $c['complaint_code'] . '.', 'message', $cid);
    }
}
flash_set('success', 'Message sent.');
redirect($back);
