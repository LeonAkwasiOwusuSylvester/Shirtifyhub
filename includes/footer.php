<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$activeCurrency = $_SESSION['currency'] ?? 'GHS';
$redirectUrl = urlencode($_SERVER['REQUEST_URI']);
?>
<footer class="premium-footer mt-auto pt-5">
    <div class="container px-4 px-lg-5">
        <div class="row g-5 mb-5">
            
            <div class="col-lg-4 col-md-12 pe-lg-5">
                <a class="navbar-brand fw-bold fs-3 text-uppercase mb-4 d-block text-white" href="index.php" style="letter-spacing: 3px;">
                    Shirtifyhub
                </a>
                <p class="footer-desc mb-4">
                    The ultimate destination for the modern gentleman. We bridge the gap between global luxury and local style, delivering exclusive sneakers, timepieces, and apparel curated from the fashion capitals of the world directly to your door in Ghana.
                </p>
                
                <div class="d-flex gap-3 social-links">
                    <a href="#" class="social-icon" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-icon" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="social-icon" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-icon" aria-label="Tiktok"><i class="bi bi-tiktok"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <h6 class="footer-heading">The Collection</h6>
                <ul class="list-unstyled footer-list">
                    <li><a href="index.php?category=sneakers">Limited Sneakers</a></li>
                    <li><a href="index.php?category=watches">Luxury Watches</a></li>
                    <li><a href="index.php?category=official-wear">Executive Wear</a></li>
                    <li><a href="index.php?category=accessories">Daily Essentials</a></li>
                    <li><a href="index.php">Full Catalog</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-6">
                <h6 class="footer-heading">Assistance</h6>
                <ul class="list-unstyled footer-list">
                    <li><a href="help.php" style="color: #fff; font-weight: 700;"><i class="bi bi-question-circle me-1"></i> Help Center & Contact</a></li>
                    <li><a href="orders.php">Track Order</a></li>
                    <li><a href="profile.php">My Account</a></li>
                    <li><a href="cart.php">Shopping Bag</a></li>
                    <li><a href="shipping-policy.php">Shipping Policy</a></li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-12">
                <h6 class="footer-heading">Elite Insights</h6>
                <p class="small text-white-50 mb-3">Subscribe to receive first-access to limited drops and exclusive seasonal offers.</p>
                <form class="newsletter-form mb-4">
                    <div class="input-group border border-secondary border-opacity-50 rounded-3 overflow-hidden">
                        <input type="email" class="form-control bg-transparent border-0 text-white ps-3 py-2" placeholder="Your email address" required>
                        <button class="btn btn-white fw-bold px-4" type="submit">Join</button>
                    </div>
                </form>

                <h6 class="footer-heading-sm">Verified Credentials</h6>
                <div class="d-flex flex-wrap gap-3 opacity-75">
                    <div class="trust-badge"><i class="bi bi-patch-check-fill me-1"></i> Authentic Only</div>
                    <div class="trust-badge"><i class="bi bi-truck me-1"></i> Global Sourcing</div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom py-4 border-top border-secondary border-opacity-25 mt-5">
            <div class="row align-items-center">
                <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                    <span class="copyright-text">&copy; <?= date('Y') ?> <span class="fw-bold text-white">Shirtifyhub Limited</span>. All Rights Reserved.</span>
                </div>

                <div class="col-md-4 text-center text-md-end">
                    <div class="payment-partners d-flex justify-content-center justify-content-md-end gap-3 align-items-center">
                        <span class="small text-white-50 me-2">Secure Payments:</span>
                        <i class="bi bi-phone-vibrate-fill fs-5" title="Mobile Money"></i>
                        <i class="bi bi-bank fs-5" title="Bank Transfer"></i>
                        <i class="bi bi-shield-lock-fill fs-5 text-success" title="SSL Secured"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Premium Color Palette */
    :root {
        --footer-bg: #0f172a; /* Deep Slate/Midnight */
        --footer-text: #94a3b8;
        --footer-accent: #6366f1; /* Premium Indigo */
    }

    .premium-footer {
        background-color: var(--footer-bg);
        color: var(--footer-text);
        font-family: 'Inter', sans-serif;
    }

    .footer-desc {
        font-size: 0.9rem;
        line-height: 1.8;
        color: rgba(255, 255, 255, 0.6);
        font-weight: 400;
    }

    .footer-heading {
        color: #ffffff;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 1.5px;
        margin-bottom: 2rem;
    }

    .footer-heading-sm {
        color: #ffffff;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.65rem;
        letter-spacing: 1px;
        margin-bottom: 1rem;
    }

    /* List & Link Styling */
    .footer-list li {
        margin-bottom: 1rem;
    }

    .footer-list a {
        color: var(--footer-text);
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .footer-list a:hover {
        color: #ffffff;
        padding-left: 5px;
    }

    /* Newsletter Form Glassmorphism */
    .newsletter-form .form-control::placeholder {
        color: #64748b;
        font-size: 0.85rem;
    }

    .newsletter-form .form-control:focus {
        box-shadow: none;
        background-color: rgba(255, 255, 255, 0.03);
    }

    .btn-white {
        background-color: #ffffff;
        color: var(--footer-bg);
        border: none;
        transition: 0.3s ease;
    }

    .btn-white:hover {
        background-color: #f1f5f9;
        color: #000;
    }

    /* Social Icons Styling */
    .social-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .social-icon:hover {
        background: var(--footer-accent);
        color: #fff;
        transform: translateY(-4px);
        border-color: var(--footer-accent);
    }

    /* Trust Badges */
    .trust-badge {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #ffffff;
        background: rgba(255, 255, 255, 0.05);
        padding: 6px 12px;
        border-radius: 6px;
        letter-spacing: 0.5px;
    }

    .copyright-text {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .payment-partners i {
        color: rgba(255, 255, 255, 0.5);
        transition: color 0.3s;
    }
    
    .payment-partners i:hover {
        color: #fff;
    }

    /* Currency Dropdown Enhancements */
    .dropdown-menu-dark .dropdown-item {
        transition: 0.2s ease;
    }
    .dropdown-menu-dark .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
        padding-left: 22px;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>