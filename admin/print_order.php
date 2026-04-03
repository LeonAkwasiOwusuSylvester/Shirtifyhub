<?php
require_once __DIR__ . "/../config/db.php";
session_start();

if (!isset($_SESSION['admin_logged_in'])) { exit('Unauthorized'); }

$orderId = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) { exit('Order not found'); }

$itemStmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Subtotal
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += ($item['price'] * $item['quantity']);
}

// Determine Delivery Fee based on the method
$deliveryFee = 0;
$deliveryMethod = strtolower($order['delivery_method']);
if ($deliveryMethod === 'standard') { $deliveryFee = 30.00; }
elseif ($deliveryMethod === 'express') { $deliveryFee = 60.00; }

// Define the tracking URL for the QR code
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$trackingUrl = $protocol . $_SERVER['HTTP_HOST'] . "/shirtifyhub/orders.php";

// Generate QR Code
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($trackingUrl) . "&margin=0";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Packing Slip - SH-<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            color: #0f172a; 
            margin: 0 auto; 
            padding: 50px; 
            line-height: 1.5;
            max-width: 900px;
        }
        
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
            border-bottom: 2px solid #0f172a; 
            padding-bottom: 30px; 
            margin-bottom: 40px; 
        }
        
        .brand { 
            font-size: 36px; 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 3px; 
            margin-bottom: 5px; 
            color: #0f172a;
        }
        
        .order-meta { 
            font-size: 13px; 
            color: #64748b; 
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .qr-section { 
            text-align: center; 
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        .qr-section img { width: 80px; height: 80px; margin-bottom: 8px; }
        .qr-label { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #0f172a; }

        .info-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        .info-block { width: 45%; }
        .info-title { 
            text-transform: uppercase; 
            font-size: 11px; 
            font-weight: 800; 
            color: #94a3b8; 
            margin-bottom: 10px; 
            letter-spacing: 1px;
        }
        
        .customer-name { font-size: 18px; font-weight: 800; margin-bottom: 5px; }
        .customer-address { font-size: 14px; color: #475569; line-height: 1.6; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { 
            text-align: left; 
            border-bottom: 2px solid #e2e8f0; 
            padding: 12px 5px; 
            font-size: 11px; 
            text-transform: uppercase; 
            letter-spacing: 1px;
            color: #64748b;
        }
        td { 
            padding: 15px 5px; 
            border-bottom: 1px solid #f1f5f9; 
            font-size: 14px; 
            vertical-align: middle;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* The Calculation Section */
        .calculation-box {
            width: 350px;
            margin-left: auto;
            margin-top: 20px;
        }
        .calc-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
            color: #475569;
        }
        .calc-total {
            border-top: 2px solid #0f172a;
            padding-top: 15px;
            margin-top: 10px;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }

        /* The Official Stamp */
        .stamp-container {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .official-stamp {
            display: inline-block;
            border: 3px solid #0f172a;
            color: #0f172a;
            padding: 12px 24px;
            border-radius: 8px;
            transform: rotate(-3deg);
            opacity: 0.85;
            text-align: center;
        }
        .stamp-title { font-weight: 800; font-size: 16px; letter-spacing: 2px; }
        .stamp-subtitle { font-size: 10px; font-weight: 600; letter-spacing: 1px; border-top: 1px solid #0f172a; margin-top: 4px; padding-top: 4px; }

        .footer-note { 
            text-align: right; 
            color: #94a3b8; 
            font-size: 11px; 
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        @media print {
            body { padding: 0; max-width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <div>
            <div class="brand">ShirtifyHub</div>
            <div class="order-meta">
                Official Dispatch Slip &bull; #SH-<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?><br>
                Date: <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
            </div>
        </div>
        
        <div class="qr-section">
            <img src="<?= $qrCodeUrl ?>" alt="QR Code">
            <div class="qr-label">Scan to Track</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-block">
            <div class="info-title">Ship To</div>
            <div class="customer-name"><?= htmlspecialchars($order['name']) ?></div>
            <div class="customer-address">
                <?= nl2br(htmlspecialchars($order['address'])) ?><br>
                <?= htmlspecialchars($order['location']) ?><br>
                <div style="margin-top: 8px;"><strong>T:</strong> <?= htmlspecialchars($order['phone']) ?></div>
            </div>
        </div>
        
        <div class="info-block" style="text-align: right;">
            <div class="info-title">Logistics Details</div>
            <div class="customer-address">
                <strong>Method:</strong> <?= ucfirst($deliveryMethod) ?> Delivery<br>
                <strong>Status:</strong> <?= ucfirst($order['status']) ?><br>
                <strong>Payment:</strong> Verified
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product Description</th>
                <th>Variation</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><strong style="color: #0f172a;"><?= htmlspecialchars($item['name']) ?></strong></td>
                <td style="color: #64748b; font-size: 12px;"><?= $item['selected_size'] ?> / <?= $item['selected_color'] ?></td>
                <td class="text-center fw-bold"><?= $item['quantity'] ?></td>
                <td class="text-right">₵<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="calculation-box">
        <div class="calc-row">
            <span>Item Subtotal</span>
            <span>₵<?= number_format($subtotal, 2) ?></span>
        </div>
        <div class="calc-row">
            <span>Delivery Fee (<?= ucfirst($deliveryMethod) ?>)</span>
            <span>₵<?= number_format($deliveryFee, 2) ?></span>
        </div>
        <div class="calc-row calc-total">
            <span>Total Paid</span>
            <span>₵<?= number_format($order['total_amount'], 2) ?></span>
        </div>
    </div>

    <div class="stamp-container">
        <div class="official-stamp">
            <div class="stamp-title">SHIRTIFYHUB</div>
            <div class="stamp-subtitle">AUTHENTICATED & PAID</div>
        </div>
        
        <div class="footer-note">
            Authentic Imports. Global Heritage.<br>
            Thank you for your business.
        </div>
    </div>

</body>
</html>