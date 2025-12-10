<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Database connection
try {
    $host = 'localhost';
    $dbname = 'stylenwear_db';
    $dbuser = 'root';
    $dbpass = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all users with their info
    $stmt = $pdo->query("
        SELECT u.*, ui.address, ui.contact_no 
        FROM users u 
        LEFT JOIN user_info ui ON u.user_id = ui.user_id 
        ORDER BY u.date_joined DESC
    ");
    $users = $stmt->fetchAll();
    
    // Get total user count
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

function e($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Style'n Wear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/jpg" href="../image/stylenwear.png">
    <link rel="stylesheet" href="../css/users-style.css">
    <style>
    /* Add this at the top of your style section to override Bootstrap */
    .admin-content .table {
        --bs-table-bg: transparent !important;
        --bs-table-color: #f8f5f0 !important;
        background-color: transparent !important;
        border-color: rgba(212, 175, 55, 0.2) !important;
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
    
    .text-muted {
        color: #888 !important;
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
            <a href="users.php" class="sidebar-item active">
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
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="page-title">User Management</h1>
                        <p class="page-subtitle">Manage all registered users and their information</p>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end gap-3">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchUsers" placeholder="Search users...">
                            </div>
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
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-value"><?php echo e($totalUsers); ?></div>
                        <div class="stats-label">Total Users</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stats-value"><?php echo e($totalUsers); ?></div>
                        <div class="stats-label">Active Users</div>
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
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stats-value">100%</div>
                        <div class="stats-label">Active Rate</div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="admin-table">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Contact Info</th>
                                <th>Address</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): 
                                $initial = strtoupper(substr($user['fullname'], 0, 1));
                                $joinDate = date('M d, Y', strtotime($user['date_joined']));
                                $joinTime = date('h:i A', strtotime($user['date_joined']));
                            ?>
                            <tr>
                                <td><strong>#<?php echo e($user['user_id']); ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar">
                                            <?php echo e($initial); ?>
                                        </div>
                                        <div>
                                            <div class="user-name"><?php echo e($user['fullname']); ?></div>
                                            <div class="user-email"><?php echo e($user['email']); ?></div>
                                            <small class="text-muted">@<?php echo e($user['username']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($user['contact_no'])): ?>
                                        <div><i class="fas fa-phone"></i> <?php echo e($user['contact_no']); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">No contact</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($user['address'])): ?>
                                        <div class="text-truncate" style="max-width: 200px;" title="<?php echo e($user['address']); ?>">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo e($user['address']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No address</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?php echo e($joinDate); ?></div>
                                    <small class="text-muted"><?php echo e($joinTime); ?></small>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="action-btn btn-view" onclick="viewUser(<?php echo e($user['user_id']); ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="action-btn btn-edit" onclick="editUser(<?php echo e($user['user_id']); ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="action-btn btn-delete" onclick="deleteUser(<?php echo e($user['user_id']); ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
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
                        Showing <?php echo count($users); ?> of <?php echo e($totalUsers); ?> users
                    </div>
                </div>
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchUsers').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // User action functions
        function viewUser(userId) {
            alert(`View user #${userId} details`);
            // In a real application, this would open a modal or redirect to user details page
        }

        function editUser(userId) {
            alert(`Edit user #${userId}`);
            // In a real application, this would open an edit modal
        }

        function deleteUser(userId) {
            if (confirm(`Are you sure you want to delete user #${userId}?`)) {
                alert(`User #${userId} deleted successfully!`);
                // In a real application, this would send a DELETE request to the server
            }
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>