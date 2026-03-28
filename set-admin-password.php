<?php
/**
 * Set Working Admin Password
 */

require_once 'includes/db.php';

// Generate working password hash for "admin123"
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h1>Set Working Admin Password</h1>";
echo "Password: $password<br>";
echo "Hash: $hash<br>";
echo "Valid: " . (password_verify($password, $hash) ? "YES" : "NO") . "<br><br>";

try {
    $db = getDB();
    
    // Delete existing admin if any
    $db->delete('users', 'email = ?', ['admin@parking.com']);
    
    // Insert new admin with working hash
    $adminData = [
        'full_name' => 'System Administrator',
        'email' => 'admin@parking.com',
        'password' => $hash,
        'role' => 'admin',
        'is_active' => 1
    ];
    
    $adminId = $db->insert('users', $adminData);
    
    if ($adminId) {
        echo "<div style='background:#4CAF50;color:white;padding:15px;border-radius:5px;'>";
        echo "✅ Admin user created successfully!<br>";
        echo "ID: $adminId<br>";
        echo "Email: admin@parking.com<br>";
        echo "Password: admin123<br>";
        echo "</div>";
        
        // Test login
        $testAdmin = $db->fetch("SELECT * FROM users WHERE email = 'admin@parking.com'");
        if ($testAdmin && password_verify('admin123', $testAdmin['password'])) {
            echo "<br>✅ Login test PASSED!";
        } else {
            echo "<br>❌ Login test FAILED!";
        }
        
        echo "<br><h3>Ready to Login:</h3>";
        echo '<a href="login.php" style="background:#00D4FF;color:#000;padding:15px 30px;text-decoration:none;border-radius:5px;font-size:16px;">🔐 Go to Login</a>';
        
        echo "<br><br><h3>Or Direct Access:</h3>";
        echo '<a href="admin-direct.php" style="background:#4CAF50;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-size:16px;">🚀 Direct Admin Access</a>';
        
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
