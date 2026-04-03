<?php
session_start();
require_once __DIR__ . "/../config/db.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Handle Delete User (Optional)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userIdToDelete = (int)$_POST['user_id'];
    try {
        $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delStmt->execute([$userIdToDelete]);
        $_SESSION['flash_success'] = "Customer account deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = "Cannot delete this user because they have existing orders.";
    }
    header("Location: users.php");
    exit;
}

// ✅ FIXED: Select * and sort by ID so it never crashes if 'created_at' is missing
$search = trim($_GET['search'] ?? '');
$query = "SELECT * FROM users";
$params = [];

if (!empty($search)) {
    $query .= " WHERE name LIKE ? OR email LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Clear flash messages
$success = $_SESSION['flash_success'] ?? null;
$error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Get Pending Messages Count for Navbar
$msgStmt = $pdo->query("SELECT COUNT(*) FROM support_messages WHERE status = 'pending'");
$pending_messages = $msgStmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Accounts | ShirtifyHub Admin</title>
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
        .btn-logout { background: #fee2e2; color: #ef4444; border: none; padding: 8px 20px; font-weight: 700; border-radius: 50px; font-size: 0.85rem; transition: 0.2s; }
        .btn-logout:hover { background: #f87171; color: white; }

        .card-premium {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 20px;
            padding: 1.8rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
        }

        .table-premium thead th {
            background: #fcfcfd;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-top: none;
            padding: 1rem;
            border-bottom: 1px solid var(--border-light);
        }
        .table-premium tbody td { padding: 1.2rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        
        .avatar-circle {
            width: 40px;
            height: 40px;
            background-color: #e0e7ff;
            color: #4338ca;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
        }

        .btn-action { font-size: 0.8rem; font-weight: 600; padding: 6px 12px; border-radius: 8px; transition: 0.2s; border: none; }
        .btn-view { background: #f1f5f9; color: #475569; text-decoration: none; }
        .btn-view:hover { background: #e2e8f0; color: #0f172a; transform: translateY(-2px); }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background: #fca5a5; color: white; transform: translateY(-2px); }
        
        /* Message Icon Animation */
        @keyframes pulse-red {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .pulse-badge { animation: pulse-red 2s infinite; }
        
        .search-box {
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            border: 1px solid #cbd5e1;
            font-size: 0.9rem;
            width: 250px;
        }
        .search-box:focus { outline: none; border-color: var(--admin-dark); box-shadow: 0 0 0 3px rgba(15,23,42,0.1); }
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
                <a href="logout.php" class="btn-logout text-decoration-none">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
            <div>
                <a href="index.php" class="text-decoration-none text-muted small fw-bold mb-2 d-inline-block"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
                <h1 class="fw-bold text-dark mb-1" style="letter-spacing: -1.5px;">Customer Accounts</h1>
                <p class="text-muted mb-0">View and manage all registered users on your store.</p>
            </div>
            
            <form method="GET" action="users.php" class="d-flex gap-2">
                <input type="text" name="search" class="search-box" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-dark rounded-pill px-3"><i class="bi bi-search"></i></button>
                <?php if(!empty($search)): ?>
                    <a href="users.php" class="btn btn-light border rounded-pill px-3">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 alert-dismissible fade show fw-bold">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 alert-dismissible fade show fw-bold">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card-premium p-0 overflow-hidden">
            <?php if (empty($users)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-people text-muted opacity-25 d-block mb-3" style="font-size: 4rem;"></i>
                    <h5 class="fw-bold text-secondary">No Customers Found</h5>
                    <p class="text-muted small">No accounts match your search criteria.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-premium mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Customer Info</th>
                                <th>Contact Details</th>
                                <th>Account Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): 
                                $initial = strtoupper(substr($user['name'] ?? 'U', 0, 1));
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-circle shadow-sm"><?= $initial ?></div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($user['name'] ?? 'Unknown User') ?></div>
                                                <div class="text-muted small" style="font-size: 0.75rem;">ID: #<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-secondary" style="font-size: 0.85rem;"><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($user['email'] ?? 'No email') ?></div>
                                        <?php if(!empty($user['phone'])): ?>
                                            <div class="text-muted small mt-1"><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($user['phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($user['created_at'])): ?>
                                            <div class="text-dark fw-bold" style="font-size: 0.85rem;"><?= date("M d, Y", strtotime($user['created_at'])) ?></div>
                                            <div class="text-muted small">Joined</div>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill">Active Account</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this customer? This cannot be undone.');">
                                            <input type="hidden" name="delete_user" value="1">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn-action btn-delete shadow-sm" title="Delete User">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>