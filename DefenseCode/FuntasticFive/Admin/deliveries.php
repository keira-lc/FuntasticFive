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

// Handle delivery actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $delivery_id = $_POST['order_id'];
    $action = $_POST['action'];
    
    switch($action) {
        case 'track':
            // In real app, this would update tracking info
            break;
            
        case 'ship':
            $stmt = $pdo->prepare("UPDATE orders SET status = 'shipped' WHERE order_id = ?");
            $stmt->execute([$delivery_id]);
            break;
            
        case 'complete':
            $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE order_id = ?");
            $stmt->execute([$delivery_id]);
            break;
            
        case 'update_location':
            // In real app, this would update delivery location
            break;
    }
    
    header("Location: deliveries.php");
    exit();
}

// Fetch deliveries
$sql = "SELECT 
            o.order_id,
            o.total_amount,
            o.status,
            o.placed_at,
            u.fullname,
            u.email,
            sa.recipient_name,
            sa.address_line,
            sa.city,
            sa.province,
            sa.postal_code,
            sa.phone,
            COUNT(oi.order_item_id) as item_count
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        LEFT JOIN shipping_addresses sa ON o.shipping_address_id = sa.address_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.status IN ('shipped', 'paid')
        GROUP BY o.order_id
        ORDER BY 
            CASE o.status 
                WHEN 'shipped' THEN 1
                WHEN 'paid' THEN 2
                ELSE 3
            END,
            o.placed_at DESC";

$stmt = $pdo->query($sql);
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$totalDeliveries = count($deliveries);
$shippingToday = count(array_filter($deliveries, function($del) {
    return date('Y-m-d', strtotime($del['placed_at'])) === date('Y-m-d');
}));
$completedDeliveries = count(array_filter($deliveries, function($del) {
    return $del['status'] === 'completed';
}));

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
    <title>Delivery Management - Style'n Wear</title>
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

        /* Delivery-specific styles */
        .delivery-card {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: var(--transition);
        }

        .delivery-card:hover {
            border-color: var(--gold);
            transform: translateY(-5px);
        }

        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        }

        .delivery-id {
            color: var(--gold);
            font-family: 'Cinzel', serif;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .delivery-address {
            color: var(--gold-light);
            font-size: 0.9rem;
            margin: 5px 0;
        }

        .delivery-progress {
            margin-top: 15px;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 20px;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 12px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: rgba(212, 175, 55, 0.2);
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }

        .step-icon {
            width: 25px;
            height: 25px;
            background: rgba(212, 175, 55, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 5px;
            color: var(--gold-light);
            font-size: 0.8rem;
        }

        .step.active .step-icon {
            background: var(--gold);
            color: var(--charcoal);
        }

        .step.completed .step-icon {
            background: #28a745;
            color: white;
        }

        .step-label {
            font-size: 0.75rem;
            color: #888;
        }

        .step.active .step-label {
            color: var(--gold);
        }

        .delivery-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            background: linear-gradient(45deg, var(--gold-dark), var(--gold));
            color: var(--charcoal);
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            transition: var(--transition);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
            color: white;
        }

        .map-preview {
            height: 150px;
            background: rgba(212, 175, 55, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            margin-top: 15px;
        }

        .driver-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(212, 175, 55, 0.1);
        }

        .driver-avatar {
            width: 40px;
            height: 40px;
            background: rgba(212, 175, 55, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
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
            .delivery-card {
                padding: 15px;
            }
            .progress-steps {
                font-size: 0.9rem;
            }
            .step-label {
                font-size: 0.7rem;
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
                font-size: 1.8rem;
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
            <a href="deliveries.php" class="sidebar-item active">
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
                        <h1 class="page-title">Delivery Management</h1>
                        <p class="page-subtitle">Track and manage order deliveries</p>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end gap-3">
                            <button class="btn logout-btn" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print
                            </button>
                            <button class="btn logout-btn" onclick="exportDeliveries()">
                                <i class="fas fa-file-export me-2"></i>Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="stats-value"><?php echo e($totalDeliveries); ?></div>
                        <div class="stats-label">Active Deliveries</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="stats-value"><?php echo e($shippingToday); ?></div>
                        <div class="stats-label">Shipping Today</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stats-value"><?php echo e($completedDeliveries); ?></div>
                        <div class="stats-label">Completed</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="stats-value">5</div>
                        <div class="stats-label">Active Drivers</div>
                    </div>
                </div>
            </div>

            <!-- Delivery Cards -->
            <?php if (empty($deliveries)): ?>
            <div class="empty-state">
                <i class="fas fa-truck"></i>
                <h3>No active deliveries</h3>
                <p>All orders have been delivered or are awaiting shipment</p>
            </div>
            <?php else: ?>
            <?php foreach ($deliveries as $delivery): 
                $statusClass = getStatusBadge($delivery['status']);
            ?>
            <div class="delivery-card">
                <div class="delivery-header">
                    <div>
                        <div class="delivery-id">Delivery #<?php echo e($delivery['order_id']); ?></div>
                        <div class="text-muted">Order placed: <?php echo date('M d, Y', strtotime($delivery['placed_at'])); ?></div>
                    </div>
                    <span class="status-badge <?php echo e($statusClass); ?>">
                        <?php echo e(ucfirst($delivery['status'])); ?>
                    </span>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div>
                            <strong>Customer:</strong> <?php echo e($delivery['fullname']); ?>
                        </div>
                        <div>
                            <strong>Items:</strong> <?php echo e($delivery['item_count']); ?> items
                        </div>
                        <div>
                            <strong>Amount:</strong> <span class="text-gold">₱<?php echo e(number_format($delivery['total_amount'], 2)); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div>
                            <strong>Delivery Address:</strong>
                        </div>
                        <div class="delivery-address">
                            <?php if (!empty($delivery['recipient_name'])): ?>
                                <?php echo e($delivery['recipient_name']); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($delivery['address_line'])): ?>
                                <?php echo e($delivery['address_line']); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($delivery['city'])): ?>
                                <?php echo e($delivery['city'] . ', ' . $delivery['province'] . ' ' . $delivery['postal_code']); ?>
                            <?php endif; ?>
                            <?php if (!empty($delivery['phone'])): ?>
                                <br>📱 <?php echo e($delivery['phone']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Delivery Progress -->
                <div class="delivery-progress">
                    <div class="progress-steps">
                        <div class="step <?php echo in_array($delivery['status'], ['paid', 'shipped', 'completed']) ? 'completed' : 'active'; ?>">
                            <div class="step-icon"><i class="fas fa-box"></i></div>
                            <div class="step-label">Order Placed</div>
                        </div>
                        <div class="step <?php echo $delivery['status'] == 'shipped' || $delivery['status'] == 'completed' ? 'completed' : ($delivery['status'] == 'paid' ? 'active' : ''); ?>">
                            <div class="step-icon"><i class="fas fa-shipping-fast"></i></div>
                            <div class="step-label">Shipped</div>
                        </div>
                        <div class="step <?php echo $delivery['status'] == 'completed' ? 'completed' : ''; ?>">
                            <div class="step-icon"><i class="fas fa-check"></i></div>
                            <div class="step-label">Delivered</div>
                        </div>
                    </div>

                    <div class="delivery-actions">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="order_id" value="<?php echo e($delivery['order_id']); ?>">
                            <input type="hidden" name="action" value="track">
                            <button type="submit" class="action-btn">
                                <i class="fas fa-map-marked-alt"></i> Track
                            </button>
                        </form>
                        
                        <?php if ($delivery['status'] == 'shipped'): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="order_id" value="<?php echo e($delivery['order_id']); ?>">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="action-btn">
                                <i class="fas fa-check-circle"></i> Mark Delivered
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <a href="invoice_view.php?id=<?php echo e($delivery['order_id']); ?>" class="action-btn">
                            <i class="fas fa-file-invoice"></i> Invoice
                        </a>
                    </div>
                </div>

                <!-- Driver Info (Simulated) -->
                <?php if ($delivery['status'] == 'shipped'): ?>
                <div class="driver-info">
                    <div class="driver-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div><strong>Driver:</strong> John Doe</div>
                        <div class="text-muted">Estimated delivery: Today, 3-5 PM</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportDeliveries() {
            alert('Export functionality would be implemented here');
        }
    </script>
</body>
</html>