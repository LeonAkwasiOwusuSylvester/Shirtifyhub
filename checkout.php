<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Read the hidden .env file safely
$env = parse_ini_file('.env');

// 2. Assign the keys to variables
$paystack_public = $env['PAYSTACK_PUBLIC_KEY'];
$paystack_secret = $env['PAYSTACK_SECRET_KEY'];

require_once __DIR__ . "/config/db.php";

if(file_exists(__DIR__ . "/helpers/mailer.php")) {
    require_once __DIR__ . "/helpers/mailer.php";
}

$isLoggedIn = isset($_SESSION["user_id"]);
$userId     = $_SESSION["user_id"] ?? null;

$items = [];
$total = 0;
$user  = []; 
$has_preorders = false;
$savedCity = '';
$savedRegion = '';

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

// Fetch User Data
if ($isLoggedIn) {
    $stmtUser = $pdo->prepare("SELECT name, email, phone, address, location FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $fetchedUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    if ($fetchedUser) {
        $user = $fetchedUser;
        if (!empty($user['location'])) {
            $locParts = explode(', ', $user['location']);
            $savedCity = trim($locParts[0] ?? '');
            $savedRegion = trim($locParts[1] ?? '');
        }
    }
}

// Fetch Cart Products
$product_ids = array_unique(array_column($_SESSION['cart'], 'product_id'));
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));

$stmt = $pdo->prepare("SELECT id, name, price, discount_price, image, is_preorder FROM products WHERE id IN ($placeholders)");
$stmt->execute(array_values($product_ids));
$db_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products_lookup = [];
foreach ($db_products as $dp) {
    $products_lookup[$dp['id']] = $dp;
}

foreach ($_SESSION['cart'] as $key => $session_item) {
    $pid = $session_item['product_id'];
    
    if(isset($products_lookup[$pid])) {
        $qty = $session_item['quantity'];
        $dbPrice = (float)$products_lookup[$pid]['price'];
        $dbDiscount = (float)$products_lookup[$pid]['discount_price'];
        $active_price = ($dbDiscount > 0) ? $dbDiscount : $dbPrice;
        
        $subtotal = $qty * $active_price;
        $total += $subtotal;
        
        if ($products_lookup[$pid]['is_preorder']) $has_preorders = true;
        
        $items[] = [
            'product_id' => $pid,
            'name' => $products_lookup[$pid]['name'],
            'price' => $active_price,
            'quantity' => $qty,
            'subtotal' => $subtotal,
            'image' => $products_lookup[$pid]['image'],
            'size' => $session_item['size'] ?? 'N/A',
            'color' => $session_item['color'] ?? 'N/A',
            'is_preorder' => $products_lookup[$pid]['is_preorder']
        ];
    }
}

// HANDLE AUTOMATED ORDER SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    
    $customerName = trim($_POST['name'] ?? 'Customer');
    $customerEmail = trim($_POST['email'] ?? '');
    $customerPhone = trim($_POST['phone'] ?? '');
    $customerAddress = trim($_POST['address'] ?? '');
    $customerLocation = trim($_POST['city'] ?? '') . ', ' . trim($_POST['region'] ?? '');
    
    $deliveryMethod = $_POST['delivery_method'] ?? 'standard';
    $paymentChannel = $_POST['payment_channel'] ?? 'mobile_money';
    
    $shippingCost = 0;
    if ($deliveryMethod === 'standard') $shippingCost = 30;
    if ($deliveryMethod === 'express') $shippingCost = 60;
    
    $finalTotal = $total + $shippingCost;

    // 1. VERIFY PAYSTACK PAYMENT
    $paystackRef = trim($_POST['paystack_reference'] ?? '');
    
    if (empty($paystackRef)) {
        die("<div class='alert alert-danger text-center m-5 p-4 rounded-4 fw-bold'>Security Error: Payment Reference missing. Please try again.</div>");
    }

    // Ping Paystack API (Now securely using the .env variable)
    $url = "https://api.paystack.co/transaction/verify/" . rawurlencode($paystackRef);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $paystack_secret"]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    
    if ($result && isset($result['data']['status']) && $result['data']['status'] === 'success') {
        $orderStatus = 'processing'; 
        $notes = "Automated Paystack (" . strtoupper($paymentChannel) . ") | Ref: " . $paystackRef;
    } else {
        die("<div class='alert alert-danger text-center m-5 p-4 rounded-4 fw-bold'>Payment Verification Failed. If you were debited, please contact support immediately with reference: $paystackRef</div>");
    }

    // 2. SAVE ORDER TO DATABASE
    try {
        $pdo->beginTransaction();

        $orderStmt = $pdo->prepare("
            INSERT INTO orders (user_id, total_amount, delivery_method, status, name, email, phone, address, location, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $orderStmt->execute([$userId, $finalTotal, $deliveryMethod, $orderStatus, $customerName, $customerEmail, $customerPhone, $customerAddress, $customerLocation, $notes]);
        $orderDbId = $pdo->lastInsertId();
        $formattedOrderId = "SH-" . str_pad($orderDbId, 6, '0', STR_PAD_LEFT);

        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, selected_size, selected_color) VALUES (?, ?, ?, ?, ?, ?)");
        $fallbackStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        
        foreach ($items as $item) {
            try {
                $itemStmt->execute([$orderDbId, $item['product_id'], $item['quantity'], $item['price'], $item['size'], $item['color']]);
            } catch (PDOException $e) {
                $fallbackStmt->execute([$orderDbId, $item['product_id'], $item['quantity'], $item['price']]);
            }
        }
        
        $pdo->commit();

        // 3. SEND CONFIRMATION EMAIL
        if (!empty($customerEmail) && function_exists('sendMail')) {
            $statusMsg = "<div style='background: #ecfdf5; color: #10b981; padding: 15px; border-radius: 8px;'><strong>Payment Successful!</strong> Your automated payment has been verified. Our logistics team is now preparing your items.</div>";

            $subject = "Payment Received - Order $formattedOrderId";
            $title = "We got your order!";
            $message = "Hello <strong>$customerName</strong>,<br><br>Thank you for shopping at Shirtifyhub. Your payment was successfully processed.<br><br>$statusMsg";
            
            sendMail($customerEmail, $subject, $title, $message, ['text' => 'Track My Order', 'url' => 'http://'.$_SERVER['HTTP_HOST'].'/shirtifyhub/orders.php']);
        }

        unset($_SESSION['cart']);
        header("Location: success.php?order_id=" . $orderDbId); 
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("System Error: Could not save order. " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout | Shirtifyhub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }
        .card-premium { background: #ffffff; border-radius: 20px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); padding: 35px; margin-bottom: 25px; }
        .form-control, .form-select { background-color: #f1f5f9; border: 1px solid transparent; border-radius: 12px; padding: 14px 18px; font-weight: 500; transition: 0.2s; }
        .form-control:focus, .form-select:focus { background-color: #ffffff; border-color: #0f172a; box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.1); }
        .select-option { border: 2px solid #e2e8f0; border-radius: 16px; padding: 20px; cursor: pointer; transition: all 0.2s ease; background: #ffffff; position: relative; height: 100%; display: block; }
        .select-option:hover { border-color: #cbd5e1; }
        .select-option.active-option { border-color: #0f172a; background-color: #f8fafc; }
        .active-option::before { content: '\F26A'; font-family: bootstrap-icons; position: absolute; top: 15px; right: 15px; color: #0f172a; font-size: 1.2rem; }
        .badge-preorder { background-color: rgba(99, 102, 241, 0.1); color: #6366f1; font-size: 0.6rem; font-weight: 800; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; }
        .btn-checkout { background-color: #0f172a; color: white; border-radius: 16px; transition: 0.3s; letter-spacing: 0.5px; padding: 16px; border: none;}
        .btn-checkout:hover { background-color: #1e293b; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); color: white;}
    </style>
</head>
<body>

<?php require_once __DIR__ . "/includes/header.php"; ?>

<main class="container py-4 py-lg-5 mb-5">
    
    <div class="d-flex align-items-center mb-4">
        <a href="cart.php" class="btn btn-light rounded-circle border shadow-sm me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="fw-bold mb-0 text-dark">Secure Checkout</h2>
    </div>

    <?php if(!$isLoggedIn): ?>
        <div class="alert alert-warning small fw-bold d-flex align-items-center mb-4 border-0 shadow-sm" style="border-radius: 12px; background-color: #fffbeb; color: #b45309;">
            <i class="bi bi-exclamation-triangle-fill me-3 fs-5"></i>
            You are checking out as a guest. <a href="login.php" class="alert-link ms-1">Log in</a> to auto-fill your saved details.
        </div>
    <?php endif; ?>

    <form method="POST" action="checkout.php" id="checkoutForm">
        <input type="hidden" name="place_order" value="1">
        <input type="hidden" name="paystack_reference" id="paystack_reference" value="">

        <div class="row g-5">
            <div class="col-lg-7">
                
                <div class="card-premium">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-weight: bold;">1</div>
                        <h5 class="fw-bold mb-0">Delivery Details</h5>
                    </div>
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Full Name</label>
                            <input type="text" name="name" id="cus_name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Email Address</label>
                            <input type="email" name="email" id="cus_email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Street Address / House No.</label>
                            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">City</label>
                            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($savedCity) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Region</label>
                            <select name="region" class="form-select" required>
                                <option value="" disabled <?= empty($savedRegion) ? 'selected' : '' ?>>Select Region</option>
                                <?php
                                $regions = ['Ahafo', 'Ashanti', 'Bono', 'Bono East', 'Central', 'Eastern', 'Greater Accra', 'North East', 'Northern', 'Oti', 'Savannah', 'Upper East', 'Upper West', 'Volta', 'Western', 'Western North'];
                                foreach ($regions as $r) {
                                    $selected = ($savedRegion === $r) ? 'selected' : '';
                                    echo "<option value=\"$r\" $selected>$r</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card-premium">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-weight: bold;">2</div>
                        <h5 class="fw-bold mb-0">Delivery Method</h5>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="select-option active-option">
                                <input type="radio" name="delivery_method" value="standard" class="d-none delivery-radio" checked>
                                <div class="fw-bold text-dark mb-1 fs-6">Standard</div>
                                <div class="small text-muted mb-2">3-5 Business Days</div>
                                <div class="fw-bold text-dark">₵30.00</div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="select-option">
                                <input type="radio" name="delivery_method" value="express" class="d-none delivery-radio">
                                <div class="fw-bold text-dark mb-1 fs-6">Express</div>
                                <div class="small text-muted mb-2">Next Business Day</div>
                                <div class="fw-bold text-dark">₵60.00</div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="select-option">
                                <input type="radio" name="delivery_method" value="pickup" class="d-none delivery-radio">
                                <div class="fw-bold text-dark mb-1 fs-6">Pickup Station</div>
                                <div class="small text-muted mb-2">Collect from Office</div>
                                <div class="fw-bold text-success">Free</div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card-premium">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-weight: bold;">3</div>
                        <h5 class="fw-bold mb-0">Payment Option</h5>
                    </div>

                    <p class="text-muted small mb-4">All transactions are secured and encrypted by Paystack.</p>

                    <label class="select-option mb-3 active-option">
                        <input type="radio" name="payment_channel" value="mobile_money" class="d-none payment-radio" checked>
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-light p-2 rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-phone-vibrate text-dark fs-4"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-6">Mobile Money</div>
                                <div class="small text-muted fw-bold">MTN, Telecel, AT</div>
                            </div>
                        </div>
                    </label>

                    <label class="select-option">
                        <input type="radio" name="payment_channel" value="card" class="d-none payment-radio">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-light p-2 rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-credit-card-2-front text-dark fs-4"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-6">Credit / Debit Card</div>
                                <div class="small text-muted fw-bold">Visa, Mastercard</div>
                            </div>
                        </div>
                    </label>

                </div>
            </div>

            <div class="col-lg-5">
                <div class="card-premium sticky-top" style="top: 100px;">
                    <h5 class="fw-bold mb-4 text-dark">Order Summary</h5>
                    
                    <?php if($has_preorders): ?>
                        <div class="alert border-0 py-2 px-3 mb-4 small fw-bold" style="background-color: #eef2ff; color: #4338ca; border-radius: 8px;">
                            <i class="bi bi-info-circle-fill me-2"></i> Includes Pre-order items
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-4 pe-2" style="max-height: 350px; overflow-y: auto;">
                        <?php foreach ($items as $i): ?>
                            <div class="d-flex align-items-center mb-3">
                                <?php $imgPath = !empty($i['image']) ? 'uploads/' . $i['image'] : 'assets/images/no-image.png'; ?>
                                <img src="<?= htmlspecialchars($imgPath) ?>" alt="" class="rounded bg-light p-1 me-3" style="width: 60px; height: 60px; object-fit: contain;">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem;"><?= htmlspecialchars($i['name']) ?></div>
                                    <div class="text-muted small fw-medium my-1">
                                        <?php if($i['size'] !== 'N/A') echo "Size: " . htmlspecialchars($i['size']); ?>
                                        <?php if($i['size'] !== 'N/A' && $i['color'] !== 'N/A') echo " &bull; "; ?>
                                        <?php if($i['color'] !== 'N/A') echo "Color: " . htmlspecialchars($i['color']); ?>
                                    </div>
                                </div>
                                <div class="fw-bold ms-3" style="font-size: 1rem;">₵<?= number_format($i['subtotal'], 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr class="text-muted opacity-25 mb-4">
                    
                    <div class="d-flex justify-content-between mb-3 text-muted fw-medium small">
                        <span>Subtotal</span>
                        <span class="text-dark">₵<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-4 pb-4 border-bottom text-muted fw-medium small">
                        <span>Delivery Fee</span>
                        <span class="text-dark" id="ui-shipping-cost">₵30.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <span class="fw-bold text-muted text-uppercase small" style="letter-spacing: 1px;">Total to Pay</span>
                        <span class="fw-bold fs-3 text-dark" style="line-height: 1;" id="ui-total-cost">₵<?= number_format($total + 30, 2) ?></span>
                    </div>

                    <button type="submit" class="btn w-100 fw-bold text-uppercase btn-checkout">
                        <i class="bi bi-shield-lock-fill me-2"></i> Pay & Place Order
                    </button>
                    
                    <div class="text-center mt-3 d-flex justify-content-center gap-2 opacity-50">
                        <i class="bi bi-credit-card fs-4"></i>
                        <i class="bi bi-apple fs-4"></i>
                        <i class="bi bi-phone fs-4"></i>
                    </div>
                </div>
            </div>

        </div>
    </form>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

<script src="https://js.paystack.co/v1/inline.js"></script>

<script>
    const subtotal = <?= $total ?>;
    let currentShippingCost = 30.00;

    // Delivery Math Logic
    const deliveryRadios = document.querySelectorAll('.delivery-radio');
    deliveryRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.delivery-radio').forEach(r => r.closest('.select-option').classList.remove('active-option'));
            this.closest('.select-option').classList.add('active-option');
            
            if (this.value === 'standard') currentShippingCost = 30.00;
            else if (this.value === 'express') currentShippingCost = 60.00;
            else if (this.value === 'pickup') currentShippingCost = 0.00;

            document.getElementById('ui-shipping-cost').innerText = currentShippingCost === 0 ? 'Free' : '₵' + currentShippingCost.toFixed(2);
            document.getElementById('ui-total-cost').innerText = '₵' + (subtotal + currentShippingCost).toFixed(2);
        });
    });

    // Payment Option Styling Logic
    const paymentRadios = document.querySelectorAll('.payment-radio');
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-radio').forEach(r => r.closest('.select-option').classList.remove('active-option'));
            this.closest('.select-option').classList.add('active-option');
        });
    });

    // --- PAYSTACK CHECKOUT INTERCEPTION ---
    const checkoutForm = document.getElementById('checkoutForm');
    
    checkoutForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Always stop standard submission first
        
        // HTML5 Form Validation
        if (!checkoutForm.checkValidity()) {
            checkoutForm.reportValidity();
            return;
        }

        let finalAmount = subtotal + currentShippingCost;
        let emailValue = document.getElementById('cus_email').value;
        let selectedChannel = document.querySelector('input[name="payment_channel"]:checked').value;

        let handler = PaystackPop.setup({
            key: '<?= $paystack_public ?>', // Securely injected via PHP
            email: emailValue,
            amount: Math.round(finalAmount * 100), // Paystack needs pesewas
            currency: 'GHS',
            channels: [selectedChannel], // Forces either Mobile Money OR Card based on UI selection
            ref: 'SHP_' + Math.floor((Math.random() * 1000000000) + 1),
            callback: function(response) {
                document.getElementById('paystack_reference').value = response.reference;
                checkoutForm.submit(); // Now we actually submit the form to PHP
            },
            onClose: function() {
                alert('Payment window closed. Order was not placed.');
            }
        });
        
        handler.openIframe();
    });
</script>

</body>
</html>