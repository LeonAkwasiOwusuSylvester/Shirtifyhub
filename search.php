<?php
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/header.php";

// Get the search term from the URL
$searchQuery = $_GET['q'] ?? '';
$products = [];

if (!empty($searchQuery)) {
    // Securely search the database for matching names or descriptions
    $stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active' AND (name LIKE ? OR description LIKE ?) ORDER BY created_at DESC");
    $searchTerm = "%" . $searchQuery . "%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $products = $stmt->fetchAll();
}
?>

<main class="container my-5" style="min-height: 60vh;">
    <div class="mb-4 border-bottom pb-3">
        <h2 class="fw-light">Search Results for "<span class="fw-bold"><?= htmlspecialchars($searchQuery) ?></span>"</h2>
        <p class="text-muted mb-0"><?= count($products) ?> item(s) found</p>
    </div>

    <?php if (count($products) > 0): ?>
        <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="product-card">
                        <a href="product.php?id=<?= $product['id'] ?>" class="card-img position-relative">
                            <span class="badge badge-custom position-absolute top-0 start-0 m-3">Imported</span>
                            
                            <button class="wishlist-btn position-absolute top-0 end-0 m-3" title="Add to Wishlist">
                                <i class="bi bi-heart"></i>
                            </button>

                            <?php $imgPath = !empty($product['image']) ? 'uploads/' . $product['image'] : 'https://via.placeholder.com/400x500'; ?>
                            <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                        </a>
                        <div class="card-body">
                            <a href="product.php?id=<?= $product['id'] ?>" class="product-title"><?= htmlspecialchars($product['name']) ?></a>
                            <div class="product-price">GH₵ <?= number_format($product['price'], 2) ?></div>
                            <a href="product.php?id=<?= $product['id'] ?>" class="btn-view">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5 my-5">
            <i class="bi bi-search text-muted mb-3" style="font-size: 3rem;"></i>
            <h4 class="fw-light">No products found</h4>
            <p class="text-muted">Try searching for something else like "sneakers" or "watch".</p>
            <a href="index.php" class="btn btn-outline-dark mt-3 rounded-0 px-4">Back to Shop</a>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>