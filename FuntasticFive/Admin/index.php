<?php
session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

// List of authorized admin email addresses
$admin_emails = [
    'admin@stylenwear.com',
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password!';
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=stylenwear_db", "root", "");
            
            // Check for user credentials
            $stmt = $pdo->prepare("SELECT user_id, fullname, password_hash, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Verify password (support both hashed and plain text for existing admin)
                $passwordValid = false;
                if (password_verify($password, $user['password_hash'])) {
                    $passwordValid = true;
                } elseif ($password === $user['password_hash']) { // For existing plain text passwords
                    $passwordValid = true;
                }
                
                if ($passwordValid) {
                    // Check if email is in admin list
                    if (in_array($user['email'], $admin_emails)) {
                        $_SESSION['admin_id'] = $user['user_id'];
                        $_SESSION['admin_name'] = $user['fullname'];
                        $_SESSION['admin_email'] = $user['email'];
                        $_SESSION['admin_logged_in'] = true;
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $error = 'Access denied. This email does not have admin privileges.';
                    }
                } else {
                    $error = 'Invalid password!';
                }
            } else {
                $error = 'User account not found!';
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
    <title>Style'n Wear - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/jpg" href="../image/stylenwear.png">
    <style>
        :root {
            --gold: #d4af37;
            --gold-light: #f4e4a6;
            --gold-dark: #b8860b;
            --gold-glow: rgba(212, 175, 55, 0.3);
            --charcoal: #1a1a1a;
            --off-white: #f8f5f0;
            --cream: #fff8e1;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            color: var(--off-white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .gold-text {
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 600;
        }

        .luxury-card {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 20px;
            padding: 50px;
            width: 100%;
            max-width: 500px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .luxury-card:hover {
            transform: translateY(-10px);
            border-color: var(--gold);
            box-shadow: 0 25px 50px rgba(212, 175, 55, 0.2);
        }

        .luxury-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            border-radius: 20px 20px 0 0;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-container img {
            width: 150px;
            height: 150px;
            object-fit: contain;
            filter: drop-shadow(0 0 10px rgba(212, 175, 55, 0.3));
            transition: var(--transition);
        }

        .logo-container img:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 0 15px rgba(212, 175, 55, 0.5));
        }

        .brand-title {
            font-family: 'Cinzel', serif;
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-align: center;
            margin-bottom: 10px;
            position: relative;
        }

        .brand-title::after {
            content: '™';
            font-size: 0.8rem;
            position: absolute;
            top: 5px;
            right: -15px;
            color: var(--gold);
        }

        .card-subtitle {
            text-align: center;
            color: var(--gold-light);
            margin-bottom: 30px;
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
        }

        .form-label {
            font-family: 'Playfair Display', serif;
            color: var(--gold-light);
            font-size: 1.1rem;
            margin-bottom: 8px;
            display: block;
        }

        .form-control-luxury {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: var(--off-white);
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
            width: 100%;
        }

        .form-control-luxury:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
            background: rgba(255, 255, 255, 0.08);
        }

        .form-control-luxury::placeholder {
            color: rgba(212, 175, 55, 0.5);
        }

        .btn-gold {
            background: linear-gradient(45deg, var(--gold-dark), var(--gold));
            color: var(--charcoal);
            border: none;
            padding: 14px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-family: 'Cinzel', serif;
            letter-spacing: 1px;
            transition: var(--transition);
            width: 100%;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .btn-gold:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.4);
            color: white;
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        .form-check-label {
            color: var(--gold-light);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check-input {
            accent-color: var(--gold);
        }

        .forgot-link {
            color: var(--gold);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .forgot-link:hover {
            color: var(--gold-light);
            text-decoration: underline;
        }

        .alert-luxury {
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid var(--gold);
            color: var(--gold-light);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.95rem;
        }

        .alert-luxury.danger {
            background: rgba(245, 101, 101, 0.1);
            border-color: #f56565;
            color: #ff9999;
        }

        .alert-luxury.warning {
            background: rgba(255, 193, 7, 0.1);
            border-color: #ffc107;
            color: #ffe69c;
        }

        .admin-warning {
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid var(--gold);
            color: var(--gold-light);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
        }

        .admin-warning i {
            color: var(--gold);
            margin-right: 8px;
        }

        .back-to-home {
            text-align: center;
            color: var(--gold-light);
            margin-top: 20px;
        }

        .back-to-home a {
            color: var(--gold);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .back-to-home a:hover {
            color: var(--gold-light);
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .luxury-card {
                padding: 30px;
            }
            
            .brand-title {
                font-size: 1.8rem;
            }
            
            .logo-container img {
                width: 120px;
                height: 120px;
            }
        }

        @media (max-width: 576px) {
            .luxury-card {
                padding: 20px;
            }
            
            .brand-title {
                font-size: 1.6rem;
            }
            
            .logo-container img {
                width: 100px;
                height: 100px;
            }
            
            .form-options {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="luxury-card">
        <!-- Logo Container -->
        <div class="logo-container">
            <img src="../image/stylenwear.png" alt="Style'n Wear Logo">
        </div>

        <div class="brand-title gold-text">
            Style'n Wear
        </div>
        <div class="card-subtitle">
            Admin Portal - Restricted Access
        </div>

        <?php if ($error): ?>
            <div class="alert-luxury danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Admin Access Warning -->
        <div class="admin-warning">
            <i class="fas fa-shield-alt"></i>
            This portal is restricted to authorized administrators only.
        </div>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="form-label">Admin Email</label>
                <input type="email" name="email" class="form-control-luxury" 
                       placeholder="Enter admin email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control-luxury" 
                       placeholder="Enter password" required>
            </div>

            <div class="form-options">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="remember">
                    Remember me
                </label>
                <a href="#" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn-gold mb-4">
                <i class="fas fa-sign-in-alt me-2"></i>Admin Login
            </button>

            <div class="back-to-home">
                <span>Return to </span>
                <a href="../index.php">Public Website</a>
            </div>
        </form>
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
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';
                        submitBtn.disabled = true;
                    }
                });
            }
        });
    </script>
</body>
</html>