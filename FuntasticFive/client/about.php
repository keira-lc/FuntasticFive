<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Style'n Wear - About Us</title>
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
                    <a href="about.php" class="nav-link active">About</a>
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

    <!-- Mobile Menu (Offcanvas) -->
    <div class="offcanvas offcanvas-end bg-dark text-white" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header border-bottom border-gold">
            <h5 class="offcanvas-title gold-text">Style'n Wear</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div class="d-flex flex-column gap-3">
                <a href="index.php" class="nav-link">Home</a>
                <a href="about.php" class="nav-link active">About</a>
                <a href="products.php" class="nav-link">Products</a>
                <a href="contact.php" class="nav-link">Contact</a>
                <a href="cart.php" class="nav-link">Cart</a>
                <a href="my_orders.php" class="nav-link">My Orders</a>
                <a href="../logout.php" class="nav-link logout-btn mt-4">Logout</a>
            </div>
        </div>
    </div>

    <!-- Hero Section for About -->
    <section class="hero-section d-flex align-items-center" style="min-height: 400px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h1 class="hero-title gold-text">About Style'n Wear</h1>
                    <p class="hero-subtitle mx-auto" style="text-align: center;">
                        Discover the story behind our passion for fashion and our commitment to quality.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Content Section -->
    <section class="products-section">
        <div class="container">
            <h2 class="section-title gold-text mb-5 text-center">Our Story</h2>
            
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- Your original About text -->
                    <div class="about-content p-5 text-center" style="background: rgba(30, 30, 30, 0.7); border-radius: 20px; border: 1px solid rgba(212, 175, 55, 0.2);">
                        <p class="text-center fs-4 mb-4" style="color: var(--gold-light);">
                            Welcome to Style'nWear, your ultimate destination for stylish apparels and chic accessories that elevate every look with effortless elegance!
                        </p>
                        
                        <p class="mb-4">
                            At Style'nWear, we blend timeless elegance with contemporary flair to create apparel and accessories that empower you to express your unique style effortlessly. Founded on a passion for quality craftsmanship and innovative designs, our collections feature versatile pieces—from flowing dresses and tailored blazers to statement jewelry and chic handbags—that transition seamlessly from day to night.
                        </p>
                        
                        <p class="mb-4">
                            We prioritize sustainable sourcing and ethical production to ensure every piece not only looks good but feels good for you and the planet. With a commitment to inclusivity, our sizes and styles celebrate diversity, making fashion accessible and empowering for all.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Values Section (Optional) -->
            <div class="row mt-5 pt-5">
                <div class="col-12">
                    <h3 class="section-title gold-text mb-5 text-center">Our Values</h3>
                    
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="value-card p-4 text-center h-100" style="background: rgba(212, 175, 55, 0.05); border-radius: 15px; border: 1px solid rgba(212, 175, 55, 0.2);">
                                <i class="fas fa-leaf fa-2x gold-text mb-3"></i>
                                <h4 class="gold-text mb-3">Sustainability</h4>
                                <p style="color: #aaa;">Committed to eco-friendly practices and ethical production methods.</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="value-card p-4 text-center h-100" style="background: rgba(212, 175, 55, 0.05); border-radius: 15px; border: 1px solid rgba(212, 175, 55, 0.2);">
                                <i class="fas fa-gem fa-2x gold-text mb-3"></i>
                                <h4 class="gold-text mb-3">Quality</h4>
                                <p style="color: #aaa;">Premium materials and meticulous craftsmanship in every piece.</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="value-card p-4 text-center h-100" style="background: rgba(212, 175, 55, 0.05); border-radius: 15px; border: 1px solid rgba(212, 175, 55, 0.2);">
                                <i class="fas fa-heart fa-2x gold-text mb-3"></i>
                                <h4 class="gold-text mb-3">Inclusivity</h4>
                                <p style="color: #aaa;">Fashion for everyone, celebrating diversity in all forms.</p>
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