<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/config/db.php";

// 1. Get Product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$product_id) { header("Location: index.php"); exit; }

// 2. Fetch Product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) { 
    die("<div class='container my-5 text-center'><h4>Product not found.</h4><a href='index.php' class='btn btn-dark mt-3 rounded-pill px-4'>Back to Shop</a></div>"); 
}

// 3. Fetch Gallery
$galleryStmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
$galleryStmt->execute([$product_id]);
$gallery = $galleryStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Fetch Variants
$variantStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND stock > 0");
$variantStmt->execute([$product_id]);
$variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);

$sizes = array_filter(array_unique(array_column($variants, 'size')));
$colors = array_filter(array_unique(array_column($variants, 'color')));

// 5. Fetch Review Stats (Using the product_reviews table created earlier)
$reviewStatsStmt = $pdo->prepare("SELECT COUNT(*) as total_reviews, AVG(rating) as avg_rating FROM reviews WHERE product_id = ?");
$reviewStatsStmt->execute([$product_id]);
$stats = $reviewStatsStmt->fetch();

$avg_rating = round($stats['avg_rating'] ?? 0, 1);
$total_reviews = $stats['total_reviews'] ?? 0;

$reviewsStmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$reviewsStmt->execute([$product_id]);
$all_reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

if (!function_exists('formatPrice')) {
    function formatPrice($amount) { return '₵' . number_format((float)$amount, 2); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | Shirtifyhub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --premium-navy: #0f172a; --accent-indigo: #6366f1; --bg-soft: #f8fafc; }
        body { background-color: #ffffff; font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }

        /* Premium Gallery Layout */
        .gallery-container { display: flex; gap: 20px; }
        .thumbnail-stack { display: flex; flex-direction: column; gap: 15px; width: 80px; }
        .thumb-item { 
            width: 80px; height: 80px; border-radius: 12px; cursor: pointer; 
            border: 2px solid transparent; transition: 0.3s; overflow: hidden; background: var(--bg-soft);
        }
        .thumb-item img { width: 100%; height: 100%; object-fit: cover; transition: 0.3s; mix-blend-mode: multiply; }
        .thumb-item.active { border-color: var(--premium-navy); }
        
        .main-display { 
            flex: 1; background: var(--bg-soft); border-radius: 30px; display: flex; 
            align-items: center; justify-content: center; padding: 60px; min-height: 550px;
        }
        .main-display img { max-height: 500px; width: auto; mix-blend-mode: multiply; transition: 0.5s ease; }

        /* Badges */
        .badge-premium { font-size: 0.7rem; font-weight: 800; padding: 6px 14px; border-radius: 50px; text-transform: uppercase; letter-spacing: 1px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 15px; }
        .badge-pre { background: var(--premium-navy); color: white; }
        .badge-sale { background: #fee2e2; color: #dc2626; }

        /* Pricing */
        .price-current { font-size: 2.8rem; font-weight: 800; color: var(--premium-navy); letter-spacing: -2px; line-height: 1; }
        .price-was { text-decoration: line-through; color: #94a3b8; font-size: 1.4rem; font-weight: 600; }
        
        /* Variants UI (Chips) */
        .variant-radio { display: none; }
        .variant-chip { 
            border: 1.5px solid #e2e8f0; padding: 10px 24px; border-radius: 14px; cursor: pointer; 
            font-weight: 700; font-size: 0.9rem; transition: 0.2s; display: inline-block; margin-right: 8px; margin-bottom: 8px;
            background: white; color: #64748b;
        }
        .variant-radio:checked + .variant-chip { background: var(--premium-navy); color: white; border-color: var(--premium-navy); box-shadow: 0 10px 15px -3px rgba(15,23,42,0.1); }

        /* Primary Buttons */
        .btn-premium-buy { background: var(--premium-navy); color: white; border-radius: 16px; padding: 18px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; width: 100%; border: none; transition: 0.3s; }
        .btn-premium-buy:hover { background: #1e293b; transform: translateY(-3px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .btn-premium-cart { background: transparent; border: 2px solid var(--premium-navy); color: var(--premium-navy); border-radius: 16px; padding: 16px; font-weight: 800; width: 100%; transition: 0.2s; }
        .btn-premium-cart:hover { background: var(--bg-soft); }

        /* Custom Tabs */
        .premium-tabs .nav-link { border: none; color: #94a3b8; font-weight: 800; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; padding: 20px 0; margin-right: 40px; position: relative; }
        .premium-tabs .nav-link.active { color: var(--premium-navy); background: transparent; }
        .premium-tabs .nav-link.active::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 3px; background: var(--premium-navy); border-radius: 10px; }

        @media (max-width: 991px) {
            .gallery-container { flex-direction: column-reverse; }
            .thumbnail-stack { flex-direction: row; width: 100%; overflow-x: auto; padding-bottom: 10px; }
            .main-display { padding: 40px; min-height: 400px; }
            .main-display img { max-height: 350px; }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . "/includes/header.php"; ?>

<main class="container py-5 my-lg-4">
    <nav aria-label="breadcrumb" class="mb-4 d-none d-md-block">
        <ol class="breadcrumb small fw-bold text-uppercase" style="letter-spacing: 1px;">
            <li class="breadcrumb-item"><a href="index.php" class="text-muted text-decoration-none">Shop</a></li>
            <li class="breadcrumb-item active text-dark" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <div class="col-lg-7">
            <div class="gallery-container">
                <div class="thumbnail-stack no-scrollbar">
                    <div class="thumb-item active" onclick="swapImage(this)">
                        <img src="uploads/<?= htmlspecialchars($product['image']) ?>" alt="">
                    </div>
                    <?php foreach($gallery as $g): ?>
                        <div class="thumb-item" onclick="swapImage(this)">
                            <img src="uploads/<?= htmlspecialchars($g['image_path']) ?>" alt="">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="main-display">
                    <img id="mainView" src="uploads/<?= htmlspecialchars($product['image']) ?>" class="img-fluid" alt="">
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="product-details ps-lg-4">
                <div class="d-flex flex-wrap gap-2">
                    <?php if($product['is_preorder']): ?>
                        <span class="badge-premium badge-pre"><i class="bi bi-lightning-charge-fill"></i> Secure Pre-order</span>
                    <?php endif; ?>
                    <?php if($product['discount_price'] > 0): ?>
                        <span class="badge-premium badge-sale">Limited Time Offer</span>
                    <?php endif; ?>
                </div>

                <h1 class="fw-800 text-dark mb-2" style="font-size: 2.5rem; letter-spacing: -1px;"><?= htmlspecialchars($product['name']) ?></h1>
                
                <div class="d-flex align-items-center mb-4">
                    <div class="text-warning me-2 fs-5">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <i class="bi <?= $i <= $avg_rating ? 'bi-star-fill' : ($i - 0.5 <= $avg_rating ? 'bi-star-half' : 'bi-star') ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="text-muted small fw-bold mt-1">(<?= $avg_rating ?> / 5.0 from <?= $total_reviews ?> Verified Buyers)</span>
                </div>

                <div class="mb-5">
                    <?php if($product['discount_price'] > 0): 
                        $perc = round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>
                        <div class="d-flex align-items-baseline gap-3">
                            <span class="price-current"><?= formatPrice($product['discount_price']) ?></span>
                            <span class="price-was"><?= formatPrice($product['price']) ?></span>
                            <span class="badge bg-danger rounded-pill px-3 py-2 fw-800 small">-<?= $perc ?>%</span>
                        </div>
                    <?php else: ?>
                        <span class="price-current"><?= formatPrice($product['price']) ?></span>
                    <?php endif; ?>
                </div>

                <form action="cart.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <?php if(!empty($sizes)): ?>
                        <div class="mb-4">
                            <label class="fw-800 small text-uppercase text-muted mb-3 d-block" style="letter-spacing: 1px;">1. Choose Size</label>
                            <div class="d-flex flex-wrap">
                                <?php foreach($sizes as $idx => $s): ?>
                                    <input type="radio" name="size" id="size_<?= $idx ?>" value="<?= htmlspecialchars($s) ?>" class="variant-radio" required <?= $idx==0?'checked':'' ?>>
                                    <label for="size_<?= $idx ?>" class="variant-chip"><?= htmlspecialchars($s) ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($colors)): ?>
                        <div class="mb-4">
                            <label class="fw-800 small text-uppercase text-muted mb-3 d-block" style="letter-spacing: 1px;">2. Choose Color</label>
                            <div class="d-flex flex-wrap">
                                <?php foreach($colors as $idx => $c): ?>
                                    <input type="radio" name="color" id="color_<?= $idx ?>" value="<?= htmlspecialchars($c) ?>" class="variant-radio" required <?= $idx==0?'checked':'' ?>>
                                    <label for="color_<?= $idx ?>" class="variant-chip"><?= htmlspecialchars($c) ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 align-items-end">
                        <div class="col-3">
                            <label class="fw-800 small text-uppercase text-muted mb-3 d-block">Qty</label>
                            <input type="number" name="quantity" class="form-control text-center fw-bold border-2" value="1" min="1" style="height: 60px; border-radius: 16px;">
                        </div>
                        <div class="col-9">
                            <button type="submit" name="buy_now" class="btn-premium-buy shadow-lg">
                                <?= $product['is_preorder'] ? 'Authorize Pre-order' : 'Confirm Purchase' ?>
                            </button>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-premium-cart">
                                <i class="bi bi-bag-plus me-2"></i> Add to Shopping Bag
                            </button>
                        </div>
                    </div>
                </form>

                <div class="mt-5 p-4 border rounded-4 bg-light bg-opacity-50">
                    <div class="row g-3">
                        <div class="col-6 d-flex align-items-center gap-2">
                            <i class="bi bi-patch-check-fill text-success fs-4"></i>
                            <span class="small fw-bold text-dark">100% Authentic Guaranteed</span>
                        </div>
                        <div class="col-6 d-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock-fill text-primary fs-4"></i>
                            <span class="small fw-bold text-dark">Secure Local Payment</span>
                        </div>
                        <div class="col-12 border-top mt-3 pt-3">
                            <p class="small text-muted mb-0"><i class="bi bi-truck me-2"></i><strong>Free delivery</strong> in Kumasi. 24-48h Nationwide importation clearing.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5 pt-lg-5">
        <div class="col-12">
            <nav class="nav premium-tabs border-bottom mb-5" id="productTabs">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-desc">Description</button>
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-shipping">Shipping & Returns</button>
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-reviews">Buyer Reviews (<?= $total_reviews ?>)</button>
            </nav>

            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="tab-desc">
                    <div class="lh-lg text-secondary" style="max-width: 800px; font-size: 1.05rem;">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-shipping">
                    <div class="row g-4 small fw-medium">
                        <div class="col-md-6 p-4 border rounded-4">
                            <h6 class="fw-800 text-dark text-uppercase mb-3">Delivery Estimates</h6>
                            <p><strong>Ghana Stock:</strong> Same day (Kumasi), 24-48h (Accra/Nationwide).</p>
                            <p><strong>International Pre-order:</strong> 10-14 days via Express Air Freight.</p>
                        </div>
                        <div class="col-md-6 p-4 border rounded-4">
                            <h6 class="fw-800 text-dark text-uppercase mb-3">Return Policy</h6>
                            <p>We accept exchanges within 7 days of delivery for incorrect sizing or manufacturing defects. All tags must remain attached.</p>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-reviews">
                    <?php if(empty($all_reviews)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-square-text text-muted opacity-25 display-1"></i>
                            <p class="mt-3 fw-bold text-muted">No reviews yet. Be the first to share your experience!</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach($all_reviews as $rev): ?>
                                <div class="col-md-6">
                                    <div class="p-4 rounded-4 border h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px; font-size: 0.8rem;">
                                                    <?= strtoupper(substr($rev['username'], 0, 1)) ?>
                                                </div>
                                                <strong class="text-dark"><?= htmlspecialchars($rev['username']) ?></strong>
                                            </div>
                                            <div class="text-warning small">
                                                <?php for($s=1; $s<=5; $s++) echo ($s <= $rev['rating']) ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>'; ?>
                                            </div>
                                        </div>
                                        <p class="text-secondary small mb-2"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                                        <div class="small text-muted opacity-50 fw-bold"><?= date('d M, Y', strtotime($rev['created_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function swapImage(thumb) {
        // Update main image source
        const mainImg = document.getElementById('mainView');
        const clickedImg = thumb.querySelector('img').src;
        mainImg.src = clickedImg;
        
        // Update active thumbnail state
        document.querySelectorAll('.thumb-item').forEach(el => el.classList.remove('active'));
        thumb.classList.add('active');
    }
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
</body>
</html>