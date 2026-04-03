<?php
// help.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/config/db.php"; // Adjust to his actual DB path

$successMsg = '';
$errorMsg = '';

// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_help'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $errorMsg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Please enter a valid email address.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO support_messages (name, email, subject, message, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$name, $email, $subject, $message]);
            
            $successMsg = "Your message has been sent successfully! Our team will reply to your email within 24 hours.";
        } catch (PDOException $e) {
            $errorMsg = "Something went wrong. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center | ShirtifyHub</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #334155;
        }
        
        .hero-section {
            background-color: #0f172a; /* Sleek dark blue for ShirtifyHub */
            color: white;
            padding: 4rem 0 5rem 0;
            text-align: center;
        }
        
        .contact-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            padding: 2.5rem;
            margin-top: -3.5rem; /* Pulls the card up over the dark hero section */
        }
        
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1.5px solid #cbd5e1;
            font-size: 0.95rem;
            background-color: #f8fafc;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0f172a;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.1);
        }
        
        .btn-submit {
            background-color: #0f172a;
            color: white;
            font-weight: 700;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            transition: 0.2s;
            width: 100%;
        }
        .btn-submit:hover {
            background-color: #1e293b;
            color: white;
            transform: translateY(-2px);
        }
        
        .faq-accordion .accordion-button:not(.collapsed) {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            box-shadow: none;
        }
        .faq-accordion .accordion-button:focus {
            box-shadow: none;
            border-color: rgba(0,0,0,.125);
        }
        
        .contact-info-box {
            background: #f1f5f9;
            padding: 2rem;
            border-radius: 12px;
            height: 100%;
            border: 1px solid #e2e8f0;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1.5rem;
        }
        
        .info-icon {
            width: 45px;
            height: 45px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #0f172a;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            flex-shrink: 0;
        }
    </style>
</head>
<body>

<div class="hero-section">
    <div class="container">
        <h1 class="fw-bold mb-3">How can we help you?</h1>
        <p class="opacity-75 fs-5 mb-0">We are here to answer your questions about sizing, orders, and more.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="contact-card">
                
                <?php if($successMsg): ?>
                    <div class="alert alert-success fw-bold border-0 shadow-sm"><i class="bi bi-check-circle-fill me-2"></i><?= $successMsg ?></div>
                <?php endif; ?>
                <?php if($errorMsg): ?>
                    <div class="alert alert-danger fw-bold border-0 shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $errorMsg ?></div>
                <?php endif; ?>

                <div class="row g-5">
                    
                    <div class="col-md-7">
                        <h4 class="fw-bold mb-4 text-dark">Send us a Message</h4>
                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" required placeholder="Leon Dwayne">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" required placeholder="leon@example.com">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-muted text-uppercase">What is this regarding?</label>
                                    <select name="subject" class="form-select">
                                        <option value="Order Tracking">Order Tracking</option>
                                        <option value="Returns & Exchanges">Returns & Exchanges</option>
                                        <option value="Sizing Question">Sizing Question</option>
                                        <option value="General Inquiry">General Inquiry</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Your Message <span class="text-danger">*</span></label>
                                    <textarea name="message" class="form-control" rows="5" required placeholder="How can we help you today?"></textarea>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" name="submit_help" class="btn btn-submit">
                                        <i class="bi bi-send-fill me-2"></i> Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-5">
                        <div class="contact-info-box">
                            <h5 class="fw-bold mb-4 text-dark">Get in Touch</h5>
                            
                            <div class="info-item">
                                <div class="info-icon"><i class="bi bi-envelope-paper"></i></div>
                                <div>
                                    <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.75rem;">Email Support</div>
                                    <div class="fw-bold text-dark">support@shirtifyhub.com</div>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon"><i class="bi bi-telephone"></i></div>
                                <div>
                                    <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.75rem;">Phone</div>
                                    <div class="fw-bold text-dark"> +233 59 772 7542</div>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon"><i class="bi bi-clock"></i></div>
                                <div>
                                    <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.75rem;">Response Time</div>
                                    <div class="fw-bold text-dark">Within 24 hours</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="row justify-content-center mt-5">
        <div class="col-xl-8">
            <h4 class="fw-bold text-center mb-4 text-dark">Frequently Asked Questions</h4>
            <div class="accordion faq-accordion shadow-sm border rounded-4 overflow-hidden" id="faqAccordion">
                
                <div class="accordion-item border-0 border-bottom mb-0 rounded-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed px-4 py-3 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How long does shipping take?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body px-4 text-secondary">
                            Standard shipping usually takes 3-4 business weeks. Express shipping arrives in 1- 2 business weeks. You will receive a tracking number directly to your email as soon as your order ships!
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-0 border-bottom mb-0 rounded-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed px-4 py-3 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            What is your return policy?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body px-4 text-secondary">
                            We accept returns within 7 days of delivery. Shirts must be unworn, unwashed, and have the original tags attached to be eligible for a full refund.
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-0 mb-0 rounded-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed px-4 py-3 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            How do I know my shirt size?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body px-4 text-secondary">
                            Each product page features a detailed size guide. We recommend measuring your favorite fitting shirt flat on a table and comparing it to our size chart to ensure the perfect fit.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>