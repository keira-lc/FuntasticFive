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
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Cannot connect to database: " . $e->getMessage());
}

// Get search and filter values
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Prepare SQL query
$sql = "SELECT 
            o.order_id,
            o.total_amount,
            o.currency,
            o.status as order_status,
            o.placed_at as invoice_date,
            u.fullname,
            u.email,
            COUNT(oi.order_item_id) as item_count
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE 1=1";

$params = [];

// Add search conditions
if (!empty($search)) {
    $sql .= " AND (u.fullname LIKE ? OR u.email LIKE ? OR o.order_id LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search;
}

// Add status filter
if (!empty($status) && $status != 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status;
}

// Group by and order
$sql .= " GROUP BY o.order_id ORDER BY o.placed_at DESC LIMIT 50";

// Execute query
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$totalInvoices = count($invoices);
$totalRevenue = array_sum(array_column($invoices, 'total_amount'));
$pendingInvoices = count(array_filter($invoices, function($inv) {
    return $inv['order_status'] == 'pending';
}));

// Generate invoice numbers
foreach ($invoices as &$invoice) {
    $invoice['invoice_number'] = "INV-" . date('mdY', strtotime($invoice['invoice_date'])) . "-" . $invoice['order_id'];
}
unset($invoice);

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
    <title>Invoice Management - Style'n Wear</title>
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

        /* Stats Cards */
        .stats-card {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: var(--transition);
            height: 100%;
        }

        .stats-card:hover {
            border-color: var(--gold);
            transform: translateY(-5px);
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
            font-size: 2rem;
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

        /* Invoice Cards */
        .invoice-card {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .invoice-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold);
            box-shadow: 0 15px 30px rgba(212, 175, 55, 0.2);
            cursor: pointer;
        }

        .invoice-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--gold), var(--gold-light));
        }

        .invoice-number {
            color: var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .invoice-order {
            color: #888;
            font-size: 0.9rem;
        }

        .customer-name {
            color: white;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .customer-email {
            color: var(--gold-light);
            font-size: 0.9rem;
        }

        .invoice-date {
            color: #aaa;
            font-size: 0.9rem;
        }

        .invoice-amount {
            color: white;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .item-count {
            color: var(--gold);
            font-size: 0.9rem;
            font-weight: 600;
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

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 8px 20px;
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: var(--gold-light);
            border-radius: 20px;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Playfair Display', serif;
        }

        .filter-tab:hover,
        .filter-tab.active {
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            color: var(--charcoal);
            border-color: var(--gold);
        }

        /* Search Box */
        .search-box {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 25px;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            transition: var(--transition);
            margin-bottom: 20px;
        }

        .search-box:focus-within {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        .search-box input,
        .search-box select {
            background: transparent;
            border: none;
            color: var(--off-white);
            flex: 1;
            outline: none;
            padding: 5px;
        }

        .search-box select option {
            background: #1e1e1e;
            color: var(--off-white);
        }

        .search-box i {
            color: var(--gold);
            margin-right: 10px;
        }

        /* View Button */
        .view-btn {
            background: linear-gradient(45deg, var(--gold-dark), var(--gold));
            color: var(--charcoal);
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .view-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
            color: white;
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gold-light);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gold);
            margin-bottom: 20px;
            opacity: 0.5;
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
            .filter-tabs {
                justify-content: center;
            }
            .invoice-card {
                padding: 15px;
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
            .stats-value {
                font-size: 1.6rem;
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
            <a href="invoice_list.php" class="sidebar-item active">
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
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="page-title">Invoice Management</h1>
                        <p class="page-subtitle">View and manage all customer invoices</p>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end gap-3">
                            <a href="orders.php" class="btn logout-btn">
                                <i class="fas fa-shopping-cart me-2"></i>Orders
                            </a>
                            <button class="btn logout-btn" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Search Form -->
                <form method="GET" class="mt-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" 
                                       placeholder="Search by name, email, or order ID..."
                                       value="<?php echo e($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="search-box">
                                <i class="fas fa-filter"></i>
                                <select name="status" class="form-select bg-transparent border-0">
                                    <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="shipped" <?php echo $status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn logout-btn w-100">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="stats-value"><?php echo e($totalInvoices); ?></div>
                        <div class="stats-label">Total Invoices</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stats-value"><?php echo e($pendingInvoices); ?></div>
                        <div class="stats-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stats-value">₱<?php echo e(number_format($totalRevenue, 2)); ?></div>
                        <div class="stats-label">Total Revenue</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stats-value"><?php echo e(date('Y')); ?></div>
                        <div class="stats-label">This Year</div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab <?php echo empty($status) || $status == 'all' ? 'active' : ''; ?>" 
                     onclick="window.location='invoice_list.php'">All Invoices</div>
                <div class="filter-tab <?php echo $status == 'pending' ? 'active' : ''; ?>" 
                     onclick="window.location='invoice_list.php?status=pending'">Pending</div>
                <div class="filter-tab <?php echo $status == 'paid' ? 'active' : ''; ?>" 
                     onclick="window.location='invoice_list.php?status=paid'">Paid</div>
                <div class="filter-tab <?php echo $status == 'shipped' ? 'active' : ''; ?>" 
                     onclick="window.location='invoice_list.php?status=shipped'">Shipped</div>
                <div class="filter-tab <?php echo $status == 'completed' ? 'active' : ''; ?>" 
                     onclick="window.location='invoice_list.php?status=completed'">Completed</div>
                <div class="filter-tab <?php echo $status == 'cancelled' ? 'active' : ''; ?>" 
                     onclick="window.location='invoice_list.php?status=cancelled'">Cancelled</div>
            </div>

            <!-- Invoice Cards -->
            <?php if (empty($invoices)): ?>
            <div class="empty-state">
                <i class="fas fa-file-invoice"></i>
                <h3>No invoices found</h3>
                <p>Try changing your search criteria or create new invoices</p>
            </div>
            <?php else: ?>
            <?php foreach ($invoices as $invoice): 
                $statusClass = getStatusBadge($invoice['order_status']);
                $invoiceDate = date('M d, Y', strtotime($invoice['invoice_date']));
                $invoiceTime = date('h:i A', strtotime($invoice['invoice_date']));
            ?>
            <div class="invoice-card" onclick="window.location='invoice_view.php?id=<?php echo e($invoice['order_id']); ?>'">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <div class="invoice-number"><?php echo e($invoice['invoice_number']); ?></div>
                        <div class="invoice-order">Order #<?php echo e($invoice['order_id']); ?></div>
                        <span class="item-count">
                            <i class="fas fa-box"></i> <?php echo e($invoice['item_count']); ?> items
                        </span>
                    </div>
                    <div class="col-md-3">
                        <div class="customer-name"><?php echo e($invoice['fullname']); ?></div>
                        <div class="customer-email"><?php echo e($invoice['email']); ?></div>
                    </div>
                    <div class="col-md-2">
                        <div class="invoice-date">
                            <i class="fas fa-calendar me-1"></i> <?php echo e($invoiceDate); ?>
                        </div>
                        <small class="text-muted"><?php echo e($invoiceTime); ?></small>
                    </div>
                    <div class="col-md-2">
                        <div class="invoice-amount text-gold">
                            ₱<?php echo e(number_format($invoice['total_amount'], 2)); ?>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <span class="status-badge <?php echo e($statusClass); ?>">
                            <?php echo e(ucfirst($invoice['order_status'])); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Footer -->
            <footer class="mt-5 pt-4 border-top" style="border-color: rgba(212, 175, 55, 0.2);">
                <div class="d-flex justify-content-between align-items-center" style="color: var(--gold-light); font-size: 0.9rem;">
                    <div>© <?php echo date('Y'); ?> Style'n Wear Admin Panel</div>
                    <div>
                        Showing <?php echo count($invoices); ?> invoice(s)
                    </div>
                </div>
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Make invoice cards clickable
        document.querySelectorAll('.invoice-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (!e.target.closest('a, button, .dropdown')) {
                    window.location = this.getAttribute('onclick').replace("window.location='", "").replace("'", "");
                }
            });
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>