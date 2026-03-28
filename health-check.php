<?php
// Health check for Heroku worker
header('Content-Type: application/json');
echo json_encode([
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'app' => 'Smart Parking System',
    'version' => '1.0.0'
]);
?>
