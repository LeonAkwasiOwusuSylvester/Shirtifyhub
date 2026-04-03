<?php
require_once __DIR__ . "/../config/db.php";

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = "";

// 1. Fetch Categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $discount_price = !empty($_POST['discount_price']) ? $_POST['discount_price'] : 0;
    $is_preorder = isset($_POST['is_preorder']) ? 1 : 0;
    $description = trim($_POST['description']);
    
    // Main Image Upload
    $primaryImage = "";
    if (!empty($_FILES['image']['name'])) {
        $primaryImage = time() . "_main_" . str_replace(' ', '_', basename($_FILES['image']['name']));
        move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . "/../uploads/" . $primaryImage);
    }

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO products (category_id, name, description, price, discount_price, is_preorder, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$category_id, $name, $description, $price, $discount_price, $is_preorder, $primaryImage]);
        
        $product_id = $pdo->lastInsertId();

        // Gallery Images
        if (!empty($_FILES['gallery']['name'][0])) {
            $galleryStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
            foreach ($_FILES['gallery']['tmp_name'] as $key => $tmp_name) {
                if(!empty($tmp_name)){
                    $gImageName = time() . "_gal_" . $key . "_" . str_replace(' ', '_', basename($_FILES['gallery']['name'][$key]));
                    if (move_uploaded_file($tmp_name, __DIR__ . "/../uploads/" . $gImageName)) {
                        $galleryStmt->execute([$product_id, $gImageName]);
                    }
                }
            }
        }

        // Variants
        if (!empty($_POST['sizes'])) {
            $variantStmt = $pdo->prepare("INSERT INTO product_variants (product_id, size, color, stock) VALUES (?, ?, ?, ?)");
            foreach ($_POST['sizes'] as $index => $size) {
                if (!empty($size)) {
                    $color = $_POST['colors'][$index] ?? 'N/A';
                    $stock = (int)($_POST['stocks'][$index] ?? 0);
                    $variantStmt->execute([$product_id, $size, $color, $stock]);
                }
            }
        }

        $pdo->commit();
        $message = "<div class='alert alert-success border-0 shadow-sm rounded-4 mb-4'><i class='bi bi-check-circle-fill me-2'></i> Product published successfully!</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger border-0 shadow-sm rounded-4 mb-4'><i class='bi bi-exclamation-octagon-fill me-2'></i> Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | ShirtifyHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --admin-dark: #0f172a; 
            --admin-bg: #f8fafc;
            --border-light: #e2e8f0;
            --indigo-600: #4f46e5;
        }
        body { 
            background-color: var(--admin-bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #334155;
        }

        .navbar { background: white; border-bottom: 1px solid var(--border-light); padding: 1.2rem 0; }
        .nav-brand { font-weight: 800; letter-spacing: -0.5px; color: var(--admin-dark); text-decoration: none; font-size: 1.3rem; }

        .card-premium {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }
        .section-header i { color: var(--indigo-600); font-size: 1.2rem; }
        .section-header span { font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--admin-dark); }

        .form-label { font-weight: 700; font-size: 0.85rem; color: #475569; margin-bottom: 8px; }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid var(--border-light);
            background-color: #fcfcfd;
            font-weight: 500;
            transition: 0.2s;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: var(--indigo-600);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .btn-add-variant {
            background: #eef2ff;
            color: var(--indigo-600);
            border: none;
            font-weight: 700;
            font-size: 0.8rem;
            border-radius: 10px;
            padding: 8px 16px;
            transition: 0.2s;
        }
        .btn-add-variant:hover { background: var(--indigo-600); color: white; }

        .variant-row {
            background: #f8fafc;
            border-radius: 16px;
            padding: 15px;
            border: 1px solid var(--border-light);
            margin-bottom: 12px;
            transition: 0.2s;
        }

        .btn-publish {
            background: var(--admin-dark);
            color: white;
            border-radius: 50px;
            padding: 16px;
            font-weight: 800;
            letter-spacing: 0.5px;
            border: none;
            transition: 0.3s;
        }
        .btn-publish:hover { background: #1e293b; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <nav class="navbar sticky-top shadow-sm">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="nav-brand" href="index.php">SHIRTIFYHUB <span class="text-muted fw-light">ADMIN</span></a>
            <div class="d-flex align-items-center gap-2">
                <a href="../index.php" target="_blank" class="btn btn-light border btn-sm px-4 rounded-pill fw-bold">Live Store</a>
                <a href="logout.php" class="btn btn-danger btn-sm px-4 rounded-pill fw-bold">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 950px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold text-dark mb-1" style="letter-spacing: -1px;">Create Product</h1>
                <p class="text-muted mb-0 small">Publish a new item to your online collection.</p>
            </div>
            <a href="index.php" class="btn btn-light border px-4 rounded-pill fw-bold text-dark small"><i class="bi bi-arrow-left me-2"></i>Dashboard</a>
        </div>

        <?= $message ?>

        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="card-premium p-4 p-lg-5 mb-4">
                <div class="section-header">
                    <i class="bi bi-info-circle"></i>
                    <span>Primary Information</span>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-8">
                        <label class="form-label">Full Product Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Signature Oversized Hoodie" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="" disabled selected>Select Category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Listing Price (₵)</label>
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-success">Promotional Price (₵)</label>
                        <input type="number" step="0.01" name="discount_price" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch mb-2 ms-2">
                            <input class="form-check-input" type="checkbox" name="is_preorder" id="preorderSwitch">
                            <label class="form-check-label fw-bold text-dark" for="preorderSwitch" style="font-size: 0.85rem;">Mark as Pre-order</label>
                        </div>
                    </div>
                    <div class="col-12 mt-4">
                        <label class="form-label">Product Description</label>
                        <textarea name="description" class="form-control" rows="5" placeholder="Detail the materials, fit, and style..."></textarea>
                    </div>
                </div>
            </div>

            <div class="card-premium p-4 p-lg-5 mb-4">
                <div class="section-header">
                    <i class="bi bi-images"></i>
                    <span>Media Assets</span>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Cover Image</label>
                        <div class="p-3 border rounded-4 bg-light bg-opacity-50">
                            <input type="file" name="image" class="form-control border-0" accept="image/*" required>
                            <div class="small text-muted mt-2 ps-1">This is the main thumbnail used on the store.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gallery Upload</label>
                        <div class="p-3 border rounded-4 bg-light bg-opacity-50">
                            <input type="file" name="gallery[]" class="form-control border-0" accept="image/*" multiple>
                            <div class="small text-muted mt-2 ps-1">Upload additional angles (Select multiple files).</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-premium p-4 p-lg-5 mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <div class="section-header border-0 mb-0 pb-0">
                        <i class="bi bi-layers"></i>
                        <span>Stock & Variations</span>
                    </div>
                    <button type="button" class="btn btn-add-variant" onclick="addVariantRow()">
                        <i class="bi bi-plus-lg me-1"></i> Add Variant
                    </button>
                </div>

                <div id="variants-container">
                    <div class="variant-row shadow-sm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small">Size</label>
                                <input type="text" name="sizes[]" class="form-control form-control-sm" placeholder="e.g. XL or 42" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Color / Style</label>
                                <input type="text" name="colors[]" class="form-control form-control-sm" placeholder="e.g. Navy Blue" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Stock Count</label>
                                <input type="number" name="stocks[]" class="form-control form-control-sm" placeholder="Qty" required>
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="removeRow(this)">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid mb-5">
                <button type="submit" class="btn btn-publish py-3 shadow-lg">
                    <i class="bi bi-send-fill me-2"></i>Publish to Live Store
                </button>
            </div>
        </form>
    </div>

    <footer class="bg-white border-top py-4 mt-5">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="small fw-bold text-muted">© <?= date('Y') ?> ShirtifyHub Admin</div>
            <div class="small text-muted fw-medium">Catalogue Management System v3.0</div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addVariantRow() {
            const container = document.getElementById('variants-container');
            const row = document.createElement('div');
            row.className = 'variant-row shadow-sm animate__animated animate__fadeIn';
            row.innerHTML = `
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Size</label>
                        <input type="text" name="sizes[]" class="form-control form-control-sm" placeholder="e.g. XL or 42" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Color / Style</label>
                        <input type="text" name="colors[]" class="form-control form-control-sm" placeholder="e.g. Navy Blue" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Stock Count</label>
                        <input type="number" name="stocks[]" class="form-control form-control-sm" placeholder="Qty" required>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="removeRow(this)">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(row);
        }

        function removeRow(btn) {
            const rows = document.querySelectorAll('.variant-row');
            if (rows.length > 1) {
                btn.closest('.variant-row').remove();
            } else {
                alert("You must have at least one variant.");
            }
        }
    </script>
</body>
</html>