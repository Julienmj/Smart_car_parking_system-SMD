<?php
/**
 * Logout Page - Smart Car Parking System
 * Handles user logout and session cleanup
 */

require_once 'includes/auth.php';

// Get current user before logout for message
$currentUser = getCurrentUser();

// Logout user
logoutUser();

// Set success message
if ($currentUser) {
    setFlashMessage('success', 'You have been successfully logged out.');
} else {
    setFlashMessage('info', 'You have been logged out.');
}

// Redirect to login page
header('Location: login.php');
exit();
?>
