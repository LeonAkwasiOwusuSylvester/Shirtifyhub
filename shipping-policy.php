<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/config/db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping & Logistics | Shirtifyhub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --brand-primary: #0f172a; 
            --accent-indigo: #6366f1;
            --bg-soft: #f8fafc;
        }
        
        body { 
            background-color: #ffffff; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #334155;
        }

        .hero-section {
            background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)), 
                        url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            padding: 100px 0;
            color: white;
            text-align: center;
        }

        .shipping-card {
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 40px;
            height: 100%;
            transition: all 0.3s ease;
            background: white;
        }

        .shipping-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
            border-color: var(--accent-indigo);
        }

        .icon-box {
            width: 60px;
            height: 60px;
            background: #f1f5f9;
            color: var(--brand-primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 25px;
        }

        .shipping-card:hover .icon-box {
            background: var(--brand-primary);
            color: white;
        }

        .method-badge {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 6px 12px;
            border-radius: 50px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--accent-indigo);
            margin-bottom: 15px;
            display: inline-block;
        }

        .timeline-box {
            border-left: 2px dashed #e2e8f0;
            padding-left: 30px;
            position: relative;
            margin-left: 10px;
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: -37px;
            top: 5px;
            width: 12px;
            height: 12px;
            background: var(--accent-indigo);
            border-radius: 50%;
        }

        .premium-table {
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .premium-table thead {
            background: var(--brand-primary);
            color: white;
        }
        .premium-table th, .premium-table td {
            padding: 20px;
            vertical-align: middle;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . "/includes/header.php"; ?>

<section class="hero-section">
    <div class="container">
        <h1 class="display-4 fw-800 mb-3">Global Logistics</h1>
        <p class="lead opacity-75 mx-auto" style="max-width: 600px;">
            From the streets of London and New York to your doorstep in Ghana. Modern fashion, handled with care.
        </p>
    </div>
</section>

<main class="container py-5 my-5">
    
    <div class="text-center mb-5">
        <span class="method-badge">How we deliver</span>
        <h2 class="fw-bold text-dark">Flexible Shipping Options</h2>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="shipping-card">
                <div class="icon-box">
                    <i class="bi bi-lightning-charge-fill"></i>
                </div>
                <h4 class="fw-bold">In-Stock (Ghana)</h4>
                <p class="text-muted small mb-4">Products currently stored in our Kumasi hub are ready for immediate dispatch.</p>
                <ul class="list-unstyled small fw-medium">
                    <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i> Kumasi: Same-day delivery</li>
                    <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i> Accra: 24 - 48 Hours</li>
                    <li><i class="bi bi-check2-circle text-success me-2"></i> Nationwide: 2-3 Business Days</li>
                </ul>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="shipping-card">
                <div class="icon-box">
                    <i class="bi bi-airplane-fill"></i>
                </div>
                <h4 class="fw-bold">Global Air Freight</h4>
                <p class="text-muted small mb-4">The fastest way to get exclusive pre-order items from the UK, US, or Europe.</p>
                <div class="timeline-box small">
                    <div class="timeline-item mb-3">
                        <div class="fw-bold text-dark">Procurement</div>
                        <div class="text-muted">1-3 Days to secure items</div>
                    </div>
                    <div class="timeline-item mb-3">
                        <div class="fw-bold text-dark">Transit</div>
                        <div class="text-muted">7-14 Business Days to Ghana</div>
                    </div>
                    <div class="timeline-item">
                        <div class="fw-bold text-dark">Clearing</div>
                        <div class="text-muted">2-3 Days Customs Handling</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="shipping-card">
                <div class="icon-box">
                    <i class="bi bi-tsunami"></i>
                </div>
                <h4 class="fw-bold">Ocean Freight</h4>
                <p class="text-muted small mb-4">The most cost-effective method for bulk accessories or non-urgent official wear.</p>
                <div class="timeline-box small">
                    <div class="timeline-item mb-3">
                        <div class="fw-bold text-dark">Consolidation</div>
                        <div class="text-muted">Weekly container loading</div>
                    </div>
                    <div class="timeline-item mb-3">
                        <div class="fw-bold text-dark">Vessel Transit</div>
                        <div class="text-muted">6 - 8 Weeks to Tema Port</div>
                    </div>
                    <div class="timeline-item">
                        <div class="fw-bold text-dark">Final Delivery</div>
                        <div class="text-muted">Direct to hub after clearing</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5 pt-5 align-items-center">
        <div class="col-lg-5">
            <h2 class="fw-bold text-dark mb-4">Important Notice for Pre-orders</h2>
            <p class="text-muted mb-4">
                Since we deal with authentic luxury items, pre-order timelines begin only after payment verification is completed. External factors like international courier delays or customs inspections may slightly alter these dates.
            </p>
            <div class="d-flex gap-3">
                <div class="p-3 bg-light rounded-4 border flex-fill">
                    <div class="fw-bold text-dark">Tracking</div>
                    <div class="small text-muted">Real-time status updates via your dashboard.</div>
                </div>
                <div class="p-3 bg-light rounded-4 border flex-fill">
                    <div class="fw-bold text-dark">Support</div>
                    <div class="small text-muted">24/7 assistance for global tracking.</div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 offset-lg-1">
            <div class="premium-table table-responsive">
                <table class="table m-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>By Air</th>
                            <th>By Sea</th>
                        </tr>
                    </thead>
                    <tbody class="small fw-medium">
                        <tr>
                            <td>Sneakers</td>
                            <td class="text-success">Recommended</td>
                            <td class="text-muted">Not Available</td>
                        </tr>
                        <tr>
                            <td>Watches</td>
                            <td class="text-success">Recommended</td>
                            <td class="text-muted">Not Available</td>
                        </tr>
                        <tr>
                            <td>Suits/Official</td>
                            <td>Available</td>
                            <td>Available</td>
                        </tr>
                        <tr>
                            <td>Accessories</td>
                            <td>Available</td>
                            <td class="text-primary">Best Value</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

</body>
</html>