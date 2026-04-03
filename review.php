<?php
session_start();
require_once __DIR__ . "/config/db.php";

// 1. Check Authentication
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];

// 2. Validate Order ID
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: my-orders.php");
    exit;
}

$orderId = (int)$_GET['order_id'];

// 3. Fetch Order Items for review (Only from delivered orders)
$stmt = $pdo->prepare("
    SELECT oi.product_id, p.name, p.image, oi.price 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.id = ? AND o.user_id = ? AND o.status = 'delivered'
");
$stmt->execute([$orderId, $userId]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$products) {
    die("<div class='container my-5 text-center'><h4>No items available to review.</h4><a href='my-orders.php' class='btn btn-dark mt-3'>Back to Orders</a></div>");
}

// 4. Handle Submission
$showSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reviews'])) {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST['reviews'] as $productId => $data) {
            $rating = (int)$data['rating'];
            $comment = trim($data['comment']);
            
            if ($rating >= 1 && $rating <= 5) {
                // Check if user already reviewed this product
                $check = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
                $check->execute([$userId, $productId]);
                
                if (!$check->fetch()) {
                    $ins = $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $ins->execute([$userId, $productId, $rating, $comment]);
                }
            }
        }
        
        $pdo->commit();
        $showSuccess = true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "System Error: Feedback could not be saved.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Purchase | Shirtifyhub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { background-color: var(--bg-main); }
        .review-card { border-radius: var(--radius-card); border: 1px solid var(--border-color); background: white; box-shadow: var(--shadow-sm); overflow: hidden; }
        
        .star-rating { direction: rtl; display: flex; justify-content: flex-start; gap: 10px; }
        .star-rating input { display: none; }
        .star-rating label { cursor: pointer; font-size: 2rem; color: #e2e8f0; transition: 0.2s; }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label { color: #f59e0b; }

        .product-thumb { width: 70px; height: 70px; object-fit: cover; border-radius: 12px; border: 1px solid var(--border-color); background: #f8fafc; }
        .btn-brand { background-color: var(--brand-primary); color: white; border-radius: 25px; padding: 12px 30px; font-weight: 600; border: none; }
        .btn-brand:hover { background-color: #1e293b; color: white; }
    </style>
</head>
<body>

<?php require_once __DIR__ . "/includes/header.php"; ?>

<div class="container py-5" style="max-width: 700px;">
    <div class="mb-4">
        <a href="my-orders.php" class="text-decoration-none text-muted small fw-bold">
            <i class="bi bi-arrow-left"></i> BACK TO ORDERS
        </a>
        <h2 class="fw-bold mt-2 mb-0">Review your fresh fits</h2>
        <p class="text-muted">Tell us what you think about Order #<?= $orderId ?></p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger rounded-4 border-0 shadow-sm"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <?php foreach ($products as $prod): ?>
            <div class="review-card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4">
                        <img src="uploads/<?= htmlspecialchars($prod['image']) ?>" class="product-thumb me-3" onerror="this.src='assets/images/no-image.png'">
                        <div>
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($prod['name']) ?></h6>
                            <span class="text-muted small">Purchased for ₵<?= number_format($prod['price'], 2) ?></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase text-muted">Quality Rating</label>
                        <div class="star-rating">
                            <?php for($s=5; $s>=1; $s--): ?>
                                <input type="radio" id="s<?= $s ?>-<?= $prod['product_id'] ?>" name="reviews[<?= $prod['product_id'] ?>][rating]" value="<?= $s ?>" required>
                                <label for="s<?= $s ?>-<?= $prod['product_id'] ?>" class="bi bi-star-fill"></label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-uppercase text-muted">Comments</label>
                        <textarea 
                            name="reviews[<?= $prod['product_id'] ?>][comment]" 
                            class="form-control border-0 bg-light p-3" 
                            rows="3" 
                            maxlength="200"
                            style="border-radius: 12px;"
                            placeholder="Tell us about the fit and material..."
                        ></textarea>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="d-grid gap-3 mt-4">
            <button type="submit" name="submit_reviews" class="btn btn-brand btn-lg shadow-sm">
                Submit Feedback
            </button>
            <a href="orders.php" class="btn btn-link text-muted text-decoration-none fw-bold">Skip for now</a>
        </div>
    </form>
</div>

<script>
    <?php if ($showSuccess): ?>
    Swal.fire({
        title: 'Thank You!',
        text: 'Your review helps us keep Shirtifyhub quality high.',
        icon: 'success',
        confirmButtonColor: '#0f172a',
        confirmButtonText: 'Back to Orders'
    }).then(() => {
        window.location.href = 'orders.php';
    });
    <?php endif; ?>
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

</body>
</html>