<?php
$host = "localhost";
$user = "root";
$pass = "kashifkhan";   // put your MySQL password if you have one
$db   = "cybershield_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>