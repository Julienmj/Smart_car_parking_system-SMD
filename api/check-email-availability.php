<?php
/**
 * API Endpoint - Check Email Availability
 * Used during registration to check if email is already taken
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db.php';

$email = sanitizeInput($_GET['email'] ?? $_POST['email'] ?? '');

if (empty($email)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Email is required'
    ]);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid email format'
    ]);
    exit();
}

$db = getDB();

try {
    // Check if email exists
    $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
    
    echo json_encode([
        'success' => true,
        'available' => !$existing,
        'message' => $existing ? 'Email is already registered' : 'Email is available'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
