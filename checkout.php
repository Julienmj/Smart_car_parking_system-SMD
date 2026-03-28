<?php
/**
 * Checkout Page - Smart Car Parking System
 * Handles parking session checkout and fee calculation
 */

require_once 'includes/auth.php';
require_once 'includes/db.php';

// Require client login
requireClient('login.php');

// Get current user
$currentUser = getCurrentUser();

// Get user's active session
$activeSession = null;
$db = getDB();

try {
    $activeSession = $db->fetch(
        "SELECT ps.*, s.slot_code, s.slot_type, s.floor 
         FROM parking_sessions ps 
         JOIN parking_slots s ON ps.slot_id = s.id 
         WHERE ps.user_id = ? AND ps.status = 'active'",
        [$currentUser['id']]
    );
} catch (Exception $e) {
    error_log("Error fetching active session: " . $e->getMessage());
}

// Redirect if no active session
if (!$activeSession) {
    setFlashMessage('error', 'No active parking session found');
    header('Location: dashboard-client.php');
    exit();
}

// Calculate parking duration and fee
$checkinTime = new DateTime($activeSession['checkin_time']);
$checkoutTime = new DateTime(); // Current time
$interval = $checkinTime->diff($checkoutTime);
$totalMinutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;

// Fee calculation logic
$feeAmount = 0;
$hourlyRate = 200; // Standard rate

if ($activeSession['slot_type'] === 'VIP') {
    $hourlyRate = 350; // VIP rate
}

// First 30 minutes free
if ($totalMinutes > 30) {
    $billableMinutes = $totalMinutes - 30;
    $hours = ceil($billableMinutes / 60); // Round up to nearest hour
    $feeAmount = $hours * $hourlyRate;
}

// Handle POST request for checkout confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Update session with checkout time and fee
        $db->update('parking_sessions',
            [
                'checkout_time' => $checkoutTime->format('Y-m-d H:i:s'),
                'fee_amount' => $feeAmount,
                'status' => 'completed'
            ],
            'id = ?',
            [$activeSession['id']]
        );
        
        // Update slot status to available
        $db->update('parking_slots',
            ['status' => 'available'],
            'id = ?',
            [$activeSession['slot_id']]
        );
        
        // Commit transaction
        $db->commit();
        
        // Redirect to payment page
        header("Location: payment.php?session_id={$activeSession['id']}");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $db->rollback();
        
        setFlashMessage('error', 'Checkout failed: ' . $e->getMessage());
        header('Location: dashboard-client.php');
        exit();
    }
}

$pageTitle = 'Checkout - Smart Parking System';
?>
<?php include 'includes/header.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="checkout-container">
            <h1 class="form-title">Parking Checkout</h1>
            <p class="form-subtitle">Review your parking session and proceed to payment</p>
            
            <!-- Session Summary -->
            <div class="session-summary">
                <h2>Session Details</h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-label">Slot Code:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($activeSession['slot_code']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Slot Type:</span>
                        <span class="summary-value">
                            <span class="status-badge <?php echo $activeSession['slot_type'] === 'VIP' ? 'vip' : ($activeSession['slot_type'] === 'disabled' ? 'disabled' : ''); ?>">
                                <?php echo htmlspecialchars($activeSession['slot_type']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Floor:</span>
                        <span class="summary-value">Floor <?php echo $activeSession['floor']; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Check-in Time:</span>
                        <span class="summary-value"><?php echo $checkinTime->format('M j, Y H:i'); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Current Time:</span>
                        <span class="summary-value"><?php echo $checkoutTime->format('M j, Y H:i'); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Duration:</span>
                        <span class="summary-value">
                            <?php 
                            if ($interval->days > 0) {
                                echo $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ', ';
                            }
                            echo $interval->h . ' hour' . ($interval->h != 1 ? 's' : '') . ' ' . $interval->i . ' minute' . ($interval->i != 1 ? 's' : '');
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Fee Calculation -->
            <div class="fee-calculation">
                <h2>Fee Breakdown</h2>
                <div class="fee-details">
                    <div class="fee-row">
                        <span class="fee-label">Parking Duration:</span>
                        <span class="fee-value"><?php echo $totalMinutes; ?> minutes</span>
                    </div>
                    <div class="fee-row">
                        <span class="fee-label">Free Period:</span>
                        <span class="fee-value">30 minutes</span>
                    </div>
                    <div class="fee-row">
                        <span class="fee-label">Billable Duration:</span>
                        <span class="fee-value">
                            <?php 
                            $billableMinutes = max(0, $totalMinutes - 30);
                            echo $billableMinutes . ' minutes';
                            ?>
                        </span>
                    </div>
                    <div class="fee-row">
                        <span class="fee-label">Hourly Rate:</span>
                        <span class="fee-value"><?php echo $hourlyRate; ?> RWF/hour</span>
                    </div>
                    <?php if ($totalMinutes > 30): ?>
                    <div class="fee-row">
                        <span class="fee-label">Billable Hours:</span>
                        <span class="fee-value">
                            <?php 
                            $hours = ceil($billableMinutes / 60);
                            echo $hours . ' hour' . ($hours != 1 ? 's' : '');
                            ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="fee-row total">
                        <span class="fee-label">Total Fee:</span>
                        <span class="fee-value"><?php echo number_format($feeAmount, 0); ?> RWF</span>
                    </div>
                </div>
                
                <?php if ($feeAmount === 0): ?>
                <div class="free-parking-notice">
                    <div class="notice-icon">🎉</div>
                    <div class="notice-text">
                        <strong>Free Parking!</strong><br>
                        Your parking session is within the 30-minute free period.
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Checkout Form -->
            <form method="POST" class="checkout-form">
                <div class="checkout-confirmation">
                    <h3>Confirm Checkout</h3>
                    <p>By confirming checkout, you agree to the calculated parking fee and will be redirected to the payment page.</p>
                    
                    <div class="confirmation-actions">
                        <a href="dashboard-client.php" class="btn btn-outline">
                            <span>←</span> Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <span>💳</span> Proceed to Payment
                            <?php if ($feeAmount === 0): ?>
                            <span class="free-badge">(FREE)</span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.checkout-container {
    max-width: 800px;
    margin: 0 auto;
}

.session-summary,
.fee-calculation {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-xl);
}

.session-summary h2,
.fee-calculation h2 {
    font-family: var(--font-heading);
    font-size: 1.5rem;
    color: var(--accent-cyan);
    margin-bottom: var(--spacing-lg);
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-md);
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: var(--spacing-sm);
    background: var(--secondary-bg);
    border-radius: var(--radius-sm);
}

.summary-label {
    color: var(--text-secondary);
}

.summary-value {
    font-weight: bold;
    color: var(--text-primary);
}

.fee-details {
    space-y: var(--spacing-sm);
}

.fee-row {
    display: flex;
    justify-content: space-between;
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--border-color);
}

.fee-row:last-child {
    border-bottom: none;
}

.fee-row.total {
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-md);
    border-top: 2px solid var(--accent-cyan);
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--accent-cyan);
}

.fee-label {
    color: var(--text-secondary);
}

.fee-value {
    font-weight: bold;
    color: var(--text-primary);
}

.free-parking-notice {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
    padding: var(--spacing-lg);
    background: rgba(0, 255, 136, 0.1);
    border: 1px solid var(--success-green);
    border-radius: var(--radius-md);
}

.notice-icon {
    font-size: 2rem;
}

.notice-text {
    flex: 1;
}

.checkout-form {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
}

.checkout-confirmation h3 {
    font-family: var(--font-heading);
    font-size: 1.3rem;
    color: var(--text-primary);
    margin-bottom: var(--spacing-md);
}

.checkout-confirmation p {
    color: var(--text-secondary);
    margin-bottom: var(--spacing-lg);
}

.confirmation-actions {
    display: flex;
    gap: var(--spacing-md);
    justify-content: space-between;
    flex-wrap: wrap;
}

.free-badge {
    background: var(--success-green);
    color: var(--primary-bg);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-sm);
    font-size: 0.8rem;
    margin-left: var(--spacing-sm);
}

@media (max-width: 768px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }
    
    .confirmation-actions {
        flex-direction: column;
    }
    
    .confirmation-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update current time every minute
    setInterval(updateCurrentTime, 60000);
    
    // Auto-refresh fee calculation every 5 minutes
    setInterval(() => {
        window.location.reload();
    }, 300000);
});

function updateCurrentTime() {
    const currentTimeElement = document.querySelector('.summary-item:nth-child(5) .summary-value');
    if (currentTimeElement) {
        const now = new Date();
        currentTimeElement.textContent = now.toLocaleString('en-RW', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

// Confirm checkout before form submission
document.querySelector('.checkout-form').addEventListener('submit', function(e) {
    const feeAmount = <?php echo $feeAmount; ?>;
    
    if (feeAmount > 0) {
        const confirmMessage = `You will be charged ${feeAmount} RWF for this parking session. Proceed with checkout?`;
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
