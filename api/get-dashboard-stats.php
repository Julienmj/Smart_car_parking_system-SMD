<?php
/**
 * API Endpoint - Get Dashboard Statistics
 * Returns real-time dashboard statistics for admin
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Require admin login
requireAdmin('../login.php');

$db = getDB();
$stats = [];

try {
    // Total slots
    $stats['total_slots'] = $db->fetch("SELECT COUNT(*) as count FROM parking_slots")['count'];
    
    // Active sessions (vehicles currently parked)
    $stats['active_sessions'] = $db->fetch("SELECT COUNT(*) as count FROM parking_sessions WHERE status = 'active'")['count'];
    
    // Available slots
    $stats['available_slots'] = $db->fetch("SELECT COUNT(*) as count FROM parking_slots WHERE status = 'available'")['count'];
    
    // Occupied slots (from active sessions)
    $stats['occupied_slots'] = $stats['active_sessions'];
    
    // Total users
    $stats['total_users'] = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'client'")['count'];
    
    // Active sessions
    $stats['active_sessions'] = $db->fetch("SELECT COUNT(*) as count FROM parking_sessions WHERE status = 'active'")['count'];
    
    // Today's revenue
    $stats['today_revenue'] = $db->fetch("
        SELECT COALESCE(SUM(p.amount), 0) as total 
        FROM payments p 
        WHERE DATE(p.paid_at) = CURDATE()
    ")['total'];
    
    // Total revenue
    $stats['total_revenue'] = $db->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM payments")['total'];
    
    // Today's sessions
    $stats['today_sessions'] = $db->fetch("
        SELECT COUNT(*) as count 
        FROM parking_sessions 
        WHERE DATE(checkin_time) = CURDATE()
    ")['count'];
    
    // Slot distribution
    $stats['slot_distribution'] = $db->fetchAll("
        SELECT 
            slot_type,
            status,
            COUNT(*) as count
        FROM parking_slots
        GROUP BY slot_type, status
        ORDER BY slot_type, status
    ");
    
    // Revenue data for last 7 days
    $stats['revenue_chart'] = $db->fetchAll("
        SELECT 
            DATE(p.paid_at) as date,
            SUM(p.amount) as revenue
        FROM payments p
        WHERE p.paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(p.paid_at)
        ORDER BY date
    ");
    
    // Recent activity
    $stats['recent_activity'] = $db->fetchAll("
        SELECT 
            ps.id, ps.checkin_time, ps.checkout_time, ps.status, ps.fee_amount,
            u.full_name, s.slot_code, s.slot_type
        FROM parking_sessions ps
        JOIN users u ON ps.user_id = u.id
        JOIN parking_slots s ON ps.slot_id = s.id
        ORDER BY ps.checkin_time DESC
        LIMIT 5
    ");
    
    echo json_encode([
        'success' => true,
        'data' => $stats,
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
