<?php
$host = '127.0.0.1';
$dbname = 'stylenwear_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // GET ALL ITEMS WITH CATEGORY NAMES
    $stmt = $pdo->query("SELECT i.*, c.category_name FROM items i LEFT JOIN category c ON i.category_id = c.category_id ORDER BY i.item_id");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process items to calculate discount percentage and amount
    foreach ($items as &$item) {
        if ($item['is_on_sale'] == 'Y' && $item['item_price'] > 0) {
            // Make sure discount_price is set and valid
            if (!isset($item['discount_price']) || $item['discount_price'] <= 0) {
                $item['discount_price'] = $item['item_price'];
            }
            
            // Calculate discount amount
            $item['discount_amount'] = $item['item_price'] - $item['discount_price'];
            
            // Calculate discount percentage
            if ($item['item_price'] > 0) {
                $item['discount_percentage'] = round(($item['discount_amount'] / $item['item_price']) * 100);
            } else {
                $item['discount_percentage'] = 0;
            }
        }
    }
    unset($item); // Unset reference
    
    // GET CATEGORIES FOR TABS - SIMPLE ARRAY
    $stmt2 = $pdo->query("SELECT category_id, category_name FROM category ORDER BY category_id");
    $categories = [];
    while($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $categories[$row['category_id']] = $row['category_name'];
    }
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Style'n Wear - Luxury Gold Fashion</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Luxury Navigation -->
    <nav class="luxury-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Brand -->
                <a href="#" class="brand-title gold-text">
                    Style<span style="color: var(--gold)">'n</span>Wear
                </a>
                
                <!-- Navigation -->
                <div class="d-none d-lg-flex align-items-center">
                    <a href="index.php" class="nav-link active">Home</a>
                    <a href="about.php" class="nav-link">About</a>
                    <a href="products.php" class="nav-link">Products</a>
                    <a href="contact.php" class="nav-link">Contact</a>
                    <a href="cart.php" class="nav-link">Cart</a>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title gold-text">Welcome to Style'n Wear</h1>
                    <p class="hero-subtitle">
                        Discover your unique style with our curated collection of fashionable apparel and accessories.
                    </p>
                    <button class="add-to-cart" style="max-width: 200px;">
                        SHOP NOW <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <h2 class="section-title gold-text">Exclusive Collection</h2>
            
            <!-- Category Tabs -->
            <div class="category-tabs">
                <button class="category-btn active" data-category="all">All Products</button>
                <?php foreach ($categories as $category_id => $category_name): ?>
                    <button class="category-btn" data-category="category-<?= $category_id ?>">
                        <?= htmlspecialchars($category_name) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <!-- Products Grid - FIXED -->
            <div class="row g-4" id="products-container">
                <?php foreach ($items as $item): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 col-12 product-item" data-category="category-<?= $item['category_id'] ?>">
                    <div class="product-card h-100 d-flex flex-column">
                        <?php if ($item['is_on_sale'] == 'Y'): ?>
                        <div class="sale-badge">
                            <?= ($item['discount_percentage'] ?? 20) ?>% OFF
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        // Get image URL from database
                        $image_url = !empty($item['image_url']) ? $item['image_url'] : 'default.jpg';
                        $image_filename = basename($image_url);
                        ?>
                        
                        <!-- Image container with fixed height -->
                        <div class="product-image-container" style="height: 250px; overflow: hidden; position: relative;">
                            <img src="../image/<?= htmlspecialchars($image_filename) ?>" 
                                 alt="<?= htmlspecialchars($item['item_name']) ?>"
                                 class="product-image"
                                 style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                        </div>
                        
                        <div class="product-content d-flex flex-column flex-grow-1 p-3">
                            <h4 class="product-title text-truncate" title="<?= htmlspecialchars($item['item_name']) ?>">
                                <?= htmlspecialchars($item['item_name']) ?>
                            </h4>
                            <span class="product-category"><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></span>
                            <div class="price-container mt-auto pt-3">
                                <?php if ($item['is_on_sale'] == 'Y'): ?>
                                <div class="d-flex align-items-center flex-wrap">
                                    <span class="current-price gold-text me-3">₱<?= number_format($item['discount_price'], 2) ?></span>
                                    <span class="original-price">₱<?= number_format($item['item_price'], 2) ?></span>
                                </div>
                                <div class="discount mt-2">
                                    SAVE ₱<?= number_format($item['discount_amount'] ?? 0, 2) ?> 
                                    (<?= ($item['discount_percentage'] ?? 20) ?>%)
                                </div>
                                <?php else: ?>
                                <span class="current-price gold-text">₱<?= number_format($item['item_price'], 2) ?></span>
                                <?php endif; ?>
                            </div>
                            <button class="add-to-cart mt-3" data-item-id="<?= $item['item_id'] ?>" data-item-name="<?= htmlspecialchars($item['item_name']) ?>">
                                <i class="fas fa-shopping-bag me-2"></i>ADD TO CART
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
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
                        <a href="#">Women's Collection</a>
                        <a href="#">Men's Collection</a>
                        <a href="#">Accessories</a>
                        <a href="#">New Arrivals</a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <div class="footer-links">
                        <h6>Support</h6>
                        <a href="#">Contact Us</a>
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
        // Category tabs functionality
        document.querySelectorAll('.category-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.category-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                // Add active class to clicked button
                this.classList.add('active');
                
                const category = this.dataset.category;
                const products = document.querySelectorAll('.product-item');
                
                products.forEach(product => {
                    if (category === 'all' || product.dataset.category === category) {
                        product.style.display = 'block';
                    } else {
                        product.style.display = 'none';
                    }
                });
            });
        });
        
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.dataset.itemId;
                const itemName = this.dataset.itemName;
                const originalButton = this;
                const originalText = this.innerHTML;
                
                // Show added feedback
                this.innerHTML = '<i class="fas fa-check me-2"></i>ADDED!';
                this.style.background = 'linear-gradient(45deg, #2dce89, #2dcecc)';
                
                // Send AJAX request to add to cart
                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        item_id: itemId,
                        quantity: 1
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Added to cart:', data);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.background = 'linear-gradient(45deg, var(--gold), var(--gold-light))';
                }, 2000);
                
                console.log(`Added to cart: ${itemName} (ID: ${itemId})`);
            });
        });
        
        // Smooth scroll for navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>