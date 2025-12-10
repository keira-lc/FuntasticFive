<?php
session_start();

// Database connection
$host = '127.0.0.1';
$dbname = 'stylenwear_db';
$username = 'root';
$password = '';

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$item_id = intval($_GET['id']);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get product details
    $stmt = $pdo->prepare("
        SELECT i.*, c.category_name 
        FROM items i 
        LEFT JOIN category c ON i.category_id = c.category_id 
        WHERE i.item_id = ?
    ");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        header('Location: products.php');
        exit();
    }
    
    // Calculate discount if on sale
    if ($item['is_on_sale'] == 'Y' && $item['item_price'] > 0) {
        if (!isset($item['discount_price']) || $item['discount_price'] <= 0) {
            $item['discount_price'] = $item['item_price'];
        }
        $item['discount_amount'] = $item['item_price'] - $item['discount_price'];
        if ($item['item_price'] > 0) {
            $item['discount_percentage'] = round(($item['discount_amount'] / $item['item_price']) * 100);
        }
    }
    
    // Handle Add to Cart form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php?redirect=product-detail.php?id=' . $item_id);
            exit();
        }
        
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        // Get user's cart ID
        $cartStmt = $pdo->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
        $cartStmt->execute([$_SESSION['user_id']]);
        $cart = $cartStmt->fetch();
        
        if ($cart) {
            $cart_id = $cart['cart_id'];
            
            // Check if item already in cart
            $checkStmt = $pdo->prepare("SELECT * FROM cart_items WHERE cart_id = ? AND item_id = ?");
            $checkStmt->execute([$cart_id, $item_id]);
            $existing = $checkStmt->fetch();
            
            if ($existing) {
                $updateStmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE cart_item_id = ?");
                $updateStmt->execute([$quantity, $existing['cart_item_id']]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO cart_items (cart_id, item_id, quantity) VALUES (?, ?, ?)");
                $insertStmt->execute([$cart_id, $item_id, $quantity]);
            }
            
            $success_message = "Item added to cart successfully!";
        }
    }
    
} catch(PDOException $e) {
    header('Location: products.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['item_name']); ?> - Style'n Wear</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .product-detail-section {
            padding: 100px 0 50px;
            background: linear-gradient(180deg, #0f0f0f 0%, #1a1a1a 100%);
        }
        
        .product-image-container {
            border-radius: 20px;
            overflow: hidden;
            border: 2px solid var(--gold);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.2);
            background: linear-gradient(145deg, #1e1e1e, #151515);
        }
        
        .product-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
        }
        
        .product-info-card {
            background: linear-gradient(145deg, #1e1e1e, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 20px;
            padding: 30px;
            height: 100%;
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.1);
        }
        
        .sale-badge-detail {
            position: absolute;
            top: 20px;
            left: 20px;
            background: linear-gradient(45deg, #ff0000, #ff6b6b);
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
            z-index: 2;
            box-shadow: 0 4px 15px rgba(255, 0, 0, 0.3);
            animation: pulse 2s infinite;
        }
        
        .stock-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .stock-available {
            background: rgba(45, 206, 137, 0.2);
            color: #2dce89;
        }
        
        .stock-low {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .stock-out {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .quantity-btn {
            width: 45px;
            height: 45px;
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
            width: 80px;
            text-align: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--gold);
            border-radius: 10px;
            color: var(--off-white);
            padding: 10px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .btn-add-to-cart {
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            color: var(--charcoal);
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-family: "Cinzel", serif;
            letter-spacing: 1px;
            transition: var(--transition);
            font-size: 1.1rem;
            width: 100%;
        }
        
        .btn-add-to-cart:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.4);
            color: white;
        }
        
        .btn-back {
            background: transparent;
            border: 1px solid var(--gold);
            color: var(--gold);
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-family: "Cinzel", serif;
            letter-spacing: 1px;
            transition: var(--transition);
            font-size: 1.1rem;
            width: 100%;
        }
        
        .btn-back:hover {
            background: var(--gold);
            color: var(--charcoal);
        }
        
        .description-box {
            background: rgba(30, 30, 30, 0.5);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(212, 175, 55, 0.2);
            margin-top: 30px;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @media (max-width: 768px) {
            .product-image {
                height: 350px;
            }
            
            .quantity-control {
                justify-content: center;
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
                    <a href="products.php" class="nav-link active">Products</a>
                    <a href="contact.php" class="nav-link">Contact</a>
                    <a href="cart.php" class="nav-link">Cart</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="my_orders.php" class="nav-link">My Orders</a>
                        <a href="../logout.php" class="nav-link logout-btn">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link logout-btn">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    <?php endif; ?>
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
                <a href="products.php" class="nav-link active">Products</a>
                <a href="contact.php" class="nav-link">Contact</a>
                <a href="cart.php" class="nav-link">Cart</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="my_orders.php" class="nav-link">My Orders</a>
                    <a href="../logout.php" class="nav-link logout-btn mt-4">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link logout-btn mt-4">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <?php if (isset($success_message)): ?>
    <div class="container mt-4">
        <div class="alert alert-success alert-dismissible fade show" 
             style="background: rgba(45, 206, 137, 0.2); border-color: #2dce89; color: #2dce89; border-radius: 15px;">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            <a href="cart.php" class="alert-link ms-2" style="color: #2dce89; font-weight: 600;">View Cart</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: invert(1);"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Product Detail Section -->
    <section class="product-detail-section">
        <div class="container">
            <div class="row g-5">
                <!-- Product Images -->
                <div class="col-lg-6">
                    <div class="product-image-container position-relative">
                        <?php if ($item['is_on_sale'] == 'Y'): ?>
                        <div class="sale-badge-detail">
                            <?= ($item['discount_percentage'] ?? 20) ?>% OFF
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        $image_url = !empty($item['image_url']) ? $item['image_url'] : 'default.jpg';
                        $image_filename = basename($image_url);
                        ?>
                        
                        <img src="../image/<?= htmlspecialchars($image_filename) ?>" 
                             class="product-image"
                             alt="<?= htmlspecialchars($item['item_name']) ?>">
                    </div>
                </div>
                
                <!-- Product Information -->
                <div class="col-lg-6">
                    <div class="product-info-card">
                        <div class="mb-4">
                            <h1 class="gold-text mb-2"><?php echo htmlspecialchars($item['item_name']); ?></h1>
                            <div class="d-flex align-items-center flex-wrap gap-3 mb-3">
                                <span class="badge" style="background: rgba(212, 175, 55, 0.2); color: var(--gold-light);">
                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                </span>
                                <span class="text-muted">SKU: <?php echo htmlspecialchars($item['item_sku']); ?></span>
                            </div>
                        </div>
                        
                        <!-- Price Display -->
                        <div class="mb-4">
                            <?php if ($item['is_on_sale'] == 'Y'): ?>
                            <div class="mb-3">
                                <div class="d-flex align-items-center flex-wrap gap-3">
                                    <h2 class="gold-text mb-0">₱<?php echo number_format($item['discount_price'] ?? $item['item_price'], 2); ?></h2>
                                    <span class="text-decoration-line-through" style="color: #888; font-size: 1.2rem;">
                                        ₱<?php echo number_format($item['item_price'], 2); ?>
                                    </span>
                                </div>
                                <div class="discount mt-2" style="color: var(--gold-light);">
                                    <i class="fas fa-tag me-1"></i>
                                    SAVE ₱<?php echo number_format($item['discount_amount'] ?? 0, 2); ?> 
                                    (<?= ($item['discount_percentage'] ?? 20) ?>% OFF)
                                </div>
                            </div>
                            <?php else: ?>
                            <h2 class="gold-text">₱<?php echo number_format($item['item_price'], 2); ?></h2>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Stock Status -->
                        <div class="mb-4">
                            <?php if ($item['stock'] > 10): ?>
                                <span class="stock-badge stock-available">
                                    <i class="fas fa-check-circle me-1"></i> In Stock (<?php echo $item['stock']; ?> available)
                                </span>
                            <?php elseif ($item['stock'] > 0): ?>
                                <span class="stock-badge stock-low">
                                    <i class="fas fa-exclamation-triangle me-1"></i> Low Stock (<?php echo $item['stock']; ?> left)
                                </span>
                            <?php else: ?>
                                <span class="stock-badge stock-out">
                                    <i class="fas fa-times-circle me-1"></i> Out of Stock
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Add to Cart Form -->
                        <form method="POST" action="" class="mt-4">
                            <div class="quantity-control">
                                <div class="d-flex align-items-center gap-3">
                                    <label class="form-label mb-0" style="color: var(--gold-light);">Quantity:</label>
                                    <button type="button" class="quantity-btn minus-btn">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           id="quantity" 
                                           name="quantity" 
                                           class="quantity-input" 
                                           value="1" 
                                           min="1" 
                                           max="<?php echo $item['stock']; ?>"
                                           readonly>
                                    <button type="button" class="quantity-btn plus-btn">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-3 mt-4">
                                <?php if ($item['stock'] > 0): ?>
                                <button type="submit" name="add_to_cart" class="btn-add-to-cart">
                                    <i class="fas fa-shopping-bag me-2"></i> ADD TO CART
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn-add-to-cart" disabled style="background: #666; cursor: not-allowed;">
                                    <i class="fas fa-times me-2"></i> OUT OF STOCK
                                </button>
                                <?php endif; ?>
                                
                                <a href="products.php" class="btn-back text-center">
                                    <i class="fas fa-arrow-left me-2"></i> BACK TO PRODUCTS
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Product Description -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="description-box">
                        <h3 class="gold-text mb-4">
                            <i class="fas fa-info-circle me-2"></i>Product Description
                        </h3>
                        <div style="color: #aaa; line-height: 1.8; font-size: 1.1rem;">
                            <?php echo nl2br(htmlspecialchars($item['item_description'])); ?>
                        </div>
                        
                        <!-- Additional Product Info -->
                        <div class="row mt-4 pt-4 border-top" style="border-color: rgba(212, 175, 55, 0.2);">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-shipping-fast fa-2x" style="color: var(--gold);"></i>
                                    </div>
                                    <div>
                                        <h6 class="gold-text mb-1">Free Shipping</h6>
                                        <p class="mb-0" style="color: #aaa; font-size: 0.9rem;">
                                            Free delivery on orders over ₱2,000
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-undo fa-2x" style="color: var(--gold);"></i>
                                    </div>
                                    <div>
                                        <h6 class="gold-text mb-1">Easy Returns</h6>
                                        <p class="mb-0" style="color: #aaa; font-size: 0.9rem;">
                                            30-day return policy
                                        </p>
                                    </div>
                                </div>
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
                    © <?= date('Y') ?> Style'n Wear. All rights reserved. | Luxury Redefined
                </p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Quantity control buttons
        const minusBtn = document.querySelector('.minus-btn');
        const plusBtn = document.querySelector('.plus-btn');
        const quantityInput = document.getElementById('quantity');
        
        minusBtn.addEventListener('click', function() {
            let value = parseInt(quantityInput.value);
            if (value > 1) {
                quantityInput.value = value - 1;
            }
        });
        
        plusBtn.addEventListener('click', function() {
            let value = parseInt(quantityInput.value);
            let max = parseInt(quantityInput.max);
            if (value < max) {
                quantityInput.value = value + 1;
            }
        });
        
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
    </script>
</body>
</html>