<?php
session_start();
require_once __DIR__ . "/config/db.php";

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error   = "";

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name     = trim($_POST['name']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $location = trim($_POST['location']);

    if (empty($name)) {
        $error = "Full Name is required.";
    } else {
        try {
            $sql = "UPDATE users SET name = ?, phone = ?, address = ?, location = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $phone, $address, $location, $user_id]);
            
            $_SESSION['name'] = $name;
            $message = "Profile details updated successfully!";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass     = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?"); 
            $stmt->execute([$user_id]);
            $db_pass = $stmt->fetchColumn();

            if ($db_pass && password_verify($current_pass, $db_pass)) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->execute([$new_hash, $user_id]);
                $message = "Password changed successfully.";
            } else {
                $error = "Current password is incorrect.";
            }
        } catch (PDOException $e) {
            $error = "Password Update Error: " . $e->getMessage();
        }
    }
}

// Fetch Latest User Data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Critical Error: Could not fetch user data.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Shirtifyhub</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public/style.css">

    <style>
        body { background-color: var(--bg-main); }
        
        .page-header {
            background: white;
            padding: 3rem 0 2rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 2.5rem;
        }
        
        .profile-sidebar {
            background: white;
            border-radius: var(--radius-card);
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .user-brief {
            background: var(--brand-primary); 
            color: white; 
            padding: 2rem; 
            text-align: center;
        }
        .user-avatar-lg {
            width: 80px; height: 80px; 
            background: #ffc107; color: var(--brand-primary);
            border-radius: 50%; font-size: 2rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem; border: 4px solid rgba(255,255,255,0.2);
        }
        .sidebar-menu a {
            display: block; padding: 15px 25px; color: var(--text-dark); text-decoration: none;
            font-weight: 500; border-bottom: 1px solid #f1f5f9; transition: 0.2s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #f8fafc; color: var(--brand-primary); border-left: 4px solid var(--brand-primary);
        }
        .sidebar-menu a i { margin-right: 12px; opacity: 0.7; }

        .card-profile {
            background: white; border-radius: var(--radius-card); 
            border: 1px solid var(--border-color); padding: 2.5rem;
            margin-bottom: 2rem; box-shadow: var(--shadow-sm);
        }
        .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.1); }
    </style>
</head>
<body>

<?php require_once __DIR__ . "/includes/header.php"; ?>

<div class="page-header">
    <div class="container">
        <h2 class="fw-bold mb-1" style="color: var(--brand-primary);">Account Settings</h2>
        <p class="text-muted mb-0">Manage your personal details and security preferences.</p>
    </div>
</div>

<div class="container pb-5 mb-5">
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-3 fs-5"></i> 
            <div><?= htmlspecialchars($message) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-3 fs-5"></i> 
            <div><?= htmlspecialchars($error) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-5">
        
        <div class="col-lg-3">
            <div class="profile-sidebar sticky-top" style="top: 100px;">
                <div class="user-brief">
                    <div class="user-avatar-lg">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($user['name']) ?></h5>
                    <small class="opacity-75">Customer Account</small>
                </div>
                <div class="sidebar-menu">
                    <a href="profile.php" class="active"><i class="bi bi-person-gear"></i> Account Details</a>
                    <a href="orders.php"><i class="bi bi-box-seam"></i> My Orders</a>
                    <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            
            <div class="card-profile">
                <h4 class="fw-bold text-dark mb-4 border-bottom pb-3">Personal Information</h4>
                <form method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Full Name</label>
                            <input type="text" name="name" class="form-control form-control-lg bg-light" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Email Address</label>
                            <input type="email" class="form-control form-control-lg bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly title="Contact support to change email">
                            <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i>Email cannot be changed manually.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Phone Number</label>
                            <input type="tel" name="phone" class="form-control form-control-lg bg-light" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">City / Region</label>
                            <input type="text" name="location" class="form-control form-control-lg bg-light" value="<?= htmlspecialchars($user['location'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Default Delivery Address</label>
                            <textarea name="address" class="form-control form-control-lg bg-light" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 mt-4 pt-3 border-top">
                            <button type="submit" name="update_profile" class="btn fw-bold px-4 py-3 shadow-sm rounded-pill" style="background-color: var(--brand-primary); color: white;">
                                <i class="bi bi-floppy me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-profile">
                <h4 class="fw-bold text-dark mb-4 border-bottom pb-3">Security Settings</h4>
                <form method="POST">
                    <div class="row g-4 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Current Password</label>
                            <input type="password" name="current_password" class="form-control form-control-lg bg-light" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">New Password</label>
                            <input type="password" name="new_password" class="form-control form-control-lg bg-light" minlength="6" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control form-control-lg bg-light" minlength="6" required>
                        </div>
                        <div class="col-12 mt-4 pt-3 border-top">
                            <button type="submit" name="change_password" class="btn btn-outline-dark fw-bold px-4 rounded-pill py-2">
                                <i class="bi bi-shield-lock me-2"></i>Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>


</body>
</html>