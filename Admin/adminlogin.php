<?php
session_start();

// If already logged in, go directly to dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Optional DB connection
$dbAvailable = false;
if (file_exists(__DIR__ . "/config.php")) {
    require_once __DIR__ . "/config.php";
    if (isset($pdo) && $pdo instanceof PDO) $dbAvailable = true;
}

$error = "";

// Handle login submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if ($dbAvailable) {
        // Get admin from database
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
        // Fallback admin login (NO DATABASE)
        $defaultUser = "admin";
        $defaultPass = "admin123"; // CHANGE THIS!

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
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: #f5f6f7;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
.login-card {
    width: 360px;
}
</style>
</head>
<body>

<div class="card login-card shadow">
    <div class="card-body">
        <h3 class="text-center mb-3">Admin Login</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input name="username" type="text" class="form-control" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input name="password" type="password" class="form-control" required>
            </div>

            <button class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</div>

</body>
</html>
