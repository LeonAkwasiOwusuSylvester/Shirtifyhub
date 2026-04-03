<?php
// Make sure this path correctly points to your Shirtifyhub db connection file!
require_once __DIR__ . "/app/config/db.php"; 

$adminEmail = "leonakwasiowususylvester@gmail.com"; // e.g., "admin@shirtifyhub.com"
$newPassword = "admin123";
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    // Note: Change 'password_hash' to just 'password' if that is what your column is named in Shirtifyhub!
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->execute([$hashedPassword, $adminEmail]);
    
    echo "<h2>✅ Success!</h2>";
    echo "Your password has been reset to: <strong>admin123</strong><br><br>";
    echo "<span style='color:red;'>⚠️ IMPORTANT: Delete this rescue.php file immediately so hackers can't use it!</span>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>