<?php
/**
 * Direct Admin Access - Bypass Login for Testing
 */

require_once 'includes/db.php';

echo "<h1>Direct Admin Access</h1>";

try {
    $db = getDB();
    
    // Create or get admin user
    $admin = $db->fetch("SELECT * FROM users WHERE email = 'admin@parking.com'");
    
    if (!$admin) {
        echo "Creating admin user...<br>";
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $adminId = $db->insert('users', [
            'full_name' => 'System Administrator',
            'email' => 'admin@parking.com',
            'password' => $hash,
            'role' => 'admin',
            'is_active' => 1
        ]);
        
        $admin = $db->fetch("SELECT * FROM users WHERE id = ?", [$adminId]);
    }
    
    if ($admin) {
        // Start session and login admin directly
        session_start();
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = $admin['full_name'];
        $_SESSION['user_email'] = $admin['email'];
        $_SESSION['user_role'] = $admin['role'];
        $_SESSION['logged_in'] = true;
        
        echo "<div style='background:#4CAF50;color:white;padding:15px;border-radius:5px;'>";
        echo "✅ Admin session created successfully!<br>";
        echo "User: " . $admin['full_name'] . " (" . $admin['email'] . ")<br>";
        echo "Role: " . $admin['role'];
        echo "</div>";
        
        echo "<br><h3>Go to Admin Dashboard:</h3>";
        echo '<a href="dashboard-admin.php" style="background:#00D4FF;color:#000;padding:15px 30px;text-decoration:none;border-radius:5px;font-size:16px;">🚀 Admin Dashboard</a>';
        
        echo "<br><br><h3>Or go to Client Dashboard:</h3>";
        echo '<a href="dashboard-client.php" style="background:#4CAF50;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-size:16px;">👤 Client Dashboard</a>';
        
        echo "<br><br><h3>Test Login Page:</h3>";
        echo '<a href="login.php" style="background:#2196F3;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-size:16px;">🔐 Login Page</a>';
        
    } else {
        echo "<div style='background:#f44336;color:white;padding:15px;border-radius:5px;'>";
        echo "❌ Failed to create admin user";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background:#f44336;color:white;padding:15px;border-radius:5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
?>
