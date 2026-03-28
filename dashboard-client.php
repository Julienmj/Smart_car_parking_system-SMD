<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

requireClient('login.php');

$currentUser = getCurrentUser();
$db = getDB();

$activeSession = null;
try {
    $activeSession = $db->fetch(
        "SELECT ps.*, s.slot_code, s.slot_type, s.floor
         FROM parking_sessions ps
         JOIN parking_slots s ON ps.slot_id = s.id
         WHERE ps.user_id = ? AND ps.status = 'active'",
        [$currentUser['id']]
    );
} catch (Exception $e) { error_log($e->getMessage()); }

$allSlots = [];
try {
    $allSlots = $db->fetchAll(
        "SELECT s.*,
         CASE WHEN ps.status = 'active' THEN 'occupied' ELSE s.status END as current_status,
         CASE WHEN ps.user_id = ? THEN 1 ELSE 0 END as is_user_slot
         FROM parking_slots s
         LEFT JOIN parking_sessions ps ON s.id = ps.slot_id AND ps.status = 'active'
         ORDER BY s.floor, s.slot_code",
        [$currentUser['id']]
    );
} catch (Exception $e) { error_log($e->getMessage()); }

$totalSessions = 0;
$totalFees = 0;
try {
    $hist = $db->fetch(
        "SELECT COUNT(*) as cnt, COALESCE(SUM(fee_amount),0) as total FROM parking_sessions WHERE user_id = ?",
        [$currentUser['id']]
    );
    $totalSessions = $hist['cnt'];
    $totalFees     = $hist['total'];
} catch (Exception $e) {}

$availableCount = count(array_filter($allSlots, fn($s) => $s['current_status'] === 'available'));

$pageTitle = 'Client Dashboard - Smart Parking System';
?>
<?php include 'includes/header.php'; ?>

<div class="main-content">
<div class="container">

    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">Parking Dashboard</h1>
            <p class="dashboard-subtitle">Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?></p>
        </div>
        <div class="dashboard-actions">
            <button class="btn btn-primary" id="refreshBtn" onclick="refreshSlots()">🔄 Refresh</button>
        </div>
    </div>

    <?php if ($activeSession): ?>
    <div class="session-info">
        <div class="session-header">
            <h2 class="session-title">🟢 Active Parking Session</h2>
            <div class="session-timer" data-checkin-time="<?php echo $activeSession['checkin_time']; ?>">00:00:00</div>
        </div>
        <div class="session-details">
            <div class="session-detail">
                <span class="session-detail-label">Slot</span>
                <span class="session-detail-value"><?php echo htmlspecialchars($activeSession['slot_code']); ?></span>
            </div>
            <div class="session-detail">
                <span class="session-detail-label">Type</span>
                <span class="session-detail-value"><?php echo ucfirst($activeSession['slot_type']); ?></span>
            </div>
            <div class="session-detail">
                <span class="session-detail-label">Floor</span>
                <span class="session-detail-value"><?php echo $activeSession['floor']; ?></span>
            </div>
            <div class="session-detail">
                <span class="session-detail-label">Check-in</span>
                <span class="session-detail-value"><?php echo date('M j, Y H:i', strtotime($activeSession['checkin_time'])); ?></span>
            </div>
            <div class="session-detail">
                <span class="session-detail-label">Plate</span>
                <span class="session-detail-value" style="color:var(--accent-cyan);font-weight:700">
                    <?php echo htmlspecialchars($activeSession['plate_number'] ?: '—'); ?>
                </span>
            </div>
        </div>
        <div class="session-actions">
            <a href="checkout.php" class="btn btn-danger">🚗 Check Out</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Parking Map -->
    <div class="parking-map">
        <div class="parking-map-header">
            <h2 class="parking-map-title">Parking Slots</h2>
            <div class="parking-legend">
                <div class="legend-item"><div class="legend-color available"></div><span>Available</span></div>
                <div class="legend-item"><div class="legend-color occupied"></div><span>Occupied</span></div>
                <?php if ($activeSession): ?>
                <div class="legend-item"><div class="legend-color user"></div><span>Your Slot</span></div>
                <?php endif; ?>
                <div class="legend-item"><div class="legend-color vip"></div><span>VIP</span></div>
                <div class="legend-item"><div class="legend-color disabled"></div><span>Disabled</span></div>
            </div>
        </div>

        <div class="parking-grid">
            <?php foreach ($allSlots as $slot): ?>
                <?php
                if ($slot['is_user_slot'])                        $cls = 'user-slot';
                elseif ($slot['current_status'] === 'available')  $cls = 'available';
                else                                               $cls = 'occupied';
                $typeClass = $slot['slot_type'] === 'VIP' ? 'vip' : ($slot['slot_type'] === 'disabled' ? 'disabled' : '');
                $clickable = ($slot['current_status'] === 'available');
                ?>
                <div class="parking-slot <?php echo $cls . ' ' . $typeClass; ?>"
                     data-slot-id="<?php echo $slot['id']; ?>"
                     data-available="<?php echo $clickable ? '1' : '0'; ?>"
                     style="<?php echo !$clickable ? 'cursor:not-allowed' : 'cursor:pointer'; ?>">
                    <div class="slot-code"><?php echo htmlspecialchars($slot['slot_code']); ?></div>
                    <div class="slot-type"><?php echo htmlspecialchars($slot['slot_type']); ?></div>
                    <div class="slot-floor">F<?php echo $slot['floor']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="dashboard-stats">
        <div class="stat-box">
            <div class="stat-box-header"><span class="stat-box-title">Total Sessions</span><span class="stat-box-icon">🅿️</span></div>
            <div class="stat-box-value"><?php echo $totalSessions; ?></div>
            <div class="stat-box-change">All-time parking sessions</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-header"><span class="stat-box-title">Total Fees Paid</span><span class="stat-box-icon">💰</span></div>
            <div class="stat-box-value"><?php echo number_format($totalFees, 0); ?> RWF</div>
            <div class="stat-box-change">Total amount spent</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-header"><span class="stat-box-title">Available Slots</span><span class="stat-box-icon">✅</span></div>
            <div class="stat-box-value"><?php echo $availableCount; ?></div>
            <div class="stat-box-change">Ready for parking now</div>
        </div>
    </div>

</div>
</div>

<!-- Check-in Modal -->
<div id="checkinModal" class="modal">
    <div class="modal-content" style="max-width:420px">
        <div class="modal-header">
            <h2 class="modal-title">🚗 Check In</h2>
            <button class="modal-close" id="closeModalBtn">&times;</button>
        </div>
        <div class="modal-body">
            <div style="background:var(--secondary-bg);border:1px solid var(--border-color);border-radius:8px;padding:1rem;margin-bottom:1.5rem;text-align:center">
                <div style="font-size:2rem;font-weight:700;color:var(--accent-cyan)" id="checkin-slot-code"></div>
                <div style="color:var(--text-secondary);font-size:0.9rem" id="checkin-slot-meta"></div>
            </div>
            <form id="checkinForm" method="POST" action="checkin.php">
                <input type="hidden" name="slot_id" id="checkin-slot-id">
                <div class="form-group">
                    <label class="form-label">Car Plate Number</label>
                    <input type="text" name="plate_number" id="plate_number" class="form-input"
                        placeholder="e.g. RAB 123 A"
                        maxlength="15"
                        autocomplete="off"
                        style="text-transform:uppercase;font-size:1.2rem;letter-spacing:2px;text-align:center"
                        required>
                    <div class="form-error" id="plate-error"></div>
                    <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px">Enter your vehicle registration plate</div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" id="cancelCheckinBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">🔑 Confirm Check-in</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.dashboard-subtitle { color:var(--text-secondary); margin-top:var(--spacing-sm); }
.dashboard-actions  { display:flex; gap:var(--spacing-sm); }
@media(max-width:768px){
    .dashboard-header { flex-direction:column; align-items:flex-start; gap:var(--spacing-md); }
    .parking-legend   { flex-wrap:wrap; gap:var(--spacing-xs); }
}
</style>

<script>
(function() {
    var hasActive = <?php echo $activeSession ? 'true' : 'false'; ?>;

    // ── Modal helpers ──────────────────────────────────────────
    function openModal(slotEl) {
        var slotId    = slotEl.dataset.slotId;
        var slotCode  = slotEl.querySelector('.slot-code').textContent;
        var slotType  = slotEl.querySelector('.slot-type').textContent;
        var slotFloor = slotEl.querySelector('.slot-floor').textContent;

        document.getElementById('checkin-slot-id').value          = slotId;
        document.getElementById('checkin-slot-code').textContent   = slotCode;
        document.getElementById('checkin-slot-meta').textContent   = slotType + ' • ' + slotFloor;
        document.getElementById('plate_number').value              = '';
        document.getElementById('plate-error').textContent         = '';

        document.getElementById('checkinModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(function(){ document.getElementById('plate_number').focus(); }, 150);
    }

    function closeModal() {
        document.getElementById('checkinModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    // ── Slot click handler ─────────────────────────────────────
    document.querySelectorAll('.parking-slot[data-available="1"]').forEach(function(slot) {
        slot.addEventListener('click', function() {
            if (hasActive) {
                if (window.SmartParking) SmartParking.showToast('You already have an active session!', 'error');
                return;
            }
            openModal(this);
        });
    });

    // ── Form submit validation ─────────────────────────────────
    document.getElementById('checkinForm').addEventListener('submit', function(e) {
        var plate = document.getElementById('plate_number').value.trim().toUpperCase();
        var err   = document.getElementById('plate-error');
        err.textContent = '';

        if (!plate) {
            e.preventDefault();
            err.textContent = 'Plate number is required';
            return;
        }
        if (!/^[A-Z0-9\s\-]{2,15}$/.test(plate)) {
            e.preventDefault();
            err.textContent = 'Invalid format — letters, numbers, spaces or hyphens only';
            return;
        }
        document.getElementById('plate_number').value = plate;
        // form submits normally to checkin.php
    });

    // ── Close modal triggers ───────────────────────────────────
    document.getElementById('closeModalBtn').addEventListener('click', closeModal);
    document.getElementById('cancelCheckinBtn').addEventListener('click', closeModal);
    document.getElementById('checkinModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });

    // ── Auto-uppercase plate ───────────────────────────────────
    document.getElementById('plate_number').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    // ── Session timer ──────────────────────────────────────────
    <?php if ($activeSession): ?>
    (function() {
        var checkinTime = new Date('<?php echo $activeSession['checkin_time']; ?>');
        var timerEl = document.querySelector('.session-timer');
        function tick() {
            var diff = Math.floor((new Date() - checkinTime) / 1000);
            var h = Math.floor(diff / 3600);
            var m = Math.floor((diff % 3600) / 60);
            var s = diff % 60;
            timerEl.textContent =
                String(h).padStart(2,'0') + ':' +
                String(m).padStart(2,'0') + ':' +
                String(s).padStart(2,'0');
        }
        tick();
        setInterval(tick, 1000);
    })();
    <?php endif; ?>

    // ── Refresh slots ──────────────────────────────────────────
    window.refreshSlots = function() {
        var btn = document.getElementById('refreshBtn');
        btn.disabled = true;
        btn.textContent = '⏳ Refreshing...';
        fetch('api/get-slot-status.php')
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (data.success && data.data && data.data.slots) {
                    data.data.slots.forEach(function(s) {
                        var el = document.querySelector('[data-slot-id="' + s.id + '"]');
                        if (!el) return;
                        el.classList.remove('available','occupied','user-slot');
                        el.classList.add(s.current_status === 'occupied' ? 'occupied' : s.current_status);
                        el.dataset.available = (s.current_status === 'available') ? '1' : '0';
                        el.style.cursor = (s.current_status === 'available') ? 'pointer' : 'not-allowed';
                    });
                }
                if (window.SmartParking) SmartParking.showToast('Slots refreshed!', 'success');
            })
            .catch(function(){ if (window.SmartParking) SmartParking.showToast('Refresh failed', 'error'); })
            .finally(function(){ btn.disabled = false; btn.textContent = '🔄 Refresh'; });
    };

})();
</script>

<?php include 'includes/footer.php'; ?>
