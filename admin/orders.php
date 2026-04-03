<?php
require_once __DIR__ . "/../config/db.php";

// Load mailer helper
if(file_exists(__DIR__ . "/../helpers/mailer.php")) {
    require_once __DIR__ . "/../helpers/mailer.php";
}

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Update order status and trigger email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];

    $updateStmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    
    if ($updateStmt->execute([$newStatus, $orderId])) {
        
        // Fetch order details for the notification
        $stmt = $pdo->prepare("SELECT name, email FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order && function_exists('sendMail')) {
            $customerName = $order['name'];
            $customerEmail = $order['email'];
            $formattedId = "SH-" . str_pad($orderId, 6, '0', STR_PAD_LEFT);
            
            $statusMessages = [
                'processing' => "Great news! Your order is now being processed and packed. We'll let you know when it's ready for delivery.",
                'delivered'  => "Your fresh fits have been delivered! We hope you love them. Tag us in your photos!",
                'cancelled'  => "Your order has been cancelled. If this was a mistake, please contact our support team.",
                'pending'    => "Your order is currently pending payment verification."
            ];

            $messageText = $statusMessages[$newStatus] ?? "Your order status has been updated to: " . ucfirst($newStatus);
            $subject = "Order Update: $formattedId - Shirtifyhub";
            $title = "Order " . ucfirst($newStatus);
            $message = "Hello <strong>$customerName</strong>,<br><br>$messageText<br><br>Order ID: <strong>$formattedId</strong>";
            
            $button = [
                'text' => 'View My Orders',
                'url'  => 'http://' . $_SERVER['HTTP_HOST'] . '/shirtifyhub/orders.php'
            ];

            sendMail($customerEmail, $subject, $title, $message, $button);
        }
    }
    
    header("Location: orders.php?success=1");
    exit;
}

// Fetch all orders with a fresh query
$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | Shirtifyhub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --admin-bg: #f8fafc; --premium-dark: #0f172a; --border-color: #e2e8f0; }
        body { background-color: var(--admin-bg); font-family: 'Inter', system-ui, sans-serif; }
        
        .navbar { background: #ffffff; border-bottom: 1px solid var(--border-color); padding: 1rem 0; }
        .nav-brand { font-weight: 800; letter-spacing: -0.5px; color: var(--premium-dark); text-decoration: none; }
        
        .order-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            overflow: hidden;
            transition: 0.3s ease;
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .order-card:hover { border-color: #cbd5e1; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        
        .order-header {
            background: #fcfcfd;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .status-pill {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-processing { background: #e0f2fe; color: #075985; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .info-label { font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .info-value { color: var(--premium-dark); font-weight: 600; line-height: 1.5; }

        .item-row { padding: 15px 0; border-bottom: 1px solid #f1f5f9; }
        .item-row:last-child { border-bottom: none; }
        
        .payment-note {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px dashed #cbd5e1;
            margin-bottom: 20px;
        }

        .btn-print { 
            background: #f1f5f9; 
            color: #475569; 
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-weight: 700;
            transition: 0.2s;
        }
        .btn-print:hover { background: #e2e8f0; color: var(--premium-dark); }

        .btn-save { 
            background: var(--premium-dark); 
            color: white; 
            border-radius: 10px; 
            font-weight: 700; 
            padding: 10px;
            border: none;
        }
        .btn-save:hover { background: #1e293b; color: white; }

        .img-thumb { width: 50px; height: 50px; border-radius: 8px; background: #f1f5f9; border: 1px solid #e2e8f0; object-fit: cover; }
    </style>
</head>
<body>

    <nav class="navbar sticky-top">
        <div class="container">
            <a class="nav-brand fs-4" href="index.php">ShirtifyHub <span class="text-muted fw-light">Admin</span></a>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-light border btn-sm px-4 rounded-pill fw-bold">Dashboard</a>
                <a href="../index.php" target="_blank" class="btn btn-dark btn-sm px-4 rounded-pill fw-bold">View Store</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h2 class="fw-bold text-dark m-0">Customer Orders</h2>
            <span class="badge bg-white text-dark border px-4 py-2 rounded-pill fw-bold"><?= count($orders) ?> Total Orders</span>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                <div class="fw-bold">Status updated and notification email sent!</div>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5 bg-white rounded-4 border shadow-sm">
                <i class="bi bi-inbox text-muted display-1 mb-3"></i>
                <h4 class="fw-bold text-dark">No orders yet</h4>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php
                    $itemStmt = $pdo->prepare("
                        SELECT oi.*, p.name, p.image 
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?
                    ");
                    $itemStmt->execute([$order['id']]);
                    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                    $status = strtolower($order['status']);
                ?>
                <div class="order-card">
                    <div class="order-header d-flex justify-content-between align-items-center">
                        <div>
                            <div class="info-label mb-0">Order #<?= $order['id'] ?></div>
                            <div class="small text-muted fw-medium"><i class="bi bi-calendar3 me-1"></i> <?= date('M d, Y • h:i A', strtotime($order['created_at'])) ?></div>
                        </div>
                        <span class="status-pill status-<?= $status ?>"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> <?= $status ?></span>
                    </div>

                    <div class="p-4 p-lg-5">
                        <div class="row g-5">
                            <div class="col-md-4 border-end">
                                <div class="mb-4">
                                    <div class="info-label">Customer Info</div>
                                    <div class="info-value"><i class="bi bi-person me-2"></i><?= htmlspecialchars($order['name']) ?></div>
                                    <div class="info-value small text-muted ps-4"><?= htmlspecialchars($order['email']) ?></div>
                                </div>
                                <div class="mb-4">
                                    <div class="info-label">Contact Number</div>
                                    <div class="info-value"><i class="bi bi-telephone me-2"></i><?= htmlspecialchars($order['phone']) ?></div>
                                </div>
                                <div class="mb-0">
                                    <div class="info-label">Shipping Address</div>
                                    <div class="info-value small">
                                        <i class="bi bi-geo-alt me-2"></i><?= nl2br(htmlspecialchars($order['address'])) ?><br>
                                        <span class="ps-4"><?= htmlspecialchars($order['location']) ?></span>
                                    </div>
                                    <div class="mt-3">
                                        <span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.65rem; padding: 5px 10px;">Method: <?= $order['delivery_method'] ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-5 border-end">
                                <div class="info-label mb-3">Order Items</div>
                                <?php foreach ($items as $item): ?>
                                    <div class="item-row d-flex gap-3 align-items-center">
                                        <img src="../uploads/<?= $item['image'] ?>" class="img-thumb" alt="">
                                        <div class="flex-grow-1">
                                            <div class="fw-bold small text-dark"><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)</div>
                                            <div class="text-muted small">
                                                <?= $item['selected_size'] ?> / <?= $item['selected_color'] ?>
                                            </div>
                                        </div>
                                        <div class="fw-bold text-dark small">₵<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="mt-4 p-4 bg-light rounded-4 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-muted small text-uppercase">Total Amount Paid</span>
                                    <span class="fw-bold fs-4 text-dark">₵<?= number_format($order['total_amount'], 2) ?></span>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="info-label">Payment Verification</div>
                                <div class="payment-note">
                                    <div class="small text-dark fw-bold mb-1"><?= !empty($order['notes']) ? htmlspecialchars($order['notes']) : 'No ID provided.' ?></div>
                                    <div class="small text-muted" style="font-size: 0.7rem;">Verify this on your MoMo or Bank app before processing.</div>
                                </div>

                                <div class="info-label">Update Order</div>
                                <a href="print_order.php?id=<?= $order['id'] ?>" target="_blank" class="btn btn-print w-100 mb-3 py-2 btn-sm shadow-sm">
                                    <i class="bi bi-printer me-2"></i> Print Packing Slip
                                </a>

                                <form action="orders.php" method="POST">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <div class="mb-3">
                                        <select name="status" class="form-select form-select-sm border-2 fw-bold" onchange="this.form.submit()">
                                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                            <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="text-center text-muted" style="font-size: 0.65rem;">Changing status automatically sends an update email to the customer.</div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>