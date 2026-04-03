<?php
// 1. Start session correctly so we can verify the user passed the OTP stage
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Accra');

require_once __DIR__ . "/config/db.php";
// 🛑 REMOVED config/session.php because it kicks unauthenticated users to login!

// Fallback if mailer doesn't exist yet
if(file_exists(__DIR__ . "/helpers/mailer.php")) {
    require_once __DIR__ . "/helpers/mailer.php";
}

// 2. Check if they successfully passed the OTP verification
if (empty($_SESSION["reset_user"])) {
    header("Location: forgot-password.php");
    exit;
}

$resetUser = $_SESSION["reset_user"];
$error = "";
$showSuccess = false; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"] ?? "";
    $confirm  = $_POST["confirm_password"] ?? "";

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Fetch user email (safely removed 'name' column check to prevent DB errors)
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$resetUser]);
            $userData = $stmt->fetch();
            
            $recipientName = explode('@', $userData['email'])[0]; // Create a fallback name from email
            $userEmail = $userData['email'];

            // Update the password
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            // Also checking if your DB column is password or password_hash. Based on your register.php, it's 'password'.
            $update->execute([$hash, $resetUser]);

            // Send confirmation email
            if (function_exists('sendMail')) {
                $subject = "Password Changed - Shirtifyhub";
                $title   = "Security Update";
                $message = "Hello <strong>$recipientName</strong>,<br>Your Shirtifyhub account password was successfully changed. If you did not perform this change, please contact our support team immediately.";
                
                $button = [
                    'text' => 'Login Now',
                    'url'  => 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/login.php'
                ];

                sendMail($userEmail, $subject, $title, $message, $button);
            }

            // Cleanup and trigger animation
            unset($_SESSION["reset_user"], $_SESSION["reset_email"]);
            $_SESSION["flash_success"] = "Password updated successfully!";
            $showSuccess = true; 

        } catch (PDOException $e) {
            $error = "System Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | Shirtifyhub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public/style.css">
    <style>
        body { 
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: url('https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat fixed;
            font-family: 'Inter', sans-serif;
        }

        .bg-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.75) 0%, rgba(15, 23, 42, 0.95) 100%);
            z-index: -1;
        }
        
        .back-link {
            position: absolute;
            top: 25px;
            left: 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: 0.2s;
            z-index: 10;
            padding: 8px 16px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .back-link:hover { color: #fff; background: rgba(255, 255, 255, 0.2); }

        .auth-container { 
            flex: 1; 
            width: 100%; 
            padding: 80px 20px; 
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }
        
        .auth-card { 
            background: #f8fafc; 
            border-radius: 24px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
            padding: 45px 40px; 
            width: 100%; 
            max-width: 440px; 
            position: relative;
            overflow: hidden;
        }

        .brand-logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--brand-primary);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .custom-input-group {
            display: flex;
            align-items: center;
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            transition: 0.2s ease;
        }

        .custom-input-group:focus-within {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.1);
        }

        .custom-input-group .icon-box {
            padding: 0 16px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-input-group:focus-within .icon-box,
        .custom-input-group:focus-within .password-toggle {
            color: var(--brand-primary);
        }

        .custom-input-group .form-control {
            border: none;
            background-color: transparent !important;
            padding: 14px 16px 14px 0;
            font-weight: 500;
            box-shadow: none;
            width: 100%;
        }

        input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 50px white inset !important;
            -webkit-text-fill-color: var(--text-dark) !important;
        }

        .password-toggle {
            cursor: pointer;
            padding: 0 16px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-brand { 
            background-color: var(--brand-primary); 
            color: white; 
            border-radius: 12px; 
            padding: 15px; 
            font-weight: 700; 
            font-size: 1.05rem;
            border: none; 
            transition: all 0.3s ease; 
            width: 100%; 
        }
        .btn-brand:hover { 
            background-color: #1e293b; 
            color: white; 
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
        }

        /* Success Overlay Animations */
        #successOverlay {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: #f8fafc; display: none;
            flex-direction: column; align-items: center; justify-content: center; z-index: 10;
            animation: fadeIn 0.4s ease;
        }
        .check-icon { font-size: 4rem; color: #10b981; animation: scaleUp 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes scaleUp { from { transform: scale(0); } to { transform: scale(1); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .auth-footer {
            width: 100%;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
            padding: 20px 0;
            z-index: 10;
            position: relative;
        }
        .auth-footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            margin: 0 10px;
            transition: 0.3s;
        }
        .auth-footer a:hover { color: #ffffff; }
    </style>
</head>
<body>

<div class="bg-overlay"></div>

<a href="login.php" class="back-link"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>

<div class="auth-container">
    <div class="auth-card">
        
        <div id="successOverlay" style="<?= $showSuccess ? 'display: flex;' : '' ?>">
            <i class="bi bi-check-circle-fill check-icon mb-3"></i>
            <h4 class="fw-bold text-dark">Password Updated!</h4>
            <p class="text-muted small">Redirecting you to login...</p>
        </div>

        <div class="text-center mb-4">
            <div class="brand-logo mb-2">Shirtifyhub</div>
            <h4 class="fw-bold mb-1 text-dark">Create New Password</h4>
            <p class="text-muted small">Your new password must be at least 8 characters.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small mb-4 d-flex align-items-center" style="border-radius: 10px; font-weight: 600;">
                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">New Password</label>
                <div class="custom-input-group">
                    <span class="icon-box"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="passwordInput1" class="form-control" placeholder="••••••••" required autofocus>
                    <span class="password-toggle" onclick="togglePassword('passwordInput1', 'toggleIcon1')">
                        <i class="bi bi-eye-slash" id="toggleIcon1"></i>
                    </span>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Confirm Password</label>
                <div class="custom-input-group">
                    <span class="icon-box"><i class="bi bi-shield-lock"></i></span>
                    <input type="password" name="confirm_password" id="passwordInput2" class="form-control" placeholder="••••••••" required>
                    <span class="password-toggle" onclick="togglePassword('passwordInput2', 'toggleIcon2')">
                        <i class="bi bi-eye-slash" id="toggleIcon2"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-brand mb-2">Reset Password</button>
            
        </form>
    </div>
</div>

<footer class="auth-footer">
    <div class="mb-2">
        <a href="#">Terms of Service</a> &bull; 
        <a href="#">Privacy Policy</a> &bull; 
        <a href="#">Contact Support</a>
    </div>
    <div>&copy; <?= date('Y') ?> Shirtifyhub. All rights reserved.</div>
</footer>

<script>
    function togglePassword(inputId, iconId) {
        const passInput = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (passInput.type === 'password') {
            passInput.type = 'text';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        } else {
            passInput.type = 'password';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        }
    }

    <?php if($showSuccess): ?>
    setTimeout(() => {
        window.location.href = 'login.php';
    }, 2500);
    <?php endif; ?>
</script>

</body>
</html>