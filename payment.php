<?php
/**
 * Payment Page - Smart Car Parking System
 * Simulates payment processing for parking fees
 */

require_once 'includes/auth.php';
require_once 'includes/db.php';

// Require client login
requireClient('login.php');

// Get current user
$currentUser = getCurrentUser();

// Get session ID from URL
$sessionId = intval($_GET['session_id'] ?? 0);

if ($sessionId <= 0) {
    setFlashMessage('error', 'Invalid session ID');
    header('Location: dashboard-client.php');
    exit();
}

// Get session details
$db = getDB();
$session = null;

try {
    $session = $db->fetch(
        "SELECT ps.*, s.slot_code, s.slot_type, s.floor 
         FROM parking_sessions ps 
         JOIN parking_slots s ON ps.slot_id = s.id 
         WHERE ps.id = ? AND ps.user_id = ? AND ps.status = 'completed'",
        [$sessionId, $currentUser['id']]
    );
} catch (Exception $e) {
    error_log("Error fetching session: " . $e->getMessage());
}

if (!$session) {
    setFlashMessage('error', 'Session not found or already processed');
    header('Location: dashboard-client.php');
    exit();
}

// Check if payment already exists
$existingPayment = null;
try {
    $existingPayment = $db->fetch(
        "SELECT * FROM payments WHERE session_id = ?",
        [$sessionId]
    );
} catch (Exception $e) {
    error_log("Error checking existing payment: " . $e->getMessage());
}

if ($existingPayment) {
    setFlashMessage('info', 'Payment already processed for this session');
    header('Location: dashboard-client.php');
    exit();
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = sanitizeInput($_POST['payment_method'] ?? '');
    
    if (!in_array($paymentMethod, ['cash', 'card', 'mobile'])) {
        setFlashMessage('error', 'Invalid payment method');
    } else {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Create payment record
            $paymentData = [
                'session_id' => $sessionId,
                'user_id' => $currentUser['id'],
                'amount' => $session['fee_amount'],
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'paid_at' => date('Y-m-d H:i:s')
            ];
            
            $paymentId = $db->insert('payments', $paymentData);
            
            if (!$paymentId) {
                throw new Exception('Failed to create payment record');
            }
            
            // Commit transaction
            $db->commit();
            
            // Set success message and redirect
            setFlashMessage('success', 'Payment successful! Thank you for using Smart Parking.');
            header("Location: payment.php?session_id={$sessionId}&success=1");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction
            $db->rollback();
            
            setFlashMessage('error', 'Payment failed: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Payment - Smart Parking System';
?>
<?php include 'includes/header.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="payment-container">
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <!-- Payment Success -->
                <div class="payment-success">
                    <div class="success-icon">✅</div>
                    <h1 class="success-title">Payment Successful!</h1>
                    <p class="success-message">Thank you for using Smart Parking System</p>
                    
                    <div class="receipt">
                        <h2>Payment Receipt</h2>
                        <div class="receipt-details">
                            <div class="receipt-row">
                                <span class="receipt-label">Receipt #:</span>
                                <span class="receipt-value"><?php echo sprintf('PAY%06d', $sessionId); ?></span>
                            </div>
                            <div class="receipt-row">
                                <span class="receipt-label">Date:</span>
                                <span class="receipt-value"><?php echo date('M j, Y H:i:s'); ?></span>
                            </div>
                            <div class="receipt-row">
                                <span class="receipt-label">Customer:</span>
                                <span class="receipt-value"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                            </div>
                            <div class="receipt-row">
                                <span class="receipt-label">Email:</span>
                                <span class="receipt-value"><?php echo htmlspecialchars($currentUser['email']); ?></span>
                            </div>
                            <div class="receipt-divider"></div>
                            <div class="receipt-row">
                                <span class="receipt-label">Slot Code:</span>
                                <span class="receipt-value"><?php echo htmlspecialchars($session['slot_code']); ?></span>
                            </div>
                            <div class="receipt-row">
                                <span class="receipt-label">Check-in:</span>
                                <span class="receipt-value"><?php echo date('M j, Y H:i', strtotime($session['checkin_time'])); ?></span>
                            </div>
                            <div class="receipt-row">
                                <span class="receipt-label">Check-out:</span>
                                <span class="receipt-value"><?php echo date('M j, Y H:i', strtotime($session['checkout_time'])); ?></span>
                            </div>
                            <div class="receipt-divider"></div>
                            <div class="receipt-row total">
                                <span class="receipt-label">Total Paid:</span>
                                <span class="receipt-value"><?php echo number_format($session['fee_amount'], 0); ?> RWF</span>
                            </div>
                        </div>
                        
                        <div class="receipt-actions">
                            <button class="btn btn-primary" onclick="window.print()">
                                <span>🖨️</span> Print Receipt
                            </button>
                            <a href="dashboard-client.php" class="btn btn-outline">
                                <span>🏠</span> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Payment Form -->
                <h1 class="form-title">Complete Payment</h1>
                <p class="form-subtitle">Choose your preferred payment method</p>
                
                <!-- Payment Summary -->
                <div class="payment-summary">
                    <h2>Payment Summary</h2>
                    <div class="summary-row">
                        <span class="summary-label">Parking Session:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($session['slot_code']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Duration:</span>
                        <span class="summary-value">
                            <?php 
                            $checkin = new DateTime($session['checkin_time']);
                            $checkout = new DateTime($session['checkout_time']);
                            $interval = $checkin->diff($checkout);
                            
                            if ($interval->days > 0) {
                                echo $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ', ';
                            }
                            echo $interval->h . ' hour' . ($interval->h != 1 ? 's' : '') . ' ' . $interval->i . ' minute' . ($interval->i != 1 ? 's' : '');
                            ?>
                        </span>
                    </div>
                    <div class="summary-row total">
                        <span class="summary-label">Amount Due:</span>
                        <span class="summary-value"><?php echo number_format($session['fee_amount'], 0); ?> RWF</span>
                    </div>
                </div>
                
                <!-- Payment Methods -->
                <form method="POST" class="payment-form">
                    <div class="payment-methods">
                        <h2>Select Payment Method</h2>
                        
                        <div class="payment-method">
                            <input type="radio" id="cash" name="payment_method" value="cash" required>
                            <label for="cash" class="payment-method-label">
                                <div class="payment-method-icon">💵</div>
                                <div class="payment-method-details">
                                    <div class="payment-method-title">Cash Payment</div>
                                    <div class="payment-method-description">Pay with cash at the exit gate</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" id="card" name="payment_method" value="card" required>
                            <label for="card" class="payment-method-label">
                                <div class="payment-method-icon">💳</div>
                                <div class="payment-method-details">
                                    <div class="payment-method-title">Credit/Debit Card</div>
                                    <div class="payment-method-description">Pay with your credit or debit card</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" id="mobile" name="payment_method" value="mobile" required>
                            <label for="mobile" class="payment-method-label">
                                <div class="payment-method-icon">📱</div>
                                <div class="payment-method-details">
                                    <div class="payment-method-title">Mobile Money</div>
                                    <div class="payment-method-description">Pay with MTN, Airtel Money or other mobile money services</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Card Payment Details (shown when card is selected) -->
                    <div id="cardDetails" class="card-details" style="display: none;">
                        <h3>Card Information</h3>
                        <div class="card-inputs">
                            <div class="form-group">
                                <label for="card_number" class="form-label">Card Number</label>
                                <input 
                                    type="text" 
                                    id="card_number" 
                                    name="card_number" 
                                    class="form-input" 
                                    placeholder="1234 5678 9012 3456"
                                    maxlength="19"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="card_name" class="form-label">Cardholder Name</label>
                                <input 
                                    type="text" 
                                    id="card_name" 
                                    name="card_name" 
                                    class="form-input" 
                                    placeholder="John Doe"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="expiry" class="form-label">Expiry Date</label>
                                <input 
                                    type="text" 
                                    id="expiry" 
                                    name="expiry" 
                                    class="form-input" 
                                    placeholder="MM/YY"
                                    maxlength="5"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="cvv" class="form-label">CVV</label>
                                <input 
                                    type="text" 
                                    id="cvv" 
                                    name="cvv" 
                                    class="form-input" 
                                    placeholder="123"
                                    maxlength="3"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Money Details (shown when mobile is selected) -->
                    <div id="mobileDetails" class="mobile-details" style="display: none;">
                        <h3>Mobile Money Information</h3>
                        <div class="form-group">
                            <label for="mobile_provider" class="form-label">Mobile Provider</label>
                            <select id="mobile_provider" name="mobile_provider" class="form-input">
                                <option value="">Select Provider</option>
                                <option value="mtn">MTN Mobile Money</option>
                                <option value="airtel">Airtel Money</option>
                                <option value="tigocash">Tigo Cash</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="mobile_number" class="form-label">Mobile Number</label>
                            <input 
                                type="tel" 
                                id="mobile_number" 
                                name="mobile_number" 
                                class="form-input" 
                                placeholder="250788123456"
                            >
                        </div>
                    </div>
                    
                    <div class="payment-actions">
                        <a href="dashboard-client.php" class="btn btn-outline">
                            <span>←</span> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <span>💳</span> Complete Payment
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.payment-container {
    max-width: 600px;
    margin: 0 auto;
}

.payment-success {
    text-align: center;
    padding: var(--spacing-xxl);
}

.success-icon {
    font-size: 4rem;
    margin-bottom: var(--spacing-lg);
    animation: successPulse 2s ease-in-out infinite;
}

@keyframes successPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.success-title {
    font-family: var(--font-heading);
    font-size: 2.5rem;
    color: var(--success-green);
    margin-bottom: var(--spacing-md);
}

.success-message {
    font-size: 1.2rem;
    color: var(--text-secondary);
    margin-bottom: var(--spacing-xl);
}

.receipt {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    text-align: left;
    max-width: 400px;
    margin: 0 auto;
}

.receipt h2 {
    font-family: var(--font-heading);
    font-size: 1.5rem;
    color: var(--accent-cyan);
    text-align: center;
    margin-bottom: var(--spacing-lg);
}

.receipt-details {
    space-y: var(--spacing-sm);
}

.receipt-row {
    display: flex;
    justify-content: space-between;
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--border-color);
}

.receipt-row:last-child {
    border-bottom: none;
}

.receipt-row.total {
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-md);
    border-top: 2px solid var(--accent-cyan);
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--accent-cyan);
}

.receipt-label {
    color: var(--text-secondary);
}

.receipt-value {
    font-weight: bold;
    color: var(--text-primary);
}

.receipt-divider {
    height: 1px;
    background: var(--border-color);
    margin: var(--spacing-md) 0;
}

.receipt-actions {
    display: flex;
    gap: var(--spacing-md);
    margin-top: var(--spacing-xl);
    justify-content: center;
}

.payment-summary {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-xl);
}

.payment-summary h2 {
    font-family: var(--font-heading);
    font-size: 1.5rem;
    color: var(--accent-cyan);
    margin-bottom: var(--spacing-lg);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--border-color);
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row.total {
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-md);
    border-top: 2px solid var(--accent-cyan);
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--accent-cyan);
}

.summary-label {
    color: var(--text-secondary);
}

.summary-value {
    font-weight: bold;
    color: var(--text-primary);
}

.payment-form {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
}

.payment-methods h2 {
    font-family: var(--font-heading);
    font-size: 1.5rem;
    color: var(--accent-cyan);
    margin-bottom: var(--spacing-lg);
}

.payment-method {
    margin-bottom: var(--spacing-md);
}

.payment-method input[type="radio"] {
    display: none;
}

.payment-method-label {
    display: flex;
    align-items: center;
    padding: var(--spacing-lg);
    background: var(--secondary-bg);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: var(--transition-fast);
}

.payment-method-label:hover {
    border-color: var(--accent-cyan);
    background: rgba(0, 212, 255, 0.05);
}

.payment-method input[type="radio"]:checked + .payment-method-label {
    border-color: var(--accent-cyan);
    background: rgba(0, 212, 255, 0.1);
    box-shadow: 0 0 15px rgba(0, 212, 255, 0.3);
}

.payment-method-icon {
    font-size: 2rem;
    margin-right: var(--spacing-lg);
}

.payment-method-details {
    flex: 1;
}

.payment-method-title {
    font-weight: bold;
    margin-bottom: var(--spacing-xs);
    color: var(--text-primary);
}

.payment-method-description {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.card-details,
.mobile-details {
    margin-top: var(--spacing-xl);
    padding: var(--spacing-lg);
    background: var(--secondary-bg);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.card-details h3,
.mobile-details h3 {
    font-family: var(--font-heading);
    font-size: 1.3rem;
    color: var(--accent-cyan);
    margin-bottom: var(--spacing-lg);
}

.card-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-md);
}

.card-inputs .form-group:first-child {
    grid-column: 1 / -1;
}

.payment-actions {
    display: flex;
    gap: var(--spacing-md);
    margin-top: var(--spacing-xl);
    justify-content: space-between;
}

@media (max-width: 768px) {
    .payment-actions {
        flex-direction: column;
    }
    
    .payment-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .card-inputs {
        grid-template-columns: 1fr;
    }
    
    .receipt-actions {
        flex-direction: column;
    }
    
    .receipt-actions .btn {
        width: 100%;
    }
}

@media print {
    .payment-actions,
    .navbar,
    .footer {
        display: none !important;
    }
    
    .receipt {
        box-shadow: none;
        border: 1px solid #000;
    }
    
    body {
        background: white !important;
        color: black !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle payment method selection
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const cardDetails = document.getElementById('cardDetails');
    const mobileDetails = document.getElementById('mobileDetails');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            // Hide all detail sections
            cardDetails.style.display = 'none';
            mobileDetails.style.display = 'none';
            
            // Show relevant section
            if (this.value === 'card') {
                cardDetails.style.display = 'block';
            } else if (this.value === 'mobile') {
                mobileDetails.style.display = 'block';
            }
        });
    });
    
    // Initialize card formatting
    initializeCardFormatting();
});

function initializeCardFormatting() {
    const cardInput = document.getElementById('card_number');
    const expiryInput = document.getElementById('expiry');
    const cvvInput = document.getElementById('cvv');
    
    if (cardInput) {
        cardInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
    }
    
    if (expiryInput) {
        expiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
        });
    }
    
    if (cvvInput) {
        cvvInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 3);
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
