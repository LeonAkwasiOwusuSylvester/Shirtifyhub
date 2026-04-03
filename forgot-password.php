<?php
session_start();
require_once __DIR__ . "/config/db.php";

// Fallback if mailer doesn't exist yet
if(file_exists(__DIR__ . "/helpers/mailer.php")) {
    require_once __DIR__ . "/helpers/mailer.php";
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate a secure 6-digit OTP
                $otp = random_int(100000, 999999);
                
                // Save to session to verify on the next page
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['reset_email'] = $email;
                $_SESSION['temp_reset_user_id'] = $user['id']; // Stored temporarily until OTP is verified

                // Send email if mailer exists
                if (function_exists('sendMail')) {
                    $recipientName = !empty($user['name']) ? $user['name'] : 'Customer';
                    $subject = "Password Reset Code - Shirtifyhub";
                    $title   = "Reset Your Password";
                    $message = "Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,<br><br>You requested to reset your password. Here is your 6-digit verification code:<br><br><h2 style='text-align:center; letter-spacing: 5px; color: #0f172a; padding: 20px; background: #f1f5f9; border-radius: 10px;'>" . $otp . "</h2><br>If you did not request this, please ignore this email to keep your account secure.";
                    
                    sendMail($email, $subject, $title, $message, []);
                }

                // Redirect to OTP verification page
                header("Location: verify-otp.php");
                exit;
            } else {
                // Generic error for security (prevents email enumeration)
                $error = "If this email exists, a reset code will be sent.";
            }
        } catch (PDOException $e) {
            $error = "System Error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Shirtifyhub</title>
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
            max-width: 420px; 
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

        .custom-input-group:focus-within .icon-box {
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
        <div class="text-center mb-4">
            <div class="brand-logo mb-2">Shirtifyhub</div>
            <h4 class="fw-bold mb-1 text-dark">Forgot Password?</h4>
            <p class="text-muted small">Enter your email address and we'll send you a 6-digit recovery code.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-warning py-2 small mb-4 d-flex align-items-center" style="border-radius: 10px; font-weight: 600;">
                <i class="bi bi-info-circle-fill me-2 fs-5"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="forgot-password.php" method="POST">
            
            <div class="mb-4">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Email Address</label>
                <div class="custom-input-group">
                    <span class="icon-box"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="example@gmail.com" required autofocus>
                </div>
            </div>

            <button type="submit" class="btn btn-brand mb-3">Send Reset Code</button>
            
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

</body>
</html> 