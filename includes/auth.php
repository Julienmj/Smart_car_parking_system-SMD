<?php
/**
 * Authentication Helpers - Smart Car Parking System
 * Session management and user authentication functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? ''
    ];
}

/**
 * Login user
 * @param int $userId
 * @param string $fullName
 * @param string $email
 * @param string $role
 * @return bool
 */
function loginUser($userId, $fullName, $email, $role) {
    // Regenerate session ID on login to prevent session fixation
    session_regenerate_id(true);
    $_SESSION['user_id']    = $userId;
    $_SESSION['user_name']  = $fullName;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role']  = $role;
    $_SESSION['login_time'] = time();
    return true;
}

/**
 * Logout user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
}

/**
 * Require user to be logged in, redirect to login if not
 * @param string $redirectUrl
 */
function requireLogin($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Require admin role, redirect if not admin
 * @param string $redirectUrl
 */
function requireAdmin($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectUrl);
        exit();
    }
    $user = getCurrentUser();
    if (!$user || $user['role'] !== 'admin') {
        // Client tried to access admin page — send to their dashboard
        header('Location: dashboard-client.php');
        exit();
    }
}

/**
 * Require client role, redirect if not client
 * @param string $redirectUrl
 */
function requireClient($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectUrl);
        exit();
    }
    $user = getCurrentUser();
    if (!$user || $user['role'] !== 'client') {
        // Admin tried to access client page — send to their dashboard
        header('Location: dashboard-admin.php');
        exit();
    }
}

/**
 * Check if current user is admin
 * @return bool
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Check if current user is client
 * @return bool
 */
function isClient() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'client';
}

/**
 * Validate user credentials
 * @param string $email
 * @param string $password
 * @return array|null
 */
function validateUser($email, $password) {
    try {
        $db = getDB();
        
        $sql = "SELECT id, full_name, email, password, role, is_active 
                FROM users 
                WHERE email = ? AND is_active = 1";
        
        $user = $db->fetch($sql, [$email]);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Login validation error: " . $e->getMessage());
        return null;
    }
}

/**
 * Register new user
 * @param string $fullName
 * @param string $email
 * @param string $password
 * @return array
 */
function registerUser($fullName, $email, $password) {
    try {
        $db = getDB();
        
        // Check if email already exists
        $existingUser = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingUser) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $userData = [
            'full_name' => $fullName,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => 'client'
        ];
        
        $userId = $db->insert('users', $userData);
        
        if ($userId) {
            return ['success' => true, 'message' => 'Registration successful', 'user_id' => $userId];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

/**
 * Set flash message for next page load
 * @param string $type
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get flash message and remove it
 * @param string $type
 * @return string|null
 */
function getFlashMessage($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

/**
 * Get all flash messages
 * @return array
 */
function getAllFlashMessages() {
    $messages = $_SESSION['flash'] ?? [];
    $_SESSION['flash'] = [];
    return $messages;
}

/**
 * Sanitize input data
 * @param string $input
 * @return string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength (minimum 6 characters)
 * @param string $password
 * @return bool
 */
function isValidPassword($password) {
    return strlen($password) >= 6;
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
