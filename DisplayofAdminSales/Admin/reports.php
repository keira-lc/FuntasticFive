<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Database connection
$dbAvailable = false;
$itemsToSell = $itemsOrdered = $adminSales = $inventory = [];
$totalRevenue = $totalOrders = $totalCustomers = $totalProducts = 0;

try {
    $host = 'localhost';
    $dbname = 'stylenwear_db';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbAvailable = true;
    
    // Fetch statistics
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('completed', 'shipped')")->fetchColumn();
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'")->fetchColumn();
    $totalCustomers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
    
    // Fetch Items to Sell (all items with stock)
    $stmt = $pdo->query("
        SELECT item_name, item_price, stock, image_url, item_sku
        FROM items 
        WHERE stock > 0 
        ORDER BY date_added DESC
        LIMIT 20
    ");
    $itemsToSell = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch Items Ordered (EXCLUDE CANCELLED ORDERS)
    $stmt = $pdo->query("
        SELECT oi.order_id, i.item_name, oi.quantity, o.placed_at as order_date, o.status,
               u.fullname, o.total_amount
        FROM order_items oi
        JOIN items i ON oi.item_id = i.item_id
        JOIN orders o ON oi.order_id = o.order_id
        JOIN users u ON o.user_id = u.user_id
        WHERE o.status != 'cancelled'
        ORDER BY o.placed_at DESC
        LIMIT 15
    ");
    $itemsOrdered = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch Sales Data for Chart
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(o.placed_at, '%Y-%m') as month,
            COUNT(o.order_id) as order_count,
            SUM(o.total_amount) as revenue
        FROM orders o
        WHERE o.status IN ('completed', 'shipped', 'paid')
        AND o.placed_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(o.placed_at, '%Y-%m')
        ORDER BY month
    ");
    $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare data for chart
    $chartMonths = [];
    $chartOrders = [];
    $chartRevenue = [];
    foreach ($salesData as $data) {
        $chartMonths[] = date('M Y', strtotime($data['month'] . '-01'));
        $chartOrders[] = (int)$data['order_count'];
        $chartRevenue[] = (float)$data['revenue'];
    }
    
    // Fetch Inventory Report
    $stmt = $pdo->query("
        SELECT 
            item_name as item,
            stock,
            (SELECT COALESCE(SUM(oi.quantity), 0) 
             FROM order_items oi 
             JOIN orders o ON oi.order_id = o.order_id 
             WHERE oi.item_id = i.item_id 
             AND o.status IN ('completed', 'shipped', 'paid')
             AND o.status != 'cancelled') as sold,
            item_price,
            (stock * item_price) as stock_value
        FROM items i
        ORDER BY stock_value DESC
    ");
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate inventory stats
    $totalStockValue = array_sum(array_column($inventory, 'stock_value'));
    $totalItemsSold = array_sum(array_column($inventory, 'sold'));
    $lowStockItems = count(array_filter($inventory, function($item) {
        return $item['stock'] > 0 && $item['stock'] <= 10;
    }));
    
} catch(PDOException $e) {
    $dbAvailable = false;
    error_log("Database error: " . $e->getMessage());
}

function e($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}

// Function to get status badge class
function getStatusBadge($status) {
    $classes = [
        'pending' => 'status-pending',
        'paid' => 'status-paid',
        'shipped' => 'status-shipped',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled'
    ];
    return $classes[$status] ?? 'status-pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - Style'n Wear</title>
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
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            color: var(--off-white);
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

        /* Stats Cards */
        .stats-card {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: var(--transition);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stats-card:hover {
            border-color: var(--gold);
            transform: translateY(-5px);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, var(--gold-dark), var(--gold));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--charcoal);
            font-size: 1.5rem;
        }

        .stats-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }

        .stats-label {
            color: var(--gold-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Report Cards */
        .report-card {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            transition: var(--transition);
        }

        .report-card:hover {
            border-color: var(--gold);
            box-shadow: 0 15px 30px rgba(212, 175, 55, 0.1);
        }

        .report-title {
            color: var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
        }

        /* OVERRIDE BOOTSTRAP TABLE STYLES - THIS IS THE FIX */
        .report-table {
            background: transparent !important;
            border: 1px solid rgba(212, 175, 55, 0.2) !important;
            border-radius: 10px !important;
            overflow: hidden !important;
            margin-bottom: 0 !important;
        }
        
        .report-table.table {
            --bs-table-bg: transparent !important;
            --bs-table-striped-bg: rgba(212, 175, 55, 0.05) !important;
            --bs-table-striped-color: var(--off-white) !important;
            --bs-table-active-bg: rgba(212, 175, 55, 0.1) !important;
            --bs-table-active-color: var(--off-white) !important;
            --bs-table-hover-bg: rgba(212, 175, 55, 0.05) !important;
            --bs-table-hover-color: var(--off-white) !important;
            color: var(--off-white) !important;
            border-color: rgba(212, 175, 55, 0.2) !important;
            background-color: rgba(30, 30, 30, 0.5) !important;
        }
        
        .report-table.table > :not(caption) > * > * {
            background-color: transparent !important;
            color: var(--off-white) !important;
            border-bottom-color: rgba(212, 175, 55, 0.2) !important;
        }
        
        .report-table.table > thead {
            vertical-align: bottom !important;
        }
        
        .report-table.table > thead > tr > th {
            background: rgba(212, 175, 55, 0.15) !important;
            color: var(--gold) !important;
            font-family: 'Cinzel', serif !important;
            padding: 15px !important;
            border-bottom: 2px solid var(--gold) !important;
            font-weight: 600 !important;
            border-bottom-width: 2px !important;
        }
        
        .report-table.table > tbody > tr > td {
            color: var(--off-white) !important;
            padding: 15px !important;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2) !important;
            vertical-align: middle !important;
            background-color: transparent !important;
        }
        
        .report-table.table > tbody > tr:hover > * {
            background: rgba(212, 175, 55, 0.05) !important;
            color: var(--off-white) !important;
        }

        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-paid {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-shipped {
            background: rgba(13, 202, 240, 0.2);
            color: #0dcaf0;
            border: 1px solid rgba(13, 202, 240, 0.3);
        }

        .status-completed {
            background: rgba(13, 110, 253, 0.2);
            color: #0d6efd;
            border: 1px solid rgba(13, 110, 253, 0.3);
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        /* Chart Container */
        .chart-container {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }

        /* Stock Badges */
        .stock-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .stock-good {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .stock-warning {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .stock-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        /* Action Buttons */
        .action-btn {
            background: linear-gradient(45deg, var(--gold-dark), var(--gold));
            color: var(--charcoal);
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.6);
            color: white;
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
        }

        /* Text Colors */
        .text-gold {
            color: var(--gold) !important;
        }
        
        .text-muted {
            color: #888 !important;
        }
        
        /* Custom Badge Styles */
        .custom-badge-dark {
            background-color: var(--charcoal) !important;
            color: var(--gold-light) !important;
            border: 1px solid var(--gold);
        }
        
        .custom-badge-warning {
            background-color: rgba(212, 175, 55, 0.3) !important;
            color: var(--gold) !important;
            border: 1px solid var(--gold);
        }
        
        .custom-badge-success {
            background-color: rgba(40, 167, 69, 0.3) !important;
            color: #28a745 !important;
            border: 1px solid #28a745;
        }
        
        .custom-badge-primary {
            background-color: rgba(13, 110, 253, 0.3) !important;
            color: #0d6efd !important;
            border: 1px solid #0d6efd;
        }
        
        /* Footer */
        footer {
            border-color: rgba(212, 175, 55, 0.2) !important;
            color: var(--gold-light) !important;
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
            .stats-value {
                font-size: 1.8rem;
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
            .report-card {
                padding: 15px;
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
            <a href="reports.php" class="sidebar-item active">
                <i class="fas fa-chart-bar"></i>Analytics
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
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="page-title">Analytics & Reports</h1>
                        <p class="page-subtitle">Business intelligence and performance metrics</p>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end gap-3">
                            <button class="btn action-btn" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                            <button class="btn action-btn" onclick="exportToExcel()">
                                <i class="fas fa-file-excel me-2"></i>Export Excel
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Database Status -->
                <div class="mt-4">
                    <?php if($dbAvailable): ?>
                        <span class="badge custom-badge-success px-3 py-2">
                            <i class="fas fa-database me-2"></i>Database Connected - Real-time Data
                        </span>
                    <?php else: ?>
                        <span class="badge custom-badge-warning px-3 py-2">
                            <i class="fas fa-exclamation-triangle me-2"></i>Database Connection Failed
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stats-value">₱<?php echo number_format($totalRevenue, 2); ?></div>
                        <div class="stats-label">Total Revenue</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stats-value"><?php echo e($totalOrders); ?></div>
                        <div class="stats-label">Active Orders</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-value"><?php echo e($totalCustomers); ?></div>
                        <div class="stats-label">Total Customers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stats-value"><?php echo e($totalProducts); ?></div>
                        <div class="stats-label">Products</div>
                    </div>
                </div>
            </div>

            <!-- Sales Chart -->
            <div class="chart-container">
                <h5 class="report-title">Sales Trend (Last 6 Months)</h5>
                <canvas id="salesChart" height="100"></canvas>
            </div>

            <!-- Inventory Report -->
            <div class="report-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="report-title m-0">Inventory Analysis</h5>
                    <div>
                        <span class="badge custom-badge-dark px-3 py-2 me-2">
                            Stock Value: ₱<?php echo number_format($totalStockValue, 2); ?>
                        </span>
                        <span class="badge custom-badge-warning px-3 py-2">
                            Low Stock Items: <?php echo e($lowStockItems); ?>
                        </span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table report-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Current Stock</th>
                                <th>Total Sold</th>
                                <th>Unit Price</th>
                                <th>Stock Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($inventory)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-box-open fa-2x mb-3"></i><br>
                                        No inventory data available
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inventory as $item): 
                                    $stockClass = $item['stock'] > 10 ? 'stock-good' : 
                                                 ($item['stock'] > 0 ? 'stock-warning' : 'stock-danger');
                                    $stockText = $item['stock'] > 10 ? 'Good' : 
                                                ($item['stock'] > 0 ? 'Low Stock' : 'Out of Stock');
                                ?>
                                <tr>
                                    <td><?php echo e($item['item']); ?></td>
                                    <td>
                                        <span class="badge custom-badge-dark"><?php echo e($item['stock']); ?></span>
                                    </td>
                                    <td><?php echo e($item['sold']); ?></td>
                                    <td>₱<?php echo number_format($item['item_price'], 2); ?></td>
                                    <td>
                                        <span class="text-gold">₱<?php echo number_format($item['stock_value'], 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="stock-badge <?php echo e($stockClass); ?>">
                                            <?php echo e($stockText); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="report-card">
                <h5 class="report-title">Recent Orders</h5>
                <div class="table-responsive">
                    <table class="table report-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($itemsOrdered)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-shopping-cart fa-2x mb-3"></i><br>
                                        No active orders found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($itemsOrdered as $order): ?>
                                <tr>
                                    <td>
                                        <strong class="text-gold">#<?php echo e($order['order_id']); ?></strong>
                                    </td>
                                    <td><?php echo e($order['fullname']); ?></td>
                                    <td><?php echo e($order['item_name']); ?></td>
                                    <td>
                                        <span class="badge custom-badge-primary"><?php echo e($order['quantity']); ?></span>
                                    </td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo getStatusBadge($order['status']); ?>">
                                            <?php echo ucfirst(e($order['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <footer class="mt-5 pt-4 border-top" style="border-color: rgba(212, 175, 55, 0.2);">
                <div class="d-flex justify-content-between align-items-center" style="color: var(--gold-light); font-size: 0.9rem;">
                    <div>© <?php echo date('Y'); ?> Style'n Wear Analytics Dashboard</div>
                    <div>
                        <span class="me-3">Report Generated: <?php echo date('F d, Y h:i A'); ?></span>
                        <?php if($dbAvailable): ?>
                            <span class="badge custom-badge-success">Live Data</span>
                        <?php endif; ?>
                    </div>
                </div>
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Export to Excel (dummy function - in real app would generate CSV)
        function exportToExcel() {
            alert('Export to Excel would be implemented here. In a real application, this would generate a CSV file.');
        }

        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const months = <?php echo json_encode($chartMonths); ?>;
        const orders = <?php echo json_encode($chartOrders); ?>;
        const revenue = <?php echo json_encode($chartRevenue); ?>;

        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Orders',
                        data: orders,
                        borderColor: '#d4af37',
                        backgroundColor: 'rgba(212, 175, 55, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Revenue (PHP)',
                        data: revenue,
                        borderColor: '#0dcaf0',
                        backgroundColor: 'rgba(13, 202, 240, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                stacked: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#f8f5f0'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 30, 30, 0.9)',
                        titleColor: '#d4af37',
                        bodyColor: '#f8f5f0',
                        borderColor: '#d4af37',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#888'
                        },
                        grid: {
                            color: 'rgba(212, 175, 55, 0.1)'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            color: '#888'
                        },
                        grid: {
                            color: 'rgba(212, 175, 55, 0.1)'
                        },
                        title: {
                            display: true,
                            text: 'Orders',
                            color: '#d4af37'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: {
                            color: '#888',
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: 'Revenue (PHP)',
                            color: '#0dcaf0'
                        }
                    }
                }
            }
        });

        // Auto-refresh data every 5 minutes
        setTimeout(function() {
            window.location.reload();
        }, 300000); // 5 minutes

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>