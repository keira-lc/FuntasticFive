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

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $product_id = $_POST['product_id'] ?? null;
        
        switch($action) {
            case 'delete':
                if ($product_id) {
                    $stmt = $pdo->prepare("DELETE FROM items WHERE item_id = ?");
                    $stmt->execute([$product_id]);
                }
                break;
                
            case 'update_stock':
                if ($product_id && isset($_POST['stock'])) {
                    $stmt = $pdo->prepare("UPDATE items SET stock = ? WHERE item_id = ?");
                    $stmt->execute([$_POST['stock'], $product_id]);
                }
                break;
        }
        
        header("Location: products.php");
        exit();
    }
}

// Fetch products
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'all';
$stock_filter = $_GET['stock'] ?? 'all';

$sql = "SELECT 
            i.*,
            c.category_name,
            (SELECT COALESCE(SUM(oi.quantity), 0) 
             FROM order_items oi 
             JOIN orders o ON oi.order_id = o.order_id 
             WHERE oi.item_id = i.item_id 
             AND o.status IN ('completed', 'shipped', 'paid')
             AND o.status != 'cancelled') as total_sold
        FROM items i
        LEFT JOIN category c ON i.category_id = c.category_id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (i.item_name LIKE ? OR i.item_description LIKE ? OR i.item_sku LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($category != 'all') {
    $sql .= " AND c.category_name = ?";
    $params[] = $category;
}

if ($stock_filter == 'low') {
    $sql .= " AND i.stock <= 10 AND i.stock > 0";
} elseif ($stock_filter == 'out') {
    $sql .= " AND i.stock = 0";
}

$sql .= " ORDER BY i.date_added DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories = $pdo->query("SELECT DISTINCT category_name FROM category")->fetchAll(PDO::FETCH_COLUMN);

// Get statistics
$totalProducts = count($products);
$totalStockValue = 0;
$lowStockItems = 0;
$outOfStockItems = 0;

foreach ($products as $product) {
    $stockValue = $product['stock'] * $product['item_price'];
    $totalStockValue += $stockValue;
    
    if ($product['stock'] <= 10 && $product['stock'] > 0) {
        $lowStockItems++;
    } elseif ($product['stock'] == 0) {
        $outOfStockItems++;
    }
}

function e($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}

// Function to get stock status class
function getStockClass($stock) {
    if ($stock > 10) return 'stock-good';
    if ($stock > 0) return 'stock-warning';
    return 'stock-danger';
}

// Function to get stock status text
function getStockText($stock) {
    if ($stock > 10) return 'Good';
    if ($stock > 0) return 'Low Stock';
    return 'Out of Stock';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Style'n Wear</title>
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

        /* Search Box */
        .search-box {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 25px;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            transition: var(--transition);
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

        /* Product-specific styles */
        .product-card {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 15px;
            overflow: hidden;
            transition: var(--transition);
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-10px);
            border-color: var(--gold);
            box-shadow: 0 20px 40px rgba(212, 175, 55, 0.2);
        }

        .product-image {
            height: 200px;
            width: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-content {
            padding: 20px;
        }

        .product-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-sku {
            color: var(--gold-light);
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .product-category {
            background: rgba(212, 175, 55, 0.1);
            color: var(--gold);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 10px;
        }

        .product-stock {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .stock-badge {
            padding: 4px 12px;
            border-radius: 15px;
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

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .product-action-btn {
            flex: 1;
            padding: 8px;
            border-radius: 10px;
            border: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-edit {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            border: 1px solid rgba(13, 110, 253, 0.3);
        }

        .btn-edit:hover {
            background: #0d6efd;
            color: white;
        }

        .btn-delete {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .btn-delete:hover {
            background: #dc3545;
            color: white;
        }

        .btn-view {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            border: 1px solid rgba(108, 117, 125, 0.3);
        }

        .btn-view:hover {
            background: #6c757d;
            color: white;
        }

        /* Add Product Button */
        .add-product-btn {
            background: linear-gradient(45deg, var(--gold-dark), var(--gold));
            color: var(--charcoal);
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .add-product-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.4);
            color: white;
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
        }

        /* Quick Stats */
        .quick-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat-item {
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 10px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--gold);
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--gold-light);
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
            .product-card {
                margin-bottom: 20px;
            }
            .quick-stats {
                justify-content: center;
            }
            .product-actions {
                flex-direction: column;
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
            <a href="deliveries.php" class="sidebar-item">
                <i class="fas fa-truck"></i>Deliveries
            </a>
            <a href="products.php" class="sidebar-item active">
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
                        <h1 class="page-title">Product Management</h1>
                        <p class="page-subtitle">Manage your store's product inventory</p>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end gap-3">
                            <button class="btn logout-btn" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print
                            </button>
                            <button class="btn add-product-btn" onclick="addProduct()">
                                <i class="fas fa-plus me-2"></i>Add Product
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Search Form -->
                <form method="GET" class="mt-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" 
                                       placeholder="Search products..." 
                                       value="<?php echo e($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="search-box">
                                <i class="fas fa-filter"></i>
                                <select name="category" class="form-select bg-transparent border-0">
                                    <option value="all">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo e($cat); ?>" <?php echo $category == $cat ? 'selected' : ''; ?>>
                                        <?php echo e($cat); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="search-box">
                                <i class="fas fa-box"></i>
                                <select name="stock" class="form-select bg-transparent border-0">
                                    <option value="all">All Stock Levels</option>
                                    <option value="low" <?php echo $stock_filter == 'low' ? 'selected' : ''; ?>>Low Stock (≤10)</option>
                                    <option value="out" <?php echo $stock_filter == 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn logout-btn w-100">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo e($totalProducts); ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">₱<?php echo e(number_format($totalStockValue, 2)); ?></div>
                    <div class="stat-label">Stock Value</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo e($lowStockItems); ?></div>
                    <div class="stat-label">Low Stock</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo e($outOfStockItems); ?></div>
                    <div class="stat-label">Out of Stock</div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="row">
                <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-tshirt fa-3x mb-3"></i>
                        <h3>No products found</h3>
                        <p>Try changing your search criteria or add new products</p>
                        <button class="btn add-product-btn mt-3" onclick="addProduct()">
                            <i class="fas fa-plus me-2"></i>Add Product
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($products as $product): 
                    $stockClass = getStockClass($product['stock']);
                    $stockText = getStockText($product['stock']);
                ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="product-card">
                        <div class="position-relative">
                            <?php if (!empty($product['image_url'])): ?>
                            <img src="../<?php echo e($product['image_url']); ?>" 
                                 alt="<?php echo e($product['item_name']); ?>" 
                                 class="product-image">
                            <?php else: ?>
                            <div class="product-image d-flex align-items-center justify-content-center bg-dark">
                                <i class="fas fa-tshirt fa-3x" style="color: var(--gold); opacity: 0.5;"></i>
                            </div>
                            <?php endif; ?>
                            <div class="position-absolute top-0 end-0 p-2">
                                <span class="stock-badge <?php echo e($stockClass); ?>">
                                    <?php echo e($stockText); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="product-content">
                            <div class="product-title"><?php echo e($product['item_name']); ?></div>
                            <div class="product-sku">SKU: <?php echo e($product['item_sku']); ?></div>
                            <div class="product-category"><?php echo e($product['category_name'] ?? 'Uncategorized'); ?></div>
                            
                            <div class="product-price">
                                ₱<?php echo e(number_format($product['item_price'], 2)); ?>
                            </div>
                            
                            <div class="product-stock">
                                <div>
                                    <span class="text-muted">Stock:</span>
                                    <span class="ms-2 fw-bold"><?php echo e($product['stock']); ?></span>
                                </div>
                                <div>
                                    <span class="text-muted">Sold:</span>
                                    <span class="ms-2 fw-bold"><?php echo e($product['total_sold']); ?></span>
                                </div>
                            </div>
                            
                            <div class="product-actions">
                                <button class="product-action-btn btn-view" 
                                        onclick="viewProduct(<?php echo e($product['item_id']); ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="product-action-btn btn-edit" 
                                        onclick="editProduct(<?php echo e($product['item_id']); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline" style="flex: 1;" onsubmit="return confirm('Delete this product?');">
                                    <input type="hidden" name="product_id" value="<?php echo e($product['item_id']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="product-action-btn btn-delete w-100">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addProduct() {
            alert('Add product form would open here');
            // In real app, this would open a modal or redirect to add product page
        }

        function editProduct(productId) {
            alert(`Edit product #${productId}`);
            // In real app, this would open an edit modal
        }

        function viewProduct(productId) {
            window.location.href = `product_view.php?id=${productId}`;
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>