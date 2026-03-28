<?php
/**
 * Admin View Sessions - Smart Car Parking System
 * Allows administrators to view all parking sessions
 */

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Require admin login
requireAdmin('../login.php');

// Get current user
$currentUser = getCurrentUser();
$db = getDB();

// Pagination settings
$limit = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Filter settings
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$dateFilter   = sanitizeInput($_GET['date'] ?? '');
$searchFilter = sanitizeInput($_GET['search'] ?? '');

// Build WHERE clause
$whereClause = '';
$params = [];

if ($statusFilter && $statusFilter !== 'all') {
    $whereClause .= ' WHERE ps.status = ?';
    $params[] = $statusFilter;
}
if ($dateFilter) {
    $whereClause .= ($whereClause ? ' AND' : ' WHERE') . ' DATE(ps.checkin_time) = ?';
    $params[] = $dateFilter;
}
if ($searchFilter) {
    $whereClause .= ($whereClause ? ' AND' : ' WHERE') . ' (u.full_name LIKE ? OR u.email LIKE ? OR s.slot_code LIKE ?)';
    $like = '%' . $searchFilter . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}

// Fetch sessions with pagination
try {
    $sessions = $db->fetchAll("
        SELECT ps.*, 
               s.slot_code, s.slot_type, s.floor,
               u.full_name, u.email,
               CASE 
                   WHEN ps.status = 'active' THEN NULL
                   ELSE TIMESTAMPDIFF(MINUTE, ps.checkin_time, ps.checkout_time)
               END as duration_minutes
        FROM parking_sessions ps
        JOIN parking_slots s ON ps.slot_id = s.id
        JOIN users u ON ps.user_id = u.id
        {$whereClause}
        ORDER BY ps.checkin_time DESC
        LIMIT {$limit} OFFSET {$offset}
    ", $params);
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM parking_sessions ps {$whereClause}";
    $totalResult = $db->fetch($countSql, $params);
    $totalSessions = $totalResult['total'];
    
    // Get statistics
    $statsSql = "
        SELECT 
            COUNT(*) as total_sessions,
            SUM(CASE WHEN ps.status = 'active' THEN 1 ELSE 0 END) as active_sessions,
            SUM(CASE WHEN ps.status = 'completed' THEN 1 ELSE 0 END) as completed_sessions,
            COALESCE(SUM(ps.fee_amount), 0) as total_revenue,
            AVG(TIMESTAMPDIFF(MINUTE, ps.checkin_time, ps.checkout_time)) as avg_duration
        FROM parking_sessions ps
        {$whereClause}
    ";
    $stats = $db->fetch($statsSql, $params);
    if (!$stats) $stats = array_fill_keys(['total_sessions','active_sessions','completed_sessions','total_revenue','avg_duration'], 0);
    $stats['avg_duration'] = $stats['avg_duration'] ?? 0;
} catch (Exception $e) {
    error_log("Error fetching sessions: " . $e->getMessage());
    $sessions = [];
    $totalSessions = 0;
    $stats = array_fill_keys(['total_sessions', 'active_sessions', 'completed_sessions', 'total_revenue', 'avg_duration'], 0);
}

$pageTitle = 'View Sessions - Smart Parking System';
$adminPage = true;
?>
<?php include '../includes/header.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Parking Sessions</h1>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="location.reload()">🔄 Refresh</button>
                <button class="btn btn-outline" onclick="exportSessions()">📥 Export CSV</button>
                <button class="btn btn-outline" onclick="window.print()">🖨️ Print</button>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label class="filter-label">Search</label>
                    <input type="text" name="search" class="form-input" placeholder="Name, email or slot..."
                        value="<?php echo htmlspecialchars($searchFilter); ?>">
                </div>
                <div class="filter-group">
                    <label for="status" class="filter-label">Status</label>
                    <select id="status" name="status" class="form-input" onchange="this.form.submit()">
                        <option value="all">All Status</option>
                        <option value="active" <?php echo $statusFilter==='active'?'selected':''; ?>>Active</option>
                        <option value="completed" <?php echo $statusFilter==='completed'?'selected':''; ?>>Completed</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="date" class="filter-label">Date</label>
                    <input type="date" id="date" name="date" class="form-input"
                        value="<?php echo htmlspecialchars($dateFilter); ?>" onchange="this.form.submit()">
                </div>
                <button type="submit" class="btn btn-primary">🔍 Search</button>
                <button type="button" class="btn btn-outline" onclick="clearFilters()">✕ Clear</button>
            </form>
        </div>

        <!-- Session Statistics -->
        <div class="session-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($stats['total_sessions']); ?></span>
                <span class="stat-label">Total Sessions</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($stats['active_sessions']); ?></span>
                <span class="stat-label">Active Sessions</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($stats['completed_sessions']); ?></span>
                <span class="stat-label">Completed Sessions</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($stats['total_revenue'], 0); ?></span>
                <span class="stat-label">Revenue (RWF)</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo round($stats['avg_duration']); ?></span>
                <span class="stat-label">Avg Duration (min)</span>
            </div>
        </div>

        <!-- Sessions Table -->
        <div class="sessions-table-container data-table-container">
            <div class="table-header">
                <h2>Sessions (<?php echo number_format($totalSessions); ?> total)</h2>
                <div class="table-info">
                    Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $totalSessions); ?> of <?php echo number_format($totalSessions); ?>
                </div>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Slot</th>
                        <th>Plate</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Duration</th>
                        <th>Fee</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No sessions found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.9rem;">
                                            <?php echo strtoupper(substr($session['full_name'], 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name" style="font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($session['full_name']); ?>
                                            </div>
                                            <div class="user-email" style="font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($session['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="slot-info">
                                        <div class="slot-code"><?php echo htmlspecialchars($session['slot_code']); ?></div>
                                        <div class="slot-details">
                                            <?php echo ucfirst(htmlspecialchars($session['slot_type'])); ?> • Floor <?php echo $session['floor']; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong style="color:var(--accent-cyan);letter-spacing:1px">
                                        <?php echo htmlspecialchars($session['plate_number'] ?: '—'); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php echo date('M j, Y H:i', strtotime($session['checkin_time'])); ?>
                                </td>
                                <td>
                                    <?php if ($session['status'] === 'active'): ?>
                                        <span class="status-badge active">Active</span>
                                    <?php else: ?>
                                        <?php echo date('M j, Y H:i', strtotime($session['checkout_time'])); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($session['duration_minutes']): ?>
                                        <?php echo formatDuration($session['duration_minutes']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo number_format($session['fee_amount'], 0); ?> RWF
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $session['status']; ?>">
                                        <?php echo ucfirst(htmlspecialchars($session['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalSessions > $limit): ?>
            <?php
            $totalPages = ceil($totalSessions / $limit);
            $currentUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['page' => '']));
            ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?php echo str_replace('page=' . $page, 'page=' . ($page - 1), $currentUrl); ?>" class="pagination-link">
                        ← Previous
                    </a>
                <?php endif; ?>
                
                <div class="pagination-numbers">
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="<?php echo str_replace('page=' . $page, 'page=' . $i, $currentUrl); ?>" 
                           class="pagination-number <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                
                <?php if ($page < $totalPages): ?>
                    <a href="<?php echo str_replace('page=' . $page, 'page=' . ($page + 1), $currentUrl); ?>" class="pagination-link">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Sessions data for export
const sessionsData = <?php echo json_encode($sessions); ?>;

function clearFilters() {
    window.location.href = 'view-sessions.php';
}

function printSessions() {
    window.print();
}
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

function exportSessions() {
    let csv = 'User,Email,Slot,Type,Floor,Check-in,Check-out,Duration,Fee,Status\n';
    
    sessionsData.forEach(session => {
        const duration = session.duration_minutes ? formatDuration(session.duration_minutes) : 'Active';
        const checkout = session.status === 'active' ? 'Active' : session.checkout_time;
        
        csv += `"${session.full_name}","${session.email}","${session.slot_code}","${session.slot_type}","${session.floor}","${session.checkin_time}","${checkout}","${duration}","${session.fee_amount}","${session.status}"\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', 'sessions_export.csv');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

function printSessions() {
    window.print();
}
</script>

<?php 
// Helper function for duration formatting
function formatDuration($minutes) {
    if ($minutes < 60) {
        return "{$minutes} min";
    } elseif ($minutes < 1440) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return "{$hours}h {$mins}m";
    } else {
        $days = floor($minutes / 1440);
        $hours = floor(($minutes % 1440) / 60);
        return "{$days}d {$hours}h";
    }
}
?>

<?php include '../includes/footer.php'; ?>
