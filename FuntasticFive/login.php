<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: client/index.php');
    exit();
}

$error = '';
$success = false;

// List of authorized admin email addresses
$admin_emails = [
    'admin@stylenwear.com',
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Check if the email is an admin email
    if (in_array($email, $admin_emails)) {
        $error = 'Admin must login through the admin portal. <a href="../admin/index.php">Go to Admin Portal</a>';
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=stylenwear_db", "root", "");
            
            $stmt = $pdo->prepare("SELECT user_id, fullname, password_hash, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // Verify password using password_verify() for hashed passwords
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['logged_in'] = true;
                $success = true;
            } else {
                $error = 'Invalid email or password!';
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Style'n Wear - Luxury Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login-style.css">
    <link rel="icon" type="image/jpg" href="image/stylenwear.png">
</head>
<body>
    <div class="luxury-card">
        <!-- Logo Container -->
        <div class="logo-container">
            <img src="image/stylenwear.png" alt="Style'n Wear Logo">
        </div>

        <div class="brand-title gold-text">
            Style'n Wear
        </div>
        <div class="card-subtitle">
            Sign in to your luxury fashion account
        </div>

        <?php if ($success): ?>
            <div class="success-message">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h3>Welcome back!</h3>
                <p>Redirecting to your dashboard...</p>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = 'client/index.php';
                }, 2000);
            </script>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert-luxury danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control-luxury" placeholder="Enter your email" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control-luxury" placeholder="Enter your password" required>
                </div>

                <div class="form-options">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" name="remember">
                        Remember me
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn-gold mb-4">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>

                <div class="divider">
                    <span>Or continue with</span>
                </div>

                <div class="social-buttons">
                    <button type="button" class="social-btn">
                        <img src="image/google-icon.png" alt="Google">
                        Google
                    </button>
                    <button type="button" class="social-btn">
                        <img src="image/fb.png" alt="Facebook">
                        Facebook
                    </button>
                </div>

                <div class="signup-link">
                    <span>Don't have an account?</span>
                    <a href="register.php">Create one now</a>
                </div>

                <!-- Contact Section -->
                <div class="contact-section">
                    <h5 class="contact-title">Need Help?</h5>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>info@stylenwear.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+63 123 456 7890</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Manila, Philippines</span>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus effects
            const inputs = document.querySelectorAll('.form-control-luxury');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = 'var(--gold)';
                    this.style.boxShadow = '0 0 0 3px rgba(212, 175, 55, 0.1)';
                });
                
                input.addEventListener('blur', function() {
                    this.style.borderColor = 'rgba(212, 175, 55, 0.3)';
                    this.style.boxShadow = 'none';
                });
            });

            // Form submit animation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('.btn-gold');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing in...';
                        submitBtn.disabled = true;
                    }
                });
            }

            // Social buttons handlers
            document.querySelectorAll('.social-btn').forEach(button => {
                button.addEventListener('click', function() {
                    alert('Social login functionality would be implemented here.');
                });
            });
        });
    </script>
</body>
</html>