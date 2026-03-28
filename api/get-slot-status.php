<?php
/**
 * API Endpoint - Get Real-time Slot Status
 * Returns current status of all parking slots for dashboard updates
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check if user is logged in (client or admin)
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit();
}

$db = getDB();

try {
    // Get all slots with current status
    $slots = $db->fetchAll("
        SELECT 
            s.id,
            s.slot_code,
            s.slot_type,
            s.floor,
            s.status as slot_status,
            CASE 
                WHEN ps.status = 'active' THEN 'occupied'
                ELSE s.status
            END as current_status,
            u.full_name as current_user,
            ps.checkin_time as occupied_since,
            TIMESTAMPDIFF(MINUTE, ps.checkin_time, NOW()) as occupied_minutes
        FROM parking_slots s
        LEFT JOIN parking_sessions ps ON s.id = ps.slot_id AND ps.status = 'active'
        LEFT JOIN users u ON ps.user_id = u.id
        ORDER BY s.floor, s.slot_code
    ");
    
    // Calculate statistics
    $totalSlots = count($slots);
    $availableSlots = count(array_filter($slots, fn($s) => $s['current_status'] === 'available'));
    $occupiedSlots = count(array_filter($slots, fn($s) => $s['current_status'] === 'occupied'));
    $maintenanceSlots = count(array_filter($slots, fn($s) => $s['current_status'] === 'maintenance'));
    
    // Group by type for statistics
    $typeStats = [];
    foreach ($slots as $slot) {
        $type = $slot['slot_type'];
        if (!isset($typeStats[$type])) {
            $typeStats[$type] = [
                'total' => 0,
                'available' => 0,
                'occupied' => 0,
                'maintenance' => 0
            ];
        }
        
        $typeStats[$type]['total']++;
        $typeStats[$type][$slot['current_status']]++;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'slots' => $slots,
            'statistics' => [
                'total_slots' => $totalSlots,
                'available_slots' => $availableSlots,
                'occupied_slots' => $occupiedSlots,
                'maintenance_slots' => $maintenanceSlots,
                'occupancy_rate' => $totalSlots > 0 ? round(($occupiedSlots / $totalSlots) * 100, 1) : 0
            ],
            'type_statistics' => $typeStats
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
