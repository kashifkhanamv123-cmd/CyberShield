<?php
include("../../config/db.php");

$campaign_id = $_GET['id'];

$conn->query("INSERT INTO phishing_events 
    (campaign_id, event_type, target_email)
    VALUES ($campaign_id, 'click', 'employee@test.com')");
?>

<h2>This was a phishing simulation.</h2>
<p>Your action has been recorded for training purposes.</p>