<?php
session_start();

// Database connection
$host = '127.0.0.1';
$dbname = 'stylenwear_db';
$username = 'root';
$password = '';

// Connect to database
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Show cart message from products.php if exists
$success_message = '';
if (isset($_SESSION['cart_message'])) {
    $success_message = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']); // Clear after showing
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_cart'])) {
        // Update quantity
        $item_id = $_POST['item_id'];
        $new_quantity = $_POST['quantity'];
        
        // Get cart ID for this user
        $cart_sql = "SELECT cart_id FROM carts WHERE user_id = $user_id";
        $cart_result = $conn->query($cart_sql);
        
        if ($cart_result->num_rows > 0) {
            $cart_row = $cart_result->fetch_assoc();
            $cart_id = $cart_row['cart_id'];
            
            if ($new_quantity <= 0) {
                // Remove item if quantity is 0 or less
                $delete_sql = "DELETE FROM cart_items WHERE cart_id = $cart_id AND item_id = $item_id";
                $conn->query($delete_sql);
                $message = "Item removed from cart!";
            } else {
                // Update quantity
                $update_sql = "UPDATE cart_items SET quantity = $new_quantity 
                               WHERE cart_id = $cart_id AND item_id = $item_id";
                $conn->query($update_sql);
                $message = "Cart updated!";
            }
        }
    }
    
    if (isset($_POST['remove_item'])) {
        // Remove item
        $item_id = $_POST['item_id'];
        
        // Get cart ID
        $cart_sql = "SELECT cart_id FROM carts WHERE user_id = $user_id";
        $cart_result = $conn->query($cart_sql);
        
        if ($cart_result->num_rows > 0) {
            $cart_row = $cart_result->fetch_assoc();
            $cart_id = $cart_row['cart_id'];
            
            $delete_sql = "DELETE FROM cart_items WHERE cart_id = $cart_id AND item_id = $item_id";
            $conn->query($delete_sql);
            $message = "Item removed from cart!";
        }
    }
    
    if (isset($_POST['clear_cart'])) {
        // Clear all items from cart
        $cart_sql = "SELECT cart_id FROM carts WHERE user_id = $user_id";
        $cart_result = $conn->query($cart_sql);
        
        if ($cart_result->num_rows > 0) {
            $cart_row = $cart_result->fetch_assoc();
            $cart_id = $cart_row['cart_id'];
            
            $clear_sql = "DELETE FROM cart_items WHERE cart_id = $cart_id";
            $conn->query($clear_sql);
            $message = "Cart cleared!";
        }
    }
}

// Get user's cart
$cart_sql = "SELECT cart_id FROM carts WHERE user_id = $user_id";
$cart_result = $conn->query($cart_sql);

$cart_items = array();
$total_price = 0;
$item_count = 0;

if ($cart_result->num_rows > 0) {
    $cart_row = $cart_result->fetch_assoc();
    $cart_id = $cart_row['cart_id'];
    
    // Get all items in the cart
    $items_sql = "SELECT cart_items.*, items.*, category.category_name 
                  FROM cart_items 
                  JOIN items ON cart_items.item_id = items.item_id 
                  LEFT JOIN category ON items.category_id = category.category_id 
                  WHERE cart_items.cart_id = $cart_id";
    $items_result = $conn->query($items_sql);
    
    if ($items_result->num_rows > 0) {
        while($row = $items_result->fetch_assoc()) {
            // Calculate discount if item is on sale
            if ($row['is_on_sale'] == 'Y' && $row['item_price'] > 0) {
                if (!isset($row['discount_price']) || $row['discount_price'] <= 0) {
                    $row['discount_price'] = $row['item_price'];
                }
                $row['discount_amount'] = $row['item_price'] - $row['discount_price'];
                if ($row['item_price'] > 0) {
                    $row['discount_percentage'] = round(($row['discount_amount'] / $row['item_price']) * 100);
                }
                $row['final_price'] = $row['discount_price'];
            } else {
                $row['final_price'] = $row['item_price'];
            }
            
            $cart_items[] = $row;
            $total_price += $row['final_price'] * $row['quantity'];
            $item_count += $row['quantity'];
        }
    }
} else {
    // Create a cart for user if they don't have one
    $create_sql = "INSERT INTO carts (user_id) VALUES ($user_id)";
    $conn->query($create_sql);
}

// Calculate tax and grand total
$tax = $total_price * 0.12;
$grand_total = $total_price + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Style'n Wear</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .cart-item-card {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: var(--transition);
        }
        
        .cart-item-card:hover {
            border-color: var(--gold);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.1);
        }
        
        .product-image-cart {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid var(--gold);
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid var(--gold);
            color: var(--gold);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .quantity-btn:hover {
            background: var(--gold);
            color: var(--charcoal);
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--gold);
            border-radius: 5px;
            color: var(--off-white);
            padding: 5px;
        }
        
        .summary-box {
            background: rgba(30, 30, 30, 0.7);
            border-radius: 15px;
            border: 2px solid var(--gold);
            padding: 25px;
            position: sticky;
            top: 100px;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            border-radius: 15px;
            background: rgba(30, 30, 30, 0.7);
            border: 2px solid var(--gold);
        }
        
        .empty-cart-icon {
            font-size: 4rem;
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        
        .btn-gold {
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            color: var(--charcoal);
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            font-family: "Cinzel", serif;
            letter-spacing: 1px;
            transition: var(--transition);
        }
        
        .btn-gold:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.4);
            color: white;
        }
        
        .btn-outline-gold {
            border: 1px solid var(--gold);
            color: var(--gold);
            background: transparent;
            padding: 10px 25px;
            border-radius: 25px;
            transition: var(--transition);
        }
        
        .btn-outline-gold:hover {
            background: var(--gold);
            color: var(--charcoal);
        }
        
        .cart-header {
            border-bottom: 2px solid var(--gold);
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        
        .price-highlight {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--gold);
        }
        
        .cart-badge {
            background: linear-gradient(45deg, #ff0000, #ff6b6b);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
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
                    <a href="cart.php" class="nav-link active">Cart</a>
                    <a href="my_orders.php" class="nav-link">My Orders</a>
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
                <a href="cart.php" class="nav-link active">Cart</a>
                <a href="my_orders.php" class="nav-link">My Orders</a>
                <a href="../logout.php" class="nav-link logout-btn mt-4">Logout</a>
            </div>
        </div>
    </div>

    <!-- Cart Hero Section -->
    <section class="hero-section d-flex align-items-center" style="min-height: 200px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h1 class="hero-title gold-text">My Shopping Cart</h1>
                    <p class="hero-subtitle mx-auto" style="text-align: center;">
                        <?php echo $item_count > 0 ? "You have $item_count item(s) in your cart" : "Your fashion journey awaits"; ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Cart Content Section -->
    <section class="products-section" style="padding-top: 50px;">
        <div class="container">
            <!-- Show success message from products.php -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" 
                     style="background: rgba(45, 206, 137, 0.2); border-color: #2dce89; color: #2dce89;">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Show messages if any from cart actions -->
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" 
                     style="background: rgba(45, 206, 137, 0.2); border-color: #2dce89; color: #2dce89;">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="cart-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="section-title gold-text mb-0">
                        <i class="fas fa-shopping-bag me-2"></i>Shopping Cart
                    </h2>
                    <span style="color: var(--gold-light);">
                        <i class="fas fa-box me-1"></i> <?php echo $item_count; ?> item(s)
                    </span>
                </div>
            </div>
            
            <div class="row">
                <!-- Cart Items Column -->
                <div class="col-lg-8">
                    <?php if (empty($cart_items)): ?>
                        <!-- Empty Cart -->
                        <div class="empty-cart">
                            <div class="empty-cart-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <h3 class="gold-text mb-3">Your Cart is Empty</h3>
                            <p style="color: #aaa; margin-bottom: 30px;">
                                Looks like you haven't added any stylish items to your cart yet.
                            </p>
                            <a href="products.php" class="btn-gold">
                                <i class="fas fa-tshirt me-2"></i>Browse Collections
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Cart Items List -->
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item-card">
                                <div class="row align-items-center">
                                    <!-- Product Image -->
                                    <div class="col-md-3 col-4">
                                        <?php 
                                        $image_url = !empty($item['image_url']) ? $item['image_url'] : 'default.jpg';
                                        $image_filename = basename($image_url);
                                        ?>
                                        <img src="../image/<?php echo htmlspecialchars($image_filename); ?>" 
                                             class="product-image-cart"
                                             alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                                    </div>
                                    
                                    <!-- Product Details -->
                                    <div class="col-md-5 col-8">
                                        <h5 class="mb-2"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                        <div class="mb-2">
                                            <span class="badge" style="background: rgba(212, 175, 55, 0.2); color: var(--gold-light);">
                                                <?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?>
                                            </span>
                                            <?php if ($item['is_on_sale'] == 'Y'): ?>
                                                <span class="cart-badge ms-2">SALE</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <?php if ($item['stock'] > 0): ?>
                                                <span style="color: #2dce89;">
                                                    <i class="fas fa-check-circle me-1"></i> In Stock
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #ff6b6b;">
                                                    <i class="fas fa-times-circle me-1"></i> Out of Stock
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Price Display -->
                                        <div>
                                            <?php if ($item['is_on_sale'] == 'Y'): ?>
                                                <div class="d-flex align-items-center">
                                                    <span class="price-highlight me-3">
                                                        ₱<?php echo number_format($item['final_price'], 2); ?>
                                                    </span>
                                                    <span style="text-decoration: line-through; color: #888; font-size: 0.9rem;">
                                                        ₱<?php echo number_format($item['item_price'], 2); ?>
                                                    </span>
                                                    <span class="badge bg-danger ms-2">
                                                        Save <?php echo $item['discount_percentage'] ?? 20; ?>%
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="price-highlight">
                                                    ₱<?php echo number_format($item['final_price'], 2); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Quantity and Actions -->
                                    <div class="col-md-4 col-12 mt-3 mt-md-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <!-- Quantity Control -->
                                            <div class="quantity-control">
                                                <form method="POST" action="cart.php" class="d-flex align-items-center">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                    <button type="button" class="quantity-btn minus-btn" 
                                                            onclick="this.nextElementSibling.stepDown(); this.nextElementSibling.onchange();">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" 
                                                           name="quantity" 
                                                           value="<?php echo $item['quantity']; ?>" 
                                                           min="1" 
                                                           max="<?php echo $item['stock']; ?>"
                                                           class="quantity-input"
                                                           onchange="this.form.submit()">
                                                    <input type="hidden" name="update_cart" value="1">
                                                    <button type="button" class="quantity-btn plus-btn" 
                                                            onclick="this.previousElementSibling.stepUp(); this.previousElementSibling.onchange();">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <!-- Remove Button -->
                                            <form method="POST" action="cart.php" class="d-inline">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                <button type="submit" name="remove_item" value="1" 
                                                        class="btn btn-sm" 
                                                        style="background: rgba(255, 107, 107, 0.2); color: #ff6b6b; border: none;"
                                                        onclick="return confirm('Remove this item from cart?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <!-- Item Total -->
                                        <div class="text-end mt-3">
                                            <strong style="color: var(--gold);">
                                                ₱<?php echo number_format($item['final_price'] * $item['quantity'], 2); ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Cart Actions -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="products.php" class="btn-outline-gold">
                                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                            </a>
                            
                            <div class="d-flex gap-2">
                                <form method="POST" action="cart.php">
                                    <button type="submit" name="clear_cart" value="1" 
                                            class="btn" 
                                            style="background: rgba(255, 107, 107, 0.2); color: #ff6b6b; border: none;"
                                            onclick="return confirm('Clear all items from your cart?')">
                                        <i class="fas fa-trash-alt me-2"></i>Clear Cart
                                    </button>
                                </form>
                                
                                <a href="checkout.php" class="btn-gold">
                                    <i class="fas fa-shopping-bag me-2"></i>Update Cart
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Order Summary Column -->
                <div class="col-lg-4">
                    <div class="summary-box">
                        <h3 class="gold-text mb-4">Order Summary</h3>
        
                        <div class="mb-3">
                            <!-- Subtotal -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex flex-column">
                                    <span class="fw-medium" style="color: #aaa;">Subtotal</span>
                                    <small class="text-muted" style="font-size: 0.8rem; color: #777 !important;">
                                        <?php echo $item_count; ?> item<?php echo $item_count != 1 ? 's' : ''; ?>
                                    </small>
                                </div>
                                <span class="price-highlight">₱<?php echo number_format($total_price, 2); ?></span>
                            </div>
            
                            <!-- Shipping -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex flex-column">
                                    <span class="fw-medium" style="color: #aaa;">Shipping</span>
                                    <small class="text-success" style="font-size: 0.8rem;">
                                        <i class="fas fa-shipping-fast me-1"></i>Free Shipping
                                    </small>
                                </div>
                                <span style="color: #2dce89; font-weight: 600;">FREE</span>
                            </div>
            
                            <!-- Tax -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-medium" style="color: #aaa;">Tax (12%)</span>
                                <span>₱<?php echo number_format($tax, 2); ?></span>
                            </div>
            
                            <hr style="border-color: rgba(212, 175, 55, 0.3); margin: 20px 0;">
            
                            <!-- Total -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="d-flex flex-column">
                                    <h4 class="mb-0">Total</h4>
                                    <small class="text-muted" style="font-size: 0.8rem; color: #777 !important;">
                                        Including all taxes
                                    </small>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <h3 class="gold-text mb-0">₱<?php echo number_format($grand_total, 2); ?></h3>
                                    <small class="text-muted" style="font-size: 0.8rem; color: #777 !important;">
                                        <i class="fas fa-info-circle me-1"></i>PHP
                                    </small>
                                </div>
                            </div>
                        </div>
        
                        <?php if (!empty($cart_items)): ?>
                            <a href="checkout.php" class="btn-gold w-100 py-3 mb-3 d-flex justify-content-center align-items-center">
                                <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
            
                            <div class="text-center">
                                <small style="color: #aaa;">
                                    <i class="fas fa-lock me-1"></i>Secure & encrypted checkout
                                </small>
                            </div>
                        <?php endif; ?>
        
                        <!-- Payment Icons -->
                        <div class="mt-4 pt-3 border-top" style="border-color: rgba(212, 175, 55, 0.3);">
                            <p class="small mb-2" style="color: #aaa;">We Accept:</p>
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                <i class="fab fa-cc-visa fa-2x" style="color: #1a1f71;"></i>
                                <i class="fab fa-cc-mastercard fa-2x" style="color: #eb001b;"></i>
                                <i class="fab fa-cc-paypal fa-2x" style="color: #003087;"></i>
                                <i class="fab fa-cc-amazon-pay fa-2x" style="color: #ff9900;"></i>
                                <i class="fab fa-cc-amex fa-2x" style="color: #2e77bc;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
        });
        
        // Quantity button functionality
        document.querySelectorAll('.minus-btn, .plus-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.classList.contains('minus-btn') 
                    ? this.nextElementSibling 
                    : this.previousElementSibling;
                
                // Update the value
                if (this.classList.contains('minus-btn')) {
                    if (parseInt(input.value) > 1) {
                        input.stepDown();
                        input.onchange();
                    }
                } else {
                    if (parseInt(input.value) < parseInt(input.max)) {
                        input.stepUp();
                        input.onchange();
                    }
                }
            });
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>