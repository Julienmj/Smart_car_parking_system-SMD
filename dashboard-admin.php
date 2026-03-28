<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireAdmin('login.php');

$currentUser = getCurrentUser();
$db = getDB();
$stats = [];

try {
    $stats['total_slots']    = $db->fetch("SELECT COUNT(*) as c FROM parking_slots")['c'];
    $stats['available_slots']= $db->fetch("SELECT COUNT(*) as c FROM parking_slots WHERE status = 'available'")['c'];
    $stats['maintenance_slots']= $db->fetch("SELECT COUNT(*) as c FROM parking_slots WHERE status = 'maintenance'")['c'];
    $stats['active_sessions']= $db->fetch("SELECT COUNT(*) as c FROM parking_sessions WHERE status = 'active'")['c'];
    $stats['occupied_slots'] = $stats['active_sessions'];
    $stats['total_users']    = $db->fetch("SELECT COUNT(*) as c FROM users WHERE role = 'client'")['c'];
    $stats['active_users']   = $db->fetch("SELECT COUNT(*) as c FROM users WHERE role = 'client' AND is_active = 1")['c'];
    $stats['today_sessions'] = $db->fetch("SELECT COUNT(*) as c FROM parking_sessions WHERE DATE(checkin_time) = CURDATE()")['c'];
    $stats['today_revenue']  = $db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE DATE(paid_at) = CURDATE()")['t'];
    $stats['total_revenue']  = $db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM payments")['t'];
    $stats['month_revenue']  = $db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE MONTH(paid_at)=MONTH(CURDATE()) AND YEAR(paid_at)=YEAR(CURDATE())")['t'];
} catch (Exception $e) {
    $stats = array_fill_keys(['total_slots','available_slots','maintenance_slots','active_sessions','occupied_slots','total_users','active_users','today_sessions','today_revenue','total_revenue','month_revenue'], 0);
}

// Recent activity — use checkin_time for ordering (no created_at column)
$recentActivity = [];
try {
    $recentActivity = $db->fetchAll("
        SELECT ps.id, ps.checkin_time, ps.checkout_time, ps.status, ps.fee_amount,
               u.full_name, s.slot_code, s.slot_type
        FROM parking_sessions ps
        JOIN users u ON ps.user_id = u.id
        JOIN parking_slots s ON ps.slot_id = s.id
        ORDER BY ps.checkin_time DESC
        LIMIT 10
    ");
} catch (Exception $e) { error_log($e->getMessage()); }

// Slot distribution for chart
$slotDistribution = [];
try {
    $rawSlots = $db->fetchAll("
        SELECT s.slot_type,
               CASE WHEN ps.status = 'active' THEN 'occupied' ELSE s.status END as effective_status,
               COUNT(*) as count
        FROM parking_slots s
        LEFT JOIN parking_sessions ps ON s.id = ps.slot_id AND ps.status = 'active'
        GROUP BY s.slot_type, effective_status
    ");
    foreach ($rawSlots as $row) {
        $slotDistribution[] = $row;
    }
} catch (Exception $e) { error_log($e->getMessage()); }

// Revenue last 7 days
$revenueChart = [];
try {
    $revenueChart = $db->fetchAll("
        SELECT DATE(paid_at) as date, SUM(amount) as revenue
        FROM payments
        WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(paid_at)
        ORDER BY date
    ");
} catch (Exception $e) { error_log($e->getMessage()); }

// Sessions per day last 7 days
$sessionsChart = [];
try {
    $sessionsChart = $db->fetchAll("
        SELECT DATE(checkin_time) as date, COUNT(*) as count
        FROM parking_sessions
        WHERE checkin_time >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(checkin_time)
        ORDER BY date
    ");
} catch (Exception $e) { error_log($e->getMessage()); }

$pageTitle = 'Admin Dashboard - Smart Parking System';
$adminPage = true;
?>
<?php include 'includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="main-content">
<div class="container">

    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">Admin Dashboard</h1>
            <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($currentUser['full_name']); ?> &mdash; <?php echo date('l, F j, Y'); ?></p>
        </div>
        <div class="dashboard-actions">
            <button class="btn btn-outline" onclick="refreshDashboard(this)">🔄 Refresh</button>
            <a href="admin/manage-slots.php" class="btn btn-outline">🅿️ Slots</a>
            <a href="admin/manage-users.php" class="btn btn-outline">👥 Users</a>
            <a href="admin/view-sessions.php" class="btn btn-primary">📊 Sessions</a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="dashboard-stats">
        <div class="stat-box" id="sb-slots">
            <div class="stat-box-header">
                <span class="stat-box-title">Parking Slots</span>
                <span class="stat-box-icon">🅿️</span>
            </div>
            <div class="stat-box-value" id="stat-total-slots"><?php echo $stats['total_slots']; ?></div>
            <div class="stat-box-change">
                <span style="color:var(--success-green)"><?php echo $stats['available_slots']; ?> free</span> &bull;
                <span style="color:var(--error-red)"><?php echo $stats['occupied_slots']; ?> occupied</span> &bull;
                <span style="color:#ff9f40"><?php echo $stats['maintenance_slots']; ?> maintenance</span>
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-box-header">
                <span class="stat-box-title">Active Sessions</span>
                <span class="stat-box-icon">🚗</span>
            </div>
            <div class="stat-box-value" id="stat-active-sessions"><?php echo $stats['active_sessions']; ?></div>
            <div class="stat-box-change"><?php echo $stats['today_sessions']; ?> sessions today</div>
        </div>

        <div class="stat-box">
            <div class="stat-box-header">
                <span class="stat-box-title">Today's Revenue</span>
                <span class="stat-box-icon">💰</span>
            </div>
            <div class="stat-box-value" id="stat-today-revenue"><?php echo number_format($stats['today_revenue'], 0); ?></div>
            <div class="stat-box-change">RWF &bull; Month: <?php echo number_format($stats['month_revenue'], 0); ?> RWF</div>
        </div>

        <div class="stat-box">
            <div class="stat-box-header">
                <span class="stat-box-title">Total Revenue</span>
                <span class="stat-box-icon">💎</span>
            </div>
            <div class="stat-box-value" id="stat-total-revenue"><?php echo number_format($stats['total_revenue'], 0); ?></div>
            <div class="stat-box-change">RWF all-time</div>
        </div>

        <div class="stat-box">
            <div class="stat-box-header">
                <span class="stat-box-title">Registered Users</span>
                <span class="stat-box-icon">👥</span>
            </div>
            <div class="stat-box-value" id="stat-total-users"><?php echo $stats['total_users']; ?></div>
            <div class="stat-box-change"><?php echo $stats['active_users']; ?> active accounts</div>
        </div>

        <div class="stat-box">
            <div class="stat-box-header">
                <span class="stat-box-title">Occupancy Rate</span>
                <span class="stat-box-icon">📈</span>
            </div>
            <div class="stat-box-value" id="stat-occupancy">
                <?php echo $stats['total_slots'] > 0 ? round(($stats['occupied_slots'] / $stats['total_slots']) * 100) : 0; ?>%
            </div>
            <div class="stat-box-change"><?php echo $stats['available_slots']; ?> slots available now</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-section">
        <div class="chart-container">
            <h2>Slot Occupancy by Type</h2>
            <canvas id="occupancyChart" height="200"></canvas>
        </div>
        <div class="chart-container">
            <h2>Revenue — Last 7 Days (RWF)</h2>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
    </div>

    <div class="charts-section" style="margin-top:0">
        <div class="chart-container">
            <h2>Sessions — Last 7 Days</h2>
            <canvas id="sessionsChart" height="200"></canvas>
        </div>
        <div class="chart-container">
            <h2>Slot Type Distribution</h2>
            <canvas id="typeChart" height="200"></canvas>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h2 class="section-title" style="margin:0">Recent Activity</h2>
            <a href="admin/view-sessions.php" class="btn btn-outline" style="font-size:0.85rem">View All →</a>
        </div>
        <div class="activity-list">
            <?php if (!empty($recentActivity)): ?>
                <?php foreach ($recentActivity as $a): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php echo $a['status'] === 'active' ? '🟢' : '✅'; ?>
                    </div>
                    <div class="activity-details">
                        <div class="activity-title">
                            <?php echo htmlspecialchars($a['full_name']); ?> &mdash;
                            Slot <strong><?php echo htmlspecialchars($a['slot_code']); ?></strong>
                            <span class="status-badge <?php echo ucfirst($a['slot_type']); ?>" style="font-size:0.7rem;margin-left:4px"><?php echo ucfirst($a['slot_type']); ?></span>
                        </div>
                        <div class="activity-meta">
                            Check-in: <?php echo date('M j, Y H:i', strtotime($a['checkin_time'])); ?>
                            <?php if ($a['checkout_time']): ?>
                                &bull; Check-out: <?php echo date('M j, Y H:i', strtotime($a['checkout_time'])); ?>
                                &bull; Fee: <?php echo number_format($a['fee_amount'], 0); ?> RWF
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="activity-status">
                        <span class="status-badge <?php echo $a['status']; ?>">
                            <?php echo ucfirst($a['status']); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-activity"><p>No recent activity found</p></div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

<style>
.dashboard-subtitle { color:var(--text-secondary); margin-top:var(--spacing-sm); }
.charts-section {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(380px,1fr));
    gap:var(--spacing-xl);
    margin:var(--spacing-xl) 0;
}
.chart-container {
    background:var(--card-bg);
    border:1px solid var(--border-color);
    border-radius:var(--radius-lg);
    padding:var(--spacing-xl);
}
.chart-container h2 {
    font-family:var(--font-heading);
    font-size:1.3rem;
    color:var(--accent-cyan);
    margin-bottom:var(--spacing-lg);
    text-align:center;
}
.recent-activity { margin-top:var(--spacing-xl); margin-bottom:var(--spacing-xxl); }
.activity-list {
    background:var(--card-bg);
    border:1px solid var(--border-color);
    border-radius:var(--radius-lg);
    overflow:hidden;
}
.activity-item {
    display:flex;
    align-items:center;
    gap:var(--spacing-md);
    padding:var(--spacing-md) var(--spacing-lg);
    border-bottom:1px solid var(--border-color);
    transition:var(--transition-fast);
}
.activity-item:last-child { border-bottom:none; }
.activity-item:hover { background:var(--secondary-bg); }
.activity-icon { font-size:1.3rem; width:32px; text-align:center; flex-shrink:0; }
.activity-details { flex:1; min-width:0; }
.activity-title { font-weight:600; color:var(--text-primary); margin-bottom:2px; }
.activity-meta { font-size:0.82rem; color:var(--text-secondary); }
.activity-status { flex-shrink:0; }
.no-activity { text-align:center; padding:var(--spacing-xl); color:var(--text-muted); }
@media(max-width:768px){
    .charts-section { grid-template-columns:1fr; }
    .dashboard-actions { flex-wrap:wrap; }
}
</style>

<script>
const slotDist   = <?php echo json_encode($slotDistribution); ?>;
const revData    = <?php echo json_encode($revenueChart); ?>;
const sessData   = <?php echo json_encode($sessionsChart); ?>;
const chartDefaults = {
    color: '#B8C0D0',
    gridColor: '#2D3447'
};

function last7Days() {
    const days = [], labels = [];
    for (let i = 6; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        days.push(d.toISOString().split('T')[0]);
        labels.push(d.toLocaleDateString('en-US', { month:'short', day:'numeric' }));
    }
    return { days, labels };
}

document.addEventListener('DOMContentLoaded', function() {
    buildOccupancyChart();
    buildRevenueChart();
    buildSessionsChart();
    buildTypeChart();
    setInterval(refreshDashboard, 30000);
});

function buildOccupancyChart() {
    const ctx = document.getElementById('occupancyChart');
    if (!ctx) return;
    const types = ['standard','VIP','disabled'];
    const available = types.map(t => {
        const r = slotDist.find(d => d.slot_type === t && d.effective_status === 'available');
        return r ? parseInt(r.count) : 0;
    });
    const occupied = types.map(t => {
        const r = slotDist.find(d => d.slot_type === t && d.effective_status === 'occupied');
        return r ? parseInt(r.count) : 0;
    });
    const maintenance = types.map(t => {
        const r = slotDist.find(d => d.slot_type === t && d.effective_status === 'maintenance');
        return r ? parseInt(r.count) : 0;
    });
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Standard','VIP','Disabled'],
            datasets: [
                { label:'Available', data:available, backgroundColor:'rgba(0,255,136,0.7)', borderColor:'#00ff88', borderWidth:1 },
                { label:'Occupied',  data:occupied,  backgroundColor:'rgba(255,71,87,0.7)',  borderColor:'#ff4757', borderWidth:1 },
                { label:'Maintenance',data:maintenance,backgroundColor:'rgba(255,159,64,0.7)',borderColor:'#ff9f40',borderWidth:1 }
            ]
        },
        options: {
            responsive:true,
            plugins:{ legend:{ labels:{ color:chartDefaults.color } } },
            scales:{
                y:{ beginAtZero:true, ticks:{ color:chartDefaults.color, stepSize:1 }, grid:{ color:chartDefaults.gridColor } },
                x:{ ticks:{ color:chartDefaults.color }, grid:{ color:chartDefaults.gridColor } }
            }
        }
    });
}

function buildRevenueChart() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    const { days, labels } = last7Days();
    const data = days.map(d => {
        const r = revData.find(x => x.date === d);
        return r ? parseFloat(r.revenue) : 0;
    });
    new Chart(ctx, {
        type:'line',
        data:{
            labels,
            datasets:[{
                label:'Revenue (RWF)', data,
                borderColor:'#00D4FF', backgroundColor:'rgba(0,212,255,0.1)',
                borderWidth:2, fill:true, tension:0.4, pointBackgroundColor:'#00D4FF'
            }]
        },
        options:{
            responsive:true,
            plugins:{ legend:{ display:false } },
            scales:{
                y:{ beginAtZero:true, ticks:{ color:chartDefaults.color, callback:v=>v.toLocaleString()+' RWF' }, grid:{ color:chartDefaults.gridColor } },
                x:{ ticks:{ color:chartDefaults.color }, grid:{ color:chartDefaults.gridColor } }
            }
        }
    });
}

function buildSessionsChart() {
    const ctx = document.getElementById('sessionsChart');
    if (!ctx) return;
    const { days, labels } = last7Days();
    const data = days.map(d => {
        const r = sessData.find(x => x.date === d);
        return r ? parseInt(r.count) : 0;
    });
    new Chart(ctx, {
        type:'bar',
        data:{
            labels,
            datasets:[{
                label:'Sessions', data,
                backgroundColor:'rgba(95,159,255,0.7)', borderColor:'#5f9fff', borderWidth:1
            }]
        },
        options:{
            responsive:true,
            plugins:{ legend:{ display:false } },
            scales:{
                y:{ beginAtZero:true, ticks:{ color:chartDefaults.color, stepSize:1 }, grid:{ color:chartDefaults.gridColor } },
                x:{ ticks:{ color:chartDefaults.color }, grid:{ color:chartDefaults.gridColor } }
            }
        }
    });
}

function buildTypeChart() {
    const ctx = document.getElementById('typeChart');
    if (!ctx) return;
    const types = ['standard','VIP','disabled'];
    const totals = types.map(t => slotDist.filter(d => d.slot_type === t).reduce((s,d) => s + parseInt(d.count), 0));
    new Chart(ctx, {
        type:'doughnut',
        data:{
            labels:['Standard','VIP','Disabled'],
            datasets:[{
                data: totals,
                backgroundColor:['rgba(0,212,255,0.8)','rgba(255,215,0,0.8)','rgba(95,159,255,0.8)'],
                borderColor:['#00D4FF','#FFD700','#5F9FFF'],
                borderWidth:2
            }]
        },
        options:{
            responsive:true,
            plugins:{
                legend:{ position:'bottom', labels:{ color:chartDefaults.color, padding:16 } }
            }
        }
    });
}

function refreshDashboard(btn) {
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Refreshing...'; }
    fetch('api/get-dashboard-stats.php')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const d = res.data;
            document.getElementById('stat-total-slots').textContent    = d.total_slots;
            document.getElementById('stat-active-sessions').textContent = d.active_sessions;
            document.getElementById('stat-today-revenue').textContent  = parseInt(d.today_revenue).toLocaleString();
            document.getElementById('stat-total-revenue').textContent  = parseInt(d.total_revenue).toLocaleString();
            document.getElementById('stat-total-users').textContent    = d.total_users;
            const occ = d.total_slots > 0 ? Math.round((d.active_sessions / d.total_slots) * 100) : 0;
            document.getElementById('stat-occupancy').textContent      = occ + '%';
            if (window.SmartParking) SmartParking.showToast('Dashboard refreshed', 'success');
        })
        .catch(() => { if (window.SmartParking) SmartParking.showToast('Refresh failed', 'error'); })
        .finally(() => {
            if (btn) { btn.disabled = false; btn.textContent = '🔄 Refresh'; }
        });
}
</script>

<?php include 'includes/footer.php'; ?>
