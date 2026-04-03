<?php
session_start();
require_once __DIR__ . "/../config/db.php";

// ✅ Load your Mailer Helper (Adjust this path if your mailer.php is in a different folder!)
$mailerPath = __DIR__ . "/../helpers/mailer.php";
if (file_exists($mailerPath)) {
    require_once $mailerPath;
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Automatically create the table so the page NEVER throws a 500 error!
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('pending', 'replied', 'closed') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    die("Database Error: Could not verify support table.");
}

// Handle Reply Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reply_message'])) {
    $messageId = (int) $_POST['message_id'];
    $replyText = trim($_POST['reply_text']);

    if (!empty($replyText)) {
        $stmt = $pdo->prepare("SELECT * FROM support_messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $msgData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($msgData) {
            $to = $msgData['email'];
            $subject = "Re: " . $msgData['subject'];
            $title = "Support Reply";
            
            // Build the beautiful email body for your sendMail function
            $emailBody = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2 style='color: #0f172a; margin-bottom: 20px;'>Hello {$msgData['name']},</h2>
                    <p style='font-size: 16px; line-height: 1.6;'>" . nl2br(htmlspecialchars($replyText)) . "</p>
                    <br>
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                    <div style='background-color: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;'>
                        <p style='font-size: 13px; color: #64748b; margin-top: 0; text-transform: uppercase; font-weight: bold;'>Your Original Message:</p>
                        <p style='font-size: 14px; color: #475569; font-style: italic; margin-bottom: 0;'>" . nl2br(htmlspecialchars($msgData['message'])) . "</p>
                    </div>
                </div>
            ";

            try {
                // Send Email using your robust SMTP Mailer function
                if (function_exists('sendMail')) {
                    sendMail($to, $subject, $title, $emailBody);
                } else {
                    // Fallback to native mail if sendMail is missing (just in case)
                    $domain = $_SERVER['HTTP_HOST'];
                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                    $headers .= "From: ShirtifyHub Support <support@{$domain}>\r\n";
                    @mail($to, $subject, $emailBody, $headers);
                }

                // Mark as replied in the database
                $update = $pdo->prepare("UPDATE support_messages SET status = 'replied' WHERE id = ?");
                $update->execute([$messageId]);

                $_SESSION['flash_success'] = "Reply sent successfully to " . htmlspecialchars($msgData['email']);
            } catch (Exception $e) {
                $_SESSION['flash_error'] = "Failed to send email. Check your SMTP settings.";
            }
            
        } else {
            $_SESSION['flash_error'] = "Message record not found.";
        }
    } else {
        $_SESSION['flash_error'] = "You cannot send an empty reply.";
    }
    
    header("Location: messages.php");
    exit;
}

// Fetch Messages (Pending float to the top)
$stmt = $pdo->query("SELECT * FROM support_messages ORDER BY FIELD(status, 'pending') DESC, created_at DESC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Pending Count for Navbar
$msgStmt = $pdo->query("SELECT COUNT(*) FROM support_messages WHERE status = 'pending'");
$pending_messages = $msgStmt->fetchColumn() ?: 0;

// Clear flash messages
$success = $_SESSION['flash_success'] ?? null;
$error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets | ShirtifyHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --admin-dark: #0f172a; 
            --admin-bg: #f8fafc;
            --border-light: #e2e8f0;
            --accent-blue: #6366f1;
        }
        body { 
            background-color: var(--admin-bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #334155;
        }

        /* Top Navbar */
        .admin-nav {
            background: #ffffff;
            border-bottom: 1px solid var(--border-light);
            padding: 1.2rem 0;
        }
        .nav-brand { font-weight: 800; letter-spacing: -0.5px; color: var(--admin-dark); text-decoration: none; font-size: 1.4rem; }
        .btn-logout { background: #fee2e2; color: #ef4444; border: none; padding: 8px 20px; font-weight: 700; border-radius: 50px; font-size: 0.85rem; transition: 0.2s; }
        .btn-logout:hover { background: #f87171; color: white; }

        .card-premium {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 20px;
            padding: 1.8rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
        }

        .table-premium thead th {
            background: #fcfcfd;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-top: none;
            padding: 1rem;
            border-bottom: 1px solid var(--border-light);
        }
        .table-premium tbody td { padding: 1.2rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        
        .status-badge { padding: 6px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-pending { background: #fffbeb; color: #b45309; border: 1px solid #fef3c7; }
        .badge-replied { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        
        .msg-preview { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #64748b; font-size: 0.85rem; }
        
        .btn-reply { font-size: 0.8rem; font-weight: 600; padding: 8px 16px; border-radius: 8px; background: var(--admin-dark); color: #fff; border: none; transition: 0.2s; }
        .btn-reply:hover { background: #1e293b; transform: translateY(-2px); box-shadow: 0 4px 6px rgba(15,23,42,0.2); color: #fff; }
        .btn-view { background: #f1f5f9; color: #475569; }
        .btn-view:hover { background: #e2e8f0; color: #0f172a; box-shadow: none; transform: translateY(-2px); }

        /* Modal Styling */
        .modal-content { border-radius: 16px; border: none; }
        .modal-header { border-bottom: 1px solid #f1f5f9; padding: 20px 24px; }
        .msg-bubble { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; margin-bottom: 20px; }
        .form-control-custom { border-radius: 10px; padding: 12px 16px; border: 1.5px solid #cbd5e1; font-size: 0.95rem; }
        .form-control-custom:focus { border-color: var(--admin-dark); box-shadow: 0 0 0 3px rgba(15,23,42,0.1); outline: none; }
    </style>
</head>
<body>

    <nav class="admin-nav sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="nav-brand" href="index.php">SHIRTIFYHUB <span class="text-muted fw-light">ADMIN</span></a>
            <div class="d-flex align-items-center gap-3">
                <a href="messages.php" class="text-dark position-relative text-decoration-none me-2" title="Support Messages">
                    <i class="bi bi-envelope fs-4"></i>
                    <?php if ($pending_messages > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger pulse-badge" style="font-size: 0.55rem; border: 2px solid white;">
                            <?= $pending_messages ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="../index.php" target="_blank" class="btn btn-light border btn-sm px-4 rounded-pill fw-bold text-dark d-none d-sm-inline-flex">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Live Store
                </a>
                <a href="logout.php" class="btn-logout text-decoration-none">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
            <div>
                <a href="index.php" class="text-decoration-none text-muted small fw-bold mb-2 d-inline-block"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
                <h1 class="fw-bold text-dark mb-1" style="letter-spacing: -1.5px;">Support Tickets</h1>
                <p class="text-muted mb-0">Manage and reply to customer inquiries.</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 alert-dismissible fade show fw-bold">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 alert-dismissible fade show fw-bold">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card-premium p-0 overflow-hidden">
            <?php if (empty($messages)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-envelope-check text-muted opacity-25 d-block mb-3" style="font-size: 4rem;"></i>
                    <h5 class="fw-bold text-secondary">Inbox Zero! 🎉</h5>
                    <p class="text-muted small">You have no customer support messages right now.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-premium mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Customer</th>
                                <th>Subject</th>
                                <th>Preview</th>
                                <th>Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($msg['name']) ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($msg['email']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?= htmlspecialchars($msg['subject']) ?></div>
                                    </td>
                                    <td>
                                        <div class="msg-preview" title="<?= htmlspecialchars($msg['message']) ?>">
                                            <?= htmlspecialchars($msg['message']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-secondary small fw-medium"><?= date("M d, Y", strtotime($msg['created_at'])) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($msg['status'] === 'pending'): ?>
                                            <span class="status-badge badge-pending">Pending</span>
                                        <?php else: ?>
                                            <span class="status-badge badge-replied">Replied</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="button" class="btn-reply <?= $msg['status'] === 'replied' ? 'btn-view' : '' ?>" 
                                                onclick="openReplyModal(
                                                    <?= $msg['id'] ?>, 
                                                    '<?= htmlspecialchars(addslashes($msg['name'])) ?>', 
                                                    '<?= htmlspecialchars(addslashes($msg['email'])) ?>', 
                                                    '<?= htmlspecialchars(addslashes($msg['subject'])) ?>', 
                                                    `<?= htmlspecialchars(addslashes($msg['message'])) ?>`,
                                                    '<?= $msg['status'] ?>'
                                                )">
                                            <?php if ($msg['status'] === 'pending'): ?>
                                                <i class="bi bi-reply-fill me-1"></i> Reply
                                            <?php else: ?>
                                                <i class="bi bi-eye-fill me-1"></i> View
                                            <?php endif; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="replyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-envelope-open me-2" style="color: var(--accent-blue);"></i> Support Ticket
                    </h5>
                    <button type="button" class="btn-close bg-light rounded-circle p-2" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST" id="replyForm">
                    <div class="modal-body p-4">
                        <input type="hidden" name="message_id" id="modalMessageId">
                        
                        <div class="d-flex justify-content-between mb-2">
                            <div class="small text-muted fw-bold text-uppercase">From: <span id="modalCustomerName" class="text-dark"></span> (<span id="modalCustomerEmail"></span>)</div>
                            <div id="modalStatusBadge"></div>
                        </div>
                        
                        <div class="fw-bold text-dark mb-3" style="font-size: 1.1rem;" id="modalSubject"></div>
                        
                        <div class="msg-bubble shadow-sm">
                            <p class="mb-0 text-secondary" id="modalMessageBody" style="line-height: 1.6; white-space: pre-wrap;"></p>
                        </div>

                        <div id="replyArea">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2"><i class="bi bi-pencil-square me-1"></i> Your Reply</label>
                            <textarea name="reply_text" class="form-control form-control-custom" rows="6" placeholder="Type your response here... It will be emailed directly to the customer." required></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0" id="replyFooter">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reply_message" class="btn btn-reply rounded-pill px-4" id="submitReplyBtn">
                            <i class="bi bi-send-fill me-2"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openReplyModal(id, name, email, subject, message, status) {
            document.getElementById('modalMessageId').value = id;
            document.getElementById('modalCustomerName').innerText = name;
            document.getElementById('modalCustomerEmail').innerText = email;
            document.getElementById('modalSubject').innerText = subject;
            document.getElementById('modalMessageBody').innerText = message;
            
            const badge = document.getElementById('modalStatusBadge');
            const replyArea = document.getElementById('replyArea');
            const replyFooter = document.getElementById('replyFooter');
            
            if (status === 'replied') {
                badge.innerHTML = '<span class="status-badge badge-replied">Already Replied</span>';
                replyArea.style.display = 'none';
                replyArea.querySelector('textarea').removeAttribute('required');
                replyFooter.style.display = 'none';
            } else {
                badge.innerHTML = '<span class="status-badge badge-pending">Needs Reply</span>';
                replyArea.style.display = 'block';
                replyArea.querySelector('textarea').value = ''; 
                replyArea.querySelector('textarea').setAttribute('required', 'required');
                replyFooter.style.display = 'flex';
            }

            const modal = new bootstrap.Modal(document.getElementById('replyModal'));
            modal.show();
        }
    </script>
</body>
</html>