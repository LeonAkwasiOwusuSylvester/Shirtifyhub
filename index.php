<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Handle Add to Cart Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = (int)$_POST['product_id'];
    if ($product_id > 0) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$product_id] = ['product_id' => $product_id, 'quantity' => 1];
        }
    }
    header("Location: index.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// 2. Handle Wishlist Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'wishlist') {
    $product_id = (int)$_POST['product_id'];
    if ($product_id > 0) {
        if (!isset($_SESSION['wishlist'])) $_SESSION['wishlist'] = [];
        if (in_array($product_id, $_SESSION['wishlist'])) {
            $_SESSION['wishlist'] = array_diff($_SESSION['wishlist'], [$product_id]);
        } else {
            $_SESSION['wishlist'][] = $product_id;
        }
    }
    header("Location: index.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

require_once __DIR__ . "/config/db.php";

// Price Formatter
if (!function_exists('formatPrice')) {
    function formatPrice($amount) { return '₵' . number_format((float)$amount, 2); }
}

// 3. Data Fetching
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$items_per_page = 20;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;
$where  = "1=1"; 
$params = [];

if (!empty($_GET["category"])) { $where .= " AND category_id = ?"; $params[] = (int)$_GET["category"]; }
if (!empty($_GET["q"])) { $where .= " AND name LIKE ?"; $params[] = "%" . $_GET["q"] . "%"; }
if (!empty($_GET["min_price"])) { $where .= " AND (CASE WHEN discount_price > 0 THEN discount_price ELSE price END) >= ?"; $params[] = (float)$_GET["min_price"]; }
if (!empty($_GET["max_price"])) { $where .= " AND (CASE WHEN discount_price > 0 THEN discount_price ELSE price END) <= ?"; $params[] = (float)$_GET["max_price"]; }

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE $where");
$countStmt->execute($params);
$total_products = (int)$countStmt->fetchColumn();
$total_pages = max(1, ceil($total_products / $items_per_page));

$sql = "SELECT id, name, price, discount_price, is_preorder, image FROM products WHERE $where ORDER BY id DESC LIMIT $items_per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shirtifyhub | Premium International Imports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --premium-navy: #0f172a; 
            --premium-accent: #6366f1;
            --bg-body: #f8fafc;
            --border-soft: #e2e8f0;
        }
        
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }

        /* HERO AREA */
        .hero-section { 
            background: var(--premium-navy);
            border-radius: 40px;
            margin: 20px 4%;
            padding: 80px 40px;
            color: white;
            text-align: center;
            background-image: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent);
        }

        /* CATEGORY SCROLL */
        .category-nav { display: flex; gap: 10px; overflow-x: auto; padding: 20px 4%; scrollbar-width: none; }
        .category-nav::-webkit-scrollbar { display: none; }
        .cat-pill { 
            background: white; border: 1px solid var(--border-soft); padding: 10px 24px; border-radius: 50px; 
            text-decoration: none; color: #64748b; font-weight: 700; font-size: 0.85rem; white-space: nowrap; transition: 0.3s; 
        }
        .cat-pill.active { background: var(--premium-navy); color: white; border-color: var(--premium-navy); }

        /* PREMIUM PRODUCT CARD */
        .product-card { 
            background: #ffffff;
            border-radius: 28px;
            border: 1px solid var(--border-soft);
            padding: 10px;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
        }
        .product-card:hover { 
            transform: translateY(-8px);
            box-shadow: 0 30px 60px -12px rgba(15, 23, 42, 0.12);
            border-color: #cbd5e1;
        }

        /* IMPROVED TAGS / BADGES */
        .tag-container {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            z-index: 10;
        }
        .badge-premium {
            font-size: 0.6rem;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            width: fit-content;
        }
        .badge-preorder { background: var(--premium-navy); color: white; }
        .badge-sale { background: #ef4444; color: white; }

        .img-holder { 
            background: #f1f5f9;
            border-radius: 22px;
            height: 260px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 15px;
        }
        .img-holder img { max-width: 80%; transition: 0.6s ease; mix-blend-mode: multiply; }
        .product-card:hover .img-holder img { transform: scale(1.1) rotate(-3deg); }

        .wishlist-btn { 
            position: absolute; top: 20px; right: 20px; background: white;
            border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; 
            align-items: center; justify-content: center; color: #94a3b8; transition: 0.3s; z-index: 10;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .wishlist-btn.active { color: #ef4444; }
        
        .product-content { padding: 0 10px 10px; flex-grow: 1; display: flex; flex-direction: column; }
        .product-name { font-size: 0.95rem; font-weight: 700; color: var(--premium-navy); text-decoration: none; margin-bottom: 6px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        
        /* THE FIX: Fluid Typography & Wrap for Mobile */
        .price-wrapper { 
            display: flex; 
            align-items: baseline; 
            gap: 6px; 
            margin-bottom: 15px; 
            flex-wrap: wrap; 
        }
        .price-current { 
            font-weight: 800; 
            font-size: clamp(0.95rem, 3.5vw, 1.15rem); 
            color: var(--premium-navy); 
            line-height: 1;
        }
        .price-was { 
            text-decoration: line-through; 
            color: #94a3b8; 
            font-size: clamp(0.75rem, 2.5vw, 0.85rem); 
            font-weight: 600; 
            line-height: 1;
        }

        .btn-bag { 
            background: var(--bg-body); color: var(--premium-navy); border: 1px solid var(--border-soft); 
            border-radius: 14px; padding: 12px; width: 100%; font-weight: 800; font-size: 0.8rem; 
            transition: 0.2s; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .btn-bag:hover { background: var(--premium-navy); color: white; border-color: var(--premium-navy); }
    </style>
</head>
<body>

<?php require_once __DIR__ . "/includes/header.php"; ?>

<main>
    <div class="hero-section">
        <h1 class="display-4 fw-800 mb-3" style="letter-spacing: -2px;">Global Style, Local Soul.</h1>
        <p class="opacity-75 mx-auto" style="max-width: 500px;">Exclusive footwear and luxury apparel imported from the fashion capitals of the world.</p>
    </div>

    <div class="category-nav">
        <a href="index.php" class="cat-pill <?= empty($_GET['category']) ? 'active' : '' ?>">New Arrivals</a>
        <?php foreach($categories as $c): ?>
            <a href="index.php?category=<?= $c['id'] ?>" class="cat-pill <?= (($_GET['category'] ?? 0) == $c['id']) ? 'active' : '' ?>">
                <?= htmlspecialchars($c['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="container-fluid px-4 px-lg-5 pb-5">
        <div class="row g-4">
            <?php foreach($products as $p): 
                $is_wishlisted = isset($_SESSION['wishlist']) && in_array($p['id'], $_SESSION['wishlist']);
                $has_discount = $p['discount_price'] > 0;
            ?>
            <div class="col-6 col-md-4 col-xl-3">
                <div class="product-card">
                    <div class="tag-container">
                        <?php if($p['is_preorder']): ?>
                            <span class="badge-premium badge-preorder">Pre-Order</span>
                        <?php endif; ?>
                        <?php if($has_discount): 
                            $off = round((($p['price'] - $p['discount_price']) / $p['price']) * 100); ?>
                            <span class="badge-premium badge-sale"><?= $off ?>% Off</span>
                        <?php endif; ?>
                    </div>

                    <form action="index.php<?= !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ?>" method="POST">
                        <input type="hidden" name="action" value="wishlist">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="wishlist-btn <?= $is_wishlisted ? 'active' : '' ?>">
                            <i class="bi <?= $is_wishlisted ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                        </button>
                    </form>

                    <a href="product.php?id=<?= $p['id'] ?>" class="img-holder">
                        <img src="uploads/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                    </a>

                    <div class="product-content">
                        <a href="product.php?id=<?= $p['id'] ?>" class="product-name">
                            <?= htmlspecialchars($p['name']) ?>
                        </a>
                        
                        <div class="price-wrapper">
                            <?php if($has_discount): ?>
                                <span class="price-current"><?= formatPrice($p['discount_price']) ?></span>
                                <span class="price-was"><?= formatPrice($p['price']) ?></span>
                            <?php else: ?>
                                <span class="price-current"><?= formatPrice($p['price']) ?></span>
                            <?php endif; ?>
                        </div>

                        <form action="index.php" method="POST" class="mt-auto">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn-bag">
                                <i class="bi <?= $p['is_preorder'] ? 'bi-lightning-charge-fill' : 'bi-bag-plus' ?> me-2"></i>
                                <?= $p['is_preorder'] ? 'Reserve Now' : 'Add to Bag' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if($total_pages > 1): ?>
        <div class="mt-5 d-flex justify-content-center">
            <nav>
                <ul class="pagination pagination-sm gap-2">
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <li class="page-item">
                            <a class="page-link border rounded-circle fw-bold <?= ($i == $page) ? 'bg-dark text-white' : 'text-dark' ?>" 
                               href="index.php?page=<?= $i ?>&<?= http_build_query(array_diff_key($_GET, ['page'=>''])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

</body>
</html>