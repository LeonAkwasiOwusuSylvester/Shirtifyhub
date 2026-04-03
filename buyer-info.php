<?php
session_start();
require_once __DIR__ . "/config/db.php";

// Guard: User must be logged in (passed through register -> verify-otp -> here)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$error = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $region   = trim($_POST['region'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    if (empty($fullName) || empty($phone) || empty($region) || empty($city) || empty($address)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            // Combine City and Region into the 'location' column
            $locationString = $city . ", " . $region;

            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, phone = ?, location = ?, address = ? 
                WHERE id = ?
            ");
            
            if ($updateStmt->execute([$fullName, $phone, $locationString, $address, $userId])) {
                $_SESSION['name'] = $fullName; 
                header("Location: index.php");
                exit;
            } else {
                $error = "Could not save your information. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile | Shirtifyhub</title>
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
            max-width: 580px; /* Made wider to fit the grid perfectly */
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

        .custom-input-group.align-items-start .icon-box {
            padding-top: 14px;
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

        .custom-input-group .form-control,
        .custom-input-group .form-select {
            border: none;
            background-color: transparent !important;
            padding: 14px 16px 14px 0;
            font-weight: 500;
            box-shadow: none;
            width: 100%;
        }

        /* Adjust select padding to align text with inputs */
        .custom-input-group .form-select {
            padding-left: 0;
            color: var(--text-dark);
            cursor: pointer;
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

<div class="auth-container">
    <div class="auth-card">
        
        <div class="text-center mb-4">
            <div class="brand-logo mb-2">Shirtifyhub</div>
            <h4 class="fw-bold mb-1 text-dark">Delivery Details</h4>
            <p class="text-muted small">Where should we send your fresh fits?</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger py-2 small mb-4 d-flex align-items-center" style="border-radius: 10px; font-weight: 600;">
                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="buyer-info.php" method="POST">
            <div class="row g-3">
                
                <div class="col-12">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Full Name</label>
                    <div class="custom-input-group">
                        <span class="icon-box"><i class="bi bi-person"></i></span>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Kwesi Mensah" required autofocus>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Phone Number</label>
                    <div class="custom-input-group">
                        <span class="icon-box"><i class="bi bi-telephone"></i></span>
                        <input type="tel" name="phone" class="form-control" placeholder="050 000 0000" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Region</label>
                    <div class="custom-input-group">
                        <span class="icon-box"><i class="bi bi-map"></i></span>
                        <select name="region" class="form-select border-0" required>
                            <option value="" disabled selected>Select Region</option>
                            <option value="Ahafo">Ahafo</option>
                            <option value="Ashanti">Ashanti</option>
                            <option value="Bono">Bono</option>
                            <option value="Bono East">Bono East</option>
                            <option value="Central">Central</option>
                            <option value="Eastern">Eastern</option>
                            <option value="Greater Accra">Greater Accra</option>
                            <option value="North East">North East</option>
                            <option value="Northern">Northern</option>
                            <option value="Oti">Oti</option>
                            <option value="Savannah">Savannah</option>
                            <option value="Upper East">Upper East</option>
                            <option value="Upper West">Upper West</option>
                            <option value="Volta">Volta</option>
                            <option value="Western">Western</option>
                            <option value="Western North">Western North</option>
                        </select>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">City / Town</label>
                    <div class="custom-input-group">
                        <span class="icon-box"><i class="bi bi-buildings"></i></span>
                        <input type="text" name="city" class="form-control" placeholder="e.g. Kumasi" required>
                    </div>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Residential Address</label>
                    <div class="custom-input-group align-items-start">
                        <span class="icon-box"><i class="bi bi-geo-alt"></i></span>
                        <textarea name="address" class="form-control" rows="2" placeholder="House No / Street Name / Landmark" required></textarea>
                    </div>
                </div>

            </div>

            <button type="submit" class="btn btn-brand mt-2">Save & Start Shopping</button>
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