<?php
/**
 * API Endpoint - Get User Parking History
 * Returns detailed parking history for a specific user
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Require admin login
requireAdmin('../login.php');

// Get user ID from query parameter
$userId = intval($_GET['user_id'] ?? 0);

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid user ID'
    ]);
    exit();
}

$db = getDB();

try {
    // Get user details
    $user = $db->fetch("
        SELECT 
            id,
            full_name,
            email,
            created_at,
            is_active
        FROM users 
        WHERE id = ? AND role = 'client'
    ", [$userId]);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit();
    }
    
    // Get user's parking sessions
    $sessions = $db->fetchAll("
        SELECT 
            ps.*,
            s.slot_code,
            s.slot_type,
            s.floor,
            TIMESTAMPDIFF(MINUTE, ps.checkin_time, COALESCE(ps.checkout_time, NOW())) as duration_minutes,
            p.payment_method,
            p.paid_at
        FROM parking_sessions ps
        JOIN parking_slots s ON ps.slot_id = s.id
        LEFT JOIN payments p ON ps.id = p.session_id
        WHERE ps.user_id = ?
        ORDER BY ps.checkin_time DESC
        LIMIT 50
    ", [$userId]);
    
    // Calculate statistics
    $stats = [
        'total_sessions' => count($sessions),
        'active_sessions' => count(array_filter($sessions, fn($s) => $s['status'] === 'active')),
        'completed_sessions' => count(array_filter($sessions, fn($s) => $s['status'] === 'completed')),
        'total_spent' => array_sum(array_column(array_filter($sessions, fn($s) => $s['fee_amount']), 'fee_amount')),
        'avg_duration' => 0,
        'total_duration' => array_sum(array_column(array_filter($sessions, fn($s) => $s['duration_minutes']), 'duration_minutes'))
    ];
    
    $completedSessions = array_filter($sessions, fn($s) => $s['status'] === 'completed' && $s['duration_minutes']);
    if (!empty($completedSessions)) {
        $stats['avg_duration'] = array_sum(array_column($completedSessions, 'duration_minutes')) / count($completedSessions);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user' => $user,
            'sessions' => $sessions,
            'stats' => $stats
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
