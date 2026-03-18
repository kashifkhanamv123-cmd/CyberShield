<?php
echo "Current directory: " . __DIR__ . "<br>";
echo "Dashboard.php status: " . (file_exists(__DIR__ . '/dashboard.php') ? "Exists" : "Missing") . "<br>";
echo "Settings.php status: " . (file_exists(__DIR__ . '/settings.php') ? "Exists" : "Missing") . "<br>";
echo "Parent uploads status: " . (is_dir(__DIR__ . '/../uploads') ? "Exists" : "Missing") . "<br>";
?>
