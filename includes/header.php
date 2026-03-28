<?php
/**
 * Header Include - Smart Car Parking System
 */
require_once __DIR__ . '/auth.php';

// Calculate base path relative to the project root
$scriptPath = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$rootPath = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$rootRelative = str_replace($docRoot, '', $rootPath);
$scriptDir = dirname($scriptPath);
$depth = substr_count(str_replace($rootRelative, '', $scriptDir), '/');
$BASE = str_repeat('../', $depth);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Smart Parking System'; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo $BASE; ?>assets/css/style.css">
    <?php if (isset($adminPage)): ?>
    <link rel="stylesheet" href="<?php echo $BASE; ?>admin/admin-complete.css">
    <?php endif; ?>
    
    <!-- Chart.js for admin dashboard -->
    <?php if (strpos($_SERVER['PHP_SELF'], 'dashboard-admin') !== false): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚗</text></svg>">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="<?php echo $BASE; ?><?php echo isLoggedIn() ? (isAdmin() ? 'dashboard-admin.php' : 'dashboard-client.php') : 'index.html'; ?>" class="brand-link">
                    <span class="brand-icon">🅿️</span>
                    <span class="brand-text">SMART PARKING</span>
                </a>
            </div>
            
            <div class="nav-menu">
                <?php if (isLoggedIn()): ?>
                    <div class="nav-user">
                        <span class="user-greeting">
                            <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                            <?php if (isAdmin()): ?>
                                <span class="role-badge admin">Admin</span>
                            <?php else: ?>
                                <span class="role-badge client">Client</span>
                            <?php endif; ?>
                        </span>
                        <div class="nav-dropdown">
                            <button class="dropdown-toggle">
                                <span class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></span>
                            </button>
                            <div class="dropdown-menu">
                                <?php if (isAdmin()): ?>
                                    <a href="<?php echo $BASE; ?>dashboard-admin.php" class="dropdown-item">Dashboard</a>
                                    <a href="<?php echo $BASE; ?>admin/manage-slots.php" class="dropdown-item">Manage Slots</a>
                                    <a href="<?php echo $BASE; ?>admin/manage-users.php" class="dropdown-item">Manage Users</a>
                                    <a href="<?php echo $BASE; ?>admin/view-sessions.php" class="dropdown-item">View Sessions</a>
                                <?php else: ?>
                                    <a href="<?php echo $BASE; ?>dashboard-client.php" class="dropdown-item">Dashboard</a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo $BASE; ?>logout.php" class="dropdown-item logout">Logout</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="nav-auth">
                        <a href="<?php echo $BASE; ?>login.php" class="btn btn-outline">Login</a>
                        <a href="<?php echo $BASE; ?>register.php" class="btn btn-primary">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php $flashMessages = getAllFlashMessages(); if (!empty($flashMessages)): ?>
    <div class="flash-messages">
        <?php foreach ($flashMessages as $type => $message): ?>
            <div class="flash-message flash-<?php echo $type; ?>" data-auto-dismiss="true">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessages = document.querySelectorAll('.flash-message[data-auto-dismiss="true"]');
            flashMessages.forEach(function(message) {
                setTimeout(function() {
                    message.style.opacity = '0';
                    message.style.transform = 'translateY(-20px)';
                    setTimeout(function() {
                        if (message.parentNode) message.parentNode.removeChild(message);
                    }, 300);
                }, 3000);
            });
        });
    </script>
    <?php endif; ?>
