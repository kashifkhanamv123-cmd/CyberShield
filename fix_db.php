<?php
include("config/db.php");

// Add landing_image to phishing_campaigns
$conn->query("ALTER TABLE phishing_campaigns ADD COLUMN landing_image VARCHAR(255) DEFAULT NULL");

// Add attacker_ip to phishing_events
$conn->query("ALTER TABLE phishing_events ADD COLUMN attacker_ip VARCHAR(50) DEFAULT NULL");

echo "Database schema updated successfully.\n";
