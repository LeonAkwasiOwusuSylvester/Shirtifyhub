<?php 
session_start();
require_once __DIR__ . "/../config/db.php";

// 1. Security Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$errorMessage = '';
$search = $_GET['search'] ?? '';

// 2. Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    try {
        $delStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $delStmt->execute([$deleteId]);
        $_SESSION['success_msg'] = "Product #$deleteId has been permanently removed.";
        header("Location: products.php");
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Cannot delete: This product is linked to existing orders.";
    }
}

// 3. Handle Quick Stock Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_update_id'])) {
    $updateId = (int) $_POST['quick_update_id'];
    $newStock = (int) $_POST['new_stock'];
    
    try {
        $updStmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $updStmt->execute([$newStock, $updateId]);
        $_SESSION['success_msg'] = "Inventory updated for Product #$updateId.";
        header("Location: products.php");
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Database update failed.";
    }
}

// 4. Fetch Products 
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Statistics
$totalActive = count($products);
$outOfStock = 0;
$lowStock = 0;
foreach($products as $prod) {
    if($prod['stock'] <= 0) $outOfStock++;
    elseif($prod['stock'] < 10) $lowStock++; // Premium brands usually flag low stock under 10
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Manager | ShirtifyHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --admin-dark: #0f172a; 
            --admin-bg: #f8fafc;
            --border-light: #e2e8f0;
        }
        body { 
            background-color: var(--admin-bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #334155;
        }

        .navbar { background: #ffffff; border-bottom: 1px solid var(--border-light); padding: 1.2rem 0; }
        .nav-brand { font-weight: 800; letter-spacing: -0.5px; color: var(--admin-dark); text-decoration: none; font-size: 1.4rem; }

        /* Stats Section */
        .stat-card {
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border-light);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
            transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        /* Product Table Styling */
        .table-container {
            background: white;
            border-radius: 24px;
            border: 1px solid var(--border-light);
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .table-premium thead th {
            background: #fcfcfd;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            padding: 1.2rem 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-premium tbody td { padding: 1.2rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }

        /* Product Item UI */
        .product-thumb {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            background: #f1f5f9;
            border: 1px solid var(--border-light);
            overflow: hidden;
            object-fit: cover;
        }
        
        .stock-pill {
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .stock-in { background: #ecfdf5; color: #10b981; }
        .stock-low { background: #fffbeb; color: #f59e0b; }
        .stock-out { background: #fef2f2; color: #ef4444; }

        /* Search Bar */
        .search-wrapper { position: relative; width: 100%; max-width: 350px; }
        .search-wrapper i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-control {
            border-radius: 50px;
            border: 1px solid var(--border-light);
            padding: 0.6rem 1rem 0.6rem 3rem;
            font-weight: 500;
            transition: 0.3s;
        }
        .search-control:focus { box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); border-color: #6366f1; }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            border: 1px solid var(--border-light);
            background: white;
            color: #64748b;
        }
        .btn-action:hover { background: var(--admin-dark); color: white; border-color: var(--admin-dark); }
        .btn-delete:hover { background: #ef4444; color: white; border-color: #ef4444; }

        .btn-premium { background: var(--admin-dark); color: white; border-radius: 50px; font-weight: 700; padding: 10px 24px; border: none; }
        .btn-premium:hover { background: #1e293b; color: white; transform: scale(1.02); }
    </style>
</head>
<body>

    <nav class="navbar sticky-top shadow-sm">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="nav-brand" href="index.php">SHIRTIFYHUB <span class="text-muted fw-light">ADMIN</span></a>
            <div class="d-flex align-items-center gap-2">
                <a href="../index.php" target="_blank" class="btn btn-light border btn-sm px-4 rounded-pill fw-bold text-dark">Live Store</a>
                <a href="logout.php" class="btn btn-danger btn-sm px-4 rounded-pill fw-bold">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h1 class="fw-bold text-dark mb-1" style="letter-spacing: -1px;">Inventory Stock</h1>
                <p class="text-muted mb-0">Manage your product catalog, stock levels and categories.</p>
            </div>
            <a href="add_product.php" class="btn btn-premium shadow-sm"><i class="bi bi-plus-lg me-2"></i>Add New Product</a>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card">
                    <div><div class="stat-label">Total Catalog</div><h3 class="fw-bold m-0 text-dark"><?= $totalActive ?></h3></div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-boxes"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div><div class="stat-label text-warning">Low Stock Alerts</div><h3 class="fw-bold m-0 text-warning"><?= $lowStock ?></h3></div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-lightning-charge"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div><div class="stat-label text-danger">Sold Out Items</div><h3 class="fw-bold m-0 text-danger"><?= $outOfStock ?></h3></div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-dash-circle"></i></div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mb-4">
            <form action="" method="GET" class="search-wrapper">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="form-control search-control" placeholder="Search by name or ID..." value="<?= htmlspecialchars($search) ?>">
            </form>
            <div class="text-muted small fw-bold">Showing <?= count($products) ?> products</div>
        </div>

        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                <div class="fw-bold"><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 fw-bold"><?= $errorMessage ?></div>
        <?php endif; ?>

        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-premium align-middle m-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Product Details</th>
                            <th>Category</th>
                            <th>Unit Price</th>
                            <th>Stock Level</th>
                            <th class="text-end pe-4">Management</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../uploads/<?= $p['image'] ?>" class="product-thumb" alt="" onerror="this.src='../public/assets/images/no-image.png'">
                                    <div>
                                        <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem; font-weight: 700;">REF: #<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border fw-bold" style="font-size: 0.65rem;"><?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></span></td>
                            <td class="fw-bold text-dark">₵<?= number_format($p['price'], 2) ?></td>
                            <td>
                                <?php if($p['stock'] <= 0): ?>
                                    <span class="stock-pill stock-out">Out of Stock</span>
                                <?php elseif($p['stock'] < 10): ?>
                                    <span class="stock-pill stock-low"><?= $p['stock'] ?> Units Left</span>
                                <?php else: ?>
                                    <span class="stock-pill stock-in"><?= $p['stock'] ?> Available</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn-action" title="Quick Stock Update" onclick="openStockModal(<?= $p['id'] ?>, <?= $p['stock'] ?>)">
                                        <i class="bi bi-layers"></i>
                                    </button>
                                    <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn-action" title="Full Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                                        <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                                        <button class="btn-action btn-delete" title="Delete Product">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="stockModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content p-4 rounded-4 border-0 shadow-lg">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-box-seam me-2"></i>Update Units</h6>
                <form method="POST">
                    <input type="hidden" name="quick_update_id" id="quickUpdateId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Current Quantity</label>
                        <input type="number" name="new_stock" id="quickUpdateStock" class="form-control fw-bold border-2" required>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-bold">Apply Changes</button>
                </form>
            </div>
        </div>
    </div>

    <footer class="bg-white border-top py-4 mt-auto">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="small fw-bold text-muted">© <?= date('Y') ?> ShirtifyHub Admin</div>
            <div class="small text-muted fw-medium">Inventory System v2.1</div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openStockModal(id, current) {
            document.getElementById('quickUpdateId').value = id;
            document.getElementById('quickUpdateStock').value = current;
            new bootstrap.Modal(document.getElementById('stockModal')).show();
        }
    </script>
</body>
</html>