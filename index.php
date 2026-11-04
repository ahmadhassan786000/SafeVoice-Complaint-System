<?php
/**
 * Entry point.
 *  - Visitors and logged-in STUDENTS see the same public home page.
 *  - Logged-in Administrators / Investigators are forwarded to their own dashboards.
 */
require_once __DIR__ . '/config/app.php';
require_once ROOT_PATH . '/includes/functions.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role']) && $_SESSION['role'] !== ROLE_STUDENT) {
    redirect(dashboard_path_for_role($_SESSION['role']));
}
require ROOT_PATH . '/public/home.php';
