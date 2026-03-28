<?php
/**
 * Login Page - Smart Car Parking System
 * Handles user authentication
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
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    if (empty($errors)) {
        // Attempt login
        $user = validateUser($email, $password);
        
        if ($user) {
            loginUser($user['id'], $user['full_name'], $user['email'], $user['role']);
            
            // Always redirect based on role — ignore any stored redirect to prevent cross-role access
            $redirectUrl = $user['role'] === 'admin' ? 'dashboard-admin.php' : 'dashboard-client.php';
            unset($_SESSION['redirect_after_login']);
            
            setFlashMessage('success', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '!');
            header("Location: $redirectUrl");
            exit();
        } else {
            $errors[] = 'Invalid email or password';
        }
    }
    
    if (!empty($errors)) {
        setFlashMessage('error', implode('<br>', $errors));
    }
}

$pageTitle = 'Login - Smart Parking System';
?>
<?php include 'includes/header.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="form-container">
            <h1 class="form-title">Sign In</h1>
            <p class="form-subtitle">Sign in to access your parking dashboard</p>
            
            <form method="POST" data-validate>
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
                        placeholder="Enter your password"
                        required
                    >
                    <div class="form-error"></div>
                </div>
                
                <div class="form-group">
                    <div class="form-checkbox">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me for 30 days</label>
                    </div>
                </div>
                
                <button type="submit" class="form-submit">
                    <span>🔐</span> Sign In
                </button>
            </form>
            
            <div class="form-footer">
                <p>Don't have an account? <a href="register.php" class="link">Register here</a></p>
                <p><a href="#" class="link" onclick="showPasswordReset()">Forgot password?</a></p>
            </div>
            
            <div class="demo-accounts">
                <h3>Demo Accounts</h3>
                <div class="demo-account">
                    <strong>Admin:</strong> admin@parking.com / admin123
                </div>
                <div class="demo-account">
                    <strong>Test Client:</strong> client@test.com / client123
                </div>
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

.demo-accounts {
    margin-top: var(--spacing-xl);
    padding: var(--spacing-lg);
    background: var(--secondary-bg);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.demo-accounts h3 {
    font-size: 1rem;
    color: var(--text-secondary);
    margin-bottom: var(--spacing-sm);
    text-align: center;
}

.demo-account {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-bottom: var(--spacing-xs);
    text-align: center;
}

.demo-account strong {
    color: var(--text-secondary);
}
</style>

<script>
// Handle remember me functionality
document.addEventListener('DOMContentLoaded', function() {
    const rememberCheckbox = document.getElementById('remember');
    const emailInput = document.getElementById('email');
    
    // Load saved email if exists
    const savedEmail = localStorage.getItem('rememberedEmail');
    if (savedEmail) {
        emailInput.value = savedEmail;
        rememberCheckbox.checked = true;
    }
    
    // Save email on form submit if remember is checked
    document.querySelector('form').addEventListener('submit', function(e) {
        if (rememberCheckbox.checked) {
            localStorage.setItem('rememberedEmail', emailInput.value);
        } else {
            localStorage.removeItem('rememberedEmail');
        }
    });
});

function showPasswordReset() {
    SmartParking.showToast('Password reset functionality coming soon!', 'info');
}
</script>

<?php include 'includes/footer.php'; ?>
