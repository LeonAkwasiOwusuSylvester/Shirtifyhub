<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/config/db.php";

// Standard Price Formatter
if (!function_exists('formatPrice')) {
    function formatPrice($amount) { return '₵' . number_format((float)$amount, 2); }
}

// Security Guard
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];

// Handle Order Deletion (Only for completed/cancelled orders)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_id'])) {
    $orderId = (int) $_POST['delete_order_id'];
    try {
        $pdo->beginTransaction();
        $checkStmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$orderId, $userId]);
        $order = $checkStmt->fetch();
        $status = strtolower(trim($order['status'] ?? ''));

        if ($order && in_array($status, ['delivered', 'cancelled', 'failed', 'refunded'])) {
            $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
            $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
            $pdo->commit();
            $_SESSION['success'] = "Order record removed.";
        } else {
            $pdo->rollBack();
            $_SESSION['error'] = "Cannot delete an active order.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
    header("Location: orders.php");
    exit;
}

// Handle Order Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $orderId = (int) $_POST['cancel_order_id'];
    try {
        $pdo->beginTransaction();
        $checkStmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$orderId, $userId]);
        $order = $checkStmt->fetch();
        
        if ($order && in_array(strtolower($order['status']), ['processing', 'pending'])) {
            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);
            $pdo->commit();
            $_SESSION['success'] = "Order #SH-$orderId cancelled.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
    header("Location: orders.php");
    exit;
}

// Fetch Orders
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders | Shirtifyhub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public/style.css">
    <style>
        :root { --premium-dark: #0f172a; --bg-soft: #f8fafc; --border-color: #e2e8f0; }
        body { background-color: var(--bg-soft); font-family: 'Inter', system-ui, sans-serif; }
        
        .page-header { background: white; padding: 3rem 0; border-bottom: 1px solid var(--border-color); margin-bottom: 3rem; }
        
        .order-card { 
            background: white; 
            border-radius: 24px; 
            margin-bottom: 2.5rem; 
            box-shadow: 0 4px 15px -3px rgba(0,0,0,0.04); 
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .order-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08); }
        
        .order-header { background: #fcfcfd; padding: 1.5rem 2.5rem; border-bottom: 1px solid #f1f5f9; }
        
        .status-badge { 
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 50px; 
            font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
        }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-processing { background: #e0f2fe; color: #075985; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .product-item { padding: 1.5rem 2.5rem; border-bottom: 1px solid #f8fafc; }
        .product-item:last-child { border-bottom: none; }
        
        .img-box { 
            width: 90px; height: 90px; border-radius: 16px; background: var(--bg-soft); 
            border: 1px solid var(--border-color); display: flex; align-items: center; 
            justify-content: center; flex-shrink: 0; overflow: hidden;
        }
        .img-box img { width: 100%; height: 100%; object-fit: cover; }
        
        .card-footer-custom { 
            background: white; padding: 1.5rem 2.5rem; border-top: 1px solid #f1f5f9; 
            display: flex; justify-content: space-between; align-items: center;
        }
        
        /* Premium Compact Action Buttons */
        .btn-group-premium { display: flex; gap: 10px; }
        .btn-action { 
            font-size: 0.85rem; font-weight: 700; border-radius: 12px; padding: 10px 20px; 
            transition: all 0.2s; border: 1px solid var(--border-color); background: white; 
            color: #475569; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-action:hover { background: var(--bg-soft); color: var(--premium-dark); border-color: #cbd5e1; }
        
        .btn-review { background: var(--premium-dark); color: white; border: none; }
        .btn-review:hover { background: #1e293b; color: white; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2); }
        
        .btn-cancel { color: #ef4444; border-color: #fecaca; }
        .btn-cancel:hover { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

<?php require_once __DIR__ . "/includes/header.php"; ?>

<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center" style="max-width: 1000px;">
        <div>
            <h2 class="fw-bold mb-1 text-dark">Order History</h2>
            <p class="text-muted mb-0 small">Manage your orders and track deliveries.</p>
        </div>
        <span class="badge bg-white text-dark border px-4 py-2 rounded-pill fw-bold shadow-sm"><?= count($orders) ?> Total Orders</span>
    </div>
</div>

<div class="container pb-5" style="max-width: 1000px;">

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center p-3">
            <i class="bi bi-check-circle-fill me-3 fs-4"></i>
            <div class="fw-bold"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5 bg-white shadow-sm border-0 rounded-4">
            <i class="bi bi-bag-heart text-muted display-1 mb-4 d-block"></i>
            <h4 class="fw-bold text-dark">Your history is clear</h4>
            <p class="text-muted mb-4">Time to add some fresh pieces to your collection.</p>
            <a href="index.php" class="btn btn-dark rounded-pill px-5 py-3 fw-bold shadow-lg">Start Shopping</a>
        </div>
    <?php else: ?>
        
        <?php foreach ($orders as $o): ?>
            <?php
                $itemStmt = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                $itemStmt->execute([$o['id']]);
                $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                $status = strtolower($o['status'] ?? 'pending');
                $formattedOrderId = "SH-" . str_pad($o['id'], 6, '0', STR_PAD_LEFT);
            ?>
            <div class="order-card">
                <div class="order-header d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 1px;">Order #<?= $formattedOrderId ?></div>
                        <div class="small text-dark fw-medium"><i class="bi bi-calendar3 me-2"></i><?= date("d M Y • h:i A", strtotime($o['created_at'])) ?></div>
                    </div>
                    <span class="status-badge status-<?= $status ?>"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> <?= $status ?></span>
                </div>

                <div class="order-body">
                    <?php foreach ($items as $i): ?>
                        <div class="product-item d-flex align-items-center gap-4">
                            <div class="img-box shadow-sm">
                                <img src="uploads/<?= $i['image'] ?>" alt="">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1 text-dark fs-5"><?= htmlspecialchars($i['name']) ?></h6>
                                <div class="text-muted small fw-medium mb-3">
                                    Quantity: <?= $i['quantity'] ?> &bull; 
                                    <?= $i['selected_size'] !== 'N/A' ? "Size: ".$i['selected_size'] : '' ?>
                                    <?= $i['selected_color'] !== 'N/A' ? " &bull; Color: ".$i['selected_color'] : '' ?>
                                </div>
                                
                                <?php if ($status === 'delivered'): ?>
                                    <a href="review.php?product_id=<?= $i['product_id'] ?>&order_id=<?= $o['id'] ?>" class="btn-action btn-review py-1 px-3">
                                        <i class="bi bi-star-fill"></i> Write a Review
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-dark fs-5"><?= formatPrice($i['price'] * $i['quantity']) ?></div>
                                <div class="text-muted small fw-medium"><?= formatPrice($i['price']) ?> / unit</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card-footer-custom">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">Order Total Paid</span>
                        <div class="fw-bold fs-3 text-dark"><?= formatPrice($o['total_amount']) ?></div>
                    </div>
                    
                    <div class="btn-group-premium">
                        <?php if (in_array($status, ['pending', 'processing'])): ?>
                            <button type="button" class="btn-action btn-cancel" onclick="confirmCancel(<?= $o['id'] ?>, '<?= $formattedOrderId ?>')">
                                <i class="bi bi-x-lg"></i> Cancel Order
                            </button>
                        <?php endif; ?>
                        
                        <?php if (in_array($status, ['delivered', 'cancelled', 'failed'])): ?>
                            <button type="button" class="btn-action" onclick="confirmDelete(<?= $o['id'] ?>, '<?= $formattedOrderId ?>')">
                                <i class="bi bi-trash3"></i> Remove
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content p-4 text-center rounded-4 border-0 shadow-lg"><div class="mb-3 text-danger"><i class="bi bi-trash3-fill display-4"></i></div><h5 class="fw-bold">Delete History?</h5><p class="small text-muted mb-4">Remove record <strong id="displayDeleteId"></strong>?</p><form method="POST"><input type="hidden" name="delete_order_id" id="hiddenDeleteOrderId"><button type="submit" class="btn btn-danger w-100 rounded-pill mb-2 fw-bold">Yes, Delete</button></form><button class="btn btn-light w-100 rounded-pill fw-bold text-muted" data-bs-dismiss="modal">Keep</button></div></div></div>
<div class="modal fade" id="cancelConfirmModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content p-4 text-center rounded-4 border-0 shadow-lg"><div class="mb-3 text-warning"><i class="bi bi-exclamation-triangle-fill display-4"></i></div><h5 class="fw-bold">Cancel Order?</h5><p class="small text-muted mb-4">Stop order <strong id="displayCancelId"></strong>?</p><form method="POST"><input type="hidden" name="cancel_order_id" id="hiddenCancelOrderId"><button type="submit" class="btn btn-warning w-100 rounded-pill mb-2 fw-bold">Yes, Cancel</button></form><button class="btn btn-light w-100 rounded-pill fw-bold text-muted" data-bs-dismiss="modal">Go Back</button></div></div></div>

<script>
    function confirmDelete(id, formattedId) {
        document.getElementById('displayDeleteId').innerText = formattedId;
        document.getElementById('hiddenDeleteOrderId').value = id;
        new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
    }
    function confirmCancel(id, formattedId) {
        document.getElementById('displayCancelId').innerText = formattedId;
        document.getElementById('hiddenCancelOrderId').value = id;
        new bootstrap.Modal(document.getElementById('cancelConfirmModal')).show();
    }
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
</body>
</html>