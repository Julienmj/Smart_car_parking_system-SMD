<?php
// Generate correct password hash
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hash: $hash\n";
echo "Valid: " . (password_verify($password, $hash) ? "YES" : "NO") . "\n";

// Create the SQL update statement
echo "\nSQL Update:\n";
echo "UPDATE users SET password = '$hash' WHERE email = 'admin@parking.com';\n";
?>
