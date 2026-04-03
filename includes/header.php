<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Safely require DB if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . "/../config/db.php";
}

// Calculate total items in cart for the badge
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}

// Calculate total items in wishlist for the badge
$wish_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

// Determine User Display Name
$displayName = 'Account';

if (isset($_SESSION['user_id'])) {
    if (!empty($_SESSION['name'])) {
        $displayName = 'Hi, ' . htmlspecialchars(explode(' ', trim($_SESSION['name']))[0]);
    } else {
        try {
            $stmtName = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmtName->execute([$_SESSION['user_id']]);
            $fetchedName = $stmtName->fetchColumn();
            
            if (!empty($fetchedName)) {
                $_SESSION['name'] = $fetchedName;
                $displayName = 'Hi, ' . htmlspecialchars(explode(' ', trim($fetchedName))[0]);
            } else {
                $displayName = 'My Account';
            }
        } catch (PDOException $e) {
            $displayName = 'My Account';
        }
    }
} else {
    $displayName = 'Login';
}
?>

<nav class="navbar navbar-expand-lg sticky-top bg-white border-bottom py-3">
    <div class="container-fluid px-4 px-lg-5 align-items-center">
        
        <a class="navbar-brand fw-bold fs-4 text-uppercase m-0" href="index.php" style="letter-spacing: 2px; color: var(--premium-navy, #0f172a);">
            Shirtifyhub
        </a>

        <form class="d-none d-lg-flex mx-auto position-relative search-container" style="width: 40%;" action="index.php" method="GET">
            <input class="form-control rounded-pill ps-4 py-2 shadow-none custom-search-input" type="search" name="q" placeholder="Search for items..." aria-label="Search" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button class="btn position-absolute end-0 top-50 translate-middle-y me-2 border-0 bg-transparent" type="submit">
                <i class="bi bi-search text-muted"></i>
            </button>
        </form>

        <div class="d-flex align-items-center gap-3 gap-md-4 ms-auto">
            
            <a class="d-lg-none text-decoration-none text-dark nav-icon-link" data-bs-toggle="collapse" href="#mobileSearch" role="button" aria-expanded="false" aria-controls="mobileSearch">
                <i class="bi bi-search fs-4"></i>
            </a>

            <a href="help.php" class="text-decoration-none text-dark d-none d-md-flex align-items-center gap-2 nav-icon-link" title="Help Center">
                <i class="bi bi-question-circle fs-4"></i>
                <span class="small fw-bold text-nowrap">Help</span>
            </a>

            <div class="dropdown">
                <a class="text-decoration-none text-dark d-flex align-items-center gap-2 nav-icon-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person fs-4"></i>
                    <span class="small fw-bold d-none d-md-inline text-nowrap">
                        <?= $displayName ?>
                    </span>
                </a>

                <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-3 py-2" style="border-radius: 16px; min-width: 220px; border: 1px solid var(--border-color, #e2e8f0);">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><h6 class="dropdown-header small text-muted text-uppercase fw-bold mb-1" style="letter-spacing: 0.5px;">My Account</h6></li>
                        <li><a class="dropdown-item py-2" href="profile.php"><i class="bi bi-person-gear me-2 text-muted"></i> Profile Settings</a></li>
                        <li><a class="dropdown-item py-2" href="orders.php"><i class="bi bi-box-seam me-2 text-muted"></i> Order History</a></li>
                        <li><hr class="dropdown-divider mx-3 my-2 opacity-25"></li>
                        
                        <li class="d-md-none"><a class="dropdown-item py-2" href="help.php"><i class="bi bi-question-circle me-2 text-muted"></i> Help & Support</a></li>
                        <li class="d-md-none"><hr class="dropdown-divider mx-3 my-2 opacity-25"></li>

                        <li><a class="dropdown-item py-2 text-danger fw-bold" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item py-2 fw-bold" href="login.php"><i class="bi bi-box-arrow-in-right me-2 text-muted"></i> Login</a></li>
                        <li><a class="dropdown-item py-2" href="register.php"><i class="bi bi-person-plus me-2 text-muted"></i> Create Account</a></li>
                        <li><hr class="dropdown-divider mx-3 my-2 opacity-25"></li>
                        <li class="d-md-none"><a class="dropdown-item py-2" href="help.php"><i class="bi bi-question-circle me-2 text-muted"></i> Help & Support</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <a href="wishlist.php" class="text-decoration-none text-dark position-relative nav-icon-link">
                <i class="bi bi-heart fs-4"></i>
                <?php if ($wish_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem; border: 2px solid white;">
                        <?= $wish_count ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="cart.php" class="text-decoration-none text-dark position-relative nav-icon-link">
                <i class="bi bi-bag fs-4"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark" style="font-size: 0.65rem; border: 2px solid white;">
                        <?= $cart_count ?>
                    </span>
                <?php endif; ?>
            </a>
            
        </div>
    </div>
    
    <div class="collapse w-100 d-lg-none" id="mobileSearch">
        <div class="bg-light p-3 border-top mt-2">
            <form class="position-relative" action="index.php" method="GET">
                <input class="form-control rounded-pill ps-4 py-2 shadow-none border-0" type="search" name="q" placeholder="Search for items..." aria-label="Search" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                <button class="btn position-absolute end-0 top-50 translate-middle-y me-2 border-0 bg-transparent" type="submit">
                    <i class="bi bi-search text-muted"></i>
                </button>
            </form>
        </div>
    </div>
</nav>

<style>
    /* Premium Hover Effects */
    .nav-icon-link {
        transition: all 0.2s ease-in-out;
        color: #334155 !important;
    }
    .nav-icon-link:hover {
        color: var(--premium-navy, #0f172a) !important;
        transform: translateY(-2px);
    }

    /* Search Bar Styling */
    .custom-search-input {
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        transition: all 0.3s ease;
    }
    .custom-search-input:focus {
        background-color: #ffffff !important;
        border-color: var(--premium-navy, #0f172a) !important;
        box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.1) !important;
    }

    /* Dropdown UI */
    .dropdown-toggle::after {
        display: none; /* Hide default arrow */
    }
    .dropdown-menu {
        border: 1px solid #e2e8f0 !important;
    }
    .dropdown-item {
        transition: 0.2s;
        border-radius: 8px;
        margin: 0 8px;
        width: calc(100% - 16px);
        font-size: 0.9rem;
        font-weight: 500;
    }
    .dropdown-item:hover {
        background-color: #f1f5f9;
        color: var(--premium-navy, #0f172a);
    }
    
    /* Mobile Spacing Fixes */
    @media (max-width: 576px) {
        .nav-icon-link i { font-size: 1.3rem !important; }
        .gap-md-4 { gap: 1rem !important; }
    }
</style>