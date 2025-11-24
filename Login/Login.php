<?php
session_start();


if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: Index.php"); 
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
   
    if ($email === 'user@example.com' && $password === 'password') {
        $_SESSION['loggedin'] = true;
        $_SESSION['email'] = $email;
        header("Location: Index.php"); 
        exit;
    } else {
        $error = "Invalid email or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Style'nWear - Login</title>
    <link rel="stylesheet" href="style.css">  
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> <!-- Added for icon -->
    <style>
        body {
            background-image: url('image 5.jpg');
            font-style: oblique;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
            color: #333333;
            background-size: cover;
            height: 100%;
        }
        .navbar {
            background-color: #e74eb1;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: #e74eb1 !important;
        }
        .nav-link {
            color: #fff !important;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .nav-link:hover {
            text-decoration: underline;
            color: #e74eb1 !important;
        }
        .login-section {
            background-color: #ffc2d9;
            padding: 80px 20px;
            text-align: center;
            font-size: medium;
            font-style: italic;
        }
        .login-section h2 {
            font-size: 3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 30px;
        }
        .login-section form {
            max-width: 400px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .login-section .form-label {
            font-size: 1rem;
            font-weight: bold;
            color: #333;
        }
        .login-section .form-control {
            border-radius: 5px;
            padding: 10px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
            font-size: 1rem;
        }
        .login-section .form-control:focus {
            border-color: #ffc2d9;
            box-shadow: 0 0 5px rgb(242, 159, 209);
        }
        .login-section button {
            background-color: #e74eb1;
            color: #fff;
            font-size: 1.1rem;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .login-section button:hover {
            background-color: #d63384;
        }
        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #555;
        }
        .signup-link a {
            color: #e74eb1;
            text-decoration: none;
        }
        .signup-link a:hover {
            text-decoration: underline;
        }
        .error-message {
            background-color: #ff6b6b;
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 1.2rem;
            font-weight: bold;
        }
        footer {
            background-color: #000000;
            color: #fff;
            padding: 30px 0;
            text-align: center;
        }
        footer a {
            color: #e74eb1;
            text-decoration: none;
        }
        footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">Style'nWear</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.html">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="services.html">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.html">Contact Us</a></li>
                    <li class="nav-item"><a class="nav-link active" href="login.php">Login</a></li>
                </ul>
            </div>
          
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <div class="d-flex align-items-center ms-3">
                    <i class="fas fa-user-circle" style="font-size: 24px; color: white;"></i>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <section class="login-section">
        <div class="container">
            <h2>Login to Style'nWear</h2>
            <p>Access your account to explore our latest collections and exclusive offers.</p>
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            <form id="loginForm" action="login.php" method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
            <div class="signup-link">
                <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
            </div>
        </div>
    </section>

    <footer class="text-center p-3">
        <p>&copy; 2025 Style'nWear. All rights reserved. | <a href="privacy.html">Privacy Policy</a></p>
    </footer>

    <script src="script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
