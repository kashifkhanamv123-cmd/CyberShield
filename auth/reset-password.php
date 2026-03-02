<?php
require_once __DIR__ . '/../config/db.php';

$error = "";
$success = "";

if (!isset($_GET['token'])) {
    die("Invalid request.");
}

$token = $_GET['token'];

$stmt = $conn->prepare("SELECT id FROM users WHERE reset_token=? AND reset_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Invalid or expired token.");
}

$user = $result->fetch_assoc();

if (isset($_POST['update'])) {

    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expiry=NULL WHERE id=?");
        $update->bind_param("si", $hashed, $user['id']);
        $update->execute();

        $success = "Password updated successfully!";
    }
}
?>