<?php
/** SafeVoice - CSRF protection (procedural). */

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Echo inside every POST form: <?= csrf_field() ?> */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify($token = null) {
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Call at the top of every action file that handles a POST. */
function csrf_require() {
    if (!is_post() || !csrf_verify()) {
        http_response_code(419);
        flash_set('danger', 'Your session expired or the request was invalid. Please try again.');
        $back = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'index.php');
        header('Location: ' . $back);
        exit;
    }
}
