<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$message = "";

if (isset($_POST['reset'])) {

    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
    } else {

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $update = $conn->prepare("UPDATE users SET reset_token=?, reset_expiry=? WHERE email=?");
            $update->bind_param("sss", $token, $expiry, $email);
            $update->execute();

            $resetLink = "http://localhost/yourproject/auth/reset-password.php?token=" . $token;

            $message = "Password reset link generated (for demo): <br>" . $resetLink;
        } else {
            $message = "If email exists, reset link sent.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
</head>
<body>

<h2>Forgot Password</h2>

<form method="POST">
    <input type="email" name="email" required placeholder="Enter your email">
    <button name="reset">Generate Reset Link</button>
</form>

<p><?php echo $message ?? ""; ?></p>

</body>
</html>