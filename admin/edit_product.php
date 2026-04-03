<?php
session_start();
require_once __DIR__ . "/../config/db.php";

// 1. Security Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$productId = $_GET['id'] ?? null;
if (!$productId) {
    header("Location: products.php");
    exit;
}

$message = "";

// 2. Handle Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $discount_price = !empty($_POST['discount_price']) ? $_POST['discount_price'] : 0;
    $is_preorder = isset($_POST['is_preorder']) ? 1 : 0;
    $description = trim($_POST['description']);
    $totalStock = (int)$_POST['stock']; // Main fallback stock

    try {
        $pdo->beginTransaction();

        // Update Main Product Details
        $sql = "UPDATE products SET name=?, category_id=?, price=?, discount_price=?, is_preorder=?, description=?, stock=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $category_id, $price, $discount_price, $is_preorder, $description, $totalStock, $productId]);

        // Handle New Main Image Upload
        if (!empty($_FILES['image']['name'])) {
            $mainImgName = time() . "_main_" . str_replace(' ', '_', basename($_FILES['image']['name']));
            if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . "/../uploads/" . $mainImgName)) {
                $pdo->prepare("UPDATE products SET image=? WHERE id=?")->execute([$mainImgName, $productId]);
            }
        }

        // Handle New Gallery Images
        if (!empty($_FILES['gallery']['name'][0])) {
            $galleryStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
            foreach ($_FILES['gallery']['tmp_name'] as $key => $tmp_name) {
                if (!empty($tmp_name)) {
                    $gImageName = time() . "_gal_" . $key . "_" . str_replace(' ', '_', basename($_FILES['gallery']['name'][$key]));
                    if (move_uploaded_file($tmp_name, __DIR__ . "/../uploads/" . $gImageName)) {
                        $galleryStmt->execute([$productId, $gImageName]);
                    }
                }
            }
        }

        // --- VARIATION LOGIC: REFRESH VARIANTS ---
        if (!empty($_POST['sizes'])) {
            // Delete existing variants for this product first
            $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$productId]);

            // Insert the updated list
            $variantStmt = $pdo->prepare("INSERT INTO product_variants (product_id, size, color, stock) VALUES (?, ?, ?, ?)");
            foreach ($_POST['sizes'] as $index => $size) {
                if (!empty($size)) {
                    $color = $_POST['colors'][$index] ?? 'N/A';
                    $vStock = (int)($_POST['stocks'][$index] ?? 0);
                    $variantStmt->execute([$productId, $size, $color, $vStock]);
                }
            }
        }

        $pdo->commit();
        $message = "<div class='alert alert-success border-0 shadow-sm rounded-4 mb-4'><i class='bi bi-check-circle-fill me-2'></i> Product and variations updated successfully!</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger border-0 shadow-sm rounded-4 mb-4'><i class='bi bi-exclamation-octagon-fill me-2'></i> Error: " . $e->getMessage() . "</div>";
    }
}

// 3. Handle Deleting a Gallery Image
if (isset($_GET['delete_img'])) {
    $imgId = (int)$_GET['delete_img'];
    $pdo->prepare("DELETE FROM product_images WHERE id=? AND product_id=?")->execute([$imgId, $productId]);
    header("Location: edit_product.php?id=$productId");
    exit;
}

// 4. Fetch Current Data
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) { header("Location: products.php"); exit; }

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$gallery = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$gallery->execute([$productId]);
$images = $gallery->fetchAll();

// Fetch Variants for the list
$vStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$vStmt->execute([$productId]);
$variants = $vStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | ShirtifyHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --admin-dark: #0f172a; --admin-bg: #f8fafc; --border-light: #e2e8f0; --indigo-600: #4f46e5; }
        body { background-color: var(--admin-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }
        .navbar { background: white; border-bottom: 1px solid var(--border-light); padding: 1.2rem 0; }
        .nav-brand { font-weight: 800; letter-spacing: -0.5px; color: var(--admin-dark); text-decoration: none; font-size: 1.3rem; }
        .card-premium { background: #ffffff; border: 1px solid var(--border-light); border-radius: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03); }
        .section-header { display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9; }
        .section-header i { color: var(--indigo-600); font-size: 1.2rem; }
        .section-header span { font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--admin-dark); }
        .form-label { font-weight: 700; font-size: 0.85rem; color: #475569; margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 12px; padding: 12px 16px; border: 1px solid var(--border-light); background-color: #fcfcfd; font-weight: 500; transition: 0.2s; }
        .form-control:focus { background-color: #fff; border-color: var(--indigo-600); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
        .img-container { position: relative; width: 110px; height: 110px; border-radius: 16px; overflow: hidden; border: 2px solid var(--border-light); background: #f8fafc; }
        .img-container img { width: 100%; height: 100%; object-fit: cover; }
        .delete-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(239, 68, 68, 0.85); display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.2s; color: white; text-decoration: none; }
        .img-container:hover .delete-overlay { opacity: 1; }
        .variant-row { background: #f8fafc; border-radius: 16px; padding: 15px; border: 1px solid var(--border-light); margin-bottom: 12px; }
        .btn-update { background: var(--admin-dark); color: white; border-radius: 50px; padding: 16px; font-weight: 800; border: none; transition: 0.3s; }
        .btn-update:hover { background: #1e293b; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); color: white; }
    </style>
</head>
<body>

    <nav class="navbar sticky-top shadow-sm">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="nav-brand" href="index.php">SHIRTIFYHUB <span class="text-muted fw-light">ADMIN</span></a>
            <a href="products.php" class="btn btn-light border btn-sm px-4 rounded-pill fw-bold">Inventory</a>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 950px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold text-dark mb-1" style="letter-spacing: -1px;">Edit Product</h1>
                <p class="text-muted mb-0 small">Updating Product #<?= str_pad($product['id'], 5, '0', STR_PAD_LEFT) ?></p>
            </div>
            <a href="products.php" class="btn btn-light border px-4 rounded-pill fw-bold text-dark small"><i class="bi bi-arrow-left me-2"></i>Back</a>
        </div>

        <?= $message ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="update_product" value="1">

            <div class="card-premium p-4 p-lg-5 mb-4">
                <div class="section-header">
                    <i class="bi bi-pencil-square"></i>
                    <span>Primary Information</span>
                </div>
                <div class="row g-4">
                    <div class="col-md-8">
                        <label class="form-label">Full Product Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Regular Price (₵)</label>
                        <input type="number" step="0.01" name="price" class="form-control fw-bold" value="<?= $product['price'] ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-success">Promotional Price (₵)</label>
                        <input type="number" step="0.01" name="discount_price" class="form-control fw-bold text-success" value="<?= $product['discount_price'] ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_preorder" id="preSwitch" <?= $product['is_preorder'] ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold text-dark ms-2" for="preSwitch" style="font-size: 0.85rem;">Pre-order Status</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-premium p-4 p-lg-5 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <div class="section-header border-0 mb-0 pb-0">
                        <i class="bi bi-layers"></i>
                        <span>Stock & Variations</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3" onclick="addVariantRow()">
                        <i class="bi bi-plus-lg me-1"></i> Add Variant
                    </button>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Total Fallback Stock (Master Count)</label>
                    <input type="number" name="stock" class="form-control mb-4" style="max-width: 200px;" value="<?= $product['stock'] ?>" required>
                </div>

                <div id="variants-container">
                    <?php if (empty($variants)): ?>
                        <p class="text-muted small italic mb-3">No specific variations (size/color) defined yet.</p>
                    <?php else: ?>
                        <?php foreach($variants as $v): ?>
                            <div class="variant-row shadow-sm">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small">Size</label>
                                        <input type="text" name="sizes[]" class="form-control form-control-sm" value="<?= htmlspecialchars($v['size']) ?>" placeholder="e.g. XL" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Color</label>
                                        <input type="text" name="colors[]" class="form-control form-control-sm" value="<?= htmlspecialchars($v['color']) ?>" placeholder="e.g. Black" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Stock</label>
                                        <input type="number" name="stocks[]" class="form-control form-control-sm" value="<?= $v['stock'] ?>" required>
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="this.closest('.variant-row').remove()">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-premium p-4 p-lg-5 mb-4">
                <div class="section-header">
                    <i class="bi bi-camera"></i>
                    <span>Media Management</span>
                </div>
                <div class="mb-5">
                    <label class="form-label">Cover Image Replacement</label>
                    <div class="d-flex align-items-start gap-4 p-3 border rounded-4 bg-light bg-opacity-50">
                        <div class="img-container shadow-sm">
                            <img src="../uploads/<?= $product['image'] ?>" alt="Current">
                        </div>
                        <div class="flex-grow-1">
                            <input type="file" name="image" class="form-control border-0" accept="image/*">
                            <p class="small text-muted mt-2 mb-0">Leave blank to keep current cover.</p>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="form-label">Gallery Collection</label>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <?php foreach($images as $img): ?>
                            <div class="img-container shadow-sm">
                                <img src="../uploads/<?= $img['image_path'] ?>">
                                <a href="?id=<?= $productId ?>&delete_img=<?= $img['id'] ?>" class="delete-overlay" onclick="return confirm('Permanently remove this image?')">
                                    <i class="bi bi-trash3 fs-4"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="p-3 border rounded-4 bg-light bg-opacity-50">
                        <input type="file" name="gallery[]" class="form-control border-0" accept="image/*" multiple>
                        <p class="small text-muted mt-2 mb-0">Add new angles to the collection.</p>
                    </div>
                </div>
            </div>

            <div class="card-premium p-4 p-lg-5 mb-5">
                <div class="section-header">
                    <i class="bi bi-text-left"></i>
                    <span>Product Description</span>
                </div>
                <textarea name="description" class="form-control" rows="6"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="d-grid mb-5">
                <button type="submit" class="btn btn-update py-3 shadow-lg">
                    <i class="bi bi-arrow-repeat me-2"></i>Push Updates to Store
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addVariantRow() {
            const container = document.getElementById('variants-container');
            const row = document.createElement('div');
            row.className = 'variant-row shadow-sm';
            row.innerHTML = `
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Size</label>
                        <input type="text" name="sizes[]" class="form-control form-control-sm" placeholder="e.g. XL" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Color</label>
                        <input type="text" name="colors[]" class="form-control form-control-sm" placeholder="e.g. Black" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Stock</label>
                        <input type="number" name="stocks[]" class="form-control form-control-sm" placeholder="Qty" required>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="this.closest('.variant-row').remove()">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(row);
        }
    </script>
</body>
</html>