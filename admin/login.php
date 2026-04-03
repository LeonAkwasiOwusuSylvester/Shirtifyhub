<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../config/db.php";

// 1. Skip login if already active
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

// Auto-create admin if empty
$checkUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($checkUsers == 0) {
    $defaultPass = password_hash('admin123', PASSWORD_DEFAULT); 
    $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')")->execute([$defaultPass]);
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Access denied. Invalid credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | ShirtifyHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --accent: #6366f1; --dark-navy: #0f172a; }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            /* Premium Midnight Background */
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
            color: #fff;
            overflow-x: hidden;
            position: relative;
        }

        /* Subtle Glow Effects */
        body::before {
            content: "";
            position: absolute;
            width: 500px; height: 500px;
            top: -10%; left: -10%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
            z-index: -1;
            filter: blur(80px);
        }

        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* Dark Glassmorphism Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(30px) saturate(120%);
            -webkit-backdrop-filter: blur(30px) saturate(120%);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 3.5rem 2.5rem;
            width: 100%;
            max-width: 410px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.5);
        }

        .brand-logo {
            font-weight: 800;
            font-size: 1.3rem;
            letter-spacing: 1.5px;
            color: #fff;
            text-decoration: none;
            margin-bottom: 0.5rem;
            display: block;
        }

        .badge-secure {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 6px 14px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .form-label { font-weight: 600; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;}

        .input-group-text {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-right: none;
            color: #475569;
            border-radius: 12px 0 0 12px;
            padding-left: 15px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.03);
            color: #fff;
            font-weight: 500;
            transition: 0.2s;
        }
        
        .form-control::placeholder { color: #334155; }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: none;
            color: #fff;
        }

        /* ✅ NEW: Styling for the Eye Icon button so it blends perfectly with the input box */
        .toggle-pw {
            border-radius: 0 12px 12px 0 !important;
            border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-left: none !important;
            cursor: pointer;
            transition: 0.2s;
            padding-right: 15px;
        }
        .toggle-pw:hover {
            color: #fff !important;
        }
        /* Make the eye icon background change when the input is focused */
        .form-control:focus + .toggle-pw {
            border-color: rgba(255, 255, 255, 0.2) !important;
            background: rgba(255, 255, 255, 0.06);
        }

        .btn-login {
            background: #fff;
            color: var(--dark-navy);
            border-radius: 12px;
            padding: 14px;
            font-weight: 800;
            border: none;
            transition: 0.3s;
            margin-top: 1.5rem;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .btn-login:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .footer-link { color: #475569; text-decoration: none; font-size: 0.75rem; font-weight: 600; transition: 0.2s; }
        .footer-link:hover { color: #fff; }

        .admin-footer {
            padding: 2.5rem 0;
            text-align: center;
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            line-height: 1.8;
        }
        .footer-brand { color: rgba(255, 255, 255, 0.4); font-weight: 600; }

        .alert-premium {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            font-size: 0.8rem;
            padding: 12px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="glass-card text-center">
            <a href="../index.php" class="brand-logo">SHIRTIFYHUB</a>
            <div class="badge-secure">
                <i class="bi bi-shield-lock"></i> Secure Terminal
            </div>

            <form action="login.php" method="POST" class="text-start">
                <?php if ($error): ?>
                    <div class="alert alert-premium mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Identifier</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="username" required autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Passkey</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="passkey" class="form-control border-end-0" style="border-top-right-radius: 0; border-bottom-right-radius: 0;" placeholder="••••••••" required>
                        <span class="input-group-text toggle-pw" id="togglePassword" title="Show/Hide Password">
                            <i class="bi bi-eye-slash" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    Authorize Access
                </button>
            </form>
            
            <div class="mt-4">
                <a href="../index.php" class="footer-link">
                    <i class="bi bi-arrow-left me-1"></i> Return to Main Shop
                </a>
            </div>
        </div>
    </div>

    <div class="admin-footer">
        &copy; <?= date("Y") ?> <span class="footer-brand">ShirtifyHub Inc.</span> All rights reserved.<br>
        <span style="opacity: 0.6; font-size: 0.7rem;">SECURE TERMINAL V2.4.0</span>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passkey = document.getElementById('passkey');
        const toggleIcon = document.getElementById('toggleIcon');

        togglePassword.addEventListener('click', function () {
            // Toggle the type attribute
            const type = passkey.getAttribute('type') === 'password' ? 'text' : 'password';
            passkey.setAttribute('type', type);
            
            // Toggle the eye icon class
            toggleIcon.classList.toggle('bi-eye');
            toggleIcon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>