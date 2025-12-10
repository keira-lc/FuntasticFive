<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'stylenwear_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Cannot connect to database: " . $e->getMessage());
}

// Handle settings update
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'update_profile':
            // Update admin profile (simulated)
            $_SESSION['admin_name'] = $_POST['fullname'] ?? $_SESSION['admin_name'];
            $message = 'Profile updated successfully!';
            $messageType = 'success';
            break;
            
        case 'update_store':
            // Update store settings (simulated)
            $message = 'Store settings updated successfully!';
            $messageType = 'success';
            break;
            
        case 'update_password':
            // Update password (simulated)
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $message = 'Password updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Passwords do not match!';
                $messageType = 'danger';
            }
            break;
            
        case 'update_email':
            // Update email (simulated)
            if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $_SESSION['admin_email'] = $_POST['email'];
                $message = 'Email updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Invalid email address!';
                $messageType = 'danger';
            }
            break;
    }
}

// Get current admin data
$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$admin_email = $_SESSION['admin_email'] ?? '';

// Get system info
$php_version = phpversion();
$mysql_version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();

function e($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Style'n Wear</title>
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
        }

        /* Luxury Header */
        .luxury-header {
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.95) 0%, transparent 100%);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--gold);
            box-shadow: 0 5px 25px rgba(212, 175, 55, 0.2);
            padding: 15px 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .brand-title {
            font-family: 'Cinzel', serif;
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 2px;
            position: relative;
            display: inline-block;
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-title::after {
            content: '™';
            font-size: 1rem;
            position: absolute;
            top: 5px;
            right: -15px;
            color: var(--gold);
        }

        .nav-link {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: var(--gold-light) !important;
            margin: 0 15px;
            padding: 10px 20px !important;
            position: relative;
            transition: var(--transition);
        }

        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            transition: width 0.3s ease;
        }

        .nav-link:hover::before {
            width: 80%;
        }

        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }

        .logout-btn {
            background: linear-gradient(45deg, var(--gold-dark), var(--gold));
            color: var(--charcoal) !important;
            padding: 8px 25px !important;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
        }

        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.6);
            color: white !important;
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
        }

        /* Sidebar */
        .admin-sidebar {
            background: linear-gradient(180deg, #0a0a0a 0%, #000 100%);
            width: 250px;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 2px solid var(--gold);
            padding-top: 80px;
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.3);
        }

        .sidebar-logo {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        }

        .sidebar-logo img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            filter: drop-shadow(0 0 10px rgba(212, 175, 55, 0.3));
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-item {
            padding: 15px 25px;
            color: var(--gold-light);
            text-decoration: none;
            display: block;
            transition: var(--transition);
            border-left: 3px solid transparent;
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
        }

        .sidebar-item:hover,
        .sidebar-item.active {
            background: rgba(212, 175, 55, 0.1);
            color: var(--gold);
            border-left-color: var(--gold);
            transform: translateX(5px);
        }

        .sidebar-item i {
            width: 25px;
            margin-right: 10px;
            text-align: center;
        }

        /* Main Content */
        .admin-content {
            margin-left: 250px;
            padding: 100px 30px 30px;
            min-height: 100vh;
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .page-title {
            color: var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: var(--gold-light);
            font-size: 1.1rem;
        }

        /* Settings-specific styles */
        .settings-card {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            transition: var(--transition);
        }

        .settings-card:hover {
            border-color: var(--gold);
            box-shadow: 0 15px 30px rgba(212, 175, 55, 0.1);
        }

        .settings-title {
            color: var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 1.4rem;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-form {
            background: rgba(212, 175, 55, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .form-label {
            color: var(--gold-light);
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        .form-control-luxury {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: var(--off-white);
            padding: 12px 15px;
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
            width: 100%;
            margin-bottom: 15px;
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

        /* Save Button */
        .save-btn {
            background: linear-gradient(45deg, var(--gold-dark), var(--gold));
            color: var(--charcoal);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }

        .save-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.6);
            color: white;
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
        }

        /* System Info */
        .system-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .info-item {
            background: rgba(212, 175, 55, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }

        .info-label {
            color: var(--gold-light);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Alert Messages */
        .alert-luxury {
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid var(--gold);
            color: var(--gold-light);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .alert-luxury.success {
            background: rgba(40, 167, 69, 0.1);
            border-color: #28a745;
            color: #90ee90;
        }

        .alert-luxury.danger {
            background: rgba(220, 53, 69, 0.1);
            border-color: #dc3545;
            color: #ff9999;
        }

        /* Tabs */
        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            padding-bottom: 15px;
        }

        .settings-tab {
            padding: 10px 20px;
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: var(--gold-light);
            border-radius: 20px;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Playfair Display', serif;
        }

        .settings-tab:hover,
        .settings-tab.active {
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            color: var(--charcoal);
            border-color: var(--gold);
        }

        /* Danger Zone */
        .danger-zone {
            background: rgba(220, 53, 69, 0.1);
            border: 2px solid #dc3545;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }

        .danger-zone .settings-title {
            color: #dc3545;
            border-bottom-color: rgba(220, 53, 69, 0.3);
        }

        .danger-btn {
            background: linear-gradient(45deg, #8b0000, #dc3545);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: var(--transition);
        }

        .danger-btn:hover {
            background: linear-gradient(45deg, #dc3545, #ff6b6b);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .admin-sidebar {
                width: 220px;
            }
            .admin-content {
                margin-left: 220px;
            }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                position: static;
                width: 100%;
                min-height: auto;
            }
            .admin-content {
                margin-left: 0;
                padding: 20px;
            }
            .sidebar-logo {
                padding: 20px;
            }
            .sidebar-logo img {
                width: 80px;
                height: 80px;
            }
            .settings-card {
                padding: 15px;
            }
            .system-info {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .luxury-header {
                padding: 10px 0;
            }
            .brand-title {
                font-size: 1.8rem;
            }
            .page-title {
                font-size: 1.6rem;
            }
            .system-info {
                grid-template-columns: 1fr;
            }
            .settings-tabs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Luxury Header -->
    <nav class="luxury-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Brand -->
                <div class="brand-title">
                    Style'n Wear
                </div>
                
                <!-- User Menu -->
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <a href="#" class="nav-link d-flex align-items-center" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2" style="font-size: 1.5rem;"></i>
                            <span><?php echo e($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-dashboard me-2"></i>Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-logo">
            <img src="../image/stylenwear.png" alt="Style'n Wear Logo">
            <div class="mt-3" style="color: var(--gold);">
                <small>ADMIN PANEL</small>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-item">
                <i class="fas fa-dashboard"></i>Dashboard
            </a>
            <a href="users.php" class="sidebar-item">
                <i class="fas fa-users"></i>Users
            </a>
            <a href="orders.php" class="sidebar-item">
                <i class="fas fa-shopping-cart"></i>Orders
            </a>
            <a href="invoice_list.php" class="sidebar-item">
                <i class="fas fa-file-invoice"></i>Invoices
            </a>
            <a href="deliveries.php" class="sidebar-item">
                <i class="fas fa-truck"></i>Deliveries
            </a>
            <a href="products.php" class="sidebar-item">
                <i class="fas fa-tshirt"></i>Products
            </a>
            <a href="reports.php" class="sidebar-item">
                <i class="fas fa-chart-bar"></i>Reports
            </a>
            <a href="settings.php" class="sidebar-item active">
                <i class="fas fa-cog"></i>Settings
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="page-title">Settings</h1>
                        <p class="page-subtitle">Manage your admin panel and store settings</p>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end gap-3">
                            <button class="btn logout-btn" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert-luxury <?php echo e($messageType); ?>">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo e($message); ?>
            </div>
            <?php endif; ?>

            <!-- Settings Tabs -->
            <div class="settings-tabs">
                <div class="settings-tab active" onclick="showTab('profile')">
                    <i class="fas fa-user me-2"></i>Profile
                </div>
                <div class="settings-tab" onclick="showTab('store')">
                    <i class="fas fa-store me-2"></i>Store Settings
                </div>
                <div class="settings-tab" onclick="showTab('security')">
                    <i class="fas fa-shield-alt me-2"></i>Security
                </div>
                <div class="settings-tab" onclick="showTab('system')">
                    <i class="fas fa-server me-2"></i>System Info
                </div>
            </div>

            <!-- Profile Settings -->
            <div class="settings-card" id="profile-tab">
                <h3 class="settings-title">
                    <i class="fas fa-user"></i>Admin Profile
                </h3>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="fullname" class="form-control-luxury" 
                               value="<?php echo e($admin_name); ?>" 
                               placeholder="Enter your full name">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control-luxury" 
                               value="<?php echo e($admin_email); ?>" 
                               placeholder="Enter your email address">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Admin ID</label>
                        <input type="text" class="form-control-luxury" 
                               value="#<?php echo e($admin_id); ?>" 
                               disabled>
                        <small class="text-muted">Admin ID cannot be changed</small>
                    </div>
                    
                    <button type="submit" class="save-btn">
                        <i class="fas fa-save"></i>Update Profile
                    </button>
                </form>
            </div>

            <!-- Store Settings -->
            <div class="settings-card" id="store-tab" style="display: none;">
                <h3 class="settings-title">
                    <i class="fas fa-store"></i>Store Settings
                </h3>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_store">
                    
                    <div class="mb-3">
                        <label class="form-label">Store Name</label>
                        <input type="text" name="store_name" class="form-control-luxury" 
                               value="Style'n Wear" 
                               placeholder="Enter store name">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Store Email</label>
                        <input type="email" name="store_email" class="form-control-luxury" 
                               value="info@stylenwear.com" 
                               placeholder="Enter store email">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Store Phone</label>
                        <input type="tel" name="store_phone" class="form-control-luxury" 
                               value="(123) 456-7890" 
                               placeholder="Enter store phone">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Store Address</label>
                        <textarea name="store_address" class="form-control-luxury" 
                                  rows="3" 
                                  placeholder="Enter store address">123 Fashion Street, Ligao City, Albay 4501, Philippines</textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Currency</label>
                        <select name="currency" class="form-control-luxury">
                            <option value="PHP" selected>Philippine Peso (₱)</option>
                            <option value="USD">US Dollar ($)</option>
                            <option value="EUR">Euro (€)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="save-btn">
                        <i class="fas fa-save"></i>Update Store Settings
                    </button>
                </form>
            </div>

            <!-- Security Settings -->
            <div class="settings-card" id="security-tab" style="display: none;">
                <h3 class="settings-title">
                    <i class="fas fa-shield-alt"></i>Security Settings
                </h3>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_password">
                    
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control-luxury" 
                               placeholder="Enter current password">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control-luxury" 
                               placeholder="Enter new password">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control-luxury" 
                               placeholder="Confirm new password">
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="showPassword">
                        <label class="form-check-label" for="showPassword">Show passwords</label>
                    </div>
                    
                    <button type="submit" class="save-btn">
                        <i class="fas fa-key"></i>Change Password
                    </button>
                </form>
            </div>

            <!-- System Info -->
            <div class="settings-card" id="system-tab" style="display: none;">
                <h3 class="settings-title">
                    <i class="fas fa-server"></i>System Information
                </h3>
                
                <div class="system-info">
                    <div class="info-item">
                        <div class="info-label">PHP Version</div>
                        <div class="info-value"><?php echo e($php_version); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">MySQL Version</div>
                        <div class="info-value"><?php echo e($mysql_version); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Total Users</div>
                        <div class="info-value"><?php echo e($total_users); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Total Orders</div>
                        <div class="info-value"><?php echo e($total_orders); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Total Products</div>
                        <div class="info-value"><?php echo e($total_products); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Server Time</div>
                        <div class="info-value"><?php echo date('H:i:s'); ?></div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button class="btn logout-btn" onclick="refreshSystemInfo()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh Info
                    </button>
                    <button class="btn logout-btn ms-2" onclick="clearCache()">
                        <i class="fas fa-broom me-2"></i>Clear Cache
                    </button>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="danger-zone">
                <h3 class="settings-title">
                    <i class="fas fa-exclamation-triangle"></i>Danger Zone
                </h3>
                <p class="text-muted mb-4">These actions are irreversible. Please proceed with caution.</p>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <button class="btn danger-btn w-100" onclick="clearAllData()">
                            <i class="fas fa-trash me-2"></i>Clear Test Data
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn danger-btn w-100" onclick="resetDatabase()">
                            <i class="fas fa-database me-2"></i>Reset Database
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn danger-btn w-100" onclick="deleteAdminAccount()">
                            <i class="fas fa-user-slash me-2"></i>Delete Admin Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab navigation
        function showTab(tabName) {
            // Hide all tabs
            document.getElementById('profile-tab').style.display = 'none';
            document.getElementById('store-tab').style.display = 'none';
            document.getElementById('security-tab').style.display = 'none';
            document.getElementById('system-tab').style.display = 'none';
            
            // Show selected tab
            document.getElementById(tabName + '-tab').style.display = 'block';
            
            // Update active tab
            const tabs = document.querySelectorAll('.settings-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
        }

        // Show/hide password
        document.getElementById('showPassword').addEventListener('change', function() {
            const passwords = document.querySelectorAll('input[type="password"]');
            passwords.forEach(password => {
                password.type = this.checked ? 'text' : 'password';
            });
        });

        // Danger zone functions
        function clearAllData() {
            if (confirm('Are you sure you want to clear all test data? This action cannot be undone.')) {
                alert('Test data cleared successfully!');
                // In real app, this would call an API endpoint
            }
        }

        function resetDatabase() {
            if (confirm('WARNING: This will reset the entire database to default. Are you absolutely sure?')) {
                const confirmReset = prompt('Type "RESET" to confirm database reset:');
                if (confirmReset === 'RESET') {
                    alert('Database reset initiated!');
                    // In real app, this would call an API endpoint
                }
            }
        }

        function deleteAdminAccount() {
            if (confirm('This will delete your admin account permanently. You will lose all access. Continue?')) {
                const confirmDelete = prompt('Type "DELETE" to confirm account deletion:');
                if (confirmDelete === 'DELETE') {
                    alert('Admin account deletion requested!');
                    // In real app, this would call an API endpoint
                }
            }
        }

        // Utility functions
        function refreshSystemInfo() {
            window.location.reload();
        }

        function clearCache() {
            alert('Cache cleared successfully!');
            // In real app, this would call an API endpoint
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>