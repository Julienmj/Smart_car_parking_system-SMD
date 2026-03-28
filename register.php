<?php
/**
 * Register Page - Smart Car Parking System
 * Handles new user registration
 */

require_once 'includes/auth.php';
require_once 'includes/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    $redirectUrl = $user['role'] === 'admin' ? 'dashboard-admin.php' : 'dashboard-client.php';
    header("Location: $redirectUrl");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($fullName)) {
        $errors[] = 'Full name is required';
    } elseif (strlen($fullName) < 2) {
        $errors[] = 'Full name must be at least 2 characters';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (!isValidPassword($password)) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if (empty($confirmPassword)) {
        $errors[] = 'Please confirm your password';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        // Attempt registration
        $result = registerUser($fullName, $email, $password);
        
        if ($result['success']) {
            setFlashMessage('success', 'Registration successful! Please login to continue.');
            header('Location: login.php');
            exit();
        } else {
            $errors[] = $result['message'];
        }
    }
    
    if (!empty($errors)) {
        setFlashMessage('error', implode('<br>', $errors));
    }
}

$pageTitle = 'Register - Smart Parking System';
?>
<?php include 'includes/header.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="form-container">
            <h1 class="form-title">Create Account</h1>
            <p class="form-subtitle">Join Smart Parking for seamless parking experience</p>
            
            <form method="POST" data-validate>
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-input" 
                        placeholder="Enter your full name"
                        required
                        value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                    >
                    <div class="form-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="Enter your email"
                        required
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    >
                    <div class="form-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Create a password (min. 6 characters)"
                        required
                    >
                    <div class="form-error"></div>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input" 
                        placeholder="Confirm your password"
                        required
                    >
                    <div class="form-error"></div>
                </div>
                
                <div class="form-group">
                    <div class="form-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="#" class="link" onclick="showTerms()">Terms and Conditions</a></label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-checkbox">
                        <input type="checkbox" id="newsletter" name="newsletter">
                        <label for="newsletter">Send me parking updates and promotions</label>
                    </div>
                </div>
                
                <button type="submit" class="form-submit">
                    <span>🚗</span> Create Account
                </button>
            </form>
            
            <div class="form-footer">
                <p>Already have an account? <a href="login.php" class="link">Sign in here</a></p>
                <p><a href="index.html" class="link">← Back to Home</a></p>
            </div>
        </div>
    </div>
</div>

<style>
.form-subtitle {
    text-align: center;
    color: var(--text-secondary);
    margin-bottom: var(--spacing-xl);
}

.form-footer {
    text-align: center;
    margin-top: var(--spacing-lg);
    padding-top: var(--spacing-lg);
    border-top: 1px solid var(--border-color);
}

.form-footer p {
    margin-bottom: var(--spacing-sm);
    color: var(--text-secondary);
}

.link {
    color: var(--accent-cyan);
    text-decoration: none;
    transition: var(--transition-fast);
}

.link:hover {
    color: var(--accent-cyan-hover);
    text-decoration: underline;
}

.password-strength {
    margin-top: var(--spacing-xs);
    font-size: 0.8rem;
    height: 20px;
}

.strength-weak {
    color: var(--error-red);
}

.strength-medium {
    color: var(--warning-yellow);
}

.strength-strong {
    color: var(--success-green);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordStrength = document.getElementById('passwordStrength');
    
    // Password strength checker
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = checkPasswordStrength(password);
        
        passwordStrength.className = 'password-strength';
        
        if (password.length === 0) {
            passwordStrength.textContent = '';
        } else if (strength < 3) {
            passwordStrength.classList.add('strength-weak');
            passwordStrength.textContent = 'Weak password';
        } else if (strength < 5) {
            passwordStrength.classList.add('strength-medium');
            passwordStrength.textContent = 'Medium strength';
        } else {
            passwordStrength.classList.add('strength-strong');
            passwordStrength.textContent = 'Strong password';
        }
    });
    
    // Real-time password confirmation
    confirmPasswordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        const confirmPassword = this.value;
        const errorElement = this.parentNode.querySelector('.form-error');
        
        if (confirmPassword && password !== confirmPassword) {
            errorElement.textContent = 'Passwords do not match';
        } else {
            errorElement.textContent = '';
        }
    });
    
    // Email availability check (debounced)
    const emailInput = document.getElementById('email');
    const checkEmailAvailability = debounce(function() {
        const email = emailInput.value;
        if (isValidEmail(email)) {
            checkEmailExists(email);
        }
    }, 500);
    
    emailInput.addEventListener('blur', checkEmailAvailability);
});

function checkPasswordStrength(password) {
    let strength = 0;
    
    // Length check
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    
    // Character variety checks
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    return strength;
}

function checkEmailExists(email) {
    fetch('api/check-email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(data => {
        const emailInput = document.getElementById('email');
        const errorElement = emailInput.parentNode.querySelector('.form-error');
        
        if (data.exists) {
            errorElement.textContent = 'Email already registered';
            emailInput.style.borderColor = 'var(--error-red)';
        } else {
            errorElement.textContent = '';
            emailInput.style.borderColor = '';
        }
    })
    .catch(error => {
        console.error('Error checking email:', error);
    });
}

function showTerms() {
    const modal = document.createElement('div');
    modal.className = 'modal active';
    modal.id = 'terms-modal';
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Terms and Conditions</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <h3>Smart Parking System Terms of Service</h3>
                
                <h4>1. Acceptance of Terms</h4>
                <p>By creating an account with Smart Parking System, you agree to these terms and conditions.</p>
                
                <h4>2. Account Responsibilities</h4>
                <p>You are responsible for maintaining the confidentiality of your account credentials.</p>
                
                <h4>3. Parking Services</h4>
                <p>Our platform provides parking slot reservation and management services. Fees are calculated based on duration and slot type.</p>
                
                <h4>4. Payment Terms</h4>
                <p>Payment is required upon checkout. We accept various payment methods including cash, card, and mobile money.</p>
                
                <h4>5. Privacy Policy</h4>
                <p>We respect your privacy and protect your personal information according to our privacy policy.</p>
                
                <h4>6. Prohibited Activities</h4>
                <p>You may not use our service for illegal activities or to disrupt our operations.</p>
                
                <h4>7. Service Availability</h4>
                <p>While we strive for 100% uptime, we cannot guarantee uninterrupted service availability.</p>
                
                <h4>8. Contact Information</h4>
                <p>For questions about these terms, contact us at support@smartparking.com</p>
                
                <div class="terms-actions">
                    <button class="btn btn-primary" onclick="acceptTerms()">I Accept</button>
                    <button class="btn btn-outline" onclick="closeTermsModal()">Close</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Initialize modal close
    modal.querySelector('.modal-close').addEventListener('click', closeTermsModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeTermsModal();
        }
    });
}

function acceptTerms() {
    const termsCheckbox = document.getElementById('terms');
    termsCheckbox.checked = true;
    closeTermsModal();
    SmartParking.showToast('Terms and conditions accepted', 'success');
}

function closeTermsModal() {
    const modal = document.getElementById('terms-modal');
    if (modal) {
        modal.remove();
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>

<?php include 'includes/footer.php'; ?>
