<?php
/**
 * Admin Slot Management - Smart Car Parking System
 * Allows administrators to manage parking slots
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
            case 'add':
                addSlot();
                break;
            case 'edit':
                editSlot();
                break;
            case 'delete':
                deleteSlot();
                break;
            case 'toggle_status':
                toggleSlotStatus();
                break;
            case 'force_free':
                forceFreeSLot();
                break;
        }
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
    
    header('Location: manage-slots.php');
    exit();
}

function addSlot() {
    global $db;
    
    $slotCode = sanitizeInput($_POST['slot_code'] ?? '');
    $slotType = sanitizeInput($_POST['slot_type'] ?? '');
    $floor = intval($_POST['floor'] ?? 1);
    
    // Validate input
    if (empty($slotCode) || empty($slotType) || $floor < 1 || $floor > 10) {
        throw new Exception('Please fill all required fields with valid values');
    }
    
    // Check if slot code already exists
    $existing = $db->fetch("SELECT id FROM parking_slots WHERE slot_code = ?", [$slotCode]);
    if ($existing) {
        throw new Exception('Slot code already exists');
    }
    
    // Add new slot
    $result = $db->insert('parking_slots', [
        'slot_code' => strtoupper($slotCode),
        'slot_type' => $slotType,
        'floor' => $floor,
        'status' => 'available'
    ]);
    
    if ($result) {
        setFlashMessage('success', 'Slot added successfully');
    } else {
        throw new Exception('Failed to add slot');
    }
}

function editSlot() {
    global $db;
    
    $slotId = intval($_POST['slot_id'] ?? 0);
    $slotCode = sanitizeInput($_POST['slot_code'] ?? '');
    $slotType = sanitizeInput($_POST['slot_type'] ?? '');
    $floor = intval($_POST['floor'] ?? 1);
    
    // Validate input
    if ($slotId <= 0 || empty($slotCode) || empty($slotType) || $floor < 1 || $floor > 10) {
        throw new Exception('Please fill all required fields with valid values');
    }
    
    // Check if slot code already exists (excluding current slot)
    $existing = $db->fetch("SELECT id FROM parking_slots WHERE slot_code = ? AND id != ?", [$slotCode, $slotId]);
    if ($existing) {
        throw new Exception('Slot code already exists');
    }
    
    // Update slot
    $result = $db->update('parking_slots', [
        'slot_code' => strtoupper($slotCode),
        'slot_type' => $slotType,
        'floor' => $floor
    ], 'id = ?', [$slotId]);
    
    if ($result) {
        setFlashMessage('success', 'Slot updated successfully');
    } else {
        throw new Exception('Failed to update slot');
    }
}

function deleteSlot() {
    global $db;
    
    $slotId = intval($_POST['slot_id'] ?? 0);
    
    if ($slotId <= 0) {
        throw new Exception('Invalid slot ID');
    }
    
    // Check if slot has active sessions
    $activeSession = $db->fetch("SELECT id FROM parking_sessions WHERE slot_id = ? AND status = 'active'", [$slotId]);
    if ($activeSession) {
        throw new Exception('Cannot delete slot with active parking session');
    }
    
    // Delete slot
    $result = $db->delete('parking_slots', 'id = ?', [$slotId]);
    
    if ($result) {
        setFlashMessage('success', 'Slot deleted successfully');
    } else {
        throw new Exception('Failed to delete slot');
    }
}

function toggleSlotStatus() {
    global $db;
    
    $slotId = intval($_POST['slot_id'] ?? 0);
    $newStatus = sanitizeInput($_POST['new_status'] ?? '');
    
    if ($slotId <= 0 || !in_array($newStatus, ['available', 'maintenance'])) {
        throw new Exception('Invalid parameters');
    }
    
    // Check if slot has active sessions when trying to put in maintenance
    if ($newStatus === 'maintenance') {
        $activeSession = $db->fetch("SELECT id FROM parking_sessions WHERE slot_id = ? AND status = 'active'", [$slotId]);
        if ($activeSession) {
            throw new Exception('Cannot put slot in maintenance with active parking session');
        }
    }
    
    // Update slot status
    $result = $db->update('parking_slots', ['status' => $newStatus], 'id = ?', [$slotId]);
    
    if ($result) {
        setFlashMessage('success', 'Slot status updated successfully');
    } else {
        throw new Exception('Failed to update slot status');
    }
}

function forceFreeSLot() {
    global $db;

    $slotId = intval($_POST['slot_id'] ?? 0);
    if ($slotId <= 0) throw new Exception('Invalid slot ID');

    // Get the active session
    $session = $db->fetch("
        SELECT ps.*, s.slot_type
        FROM parking_sessions ps
        JOIN parking_slots s ON ps.slot_id = s.id
        WHERE ps.slot_id = ? AND ps.status = 'active'
    ", [$slotId]);

    if (!$session) throw new Exception('No active session found for this slot');

    // Calculate fee
    $checkin   = new DateTime($session['checkin_time']);
    $checkout  = new DateTime();
    $minutes   = max(0, ($checkout->getTimestamp() - $checkin->getTimestamp()) / 60);
    $freeMinutes = 30;
    $hourlyRate  = $session['slot_type'] === 'VIP' ? 350 : 200;
    $fee = 0;
    if ($minutes > $freeMinutes) {
        $billableHours = ceil(($minutes - $freeMinutes) / 60);
        $fee = $billableHours * $hourlyRate;
    }

    $db->beginTransaction();
    try {
        // Complete the session
        $db->update('parking_sessions', [
            'status'        => 'completed',
            'checkout_time' => $checkout->format('Y-m-d H:i:s'),
            'fee_amount'    => $fee
        ], 'id = ?', [$session['id']]);

        // Record payment as pending (admin forced — cash to be collected)
        if ($fee > 0) {
            $db->insert('payments', [
                'session_id'     => $session['id'],
                'user_id'        => $session['user_id'],
                'amount'         => $fee,
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'paid_at'        => $checkout->format('Y-m-d H:i:s')
            ]);
        }

        $db->commit();
        setFlashMessage('success', 'Slot freed successfully. Fee: ' . number_format($fee, 0) . ' RWF (pending cash collection)');
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}


$slots = [];
$fetchError = null;
try {
    $slots = $db->fetchAll("
        SELECT s.*, 
               CASE WHEN ps.status = 'active' THEN 'occupied' ELSE s.status END as current_status,
               u.full_name as parked_user,
               ps.plate_number,
               ps.checkin_time as occupied_since
        FROM parking_slots s
        LEFT JOIN parking_sessions ps ON s.id = ps.slot_id AND ps.status = 'active'
        LEFT JOIN users u ON ps.user_id = u.id
        ORDER BY s.floor, s.slot_code
    ");
} catch (Exception $e) {
    $fetchError = $e->getMessage();
    error_log("Error fetching slots: " . $e->getMessage());
}

$pageTitle = 'Manage Slots - Smart Parking System';
$adminPage = true;
?>
<?php include '../includes/header.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Manage Parking Slots</h1>
            <button class="btn btn-primary" onclick="showAddSlotModal()">
                <span>➕</span> Add New Slot
            </button>
        </div>

        <!-- Slot Statistics -->
        <?php if ($fetchError): ?>
        <div style="background:rgba(255,71,87,0.1);border:1px solid var(--error-red);border-radius:8px;padding:1rem;margin-bottom:1.5rem;color:var(--error-red)">
            ⚠️ Database error: <?php echo htmlspecialchars($fetchError); ?>
        </div>
        <?php endif; ?>
        <div class="slot-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo count($slots); ?></span>
                <span class="stat-label">Total Slots</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">
                    <?php echo count(array_filter($slots, fn($s) => $s['current_status'] === 'available')); ?>
                </span>
                <span class="stat-label">Available</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">
                    <?php echo count(array_filter($slots, fn($s) => $s['current_status'] === 'occupied')); ?>
                </span>
                <span class="stat-label">Occupied</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">
                    <?php echo count(array_filter($slots, fn($s) => $s['status'] === 'maintenance')); ?>
                </span>
                <span class="stat-label">Maintenance</span>
            </div>
        </div>

        <!-- Slots Table -->
        <div class="slots-table-container data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Slot Code</th>
                        <th>Type</th>
                        <th>Floor</th>
                        <th>Status</th>
                        <th>Current User</th>
                        <th>Occupied Since</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($slots)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No parking slots found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($slots as $slot): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($slot['slot_code']); ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $slot['slot_type']; ?>">
                                        <?php echo ucfirst(htmlspecialchars($slot['slot_type'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($slot['floor']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $slot['current_status']; ?>">
                                        <?php echo ucfirst(htmlspecialchars($slot['current_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($slot['parked_user']): ?>
                                        <?php echo htmlspecialchars($slot['parked_user']); ?>
                                        <?php if (!empty($slot['plate_number'])): ?>
                                            <div style="font-size:0.8rem;color:var(--accent-cyan);letter-spacing:1px;margin-top:2px">
                                                🔵 <?php echo htmlspecialchars($slot['plate_number']); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($slot['occupied_since']): ?>
                                        <?php echo date('M j, Y H:i', strtotime($slot['occupied_since'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <?php if ($slot['current_status'] === 'occupied'): ?>
                                            <form method="POST" style="display:inline" onsubmit="return confirm('Force free slot <?php echo htmlspecialchars($slot['slot_code']); ?>?\n\nThis will end the active session and mark the fee as pending cash collection.')">
                                                <input type="hidden" name="action" value="force_free">
                                                <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                                <button type="submit" class="table-action-btn" style="border-color:#ff9f40;color:#ff9f40">
                                                    🔓 Free Slot
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($slot['current_status'] === 'available' || $slot['current_status'] === 'maintenance'): ?>
                                            <button class="table-action-btn edit" onclick="showEditSlotModal(<?php echo $slot['id']; ?>)">
                                                ✏️ Edit
                                            </button>
                                            
                                            <?php if ($slot['status'] === 'available'): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Put slot in maintenance mode?')">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                                    <input type="hidden" name="new_status" value="maintenance">
                                                    <button type="submit" class="table-action-btn delete">
                                                        🔧 Maintenance
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Make slot available?')">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                                    <input type="hidden" name="new_status" value="available">
                                                    <button type="submit" class="table-action-btn edit">
                                                        ✅ Available
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this slot permanently?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                                <button type="submit" class="table-action-btn delete">
                                                    🗑️ Delete
                                                </button>
                                            </form>
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

<!-- Add Slot Modal -->
<div id="addSlotModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Add New Slot</h2>
            <button class="modal-close" onclick="closeModal('addSlotModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" onsubmit="return validateSlotForm(this)">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="slot_code" class="form-label">Slot Code</label>
                    <input 
                        type="text" 
                        id="slot_code" 
                        name="slot_code" 
                        class="form-input" 
                        placeholder="e.g., A1, B12, VIP1"
                        required
                        maxlength="6"
                        style="text-transform:uppercase"
                    >
                    <div class="form-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="slot_type" class="form-label">Slot Type</label>
                    <select id="slot_type" name="slot_type" class="form-input" required>
                        <option value="">Select Type</option>
                        <option value="standard">Standard</option>
                        <option value="VIP">VIP</option>
                        <option value="disabled">Disabled</option>
                    </select>
                    <div class="form-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="floor" class="form-label">Floor</label>
                    <input 
                        type="number" 
                        id="floor" 
                        name="floor" 
                        class="form-input" 
                        placeholder="1"
                        min="1"
                        max="10"
                        required
                    >
                    <div class="form-error"></div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addSlotModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Slot Modal -->
<div id="editSlotModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Edit Slot</h2>
            <button class="modal-close" onclick="closeModal('editSlotModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" onsubmit="return validateSlotForm(this)">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_slot_id" name="slot_id">
                
                <div class="form-group">
                    <label for="edit_slot_code" class="form-label">Slot Code</label>
                    <input 
                        type="text" 
                        id="edit_slot_code" 
                        name="slot_code" 
                        class="form-input" 
                        placeholder="e.g., A1, B12, VIP1"
                        required
                        maxlength="6"
                        style="text-transform:uppercase"
                    >
                    <div class="form-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="edit_slot_type" class="form-label">Slot Type</label>
                    <select id="edit_slot_type" name="slot_type" class="form-input" required>
                        <option value="">Select Type</option>
                        <option value="standard">Standard</option>
                        <option value="VIP">VIP</option>
                        <option value="disabled">Disabled</option>
                    </select>
                    <div class="form-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="edit_floor" class="form-label">Floor</label>
                    <input 
                        type="number" 
                        id="edit_floor" 
                        name="floor" 
                        class="form-input" 
                        placeholder="1"
                        min="1"
                        max="10"
                        required
                    >
                    <div class="form-error"></div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editSlotModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Slot data for editing
const slotsData = <?php echo json_encode($slots); ?>;

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

function showAddSlotModal() {
    document.getElementById('addSlotModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function showEditSlotModal(slotId) {
    const slot = slotsData.find(s => s.id == slotId);
    if (!slot) return;
    
    // Populate form with slot data
    document.getElementById('edit_slot_id').value = slot.id;
    document.getElementById('edit_slot_code').value = slot.slot_code;
    document.getElementById('edit_slot_type').value = slot.slot_type;
    document.getElementById('edit_floor').value = slot.floor;
    
    // Show modal
    document.getElementById('editSlotModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = '';
}

function validateSlotForm(form) {
    const slotCode = form.querySelector('[name="slot_code"]').value.trim().toUpperCase();
    const slotType = form.querySelector('[name="slot_type"]').value;
    const floor = parseInt(form.querySelector('[name="floor"]').value);
    form.querySelectorAll('.form-error').forEach(e => e.textContent = '');
    let isValid = true;
    if (!slotCode) {
        form.querySelector('[name="slot_code"]').parentNode.querySelector('.form-error').textContent = 'Slot code is required';
        isValid = false;
    } else if (!/^[A-Z0-9]{1,6}$/.test(slotCode)) {
        form.querySelector('[name="slot_code"]').parentNode.querySelector('.form-error').textContent = 'Slot code must be 1-6 alphanumeric characters (e.g., A1, B12)';
        isValid = false;
    }
    if (!slotType) {
        form.querySelector('[name="slot_type"]').parentNode.querySelector('.form-error').textContent = 'Slot type is required';
        isValid = false;
    }
    if (isNaN(floor) || floor < 1 || floor > 10) {
        form.querySelector('[name="floor"]').parentNode.querySelector('.form-error').textContent = 'Floor must be between 1 and 10';
        isValid = false;
    }
    // Auto-uppercase the input before submit
    form.querySelector('[name="slot_code"]').value = slotCode;
    return isValid;
}
</script>

<?php include '../includes/footer.php'; ?>
