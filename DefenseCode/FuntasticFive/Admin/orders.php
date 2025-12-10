<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// DIRECT DATABASE CONNECTION
try {
    $host = 'localhost';
    $dbname = 'stylenwear_db';
    $dbuser = 'root';
    $dbpass = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle order status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $order_id = $_POST['order_id'];
        $action = $_POST['action'];
        
        switch($action) {
            case 'view':
                header("Location: invoice_view.php?id=$order_id");
                exit();
                
            case 'ship':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'shipped' WHERE order_id = ?");
                $stmt->execute([$order_id]);
                break;
                
            case 'complete':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE order_id = ?");
                $stmt->execute([$order_id]);
                break;
                
            case 'cancel':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
                $stmt->execute([$order_id]);
                break;
        }
        
        // Refresh page after update
        header("Location: orders.php");
        exit();
    }
    
    // Get all orders with order items count
    $stmt = $pdo->query("
        SELECT o.*, u.fullname, u.email, 
               COUNT(oi.order_item_id) as item_count,
               sa.address_line, sa.city, sa.phone
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN shipping_addresses sa ON o.shipping_address_id = sa.address_id
        GROUP BY o.order_id
        ORDER BY o.placed_at DESC
    ");
    $orders = $stmt->fetchAll();
    
    // Get order statistics
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
    $completedOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('completed', 'shipped')")->fetchColumn();
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
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
    <title>Order Management - Style'n Wear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/jpg" href="../image/stylenwear.png">
    <link rel="stylesheet" href="../css/orders-style.css">
    <style>
        .table {
            --bs-table-bg: transparent !important;
            --bs-table-color: #f8f5f0 !important;
            --bs-table-border-color: rgba(212, 175, 55, 0.2) !important;
        }
        
        .table th,
        .table td {
            background-color: transparent !important;
            color: #f8f5f0 !important;
            border-color: rgba(212, 175, 55, 0.2) !important;
        }
        
        .table thead th {
            background-color: rgba(212, 175, 55, 0.15) !important;
            color: #d4af37 !important;
        }
        
        .table tbody tr:hover {
            background-color: rgba(212, 175, 55, 0.05) !important;
        }
        
        .text-muted {
            color: #888 !important;
        }
        
        .text-gold {
            color: #d4af37 !important;
        }
        
        .badge.bg-dark {
            background-color: #1a1a1a !important;
            color: #f4e4a6 !important;
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
            <a href="orders.php" class="sidebar-item active">
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
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="page-title">Order Management</h1>
                        <p class="page-subtitle">Manage and process customer orders</p>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end gap-3">
                            <a href="invoice_list.php" class="btn logout-btn">
                                <i class="fas fa-file-invoice me-2"></i>All Invoices
                            </a>
                            <button class="btn logout-btn" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print
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
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stats-value"><?php echo e($totalOrders); ?></div>
                        <div class="stats-label">Total Orders</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stats-value"><?php echo e($pendingOrders); ?></div>
                        <div class="stats-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stats-value"><?php echo e($completedOrders); ?></div>
                        <div class="stats-label">Completed</div>
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
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab active" onclick="filterOrders('all')">All Orders</div>
                <div class="filter-tab" onclick="filterOrders('pending')">Pending</div>
                <div class="filter-tab" onclick="filterOrders('paid')">Paid</div>
                <div class="filter-tab" onclick="filterOrders('shipped')">Shipped</div>
                <div class="filter-tab" onclick="filterOrders('completed')">Completed</div>
                <div class="filter-tab" onclick="filterOrders('cancelled')">Cancelled</div>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchOrders" placeholder="Search orders by ID, customer name, or email...">
            </div>

            <!-- Orders Table -->
            <div class="orders-table">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): 
                                $orderDate = date('M d, Y', strtotime($order['placed_at']));
                                $orderTime = date('h:i A', strtotime($order['placed_at']));
                                $statusClass = getStatusBadge($order['status']);
                            ?>
                            <tr class="order-row" data-status="<?php echo e($order['status']); ?>">
                                <td>
                                    <strong>#<?php echo e($order['order_id']); ?></strong>
                                </td>
                                <td>
                                    <div class="user-name"><?php echo e($order['fullname']); ?></div>
                                    <div class="user-email"><?php echo e($order['email']); ?></div>
                                    <?php if (!empty($order['phone'])): ?>
                                    <small><i class="fas fa-phone"></i> <?php echo e($order['phone']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-dark"><?php echo e($order['item_count']); ?> items</span>
                                </td>
                                <td>
                                    <strong class="text-gold">₱<?php echo e(number_format($order['total_amount'], 2)); ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo e($statusClass); ?>">
                                        <?php echo e(ucfirst($order['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?php echo e($orderDate); ?></div>
                                    <small class="text-muted"><?php echo e($orderTime); ?></small>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?php echo e($order['order_id']); ?>">
                                            <input type="hidden" name="action" value="view">
                                            <button type="submit" class="action-btn btn-view" title="View Invoice">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </form>
                                        
                                        <?php if ($order['status'] == 'paid' || $order['status'] == 'pending'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?php echo e($order['order_id']); ?>">
                                            <input type="hidden" name="action" value="ship">
                                            <button type="submit" class="action-btn btn-ship" title="Mark as Shipped">
                                                <i class="fas fa-truck"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] == 'shipped'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?php echo e($order['order_id']); ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="action-btn btn-complete" title="Mark as Completed">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] != 'cancelled' && $order['status'] != 'completed'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                            <input type="hidden" name="order_id" value="<?php echo e($order['order_id']); ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="action-btn btn-cancel" title="Cancel Order">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <footer class="mt-5 pt-4 border-top" style="border-color: rgba(212, 175, 55, 0.2);">
                <div class="d-flex justify-content-between align-items-center" style="color: var(--gold-light); font-size: 0.9rem;">
                    <div>© <?php echo date('Y'); ?> Style'n Wear Admin Panel</div>
                    <div>
                        Showing <?php echo count($orders); ?> of <?php echo e($totalOrders); ?> orders
                    </div>
                </div>
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter orders by status
        function filterOrders(status) {
            const rows = document.querySelectorAll('.order-row');
            const tabs = document.querySelectorAll('.filter-tab');
            
            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter rows
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update count
            const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
            document.querySelector('footer .d-flex div:last-child').textContent = 
                `Showing ${visibleRows.length} of ${rows.length} orders`;
        }

        // Search functionality
        document.getElementById('searchOrders').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.order-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
            
            // Update count
            const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
            document.querySelector('footer .d-flex div:last-child').textContent = 
                `Showing ${visibleRows.length} of ${rows.length} orders`;
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>