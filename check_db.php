<?php
include("config/db.php");
$res = $conn->query("DESCRIBE phishing_campaigns");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
$res = $conn->query("DESCRIBE phishing_events");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
