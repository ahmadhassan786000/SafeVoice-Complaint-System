<?php
/**
 * SafeVoice - Authentication middleware.
 * Usage at the top of every protected page/action:
 *   require_once __DIR__ . '/../config/app.php';
 *   require_once ROOT_PATH . '/includes/functions.php';
 *   require_once ROOT_PATH . '/includes/csrf.php';
 *   require_once ROOT_PATH . '/middleware/auth.php';
 *   require_once ROOT_PATH . '/middleware/role-check.php';
 *   $me = require_role(ROLE_STUDENT);
 */

/** Ensures a valid, active session. Returns the fresh user row from the database. */
function require_login() {
    global $conn;

    if (empty($_SESSION['user_id'])) {
        flash_set('warning', 'Please log in to continue.');
        redirect('auth/login.php');
    }

    // Idle timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_IDLE_SECONDS) {
        session_unset();
        session_destroy();
        session_start();
        flash_set('warning', 'You were logged out due to inactivity.');
        redirect('auth/login.php');
    }
    $_SESSION['last_activity'] = time();

    // Always re-check the database so deactivation/suspension applies immediately
    $user = get_user_by_id($conn, $_SESSION['user_id']);
    if (!$user || $user['account_status'] !== 'active') {
        session_unset();
        session_destroy();
        session_start();
        flash_set('danger', 'Your account is not active. Please contact the administrator.');
        redirect('auth/login.php');
    }

    // The role always comes from the database, never from the browser
    $_SESSION['role'] = $user['role_code'];

    return $user;
}
