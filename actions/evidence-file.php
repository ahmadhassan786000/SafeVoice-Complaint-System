<?php
/**
 * Protected evidence delivery: actions/evidence-file.php?id=EVIDENCE_ID[&dl=1]
 * Students: own complaints. Officers: assigned complaints. Admin: all.
 */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/middleware/auth.php';

$me = require_login();
$ev = db_one($conn, "SELECT id, complaint_id, file_name, file_path, file_type FROM complaint_evidence WHERE id = ?", 'i', [(int)($_GET['id'] ?? 0)]);
if (!$ev || !can_access_complaint($conn, $ev['complaint_id'], $me)) { http_response_code(404); exit('Not found'); }

$path = UPLOAD_PATH . 'evidence/' . basename($ev['file_path']);
if (!is_file($path)) { http_response_code(404); exit('Not found'); }

$inline_ok = in_array($ev['file_type'], ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true) && empty($_GET['dl']);
header('Content-Type: ' . ($inline_ok ? $ev['file_type'] : 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'");
header('Content-Disposition: ' . ($inline_ok ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $ev['file_name']) . '"');
readfile($path);
