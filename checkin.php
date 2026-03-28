<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

requireClient('login.php');

$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard-client.php');
    exit();
}

$slotId      = intval($_POST['slot_id'] ?? 0);
$plateNumber = strtoupper(trim($_POST['plate_number'] ?? ''));

if ($slotId <= 0) {
    setFlashMessage('error', 'Invalid parking slot selected');
    header('Location: dashboard-client.php');
    exit();
}

if (empty($plateNumber)) {
    setFlashMessage('error', 'Car plate number is required');
    header('Location: dashboard-client.php');
    exit();
}

if (!preg_match('/^[A-Z0-9\s\-]{2,15}$/', $plateNumber)) {
    setFlashMessage('error', 'Invalid plate number format');
    header('Location: dashboard-client.php');
    exit();
}

$db = getDB();

try {
    $db->beginTransaction();

    // Check for existing active session
    $existing = $db->fetch(
        "SELECT id FROM parking_sessions WHERE user_id = ? AND status = 'active'",
        [$currentUser['id']]
    );
    if ($existing) throw new Exception('You already have an active parking session');

    // Check slot is available
    $slot = $db->fetch(
        "SELECT * FROM parking_slots WHERE id = ? AND status = 'available'",
        [$slotId]
    );
    if (!$slot) throw new Exception('Selected slot is not available');

    // Create session with plate number
    $sessionId = $db->insert('parking_sessions', [
        'user_id'      => $currentUser['id'],
        'slot_id'      => $slotId,
        'plate_number' => $plateNumber,
        'checkin_time' => date('Y-m-d H:i:s'),
        'status'       => 'active'
    ]);

    if (!$sessionId) throw new Exception('Failed to create parking session');

    // Mark slot occupied
    $db->update('parking_slots', ['status' => 'occupied'], 'id = ?', [$slotId]);

    $db->commit();

    setFlashMessage('success', 'Checked in to slot ' . htmlspecialchars($slot['slot_code']) . ' — Plate: ' . htmlspecialchars($plateNumber));
    header('Location: dashboard-client.php');
    exit();

} catch (Exception $e) {
    $db->rollback();
    setFlashMessage('error', $e->getMessage());
    header('Location: dashboard-client.php');
    exit();
}
