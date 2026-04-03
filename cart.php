<?php
session_start();
require_once __DIR__ . "/config/db.php";

// Initialize the cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $size = isset($_POST['size']) ? trim($_POST['size']) : 'N/A';
    $color = isset($_POST['color']) ? trim($_POST['color']) : 'N/A';

    $cart_key = $product_id . '-' . $size . '-' . $color;

    if ($product_id > 0 && $quantity > 0) {
        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$cart_key] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'size' => $size,
                'color' => $color
            ];
        }
    }
    header("Location: cart.php");
    exit;
}

// Handle Update Quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $cart_key = $_POST['cart_key'];
    $new_qty = (int)$_POST['quantity'];
    
    if (isset($_SESSION['cart'][$cart_key])) {
        if ($new_qty > 0) {
            $_SESSION['cart'][$cart_key]['quantity'] = $new_qty;
        } else {
            unset($_SESSION['cart'][$cart_key]);
        }
    }
    header("Location: cart.php");
    exit;
}

// Handle Remove Item
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['key'])) {
    $remove_key = $_GET['key'];
    unset($_SESSION['cart'][$remove_key]);
    header("Location: cart.php");
    exit;
}

// Fetch the actual product details for the items in the cart
$cart_items = [];
$cart_total = 0;
$has_preorders = false; // Flag to show a global pre-order warning at checkout

if (!empty($_SESSION['cart'])) {
    $product_ids = array_unique(array_column($_SESSION['cart'], 'product_id'));
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    // UPDATED QUERY: Fetching discount_price and is_preorder
    $stmt = $pdo->prepare("
        SELECT id as product_id, name, price, discount_price, image, is_preorder 
        FROM products 
        WHERE id IN ($placeholders)
    ");
    $stmt->execute(array_values($product_ids));
    $db_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $products = [];
    foreach ($db_products as $dp) {
        $products[$dp['product_id']] = $dp;
    }

    foreach ($_SESSION['cart'] as $key => $session_item) {
        $pid = $session_item['product_id'];
        
        if(isset($products[$pid])) {
            $qty = $session_item['quantity'];
            
            // LOGIC FIX: Determine the actual active price (use discount if > 0)
            $dbPrice = (float)$products[$pid]['price'];
            $dbDiscount = (float)$products[$pid]['discount_price'];
            $active_price = ($dbDiscount > 0) ? $dbDiscount : $dbPrice;
            
            $subtotal = $qty * $active_price;
            $cart_total += $subtotal;
            
            if ($products[$pid]['is_preorder']) {
                $has_preorders = true;
            }
            
            $item_data = $products[$pid];
            $item_data['cart_key'] = $key;
            $item_data['quantity'] = $qty;
            $item_data['active_price'] = $active_price; // Store the calculated price
            $item_data['size'] = $session_item['size'] ?? 'N/A';
            $item_data['color'] = $session_item['color'] ?? 'N/A';
            $item_data['subtotal'] = $subtotal;
            
            $cart_items[] = $item_data;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Bag | Shirtifyhub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public/style.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        
        .card-premium {
            background: #ffffff;
            border-radius: 20px;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }

        .img-container {
            background: #f1f5f9;
            border-radius: 12px;
            height: 100px;
            width: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .img-container img {
            max-height: 100%;
            object-fit: cover;
        }

        .qty-controls {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .qty-btn {
            background: transparent;
            border: none;
            color: #475569;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
        }
        
        .qty-btn:hover { background: #e2e8f0; color: #0f172a; }
        
        .qty-input {
            width: 35px;
            text-align: center;
            border: none;
            background: transparent;
            font-weight: 600;
            color: #0f172a;
            padding: 0;
        }
        
        .qty-input:focus { outline: none; }

        .btn-remove {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #fee2e2;
            color: #ef4444;
            transition: 0.2s;
            text-decoration: none;
        }

        .btn-remove:hover {
            background: #f87171;
            color: #ffffff;
        }

        .badge-preorder {
            background-color: rgba(99, 102, 241, 0.1);
            color: #6366f1;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cart-item-row:last-child {
            border-bottom: none !important;
            padding-bottom: 0 !important;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . "/includes/header.php"; ?>

<main class="container py-5 mb-5" style="min-height: 70vh;">
    
    <div class="d-flex align-items-center mb-5">
        <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -1px;">Shopping Bag</h2>
        <span class="badge bg-secondary ms-3 rounded-pill"><?= count($cart_items) ?> Items</span>
    </div>

    <?php if (empty($cart_items)): ?>
        <div class="text-center py-5 card-premium">
            <div class="mb-4 d-inline-flex bg-light rounded-circle align-items-center justify-content-center" style="width: 100px; height: 100px;">
                <i class="bi bi-bag text-muted fs-1"></i>
            </div>
            <h4 class="fw-bold text-dark">Your bag is empty</h4>
            <p class="text-muted mb-4">Discover the latest drops and elevate your style.</p>
            <a href="index.php" class="btn btn-dark px-5 py-3 rounded-pill fw-bold">Explore Collections</a>
        </div>
    <?php else: ?>
        <div class="row g-5">
            
            <div class="col-lg-8">
                <div class="card-premium p-4 p-md-5">
                    
                    <?php if($has_preorders): ?>
                        <div class="alert alert-info d-flex align-items-center mb-4 border-0" style="background-color: #eef2ff; color: #4338ca; border-radius: 12px;">
                            <i class="bi bi-info-circle-fill me-3 fs-5"></i>
                            <div class="small fw-medium">Your bag contains pre-order items. These will be shipped as soon as they arrive in stock.</div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item-row d-flex flex-column flex-sm-row align-items-start align-items-sm-center border-bottom pb-4 mb-4 gap-3">
                            
                            <?php $imgPath = !empty($item['image']) ? 'uploads/' . $item['image'] : 'assets/images/no-image.png'; ?>
                            <div class="img-container">
                                <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            </div>
                            
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1 fs-5"><?= htmlspecialchars($item['name']) ?></h6>
                                        <div class="d-flex gap-2 align-items-center mb-2">
                                            <?php if($item['is_preorder']): ?>
                                                <span class="badge-preorder">Pre-order</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small fw-medium mb-3">
                                            <?php if($item['size'] !== 'N/A') echo "Size: " . htmlspecialchars($item['size']); ?>
                                            <?php if($item['size'] !== 'N/A' && $item['color'] !== 'N/A') echo " &bull; "; ?>
                                            <?php if($item['color'] !== 'N/A') echo "Color: " . htmlspecialchars($item['color']); ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-dark fs-5">₵ <?= number_format($item['active_price'], 2) ?></div>
                                        <?php if($item['discount_price'] > 0): ?>
                                            <div class="text-muted text-decoration-line-through small">₵ <?= number_format($item['price'], 2) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <form action="cart.php" method="POST" class="qty-controls">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_key" value="<?= $item['cart_key'] ?>">
                                        <button type="button" class="qty-btn" onclick="this.parentNode.querySelector('input[type=number]').stepDown(); this.parentNode.submit();"><i class="bi bi-dash"></i></button>
                                        <input type="number" name="quantity" class="qty-input" value="<?= $item['quantity'] ?>" min="1" readonly>
                                        <button type="button" class="qty-btn" onclick="this.parentNode.querySelector('input[type=number]').stepUp(); this.parentNode.submit();"><i class="bi bi-plus"></i></button>
                                    </form>

                                    <a href="cart.php?action=remove&key=<?= $item['cart_key'] ?>" class="btn-remove shadow-sm">
                                        <i class="bi bi-trash3-fill"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-premium p-4 sticky-top" style="top: 100px;">
                    <h5 class="fw-bold text-dark mb-4">Order Summary</h5>
                    
                    <div class="d-flex justify-content-between mb-3 text-muted fw-medium small">
                        <span>Subtotal</span>
                        <span class="text-dark">₵ <?= number_format($cart_total, 2) ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-4 pb-4 border-bottom text-muted fw-medium small">
                        <span>Delivery & Handling</span>
                        <span class="text-dark">Calculated at checkout</span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <span class="fw-bold text-muted text-uppercase tracking-wider small">Estimated Total</span>
                        <span class="fw-bold fs-3 text-dark" style="line-height: 1;">₵ <?= number_format($cart_total, 2) ?></span>
                    </div>

                    <a href="checkout.php" class="btn btn-dark w-100 py-3 mb-3 rounded-pill fw-bold text-uppercase" style="letter-spacing: 1px;">Secure Checkout</a>
                    <a href="index.php" class="btn btn-outline-secondary w-100 py-3 rounded-pill fw-bold">Continue Shopping</a>
                </div>
            </div>

        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

</body>
</html>