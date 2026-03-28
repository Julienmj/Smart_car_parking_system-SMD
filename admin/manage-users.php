<?php
/**
 * Admin User Management - Smart Car Parking System
 * Allows administrators to manage users
 */

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Require admin login
requireAdmin('../login.php');

// Get current user
$currentUser = getCurrentUser();
$db = getDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    
    try {
        switch ($action) {
            case 'toggle_status':
                toggleUserStatus();
                break;
        }
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
    
    header('Location: manage-users.php');
    exit();
}

function toggleUserStatus() {
    global $db;
    
    $userId = intval($_POST['user_id'] ?? 0);
    $newStatus = sanitizeInput($_POST['new_status'] ?? '');
    
    if ($userId <= 0 || !in_array($newStatus, ['active', 'inactive'])) {
        throw new Exception('Invalid parameters');
    }
    
    // Prevent admin from deactivating themselves
    if ($userId === $_SESSION['user_id']) {
        throw new Exception('You cannot deactivate your own account');
    }
    
    // Update user status
    $result = $db->update('users', ['is_active' => ($newStatus === 'active')], 'id = ?', [$userId]);
    
    if ($result) {
        setFlashMessage('success', 'User status updated successfully');
    } else {
        throw new Exception('Failed to update user status');
    }
}

// Fetch all users with statistics
try {
    $users = $db->fetchAll("
        SELECT u.*, 
               COUNT(ps.id) as total_sessions,
               SUM(ps.fee_amount) as total_spent,
               MAX(ps.checkin_time) as last_activity,
               CASE WHEN EXISTS(
                   SELECT 1 FROM parking_sessions 
                   WHERE user_id = u.id AND status = 'active'
               ) THEN 1 ELSE 0 END as has_active_session
        FROM users u
        LEFT JOIN parking_sessions ps ON u.id = ps.user_id
        WHERE u.role = 'client'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
}

$pageTitle = 'Manage Users - Smart Parking System';
$adminPage = true;
?>
<?php include '../includes/header.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Manage Users</h1>
            <div class="header-actions">
                <div class="search-box">
                    <input 
                        type="text" 
                        id="userSearch" 
                        class="form-input" 
                        placeholder="Search users by name or email..."
                        data-target=".users-table-container tbody tr"
                        onkeyup="performSearch(this)"
                    >
                </div>
                <button class="btn btn-outline" onclick="exportUsers()">
                    <span>📥</span> Export CSV
                </button>
            </div>
        </div>

        <!-- User Statistics -->
        <div class="user-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo count($users); ?></span>
                <span class="stat-label">Total Users</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">
                    <?php echo count(array_filter($users, fn($u) => $u['is_active'])); ?>
                </span>
                <span class="stat-label">Active Users</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">
                    <?php echo count(array_filter($users, fn($u) => $u['has_active_session'])); ?>
                </span>
                <span class="stat-label">Currently Parking</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">
                    <?php echo number_format(array_sum(array_column($users, 'total_spent')), 0); ?>
                </span>
                <span class="stat-label">Total Revenue (RWF)</span>
            </div>
        </div>

        <!-- Users Table -->
        <div class="users-table-container data-table-container">
            <table class="data-table" id="usersTable">
                <thead>
                    <tr>
                        <th>User Info</th>
                        <th>Registration Date</th>
                        <th>Total Sessions</th>
                        <th>Total Spent</th>
                        <th>Last Activity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No users found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name">
                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                            </div>
                                            <div class="user-email">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </div>
                                            <?php if ($user['has_active_session']): ?>
                                                <div class="active-indicator">🚗 Currently Parking</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td>
                                    <?php echo number_format($user['total_sessions']); ?>
                                </td>
                                <td>
                                    <?php echo number_format($user['total_spent'], 0); ?> RWF
                                </td>
                                <td>
                                    <?php if ($user['last_activity']): ?>
                                        <?php echo date('M j, Y H:i', strtotime($user['last_activity'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="table-action-btn edit" onclick="showUserHistory(<?php echo $user['id']; ?>)">
                                            📊 History
                                        </button>
                                        
                                        <?php if ($user['id'] != $currentUser['id']): ?>
                                            <?php if ($user['is_active']): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Deactivate this user? They will not be able to login.')">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="new_status" value="inactive">
                                                    <button type="submit" class="table-action-btn delete">
                                                        🔒 Deactivate
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Activate this user?')">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="new_status" value="active">
                                                    <button type="submit" class="table-action-btn edit">
                                                        ✅ Activate
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User History Modal -->
<div id="userHistoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">User Parking History</h2>
            <button class="modal-close" onclick="closeModal('userHistoryModal')">&times;</button>
        </div>
        <div class="modal-body" id="userHistoryContent">
            <div class="loading">Loading user history...</div>
        </div>
    </div>
</div>

<script>
// User data for history
const usersData = <?php echo json_encode($users); ?>;

// Initialize modal event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Close modals when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Close modals with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
});

function showUserHistory(userId) {
    const modal = document.getElementById('userHistoryModal');
    const content = document.getElementById('userHistoryContent');
    content.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-secondary)">⏳ Loading...</div>';
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    fetch(`../api/get-user-history.php?user_id=${userId}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) { content.innerHTML = `<div class="error">${res.error}</div>`; return; }
            displayUserHistory(res.data.user, res.data.sessions, res.data.stats);
        })
        .catch(() => { content.innerHTML = '<div class="error">Failed to load history</div>'; });
}

function displayUserHistory(user, sessions, stats) {
    const content = document.getElementById('userHistoryContent');
    let html = `
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem">
            <div style="background:var(--secondary-bg);border:1px solid var(--border-color);border-radius:8px;padding:1rem;text-align:center">
                <div style="font-size:1.5rem;color:var(--accent-cyan);font-weight:700">${stats.total_sessions}</div>
                <div style="font-size:0.8rem;color:var(--text-secondary)">Total Sessions</div>
            </div>
            <div style="background:var(--secondary-bg);border:1px solid var(--border-color);border-radius:8px;padding:1rem;text-align:center">
                <div style="font-size:1.5rem;color:var(--success-green);font-weight:700">${stats.active_sessions}</div>
                <div style="font-size:0.8rem;color:var(--text-secondary)">Active Now</div>
            </div>
            <div style="background:var(--secondary-bg);border:1px solid var(--border-color);border-radius:8px;padding:1rem;text-align:center">
                <div style="font-size:1.5rem;color:var(--accent-cyan);font-weight:700">${number_format(stats.total_spent,0)}</div>
                <div style="font-size:0.8rem;color:var(--text-secondary)">Total Spent (RWF)</div>
            </div>
            <div style="background:var(--secondary-bg);border:1px solid var(--border-color);border-radius:8px;padding:1rem;text-align:center">
                <div style="font-size:1.5rem;color:var(--accent-cyan);font-weight:700">${Math.round(stats.avg_duration)}</div>
                <div style="font-size:0.8rem;color:var(--text-secondary)">Avg Duration (min)</div>
            </div>
        </div>
        <p style="color:var(--text-secondary);margin-bottom:1rem">📧 ${user.email} &bull; Joined ${new Date(user.created_at).toLocaleDateString()}</p>
    `;
    if (!sessions.length) {
        html += '<p class="text-muted" style="text-align:center;padding:1rem">No parking sessions found</p>';
    } else {
        html += `<div style="overflow-x:auto"><table class="data-table"><thead><tr>
            <th>Slot</th><th>Type</th><th>Check-in</th><th>Check-out</th><th>Duration</th><th>Fee</th><th>Status</th>
        </tr></thead><tbody>`;
        sessions.forEach(s => {
            const dur = s.duration_minutes ? formatDuration(parseInt(s.duration_minutes)) : '-';
            const checkout = s.checkout_time ? new Date(s.checkout_time).toLocaleString() : '<span style="color:var(--success-green)">Active</span>';
            html += `<tr>
                <td><strong>${s.slot_code}</strong></td>
                <td><span class="status-badge ${s.slot_type}">${s.slot_type}</span></td>
                <td>${new Date(s.checkin_time).toLocaleString()}</td>
                <td>${checkout}</td>
                <td>${dur}</td>
                <td>${number_format(parseFloat(s.fee_amount)||0, 0)} RWF</td>
                <td><span class="status-badge ${s.status}">${s.status}</span></td>
            </tr>`;
        });
        html += '</tbody></table></div>';
    }
    content.innerHTML = html;
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = '';
}

function formatDuration(minutes) {
    if (minutes < 60) {
        return `${minutes} min`;
    } else if (minutes < 1440) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours}h ${mins}m`;
    } else {
        const days = Math.floor(minutes / 1440);
        const hours = Math.floor((minutes % 1440) / 60);
        return `${days}d ${hours}h`;
    }
}

function number_format(number, decimals) {
    return number.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function exportUsers() {
    // Simple CSV export
    let csv = 'Name,Email,Registration Date,Total Sessions,Total Spent,Status\n';
    
    usersData.forEach(user => {
        csv += `"${user.full_name}","${user.email}","${user.created_at}",${user.total_sessions},${user.total_spent},"${user.is_active ? 'Active' : 'Inactive'}"\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', 'users_export.csv');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
</script>

<?php include '../includes/footer.php'; ?>
