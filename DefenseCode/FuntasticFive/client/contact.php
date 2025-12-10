<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Style'n Wear</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .contact-form-container {
            background: rgba(30, 30, 30, 0.7);
            border-radius: 20px;
            border: 1px solid rgba(212, 175, 55, 0.2);
            padding: 40px;
        }
        
        .contact-info-card {
            background: rgba(212, 175, 55, 0.05);
            border-radius: 15px;
            border: 1px solid rgba(212, 175, 55, 0.2);
            padding: 30px;
            height: 100%;
            transition: var(--transition);
        }
        
        .contact-info-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.1);
        }
        
        .social-icon-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(212, 175, 55, 0.3);
            transition: var(--transition);
        }
        
        .social-icon-img:hover {
            background: var(--gold);
            border-color: var(--gold);
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
        }
        
        .social-icon-img img {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: var(--off-white);
            padding: 12px 15px;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--gold);
            box-shadow: 0 0 0 0.25rem rgba(212, 175, 55, 0.25);
            color: var(--off-white);
        }
        
        .form-label {
            color: var(--gold-light);
            font-weight: 500;
        }
        
        .btn-submit {
            background: linear-gradient(45deg, var(--gold), var(--gold-light));
            color: var(--charcoal);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-family: "Cinzel", serif;
            letter-spacing: 1px;
            transition: var(--transition);
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.4);
            color: white;
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
                    <a href="contact.php" class="nav-link active">Contact</a>
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
                <a href="products.php" class="nav-link">Products</a>
                <a href="contact.php" class="nav-link active">Contact</a>
                <a href="cart.php" class="nav-link">Cart</a>
                <a href="my_orders.php" class="nav-link">My Orders</a>
                <a href="../logout.php" class="nav-link logout-btn mt-4">Logout</a>
            </div>
        </div>
    </div>

    <!-- Hero Section for Contact -->
    <section class="hero-section d-flex align-items-center" style="min-height: 300px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h1 class="hero-title gold-text">Contact Us</h1>
                    <p class="hero-subtitle mx-auto" style="text-align: center;">
                        We'd love to hear from you! Get in touch with our team.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Content Section -->
    <section class="products-section">
        <div class="container">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-10">
                    <p class="text-center mb-5" style="color: var(--gold-light); font-size: 1.1rem;">
                        Have questions, feedback, or need assistance? We're here to help! Reach out to us through any of the channels below.
                    </p>
                </div>
            </div>
            
            <div class="row mb-5">
                <!-- Contact Information Cards -->
                <div class="col-md-4 mb-4">
                    <div class="contact-info-card text-center">
                        <div class="mb-3">
                            <i class="fas fa-map-marker-alt fa-2x gold-text"></i>
                        </div>
                        <h4 class="gold-text mb-3">Our Location</h4>
                        <p style="color: #aaa;">
                            123 Fashion Street<br>
                            Style District<br>
                            Manila, Philippines 1000
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="contact-info-card text-center">
                        <div class="mb-3">
                            <i class="fas fa-phone fa-2x gold-text"></i>
                        </div>
                        <h4 class="gold-text mb-3">Call Us</h4>
                        <p style="color: #aaa;">
                            +63 912 345 6789<br>
                            +63 2 8123 4567<br>
                            Mon-Fri: 9AM-6PM
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="contact-info-card text-center">
                        <div class="mb-3">
                            <i class="fas fa-envelope fa-2x gold-text"></i>
                        </div>
                        <h4 class="gold-text mb-3">Email Us</h4>
                        <p style="color: #aaa;">
                            info@stylenwear.com<br>
                            support@stylenwear.com<br>
                            Response within 24 hours
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="contact-form-container">
                        <h3 class="text-center gold-text mb-4">Send Us a Message</h3>
                        
                        <form action="#" method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required 
                                           placeholder="Enter your full name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required 
                                           placeholder="Enter your email address">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required 
                                       placeholder="What is this regarding?">
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required 
                                          placeholder="Write your message here..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn-submit mt-3">
                                <i class="fas fa-paper-plane me-2"></i>SEND MESSAGE
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Social Media Links -->
            <div class="row mt-5 pt-5">
                <div class="col-12">
                    <h3 class="section-title gold-text mb-4 text-center">Follow Us</h3>
                    <p class="text-center mb-5" style="color: #aaa;">
                        Stay updated with our latest collections and fashion tips
                    </p>
                    
                    <div class="d-flex justify-content-center flex-wrap gap-4">
                        <a href="https://www.facebook.com" target="_blank" class="social-icon-img">
                            <img src="../image/fb.png" alt="Facebook">
                        </a>
                        <a href="https://www.instagram.com" target="_blank" class="social-icon-img">
                            <img src="../image/insta.png" alt="Instagram">
                        </a>
                        <a href="https://www.tiktok.com" target="_blank" class="social-icon-img">
                            <img src="../image/tiktok.jpg" alt="TikTok">
                        </a>
                        <a href="https://www.x.com" target="_blank" class="social-icon-img">
                            <img src="../image/X.jpg" alt="X">
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Section (Optional) -->
            <div class="row mt-5 pt-5">
                <div class="col-12">
                    <h3 class="section-title gold-text mb-5 text-center">Frequently Asked Questions</h3>
                    
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item" style="background: rgba(30, 30, 30, 0.5); border-color: rgba(212, 175, 55, 0.3);">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" 
                                        style="background: rgba(212, 175, 55, 0.1); color: var(--gold-light);">
                                    What is your return policy?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body" style="color: #aaa;">
                                    We offer a 30-day return policy for unworn items with original tags. Items must be in original condition. Contact us for return authorization.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item" style="background: rgba(30, 30, 30, 0.5); border-color: rgba(212, 175, 55, 0.3); margin-top: 10px;">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" 
                                        style="background: rgba(212, 175, 55, 0.1); color: var(--gold-light);">
                                    How long does shipping take?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body" style="color: #aaa;">
                                    Standard shipping takes 3-5 business days within Metro Manila and 5-10 business days for provincial areas. Express shipping is available for an additional fee.
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
        
        // Form submission (example - would need backend implementation)
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>SENDING...';
            submitBtn.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                alert('Thank you! Your message has been sent. We\'ll get back to you soon.');
                this.reset();
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 1500);
        });
    </script>
</body>
</html>