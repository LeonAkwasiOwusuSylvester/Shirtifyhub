<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Universal session keys for all flows
$tempUserId = $_SESSION["temp_user_id"] ?? null;

if (empty($tempUserId)) {
    header("Location: login.php");
    exit;
}

$error = "";

// Rate Limiting
if (!isset($_SESSION['otp_attempts'])) {
    $_SESSION['otp_attempts'] = 0;
}

$is_locked = false;
if (isset($_SESSION['otp_lock_time']) && time() < $_SESSION['otp_lock_time']) {
    $is_locked = true;
    $remaining_lock = ceil(($_SESSION['otp_lock_time'] - time()) / 60);
    $error = "Too many attempts. Locked for $remaining_lock more minute(s).";
} elseif (isset($_SESSION['otp_lock_time']) && time() >= $_SESSION['otp_lock_time']) {
    unset($_SESSION['otp_lock_time']);
    $_SESSION['otp_attempts'] = 0;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !$is_locked) {
    $submitted_otp = isset($_POST['otp_code']) ? implode('', $_POST['otp_code']) : "";

    if (!preg_match('/^\d{6}$/', $submitted_otp)) {
        $error = "Please enter all 6 digits.";
    } else {
        $expected_otp = (string)$_SESSION['pending_otp'];

        if ($submitted_otp === $expected_otp) {
            
            // Success! Grab the flow type and User ID
            $flow = $_SESSION['auth_flow'] ?? 'login';
            $userId = $_SESSION['temp_user_id'];
            
            // Clean up temporary sessions
            unset($_SESSION['temp_user_id'], $_SESSION['pending_otp'], $_SESSION['auth_flow'], $_SESSION['otp_attempts'], $_SESSION['otp_lock_time']);
            
            // Route them to the correct destination
            if ($flow === 'reset') {
                $_SESSION['reset_user'] = $userId;
                header("Location: reset-password.php");
            } elseif ($flow === 'register') {
                $_SESSION['user_id'] = $userId;
                header("Location: buyer-info.php");
            } else {
                $_SESSION['user_id'] = $userId;
                header("Location: index.php"); // Or dashboard
            }
            exit;
            
        } else {
            $_SESSION['otp_attempts']++;
            
            if ($_SESSION['otp_attempts'] >= 5) {
                $_SESSION['otp_lock_time'] = time() + (10 * 60);
                $is_locked = true;
                $error = "Too many failed attempts. Locked for 10 minutes.";
            } else {
                $remaining = 5 - $_SESSION['otp_attempts'];
                $error = "Invalid code. $remaining attempt(s) remaining.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account | Shirtifyhub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
        }

        .brand-logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--brand-primary);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Improved Premium OTP Inputs */
        .otp-container { display: flex; gap: 10px; justify-content: center; margin-bottom: 1.5rem; }
        .otp-box {
            width: 48px; height: 58px; 
            text-align: center; font-size: 1.5rem;
            font-weight: 700; color: var(--brand-primary);
            border: 1px solid var(--border-color); border-radius: 12px;
            background: #ffffff; transition: all 0.2s ease;
        }
        .otp-box:focus { 
            border-color: var(--brand-primary); 
            background: #fff; 
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.1); 
            outline: none; 
            transform: translateY(-2px);
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
        .btn-brand:disabled { background-color: #94a3b8; transform: none; box-shadow: none; cursor: not-allowed; }

        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-8px); } 75% { transform: translateX(8px); } }
        .shake { animation: shake 0.4s ease-in-out; }

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
    <div class="auth-card <?= $error ? 'shake' : '' ?>">
        
        <div class="text-center mb-4">
            <div class="brand-logo mb-2">Shirtifyhub</div>
            <h4 class="fw-bold mb-1 text-dark">Security Verification</h4>
            <p class="text-muted small">Enter the 6-digit code sent to your email.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small mb-4 d-flex align-items-center" style="border-radius: 10px; font-weight: 600;">
                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form id="otpForm" method="POST" autocomplete="off">
            
            <div class="otp-container" id="otpInputs">
                <?php for($i=0; $i<6; $i++): ?>
                    <input type="text" name="otp_code[]" class="otp-box" maxlength="1" inputmode="numeric" required <?= $is_locked ? 'disabled' : '' ?>>
                <?php endfor; ?>
            </div>
            
            <button type="submit" class="btn btn-brand mb-4 shadow-sm" <?= $is_locked ? 'disabled' : '' ?>>
                Verify Code
            </button>
        </form>

        <div class="text-center pt-3 border-top small" style="border-color: rgba(15, 23, 42, 0.1) !important;">
            <div class="mb-2" id="timerContainer">
                <span class="text-muted fw-bold">Code expires in</span> 
                <span id="timer" class="fw-bold ms-1" style="color: var(--brand-primary);">05:00</span>
            </div>
            <a href="javascript:void(0)" id="resendBtn" class="d-none fw-bold text-decoration-none" style="color: var(--brand-blue);">
                <i class="bi bi-arrow-clockwise me-1"></i>Resend Code
            </a>
        </div>

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
document.addEventListener('DOMContentLoaded', () => {
    const inputs = document.querySelectorAll('.otp-box');
    const form = document.getElementById('otpForm');
    const resendBtn = document.getElementById('resendBtn');
    const timerSpan = document.getElementById('timer');
    const timerContainer = document.getElementById('timerContainer');
    
    // 5 Minutes (300 Seconds)
    let timeLeft = 300; 
    let countdown;

    const updateTimerDisplay = () => {
        let minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft % 60;
        timerSpan.textContent = 
            (minutes < 10 ? "0" : "") + minutes + ":" + 
            (seconds < 10 ? "0" : "") + seconds;
    };

    const startTimer = () => {
        timeLeft = 300; // Reset to 5 minutes
        updateTimerDisplay();
        timerContainer.classList.remove('d-none');
        resendBtn.classList.add('d-none');
        clearInterval(countdown);
        
        countdown = setInterval(() => {
            timeLeft--;
            updateTimerDisplay();
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerContainer.classList.add('d-none');
                resendBtn.classList.remove('d-none');
            }
        }, 1000);
    };

    startTimer();

    resendBtn.addEventListener('click', async () => {
        const originalHtml = resendBtn.innerHTML;
        resendBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Sending...`;
        resendBtn.style.pointerEvents = 'none';

        setTimeout(() => {
            startTimer();
            resendBtn.innerHTML = originalHtml;
            resendBtn.style.pointerEvents = 'auto';
            alert("New code sent to your email!");
        }, 1500);
    });

    inputs.forEach((input, index) => {
        input.addEventListener('click', () => { input.select(); });

        input.addEventListener('input', (e) => {
            if (e.inputType === "deleteContentBackward") return;
            if (input.value && index < inputs.length - 1) inputs[index + 1].focus();
            if (Array.from(inputs).every(i => i.value !== "")) form.submit();
        });
        
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && index > 0) inputs[index - 1].focus();
        });
    });

    inputs[0].addEventListener('paste', (e) => {
        e.preventDefault();
        const data = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6).split('');
        data.forEach((char, i) => { if (inputs[i]) inputs[i].value = char; });
        if (data.length === 6) form.submit();
    });
});
</script> 
</body>
</html>