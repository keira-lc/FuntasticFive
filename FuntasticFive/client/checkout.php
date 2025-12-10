<?php
// Start the session to track user login
session_start();

// Database connection settings
$host = '127.0.0.1';       // Database server (localhost)
$dbname = 'stylenwear_db'; // Database name
$username = 'root';        // Database username
$password = '';            // Database password (empty for XAMPP default)

// Create connection to database
$conn = new mysqli($host, $username, $password, $dbname);

// Check if connection failed
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get user information
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

// Get user's shipping address
$address_sql = "SELECT * FROM shipping_addresses WHERE user_id = $user_id LIMIT 1";
$address_result = $conn->query($address_sql);
$address_row = null;
if ($address_result->num_rows > 0) {
    $address_row = $address_result->fetch_assoc();
}

// Get user's additional info
$user_info_sql = "SELECT * FROM user_info WHERE user_id = $user_id LIMIT 1";
$user_info_result = $conn->query($user_info_sql);
$user_info = null;
if ($user_info_result->num_rows > 0) {
    $user_info = $user_info_result->fetch_assoc();
}

// Get user's shopping cart
$cart_sql = "SELECT cart_id FROM carts WHERE user_id = $user_id";
$cart_result = $conn->query($cart_sql);

$cart_items = array();
$total_price = 0;
$item_count = 0;

if ($cart_result->num_rows > 0) {
    $cart_row = $cart_result->fetch_assoc();
    $cart_id = $cart_row['cart_id'];
    
    $items_sql = "SELECT cart_items.*, items.*, category.category_name 
                  FROM cart_items 
                  JOIN items ON cart_items.item_id = items.item_id 
                  LEFT JOIN category ON items.category_id = category.category_id 
                  WHERE cart_items.cart_id = $cart_id";
    $items_result = $conn->query($items_sql);
    
    if ($items_result->num_rows > 0) {
        while($row = $items_result->fetch_assoc()) {
            $cart_items[] = $row;
            
            // Use discount price if item is on sale
            $item_price = ($row['is_on_sale'] == 'Y' && $row['discount_price'] > 0) 
                        ? $row['discount_price'] 
                        : $row['item_price'];
            
            $total_price += $item_price * $row['quantity'];
            $item_count += $row['quantity'];
        }
    }
}

// Check if cart is empty
if ($item_count == 0) {
    header('Location: cart.php');
    exit();
}

// Calculate prices
$tax = $total_price * 0.12;
$grand_total = $total_price + $tax;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    // Get form data
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $shipping_address = $_POST['shipping_address'];
    $shipping_city = $_POST['shipping_city'];
    $shipping_zip = $_POST['shipping_zip'];
    $shipping_phone = $_POST['shipping_phone'];
    $payment_method = $_POST['payment_method'];
    
    $card_number = isset($_POST['card_number']) ? $_POST['card_number'] : '';
    $card_expiry = isset($_POST['card_expiry']) ? $_POST['card_expiry'] : '';
    $card_cvv = isset($_POST['card_cvv']) ? $_POST['card_cvv'] : '';
    $order_notes = isset($_POST['order_notes']) ? $_POST['order_notes'] : '';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Handle shipping address
        if ($address_row) {
            // Update existing address
            $update_address_sql = "UPDATE shipping_addresses SET 
                recipient_name = ?,
                address_line = ?,
                city = ?,
                province = ?,
                postal_code = ?,
                phone = ?
                WHERE address_id = ?";
            $stmt = $conn->prepare($update_address_sql);
            $province = 'Albay';
            $stmt->bind_param("ssssssi", $full_name, $shipping_address, $shipping_city, 
                             $province, $shipping_zip, $shipping_phone, $address_row['address_id']);
            $stmt->execute();
            $shipping_address_id = $address_row['address_id'];
        } else {
            // Insert new address
            $insert_address_sql = "INSERT INTO shipping_addresses 
                (user_id, recipient_name, address_line, city, province, postal_code, phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_address_sql);
            $province = 'Albay';
            $stmt->bind_param("issssss", $user_id, $full_name, $shipping_address, 
                             $shipping_city, $province, $shipping_zip, $shipping_phone);
            $stmt->execute();
            $shipping_address_id = $stmt->insert_id;
        }
        
        // Create order
        $order_sql = "INSERT INTO orders (user_id, shipping_address_id, total_amount, status) 
                      VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($order_sql);
        $stmt->bind_param("iid", $user_id, $shipping_address_id, $grand_total);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        
        // Add order items
        foreach ($cart_items as $item) {
            $item_id = $item['item_id'];
            $quantity = $item['quantity'];
            
            // Use correct price based on sale status
            $price = ($item['is_on_sale'] == 'Y' && $item['discount_price'] > 0) 
                    ? $item['discount_price'] 
                    : $item['item_price'];
            
            // Insert into order_items
            $order_item_sql = "INSERT INTO order_items (order_id, item_id, quantity, unit_price) 
                               VALUES (?, ?, ?, ?)";
            $stmt2 = $conn->prepare($order_item_sql);
            $stmt2->bind_param("iiid", $order_id, $item_id, $quantity, $price);
            $stmt2->execute();
            
            // Reduce stock
            $new_stock = $item['stock'] - $quantity;
            $update_stock_sql = "UPDATE items SET stock = ? WHERE item_id = ?";
            $stmt3 = $conn->prepare($update_stock_sql);
            $stmt3->bind_param("ii", $new_stock, $item_id);
            $stmt3->execute();
        }
        
        // Create payment record
        $payment_status = ($payment_method == 'cod') ? 'pending' : 'paid';
        $payment_sql = "INSERT INTO payments (order_id, amount, method, status) 
                        VALUES (?, ?, ?, ?)";
        $stmt4 = $conn->prepare($payment_sql);
        $stmt4->bind_param("idss", $order_id, $grand_total, $payment_method, $payment_status);
        $stmt4->execute();
        
        // Clear cart
        $clear_cart_sql = "DELETE FROM cart_items WHERE cart_id = ?";
        $stmt5 = $conn->prepare($clear_cart_sql);
        $stmt5->bind_param("i", $cart_id);
        $stmt5->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Store order info in session
        $_SESSION['order_id'] = $order_id;
        $_SESSION['order_total'] = $grand_total;
        
        // Redirect to confirmation
        header('Location: order_confirmation.php');
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Order failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Style'n Wear</title>
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

/* Checkout Page Container */
.checkout-container {
    padding-top: 100px;
    min-height: 100vh;
    background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
}

/* Main Checkout Box */
.checkout-box {
    background: linear-gradient(145deg, #1e1e1e, #151515);
    border: 1px solid rgba(212, 175, 55, 0.2);
    border-radius: 20px;
    overflow: hidden;
    transition: var(--transition);
    box-shadow: var(--shadow);
    padding: 30px;
    color: var(--off-white) !important;
}

/* Force ALL text in checkout form to be visible */
.checkout-box,
.checkout-box * {
    color: var(--off-white) !important;
}

/* Override any Bootstrap dark text classes */
.checkout-box .text-dark,
.checkout-box .text-black,
.checkout-box .text-body,
.checkout-box .text-dark,
.checkout-box [class*="text-"]:not(.gold-text):not(.text-success):not(.text-danger):not(.text-warning) {
    color: var(--off-white) !important;
}

.checkout-header {
    border-bottom: 2px solid var(--gold);
    padding-bottom: 20px;
    margin-bottom: 30px;
}

.checkout-title {
    font-family: "Cinzel", serif;
    background: linear-gradient(45deg, var(--gold), var(--gold-light));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 2.5rem;
}

/* Form Sections */
.form-section {
    background: rgba(30, 30, 30, 0.7);
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    border-left: 5px solid var(--gold);
    border: 1px solid rgba(212, 175, 55, 0.1);
    color: var(--off-white) !important;
}

/* Force all text in form sections */
.form-section,
.form-section * {
    color: var(--off-white) !important;
}

.form-section h3 {
    color: var(--gold) !important;
    font-family: "Playfair Display", serif;
    margin-bottom: 20px;
    font-size: 1.3rem;
}

/* Form Controls */
.form-control {
    background: rgba(40, 40, 40, 0.8);
    border: 1px solid rgba(212, 175, 55, 0.3);
    color: var(--off-white) !important;
    border-radius: 10px;
    padding: 12px 15px;
    transition: var(--transition);
}

.form-control::placeholder {
    color: #aaa !important;
    opacity: 1;
}

.form-control:focus {
    background: rgba(50, 50, 50, 0.9);
    border-color: var(--gold);
    color: white !important;
    box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
}

.form-label {
    color: var(--gold-light) !important;
    font-weight: 500;
    margin-bottom: 8px;
}

.required::after {
    content: " *";
    color: #ff6b6b;
}

/* Text muted in checkout form */
.checkout-box .text-muted {
    color: #aaa !important;
}

/* ORDER SUMMARY BOX - CRITICAL FIXES */
.order-summary-box {
    background: linear-gradient(145deg, #1e1e1e, #151515) !important;
    border: 1px solid rgba(212, 175, 55, 0.3) !important;
    border-radius: 20px;
    padding: 25px;
    position: sticky;
    top: 120px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    color: var(--off-white) !important;
}

/* Force all text in order summary to be visible */
.order-summary-box,
.order-summary-box * {
    color: var(--off-white) !important;
}

.order-summary-box .gold-text {
    color: var(--gold) !important;
    background: none !important;
    -webkit-text-fill-color: var(--gold) !important;
    background-clip: initial !important;
}

.order-summary-box .text-success {
    color: #2dce89 !important;
}

.order-summary-box .text-muted {
    color: #aaa !important;
}

.order-summary-title {
    color: var(--gold) !important;
    font-family: "Cinzel", serif;
    border-bottom: 2px solid var(--gold);
    padding-bottom: 15px;
    margin-bottom: 20px;
    font-size: 1.4rem;
}

/* Cart Items in Summary */
.cart-item-small {
    border-bottom: 1px solid rgba(212, 175, 55, 0.1);
    padding: 12px 0;
}

.cart-item-small:last-child {
    border-bottom: none;
}

.cart-item-small strong {
    color: white !important;
    font-family: "Playfair Display", serif;
    font-weight: 600;
    font-size: 1rem;
}

.cart-item-small .text-gold {
    color: var(--gold) !important;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Order Summary Text Colors */
.order-summary-box .d-flex.justify-content-between .text-muted {
    color: #bbb !important;
    font-size: 0.95rem;
    font-weight: 400;
}

.order-summary-box .d-flex.justify-content-between span:not(.text-muted):not(.text-success):not(.gold-text) {
    color: var(--gold-light) !important;
    font-weight: 500;
}

/* Total Amount */
.total-amount {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--gold) !important;
    font-family: "Cinzel", serif;
    text-shadow: 0 0 10px rgba(212, 175, 55, 0.3);
}

.order-summary-box h5 {
    color: white !important;
    font-family: "Playfair Display", serif;
    font-size: 1.2rem;
}

/* Payment Methods */
.payment-method {
    background: rgba(212, 175, 55, 0.1);
    border: 2px solid rgba(212, 175, 55, 0.2);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: var(--transition);
    color: var(--off-white) !important;
}

/* Force all text in payment method */
.payment-method,
.payment-method * {
    color: var(--off-white) !important;
}

.payment-method:hover {
    border-color: var(--gold);
    background: rgba(212, 175, 55, 0.2);
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(212, 175, 55, 0.2);
}

.payment-method.selected {
    border-color: var(--gold);
    background: rgba(212, 175, 55, 0.25);
    box-shadow: 0 5px 25px rgba(212, 175, 55, 0.3);
}

.payment-method i {
    color: var(--gold) !important;
    font-size: 1.3rem;
    margin-right: 10px;
}

/* Card Details */
.card-details {
    display: none;
    padding: 20px;
    background: rgba(30, 30, 30, 0.5);
    border-radius: 10px;
    margin-top: 15px;
    border: 1px solid rgba(212, 175, 55, 0.2);
    color: var(--off-white) !important;
}

.card-details * {
    color: var(--off-white) !important;
}

/* Buttons */
.btn-checkout {
    background: linear-gradient(45deg, var(--gold), var(--gold-light));
    color: var(--charcoal) !important;
    border: none;
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 25px;
    transition: var(--transition);
    width: 100%;
    font-family: "Cinzel", serif;
    letter-spacing: 1px;
}

.btn-checkout:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(212, 175, 55, 0.4);
    color: white !important;
    background: linear-gradient(45deg, var(--gold-dark), var(--gold));
}

.btn-outline-gold {
    background: transparent;
    border: 2px solid var(--gold);
    color: var(--gold) !important;
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: 600;
    transition: var(--transition);
    width: 100%;
}

.btn-outline-gold:hover {
    background: var(--gold);
    color: var(--charcoal) !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
}

/* Step Indicator */
.step-indicator {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
    position: relative;
}

.step {
    text-align: center;
    flex: 1;
    position: relative;
    z-index: 1;
}

.step-number {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(212, 175, 55, 0.2);
    color: var(--gold-light);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-weight: bold;
    font-size: 1.2rem;
    transition: var(--transition);
    border: 2px solid rgba(212, 175, 55, 0.3);
}

.step.active .step-number {
    background: linear-gradient(45deg, var(--gold), var(--gold-light));
    color: var(--charcoal);
    border-color: var(--gold);
    box-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
}

.step.completed .step-number {
    background: #2dce89;
    color: white;
    border-color: #2dce89;
}

.step-text {
    color: #aaa !important;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.step.active .step-text {
    color: var(--gold) !important;
    font-weight: 600;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 25px;
    right: -50%;
    width: 100%;
    height: 2px;
    background: rgba(212, 175, 55, 0.3);
    z-index: 0;
}

.step.completed:not(:last-child)::after {
    background: #2dce89;
}

/* Security Note */
.security-note {
    background: rgba(23, 162, 184, 0.1);
    border-left: 4px solid #17a2b8;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
    color: #17a2b8 !important;
}

.security-note * {
    color: #17a2b8 !important;
}

/* Error Messages */
.error-message {
    color: #ff6b6b !important;
    font-size: 0.85rem;
    margin-top: 5px;
    font-weight: 500;
}

/* Form Check */
.form-check-input:checked {
    background-color: var(--gold);
    border-color: var(--gold);
}

.form-check-label {
    color: var(--off-white) !important;
}

/* Links */
a {
    color: var(--gold) !important;
    text-decoration: none;
    transition: var(--transition);
}

a:hover {
    color: var(--gold-light) !important;
    text-decoration: underline;
}

/* Borders */
.border-gold {
    border-color: rgba(212, 175, 55, 0.3) !important;
}

/* Override Bootstrap text-dark, text-black, and other dark classes */
.text-dark, .text-black, .text-body {
    color: var(--off-white) !important;
}

/* Fix all form text */
.form-text,
.help-block,
.help-text {
    color: #aaa !important;
}

/* Fix textarea */
textarea.form-control {
    color: var(--off-white) !important;
    background: rgba(40, 40, 40, 0.8) !important;
}

textarea.form-control::placeholder {
    color: #aaa !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .checkout-container {
        padding-top: 80px;
    }
    
    .checkout-box {
        padding: 20px;
    }
    
    .order-summary-box {
        position: static;
        margin-top: 30px;
    }
    
    .step:not(:last-child)::after {
        display: none;
    }
    
    .step-indicator {
        flex-wrap: wrap;
    }
    
    .step {
        flex: 0 0 33.33%;
        margin-bottom: 20px;
    }
    
    .checkout-title {
        font-size: 2rem;
    }
    
    .total-amount {
        font-size: 1.5rem;
    }
}

/* Small mobile devices */
@media (max-width: 576px) {
    .checkout-title {
        font-size: 1.8rem;
    }
    
    .form-section {
        padding: 20px;
    }
    
    .payment-method {
        padding: 15px;
    }
    
    .btn-checkout,
    .btn-outline-gold {
        padding: 12px 20px;
        font-size: 1rem;
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
                    <a href="cart.php" class="nav-link">
                        <i></i>Cart
                    </a>
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

    <!-- Checkout Section -->
    <div class="checkout-container">
        <div class="container">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Step Progress Indicator -->
            <div class="step-indicator">
                <div class="step completed">
                    <div class="step-number">1</div>
                    <div class="step-text">Cart</div>
                </div>
                <div class="step active">
                    <div class="step-number">2</div>
                    <div class="step-text">Checkout</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-text">Confirm</div>
                </div>
            </div>
            
            <!-- Checkout Form -->
            <form method="POST" action="checkout.php" onsubmit="return validateCheckout()">
                <div class="row">
                    <!-- Left Column: Forms -->
                    <div class="col-lg-8 mb-4">
                        <div class="checkout-box">
                            <div class="checkout-header">
                                <h1 class="checkout-title"><i class="fas fa-shopping-bag me-3"></i>Checkout</h1>
                                <p class="text-muted">Complete your purchase with luxury</p>
                            </div>
                            
                            <!-- Shipping Information -->
                            <div class="form-section">
                                <h3><i class="fas fa-truck me-2"></i>Shipping Information</h3>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">Full Name</label>
                                        <input type="text" class="form-control" name="full_name" 
                                               value="<?php echo htmlspecialchars($user['fullname']); ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">Email Address</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                                               required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label required">Shipping Address</label>
                                    <input type="text" class="form-control" name="shipping_address" 
                                           placeholder="Street Address" 
                                           value="<?php 
                                               if ($address_row) {
                                                   echo htmlspecialchars($address_row['address_line']);
                                               } elseif ($user_info) {
                                                   echo htmlspecialchars($user_info['address']);
                                               }
                                           ?>" 
                                           required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">City</label>
                                        <input type="text" class="form-control" name="shipping_city" 
                                               placeholder="City" 
                                               value="<?php 
                                                   if ($address_row) {
                                                       echo htmlspecialchars($address_row['city']);
                                                   }
                                               ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">ZIP Code</label>
                                        <input type="text" class="form-control" name="shipping_zip" 
                                               placeholder="ZIP Code" 
                                               value="<?php 
                                                   if ($address_row) {
                                                       echo htmlspecialchars($address_row['postal_code']);
                                                   }
                                               ?>" 
                                               required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label required">Phone Number</label>
                                    <input type="tel" class="form-control" name="shipping_phone" 
                                           placeholder="Phone Number" 
                                           value="<?php 
                                               if ($address_row) {
                                                   echo htmlspecialchars($address_row['phone']);
                                               } elseif ($user_info) {
                                                   echo htmlspecialchars($user_info['contact_no']);
                                               }
                                           ?>" 
                                           required>
                                    <div class="error-message" id="phone-error"></div>
                                </div>
                            </div>
                            
                            <!-- Payment Method -->
                            <div class="form-section">
                                <h3><i class="fas fa-credit-card me-2"></i>Payment Method</h3>
                                
                                <div class="payment-method" onclick="selectPaymentMethod('cod')">
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" name="payment_method" 
                                               value="cod" id="cod" checked onclick="selectPaymentMethod('cod')">
                                        <label class="form-check-label" for="cod">
                                            <i class="fas fa-money-bill-wave"></i> Cash on Delivery (COD)
                                        </label>
                                        <p class="text-muted mb-0 mt-2">Pay when you receive your order</p>
                                    </div>
                                </div>
                                
                                <div class="payment-method" onclick="selectPaymentMethod('credit_card')">
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" name="payment_method" 
                                               value="credit_card" id="credit_card" onclick="selectPaymentMethod('credit_card')">
                                        <label class="form-check-label" for="credit_card">
                                            <i class="fas fa-credit-card"></i> Credit/Debit Card
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Card Details -->
                                <div id="cardDetails" class="card-details">
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Card Number</label>
                                            <input type="text" class="form-control" name="card_number" 
                                                   placeholder="1234 5678 9012 3456">
                                            <div class="error-message" id="card-error"></div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Expiry Date</label>
                                            <input type="month" class="form-control" name="card_expiry">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">CVV</label>
                                            <input type="text" class="form-control" name="card_cvv" 
                                                   placeholder="123" maxlength="3">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="payment-method" onclick="selectPaymentMethod('gcash')">
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" name="payment_method" 
                                               value="gcash" id="gcash" onclick="selectPaymentMethod('gcash')">
                                        <label class="form-check-label" for="gcash">
                                            <i class="fas fa-mobile-alt"></i> GCash
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="security-note">
                                    <i class="fas fa-lock me-2"></i> Your payment information is secure and encrypted.
                                </div>
                            </div>
                            
                            <!-- Order Notes -->
                            <div class="form-section">
                                <h3><i class="fas fa-sticky-note me-2"></i>Order Notes (Optional)</h3>
                                <textarea class="form-control" name="order_notes" rows="4" 
                                          placeholder="Special instructions for your order..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column: Order Summary -->
                    <div class="col-lg-4">
                        <div class="order-summary-box">
                            <h3 class="order-summary-title"><i class="fas fa-receipt me-2"></i>Order Summary</h3>
                            
                            <!-- Cart Items -->
                            <div class="mb-4">
                                <?php foreach ($cart_items as $item): 
                                    $item_price = ($item['is_on_sale'] == 'Y' && $item['discount_price'] > 0) 
                                                ? $item['discount_price'] 
                                                : $item['item_price'];
                                ?>
                                    <div class="cart-item-small">
                                        <div class="row">
                                            <div class="col-8">
                                                <strong class="text-truncate d-block" title="<?php echo htmlspecialchars($item['item_name']); ?>">
                                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                                </strong>
                                                <small class="text-gold">Qty: <?php echo $item['quantity']; ?></small>
                                            </div>
                                            <div class="col-4 text-end">
                                                <span class="gold-text">₱<?php echo number_format($item_price * $item['quantity'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Order Totals -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subtotal:</span>
                                    <span>₱<?php echo number_format($total_price, 2); ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Shipping:</span>
                                    <span class="text-success">FREE</span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Tax (12%):</span>
                                    <span>₱<?php echo number_format($tax, 2); ?></span>
                                </div>
                                
                                <hr class="border-gold">
                                
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <h5 class="mb-0">Total:</h5>
                                    <h3 class="total-amount mb-0">₱<?php echo number_format($grand_total, 2); ?></h3>
                                </div>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label text-muted" for="terms">
                                    I agree to the <a href="terms.php" target="_blank" class="gold-text">Terms and Conditions</a>
                                </label>
                                <div class="error-message" id="terms-error"></div>
                            </div>
                            
                            <!-- Place Order Button -->
                            <button type="submit" name="place_order" class="btn btn-checkout">
                                <i class="fas fa-check-circle me-2"></i> PLACE ORDER
                            </button>
                            
                            <!-- Back to Cart -->
                            <a href="cart.php" class="btn btn-outline-gold mt-3 w-100">
                                <i class="fas fa-arrow-left me-2"></i> Back to Cart
                            </a>
                            
                            <!-- Security Info -->
                            <div class="mt-4 pt-3 border-top border-gold text-center">
                                <small class="text-muted d-block mb-2">
                                    <i class="fas fa-shield-alt me-1"></i> 100% Secure Payment
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-undo me-1"></i> 30-Day Return Policy
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
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
    
    <!-- JavaScript for Checkout Page -->
    <script>
    // Function to select payment method
    function selectPaymentMethod(method) {
        // Update radio button
        document.getElementById(method).checked = true;
        
        // Remove 'selected' class from all payment methods
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Add 'selected' class to clicked payment method
        document.querySelector(`#${method}`).closest('.payment-method').classList.add('selected');
        
        // Show/hide card details
        const cardDetails = document.getElementById('cardDetails');
        if (method === 'credit_card') {
            cardDetails.style.display = 'block';
        } else {
            cardDetails.style.display = 'none';
        }
    }
    
    // Run when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Select COD by default
        selectPaymentMethod('cod');
        
        // Set default card expiry to next month
        const now = new Date();
        const nextMonth = new Date(now.getFullYear(), now.getMonth() + 1, 1);
        const expiryInput = document.querySelector('input[name="card_expiry"]');
        if (expiryInput) {
            const year = nextMonth.getFullYear();
            const month = String(nextMonth.getMonth() + 1).padStart(2, '0');
            expiryInput.value = `${year}-${month}`;
        }
    });
    
    // Function to validate form before submission
    function validateCheckout() {
        let isValid = true;
        
        // Clear previous error messages
        document.querySelectorAll('.error-message').forEach(el => {
            el.textContent = '';
        });
        
        // Validate phone number
        const phone = document.querySelector('input[name="shipping_phone"]').value;
        const phoneRegex = /^[0-9]{10,11}$/;
        if (!phoneRegex.test(phone.replace(/\D/g, ''))) {
            document.getElementById('phone-error').textContent = 'Please enter a valid phone number';
            isValid = false;
        }
        
        // Validate credit card if selected
        if (document.getElementById('credit_card').checked) {
            const cardNumber = document.querySelector('input[name="card_number"]').value.replace(/\s/g, '');
            const cardRegex = /^[0-9]{13,19}$/;
            
            if (!cardRegex.test(cardNumber)) {
                document.getElementById('card-error').textContent = 'Please enter a valid card number';
                isValid = false;
            }
            
            // Check expiry date
            const expiry = document.querySelector('input[name="card_expiry"]').value;
            if (expiry) {
                const [year, month] = expiry.split('-');
                const expiryDate = new Date(year, month - 1, 1);
                const now = new Date();
                const currentMonth = new Date(now.getFullYear(), now.getMonth(), 1);
                
                if (expiryDate < currentMonth) {
                    document.getElementById('card-error').textContent = 'Card has expired';
                    isValid = false;
                }
            }
        }
        
        // Validate terms agreement
        if (!document.getElementById('terms').checked) {
            document.getElementById('terms-error').textContent = 'You must agree to the terms and conditions';
            isValid = false;
        }
        
        // Scroll to first error if validation failed
        if (!isValid) {
            const firstError = document.querySelector('.error-message:not(:empty)');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        return isValid;
    }
    
    // Format card number as user types
    document.addEventListener('input', function(e) {
        if (e.target.name === 'card_number') {
            let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
            let formatted = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) formatted += ' ';
                formatted += value[i];
            }
            e.target.value = formatted;
        }
        
        // Format phone number as user types
        if (e.target.name === 'shipping_phone') {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 3 && value.length <= 6) {
                value = value.replace(/(\d{3})(\d+)/, '$1-$2');
            } else if (value.length > 6 && value.length <= 10) {
                value = value.replace(/(\d{3})(\d{3})(\d+)/, '$1-$2-$3');
            } else if (value.length > 10) {
                value = value.replace(/(\d{4})(\d{3})(\d+)/, '$1-$2-$3');
            }
            e.target.value = value;
        }
    });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>