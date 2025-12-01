<?php
session_start();


if (isset($_SESSION['user_id'])) {
    header('Location: client/index.php');
    exit();
}

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: dashboard.php");
    exit;
}

$dbAvailable = false;
if (file_exists(__DIR__ . "/config.php")) {
    require_once __DIR__ . "/config.php";
    if (isset($pdo) && $pdo instanceof PDO) $dbAvailable = true;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if ($dbAvailable) {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['role'] === "admin" && password_verify($password, $user['password'])) {
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_username'] = $user['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid admin credentials.";
        }
    } else {
        
        $defaultUser = "admin";
        $defaultPass = "admin123"; 

        if ($username === $defaultUser && $password === $defaultPass) {
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_username'] = $defaultUser;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Incorrect username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Style'n Wear</title>
<link rel="stylesheet" href="style5.css">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

<header>
    <a href="#" class="logo">Style'n Wear</a>

    <nav>
        <a href="#">Home</a>
        <a href="#">About</a>
        <a href="#">Collection</a>
        <a href="#">Contact</a>
    </nav>

    <div class="user-auth">
        <?php if (!empty($name)):?>
        <div class="profile-box">
            <div class="avatar-circle"><?= strtoupper($name[0]); ?></div>
            <div class="dropdown">
                <a href="#">My Account</a>
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </div>
        <?php else: ?>
        <button type="button" class="login-btn-modal">Login</button>
        <?php endif; ?>
    </div>
</header>

<section>
    <h1>Hey <?= $name ?? 'Developer' ?>!</h1>
</section>

<?php if (!empty($alerts)):?>
<div class="alert-box show">
    <?php foreach ($alerts as $alert): ?>
    <div class="alert <?= $alert['type'];?>">
       <i class='bx <?= $alert["type"] === "success" ? "bxs-check-circle" : "bxs-x-circle"; ?>'></i>
        <span><?= $alert['message']; ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="auth-modal <?= $active_form === 'register' ? 'show slide' : ($active_form === 'login' ? 'show' : '') ?>">
    <button type="button" class="close-btn-modal"><i class='bx bx-x'></i></button>

    <div class="form-box login">
        <h2>Login</h2>
        <form action="auth_process.php" method="POST">
            <div class="input-box">
                <input type="email" name="email" placeholder="Email" required>
                <i class='bx bxs-envelope'></i>
            </div>
            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
                <i class='bx bxs-lock'></i>
            </div>
            <button type="submit" name="login_btn" class="btn">Login</button>
            <p>Don't have an account? <a href="#" class="register-link">Register</a></p>
        </form>
    </div>

    <div class="form-box register">
        <h2>Register</h2>
        <form action="auth_process.php" method="POST">
            <div class="input-box">
                <input type="text" name="name" placeholder="Name" required>
                <i class='bx bxs-user'></i>
            </div>
            <div class="input-box">
                <input type="email" name="email" placeholder="Email" required>
                <i class='bx bxs-envelope'></i>
            </div>
            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
                <i class='bx bxs-lock'></i>
            </div>
            <button type="submit" name="register_btn" class="btn">Register</button>
            <p>Already have an account? <a href="#" class="login-link">Login</a></p>
        </form>
    </div>
</div>

<script src="script5.js"></script>
</body>
</html>
