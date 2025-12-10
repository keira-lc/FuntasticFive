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
    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $pdo = null;
}

// Helper to fetch a single integer metric from DB safely
function fetchCountOrFallback($pdo = null, $sql = '', $fallback = 0) {
    if ($pdo === null || empty($sql)) return $fallback;
    try {
        $stmt = $pdo->query($sql);
        $val = $stmt->fetchColumn();
        return (int)$val;
    } catch (Throwable $e) {
        return $fallback;
    }
}

// Fetch dashboard statistics
if ($dbConnected) {
    $totalUsers = fetchCountOrFallback($pdo, "SELECT COUNT(*) FROM users", 0);
    $activeUsers = fetchCountOrFallback($pdo, "SELECT COUNT(*) FROM users", 0);
    $totalOrders = fetchCountOrFallback($pdo, "SELECT COUNT(*) FROM orders WHERE status != 'cancelled'", 0);
    $totalProducts = fetchCountOrFallback($pdo, "SELECT COUNT(*) FROM items", 0);
    $totalRevenue = fetchCountOrFallback($pdo, "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('completed', 'paid', 'shipped')", 0);
    $pendingOrders = fetchCountOrFallback($pdo, "SELECT COUNT(*) FROM orders WHERE status = 'pending'", 0);
} else {
    $totalUsers = 124;
    $activeUsers = 98;
    $totalOrders = 42;
    $totalProducts = 25;
    $totalRevenue = 125000;
    $pendingOrders = 7;
}

// Load recent users
$usersRows = [];
if ($dbConnected) {
    try {
        $stmt = $pdo->query("SELECT user_id as id, fullname as username, email, date_joined as created_at FROM users ORDER BY date_joined DESC LIMIT 8");
        $usersRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $usersRows = [];
    }
}

// Load recent orders
$ordersRows = [];
if ($dbConnected) {
    try {
        $stmt = $pdo->query("SELECT o.order_id, u.fullname, o.total_amount, o.status, o.placed_at FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.placed_at DESC LIMIT 8");
        $ordersRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $ordersRows = [];
    }
}

// Monthly data for chart
$monthlyData = array_fill(0, 12, 0);
if ($dbConnected) {
    try {
        $sql = "SELECT MONTH(date_joined) as month_number, COUNT(*) as user_count FROM users GROUP BY MONTH(date_joined) ORDER BY month_number";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as $row) {
            $monthIndex = $row['month_number'] - 1;
            $monthlyData[$monthIndex] = (int)$row['user_count'];
        }
    } catch (Throwable $e) {
        // Keep zeros if error
    }
}

// Convert PHP array to JavaScript format
$jsMonthlyData = json_encode($monthlyData);

function e($s) { 
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Style'n Wear - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/jpg" href="../image/stylenwear.png">
    <link rel="stylesheet" href=../css/dashboard-style.css>
    <style>
        .admin-content .table {
            --bs-table-bg: transparent !important;
            --bs-table-color: #f8f5f0 !important;
            --bs-table-border-color: rgba(212, 175, 55, 0.2) !important;
        }
        
        .admin-content .table th,
        .admin-content .table td {
            background-color: transparent !important;
            color: #f8f5f0 !important;
            border-color: rgba(212, 175, 55, 0.2) !important;
        }
        
        .admin-content .table thead th {
            background-color: rgba(212, 175, 55, 0.15) !important;
            color: #d4af37 !important;
        }
        
        .admin-content .table tbody tr:hover {
            background-color: rgba(212, 175, 55, 0.05) !important;
        }
        
        .admin-content .text-muted {
            color: #888 !important;
        }
        
        .admin-content .text-gold {
            color: #d4af37 !important;
        }
        
        .admin-content .badge {
            background-color: #1a1a1a !important;
            color: #f4e4a6 !important;
        }
        
        .admin-content canvas {
            background: rgba(30, 30, 30, 0.3) !important;
            border-radius: 10px;
            padding: 10px;
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
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
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
            <div class="mt-3 text-gold" style="color: var(--gold);">
                <small>ADMIN PANEL</small>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-item active">
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
            <a href="settings.php" class="sidebar-item">
                <i class="fas fa-cog"></i>Settings
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col">
                    <h2 style="color: var(--gold); font-family: 'Cinzel', serif;">Admin Dashboard</h2>
                    <p style="color: var(--gold-light);">Welcome back, <?php echo e($_SESSION['admin_name'] ?? 'Administrator'); ?> — here's what's happening with your store.</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-title">Total Users</div>
                        <div class="card-value"><?php echo e($totalUsers); ?></div>
                        <div class="card-growth">All registered accounts</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="card-title">Total Orders</div>
                        <div class="card-value"><?php echo e($totalOrders); ?></div>
                        <div class="card-growth">All time orders</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="card-title">Total Revenue</div>
                        <div class="card-value">₱<?php echo e(number_format($totalRevenue, 2)); ?></div>
                        <div class="card-growth">Total sales</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="card-title">Pending Orders</div>
                        <div class="card-value"><?php echo e($pendingOrders); ?></div>
                        <div class="card-growth">Awaiting processing</div>
                    </div>
                </div>
            </div>

            <!-- Charts and Tables -->
            <div class="row g-4">
                <!-- Left Column - Chart -->
                <div class="col-lg-8">
                    <div class="chart-card">
                        <div class="chart-title">Monthly User Registrations</div>
                        <canvas id="usersChart" height="250"></canvas>
                    </div>
                </div>

                <!-- Right Column - Recent Orders -->
                <div class="col-lg-4">
                    <div class="chart-card">
                        <div class="chart-title">Recent Orders</div>
                        <div class="table-responsive">
                            <table class="table admin-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ordersRows as $order): ?>
                                    <tr>
                                        <td>#<?php echo e($order['order_id']); ?></td>
                                        <td><?php echo e(substr($order['fullname'], 0, 15)); ?>...</td>
                                        <td>₱<?php echo e(number_format($order['total_amount'], 2)); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo e($order['status']); ?>">
                                                <?php echo e(ucfirst($order['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Users Table -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="chart-card">
                        <div class="chart-title">Recent Users</div>
                        <div class="table-responsive">
                            <table class="table admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usersRows as $user): ?>
                                    <tr>
                                        <td>#<?php echo e($user['id']); ?></td>
                                        <td><?php echo e($user['username']); ?></td>
                                        <td><?php echo e($user['email']); ?></td>
                                        <td><?php echo e(date('M d, Y', strtotime($user['created_at']))); ?></td>
                                        <td>
                                            <button class="btn btn-sm" style="background: rgba(212, 175, 55, 0.1); color: var(--gold); border: 1px solid rgba(212, 175, 55, 0.3);">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="mt-5 pt-4 border-top" style="border-color: rgba(212, 175, 55, 0.2);">
                <div class="d-flex justify-content-between" style="color: var(--gold-light); font-size: 0.9rem;">
                    <div>© <?php echo date('Y'); ?> Style'n Wear Admin Panel</div>
                    <div>Server Time: <?php echo date('Y-m-d H:i:s'); ?></div>
                </div>
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <script>
        // User Registration Chart
        const ctx = document.getElementById('usersChart').getContext('2d');
        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        const data = {
            labels,
            datasets: [{
                label: 'User Registrations',
                data: <?php echo $jsMonthlyData; ?>,
                fill: true,
                tension: 0.4,
                borderColor: '#d4af37',
                backgroundColor: 'rgba(212, 175, 55, 0.1)',
                pointBackgroundColor: '#d4af37',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        };
        
        const config = {
            type: 'line',
            data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 30, 30, 0.9)',
                        titleColor: '#d4af37',
                        bodyColor: '#f8f5f0',
                        borderColor: '#d4af37',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return `Users: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#888',
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(212, 175, 55, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#888'
                        },
                        grid: {
                            color: 'rgba(212, 175, 55, 0.1)'
                        }
                    }
                }
            }
        };
        
        new Chart(ctx, config);
    </script>
</body>
</html>