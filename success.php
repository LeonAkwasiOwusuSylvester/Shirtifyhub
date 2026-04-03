<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/config/db.php";

// If the checkout page passed an order ID, use it. Otherwise, generate a placeholder for the UI.
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : random_int(100000, 999999);
$formattedOrderId = "SH-" . str_pad($orderId, 6, '0', STR_PAD_LEFT);

// Fetch the user's name if logged in
$userName = $_SESSION['name'] ?? 'Customer';
if (empty($_SESSION['name']) && isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userName = $stmt->fetchColumn() ?: 'Customer';
    } catch (PDOException $e) {
        $userName = 'Customer';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed | Shirtifyhub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            background-color: #f8fafc; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .success-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            position: relative;
            z-index: 2;
        }

        .card-premium {
            background: #ffffff;
            border-radius: 24px;
            border: none;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
            padding: 50px 40px;
            max-width: 550px;
            width: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .success-icon-box {
            width: 90px;
            height: 90px;
            background: #10B981;
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 0 0 0 10px rgba(16, 185, 129, 0.15);
            animation: popIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .success-icon-box i { font-size: 2.8rem; }
        
        @keyframes popIn { 
            0% { transform: scale(0); opacity: 0; } 
            100% { transform: scale(1); opacity: 1; } 
        }

        .order-badge {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 800;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }

        .info-box {
            background-color: #ecfdf5;
            border: 1px dashed #10b981;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .btn-brand { 
            background-color: #0f172a; 
            color: white; 
            border-radius: 16px; 
            padding: 16px; 
            font-weight: 800; 
            font-size: 1rem;
            border: none; 
            transition: all 0.3s ease; 
            width: 100%;
            display: block;
            text-decoration: none;
            letter-spacing: 0.5px;
        }
        
        .btn-brand:hover { 
            background-color: #1e293b; 
            color: white; 
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.2);
        }

        .btn-outline {
            background-color: transparent;
            color: #475569;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px;
            font-weight: 800;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
            display: block;
            text-decoration: none;
            margin-top: 15px;
        }

        .btn-outline:hover {
            border-color: #cbd5e1;
            background-color: #f1f5f9;
            color: #0f172a;
        }

        #confetti-canvas { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            pointer-events: none; 
            z-index: 9999; 
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . "/includes/header.php"; ?>

<canvas id="confetti-canvas"></canvas>

<div class="success-container">
    <div class="card-premium">
        
        <div class="success-icon-box">
            <i class="bi bi-check-lg"></i>
        </div>
        
        <h2 class="fw-bold text-dark mb-2" style="letter-spacing: -1px;">Order Confirmed!</h2>
        <p class="text-muted mb-3 fw-medium">Thank you for your purchase, <?= htmlspecialchars($userName) ?>.</p>
        
        <div class="order-badge">
            ORDER ID: <?= $formattedOrderId ?>
        </div>

        <div class="info-box d-flex gap-3 align-items-start">
            <i class="bi bi-shield-check-fill text-success fs-4"></i>
            <div>
                <h6 class="fw-bold text-dark mb-1">Payment Successfully Verified</h6>
                <p class="text-muted small mb-0 lh-lg fw-medium">
                    Your transaction has been securely processed. Our logistics team is now preparing your items for dispatch. You will receive an email update once your order is on the move.
                </p>
            </div>
        </div>

        <div class="d-flex flex-column gap-2 mt-2">
            <a href="orders.php" class="btn-brand">Track My Order</a>
            <a href="index.php" class="btn-outline">Continue Shopping</a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script>
    window.addEventListener('load', () => {
        var duration = 4 * 1000;
        var animationEnd = Date.now() + duration;
        var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

        function randomInRange(min, max) { return Math.random() * (max - min) + min; }

        var interval = setInterval(function() {
            var timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) { return clearInterval(interval); }
            var particleCount = 50 * (timeLeft / duration);
            
            confetti(Object.assign({}, defaults, { 
                particleCount, 
                origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 },
                colors: ['#0f172a', '#10B981', '#6366f1', '#f1f5f9'] // Matches Shirtifyhub branding
            }));
            
            confetti(Object.assign({}, defaults, { 
                particleCount, 
                origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 },
                colors: ['#0f172a', '#10B981', '#6366f1', '#f1f5f9']
            }));
        }, 250);
    });
</script>

</body>
</html>