<?php
/**
 * Application Configuration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/gamification.php';

// Application settings
define('APP_NAME', 'Online Leerplatform');
define('BASE_URL', '/');

// Security settings
define('PASSWORD_MIN_LENGTH', 6);

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_TEACHER', 'docent');
define('ROLE_STUDENT', 'student');

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_ADMIN;
}

/**
 * Check if user is a docent (teacher)
 * @return bool
 */
function isTeacher() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_TEACHER;
}

/**
 * Check if user is a student
 * @return bool
 */
function isStudent() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_STUDENT;
}

/**
 * Check if user is admin or docent
 * @return bool
 */
function isAdminOrTeacher() {
    return isAdmin() || isTeacher();
}

/**
 * Get human-readable role label
 */
function roleLabel($role) {
    switch ($role) {
        case ROLE_ADMIN: return 'Admin';
        case ROLE_TEACHER: return 'Docent';
        case ROLE_STUDENT: return 'Student';
        default: return ucfirst((string)$role);
    }
}

/**
 * Require login - redirect to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require admin - redirect if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Require admin or docent
 */
function requireAdminOrTeacher() {
    requireLogin();
    if (!isAdminOrTeacher()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Require docent
 */
function requireTeacher() {
    requireLogin();
    if (!isTeacher()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Sanitize input
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Validate email
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}


