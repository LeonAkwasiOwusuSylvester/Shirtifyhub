<?php
session_start();
require 'db_connect.php';

// Send them back to the start if they skipped the first step
if (!isset($_SESSION['auth_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['auth_email'];

if (isset($_POST['login'])) {
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $hashed_password);
    $stmt->fetch();

    if (password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $id;
        unset($_SESSION['auth_email']); // Clear the temporary email session
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Incorrect password.";
    }
    $stmt->close();
}
?>

<form action="login_step2.php" method="POST">
    <h2>Login</h2>
    <p>Welcome back, <?php echo htmlspecialchars($email); ?></p>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="login">Login</button>
</form>