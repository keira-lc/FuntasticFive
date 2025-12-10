<?php
session_start();

// Check if client is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Database connection
$host = '127.0.0.1';
$dbname = 'stylenwear_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle order actions (Cancel or Mark as Received)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $order_id = intval($_POST['order_id']);
    $action = $_POST['action'];
    
    // Verify the order belongs to the logged-in user
    $check_sql = "SELECT order_id FROM orders WHERE order_id = ? AND user_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$order_id, $user_id]);
    
    if ($check_stmt->rowCount() > 0) {
        if ($action == 'cancel') {
            // Update order status to cancelled
            $update_sql = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE order_id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$order_id]);
            $message = "Order #$order_id has been cancelled.";
        } elseif ($action == 'complete') {
            // Update order status to completed
            $update_sql = "UPDATE orders SET status = 'completed', updated_at = NOW() WHERE order_id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$order_id]);
            $message = "Order #$order_id marked as completed. Thank you!";
        }
        
        // Show success message
        $success = isset($message) ? $message : "";
    } else {
        $error = "You don't have permission to modify this order!";
    }
}

// Get all orders for this user
$sql = "
    SELECT 
        o.order_id,
        o.total_amount,
        o.currency,
        o.status as order_status,
        o.placed_at as order_date,
        COUNT(oi.order_item_id) as item_count,
        GROUP_CONCAT(i.item_name SEPARATOR ', ') as items
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN items i ON oi.item_id = i.item_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.placed_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Style'n Wear</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .order-card {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            transition: var(--transition);
        }
        
        .order-card:hover {
            border-color: var(--gold);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.1);
            transform: translateY(-5px);
        }
        
        .status-badge {
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .pending { 
            background: linear-gradient(45deg, #ffc107, #ff9800);
            color: black; 
        }
        
        .paid { 
            background: linear-gradient(45deg, #198754, #2dce89);
            color: white; 
        }
        
        .shipped { 
            background: linear-gradient(45deg, #0dcaf0, #17a2b8);
            color: black; 
        }
        
        .completed { 
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            color: var(--charcoal); 
        }
        
        .cancelled { 
            background: linear-gradient(45deg, #dc3545, #ff6b6b);
            color: white; 
        }
        
        .btn-order-action {
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid var(--gold);
            color: var(--gold);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            transition: var(--transition);
        }
        
        .btn-order-action:hover {
            background: var(--gold);
            color: var(--charcoal);
            transform: translateY(-2px);
        }
        
        .btn-view-invoice {
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            color: var(--charcoal);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            border: none;
            transition: var(--transition);
        }
        
        .btn-view-invoice:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
        }
        
        .empty-orders {
            background: rgba(30, 30, 30, 0.7);
            border-radius: 15px;
            border: 2px solid var(--gold);
            padding: 60px 20px;
            text-align: center;
        }
        
        .empty-orders-icon {
            font-size: 4rem;
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        
        .order-number {
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .order-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gold);
        }
        
        .order-items-preview {
            color: #aaa;
            font-size: 0.9rem;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <!-- Luxury Navigation -->
    <nav class="luxury-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Brand -->
                <a href="index.php" class="brand-title gold-text">
                    Style<span style="color: var(--gold)">'n</span>Wear
                </a>
                
                <!-- Navigation -->
                <div class="d-none d-lg-flex align-items-center">
                    <a href="index.php" class="nav-link">Home</a>
                    <a href="about.php" class="nav-link">About</a>
                    <a href="products.php" class="nav-link">Products</a>
                    <a href="contact.php" class="nav-link">Contact</a>
                    <a href="cart.php" class="nav-link">Cart</a>
                    <a href="my_orders.php" class="nav-link active">My Orders</a>
                    <a href="../logout.php" class="nav-link logout-btn">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
                
                <!-- Mobile Menu Button -->
                <button class="btn d-lg-none gold-text" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                    <i class="fas fa-bars fa-2x"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu (Offcanvas) -->
    <div class="offcanvas offcanvas-end bg-dark text-white" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header border-bottom border-gold">
            <h5 class="offcanvas-title gold-text">Style'n Wear</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div class="d-flex flex-column gap-3">
                <a href="index.php" class="nav-link">Home</a>
                <a href="about.php" class="nav-link">About</a>
                <a href="products.php" class="nav-link">Products</a>
                <a href="contact.php" class="nav-link">Contact</a>
                <a href="cart.php" class="nav-link">Cart</a>
                <a href="my_orders.php" class="nav-link active">My Orders</a>
                <a href="../logout.php" class="nav-link logout-btn mt-4">Logout</a>
            </div>
        </div>
    </div>

    <!-- Hero Section for Orders -->
    <section class="hero-section d-flex align-items-center" style="min-height: 250px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h1 class="hero-title gold-text">My Orders</h1>
                    <p class="hero-subtitle">
                        Track, manage, and review your purchases
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Orders Content Section -->
    <section class="products-section" style="padding-top: 50px;">
        <div class="container">
            <!-- Messages -->
            <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" 
                 style="background: rgba(45, 206, 137, 0.2); border-color: #2dce89; color: #2dce89; border-radius: 10px;">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: invert(1);"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" 
                 style="background: rgba(255, 107, 107, 0.2); border-color: #ff6b6b; color: #ff6b6b; border-radius: 10px;">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: invert(1);"></button>
            </div>
            <?php endif; ?>
            
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="section-title gold-text mb-0">
                                <i class="fas fa-shopping-bag me-2"></i>Order History
                            </h2>
                            <p class="mb-0" style="color: #aaa;">
                                Total Orders: <strong style="color: var(--gold);"><?php echo count($orders); ?></strong>
                            </p>
                        </div>
                        
                        <?php if (!empty($orders)): ?>
                            <a href="products.php" class="btn" style="background: var(--gold); color: var(--charcoal);">
                                <i class="fas fa-plus me-2"></i>New Order
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Orders List -->
            <?php if (empty($orders)): ?>
                <!-- Empty Orders State -->
                <div class="empty-orders">
                    <div class="empty-orders-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3 class="gold-text mb-3">No Orders Yet</h3>
                    <p style="color: #aaa; margin-bottom: 30px; max-width: 500px; margin-left: auto; margin-right: auto;">
                        You haven't placed any orders yet. Start your style journey with our exclusive collections!
                    </p>
                    <a href="products.php" class="btn" style="background: linear-gradient(45deg, var(--gold), var(--gold-light)); color: var(--charcoal); padding: 12px 30px;">
                        <i class="fas fa-shopping-bag me-2"></i>Browse Collections
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="row align-items-center">
                        <!-- Order Info -->
                        <div class="col-lg-3 col-md-4 mb-3 mb-md-0">
                            <span class="order-number mb-2 d-block">Order #<?php echo $order['order_id']; ?></span>
                            <div class="d-flex align-items-center" style="color: #aaa;">
                                <i class="fas fa-calendar me-2"></i>
                                <small><?php echo date('F j, Y', strtotime($order['order_date'])); ?></small>
                            </div>
                            <div class="d-flex align-items-center mt-1" style="color: #aaa;">
                                <i class="fas fa-box me-2"></i>
                                <small><?php echo $order['item_count']; ?> item(s)</small>
                            </div>
                        </div>
                        
                        <!-- Items Preview -->
                        <div class="col-lg-3 col-md-4 mb-3 mb-md-0">
                            <p class="order-items-preview mb-0">
                                <?php 
                                $items = $order['items'];
                                echo strlen($items) > 60 ? substr($items, 0, 60) . '...' : $items;
                                ?>
                            </p>
                        </div>
                        
                        <!-- Total Amount -->
                        <div class="col-lg-2 col-md-4 mb-3 mb-md-0">
                            <span class="order-total d-block">
                                ₱<?php echo number_format($order['total_amount'], 2); ?>
                            </span>
                        </div>
                        
                        <!-- Status -->
                        <div class="col-lg-2 col-md-4 mb-3 mb-lg-0">
                            <span class="status-badge <?php echo $order['order_status']; ?> d-inline-block">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </div>
                        
                        <!-- Actions -->
                        <div class="col-lg-2 col-md-4">
                            <div class="d-flex flex-column flex-md-row gap-2">
                                <!-- View Invoice Button -->
                                <a href="view_invoice.php?id=<?php echo $order['order_id']; ?>" 
                                   class="btn-view-invoice text-center" target="_blank">
                                    <i class="fas fa-file-invoice me-1"></i> Invoice
                                </a>
                                
                                <!-- Action Buttons -->
                                <?php if ($order['order_status'] == 'pending' || $order['order_status'] == 'paid'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn-order-action w-100" 
                                            onclick="return confirm('Are you sure you want to cancel this order?')">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($order['order_status'] == 'shipped'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <button type="submit" class="btn-order-action w-100" 
                                            onclick="return confirm('Mark this order as received?')">
                                        <i class="fas fa-check me-1"></i> Received
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Timeline (Optional - can be expanded) -->
                    <div class="row mt-3 pt-3" style="border-top: 1px solid rgba(212, 175, 55, 0.2);">
                        <div class="col-12">
                            <div class="timeline">
                                <div class="d-flex justify-content-between">
                                    <span class="<?php echo $order['order_status'] == 'pending' ? 'text-warning' : 'text-muted'; ?>">
                                        <i class="fas fa-clock me-1"></i> Pending
                                    </span>
                                    <span class="<?php echo $order['order_status'] == 'paid' ? 'text-success' : 'text-muted'; ?>">
                                        <i class="fas fa-money-check me-1"></i> Paid
                                    </span>
                                    <span class="<?php echo $order['order_status'] == 'shipped' ? 'text-info' : 'text-muted'; ?>">
                                        <i class="fas fa-shipping-fast me-1"></i> Shipped
                                    </span>
                                    <span class="<?php echo $order['order_status'] == 'completed' ? 'text-primary' : 'text-muted'; ?>">
                                        <i class="fas fa-check-circle me-1"></i> Completed
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Order Statistics (Optional) -->
            <?php if (!empty($orders)): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card" style="background: rgba(30, 30, 30, 0.7); border: 1px solid rgba(212, 175, 55, 0.2); border-radius: 15px; padding: 20px;">
                        <h5 class="gold-text mb-3">Order Statistics</h5>
                        <div class="row">
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-center">
                                    <div style="font-size: 2rem; color: var(--gold);">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                    <h4 class="gold-text mt-2"><?php echo count($orders); ?></h4>
                                    <small style="color: #aaa;">Total Orders</small>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-center">
                                    <div style="font-size: 2rem; color: #2dce89;">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <h4 class="text-success mt-2">
                                        <?php 
                                        $completed = array_filter($orders, function($o) { 
                                            return $o['order_status'] == 'completed'; 
                                        });
                                        echo count($completed);
                                        ?>
                                    </h4>
                                    <small style="color: #aaa;">Completed</small>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-center">
                                    <div style="font-size: 2rem; color: #0dcaf0;">
                                        <i class="fas fa-shipping-fast"></i>
                                    </div>
                                    <h4 class="text-info mt-2">
                                        <?php 
                                        $shipped = array_filter($orders, function($o) { 
                                            return $o['order_status'] == 'shipped'; 
                                        });
                                        echo count($shipped);
                                        ?>
                                    </h4>
                                    <small style="color: #aaa;">Shipped</small>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-center">
                                    <div style="font-size: 2rem; color: #ffc107;">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <h4 class="text-warning mt-2">
                                        <?php 
                                        $pending = array_filter($orders, function($o) { 
                                            return $o['order_status'] == 'pending'; 
                                        });
                                        echo count($pending);
                                        ?>
                                    </h4>
                                    <small style="color: #aaa;">Pending</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Luxury Footer -->
    <footer class="luxury-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h3 class="footer-brand gold-text">Style'n Wear</h3>
                    <p style="color: #aaa;">
                        Redefining luxury fashion with our exclusive gold-themed collections. 
                        Experience elegance, sophistication, and unparalleled style.
                    </p>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <div class="footer-links">
                        <h6>Shop</h6>
                        <a href="products.php">Women's Collection</a>
                        <a href="products.php">Men's Collection</a>
                        <a href="products.php">Accessories</a>
                        <a href="products.php">New Arrivals</a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <div class="footer-links">
                        <h6>Support</h6>
                        <a href="contact.php">Contact Us</a>
                        <a href="#">FAQ</a>
                        <a href="#">Shipping Info</a>
                        <a href="#">Returns & Exchanges</a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-4 mb-4">
                    <div class="footer-links">
                        <h6>Newsletter</h6>
                        <p style="color: #aaa; font-size: 0.9rem;">
                            Subscribe for exclusive offers and early access to new collections.
                        </p>
                        <div class="input-group">
                            <input type="email" class="form-control bg-dark text-white border-gold" 
                                   placeholder="Your email" style="border-color: var(--gold);">
                            <button class="btn gold-text" type="button" 
                                    style="background: var(--gold); color: var(--charcoal);">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5 pt-3 border-top border-gold" style="border-color: rgba(212, 175, 55, 0.3);">
                <p style="color: #777; font-size: 0.9rem;">
                    © <?= date('Y') ?> Style'n Wear. All rights reserved. | 
                    <a href="#" style="color: var(--gold); text-decoration: none;">Privacy Policy</a>
                </p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add active class to current page in mobile menu
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const mobileLinks = document.querySelectorAll('#mobileMenu .nav-link');
            
            mobileLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href === currentPage || (currentPage === '' && href === 'index.php')) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>