<?php
/** Student submits a complaint. Officer is chosen automatically from category assignments. */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_STUDENT);
csrf_require();

$school = trim($_POST['school_name'] ?? '');   $roll = trim($_POST['school_roll_number'] ?? '');
$class  = trim($_POST['class_name'] ?? '');    $gender = $_POST['gender'] ?? '';
$cat_id = (int)($_POST['category_id'] ?? 0);   $rtype = $_POST['reported_person_type'] ?? '';
$rname  = trim($_POST['reported_person_name'] ?? '');
$date   = $_POST['incident_date'] ?? '';       $loc = trim($_POST['incident_location'] ?? '');
$desc   = trim($_POST['description'] ?? '');   $wit = trim($_POST['witness_information'] ?? '');

$err = [];
if ($school === '' || mb_strlen($school) > 150) $err[] = 'School name is required (max 150 characters).';
if ($roll === '' || mb_strlen($roll) > 50)      $err[] = 'School roll number is required.';
if ($class === '' || mb_strlen($class) > 50)    $err[] = 'Class is required.';
if (!in_array($gender, ['Male', 'Female', 'Prefer not to say'], true)) $err[] = 'Please select a gender option.';
if (!db_value($conn, "SELECT id FROM complaint_categories WHERE id = ? AND status = 'active'", 'i', [$cat_id])) $err[] = 'Please choose a valid complaint category.';
if (!in_array($rtype, REPORTED_TYPES, true))    $err[] = 'Please choose who is being reported.';
if (mb_strlen($rname) > 100)                    $err[] = 'Reported person name is too long.';
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date)       $err[] = 'Please enter a valid incident date.';
elseif ($date > date('Y-m-d'))                  $err[] = 'Incident date cannot be in the future.';
if ($loc === '' || mb_strlen($loc) > 200)       $err[] = 'Incident location is required.';
if (mb_strlen($desc) < 20)                      $err[] = 'Please describe the incident in at least 20 characters.';
if (mb_strlen($desc) > 5000 || mb_strlen($wit) > 2000) $err[] = 'Description or witness text is too long.';

if ($err) { old_set($_POST); foreach ($err as $x) flash_set('danger', $x); redirect('student/submit-complaint.php'); }

$officer = officer_for_category($conn, $cat_id);          // null => stays Unassigned

mysqli_begin_transaction($conn);
$cid = db_insert($conn,
    "INSERT INTO complaints (complaint_code, student_user_id, category_id, reported_person_type, reported_person_name, incident_date, incident_location, description, witness_information, status, assigned_officer_id)
     VALUES (?,?,?,?,?,?,?,?,?, 'Pending', ?)",
    'siissssssi', ['TMP-' . bin2hex(random_bytes(8)), $me['id'], $cat_id, $rtype, $rname === '' ? null : $rname, $date, $loc, $desc, $wit === '' ? null : $wit, $officer]);
$ok = (bool)$cid;
if ($ok) {
    $code = make_complaint_code($cid);
    $ok = db_exec($conn, "UPDATE complaints SET complaint_code = ? WHERE id = ?", 'si', [$code, $cid]) !== false
       && db_exec($conn, "INSERT INTO complaint_student_details (complaint_id, school_name, school_roll_number, class_name, gender) VALUES (?,?,?,?,?)", 'issss', [$cid, $school, $roll, $class, $gender]) !== false
       && db_exec($conn, "INSERT INTO complaint_status_history (complaint_id, old_status, new_status, changed_by_user_id, public_description) VALUES (?,?,?,?,?)", 'issis', [$cid, null, 'Pending', $me['id'], 'Complaint submitted']) !== false;
    if ($ok && $officer) {
        $ok = db_exec($conn, "INSERT INTO complaint_assignments (complaint_id, previous_officer_id, new_officer_id, assigned_by_admin_id, reassignment_reason) VALUES (?,?,?,?,?)",
                      'iiiis', [$cid, null, $officer, null, 'Automatic routing by category'], ) !== false;
    }
}
if (!$ok) { mysqli_rollback($conn); old_set($_POST); flash_set('danger', 'Could not submit your complaint. Please try again.'); redirect('student/submit-complaint.php'); }
mysqli_commit($conn);

// Optional evidence (after commit so a bad file never loses the complaint)
$problems = [];
foreach (normalize_files('evidence') as $f) { $e = save_evidence($conn, $cid, (int)$me['id'], $f); if ($e !== '') $problems[] = $e; }

notify_student($conn, (int)$me['id'], 'Complaint submitted', "Your complaint $code was received. We will keep you updated.", 'submitted', $cid);
old_clear();
flash_set('success', "Complaint submitted. Your Complaint ID is $code.");
foreach ($problems as $p) flash_set('warning', 'Evidence not saved - ' . $p);
redirect('student/complaint-details.php?id=' . $cid);
