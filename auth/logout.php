<?php
/** Secure logout: clears session data, cookie and destroys the session. */
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

session_start();                       // new session just to carry the flash message
session_regenerate_id(true);
flash_set('success', 'You have been logged out securely.');
redirect('auth/login.php');
