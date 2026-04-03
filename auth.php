<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/config/db.php";

// Load the mailer helper
if(file_exists(__DIR__ . "/helpers/mailer.php")) {
    require_once __DIR__ . "/helpers/mailer.php";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $_SESSION["error"] = "Please fill in all fields.";
        header("Location: login.php");
        exit;
    }

    try {
        // Fetch the user from the database
        $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify the password matches the hash in the database
        if ($user && password_verify($password, $user['password'])) {
            
            // Passwords match! Generate OTP for Login
            $otp = random_int(100000, 999999);
            
            // Set the unified session variables
            $_SESSION['temp_user_id'] = $user['id'];
            $_SESSION['pending_otp']  = $otp;
            $_SESSION['auth_flow']    = 'login'; // Tells verify-otp to log them in after

            // Send the email
            if (function_exists('sendMail')) {
                $recipientName = !empty($user['name']) ? $user['name'] : 'Customer';
                $subject = "Login Verification Code - Shirtifyhub";
                $title   = "Security Alert";
                $message = "Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,<br><br>Your secure login code is:<br><br><h2 style='text-align:center; letter-spacing: 5px; color: #0f172a; padding: 20px; background: #f1f5f9; border-radius: 10px;'>" . $otp . "</h2><br>Do not share this code with anyone.";
                
                sendMail($email, $subject, $title, $message);
            }

            // Send them to the OTP verification page
            header("Location: verify-otp.php");
            exit;

        } else {
            // Invalid credentials
            $_SESSION["error"] = "Invalid email or password.";
            header("Location: login.php");
            exit;
        }

    } catch (PDOException $e) {
        $_SESSION["error"] = "Database error. Please try again.";
        header("Location: login.php");
        exit;
    }
} else {
    // If someone tries to access auth.php directly without submitting the form
    header("Location: login.php");
    exit;
}
?>