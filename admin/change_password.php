<?php
session_start();
require_once __DIR__ . "/../config/db.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } else {
        // Fetch current admin data
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();

        // Verify current password
        if ($admin && password_verify($current_password, $admin['password'])) {
            // Hash the new password and update
            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($update->execute([$new_hashed, $admin_id])) {
                $success = "Password successfully updated! You can use it next time you log in.";
            } else {
                $error = "Something went wrong updating your password.";
            }
        } else {
            $error = "Incorrect current password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | ShirtifyHub Admin</title>
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
        .btn-logout { background: #fee2e2; color: #ef4444; border: none; padding: 8px 20px; font-weight: 700; border-radius: 50px; font-size: 0.85rem; transition: 0.2s; text-decoration: none;}
        .btn-logout:hover { background: #f87171; color: white; }

        .card-premium {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .form-label { font-weight: 700; font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;}
        .form-control-custom { border-radius: 12px; padding: 12px 16px; border: 1.5px solid #cbd5e1; font-size: 0.95rem; font-weight: 500; transition: 0.2s;}
        .form-control-custom:focus { border-color: var(--accent-blue); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); outline: none; }

        .btn-save { background: var(--admin-dark); color: #fff; border: none; padding: 14px; font-weight: 700; border-radius: 12px; font-size: 0.95rem; transition: 0.2s; width: 100%; }
        .btn-save:hover { background: #1e293b; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.2); }
    </style>
</head>
<body>

    <nav class="admin-nav sticky-top mb-5">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="nav-brand" href="index.php">SHIRTIFYHUB <span class="text-muted fw-light">ADMIN</span></a>
            <div class="d-flex align-items-center gap-3">
                <a href="index.php" class="btn btn-light border btn-sm px-4 rounded-pill fw-bold text-dark">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-11 col-md-8 col-lg-5">
                
                <div class="text-center mb-4">
                    <div class="bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-shield-lock-fill fs-3"></i>
                    </div>
                    <h2 class="fw-bold text-dark" style="letter-spacing: -0.5px;">Update Password</h2>
                    <p class="text-muted small">Ensure your account is using a long, random password to stay secure.</p>
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

                <div class="card-premium">
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control form-control-custom" placeholder="••••••••" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control form-control-custom" placeholder="At least 8 characters" required minlength="8">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control form-control-custom" placeholder="Type it again" required minlength="8">
                        </div>

                        <button type="submit" class="btn-save mt-2">
                            <i class="bi bi-lock me-2"></i> Secure My Account
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>