<?php
session_start();
require_once __DIR__ . "/../config/db.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// 1. Get Total Revenue
$revenueStmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'");
$total_revenue = $revenueStmt->fetchColumn() ?: 0;

// 2. Get Total Orders
$ordersStmt = $pdo->query("SELECT COUNT(*) FROM orders");
$total_orders = $ordersStmt->fetchColumn();

// 3. Get Pending/Processing Orders
$pendingStmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing' OR status = 'pending'");
$pending_orders = $pendingStmt->fetchColumn();

// 4. Get Total Products
$productsStmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = $productsStmt->fetchColumn();

// 5. Get the 5 most recent orders
$recentStmt = $pdo->query("SELECT id, name as customer_name, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
$recent_orders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ NEW: 6. Get Pending Support Messages count for the badges
$msgStmt = $pdo->query("SELECT COUNT(*) FROM support_messages WHERE status = 'pending'");
$pending_messages = $msgStmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Overview | ShirtifyHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --admin-dark: #0f172a; 
            --admin-bg: #f8fafc;
            --border-light: #e2e8f0;
            --accent-blue: #6366f1;
        }
        body { 
            background-color: var(--admin-bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #334155;
        }

        /* Top Navbar */
        .admin-nav {
            background: #ffffff;
            border-bottom: 1px solid var(--border-light);
            padding: 1.2rem 0;
        }
        .nav-brand { font-weight: 800; letter-spacing: -0.5px; color: var(--admin-dark); text-decoration: none; font-size: 1.4rem; }

        /* Dashboard Cards */
        .card-premium {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 20px;
            padding: 1.8rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
            height: 100%;
        }
        .card-premium:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
        }

        .icon-shape {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
        }
        .bg-sales { background: #ecfdf5; color: #10b981; }
        .bg-pending { background: #fffbeb; color: #f59e0b; }
        .bg-orders { background: #eef2ff; color: #6366f1; }
        .bg-products { background: #f8fafc; color: #64748b; }

        .stat-label { font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem; }
        .stat-value { font-size: 1.75rem; font-weight: 800; color: var(--admin-dark); letter-spacing: -1px; }

        /* Table Styling */
        .table-premium thead th {
            background: #fcfcfd;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-top: none;
            padding: 1rem;
        }
        .table-premium tbody td { padding: 1.2rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        
        .status-pill {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-processing { background: #e0f2fe; color: #075985; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        /* Quick Action Buttons */
        .action-link {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border: 1px solid var(--border-light);
            border-radius: 12px;
            color: var(--admin-dark);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 0.75rem;
            transition: 0.2s;
        }
        .action-link:hover {
            background: var(--admin-dark);
            color: #ffffff;
            border-color: var(--admin-dark);
        }
        .action-link i { font-size: 1.2rem; margin-right: 1rem; }

        .btn-logout { background: #fee2e2; color: #ef4444; border: none; padding: 8px 20px; font-weight: 700; border-radius: 50px; font-size: 0.85rem; }
        .btn-logout:hover { background: #f87171; color: white; }
        
        /* Message Icon Animation */
        @keyframes pulse-red {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .pulse-badge { animation: pulse-red 2s infinite; }
    </style>
</head>
<body>

    <nav class="admin-nav sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="nav-brand" href="index.php">SHIRTIFYHUB <span class="text-muted fw-light">ADMIN</span></a>
            <div class="d-flex align-items-center gap-3">
                
                <a href="messages.php" class="text-dark position-relative text-decoration-none me-2" title="Support Messages">
                    <i class="bi bi-envelope fs-4"></i>
                    <?php if ($pending_messages > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger pulse-badge" style="font-size: 0.55rem; border: 2px solid white;">
                            <?= $pending_messages ?>
                        </span>
                    <?php endif; ?>
                </a>

                <a href="../index.php" target="_blank" class="btn btn-light border btn-sm px-4 rounded-pill fw-bold text-dark d-none d-sm-inline-flex">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Live Store
                </a>
                
                <a href="change_password.php" class="btn btn-light border btn-sm px-3 rounded-pill fw-bold text-dark d-none d-sm-inline-flex" title="Settings">
                    <i class="bi bi-gear-fill"></i>
                </a>

                <a href="logout.php" class="btn-logout text-decoration-none">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h1 class="fw-bold text-dark mb-1" style="letter-spacing: -1.5px;">Dashboard</h1>
                <p class="text-muted mb-0">Overview of your store's recent activity and sales.</p>
            </div>
            <a href="add_product.php" class="btn btn-dark px-4 py-2 rounded-pill fw-bold shadow-sm">
                <i class="bi bi-plus-lg me-2"></i>New Product
            </a>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card-premium">
                    <div class="icon-shape bg-sales"><i class="bi bi-currency-dollar"></i></div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value">₵<?= number_format($total_revenue, 2) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-premium">
                    <div class="icon-shape bg-pending"><i class="bi bi-clock-history"></i></div>
                    <div class="stat-label">Pending Orders</div>
                    <div class="stat-value"><?= $pending_orders ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-premium">
                    <div class="icon-shape bg-orders"><i class="bi bi-bag-check"></i></div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?= $total_orders ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-premium">
                    <div class="icon-shape bg-products"><i class="bi bi-grid-3x3-gap"></i></div>
                    <div class="stat-label">Live Products</div>
                    <div class="stat-value"><?= $total_products ?></div>
                </div>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-lg-8">
                <div class="card-premium p-0 overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center p-4 border-bottom bg-light bg-opacity-25">
                        <h5 class="fw-bold text-dark m-0">Recent Activity</h5>
                        <a href="orders.php" class="text-decoration-none small fw-bold text-primary">Explore All History <i class="bi bi-arrow-right ms-1"></i></a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-premium m-0">
                            <thead>
                                <tr>
                                    <th>Ref ID</th>
                                    <th>Customer Name</th>
                                    <th>Order Value</th>
                                    <th>Current Status</th>
                                    <th>Placement Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="text-muted small">No orders recorded in the system yet.</div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td class="fw-bold text-dark">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                            <td class="fw-bold text-secondary"><?= htmlspecialchars($order['customer_name'] ?? 'Guest Customer') ?></td>
                                            <td class="fw-bold text-dark">₵<?= number_format($order['total_amount'], 2) ?></td>
                                            <td>
                                                <?php 
                                                    $status = strtolower($order['status']);
                                                    $pillClass = 'status-' . $status;
                                                ?>
                                                <span class="status-pill <?= $pillClass ?>"><?= ucfirst($status) ?></span>
                                            </td>
                                            <td class="text-muted small fw-medium"><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-premium">
                    <h5 class="fw-bold text-dark mb-4 border-bottom pb-3">Quick Navigation</h5>
                    <div class="d-grid">
                        <a href="add_product.php" class="action-link">
                            <i class="bi bi-plus-circle text-primary"></i>
                            <span>Add New Product</span>
                        </a>
                        <a href="orders.php" class="action-link">
                            <i class="bi bi-receipt text-success"></i>
                            <span>Order Management</span>
                        </a>
                        <a href="products.php" class="action-link">
                            <i class="bi bi-boxes text-info"></i>
                            <span>Inventory & Stock</span>
                        </a>
                        <a href="users.php" class="action-link">
                            <i class="bi bi-people text-warning"></i>
                            <span>Customer Accounts</span>
                        </a>
                        <a href="messages.php" class="action-link position-relative">
                            <i class="bi bi-envelope-paper text-danger"></i>
                            <span>Support Tickets</span>
                            <?php if ($pending_messages > 0): ?>
                                <span class="badge bg-danger ms-auto rounded-pill px-2"><?= $pending_messages ?> New</span>
                            <?php endif; ?>
                        </a>
                        <a href="change_password.php" class="action-link">
                            <i class="bi bi-shield-lock text-secondary"></i>
                            <span>Change Password</span>
                        </a>
                    </div>
                    
                    <div class="mt-4 p-3 bg-light rounded-4 border">
                        <div class="small fw-bold text-muted text-uppercase mb-2" style="font-size: 0.65rem;">System Health</div>
                        <div class="d-flex align-items-center gap-2 small text-success fw-bold">
                            <i class="bi bi-check-circle-fill"></i> Database Connected
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>