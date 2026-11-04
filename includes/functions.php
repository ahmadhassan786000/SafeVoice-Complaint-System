<?php
/**
 * SafeVoice - Reusable procedural helper functions (MySQLi).
 * Include with: require_once ROOT_PATH . '/includes/functions.php';
 */

// =====================================================================
// DATABASE HELPERS (procedural MySQLi, prepared statements everywhere)
// =====================================================================

/** Prepare + bind + execute. Returns the statement on success, false on failure. */
function db_run($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log('SQL prepare error: ' . mysqli_error($conn) . ' | ' . $sql);
        return false;
    }
    if ($types !== '' && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        error_log('SQL execute error: ' . mysqli_stmt_error($stmt) . ' | ' . $sql);
        mysqli_stmt_close($stmt);
        return false;
    }
    return $stmt;
}

/** SELECT one row (associative array) or null. */
function db_one($conn, $sql, $types = '', $params = []) {
    $stmt = db_run($conn, $sql, $types, $params);
    if (!$stmt) return null;
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

/** SELECT many rows (array of associative arrays). */
function db_all($conn, $sql, $types = '', $params = []) {
    $stmt = db_run($conn, $sql, $types, $params);
    if (!$stmt) return [];
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

/** SELECT a single value (first column of first row) or null. */
function db_value($conn, $sql, $types = '', $params = []) {
    $row = db_one($conn, $sql, $types, $params);
    return $row ? array_values($row)[0] : null;
}

/** INSERT/UPDATE/DELETE. Returns affected rows (int) or false on error. */
function db_exec($conn, $sql, $types = '', $params = []) {
    $stmt = db_run($conn, $sql, $types, $params);
    if (!$stmt) return false;
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected;
}

/** INSERT and return the new auto-increment ID (or false on error). */
function db_insert($conn, $sql, $types = '', $params = []) {
    $stmt = db_run($conn, $sql, $types, $params);
    if (!$stmt) return false;
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $id;
}

// =====================================================================
// OUTPUT, REDIRECTS, URLS
// =====================================================================

/** Escape output (XSS protection). */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// =====================================================================
// FLASH MESSAGES + OLD INPUT
// =====================================================================

function flash_set($type, $message) {          // type: success | danger | warning | info
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function old_set($data) {
    unset($data['password'], $data['confirm_password'], $data['current_password'], $data['new_password']);
    $_SESSION['old'] = $data;
}

function old($key, $default = '') {
    return $_SESSION['old'][$key] ?? $default;
}

function old_clear() {
    unset($_SESSION['old']);
}

// =====================================================================
// ROLES, DASHBOARDS, USERS
// =====================================================================

function dashboard_path_for_role($role) {
    switch ($role) {
        case ROLE_ADMIN:   return 'admin/dashboard.php';
        case ROLE_OFFICER: return 'officer/dashboard.php';
        case ROLE_STUDENT: return 'index.php';   // Students land on the public Home page (no separate Student Dashboard)
    }
    return 'auth/login.php';
}

function get_role_id($conn, $role_code) {
    return db_value($conn, "SELECT id FROM roles WHERE role_code = ?", 's', [$role_code]);
}

function current_user_id()   { return $_SESSION['user_id'] ?? null; }
function current_user_role() { return $_SESSION['role'] ?? null; }

function get_user_by_id($conn, $id) {
    return db_one($conn,
        "SELECT u.id, u.student_id, u.full_name, u.username, u.email, u.phone, u.profile_image,
                u.account_status, u.is_protected, u.created_at, r.role_code
         FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?", 'i', [(int)$id]);
}

/** Create the student ID after insert, e.g. SV-2026-00007 */
function make_student_id($user_id) {
    return sprintf('SV-%s-%05d', date('Y'), $user_id);
}

/** Complaint code, e.g. SV-C-2026-000042 (used in Phase 4) */
function make_complaint_code($complaint_id) {
    return sprintf('SV-C-%s-%06d', date('Y'), $complaint_id);
}

/** URL of a user's avatar. Real images are served by the protected endpoint actions/profile-image.php. */
function avatar_url($user) {
    if (!empty($user['profile_image'])) {
        return url('actions/profile-image.php?id=' . (int)$user['id']);
    }
    switch ($user['role_code'] ?? '') {
        case ROLE_ADMIN:   return url('assets/images/default-admin.png');
        case ROLE_OFFICER: return url('assets/images/default-investigator.png');
    }
    return url('assets/images/default-student.png');
}

// =====================================================================
// VALIDATION
// =====================================================================

function valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 150;
}

function valid_phone($phone) {
    return $phone === '' || preg_match('/^[0-9+\-\s()]{7,20}$/', $phone) === 1;
}

/** Password rule: minimum 8 characters, nothing else. (72 is bcrypt's hard limit.) Returns '' when OK. */
function password_error($password) {
    if (strlen($password) < 8)  return 'Password must be at least 8 characters long.';
    if (strlen($password) > 72) return 'Password must not exceed 72 characters.';
    return '';
}

/** Phone check used where a phone number is mandatory (student signup / student profile). */
function phone_required_ok($phone) {
    return $phone !== '' && valid_phone($phone) && preg_match_all('/[0-9]/', $phone) >= 7;
}

// =====================================================================
// STUDENT NOTIFICATIONS (students only - never call for other roles)
// =====================================================================

function notify_student($conn, $student_user_id, $title, $message, $type, $complaint_id = null) {
    return db_exec($conn,
        "INSERT INTO notifications (recipient_user_id, title, message, notification_type, related_complaint_id)
         VALUES (?,?,?,?,?)", 'isssi', [$student_user_id, $title, $message, $type, $complaint_id]);
}

// =====================================================================
// STATUS BADGE (shared by all dashboards)
// =====================================================================

function status_badge($status) {
    $map = [
        'Pending' => 'sv-badge-pending', 'Under Review' => 'sv-badge-review',
        'Under Investigation' => 'sv-badge-investigation', 'Resolved' => 'sv-badge-resolved',
        'Closed' => 'sv-badge-closed', 'Unassigned' => 'sv-badge-unassigned',
    ];
    $class = $map[$status] ?? 'sv-badge-closed';
    return '<span class="sv-badge ' . $class . '">' . e($status) . '</span>';
}

// =====================================================================
// SETTINGS, SAFE REDIRECT, MISC
// =====================================================================

function get_setting($conn, $key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db_all($conn, "SELECT setting_key, setting_value FROM system_settings") as $r) {
            $cache[$r['setting_key']] = $r['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

/** Return-to path from a POST form, only if it is a local .php path (prevents open redirects). */
function safe_return($default) {
    $r = $_POST['return'] ?? '';
    if (is_string($r) && preg_match('#^[A-Za-z0-9_\-/]+\.php(\?[A-Za-z0-9=&_\-]*)?$#', $r)) return $r;
    return $default;
}

function fmt_date($ts, $withTime = true) {
    if (!$ts) return '-';
    return date($withTime ? 'd M Y, h:i A' : 'd M Y', strtotime($ts));
}

function pick($value, $allowed, $default = '') {
    return in_array($value, $allowed, true) ? $value : $default;
}

const COMPLAINT_STATUSES = ['Pending', 'Under Review', 'Under Investigation', 'Resolved', 'Closed'];
const REPORTED_TYPES = ['Student', 'Teacher', 'Staff Member', 'Principal / Administration', 'Unknown', 'Other'];

// =====================================================================
// FILE UPLOADS (profile pictures + evidence)
// =====================================================================

function normalize_files($key) {
    $out = [];
    if (empty($_FILES[$key])) return $out;
    $f = $_FILES[$key];
    if (is_array($f['name'])) {
        foreach ($f['name'] as $i => $n) {
            if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            $out[] = ['name' => $n, 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
    } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) {
        $out[] = $f;
    }
    return $out;
}

function detect_mime($path) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($fi, $path);
    finfo_close($fi);
    return $mime;
}

/** Validate + store a profile picture for $user_id. Returns '' on success or an error message. */
function set_profile_image($conn, $user_id, $file) {
    if ($file['error'] !== UPLOAD_ERR_OK)         return 'Upload failed. Please try again.';
    if ($file['size'] > MAX_PROFILE_IMAGE_SIZE)   return 'Image must be 2 MB or smaller.';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) return 'Only JPG, PNG or WEBP images are allowed.';
    if (!is_uploaded_file($file['tmp_name']))     return 'Invalid upload.';
    $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = detect_mime($file['tmp_name']);
    if (!isset($map[$mime]) || @getimagesize($file['tmp_name']) === false) return 'The file is not a valid image.';

    $name = bin2hex(random_bytes(16)) . '.' . $map[$mime];
    if (!is_dir(UPLOAD_PATH . 'profiles')) mkdir(UPLOAD_PATH . 'profiles', 0775, true);
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . 'profiles/' . $name)) return 'Could not save the image.';

    $old = db_value($conn, "SELECT profile_image FROM users WHERE id = ?", 'i', [$user_id]);
    if (db_exec($conn, "UPDATE users SET profile_image = ? WHERE id = ?", 'si', [$name, $user_id]) === false) {
        @unlink(UPLOAD_PATH . 'profiles/' . $name);
        return 'Could not update the profile.';
    }
    if ($old) @unlink(UPLOAD_PATH . 'profiles/' . basename($old));
    return '';
}

function remove_profile_image($conn, $user_id) {
    $old = db_value($conn, "SELECT profile_image FROM users WHERE id = ?", 'i', [$user_id]);
    db_exec($conn, "UPDATE users SET profile_image = NULL WHERE id = ?", 'i', [$user_id]);
    if ($old) @unlink(UPLOAD_PATH . 'profiles/' . basename($old));
}

const EVIDENCE_TYPES = [
    'jpg'  => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp'],
    'pdf'  => ['application/pdf'], 'txt' => ['text/plain'],
    'doc'  => ['application/msword', 'application/x-ole-storage', 'application/CDFV2', 'application/octet-stream'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
];

/** Validate + store one evidence file. Returns '' on success or an error message. */
function save_evidence($conn, $complaint_id, $uploader_id, $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) return $file['name'] . ': upload failed.';
    $max = (int)get_setting($conn, 'max_evidence_mb', 10) * 1024 * 1024;
    if ($file['size'] > min($max, MAX_EVIDENCE_SIZE)) return $file['name'] . ': file is too large.';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset(EVIDENCE_TYPES[$ext]))              return $file['name'] . ': this file type is not allowed.';
    if (!is_uploaded_file($file['tmp_name']))      return $file['name'] . ': invalid upload.';
    $mime = detect_mime($file['tmp_name']);
    if (!in_array($mime, EVIDENCE_TYPES[$ext], true)) return $file['name'] . ': file content does not match its type.';
    if (strpos($ext, 'php') !== false)             return $file['name'] . ': not allowed.';

    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!is_dir(UPLOAD_PATH . 'evidence')) mkdir(UPLOAD_PATH . 'evidence', 0775, true);
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . 'evidence/' . $stored)) return $file['name'] . ': could not be saved.';

    $orig = mb_substr(preg_replace('/[^\w .\-()]/u', '_', basename($file['name'])), 0, 200);
    $ok = db_insert($conn,
        "INSERT INTO complaint_evidence (complaint_id, uploaded_by_user_id, file_name, file_path, file_type, file_size) VALUES (?,?,?,?,?,?)",
        'iisssi', [$complaint_id, $uploader_id, $orig, $stored, $mime, (int)$file['size']]);
    if (!$ok) { @unlink(UPLOAD_PATH . 'evidence/' . $stored); return $file['name'] . ': database error.'; }
    return '';
}

// =====================================================================
// COMPLAINT ACCESS, STATUS, ASSIGNMENT
// =====================================================================

/** Server-side complaint authorization for every role. */
function can_access_complaint($conn, $complaint_id, $user) {
    $complaint_id = (int)$complaint_id;
    if ($complaint_id <= 0) return false;
    switch ($user['role_code']) {
        case ROLE_ADMIN:
            return (bool)db_value($conn, "SELECT id FROM complaints WHERE id = ?", 'i', [$complaint_id]);
        case ROLE_OFFICER:
            return (bool)db_value($conn, "SELECT id FROM complaints WHERE id = ? AND assigned_officer_id = ?", 'ii', [$complaint_id, $user['id']]);
        case ROLE_STUDENT:
            return (bool)db_value($conn, "SELECT id FROM complaints WHERE id = ? AND student_user_id = ?", 'ii', [$complaint_id, $user['id']]);
    }
    return false;
}

/**
 * STUDENT-FACING complaint query. Deliberately selects NO officer columns
 * (no assigned_officer_id, no names, no emails) to protect Investigator identity.
 */
function student_get_complaint($conn, $complaint_id, $student_id) {
    return db_one($conn,
        "SELECT c.id, c.complaint_code, c.reported_person_type, c.reported_person_name, c.incident_date, c.incident_location,
                c.description, c.witness_information, c.status, c.created_at, c.updated_at,
                cat.category_name, d.school_name, d.school_roll_number, d.class_name, d.gender,
                u.full_name AS student_name, u.student_id AS student_code
         FROM complaints c
         JOIN complaint_categories cat ON cat.id = c.category_id
         JOIN complaint_student_details d ON d.complaint_id = c.id
         JOIN users u ON u.id = c.student_user_id
         WHERE c.id = ? AND c.student_user_id = ?", 'ii', [(int)$complaint_id, (int)$student_id]);
}

/** Officer/Admin complaint query with full student identity. Officer is restricted to their own assignments. */
function staff_get_complaint($conn, $complaint_id, $user) {
    $sql = "SELECT c.*, cat.category_name, d.school_name, d.school_roll_number, d.class_name, d.gender,
                   s.id AS student_uid, s.full_name AS student_name, s.student_id AS student_code, s.email AS student_email,
                   s.phone AS student_phone, s.profile_image AS student_image,
                   o.full_name AS officer_name, o.email AS officer_email, o.profile_image AS officer_image
            FROM complaints c
            JOIN complaint_categories cat ON cat.id = c.category_id
            JOIN complaint_student_details d ON d.complaint_id = c.id
            JOIN users s ON s.id = c.student_user_id
            LEFT JOIN users o ON o.id = c.assigned_officer_id
            WHERE c.id = ?";
    $types = 'i'; $params = [(int)$complaint_id];
    if ($user['role_code'] === ROLE_OFFICER) { $sql .= " AND c.assigned_officer_id = ?"; $types .= 'i'; $params[] = (int)$user['id']; }
    elseif ($user['role_code'] !== ROLE_ADMIN) return null;
    return db_one($conn, $sql, $types, $params);
}

function status_public_text($status) {
    switch ($status) {
        case 'Pending':             return 'Complaint submitted';
        case 'Under Review':        return 'Your complaint is under review';
        case 'Under Investigation': return 'Investigation has started';
        case 'Resolved':            return 'Your complaint has been resolved';
        case 'Closed':              return 'Your complaint has been closed';
    }
    return 'Status updated';
}

/** Change status + record history + notify student (notification text never names the officer). */
function set_complaint_status($conn, $complaint_id, $new_status, $by_user_id, $internal_notes = null) {
    if (!in_array($new_status, COMPLAINT_STATUSES, true)) return false;
    $c = db_one($conn, "SELECT status, student_user_id, complaint_code FROM complaints WHERE id = ?", 'i', [$complaint_id]);
    if (!$c) return false;
    if ($c['status'] === $new_status) return true;

    mysqli_begin_transaction($conn);
    $ok = db_exec($conn, "UPDATE complaints SET status = ? WHERE id = ?", 'si', [$new_status, $complaint_id]) !== false
       && db_exec($conn, "INSERT INTO complaint_status_history (complaint_id, old_status, new_status, changed_by_user_id, public_description, internal_notes) VALUES (?,?,?,?,?,?)",
                  'ississ', [$complaint_id, $c['status'], $new_status, $by_user_id, status_public_text($new_status), $internal_notes]) !== false;
    if (!$ok) { mysqli_rollback($conn); return false; }
    mysqli_commit($conn);

    $type = $new_status === 'Resolved' ? 'resolved' : ($new_status === 'Closed' ? 'closed' : 'status');
    notify_student($conn, (int)$c['student_user_id'], 'Complaint status updated',
        'Complaint ' . $c['complaint_code'] . ': ' . status_public_text($new_status) . '.', $type, $complaint_id);
    return true;
}

/** Add a student-safe event to the timeline without changing status. */
function add_public_event($conn, $complaint_id, $by_user_id, $text, $internal = null) {
    $status = db_value($conn, "SELECT status FROM complaints WHERE id = ?", 'i', [$complaint_id]);
    return db_exec($conn, "INSERT INTO complaint_status_history (complaint_id, old_status, new_status, changed_by_user_id, public_description, internal_notes) VALUES (?,?,?,?,?,?)",
                   'ississ', [$complaint_id, $status, $status, $by_user_id, $text, $internal]);
}

/** Assign/reassign a complaint and keep full history. $admin_id = null for automatic routing. */
function assign_complaint($conn, $complaint_id, $new_officer_id, $admin_id, $reason) {
    $prev = db_value($conn, "SELECT assigned_officer_id FROM complaints WHERE id = ?", 'i', [$complaint_id]);
    mysqli_begin_transaction($conn);
    $ok = db_exec($conn, "UPDATE complaints SET assigned_officer_id = ? WHERE id = ?", 'ii', [$new_officer_id, $complaint_id]) !== false
       && db_exec($conn, "INSERT INTO complaint_assignments (complaint_id, previous_officer_id, new_officer_id, assigned_by_admin_id, reassignment_reason) VALUES (?,?,?,?,?)",
                  'iiiis', [$complaint_id, $prev, $new_officer_id, $admin_id, $reason]) !== false;
    if ($ok && $new_officer_id) {
        $ok = db_exec($conn, "UPDATE investigations SET officer_user_id = ? WHERE complaint_id = ?", 'ii', [$new_officer_id, $complaint_id]) !== false;
    }
    if (!$ok) { mysqli_rollback($conn); return false; }
    mysqli_commit($conn);
    return true;
}

/** Active officer currently assigned to a category (or null). */
function officer_for_category($conn, $category_id) {
    return db_value($conn,
        "SELECT ca.officer_user_id FROM category_assignments ca JOIN users u ON u.id = ca.officer_user_id
         WHERE ca.category_id = ? AND u.account_status = 'active'", 'i', [$category_id]);
}

function category_options($conn, $only_active = true) {
    return db_all($conn, "SELECT id, category_name FROM complaint_categories " . ($only_active ? "WHERE status = 'active' " : "") . "ORDER BY id");
}

function notification_target($n) {
    if (!$n['related_complaint_id']) return 'student/notifications.php';
    if (in_array($n['notification_type'], ['message', 'info_request'], true)) return 'student/messages.php?c=' . (int)$n['related_complaint_id'];
    return 'student/complaint-details.php?id=' . (int)$n['related_complaint_id'];
}

// Compact time for conversation lists: "09:41 AM" today, "02 Nov" this year, "02 Nov 2025" otherwise.
function fmt_short_time($ts) {
    if (!$ts) return '';
    $t = strtotime($ts);
    if (date('Y-m-d', $t) === date('Y-m-d')) return date('h:i A', $t);
    return date(date('Y', $t) === date('Y') ? 'd M' : 'd M Y', $t);
}

// Day label for message separators: Today / Yesterday / "02 Nov 2026".
function fmt_day_label($ts) {
    $d = date('Y-m-d', strtotime($ts));
    if ($d === date('Y-m-d')) return 'Today';
    if ($d === date('Y-m-d', strtotime('-1 day'))) return 'Yesterday';
    return date('d M Y', strtotime($ts));
}

