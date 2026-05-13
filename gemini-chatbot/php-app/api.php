<?php
/**
 * CyberShield Assistant Backend (Offline/Scripted)
 * This file is now deprecated for the chatbot as logic has been moved to frontend JS 
 * to support offline functionality.
 */

header("Content-Type: application/json");

echo json_encode([
    'status' => 'offline',
    'message' => 'Chatbot has been migrated to a fully client-side scripted assistant for offline support.'
]);
exit;