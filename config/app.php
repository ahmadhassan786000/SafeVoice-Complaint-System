<?php
/**
 * SafeVoice - Central configuration + database connection (Procedural MySQLi).
 * This is the ONLY file inside /config.
 */

// ---------- Database settings ----------
$host     = "localhost";
$username = "root";
$password = "";
$database = "safevoice";

// ---------- Application settings ----------
define('APP_NAME', 'SafeVoice');
define('APP_TAGLINE', 'A Web-Based School Complaint and Student Protection System');
define('BASE_URL', '/SafeVoice/');                 // URL path of the project
define('ROOT_PATH', dirname(__DIR__));             // Filesystem path of the project
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');    // Private upload storage
define('MAX_PROFILE_IMAGE_SIZE', 2 * 1024 * 1024); // 2 MB
define('MAX_EVIDENCE_SIZE', 10 * 1024 * 1024);     // 10 MB
define('SESSION_IDLE_SECONDS', 7200);              // 2 hours idle timeout

// Role codes (exactly three roles)
define('ROLE_STUDENT', 'STUDENT');
define('ROLE_OFFICER', 'INVESTIGATOR');
define('ROLE_ADMIN',   'SUPER_ADMIN');

date_default_timezone_set('Asia/Karachi');

// Never show raw errors to ordinary users; log them instead.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ---------- Secure session ----------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('SAFEVOICESESSID');
    session_start();
}

// ---------- Database connection (procedural MySQLi) ----------
// Make MySQLi return false on errors instead of throwing exceptions.
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    error_log('SafeVoice DB connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    die('Database connection failed. Please check config/app.php and make sure MySQL is running.');
}

mysqli_set_charset($conn, 'utf8mb4');
