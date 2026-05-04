<?php
/**
 * Gemini API Chatbot Backend
 * Handles POST requests and communicates with Google Gemini API via cURL.
 */

// Allow cross-origin requests (for development)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// 1. Define your API Key (Replace with your actual key)
define('GEMINI_API_KEY', 'AIzaSyDz1yNeYCnx0QfV-n_GmN7_5wxlwEqoBPI');

// 2. Accept POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$userMessage = isset($data['message']) ? trim($data['message']) : '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'Message is required']);
    exit;
}

if (GEMINI_API_KEY === 'YOUR_API_KEY' || empty(GEMINI_API_KEY)) {
    echo json_encode(['error' => 'Please set your Google Gemini API Key in api.php']);
    exit;
}

// 3. Prepare Gemini API Request
$url = "https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent?key=" . GEMINI_API_KEY;

$payload = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => $userMessage]
            ]
        ]
    ],
    "system_instruction" => [
        "parts" => [
            ["text" => "You are Luna, an elite AI security assistant for the CyberShield platform. Your tone is professional, friendly, and expert. You help users navigate the platform, understand security concepts, and provide guidance on cybersecurity labs. Use emojis occasionally to stay friendly."]
        ]
    ]
];

// 4. Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for SSL certificate error in local dev
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

// 5. Execute Request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// 6. Handle Response
if ($error) {
    echo json_encode(['error' => 'cURL Error: ' . $error]);
    exit;
}

if ($httpCode !== 200) {
    $errorData = json_decode($response, true);
    $message = isset($errorData['error']['message']) ? $errorData['error']['message'] : 'API Error';
    echo json_encode(['error' => 'Gemini API Error (' . $httpCode . '): ' . $message]);
    exit;
}

$responseData = json_decode($response, true);

// Extract the bot's reply from Gemini's nested response structure
if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    $botReply = $responseData['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode(['reply' => $botReply]);
} else {
    echo json_encode(['error' => 'Invalid response structure from Gemini API']);
}
