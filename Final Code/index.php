<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Style'n Wear - Luxury Fashion & Jewelry</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="client/style.css">
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
                    <a href="#" class="nav-link active">Home</a>
                    <a href="#about" class="nav-link">About</a>
                    <a href="#featured" class="nav-link">Collections</a>
                    <a href="#contact" class="nav-link">Contact</a>
                    <a href="login.php" class="nav-link logout-btn">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Shop
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
                    <h1 class="hero-title gold-text">Discover Timeless Elegance</h1>
                    <p class="hero-subtitle">
                        Clothes & Jewelry for Every Occasion. Experience luxury fashion with our exclusive collections.
                    </p>
                    <a href="login.php">
                        <button class="add-to-cart" style="max-width: 250px;">
                            <i class="fas fa-lock me-2"></i>LOGIN TO SHOP
                        </button>
                    </a>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image">
                        <img src="image/display.avif" alt="Elegant silk dress with gold jewelry accessories" 
                             class="img-fluid rounded shadow-lg" style="border: 3px solid var(--gold);">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section id="featured" class="products-section">
        <div class="container">
            <h2 class="section-title gold-text">Featured Collections</h2>
            <p class="text-center mb-5" style="color: var(--gold-light); font-size: 1.2rem;">
                Preview our exclusive items. Login to view full collection and shop.
            </p>
            
            <div class="row g-4">
                <!-- Product 1 -->
                <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                    <div class="product-card h-100 d-flex flex-column">
                        <div class="product-image-container" style="height: 250px; overflow: hidden; position: relative;">
                            <img src="image/Tube Top with Drawstrings.jpg" 
                                 alt="Tube Top with sides Drawstrings"
                                 class="product-image"
                                 style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                        </div>
                        <div class="product-content d-flex flex-column flex-grow-1 p-3">
                            <h4 class="product-title">Tube Top with Drawstrings</h4>
                            <span class="product-category">Women's Tops</span>
                            <div class="price-container mt-auto pt-3">
                                <span class="current-price gold-text">₱275.00</span>
                                <div class="discount mt-2">ON SALE</div>
                            </div>
                            <a href="login.php">
                                <button class="add-to-cart mt-3 w-100">
                                    <i class="fas fa-lock me-2"></i>LOGIN TO PURCHASE
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Product 2 -->
                <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                    <div class="product-card h-100 d-flex flex-column">
                        <div class="product-image-container" style="height: 250px; overflow: hidden; position: relative;">
                            <img src="image/Hat Gatsby.jpg" 
                                 alt="Hat Flat Gatsby Vintage"
                                 class="product-image"
                                 style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                        </div>
                        <div class="product-content d-flex flex-column flex-grow-1 p-3">
                            <h4 class="product-title">Hat Flat Gatsby Vintage</h4>
                            <span class="product-category">Accessories</span>
                            <div class="price-container mt-auto pt-3">
                                <span class="current-price gold-text">₱260.00</span>
                            </div>
                            <a href="login.php">
                                <button class="add-to-cart mt-3 w-100">
                                    <i class="fas fa-lock me-2"></i>LOGIN TO PURCHASE
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Product 3 -->
                <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                    <div class="product-card h-100 d-flex flex-column">
                        <div class="product-image-container" style="height: 250px; overflow: hidden; position: relative;">
                            <img src="image/Derby Shoes.jpg" 
                                 alt="Patent Leather Lace-Up derby Shoes"
                                 class="product-image"
                                 style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                        </div>
                        <div class="product-content d-flex flex-column flex-grow-1 p-3">
                            <h4 class="product-title">Leather Derby Shoes</h4>
                            <span class="product-category">Footwear</span>
                            <div class="price-container mt-auto pt-3">
                                <span class="current-price gold-text">₱500.00</span>
                            </div>
                            <a href="login.php">
                                <button class="add-to-cart mt-3 w-100">
                                    <i class="fas fa-lock me-2"></i>LOGIN TO PURCHASE
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Product 4 -->
                <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                    <div class="product-card h-100 d-flex flex-column">
                        <div class="product-image-container" style="height: 250px; overflow: hidden; position: relative;">
                            <img src="image/Diamond Earrings.jpg" 
                                 alt="Diamond Earrings"
                                 class="product-image"
                                 style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                        </div>
                        <div class="product-content d-flex flex-column flex-grow-1 p-3">
                            <h4 class="product-title">Diamond Earrings</h4>
                            <span class="product-category">Jewelry</span>
                            <div class="price-container mt-auto pt-3">
                                <span class="current-price gold-text">₱2,959.00</span>
                            </div>
                            <a href="login.php">
                                <button class="add-to-cart mt-3 w-100">
                                    <i class="fas fa-lock me-2"></i>LOGIN TO PURCHASE
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="login.php" class="text-decoration-none">
                    <button class="add-to-cart" style="padding: 15px 40px; font-size: 1.2rem;">
                        <i class="fas fa-arrow-right me-2"></i>VIEW FULL CATALOG
                    </button>
                </a>
            </div>
        </div>
    </section>

     <!-- About Section -->
    <section id="about" class="py-5" style="background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="gold-text mb-4" style="font-family: 'Cinzel', serif; font-size: 2.5rem;">About Style'n Wear</h2>
                    <p style="color: var(--off-white); line-height: 1.8;">
                        Welcome to Style'n Wear, where luxury meets everyday fashion. 
                        We curate exclusive collections of apparel and accessories that 
                        redefine elegance and sophistication. From statement jewelry 
                        to premium clothing, each piece is selected to help you express 
                        your unique style.
                    </p>
                    <p style="color: var(--off-white); line-height: 1.8;">
                        Our mission is to provide fashion-forward individuals with 
                        high-quality, stylish pieces that make a statement. 
                        Join our community of fashion enthusiasts and discover 
                        your signature look.
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <img src="image/stylenwear.png" 
                             alt="Luxury fashion display"
                             class="img-fluid rounded shadow-lg"
                             style="border: 2px solid var(--gold); max-height: 400px; object-fit: contain;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5">
        <div class="container">
            <h2 class="section-title gold-text">Contact Us</h2>
            <div class="row">
                <div class="col-lg-6">
                    <div class="mb-4">
                        <h5 class="gold-text mb-3">Get in Touch</h5>
                        <p style="color: var(--off-white);">
                            Have questions about our collections or need assistance? 
                            Our customer service team is here to help you.
                        </p>
                        <div class="mt-4">
                            <p style="color: var(--gold-light);">
                                <i class="fas fa-envelope me-2"></i> info@stylenwear.com
                            </p>
                            <p style="color: var(--gold-light);">
                                <i class="fas fa-phone me-2"></i> +63 123 456 7890
                            </p>
                            <p style="color: var(--gold-light);">
                                <i class="fas fa-map-marker-alt me-2"></i> Manila, Philippines
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h5 class="gold-text mb-3">Ready to Shop?</h5>
                    <p style="color: var(--off-white); margin-bottom: 30px;">
                        Create an account or login to access our full catalog and 
                        start shopping our exclusive collections.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="login.php" class="text-decoration-none flex-grow-1">
                            <button class="add-to-cart w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>LOGIN
                            </button>
                        </a>
                        <a href="register.php" class="text-decoration-none flex-grow-1">
                            <button class="add-to-cart w-100" style="background: linear-gradient(45deg, #2dce89, #2dcecc);">
                                <i class="fas fa-user-plus me-2"></i>REGISTER
                            </button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Admin Access -->
    <div class="text-center py-4" style="background: rgba(212, 175, 55, 0.1); border-top: 1px solid var(--gold);">
        <div class="container">
            <h6 class="gold-text mb-2">Admin Access</h6>
            <p style="color: var(--gold-light); font-size: 0.9rem;">
                Are you an administrator? Access the admin panel here.
            </p>
            <a href="Admin/index.php" class="text-decoration-none">
                <button class="add-to-cart" style="padding: 8px 25px; font-size: 0.9rem;">
                    <i class="fas fa-cog me-2"></i>ADMIN LOGIN
                </button>
            </a>
        </div>
    </div>

    <!-- Luxury Footer -->
    <footer class="luxury-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h3 class="footer-brand gold-text">Style'n Wear</h3>
                    <p style="color: #aaa;">
                        Redefining luxury fashion with our exclusive collections. 
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
                        <h6>Quick Links</h6>
                        <a href="#">Home</a>
                        <a href="#about">About</a>
                        <a href="#featured">Collections</a>
                        <a href="#contact">Contact</a>
                    </div>
                </div>
                
                <div class="col-lg-6 col-md-8 mb-4">
                    <div class="footer-links">
                        <h6>Join Our Community</h6>
                        <p style="color: #aaa; font-size: 0.9rem;">
                            Subscribe for exclusive offers, early access to new collections, 
                            and style inspiration delivered to your inbox.
                        </p>
                        <div class="input-group" style="max-width: 400px;">
                            <input type="email" class="form-control bg-dark text-white" 
                                   placeholder="Your email address" 
                                   style="border-color: var(--gold);">
                            <button class="btn gold-text" type="button" 
                                    style="background: var(--gold); color: var(--charcoal);">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5 pt-3 border-top" style="border-color: rgba(212, 175, 55, 0.3);">
                <p style="color: #777; font-size: 0.9rem;">
                    © 2025 Style'n Wear. All rights reserved. | Luxury Fashion Redefined
                </p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const target = document.querySelector(targetId);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Mobile menu functionality
        const mobileMenu = `
        <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileMenu" style="background: var(--charcoal); color: white;">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title gold-text">Style'n Wear</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
                <div class="d-flex flex-column gap-3">
                    <a href="#" class="nav-link">Home</a>
                    <a href="#about" class="nav-link">About</a>
                    <a href="#featured" class="nav-link">Collections</a>
                    <a href="#contact" class="nav-link">Contact</a>
                    <a href="login.php" class="nav-link logout-btn text-center">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Shop
                    </a>
                    <a href="Admin/index.php" class="nav-link text-center" style="color: var(--gold);">
                        <i class="fas fa-cog me-2"></i>Admin Login
                    </a>
                </div>
            </div>
        </div>`;
        
        // Add mobile menu to body
        document.body.insertAdjacentHTML('beforeend', mobileMenu);
    </script>
</body>
</html>