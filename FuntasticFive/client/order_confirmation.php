<?php
// Start session to get order information
session_start();

// Check if we have an order ID in the session
if (!isset($_SESSION['order_id']) || !isset($_SESSION['order_total'])) {
    header('Location: index.php');
    exit();
}

// Store order info in variables
$order_id = $_SESSION['order_id'];
$order_total = $_SESSION['order_total'];

// Clear order data from session so it doesn't show again on refresh
unset($_SESSION['order_id']);
unset($_SESSION['order_total']);

// Database connection settings
$host = '127.0.0.1';
$dbname = 'stylenwear_db';
$username = 'root';
$password = '';

// Connect to database
$conn = new mysqli($host, $username, $password, $dbname);

// Check if connection failed
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get order details from database
$order_sql = "SELECT o.*, u.fullname, u.email, u.username, 
              sa.recipient_name, sa.address_line, sa.city, sa.province, 
              sa.postal_code, sa.phone,
              p.method as payment_method, p.status as payment_status
              FROM orders o
              JOIN users u ON o.user_id = u.user_id
              JOIN shipping_addresses sa ON o.shipping_address_id = sa.address_id
              JOIN payments p ON o.order_id = p.order_id
              WHERE o.order_id = $order_id";
$order_result = $conn->query($order_sql);

// Check if order exists in database
if ($order_result->num_rows == 0) {
    die("Order not found!");
}

$order = $order_result->fetch_assoc();

// Get order items
$items_sql = "SELECT oi.*, i.item_name, i.item_price, i.image_url, i.is_on_sale
              FROM order_items oi
              JOIN items i ON oi.item_id = i.item_id
              WHERE oi.order_id = $order_id";
$items_result = $conn->query($items_sql);
$order_items = array();
$item_count = 0;
$subtotal = 0;

while($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
    $item_count += $item['quantity'];
    $subtotal += $item['quantity'] * $item['unit_price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Style'n Wear</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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

    /* Gold Gradient Text */
    .gold-text {
        background: linear-gradient(45deg, var(--gold), var(--gold-light));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 600;
    }

    /* Confirmation Page Container */
    .confirmation-container {
        padding-top: 120px;
        min-height: 100vh;
        background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
        color: var(--off-white);
    }

    /* Confirmation Box */
    .confirmation-box {
        background: linear-gradient(145deg, #1e1e1e, #151515);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 20px;
        padding: 40px;
        margin: 40px auto;
        max-width: 900px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
        color: var(--off-white) !important;
    }

    /* Force all text in confirmation box to be visible */
    .confirmation-box,
    .confirmation-box * {
        color: var(--off-white) !important;
    }

    /* Success Icon */
    .success-icon {
        font-size: 80px;
        color: #2dce89;
        margin-bottom: 20px;
        animation: bounce 1s infinite alternate;
        text-shadow: 0 0 20px rgba(45, 206, 137, 0.5);
    }

    @keyframes bounce {
        from { transform: translateY(0px); }
        to { transform: translateY(-15px); }
    }

    /* Order Number */
    .order-number {
        background: rgba(212, 175, 55, 0.1);
        border: 2px solid var(--gold);
        border-radius: 15px;
        padding: 15px 30px;
        font-size: 1.8rem;
        font-weight: bold;
        margin: 20px 0;
        display: inline-block;
        font-family: "Cinzel", serif;
        color: var(--gold) !important;
    }

    /* Order Details Box */
    .order-details-box {
        background: rgba(30, 30, 30, 0.7);
        border-radius: 15px;
        padding: 30px;
        margin-top: 30px;
        border-left: 5px solid var(--gold);
        border: 1px solid rgba(212, 175, 55, 0.2);
    }

    /* Order Items Box */
    .order-items-box {
        background: rgba(40, 40, 40, 0.5);
        border-radius: 15px;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid rgba(212, 175, 55, 0.1);
    }

    .order-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid rgba(212, 175, 55, 0.1);
    }

    .order-item:last-child {
        border-bottom: none;
    }

    .item-info {
        flex-grow: 1;
    }

    /* Buttons */
    .btn-confirmation {
        background: linear-gradient(45deg, var(--gold), var(--gold-light));
        color: var(--charcoal) !important;
        border: none;
        padding: 15px 40px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 25px;
        transition: var(--transition);
        font-family: "Cinzel", serif;
        letter-spacing: 1px;
    }

    .btn-confirmation:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(212, 175, 55, 0.4);
        color: white !important;
        background: linear-gradient(45deg, var(--gold-dark), var(--gold));
    }

    .btn-outline-gold {
        background: transparent;
        border: 2px solid var(--gold);
        color: var(--gold) !important;
        padding: 15px 40px;
        font-weight: 600;
        border-radius: 25px;
        transition: var(--transition);
        font-family: "Cinzel", serif;
        letter-spacing: 1px;
    }

    .btn-outline-gold:hover {
        background: var(--gold);
        color: var(--charcoal) !important;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
    }

    /* Status Badges */
    .status-badge {
        padding: 8px 20px;
        border-radius: 25px;
        font-weight: bold;
        font-size: 0.9rem;
        display: inline-block;
    }

    .status-pending {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .status-confirmed {
        background: rgba(45, 206, 137, 0.2);
        color: #2dce89;
        border: 1px solid rgba(45, 206, 137, 0.3);
    }

    /* Timeline */
    .timeline {
        position: relative;
        max-width: 800px;
        margin: 40px auto;
        display: flex;
        justify-content: space-between;
    }

    .timeline-step {
        text-align: center;
        flex: 1;
        position: relative;
        z-index: 1;
    }

    .timeline-step .step-icon {
        width: 60px;
        height: 60px;
        background: rgba(212, 175, 55, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-size: 1.3rem;
        color: var(--gold-light);
        border: 2px solid rgba(212, 175, 55, 0.3);
        transition: var(--transition);
    }

    .timeline-step.completed .step-icon {
        background: linear-gradient(45deg, var(--gold), var(--gold-light));
        color: var(--charcoal);
        border-color: var(--gold);
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
    }

    .timeline-step.active .step-icon {
        background: #2dce89;
        color: white;
        border-color: #2dce89;
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(45, 206, 137, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(45, 206, 137, 0); }
        100% { box-shadow: 0 0 0 0 rgba(45, 206, 137, 0); }
    }

    .step-text {
        color: #aaa !important;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .timeline-step.active .step-text {
        color: #2dce89 !important;
        font-weight: 600;
    }

    .timeline-step.completed .step-text {
        color: var(--gold) !important;
    }

    .timeline-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 30px;
        right: -50%;
        width: 100%;
        height: 2px;
        background: rgba(212, 175, 55, 0.3);
        z-index: 0;
    }

    .timeline-step.completed:not(:last-child)::after {
        background: var(--gold);
    }

    /* Info Cards */
    .info-card {
        background: rgba(30, 30, 30, 0.5);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #17a2b8;
        border: 1px solid rgba(212, 175, 55, 0.1);
    }

    .info-card h5 {
        color: #17a2b8 !important;
        margin-bottom: 15px;
        font-family: "Playfair Display", serif;
    }

    /* What's Next Section */
    .whats-next {
        background: rgba(23, 162, 184, 0.1);
        border-radius: 15px;
        padding: 25px;
        margin-top: 30px;
        border-left: 5px solid #17a2b8;
        border: 1px solid rgba(212, 175, 55, 0.1);
    }

    .whats-next h4 {
        color: #17a2b8 !important;
        font-family: "Cinzel", serif;
    }

    /* Total Amount */
    .total-amount {
        font-size: 2rem;
        font-weight: 700;
        color: var(--gold) !important;
        font-family: "Cinzel", serif;
        text-shadow: 0 0 15px rgba(212, 175, 55, 0.4);
    }

    /* Override Bootstrap classes */
    .confirmation-box .text-muted {
        color: #aaa !important;
    }

    .confirmation-box .text-gold {
        color: var(--gold) !important;
    }

    .confirmation-box h1, 
    .confirmation-box h2, 
    .confirmation-box h3, 
    .confirmation-box h4, 
    .confirmation-box h5, 
    .confirmation-box h6 {
        color: white !important;
        font-family: "Cinzel", serif;
    }

    .confirmation-box p {
        color: var(--off-white) !important;
    }

    /* Text colors */
    .confirmation-box strong {
        color: var(--gold-light) !important;
    }

    .confirmation-box small {
        color: #aaa !important;
    }

    /* Links */
    .confirmation-box a {
        color: var(--gold) !important;
        text-decoration: none;
        transition: var(--transition);
    }

    .confirmation-box a:hover {
        color: var(--gold-light) !important;
        text-decoration: underline;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .confirmation-container {
            padding-top: 100px;
        }
        
        .confirmation-box {
            padding: 20px;
            margin: 20px auto;
        }
        
        .timeline-step:not(:last-child)::after {
            display: none;
        }
        
        .timeline {
            flex-wrap: wrap;
        }
        
        .timeline-step {
            flex: 0 0 50%;
            margin-bottom: 30px;
        }
        
        .success-icon {
            font-size: 60px;
        }
        
        .order-number {
            font-size: 1.5rem;
            padding: 12px 20px;
        }
        
        .btn-confirmation,
        .btn-outline-gold {
            padding: 12px 20px;
            font-size: 1rem;
            width: 100%;
            margin-bottom: 10px;
        }
    }

    @media (max-width: 576px) {
        .timeline-step {
            flex: 0 0 100%;
        }
        
        .confirmation-box {
            padding: 15px;
        }
        
        .order-details-box {
            padding: 20px;
        }
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
                    <a href="my_orders.php" class="nav-link">My Orders</a>
                    <a href="logout.php" class="nav-link logout-btn">
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

    <!-- Confirmation Section -->
    <div class="confirmation-container">
        <div class="container">
            <div class="confirmation-box">
                
                <!-- Success Message -->
                <h1 class="mb-3 text-center">Order Confirmed!</h1>
                <p class="lead text-center mb-4">Thank you for your purchase. Your order has been successfully placed.</p>
                
                <!-- Success Icon -->
                <div class="success-icon text-center mx-auto">
                    <i class="fas fa-check-circle"></i>
                </div>

                <!-- Order Number -->
                <div class="text-center">
                    <div class="order-number">
                        Order #<?php echo str_pad($order_id, 8, '0', STR_PAD_LEFT); ?>
                    </div>
                </div>
                
                <!-- Status Badge -->
                <div class="text-center mb-4">
                    <span class="status-badge status-confirmed">
                        <i class="fas fa-check me-2"></i> Order Confirmed
                    </span>
                </div>
                
                <!-- Order Timeline -->
                <div class="timeline">
                    <div class="timeline-step completed">
                        <div class="step-icon"><i class="fas fa-shopping-cart"></i></div>
                        <div class="step-text">Order Placed</div>
                    </div>
                    <div class="timeline-step active">
                        <div class="step-icon"><i class="fas fa-box"></i></div>
                        <div class="step-text">Processing</div>
                    </div>
                    <div class="timeline-step">
                        <div class="step-icon"><i class="fas fa-shipping-fast"></i></div>
                        <div class="step-text">Shipped</div>
                    </div>
                    <div class="timeline-step">
                        <div class="step-icon"><i class="fas fa-home"></i></div>
                        <div class="step-text">Delivered</div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="order-details-box">
                    <h3 class="mb-4"><i class="fas fa-receipt me-2"></i> Order Summary</h3>
                    
                    <!-- Customer Information -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-user me-2"></i> Customer Information</h5>
                            <p>
                                <strong>Name:</strong> <?php echo htmlspecialchars($order['fullname']); ?><br>
                                <strong>Username:</strong> <?php echo htmlspecialchars($order['username']); ?><br>
                                <strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-credit-card me-2"></i> Payment Information</h5>
                            <p>
                                <strong>Method:</strong> <?php echo strtoupper($order['payment_method']); ?><br>
                                <strong>Status:</strong> 
                                    <?php if($order['payment_status'] == 'pending'): ?>
                                        <span class="status-badge status-pending">Pending</span>
                                    <?php else: ?>
                                        <span class="status-badge status-confirmed">Paid</span>
                                    <?php endif; ?>
                                <br>
                                <strong>Total Amount:</strong> 
                                <span class="text-gold fw-bold">₱<?php echo number_format($order_total, 2); ?></span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Shipping Information -->
                    <div class="info-card mb-4">
                        <h5><i class="fas fa-truck me-2"></i> Shipping Information</h5>
                        <p>
                            <strong>Recipient:</strong> <?php echo htmlspecialchars($order['recipient_name']); ?><br>
                            <strong>Address:</strong> <?php echo htmlspecialchars($order['address_line']); ?><br>
                            <strong>City:</strong> <?php echo htmlspecialchars($order['city']); ?>, 
                            <?php echo htmlspecialchars($order['province']); ?><br>
                            <strong>ZIP Code:</strong> <?php echo htmlspecialchars($order['postal_code']); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?>
                        </p>
                    </div>
                    
                    <!-- Order Items -->
                    <h5 class="mt-4 mb-3"><i class="fas fa-box-open me-2"></i> Order Items (<?php echo $item_count; ?> items)</h5>
                    <div class="order-items-box">
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <div class="item-info">
                                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong><br>
                                    <small class="text-muted">Qty: <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['unit_price'], 2); ?></small>
                                </div>
                                <div class="item-total fw-bold">
                                    ₱<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Order Totals -->
                        <div class="order-item" style="border-top: 2px solid rgba(212, 175, 55, 0.3); padding-top: 20px;">
                            <div class="item-info">
                                <strong>Subtotal</strong>
                            </div>
                            <div class="item-total">
                                ₱<?php echo number_format($subtotal, 2); ?>
                            </div>
                        </div>
                        
                        <div class="order-item">
                            <div class="item-info">
                                <strong>Tax (12%)</strong>
                            </div>
                            <div class="item-total">
                                ₱<?php echo number_format(($order_total - $subtotal), 2); ?>
                            </div>
                        </div>
                        
                        <div class="order-item" style="border-top: 2px solid var(--gold); padding-top: 20px;">
                            <div class="item-info">
                                <h5 class="mb-0">Total</h5>
                            </div>
                            <div class="item-total">
                                <h5 class="total-amount mb-0">₱<?php echo number_format($order_total, 2); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- What's Next Section -->
                <div class="whats-next">
                    <h4 class="mb-4"><i class="fas fa-info-circle me-2"></i> What's Next?</h4>
                    <div class="row mt-3">
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="step-icon" style="width: 50px; height: 50px; margin: 0 auto 15px;">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <h6>Email Confirmation</h6>
                                <p class="small mb-0">You'll receive an order confirmation email shortly.</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="step-icon" style="width: 50px; height: 50px; margin: 0 auto 15px;">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <h6>Order Processing</h6>
                                <p class="small mb-0">We're preparing your items for shipment.</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="step-icon" style="width: 50px; height: 50px; margin: 0 auto 15px;">
                                    <i class="fas fa-shipping-fast"></i>
                                </div>
                                <h6>Shipping</h6>
                                <p class="small mb-0">You'll get tracking info once your order ships.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-5 text-center">
                    <a href="index.php" class="btn btn-confirmation mx-2 mb-2">
                        <i class="fas fa-home me-2"></i> Continue Shopping
                    </a>
                    
                    <a href="my_orders.php" class="btn btn-outline-gold mx-2 mb-2">
                        <i class="fas fa-clipboard-list me-2"></i> View My Orders
                    </a>
                    
                    <button onclick="window.print()" class="btn btn-outline-gold mx-2 mb-2">
                        <i class="fas fa-print me-2"></i> Print Receipt
                    </button>
                </div>
                
                <!-- Order Note -->
                <div class="mt-4 text-center">
                    <p class="text-muted mb-0">
                        <small>
                            <i class="fas fa-clock me-1"></i> Order placed on: <?php echo date('F d, Y - h:i A', strtotime($order['placed_at'] ?? date('Y-m-d H:i:s'))); ?><br>
                            Need help? <a href="contact.php">Contact our support team</a>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>

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
                        <a href="faq.php">FAQ</a>
                        <a href="shipping.php">Shipping Info</a>
                        <a href="returns.php">Returns & Exchanges</a>
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
                    © <?= date('Y') ?> Style'n Wear. All rights reserved. | Luxury Redefined
                </p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript for Confirmation Page -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Make the success icon bounce
        const successIcon = document.querySelector('.success-icon');
        let bounceCount = 0;
        const bounceInterval = setInterval(() => {
            successIcon.style.animation = 'bounce 1s infinite alternate';
            bounceCount++;
            if (bounceCount > 5) {
                clearInterval(bounceInterval);
                successIcon.style.animation = 'none';
            }
        }, 2000);
        
        // Force fix any black text
        const confirmationBox = document.querySelector('.confirmation-box');
        if (confirmationBox) {
            const allElements = confirmationBox.querySelectorAll('*');
            allElements.forEach(el => {
                const style = getComputedStyle(el);
                if (style.color === 'rgb(0, 0, 0)' || 
                    style.color === 'rgba(0, 0, 0, 1)' ||
                    style.color.includes('rgb(0, 0, 0)') ||
                    style.color === '#000' ||
                    style.color === '#000000') {
                    el.style.color = '#f8f5f0 !important';
                }
            });
        }
        
        // Auto-scroll to top of page
        window.scrollTo(0, 0);
    });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>