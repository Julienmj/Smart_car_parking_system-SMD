<?php
/**
 * Clear all flash messages
 */

require_once 'includes/auth.php';

// Clear all flash messages
unset($_SESSION['flash']);

// Also clear any other session data that might cause issues
unset($_SESSION['redirect_after_login']);

echo "<h1>Flash Messages Cleared</h1>";
echo "<p>All flash messages have been cleared from your session.</p>";
echo "<p><a href='dashboard-client.php'>Go to Dashboard</a></p>";
echo "<p><a href='login.php'>Go to Login</a></p>";
?>
