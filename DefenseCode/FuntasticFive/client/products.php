<?php
// Database connection
$host = '127.0.0.1';
$dbname = 'stylenwear_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if filtering by category
    $category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
    
    if ($category_id) {
        $stmt = $pdo->prepare("
            SELECT i.*, c.category_name 
            FROM items i 
            LEFT JOIN category c ON i.category_id = c.category_id 
            WHERE i.category_id = ?
            ORDER BY i.item_name
        ");
        $stmt->execute([$category_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT i.*, c.category_name 
            FROM items i 
            LEFT JOIN category c ON i.category_id = c.category_id 
            ORDER BY i.item_name
        ");
        $stmt->execute();
    }
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process items for discount calculations
    foreach ($items as &$item) {
        if ($item['is_on_sale'] == 'Y' && $item['item_price'] > 0) {
            if (!isset($item['discount_price']) || $item['discount_price'] <= 0) {
                $item['discount_price'] = $item['item_price'];
            }
            $item['discount_amount'] = $item['item_price'] - $item['discount_price'];
            if ($item['item_price'] > 0) {
                $item['discount_percentage'] = round(($item['discount_amount'] / $item['item_price']) * 100);
            } else {
                $item['discount_percentage'] = 0;
            }
        }
    }
    unset($item);
    
    // Get categories for filter
    $cat_stmt = $pdo->prepare("SELECT * FROM category ORDER BY category_name");
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $items = [];
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Style'n Wear - Products</title>
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
                <a href="products.php" class="nav-link active">Products</a>
                <a href="contact.php" class="nav-link">Contact</a>
                <a href="cart.php" class="nav-link">Cart</a>
                <a href="my_orders.php" class="nav-link">My Orders</a>
                <a href="../logout.php" class="nav-link logout-btn mt-4">Logout</a>
            </div>
        </div>
    </div>

    <!-- Hero Section for Products -->
    <section class="hero-section d-flex align-items-center" style="min-height: 300px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h1 class="hero-title gold-text">Our Products</h1>
                    <p class="hero-subtitle mx-auto" style="text-align: center;">
                        Discover our exclusive collection of luxury fashion items
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <!-- Category Filter -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="category-tabs">
                        <a href="products.php" class="category-btn <?php echo !$category_id ? 'active' : ''; ?>">
                            All Products
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="products.php?category=<?php echo $cat['category_id']; ?>" 
                               class="category-btn <?php echo $category_id == $cat['category_id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="row g-4" id="products-container">
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                            <div class="product-card h-100 d-flex flex-column">
                                <?php if ($item['is_on_sale'] == 'Y'): ?>
                                <div class="sale-badge">
                                    <?= ($item['discount_percentage'] ?? 20) ?>% OFF
                                </div>
                                <?php endif; ?>
                                
                                <?php 
                                $image_url = !empty($item['image_url']) ? $item['image_url'] : 'default.jpg';
                                $image_filename = basename($image_url);
                                ?>
                                
                                <!-- Image container -->
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
                                    
                                    <!-- Stock Info -->
                                    <div class="stock-info mb-2">
                                        <?php if ($item['stock'] > 0): ?>
                                            <span class="badge" style="background: rgba(45, 206, 137, 0.2); color: #2dce89;">
                                                <i class="fas fa-check-circle me-1"></i> In Stock (<?= $item['stock'] ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: rgba(255, 0, 0, 0.2); color: #ff6b6b;">
                                                <i class="fas fa-times-circle me-1"></i> Out of Stock
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
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
                                    
                                    <!-- Action Buttons -->
                                    <div class="mt-3 d-grid gap-2">
                                        <a href="product-detail.php?id=<?= $item['item_id'] ?>" 
                                           class="btn btn-outline-gold" style="border-color: var(--gold); color: var(--gold);">
                                            <i class="fas fa-eye me-2"></i>View Details
                                        </a>
                                        <?php if ($item['stock'] > 0): ?>
                                            <button class="add-to-cart" data-item-id="<?= $item['item_id'] ?>" data-item-name="<?= htmlspecialchars($item['item_name']) ?>">
                                                <i class="fas fa-shopping-bag me-2"></i>ADD TO CART
                                            </button>
                                        <?php else: ?>
                                            <button class="add-to-cart" disabled style="background: #666; cursor: not-allowed;">
                                                <i class="fas fa-times me-2"></i>OUT OF STOCK
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-box-open fa-3x gold-text mb-3"></i>
                            <h3 class="gold-text mb-3">No Products Found</h3>
                            <a href="products.php" class="btn" style="background: var(--gold); color: var(--charcoal);">
                                <i class="fas fa-arrow-left me-2"></i>View All Products
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
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
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                if (this.disabled) return;
                
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