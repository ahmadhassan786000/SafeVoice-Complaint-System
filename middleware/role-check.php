<?php
/** SafeVoice - Role-based authorization (procedural). */

/**
 * Require the logged-in user to have exactly one of the given roles.
 * Accepts a string or an array of role codes. Returns the user row.
 */
function require_role($roles) {
    $roles = (array)$roles;
    $user = require_login();

    if (!in_array($user['role_code'], $roles, true)) {
        http_response_code(403);
        flash_set('danger', 'You do not have permission to access that page.');
        redirect(dashboard_path_for_role($user['role_code']));
    }
    return $user;
}
