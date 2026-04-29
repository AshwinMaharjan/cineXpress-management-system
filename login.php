<?php 
session_start();
include("connect.php");

$error_message = '';
$success_message = '';

if(isset($_POST['login'])) {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = $_POST['password'];

    // Use prepared statement for security
    $sql = "SELECT * FROM `users` WHERE email = ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_array($result);
        
        // Verify password (assuming plain text for now, but should use password_hash/password_verify)
        if($data['password'] === $password) {
            $role = $data['roletype'];
            
            $_SESSION['uid'] = $data['userid'];
            $_SESSION['type'] = $role;
            $_SESSION['email'] = $data['email'];
            $_SESSION['name'] = $data['name'] ?? 'User';

            if($role == 1) {
                header("Location: admin/dashboard.php");
                exit();
            } else if($role == 2) {
                header("Location: index.php");
                exit();
            }
        } else {
            $error_message = "Invalid email or password";
        }
    } else {
        $error_message = "Invalid email or password";
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cinema Hall System</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  </head>
  <body>
  <?php include("header.php")?> 

    <!-- Mobile Navigation -->
    <div class="mobile-nav" id="mobileNav">
        <a href="index.php" class="mobile-nav-link">Dashboard</a>
        <a href="movies.php" class="mobile-nav-link">Movies</a>
        <a href="theater.php" class="mobile-nav-link">Theater</a>
        <a href="login.php" class="mobile-nav-link active">Login</a>
        <a href="register.php" class="mobile-nav-link">Register</a>
    </div>

    <!-- Login Section -->
    <main class="login-main">
        <div class="login-container">
            
            <!-- Left Side - Branding -->
            <div class="login-branding">
                <div class="branding-content">
                    <div class="brand-icon">
                        <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                            <circle cx="40" cy="40" r="35" stroke="currentColor" stroke-width="2" opacity="0.3"/>
                            <path d="M30 40L35 45L50 30" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h1 class="brand-title">Welcome Back</h1>
                    <p class="brand-subtitle">Access your cinema experience</p>
                    
                    <div class="brand-features">
                        <div class="feature-item">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 12V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V5C3 3.89543 3.89543 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Easy Booking</span>
                        </div>
                        <div class="feature-item">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="6" width="18" height="15" rx="2" stroke="currentColor" stroke-width="2"/>
                                <path d="M3 10H21" stroke="currentColor" stroke-width="2"/>
                                <path d="M7 3V6M17 3V6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Schedule Management</span>
                        </div>
                        <div class="feature-item">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                                <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Real-time Updates</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="login-form-wrapper">
                <div class="login-form-container">
                    
                    <!-- Form Header -->
                    <div class="form-header">
                        <h2 class="form-title">Sign In</h2>
                        <p class="form-description">Enter your credentials to continue</p>
                    </div>

                    <!-- Alert Messages -->
                    <?php if(!empty($error_message)): ?>
                        <div class="alert alert-error">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2"/>
                                <path d="M10 6V10M10 14H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span><?= htmlspecialchars($error_message) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($success_message)): ?>
                        <div class="alert alert-success">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2"/>
                                <path d="M6 10L9 13L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span><?= htmlspecialchars($success_message) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form action="login.php" method="POST" class="login-form">
                        
                        <!-- Email Field -->
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <rect x="2" y="4" width="12" height="9" rx="1" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M2 5L8 9L14 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Email Address
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="you@example.com"
                                required
                                autocomplete="email"
                            >
                        </div>

                        <!-- Password Field -->
                        <div class="form-group">
                            <label for="password" class="form-label">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <rect x="4" y="7" width="8" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M6 7V5C6 3.89543 6.89543 3 8 3C9.10457 3 10 3.89543 10 5V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                Password
                            </label>
                            <div class="password-wrapper">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-input" 
                                    placeholder="••••••••"
                                    required
                                    autocomplete="current-password"
                                >
                                <button type="button" class="toggle-password" id="togglePassword">
                                    <svg class="eye-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <path d="M10 4C5 4 2 10 2 10C2 10 5 16 10 16C15 16 18 10 18 10C18 10 15 4 10 4Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="10" cy="10" r="2" stroke="currentColor" stroke-width="1.5"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Remember & Forgot -->
                        <div class="form-extras">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember" class="checkbox-input">
                                <span class="checkbox-text">Remember me</span>
                            </label>
                            <a href="#" class="forgot-link">Forgot Password?</a>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" name="login" class="submit-btn">
                            <span>Sign In</span>
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M7 15L12 10L7 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        
                        <!-- Register Link -->
                        <div class="form-footer">
                            <p class="footer-text">
                                Don't have an account? 
                                <a href="register.php" class="register-link">Create Account</a>
                            </p>
                        </div>

                    </form>
                </div>
            </div>

          </div>
        </main>
        <?php include("footer.php")?> 

   <!-- JavaScript -->
    <script>
        // Mobile Menu Toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileNav = document.getElementById('mobileNav');

        mobileMenuToggle?.addEventListener('click', () => {
            mobileMenuToggle.classList.toggle('active');
            mobileNav.classList.toggle('active');
        });

        // Password Toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword?.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePassword.classList.toggle('active');
        });

        // Form Validation
        const loginForm = document.querySelector('.login-form');
        loginForm?.addEventListener('submit', (e) => {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });

        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>

</body>

</html>

