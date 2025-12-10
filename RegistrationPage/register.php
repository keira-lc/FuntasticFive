<?php
session_start();

$error = '';
$success = '';
$name = $email = $address = $contact = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = $_POST['address'] ?? '';
    $contact = $_POST['contact'] ?? '';
    
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($address) || empty($contact)) {
        $error = 'All fields are required!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } elseif (!preg_match('/^[0-9]{11}$/', $contact)) {
        $error = 'Please enter a valid 11-digit contact number!';
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=stylenwear_db", "root", "");
            
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Email already exists!';
            } else {
                $username = strtolower(str_replace(' ', '', $name)) . rand(1, 99);
                
                // Use password_hash() to securely hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (fullname, username, password_hash, email, date_joined) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $username, $hashed_password, $email]);
                
                $user_id = $pdo->lastInsertId();
                
                // Insert address and contact into user_info table (using contact_no column)
                $stmt = $pdo->prepare("INSERT INTO user_info (user_id, address, contact_no) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $address, $contact]);
                
                $success = 'Registration successful! You can now login.';
                
                if ($success) {
                    $name = $email = $address = $contact = '';
                }
            }
            
        } catch (Exception $e) {
            $error = 'Registration failed. Please try again. Error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Style'n Wear - Luxury Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/register-style.css">
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
            Create your account to join our luxury community
        </div>

        <?php if ($error): ?>
            <div class="alert-luxury danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-luxury success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm">
            <!-- Full Name -->
            <div class="mb-4">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control-luxury" 
                       placeholder="Enter your full name" 
                       value="<?php echo htmlspecialchars($name); ?>" required>
            </div>

            <!-- Email -->
            <div class="mb-4">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control-luxury" 
                       placeholder="Enter your email" 
                       value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <!-- Password Fields -->
            <div class="row mb-4">
                <div class="col-12 col-md-6 mb-3 mb-md-0">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" id="password" 
                           class="form-control-luxury" placeholder="Create password (min. 6 characters)" required>
                    <div class="password-strength">
                        <div class="strength-meter" id="strengthMeter"></div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" 
                           class="form-control-luxury" placeholder="Confirm password" required>
                    <div id="passwordMatch" style="font-size: 0.85rem; margin-top: 5px;"></div>
                </div>
            </div>

            <!-- Contact Number -->
            <div class="mb-4">
                <label class="form-label">Contact Number</label>
                <input type="tel" name="contact" id="contact" class="form-control-luxury" 
                       placeholder="e.g., 09171234567" 
                       value="<?php echo htmlspecialchars($contact); ?>" 
                       maxlength="11"
                       required>
                <small style="color: var(--gold-light); font-size: 0.85rem; display: block; margin-top: 5px;">
                    Enter your 11-digit contact number
                </small>
                <div id="contactError" style="font-size: 0.85rem; margin-top: 5px;"></div>
            </div>

            <!-- Address -->
            <div class="mb-4">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control-luxury" 
                          placeholder="Enter your complete address" required><?php echo htmlspecialchars($address); ?></textarea>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-gold mb-4">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>

            <!-- Login Link -->
            <div class="login-link">
                <span>Already have an account?</span>
                <a href="login.php">Sign in here</a>
            </div>

            <!-- Contact Information Section -->
            <div class="contact-section">
                <h5 class="contact-title">Need Help?</h5>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>info@stylenwear.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>0917-123-4567</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Manila, Philippines</span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');
            const contactInput = document.getElementById('contact');
            const strengthMeter = document.getElementById('strengthMeter');
            const passwordMatch = document.getElementById('passwordMatch');
            const contactError = document.getElementById('contactError');

            // Password strength indicator
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 6) strength += 25;
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
                if (password.match(/\d/)) strength += 25;
                if (password.match(/[^a-zA-Z\d]/)) strength += 25;
                
                strengthMeter.style.width = strength + '%';
                
                if (strength < 50) {
                    strengthMeter.style.background = 'linear-gradient(90deg, #f56565, #ed8936)';
                } else if (strength < 75) {
                    strengthMeter.style.background = 'linear-gradient(90deg, #ed8936, #ecc94b)';
                } else {
                    strengthMeter.style.background = 'linear-gradient(90deg, #48bb78, #38b2ac)';
                }
            });

            // Password confirmation check
            function checkPasswordMatch() {
                if (passwordInput.value && confirmInput.value) {
                    if (passwordInput.value === confirmInput.value) {
                        passwordMatch.innerHTML = '<span style="color: #48bb78;">✓ Password Matched</span>';
                    } else {
                        passwordMatch.innerHTML = '<span style="color: #f56565;">✗ Password do not match</span>';
                    }
                } else {
                    passwordMatch.innerHTML = '';
                }
            }

            passwordInput.addEventListener('input', checkPasswordMatch);
            confirmInput.addEventListener('input', checkPasswordMatch);

            // Contact number validation
            function validateContactNumber() {
                const contact = contactInput.value;
                // Remove any non-digit characters
                const digitsOnly = contact.replace(/\D/g, '');
                
                // Update input value to digits only
                if (contact !== digitsOnly) {
                    contactInput.value = digitsOnly;
                }
                
                // Validate length
                if (digitsOnly.length === 11) {
                    // Check if it starts with 09 (Philippine mobile number format)
                    if (digitsOnly.startsWith('09')) {
                        contactError.innerHTML = '<span style="color: #48bb78;">✓ Valid number</span>';
                        contactError.style.color = '#48bb78';
                    } else {
                        contactError.innerHTML = '<span style="color: #f56565;">✗ Should start with 09</span>';
                        contactError.style.color = '#f56565';
                    }
                } else if (digitsOnly.length > 0) {
                    contactError.innerHTML = '<span style="color: #f56565;">✗ Must be exactly 11 digits</span>';
                    contactError.style.color = '#f56565';
                } else {
                    contactError.innerHTML = '';
                }
            }

            contactInput.addEventListener('input', validateContactNumber);
            contactInput.addEventListener('blur', validateContactNumber);

            // Only allow numbers in contact field
            contactInput.addEventListener('keypress', function(e) {
                const char = String.fromCharCode(e.keyCode || e.which);
                if (!/^\d$/.test(char)) {
                    e.preventDefault();
                }
            });

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
            const form = document.getElementById('registerForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Validate contact number on submit
                    const contactValue = contactInput.value.replace(/\D/g, '');
                    if (contactValue.length !== 11 || !contactValue.startsWith('09')) {
                        e.preventDefault();
                        contactError.innerHTML = '<span style="color: #f56565;">✗ Please enter a valid 11-digit Philippine number starting with 09</span>';
                        contactError.style.color = '#f56565';
                        contactInput.focus();
                        return;
                    }

                    const submitBtn = this.querySelector('.btn-gold');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
                        submitBtn.disabled = true;
                    }
                });
            }
        });
    </script>
</body>
</html>