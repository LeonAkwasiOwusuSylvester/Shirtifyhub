<?php
session_start();
require_once __DIR__ . "/config/db.php";

// If they somehow got here without typing an email first, send them back
if (!isset($_SESSION['auth_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['auth_email'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, name, email, password) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$username, $name, $email, $password])) {
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['name'] = $name;
            unset($_SESSION['auth_email']);
            header("Location: index.php");
            exit();
        } else {
            $error = "Error creating account. Please try again.";
        }
    } catch (PDOException $e) {
        $error = "An error occurred. This email might already be registered.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Shirtifyhub</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        /* ── RESET & BASE ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── FULL-SCREEN LAYOUT ── */
        .auth-layout {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: calc(100vh - 70px);
        }

        /* ── LEFT PANEL (Brand) ── */
        .auth-brand-panel {
            background-color: #0f172a;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 52px 52px 44px;
        }

        .auth-brand-panel::before {
            content: '';
            position: absolute;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            border: 80px solid rgba(37, 99, 235, 0.12);
            top: -120px;
            right: -120px;
            pointer-events: none;
        }

        .auth-brand-panel::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            border: 60px solid rgba(249, 115, 22, 0.1);
            bottom: -80px;
            left: -80px;
            pointer-events: none;
        }

        .blob-mid {
            position: absolute;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(37,99,235,0.15) 0%, transparent 70%);
            border-radius: 50%;
            top: 50%;
            left: 30%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        .dot-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,0.06) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
        }

        .brand-logo {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.6rem;
            color: #ffffff;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-logo .logo-dot {
            width: 10px;
            height: 10px;
            background: #f97316;
            border-radius: 50%;
            display: inline-block;
            margin-bottom: 2px;
        }

        .brand-hero {
            position: relative;
            z-index: 1;
        }

        /* Checklist benefits */
        .benefit-list {
            list-style: none;
            padding: 0;
            margin: 24px 0 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .benefit-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            color: #94a3b8;
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .benefit-icon {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: rgba(37, 99, 235, 0.18);
            border: 1px solid rgba(37, 99, 235, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            font-size: 0.75rem;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .benefit-list li strong {
            display: block;
            color: #e2e8f0;
            font-weight: 600;
            font-size: 0.88rem;
            margin-bottom: 1px;
        }

        .brand-hero h2 {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(1.8rem, 3vw, 2.6rem);
            color: #ffffff;
            line-height: 1.18;
            margin-bottom: 10px;
        }

        .brand-hero h2 em {
            font-style: normal;
            color: #f97316;
        }

        .brand-hero > p {
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.6;
        }

        /* Email chip */
        .email-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 100px;
            padding: 6px 14px 6px 8px;
            font-size: 0.8rem;
            color: #cbd5e1;
            margin-top: 14px;
        }

        .email-chip .chip-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(37,99,235,0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            font-size: 0.7rem;
        }

        /* Stats row */
        .brand-stats {
            display: flex;
            gap: 28px;
            position: relative;
            z-index: 1;
            padding-top: 32px;
            border-top: 1px solid rgba(255,255,255,0.07);
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 4px;
            letter-spacing: 0.3px;
        }

        /* ── RIGHT PANEL (Form) ── */
        .auth-form-panel {
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 52px 40px;
        }

        .auth-form-inner {
            width: 100%;
            max-width: 400px;
        }

        /* Step indicator */
        .step-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 36px;
        }

        .step-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #e2e8f0;
        }

        .step-dot.done {
            background: #2563eb;
            opacity: 0.4;
        }

        .step-dot.active {
            width: 24px;
            border-radius: 4px;
            background: #2563eb;
        }

        /* Form heading */
        .form-heading {
            margin-bottom: 28px;
        }

        .form-heading h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.9rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
            margin-bottom: 8px;
        }

        .form-heading p {
            color: #94a3b8;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Email badge inside form */
        .email-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 5px 12px;
            font-size: 0.82rem;
            color: #1d4ed8;
            font-weight: 500;
            margin-top: 8px;
        }

        .email-badge i {
            font-size: 0.75rem;
        }

        /* Error alert */
        .auth-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-left: 3px solid #f43f5e;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 22px;
            font-size: 0.875rem;
            color: #be123c;
            animation: slideDown 0.25s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .auth-alert i {
            font-size: 1rem;
            margin-top: 1px;
            flex-shrink: 0;
        }

        /* Input group */
        .input-group-auth {
            position: relative;
            margin-bottom: 14px;
        }

        .field-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
            letter-spacing: 0.2px;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
            z-index: 2;
        }

        .auth-input {
            width: 100%;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px 16px 15px 44px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            color: #0f172a;
            outline: none;
            transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
        }

        .auth-input::placeholder {
            color: #cbd5e1;
        }

        .auth-input:focus {
            border-color: #2563eb;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
        }

        /* Password toggle */
        .input-wrapper {
            position: relative;
        }

        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            font-size: 1rem;
            transition: color 0.2s;
        }

        .toggle-pw:hover {
            color: #475569;
        }

        /* Password strength bar */
        .strength-bar-wrap {
            display: flex;
            gap: 5px;
            margin-top: 8px;
        }

        .strength-seg {
            flex: 1;
            height: 3px;
            border-radius: 2px;
            background: #e2e8f0;
            transition: background 0.3s ease;
        }

        .strength-seg.weak   { background: #f43f5e; }
        .strength-seg.fair   { background: #f97316; }
        .strength-seg.good   { background: #2563eb; }
        .strength-seg.strong { background: #22c55e; }

        .strength-label {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 5px;
        }

        /* CTA Button */
        .btn-auth {
            width: 100%;
            background: #0f172a;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.97rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            letter-spacing: 0.2px;
        }

        .btn-auth:hover {
            background: #1e293b;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.2);
        }

        .btn-auth:active { transform: translateY(0); }

        .btn-auth .btn-arrow {
            width: 22px;
            height: 22px;
            background: rgba(255,255,255,0.15);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: transform 0.2s ease;
        }

        .btn-auth:hover .btn-arrow { transform: translateX(3px); }

        /* Back link */
        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 22px;
            font-size: 0.82rem;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .back-link:hover { color: #2563eb; }

        /* Terms note */
        .auth-footer-note {
            margin-top: 14px;
            font-size: 0.78rem;
            color: #cbd5e1;
            text-align: center;
            line-height: 1.6;
        }

        .auth-footer-note a {
            color: #94a3b8;
            text-decoration: none;
        }

        .auth-footer-note a:hover { color: #2563eb; }

        /* ── MOBILE ── */
        @media (max-width: 900px) {
            .auth-layout { grid-template-columns: 1fr; }
            .auth-brand-panel { display: none; }
            .auth-form-panel {
                padding: 40px 24px;
                background: #f8fafc;
                align-items: flex-start;
                padding-top: 60px;
            }
            .auth-form-inner { max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="auth-layout">

    <!-- ══ LEFT: Brand Panel ══ -->
    <div class="auth-brand-panel">
        <div class="dot-grid"></div>
        <div class="blob-mid"></div>

        <!-- Logo -->
        <div class="brand-logo">
            <span class="logo-dot"></span>
            Shirtifyhub
        </div>

        <!-- Hero copy -->
        <div class="brand-hero">
            <h2>
                Almost<br>
                <em>there.</em>
            </h2>
            <p>One last step before you start exploring our full collection.</p>

            <!-- Email chip -->
            <div class="email-chip">
                <span class="chip-dot"><i class="bi bi-envelope-fill"></i></span>
                <?= htmlspecialchars($email) ?>
            </div>

            <!-- Benefits list -->
            <ul class="benefit-list">
                <li>
                    <div class="benefit-icon"><i class="bi bi-bag-check-fill"></i></div>
                    <div>
                        <strong>Track your orders</strong>
                        Real-time updates from checkout to doorstep.
                    </div>
                </li>
                <li>
                    <div class="benefit-icon"><i class="bi bi-heart-fill"></i></div>
                    <div>
                        <strong>Save your wishlist</strong>
                        Bookmark favourites and come back anytime.
                    </div>
                </li>
                <li>
                    <div class="benefit-icon"><i class="bi bi-tag-fill"></i></div>
                    <div>
                        <strong>Exclusive member deals</strong>
                        Early access to sales and new drops.
                    </div>
                </li>
            </ul>
        </div>

        <!-- Stats row -->
        <div class="brand-stats">
            <div class="stat-item">
                <div class="stat-value">12k+</div>
                <div class="stat-label">Happy customers</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">500+</div>
                <div class="stat-label">Designs available</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">4.9 ★</div>
                <div class="stat-label">Average rating</div>
            </div>
        </div>
    </div>

    <!-- ══ RIGHT: Form Panel ══ -->
    <div class="auth-form-panel">
        <div class="auth-form-inner">

            <!-- Step dots — step 2 of 3 active -->
            <div class="step-indicator">
                <div class="step-dot done"></div>
                <div class="step-dot active"></div>
                <div class="step-dot"></div>
            </div>

            <!-- Heading -->
            <div class="form-heading">
                <h1>Create your account</h1>
                <p>You're registering with</p>
                <div class="email-badge">
                    <i class="bi bi-envelope-fill"></i>
                    <?= htmlspecialchars($email) ?>
                </div>
            </div>

            <!-- Error message -->
            <?php if ($error): ?>
                <div class="auth-alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Registration form -->
            <form action="register_step2.php" method="POST" id="registerForm">

                <!-- Full name -->
                <div class="input-group-auth">
                    <label class="field-label" for="name">Full Name</label>
                    <div class="input-wrapper">
                        <i class="bi bi-person input-icon"></i>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="auth-input"
                            placeholder="e.g. John Doe"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="input-group-auth">
                    <label class="field-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="auth-input"
                            placeholder="Min. 6 characters"
                            required
                            minlength="6"
                            oninput="updateStrength(this.value)"
                            style="padding-right: 44px;"
                        >
                        <button type="button" class="toggle-pw" onclick="togglePassword()" tabindex="-1">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    <!-- Strength bar -->
                    <div class="strength-bar-wrap" id="strengthBar">
                        <div class="strength-seg" id="seg1"></div>
                        <div class="strength-seg" id="seg2"></div>
                        <div class="strength-seg" id="seg3"></div>
                        <div class="strength-seg" id="seg4"></div>
                    </div>
                    <div class="strength-label" id="strengthLabel"></div>
                </div>

                <button type="submit" class="btn-auth">
                    Complete Registration
                    <span class="btn-arrow"><i class="bi bi-check-lg"></i></span>
                </button>
            </form>

            <!-- Back link -->
            <a href="login.php" class="back-link">
                <i class="bi bi-arrow-left"></i>
                Use a different email
            </a>

            <!-- Terms note -->
            <p class="auth-footer-note">
                By registering you agree to our
                <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
            </p>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ── Password visibility toggle ──
    function togglePassword() {
        const pw = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        if (pw.type === 'password') {
            pw.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            pw.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }

    // ── Password strength indicator ──
    function updateStrength(val) {
        const segs = ['seg1','seg2','seg3','seg4'];
        const label = document.getElementById('strengthLabel');

        let score = 0;
        if (val.length >= 6)  score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { label: '',         cls: ''       },
            { label: 'Weak',     cls: 'weak'   },
            { label: 'Fair',     cls: 'fair'   },
            { label: 'Good',     cls: 'good'   },
            { label: 'Strong',   cls: 'strong' },
        ];

        segs.forEach((id, i) => {
            const el = document.getElementById(id);
            el.className = 'strength-seg';
            if (i < score) el.classList.add(levels[score].cls);
        });

        label.textContent = val.length > 0 ? levels[score].label : '';
    }
</script>
</body>
</html>