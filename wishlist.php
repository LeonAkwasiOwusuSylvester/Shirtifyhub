<?php
session_start();
require_once __DIR__ . "/config/db.php";

// 1. Handle Wishlist Removal (Toggling the heart)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'wishlist') {
    $product_id = (int)$_POST['product_id'];
    if (isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = array_diff($_SESSION['wishlist'], [$product_id]);
    }
    header("Location: wishlist.php");
    exit;
}

// 2. Fetch Wishlist Items
$products = [];
if (!empty($_SESSION['wishlist'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['wishlist']), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price, discount_price, is_preorder, image FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_values($_SESSION['wishlist']));
    $products = $stmt->fetchAll();
}

if (!function_exists('formatPrice')) {
    function formatPrice($amount) { return '₵' . number_format((float)$amount, 2); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist | Shirtifyhub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public/style.css">
    <style>
        body { background-color: var(--bg-main); }
        
        /* Matching Index Page Exactly */
        .product-card { background: white; border-radius: 15px; padding: 15px; border: 1px solid var(--border-color); transition: 0.3s; position: relative; height: 100%; display: flex; flex-direction: column; }
        .product-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-sm); border-color: #cbd5e1; }
        
        .badge-preorder-card { position: absolute; top: 15px; left: 15px; background: #6366f1; color: white; font-size: 0.65rem; font-weight: 800; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; z-index: 2; }
        .badge-sale-card { position: absolute; top: 15px; left: 15px; background: #dc3545; color: white; font-size: 0.65rem; font-weight: 800; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; z-index: 2; }
        
        .wishlist-form { position: absolute; top: 15px; right: 15px; z-index: 2; }
        .wishlist-btn { background: white; border: none; width: 34px; height: 34px; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; transition: 0.2s; color: #ef4444; }

        .card-img { display: block; height: 180px; background: #f8fafc; border-radius: 10px; overflow: hidden; margin-bottom: 12px; display: flex; align-items: center; justify-content: center; }
        .card-img img { width: 100%; height: 100%; object-fit: contain; mix-blend-mode: multiply; }
        
        .product-name { font-size: 0.95rem; font-weight: 700; color: var(--text-dark); text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: auto; }
        
        .price-row { display: flex; align-items: baseline; flex-wrap: wrap; gap: 5px; margin: 10px 0; }
        .price-main { font-weight: 800; font-size: 1.1rem; color: var(--text-dark); }
        .price-discounted { color: #dc3545; font-weight: 800; font-size: 1.1rem; }
        .price-crossed { text-decoration: line-through; color: #94a3b8; font-size: 0.85rem; font-weight: 600; }
        
        .btn-add-cart-index { background-color: white; color: var(--brand-primary); border: 2px solid var(--brand-primary); border-radius: 10px; padding: 8px; width: 100%; font-weight: 700; font-size: 0.85rem; transition: 0.2s; margin-top: auto; }
        .btn-add-cart-index:hover { background-color: var(--brand-primary); color: white; }
    </style>
</head>
<body>

<?php require_once __DIR__ . "/includes/header.php"; ?>

<main class="container py-5">
    <div class="mb-4">
        <h2 class="fw-bold m-0">My Wishlist</h2>
        <p class="text-muted">Items you've marked as favorites.</p>
    </div>

    <?php if (empty($products)): ?>
        <div class="text-center py-5 bg-white rounded-4 border shadow-sm">
            <i class="bi bi-heart display-1 text-muted opacity-25 d-block mb-3"></i>
            <h4 class="fw-bold">Your wishlist is empty</h4>
            <a href="index.php" class="btn btn-dark rounded-pill px-4 mt-2">Go Shopping</a>
        </div>
    <?php else: ?>
        <div class="row g-4 row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5">
            <?php foreach ($products as $p): 
                $displayImg = !empty($p['image']) ? 'uploads/' . $p['image'] : 'assets/images/no-image.png';
            ?>
            <div class="col">
                <div class="product-card">
                    <?php if($p['is_preorder']): ?>
                        <span class="badge-preorder-card">Pre-order</span>
                    <?php elseif($p['discount_price'] > 0): ?>
                        <span class="badge-sale-card">Sale</span>
                    <?php endif; ?>
                    
                    <form action="wishlist.php" method="POST" class="wishlist-form">
                        <input type="hidden" name="action" value="wishlist">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="wishlist-btn" title="Remove from wishlist">
                            <i class="bi bi-heart-fill"></i>
                        </button>
                    </form>
                    
                    <a href="product.php?id=<?= $p['id'] ?>" class="card-img">
                        <img src="<?= htmlspecialchars($displayImg) ?>" alt="">
                    </a>

                    <div class="card-body p-0 d-flex flex-column h-100">
                        <a href="product.php?id=<?= $p['id'] ?>" class="product-name">
                            <?= htmlspecialchars($p['name']) ?>
                        </a>

                        <div class="price-row">
                            <?php if($p['discount_price'] > 0): ?>
                                <span class="price-discounted"><?= formatPrice($p['discount_price']) ?></span>
                                <span class="price-crossed"><?= formatPrice($p['price']) ?></span>
                            <?php else: ?>
                                <span class="price-main"><?= formatPrice($p['price']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <form action="index.php" method="POST" class="mt-auto pt-2">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-add-cart-index">
                                <i class="bi bi-bag-plus me-1"></i> Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

</body>
</html>