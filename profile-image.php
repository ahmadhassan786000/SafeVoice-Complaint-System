<?php
/**
 * Protected profile image delivery: profile-image.php?id=USER_ID
 * - Anyone may fetch their own image.
 * - Admin may fetch any image.
 * - Investigator may fetch a Student image only for a student whose complaint is assigned to them.
 * - Students can NEVER fetch Investigator (or Admin) images.
 * Anything not allowed returns the neutral default avatar, so nothing is revealed.
 */
require_once __DIR__ . '/config/app.php';
require_once ROOT_PATH . '/includes/functions.php';

function send_default_avatar($role_code) {
    $file = $role_code === ROLE_ADMIN ? 'default-admin.png' : ($role_code === ROLE_OFFICER ? 'default-investigator.png' : 'default-student.png');
    header('Content-Type: image/png');
    header('Cache-Control: private, max-age=300');
    readfile(ROOT_PATH . '/assets/images/' . $file);
    exit;
}

if (empty($_SESSION['user_id'])) { http_response_code(403); exit; }

$viewer_id   = (int)$_SESSION['user_id'];
$viewer_role = $_SESSION['role'] ?? '';
$target_id   = (int)($_GET['id'] ?? 0);

$target = $target_id ? get_user_by_id($conn, $target_id) : null;
if (!$target) { send_default_avatar(ROLE_STUDENT); }

$allowed = false;
if ($viewer_id === $target_id)                                   $allowed = true;
elseif ($viewer_role === ROLE_ADMIN)                             $allowed = true;
elseif ($viewer_role === ROLE_OFFICER && $target['role_code'] === ROLE_STUDENT) {
    $allowed = (bool)db_value($conn,
        "SELECT id FROM complaints WHERE student_user_id = ? AND assigned_officer_id = ? LIMIT 1",
        'ii', [$target_id, $viewer_id]);
}

if (!$allowed || empty($target['profile_image'])) { send_default_avatar($target['role_code']); }

$name = basename($target['profile_image']);                      // prevent directory traversal
$path = UPLOAD_PATH . 'profiles/' . $name;
if (!is_file($path)) { send_default_avatar($target['role_code']); }

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $path);
finfo_close($finfo);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) { send_default_avatar($target['role_code']); }

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');
readfile($path);
