<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

/** @var mysqli $conn */

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Gemini API Configuration
define('GEMINI_API_KEY', 'AIzaSyDz1yNeYCnx0QfV-n_GmN7_5wxlwEqoBPI');

try {
    $user_id = $_SESSION['user_id'];
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Empty message']);
        exit;
    }

    // Fetch User Name for Personalization
    $user_name = "Operator";
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $user_name = explode(' ', trim($row['name']))[0]; 
        }
        $stmt->close();
    }

    // 1. Prepare Gemini API Request
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . GEMINI_API_KEY;

    $payload = [
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["text" => $message]
                ]
            ]
        ],
        "system_instruction" => [
            "parts" => [
                ["text" => "You are Luna, the elite AI security assistant for the CyberShield platform. Your tone is professional, friendly, and expert. The user's name is $user_name. You help users navigate the platform, understand security concepts (like Phishing, DDoS, Malware, Brute Force), and provide guidance on cybersecurity labs. Use emojis occasionally to stay friendly. Keep your answers concise but helpful."]
            ]
        ]
    ];

    // 2. Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for SSL certificate error in local dev
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    // 3. Execute Request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // 4. Handle Response
    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errMsg = isset($errorData['error']['message']) ? $errorData['error']['message'] : 'API Error';
        throw new Exception("Gemini API Error ($httpCode): $errMsg");
    }

    $responseData = json_decode($response, true);
    $botReply = "";

    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $botReply = $responseData['candidates'][0]['content']['parts'][0]['text'];
    } else {
        throw new Exception("Invalid response structure from Gemini API");
    }

    // Log to database
    $stmt = $conn->prepare("INSERT INTO user_queries (user_id, query_text, solution_text, status) VALUES (?, ?, ?, 'resolved')");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $message, $botReply);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'reply' => $botReply,
        'timestamp' => date('H:i')
    ]);

} catch (Exception $e) {
    error_log("Chatbot Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
