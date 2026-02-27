<?php
session_start();
include("../../config/db.php");

if(!isset($_SESSION['user_id'])){
    header("Location: ../../auth/login.php");
    exit();
}

if(isset($_POST['launch'])){

    $user_id = $_SESSION['user_id'];
    $sender = $_POST['sender_name'];
    $spoof = $_POST['spoof_email'];
    $subject = $_POST['subject'];
    $body = $_POST['body'];

    // Insert campaign
    $stmt = $conn->prepare("INSERT INTO phishing_campaigns 
        (user_id, sender_name, spoof_email, subject, body)
        VALUES (?, ?, ?, ?, ?)");

    $stmt->bind_param("issss", $user_id, $sender, $spoof, $subject, $body);
    $stmt->execute();

    $campaign_id = $stmt->insert_id;

    header("Location: analytics.php?id=$campaign_id");
    exit();
}
?>